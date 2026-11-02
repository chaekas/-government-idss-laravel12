<?php

namespace Tests\Feature;

use App\Models\AdministrativeArea;
use App\Models\DiseaseCase;
use App\Models\DiseaseCaseStatusHistory;
use App\Models\DiseaseCategory;
use App\Models\Farm;
use App\Models\Farmer;
use App\Models\Investigation;
use App\Models\InvestigationHistory;
use App\Models\Outbreak;
use App\Models\SurveillanceAlertRule;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class DiseaseSurveillanceTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        return User::create(['name'=>'Test '.$role,'email'=>$role.'-'.Str::random(8).'@example.test','password'=>'Correct-Horse-Battery-42!','role'=>$role,'is_active'=>true]);
    }

    private function farm(User $officer): Farm
    {
        $farmer=Farmer::create(['farmer_code'=>'FRM-'.Str::upper(Str::random(8)),'full_name'=>'Surveillance Farmer','region'=>'Dodoma','district'=>'Kondoa']);
        return Farm::create(['farm_code'=>'FARM-'.Str::upper(Str::random(8)),'farmer_id'=>$farmer->id,'name'=>'Surveillance Farm','region'=>'Dodoma','district'=>'Kondoa','latitude'=>-6.0,'longitude'=>35.0,'bird_count'=>100,'status'=>'active']);
    }

    private function case(User $reporter,Farm $farm,array $overrides=[]): DiseaseCase
    {
        return DiseaseCase::create(array_merge([
            'case_code'=>'CASE-'.Str::upper(Str::random(8)),'farm_id'=>$farm->id,'reported_by'=>$reporter->id,
            'disease_suspected'=>'Suspected poultry disease','affected_species'=>'Poultry','date_observed'=>now()->subDay()->toDateString(),
            'reported_at'=>now(),'symptoms'=>'Respiratory signs and reduced feed intake','birds_affected'=>8,'birds_dead'=>2,
            'severity'=>'high','status'=>'reported','latitude'=>-6.0,'longitude'=>35.0,
        ],$overrides));
    }

    public function test_case_reporting_captures_srs_fields_history_and_threshold_alerts(): void
    {
        $officer=$this->user('field_officer'); $regional=$this->user('regional_veterinary_officer'); $ministry=$this->user('mlf_officer');
        $farm=$this->farm($officer);
        SurveillanceAlertRule::create(['code'=>'HIGH_CASE','name'=>'High case alert','severity'=>'high','case_threshold'=>1,'within_hours'=>24,'notify_field_officers'=>true,'notify_regional_officers'=>true,'notify_ministry_officers'=>true,'is_active'=>true]);
        $this->actingAs($officer)->post(route('cases.store'),[
            'farm_id'=>$farm->id,'disease_suspected'=>'Newcastle disease','affected_species'=>'Chicken','date_observed'=>now()->subDay()->toDateString(),
            'symptoms'=>'Respiratory signs','birds_affected'=>10,'birds_dead'=>2,'severity'=>'critical',
            'latitude'=>-6.1,'longitude'=>35.1,'location_description'=>'Kondoa village',
            'attachments'=>[UploadedFile::fake()->create('field-photo.pdf',100,'application/pdf')],
        ])->assertRedirect(route('cases.index'));

        $case=DiseaseCase::firstOrFail();
        $this->assertSame('critical',$case->severity);
        $this->assertSame('Chicken',$case->affected_species);
        $this->assertNotNull($case->reported_at);
        $this->assertDatabaseHas('disease_case_status_history',['disease_case_id'=>$case->id,'to_status'=>'reported']);
        $this->assertCount(1,$case->attachments);
        $this->assertDatabaseHas('user_notifications',['user_id'=>$regional->id,'category'=>'surveillance']);
        $this->assertDatabaseHas('user_notifications',['user_id'=>$ministry->id,'category'=>'surveillance']);
    }

    public function test_case_report_cannot_be_edited_after_investigation_starts(): void
    {
        $officer=$this->user('field_officer'); $farm=$this->farm($officer); $case=$this->case($officer,$farm);
        Investigation::create(['investigation_code'=>'INV-'.Str::upper(Str::random(8)),'disease_case_id'=>$case->id,'assigned_to'=>$officer->id,'started_by'=>$officer->id,'investigated_at'=>now(),'status'=>'under_investigation','field_observations'=>'Inspection started.']);
        $this->actingAs($officer)->get(route('cases.edit',$case))->assertForbidden();
        $this->actingAs($officer)->put(route('cases.update',$case),[
            'farm_id'=>$farm->id,'disease_suspected'=>'Changed disease','affected_species'=>'Chicken','date_observed'=>now()->subDay()->toDateString(),
            'symptoms'=>'Signs','birds_affected'=>3,'birds_dead'=>0,'severity'=>'medium',
        ])->assertForbidden();
    }

    public function test_investigation_records_epidemiology_evidence_history_and_independent_approval_lock(): void
    {
        Storage::fake('local');
        $officer=$this->user('field_officer'); $regional=$this->user('regional_veterinary_officer');
        $farm=$this->farm($officer); $case=$this->case($officer,$farm);
        $this->actingAs($officer)->post(route('investigations.store'),[
            'disease_case_id'=>$case->id,'assigned_to'=>$officer->id,'investigated_at'=>now()->subDays(2)->toDateString(),
            'field_observations'=>'Clinical signs observed at the premises.','epidemiological_findings'=>'Recent introduction of birds.',
            'possible_source'=>'New birds from a market','transmission_route'=>'Movement of birds','biosecurity_observations'=>'Footbath not maintained',
            'immediate_control_measures'=>'Movement restricted','recommended_actions'=>'Collect diagnostic samples and strengthen biosecurity.',
            'attachments'=>[UploadedFile::fake()->create('investigation.pdf',120,'application/pdf')],
        ])->assertSessionHasErrors('investigated_at');

        $this->actingAs($officer)->post(route('investigations.store'),[
            'disease_case_id'=>$case->id,'assigned_to'=>$officer->id,'investigated_at'=>now()->toDateString(),
            'field_observations'=>'Clinical signs observed at the premises.','epidemiological_findings'=>'Recent introduction of birds.',
            'possible_source'=>'New birds from a market','transmission_route'=>'Movement of birds','biosecurity_observations'=>'Footbath not maintained',
            'immediate_control_measures'=>'Movement restricted','recommended_actions'=>'Collect diagnostic samples and strengthen biosecurity.',
            'attachments'=>[UploadedFile::fake()->create('investigation.pdf',120,'application/pdf')],
        ])->assertRedirect(route('investigations.index'));

        $investigation=Investigation::firstOrFail();
        $this->assertSame('New birds from a market',$investigation->possible_source);
        $this->assertCount(1,$investigation->attachments);
        $this->assertDatabaseHas('investigation_histories',['investigation_id'=>$investigation->id,'event_type'=>'created']);

        $this->actingAs($officer)->put(route('investigations.update',$investigation),[
            'assigned_to'=>$officer->id,'investigated_at'=>now()->toDateString(),'status'=>'completed',
            'field_observations'=>'Investigation complete.','epidemiological_findings'=>'Findings documented.',
            'possible_source'=>'New birds from a market','transmission_route'=>'Movement of birds',
            'biosecurity_observations'=>'Footbath not maintained','immediate_control_measures'=>'Movement restricted',
            'recommended_actions'=>'Continue surveillance and complete sample diagnostics.',
        ])->assertRedirect(route('investigations.index'));

        $this->actingAs($regional)->post(route('investigations.approve',$investigation),['approval_comments'=>'Reviewed and approved.'])->assertRedirect(route('investigations.index'));
        $this->assertNotNull($investigation->fresh()->approved_at);
        $this->assertSame('approved',$investigation->fresh()->status);
        $this->assertDatabaseHas('investigation_histories',['investigation_id'=>$investigation->id,'event_type'=>'approved']);
        $this->actingAs($officer)->put(route('investigations.update',$investigation),[
            'assigned_to'=>$officer->id,'investigated_at'=>now()->toDateString(),'status'=>'completed','field_observations'=>'Attempt to change',
        ])->assertForbidden();
    }

    public function test_outbreak_can_link_cases_and_recalculate_case_and_death_counts(): void
    {
        $officer=$this->user('field_officer'); $farm=$this->farm($officer); $case=$this->case($officer,$farm,['birds_affected'=>12,'birds_dead'=>3]);
        $this->actingAs($officer)->post(route('outbreaks.store'),[
            'disease_name'=>'Suspected Newcastle disease','region'=>'Dodoma','district'=>'Kondoa','start_date'=>now()->subDay()->toDateString(),
            'cases_count'=>99,'deaths_count'=>50,'severity'=>'high','case_ids'=>[$case->id],
        ])->assertRedirect(route('outbreaks.index'));
        $outbreak=Outbreak::firstOrFail();
        $this->assertSame(1,$outbreak->cases_count);
        $this->assertSame(3,$outbreak->deaths_count);
        $this->assertTrue($outbreak->diseaseCases()->whereKey($case->id)->exists());
        $this->actingAs($officer)->put(route('outbreaks.update',$outbreak),[
            'disease_name'=>$outbreak->disease_name,'region'=>'Dodoma','district'=>'Kondoa','start_date'=>$outbreak->start_date->toDateString(),
            'cases_count'=>1,'deaths_count'=>3,'severity'=>'high','status'=>'active','case_ids'=>[$case->id],
        ])->assertRedirect(route('outbreaks.index'));
        $this->assertNotNull($outbreak->fresh()->confirmed_at);
    }

    public function test_surveillance_analytics_page_is_available_to_regional_officers(): void
    {
        $officer=$this->user('field_officer'); $regional=$this->user('regional_veterinary_officer');
        $farm=$this->farm($officer); $this->case($officer,$farm);
        $this->actingAs($regional)->get(route('surveillance.index'))->assertOk()->assertSee('Disease surveillance analytics')->assertSee('denominator not yet available');
    }
}

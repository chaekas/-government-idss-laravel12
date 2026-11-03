<?php

namespace Tests\Feature;

use App\Models\AdministrativeArea;
use App\Models\DiseaseCase;
use App\Models\Farm;
use App\Models\Farmer;
use App\Models\Laboratory;
use App\Models\Sample;
use App\Models\User;
use App\Services\UserDataScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DataScopeTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        return User::create([
            'name' => 'Scope '.$role,
            'email' => $role.'-'.uniqid().'@example.test',
            'password' => 'Correct-Horse-Battery-42!',
            'role' => $role,
            'is_active' => true,
        ]);
    }

    public function test_region_assignment_includes_descendant_districts_but_not_other_regions(): void
    {
        $officer = $this->user('field_officer');
        $regionA = AdministrativeArea::create(['code' => 'REG-A', 'name' => 'Region A', 'area_type' => 'region', 'is_active' => true]);
        $districtA = AdministrativeArea::create(['parent_id' => $regionA->id, 'code' => 'DIST-A', 'name' => 'District A', 'area_type' => 'district', 'is_active' => true]);
        $regionB = AdministrativeArea::create(['code' => 'REG-B', 'name' => 'Region B', 'area_type' => 'region', 'is_active' => true]);
        $districtB = AdministrativeArea::create(['parent_id' => $regionB->id, 'code' => 'DIST-B', 'name' => 'District B', 'area_type' => 'district', 'is_active' => true]);
        $officer->administrativeAreas()->attach($regionA->id, ['effective_from' => now()->toDateString()]);

        $farmer = Farmer::create(['farmer_code' => 'FRM-SCOPE-'.Str::upper(Str::random(5)), 'full_name' => 'Scope Farmer', 'region' => 'Region A', 'district' => 'District A']);
        $farmA = Farm::create(['farm_code' => 'FARM-SCOPE-A', 'farmer_id' => $farmer->id, 'name' => 'Farm A', 'region' => 'Region A', 'district' => 'District A', 'administrative_area_id' => $districtA->id, 'bird_count' => 10]);
        $farmB = Farm::create(['farm_code' => 'FARM-SCOPE-B', 'farmer_id' => $farmer->id, 'name' => 'Farm B', 'region' => 'Region B', 'district' => 'District B', 'administrative_area_id' => $districtB->id, 'bird_count' => 10]);
        $caseA = DiseaseCase::create(['case_code' => 'CASE-SCOPE-A', 'farm_id' => $farmA->id, 'reported_by' => $officer->id, 'disease_suspected' => 'Disease A', 'date_observed' => now()->toDateString(), 'symptoms' => 'Symptoms', 'birds_affected' => 2, 'birds_dead' => 0, 'severity' => 'medium', 'status' => 'reported', 'administrative_area_id' => $districtA->id]);
        $caseB = DiseaseCase::create(['case_code' => 'CASE-SCOPE-B', 'farm_id' => $farmB->id, 'reported_by' => $officer->id, 'disease_suspected' => 'Disease B', 'date_observed' => now()->toDateString(), 'symptoms' => 'Symptoms', 'birds_affected' => 2, 'birds_dead' => 0, 'severity' => 'medium', 'status' => 'reported', 'administrative_area_id' => $districtB->id]);

        $visible = app(UserDataScope::class)->cases(DiseaseCase::query(), $officer)->pluck('id')->all();
        $this->assertContains($caseA->id, $visible);
        $this->assertNotContains($caseB->id, $visible);
    }

    public function test_area_scoped_user_with_no_assignment_sees_no_geographic_records(): void
    {
        $officer = $this->user('field_officer');
        $this->assertSame([], app(UserDataScope::class)->cases(DiseaseCase::query(), $officer)->pluck('id')->all());
        $this->assertSame([], app(UserDataScope::class)->farms(Farm::query(), $officer)->pluck('id')->all());
    }

    public function test_laboratory_manager_only_sees_samples_from_assigned_laboratories(): void
    {
        $manager = $this->user('laboratory_manager');
        $technician = $this->user('laboratory_technician');
        $labA = Laboratory::create(['code' => 'LAB-SCOPE-A', 'name' => 'Lab A', 'level' => 'regional', 'is_active' => true]);
        $labB = Laboratory::create(['code' => 'LAB-SCOPE-B', 'name' => 'Lab B', 'level' => 'regional', 'is_active' => true]);
        $labA->technicians()->attach($manager->id, ['assignment_role' => 'manager', 'effective_from' => now()->toDateString()]);
        $farmer = Farmer::create(['farmer_code' => 'FRM-LABSCOPE', 'full_name' => 'Lab Scope Farmer', 'region' => 'Region', 'district' => 'District']);
        $farm = Farm::create(['farm_code' => 'FARM-LABSCOPE', 'farmer_id' => $farmer->id, 'name' => 'Lab Scope Farm', 'region' => 'Region', 'district' => 'District', 'bird_count' => 1]);
        $case = DiseaseCase::create(['case_code' => 'CASE-LABSCOPE', 'farm_id' => $farm->id, 'reported_by' => $manager->id, 'disease_suspected' => 'Disease', 'date_observed' => now()->toDateString(), 'symptoms' => 'Signs', 'birds_affected' => 1, 'birds_dead' => 0, 'severity' => 'low', 'status' => 'reported']);
        $sampleA = Sample::create(['sample_code' => 'SMP-SCOPE-A', 'disease_case_id' => $case->id, 'laboratory_id' => $labA->id, 'collected_by' => $manager->id, 'collection_date' => now()->toDateString(), 'sample_type' => 'Swab', 'transport_status' => 'received', 'quality_status' => 'accepted', 'test_status' => 'pending']);
        $sampleB = Sample::create(['sample_code' => 'SMP-SCOPE-B', 'disease_case_id' => $case->id, 'laboratory_id' => $labB->id, 'collected_by' => $manager->id, 'collection_date' => now()->toDateString(), 'sample_type' => 'Swab', 'transport_status' => 'received', 'quality_status' => 'accepted', 'test_status' => 'pending']);

        $visible = app(UserDataScope::class)->samples(Sample::query(), $manager)->pluck('id')->all();
        $this->assertContains($sampleA->id, $visible);
        $this->assertNotContains($sampleB->id, $visible);
    }
}

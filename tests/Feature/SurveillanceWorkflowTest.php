<?php

namespace Tests\Feature;

use App\Models\DiseaseCase;
use App\Models\Farm;
use App\Models\Farmer;
use App\Models\Investigation;
use App\Models\Laboratory;
use App\Models\LaboratoryTestResult;
use App\Models\Sample;
use App\Models\SampleMovement;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SurveillanceWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role = 'field_officer'): User
    {
        return User::create([
            'name' => 'Test '.$role,
            'email' => $role.'-'.uniqid().'@example.test',
            'password' => 'Correct-Horse-Battery-42!',
            'role' => $role,
            'is_active' => true,
        ]);
    }

    public function test_authenticated_staff_can_open_srs_workflow_registers(): void
    {
        $this->actingAs($this->makeUser());

        $this->get(route('animals.index'))->assertOk();
        $this->get(route('investigations.index'))->assertOk();
        $this->get(route('samples.index'))->assertOk();
        $this->get(route('outbreaks.index'))->assertOk();

        $this->actingAs($this->makeUser('laboratory_technician'));
        $this->get(route('laboratory.index'))->assertOk();
    }

    public function test_guest_is_redirected_from_surveillance_registers(): void
    {
        $this->get(route('animals.index'))->assertRedirect(route('login'));
        $this->get(route('samples.index'))->assertRedirect(route('login'));
    }

    public function test_non_administrator_cannot_access_user_management(): void
    {
        $this->actingAs($this->makeUser('field_officer'));
        $this->get(route('users.index'))->assertForbidden();
    }

    public function test_system_administrator_can_access_user_management(): void
    {
        $this->actingAs($this->makeUser('system_admin'));
        $this->get(route('users.index'))->assertOk();
    }
    public function test_sample_moves_from_field_collection_through_lab_review_to_final_release(): void
    {
        $officer = $this->makeUser('field_officer');
        $technician = $this->makeUser('laboratory_technician');
        $manager = $this->makeUser('laboratory_manager');
        $this->makeUser('regional_veterinary_officer');
        $this->makeUser('zonal_laboratory_administrator');
        $this->makeUser('mlf_officer');

        $farmer = Farmer::create([
            'farmer_code' => 'FRM-'.Str::upper(Str::random(8)),
            'full_name' => 'Workflow Test Farmer',
            'region' => 'Dodoma',
            'district' => 'Kondoa',
        ]);
        $farm = Farm::create([
            'farm_code' => 'FARM-'.Str::upper(Str::random(8)),
            'farmer_id' => $farmer->id,
            'name' => 'Workflow Test Farm',
            'region' => 'Dodoma',
            'district' => 'Kondoa',
            'bird_count' => 20,
        ]);
        $case = DiseaseCase::create([
            'case_code' => 'CASE-'.Str::upper(Str::random(8)),
            'farm_id' => $farm->id,
            'reported_by' => $officer->id,
            'disease_suspected' => 'Newcastle disease',
            'date_observed' => now()->toDateString(),
            'symptoms' => 'Respiratory signs',
            'birds_affected' => 3,
            'birds_dead' => 1,
            'severity' => 'high',
            'status' => 'under_investigation',
        ]);
        Investigation::create([
            'investigation_code' => 'INV-'.Str::upper(Str::random(8)),
            'disease_case_id' => $case->id,
            'started_by' => $officer->id,
            'assigned_to' => $officer->id,
            'investigated_at' => now(),
            'status' => 'under_investigation',
            'field_observations' => 'Field investigation completed; collect diagnostic sample.',
        ]);
        $laboratory = Laboratory::create([
            'code' => 'LAB-'.Str::upper(Str::random(8)),
            'name' => 'Regional Veterinary Laboratory',
            'level' => 'regional',
            'region' => 'Dodoma',
            'district' => 'Dodoma',
            'is_active' => true,
        ]);

        $this->actingAs($officer)->post(route('samples.store'), [
            'disease_case_id' => $case->id,
            'laboratory_id' => $laboratory->id,
            'collection_date' => now()->toDateString(),
            'sample_type' => 'Swab',
            'collection_notes' => 'Collected and packaged using sample SOP.',
        ])->assertRedirect(route('samples.index'));

        $sample = Sample::where('disease_case_id', $case->id)->firstOrFail();
        $this->assertDatabaseHas('sample_movements', ['sample_id' => $sample->id, 'event_type' => 'collected']);

        $this->actingAs($officer)->put(route('samples.update', $sample), [
            'event_type' => 'dispatched',
            'to_location' => $laboratory->name,
            'condition_on_transfer' => 'Sealed, chilled transport container',
            'notes' => 'Dispatched with custody record.',
        ])->assertRedirect(route('samples.index'));
        $this->assertSame('in_transit', $sample->fresh()->transport_status);

        $this->actingAs($technician)->put(route('samples.update', $sample), [
            'event_type' => 'received',
            'to_location' => $laboratory->name,
            'received_by' => $technician->name,
            'condition_on_transfer' => 'Seal intact, chilled',
        ])->assertRedirect(route('samples.index'));
        $this->assertSame('received', $sample->fresh()->transport_status);
        $this->assertNotEmpty($sample->fresh()->laboratory_reference_number);

        $this->actingAs($technician)->put(route('samples.update', $sample), [
            'event_type' => 'accepted',
            'notes' => 'Sample passed reception QC.',
        ])->assertRedirect(route('samples.index'));
        $this->assertSame('accepted', $sample->fresh()->quality_status);

        $this->actingAs($manager)->put(route('samples.assign', $sample), [
            'laboratory_section' => 'Virology',
            'assigned_technician' => $technician->id,
        ])->assertRedirect(route('samples.index'));
        $this->assertSame($technician->id, $sample->fresh()->assigned_technician);

        $this->actingAs($technician)->post(route('laboratory.store'), [
            'sample_id' => $sample->id,
            'laboratory_id' => $laboratory->id,
            'test_name' => 'Newcastle disease PCR',
            'method' => 'PCR',
            'observations' => 'Viral target detected',
            'qualitative_result' => 'Positive',
            'technician_comments' => 'Controls passed.',
        ])->assertRedirect(route('laboratory.index'));

        $result = LaboratoryTestResult::where('sample_id', $sample->id)->firstOrFail();
        $this->assertSame('submitted_for_review', $result->status);

        $this->actingAs($manager)->put(route('laboratory.approve', $result), [
            'decision' => 'approve',
            'review_comments' => 'Findings verified and approved.',
        ])->assertRedirect(route('laboratory.index'));

        $this->assertSame('approved', $result->fresh()->status);
        $this->assertSame('approved', $sample->fresh()->test_status);
        $this->assertNotNull($sample->fresh()->released_at);
        $this->assertSame('confirmed', $case->fresh()->status);
        $this->assertDatabaseHas('sample_movements', [
            'sample_id' => $sample->id,
            'event_type' => 'final_result_released',
        ]);
        $this->assertGreaterThanOrEqual(6, SampleMovement::where('sample_id', $sample->id)->count());
    }


}

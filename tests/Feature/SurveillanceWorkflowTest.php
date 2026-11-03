<?php

namespace Tests\Feature;

use App\Models\DiseaseCase;
use App\Models\Farm;
use App\Models\Farmer;
use App\Models\Investigation;
use App\Models\Laboratory;
use App\Models\LaboratoryTest;
use App\Models\LaboratoryTestAssignment;
use App\Models\TestMethod;
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
        $catalogueTest = LaboratoryTest::create([
            'code' => 'NDV-PCR-'.Str::upper(Str::random(4)),
            'name' => 'Newcastle disease PCR',
            'is_active' => true,
        ]);
        $secondCatalogueTest = LaboratoryTest::create([
            'code' => 'AI-PCR-'.Str::upper(Str::random(4)),
            'name' => 'Avian influenza PCR',
            'is_active' => true,
        ]);
        $testMethod = TestMethod::create([
            'code' => 'PCR-'.Str::upper(Str::random(4)),
            'name' => 'Polymerase chain reaction',
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
            'event_type' => 'packaged',
            'condition_on_transfer' => 'Sealed, leak-proof sample container',
            'notes' => 'Sample packaged according to SOP.',
        ])->assertRedirect(route('samples.index'));
        $this->assertNotNull($sample->fresh()->packaged_at);

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
            'laboratory_test_ids' => [$catalogueTest->id, $secondCatalogueTest->id],
        ])->assertRedirect(route('samples.index'));
        $assignment = LaboratoryTestAssignment::where('sample_id', $sample->id)->where('laboratory_test_id', $catalogueTest->id)->firstOrFail();
        $this->assertSame('assigned', $assignment->status);
        $this->assertSame($technician->id, $sample->fresh()->assigned_technician);

        $this->actingAs($technician)->put(route('samples.update', $sample), [
            'event_type' => 'testing_started',
            'notes' => 'Assigned technician started examination.',
        ])->assertRedirect(route('samples.index'));
        $this->assertSame('in_progress', $sample->fresh()->test_status);

        $this->actingAs($technician)->post(route('laboratory.store'), [
            'laboratory_test_assignment_id' => $assignment->id,
            'test_method_id' => $testMethod->id,
            'observations' => 'Viral target detected',
            'qualitative_result' => 'Positive',
            'technician_comments' => 'Controls passed.',
        ])->assertRedirect(route('laboratory.index'));

        $result = LaboratoryTestResult::where('sample_id', $sample->id)->firstOrFail();
        $this->assertSame('submitted_for_review', $result->status);
        $this->assertSame($catalogueTest->id, $result->laboratory_test_id);
        $this->assertSame($testMethod->id, $result->test_method_id);
        $this->assertSame('submitted_for_review', $assignment->fresh()->status);

        $this->actingAs($manager)->put(route('laboratory.approve', $result), [
            'decision' => 'approve',
            'review_comments' => 'Findings verified and approved.',
        ])->assertRedirect(route('laboratory.index'));

        $this->assertSame('approved', $result->fresh()->status);
        $this->assertSame('approved', $assignment->fresh()->status);
        $this->assertSame('completed', $sample->fresh()->test_status);
        $this->assertNull($sample->fresh()->released_at, 'The sample must not be released while another assigned diagnostic test is pending.');

        $secondAssignment = LaboratoryTestAssignment::where('sample_id', $sample->id)
            ->where('laboratory_test_id', $secondCatalogueTest->id)->firstOrFail();
        $this->actingAs($technician)->post(route('laboratory.store'), [
            'laboratory_test_assignment_id' => $secondAssignment->id,
            'test_method_id' => $testMethod->id,
            'observations' => 'Avian influenza target detected.',
            'qualitative_result' => 'Positive',
            'technician_comments' => 'Second assay controls passed.',
        ])->assertRedirect(route('laboratory.index'));
        $secondResult = LaboratoryTestResult::where('laboratory_test_assignment_id', $secondAssignment->id)->firstOrFail();

        $this->actingAs($manager)->put(route('laboratory.approve', $secondResult), [
            'decision' => 'approve',
            'review_comments' => 'Second assay independently verified.',
        ])->assertRedirect(route('laboratory.index'));

        $this->assertSame('approved', $secondAssignment->fresh()->status);
        $this->assertSame('approved', $sample->fresh()->test_status);
        $this->assertNotNull($sample->fresh()->released_at);
        $this->assertSame('confirmed', $case->fresh()->status);
        $this->assertDatabaseHas('sample_movements', [
            'sample_id' => $sample->id,
            'event_type' => 'final_result_released',
        ]);
        $this->assertGreaterThanOrEqual(6, SampleMovement::where('sample_id', $sample->id)->count());
    }


    private function makeSampleWorkflowFixture(): array
    {
        $officer = $this->makeUser('field_officer');
        $technician = $this->makeUser('laboratory_technician');
        $manager = $this->makeUser('laboratory_manager');

        $farmer = Farmer::create([
            'farmer_code' => 'FRM-'.Str::upper(Str::random(8)),
            'full_name' => 'Workflow Guard Test Farmer',
            'region' => 'Dodoma',
            'district' => 'Kondoa',
        ]);
        $farm = Farm::create([
            'farm_code' => 'FARM-'.Str::upper(Str::random(8)),
            'farmer_id' => $farmer->id,
            'name' => 'Workflow Guard Test Farm',
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
            'field_observations' => 'Sample collection required.',
        ]);
        $laboratory = Laboratory::create([
            'code' => 'LAB-'.Str::upper(Str::random(8)),
            'name' => 'Workflow Guard Laboratory',
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
            'collection_notes' => 'Workflow guard test sample.',
        ])->assertRedirect(route('samples.index'));

        return [
            'officer' => $officer,
            'technician' => $technician,
            'manager' => $manager,
            'case' => $case,
            'laboratory' => $laboratory,
            'sample' => Sample::where('disease_case_id', $case->id)->firstOrFail(),
        ];
    }

    public function test_sample_cannot_be_dispatched_before_packaging_and_rejected_sample_cannot_enter_testing(): void
    {
        $flow = $this->makeSampleWorkflowFixture();
        $sample = $flow['sample'];
        $officer = $flow['officer'];
        $technician = $flow['technician'];
        $laboratory = $flow['laboratory'];

        $this->actingAs($officer)->put(route('samples.update', $sample), [
            'event_type' => 'dispatched',
            'condition_on_transfer' => 'Sealed transport container',
        ])->assertSessionHasErrors('event_type');
        $this->assertSame('not_dispatched', $sample->fresh()->transport_status);

        $this->actingAs($officer)->put(route('samples.update', $sample), [
            'event_type' => 'packaged',
            'condition_on_transfer' => 'Sealed, leak-proof container',
        ])->assertRedirect(route('samples.index'));

        $this->actingAs($officer)->put(route('samples.update', $sample), [
            'event_type' => 'dispatched',
            'condition_on_transfer' => 'Chilled transport container',
        ])->assertRedirect(route('samples.index'));

        $this->actingAs($technician)->put(route('samples.update', $sample), [
            'event_type' => 'received',
            'received_by' => $technician->name,
            'condition_on_transfer' => 'Seal intact',
        ])->assertRedirect(route('samples.index'));

        $this->actingAs($technician)->put(route('samples.update', $sample), [
            'event_type' => 'rejected',
            'rejection_reason' => 'Container seal was compromised.',
        ])->assertRedirect(route('samples.index'));

        $sample->refresh();
        $this->assertSame('rejected', $sample->quality_status);
        $this->assertSame('Container seal was compromised.', $sample->rejection_reason);
        $this->assertSame('pending', $sample->test_status);

        $this->actingAs($flow['manager'])->put(route('samples.assign', $sample), [
            'laboratory_section' => 'Virology',
            'assigned_technician' => $technician->id,
        ])->assertSessionHasErrors('assigned_technician');

        $this->actingAs($technician)->get(route('laboratory.create'))
            ->assertDontSee($sample->sample_code);
        $this->assertDatabaseHas('sample_movements', [
            'sample_id' => $sample->id,
            'event_type' => 'rejected',
        ]);
    }

    public function test_test_performer_cannot_approve_own_submitted_result_even_if_their_role_later_changes(): void
    {
        $flow = $this->makeSampleWorkflowFixture();
        $sample = $flow['sample'];
        $officer = $flow['officer'];
        $technician = $flow['technician'];
        $manager = $flow['manager'];
        $laboratory = $flow['laboratory'];

        $this->actingAs($officer)->put(route('samples.update', $sample), [
            'event_type' => 'packaged',
            'condition_on_transfer' => 'Sealed container',
        ])->assertRedirect(route('samples.index'));
        $this->actingAs($officer)->put(route('samples.update', $sample), [
            'event_type' => 'dispatched',
            'condition_on_transfer' => 'Chilled transport',
        ])->assertRedirect(route('samples.index'));
        $this->actingAs($technician)->put(route('samples.update', $sample), [
            'event_type' => 'received',
            'received_by' => $technician->name,
            'condition_on_transfer' => 'Seal intact',
        ])->assertRedirect(route('samples.index'));
        $this->actingAs($technician)->put(route('samples.update', $sample), [
            'event_type' => 'accepted',
            'notes' => 'Passed reception quality check.',
        ])->assertRedirect(route('samples.index'));
        $this->actingAs($manager)->put(route('samples.assign', $sample), [
            'laboratory_section' => 'Virology',
            'assigned_technician' => $technician->id,
        ])->assertRedirect(route('samples.index'));
        $this->actingAs($technician)->put(route('samples.update', $sample), [
            'event_type' => 'testing_started',
        ])->assertRedirect(route('samples.index'));
        $this->actingAs($technician)->post(route('laboratory.store'), [
            'sample_id' => $sample->id,
            'laboratory_id' => $laboratory->id,
            'test_name' => 'Newcastle disease PCR',
            'method' => 'PCR',
            'qualitative_result' => 'Positive',
        ])->assertRedirect(route('laboratory.index'));

        $result = LaboratoryTestResult::where('sample_id', $sample->id)->firstOrFail();
        $technician->update(['role' => 'laboratory_manager']);

        $this->actingAs($technician)->put(route('laboratory.approve', $result), [
            'decision' => 'approve',
            'review_comments' => 'Attempted self-approval.',
        ])->assertSessionHasErrors('decision');

        $this->assertSame('submitted_for_review', $result->fresh()->status);
        $this->assertNull($result->fresh()->reviewed_by);
        $this->assertSame('completed', $sample->fresh()->test_status);
    }

}

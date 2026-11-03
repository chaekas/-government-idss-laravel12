<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Laboratory;
use App\Models\LaboratoryCorrectiveAction;
use App\Models\LaboratoryEquipment;
use App\Models\QualityControlRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LaboratoryManagementTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        return User::create([
            'name' => 'Laboratory Test '.$role,
            'email' => $role.'-'.uniqid().'@example.test',
            'password' => 'Correct-Horse-Battery-42!',
            'role' => $role,
            'is_active' => true,
        ]);
    }

    private function laboratory(): Laboratory
    {
        return Laboratory::create([
            'code' => 'LAB-'.uniqid(),
            'name' => 'Central Veterinary Laboratory',
            'level' => 'national',
            'is_active' => true,
        ]);
    }

    public function test_manager_can_register_sections_equipment_and_record_failed_calibration_with_capa(): void
    {
        $manager = $this->user('laboratory_manager');
        $lab = $this->laboratory();

        $this->actingAs($manager)->post(route('laboratory.sections.store'), [
            'laboratory_id' => $lab->id,
            'code' => 'VIROLOGY',
            'name' => 'Virology',
        ])->assertRedirect();

        $this->post(route('laboratory.equipment.store'), [
            'laboratory_id' => $lab->id,
            'equipment_code' => 'PCR-UNIT-01',
            'name' => 'Real-time PCR machine',
            'manufacturer' => 'Approved manufacturer',
            'status' => 'active',
        ])->assertRedirect();

        $equipment = LaboratoryEquipment::where('equipment_code', 'PCR-UNIT-01')->firstOrFail();
        $this->put(route('laboratory.equipment.calibrations.store', $equipment), [
            'calibration_date' => now()->toDateString(),
            'due_date' => now()->addMonths(6)->toDateString(),
            'outcome' => 'failed',
            'certificate_reference' => 'CAL-FAIL-001',
            'findings' => 'Calibration outside approved tolerance.',
            'corrective_action' => 'Remove equipment from service and arrange repair and recalibration.',
        ])->assertRedirect();

        $this->assertSame('maintenance', $equipment->fresh()->status);
        $this->assertDatabaseHas('laboratory_equipment_calibrations', [
            'laboratory_equipment_id' => $equipment->id,
            'outcome' => 'failed',
            'certificate_reference' => 'CAL-FAIL-001',
        ]);

        $action = LaboratoryCorrectiveAction::where('laboratory_equipment_id', $equipment->id)->firstOrFail();
        $this->assertSame('open', $action->status);

        $this->put(route('laboratory.corrective-actions.update', $action), [
            'status' => 'resolved',
            'resolution' => 'Equipment repaired and successfully recalibrated by an approved service provider.',
        ])->assertRedirect();

        $this->assertSame('resolved', $action->fresh()->status);
        $this->assertNotNull($action->fresh()->resolved_at);
        $this->assertGreaterThanOrEqual(3, AuditLog::where('user_id', $manager->id)->count());
    }

    public function test_failed_quality_control_inspection_generates_corrective_action(): void
    {
        $manager = $this->user('laboratory_manager');
        $lab = $this->laboratory();

        $this->actingAs($manager)->post(route('laboratory.quality-control.store'), [
            'laboratory_id' => $lab->id,
            'qc_type' => 'assay_run',
            'outcome' => 'failed',
            'findings' => 'Positive control failed acceptance criteria.',
            'corrective_action' => 'Quarantine affected run, investigate reagents and repeat assay after corrective action.',
            'performed_at' => now()->format('Y-m-d H:i:s'),
        ])->assertRedirect();

        $record = QualityControlRecord::where('laboratory_id', $lab->id)->firstOrFail();
        $this->assertSame('failed', $record->outcome);
        $this->assertDatabaseHas('laboratory_corrective_actions', [
            'quality_control_record_id' => $record->id,
            'status' => 'open',
            'priority' => 'high',
        ]);
    }

    public function test_laboratory_technician_cannot_access_management_controls(): void
    {
        $this->actingAs($this->user('laboratory_technician'))
            ->get(route('laboratory.operations'))
            ->assertForbidden();
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Laboratory;
use App\Models\LaboratoryCorrectiveAction;
use App\Models\LaboratoryEquipment;
use App\Models\LaboratoryEquipmentCalibration;
use App\Models\LaboratorySection;
use App\Models\LaboratoryTest;
use App\Models\QualityControlRecord;
use App\Models\Sample;
use App\Models\TestMethod;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class LaboratoryOperationsController extends Controller
{
    private const MANAGER_ROLES = ['laboratory_manager', 'zonal_laboratory_administrator'];
    private const ADMIN_ROLES = ['system_admin', 'super_admin', 'super-admin', 'administrator', 'admin'];

    private function authorizeManager(Request $request): void
    {
        abort_unless(in_array($request->user()->role, [...self::MANAGER_ROLES, ...self::ADMIN_ROLES], true), 403);
    }

    public function index(Request $request)
    {
        $this->authorizeManager($request);

        $laboratories = Laboratory::where('is_active', true);
        if (!in_array($request->user()->role, self::ADMIN_ROLES, true)) {
            $labIds = $request->user()->activeLaboratoryIds();
            $laboratories->whereIn('id', $labIds ?: [-1]);
        }
        $visibleLabIds = (clone $laboratories)->pluck('id')->all();

        return view('laboratory.operations', [
            'laboratories' => $laboratories->with(['sections', 'equipment.calibrations'])->orderBy('name')->get(),
            'tests' => LaboratoryTest::where('is_active', true)->orderBy('name')->get(),
            'methods' => TestMethod::where('is_active', true)->orderBy('name')->get(),
            'samples' => Sample::whereIn('laboratory_id', $visibleLabIds ?: [-1])->where('transport_status', 'received')->where('quality_status', 'accepted')->with('laboratory')->orderBy('sample_code')->get(),
            'technicians' => User::where('role', 'laboratory_technician')->where('is_active', true)->orderBy('name')->get(),
            'qualityRecords' => QualityControlRecord::whereIn('laboratory_id', $visibleLabIds ?: [-1])->with(['laboratory', 'sample', 'test'])->latest('performed_at')->paginate(15, ['*'], 'qc_page'),
            'correctiveActions' => LaboratoryCorrectiveAction::whereIn('laboratory_id', $visibleLabIds ?: [-1])->with(['laboratory', 'equipment', 'assignee'])->latest()->paginate(15, ['*'], 'action_page'),
        ]);
    }

    private function authorizeLaboratory(Request $request, int $laboratoryId): void
    {
        if (in_array($request->user()->role, self::ADMIN_ROLES, true)) return;
        abort_unless(in_array($laboratoryId, $request->user()->activeLaboratoryIds(), true), 404);
    }

    public function storeSection(Request $request)
    {
        $this->authorizeManager($request);
        $data = $request->validate([
            'laboratory_id' => ['required', 'exists:laboratories,id'],
            'code' => ['required', 'string', 'max:80'],
            'name' => ['required', 'string', 'max:160'],
        ]);
        $this->authorizeLaboratory($request, (int) $data['laboratory_id']);
        if (LaboratorySection::where('laboratory_id', $data['laboratory_id'])->where('code', $data['code'])->exists()) {
            throw ValidationException::withMessages(['code' => 'That section code already exists in the selected laboratory.']);
        }
        $section = LaboratorySection::create([...$data, 'is_active' => true]);
        $this->audit($request, 'laboratory.section_created', 'laboratory_section', $section->id, 'Created laboratory section '.$section->name.'.');
        return back()->with('status', 'Laboratory section created.');
    }

    public function storeEquipment(Request $request)
    {
        $this->authorizeManager($request);
        $data = $request->validate([
            'laboratory_id' => ['required', 'exists:laboratories,id'],
            'equipment_code' => ['required', 'string', 'max:80', 'unique:laboratory_equipment,equipment_code'],
            'name' => ['required', 'string', 'max:180'],
            'manufacturer' => ['nullable', 'string', 'max:120'],
            'model' => ['nullable', 'string', 'max:120'],
            'serial_number' => ['nullable', 'string', 'max:120'],
            'last_calibrated_at' => ['nullable', 'date', 'before_or_equal:today'],
            'calibration_due_at' => ['nullable', 'date', 'after_or_equal:last_calibrated_at'],
            'status' => ['required', Rule::in(['active', 'maintenance', 'retired'])],
        ]);
        $this->authorizeLaboratory($request, (int) $data['laboratory_id']);
        $equipment = LaboratoryEquipment::create($data);
        $this->audit($request, 'laboratory.equipment_created', 'laboratory_equipment', $equipment->id, 'Registered equipment '.$equipment->equipment_code.'.');
        return back()->with('status', 'Laboratory equipment registered.');
    }

    public function updateEquipment(Request $request, LaboratoryEquipment $equipment)
    {
        $this->authorizeManager($request);
        $this->authorizeLaboratory($request, (int) $equipment->laboratory_id);
        $data = $request->validate([
            'status' => ['required', Rule::in(['active', 'maintenance', 'retired'])],
        ]);
        $equipment->update($data);
        $this->audit($request, 'laboratory.equipment_status_updated', 'laboratory_equipment', $equipment->id, 'Changed equipment status to '.$data['status'].'.');
        return back()->with('status', 'Equipment status updated.');
    }

    public function recordCalibration(Request $request, LaboratoryEquipment $equipment)
    {
        $this->authorizeManager($request);
        $this->authorizeLaboratory($request, (int) $equipment->laboratory_id);
        $data = $request->validate([
            'calibration_date' => ['required', 'date', 'before_or_equal:today'],
            'due_date' => ['nullable', 'date', 'after:calibration_date'],
            'outcome' => ['required', Rule::in(['passed', 'failed', 'conditional'])],
            'certificate_reference' => ['nullable', 'string', 'max:180'],
            'findings' => ['nullable', 'string', 'max:4000'],
            'corrective_action' => ['nullable', 'required_if:outcome,failed', 'string', 'max:4000'],
        ]);

        $calibration = $equipment->calibrations()->create([...$data, 'performed_by' => $request->user()->id]);
        $equipment->update([
            'last_calibrated_at' => $data['calibration_date'],
            'calibration_due_at' => $data['due_date'] ?? null,
            'status' => $data['outcome'] === 'passed' ? 'active' : 'maintenance',
        ]);

        if ($data['outcome'] !== 'passed') {
            LaboratoryCorrectiveAction::create([
                'action_code' => 'CAPA-'.now()->format('ymd').'-'.Str::upper(Str::random(6)),
                'laboratory_id' => $equipment->laboratory_id,
                'laboratory_equipment_id' => $equipment->id,
                'equipment_calibration_id' => $calibration->id,
                'created_by' => $request->user()->id,
                'priority' => $data['outcome'] === 'failed' ? 'high' : 'medium',
                'status' => 'open',
                'description' => $data['corrective_action'] ?: 'Review conditional calibration and determine corrective action.',
            ]);
        }

        $this->audit($request, 'laboratory.equipment_calibrated', 'laboratory_equipment_calibration', $calibration->id, 'Recorded '.$data['outcome'].' calibration for '.$equipment->equipment_code.'.');
        return back()->with('status', 'Calibration record saved. Equipment status: '.$equipment->fresh()->status.'.');
    }

    public function recordQualityControl(Request $request)
    {
        $this->authorizeManager($request);
        $data = $request->validate([
            'laboratory_id' => ['required', 'exists:laboratories,id'],
            'laboratory_test_id' => ['nullable', 'exists:laboratory_tests,id'],
            'sample_id' => ['nullable', 'exists:samples,id'],
            'qc_type' => ['required', 'string', 'max:80'],
            'outcome' => ['required', Rule::in(['passed', 'failed', 'conditional'])],
            'findings' => ['required', 'string', 'max:4000'],
            'corrective_action' => ['nullable', 'required_if:outcome,failed', 'string', 'max:4000'],
            'performed_at' => ['required', 'date', 'before_or_equal:now'],
        ]);
        $this->authorizeLaboratory($request, (int) $data['laboratory_id']);
        if (!empty($data['sample_id'])) {
            $sample = Sample::findOrFail($data['sample_id']);
            if ((int) $sample->laboratory_id !== (int) $data['laboratory_id']) {
                throw ValidationException::withMessages(['sample_id' => 'The selected sample does not belong to this laboratory.']);
            }
        }

        $record = QualityControlRecord::create([...$data, 'recorded_by' => $request->user()->id]);
        if ($data['outcome'] === 'failed') {
            LaboratoryCorrectiveAction::create([
                'action_code' => 'CAPA-'.now()->format('ymd').'-'.Str::upper(Str::random(6)),
                'laboratory_id' => $data['laboratory_id'],
                'quality_control_record_id' => $record->id,
                'created_by' => $request->user()->id,
                'priority' => 'high',
                'status' => 'open',
                'description' => $data['corrective_action'],
            ]);
        }
        $this->audit($request, 'laboratory.quality_control_recorded', 'quality_control_record', $record->id, 'Recorded '.$data['outcome'].' QC inspection.');
        return back()->with('status', 'Quality-control inspection recorded.');
    }

    public function updateCorrectiveAction(Request $request, LaboratoryCorrectiveAction $action)
    {
        $this->authorizeManager($request);
        $this->authorizeLaboratory($request, (int) $action->laboratory_id);
        $data = $request->validate([
            'status' => ['required', Rule::in(['open', 'in_progress', 'resolved', 'cancelled'])],
            'resolution' => ['nullable', 'required_if:status,resolved', 'string', 'max:4000'],
            'due_date' => ['nullable', 'date'],
        ]);
        $action->update([
            ...$data,
            'resolved_at' => $data['status'] === 'resolved' ? now() : null,
        ]);
        $this->audit($request, 'laboratory.corrective_action_updated', 'laboratory_corrective_action', $action->id, 'Corrective action '.$action->action_code.' status: '.$data['status'].'.');
        return back()->with('status', 'Corrective action updated.');
    }

    private function audit(Request $request, string $action, string $type, int $id, string $description): void
    {
        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => $action,
            'entity_type' => $type,
            'entity_id' => (string) $id,
            'ip_address' => $request->ip(),
            'description' => $description,
            'created_at' => now(),
        ]);
    }
}

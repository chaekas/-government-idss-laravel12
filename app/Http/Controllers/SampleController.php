<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\DiseaseCase;
use App\Models\Laboratory;
use App\Models\LaboratoryTest;
use App\Models\LaboratoryTestAssignment;
use App\Models\Sample;
use App\Models\SampleMovement;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\DiseaseCaseWorkflow;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SampleController extends Controller
{
    private const ADMIN_ROLES = ['system_admin', 'super_admin', 'super-admin', 'administrator', 'admin'];
    private const LAB_ROLES = ['laboratory_technician', 'laboratory_manager', 'zonal_laboratory_administrator'];
    private const LEADERSHIP_ROLES = ['regional_veterinary_officer', 'mlf_officer'];

    public function index(Request $request)
    {
        $query = Sample::with([
            'diseaseCase.farm',
            'laboratory',
            'collector',
            'assignedTechnician',
            'assignedBy',
            'movements.recordedBy',
            'results',
            'testAssignments.test',
        ])->latest();

        // Technicians work only on samples assigned to them. Field officers see
        // the samples they collected or samples belonging to cases they reported.
        if ($request->user()->role === 'laboratory_technician') {
            $query->where(function ($samples) use ($request) {
                $samples->where('assigned_technician', $request->user()->id)
                    ->orWhere('transport_status', 'in_transit')
                    ->orWhere(function ($awaitingQuality) {
                        $awaitingQuality->where('transport_status', 'received')
                            ->where('quality_status', 'pending');
                    });
            });
        } elseif ($request->user()->role === 'field_officer') {
            $query->where(function ($samples) use ($request) {
                $samples->where('collected_by', $request->user()->id)
                    ->orWhereHas('diseaseCase', fn ($cases) => $cases->where('reported_by', $request->user()->id));
            });
        }

        return view('samples.index', ['samples' => $query->paginate(15), 'laboratoryTests' => LaboratoryTest::where('is_active', true)->orderBy('name')->get()]);
    }

    public function create()
    {
        return view('samples.create', [
            'cases' => DiseaseCase::with('farm')
                ->whereIn('status', ['reported','under_investigation','sample_collected'])
                ->whereHas('investigation', fn ($investigations) => $investigations->whereIn('status', ['under_investigation','sample_collected']))
                ->latest()
                ->get(),
            'laboratories' => Laboratory::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'disease_case_id' => ['required', 'exists:disease_cases,id'],
            'laboratory_id' => ['required', 'exists:laboratories,id'],
            'collection_date' => ['required', 'date', 'before_or_equal:today'],
            'sample_type' => ['required', 'string', 'max:120'],
            'collection_notes' => ['nullable', 'string', 'max:3000'],
        ]);

        $case = DiseaseCase::with('investigation')->findOrFail($data['disease_case_id']);
        if (!$case->investigation || $case->investigation->status === 'closed' || $case->status === 'closed') {
            throw ValidationException::withMessages([
                'disease_case_id' => 'An active disease investigation is required before a sample can be registered.',
            ]);
        }

        $laboratory = Laboratory::where('is_active', true)->find($data['laboratory_id']);
        if (!$laboratory) {
            throw ValidationException::withMessages([
                'laboratory_id' => 'Choose an active registered laboratory for this sample.',
            ]);
        }

        $code = 'SMP-'.now()->format('ymd').'-'.Str::upper(Str::random(6));
        $data['sample_code'] = $code;
        $data['barcode_value'] = $code;
        $data['collected_by'] = $request->user()->id;
        $data['transport_status'] = 'not_dispatched';
        $data['quality_status'] = 'pending';
        $data['test_status'] = 'pending';
        $sample = Sample::create($data);

        $this->recordMovement($sample, $request, 'collected', 'Field collection', 'Field collection', trim('Destination laboratory: '.$laboratory->name.'. '.($data['collection_notes'] ?? '')));
        DiseaseCaseWorkflow::transition($case, 'sample_collected', $request->user()->id, 'A sample was registered against the active investigation.');

        $this->audit($request, 'sample.collected', $sample, 'Registered sample '.$code.' for '.$laboratory->name);
        $this->notifyStakeholders($sample, 'Sample registered', $code.' was collected by '.$request->user()->name.' and is awaiting dispatch to '.$laboratory->name.'.');

        return redirect()->route('samples.index')->with('status', 'Sample registered. Sample ID: '.$code.'. Next: package and dispatch it to '.$laboratory->name.'.');
    }

    public function update(Request $request, Sample $sample)
    {
        $data = $request->validate([
            'event_type' => ['required', Rule::in(['packaged', 'dispatched', 'received', 'accepted', 'rejected', 'damaged', 'contaminated', 'insufficient_volume', 'recollection_required', 'testing_started'])],
            'to_location' => ['nullable', 'string', 'max:180'],
            'received_by' => ['nullable', 'string', 'max:160'],
            'condition_on_transfer' => ['nullable', 'required_if:event_type,packaged,dispatched,received', 'string', 'max:180'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'rejection_reason' => ['nullable', 'required_if:event_type,rejected,damaged,contaminated,insufficient_volume,recollection_required', 'string', 'max:1000'],
        ]);

        $event = $data['event_type'];
        $role = $request->user()->role;
        $isAdmin = in_array($role, self::ADMIN_ROLES, true);
        $isField = in_array($role, ['field_officer', 'regional_veterinary_officer'], true);
        $isLab = in_array($role, self::LAB_ROLES, true);

        if ($event === 'packaged') {
            abort_unless($isAdmin || $isField, 403);
            if ($sample->transport_status !== 'not_dispatched' || $sample->packaged_at) {
                throw ValidationException::withMessages(['event_type' => 'Only a collected sample that has not been packaged or dispatched can be packaged.']);
            }
        } elseif ($event === 'dispatched') {
            abort_unless($isAdmin || $isField, 403);
            if ($sample->transport_status !== 'not_dispatched') {
                throw ValidationException::withMessages(['event_type' => 'Only a collected sample that has not yet been dispatched can be dispatched.']);
            }
            if (!$sample->packaged_at) {
                throw ValidationException::withMessages(['event_type' => 'Record packaging and the seal/transport condition before dispatching the sample.']);
            }
            if (!$sample->laboratory_id) {
                throw ValidationException::withMessages(['event_type' => 'Assign a registered laboratory before dispatching this sample.']);
            }
        } elseif ($event === 'received') {
            abort_unless($isAdmin || $isLab, 403);
            if ($sample->transport_status !== 'in_transit') {
                throw ValidationException::withMessages(['event_type' => 'The sample must be dispatched and in transit before the laboratory can receive it.']);
            }
            if (blank($data['received_by'] ?? null)) {
                throw ValidationException::withMessages(['received_by' => 'Record the name of the person receiving the sample at the laboratory.']);
            }
        } elseif ($event === 'testing_started') {
            abort_unless($isAdmin || $role === 'laboratory_technician', 403);
            if ($sample->transport_status !== 'received' || $sample->quality_status !== 'accepted') {
                throw ValidationException::withMessages(['event_type' => 'Testing can start only after laboratory receipt and quality acceptance.']);
            }
            if ((int) $sample->assigned_technician !== (int) $request->user()->id) {
                throw ValidationException::withMessages(['event_type' => 'Only the technician assigned to this sample may start its examination.']);
            }
            if ($sample->test_status !== 'pending' || $sample->testing_started_at) {
                throw ValidationException::withMessages(['event_type' => 'This sample examination has already started or the sample has completed testing.']);
            }
        } else {
            abort_unless($isAdmin || $isLab, 403);
            if ($sample->transport_status !== 'received') {
                throw ValidationException::withMessages(['event_type' => 'Record laboratory receipt before making a sample quality decision.']);
            }
            if (in_array($sample->quality_status, ['rejected', 'damaged', 'contaminated', 'insufficient_volume', 'recollection_required'], true)) {
                throw ValidationException::withMessages(['event_type' => 'This sample has a final quality rejection. Register a new sample if recollection is required.']);
            }
            if ($sample->quality_status === 'accepted') {
                throw ValidationException::withMessages(['event_type' => 'This sample has already passed quality control.']);
            }
        }

        $fromLocation = $sample->movements()->first()?->to_location
            ?: ($sample->transport_status === 'in_transit' ? 'In transit' : 'Field collection');
        $toLocation = $data['to_location'] ?? match ($event) {
            'packaged' => 'Field collection / packaged',
            'dispatched' => $sample->laboratory?->name,
            'received' => $sample->laboratory?->name,
            'testing_started' => $sample->laboratory_section ?: $sample->laboratory?->name,
            default => 'Laboratory quality control',
        };

        $updates = [];
        if ($event === 'packaged') {
            $updates['packaged_at'] = now();
        } elseif ($event === 'dispatched') {
            $updates['transport_status'] = 'in_transit';
        } elseif ($event === 'received') {
            $updates['transport_status'] = 'received';
            $updates['received_at'] = now();
            if (!$sample->laboratory_reference_number) {
                $updates['laboratory_reference_number'] = 'LABREF-'.now()->format('ymd').'-'.Str::upper(Str::random(6));
            }
        } elseif ($event === 'testing_started') {
            $updates['test_status'] = 'in_progress';
            $updates['testing_started_at'] = now();
        } else {
            $updates['quality_status'] = $event;
            $updates['rejection_reason'] = in_array($event, ['rejected', 'damaged', 'contaminated', 'insufficient_volume', 'recollection_required'], true)
                ? $data['rejection_reason']
                : null;
        }

        $sample->update($updates);
        if ($event === 'testing_started') {
            $case = $sample->diseaseCase;
            if ($case) DiseaseCaseWorkflow::transition($case, 'laboratory_testing', $request->user()->id, 'Laboratory examination started for sample '.$sample->sample_code.'.');
        }
        $this->recordMovement($sample, $request, $event, $fromLocation, $toLocation, $data['notes'] ?? null, $data['received_by'] ?? null, $data['condition_on_transfer'] ?? null);
        $this->audit($request, 'sample.'.$event, $sample, 'Sample '.$sample->sample_code.': '.$event.($data['rejection_reason'] ?? ''));

        $message = match ($event) {
            'packaged' => $sample->sample_code.' has been packaged. Verify seal and transport conditions before dispatch.',
            'dispatched' => $sample->sample_code.' has been dispatched to '.$sample->laboratory?->name.' and is in transit.',
            'received' => $sample->sample_code.' was received at '.$sample->laboratory?->name.'. Laboratory reference: '.$sample->laboratory_reference_number.'.',
            'accepted' => $sample->sample_code.' passed laboratory quality control and is ready for assignment and testing.',
            'testing_started' => $sample->sample_code.' examination has started in '.$sample->laboratory_section.'.',
            default => $sample->sample_code.' quality status is '.$event.'. Reason: '.($data['rejection_reason'] ?? 'See sample custody record.'),
        };
        $this->notifyStakeholders($sample, 'Sample workflow updated', $message);

        return redirect()->route('samples.index')->with('status', 'Sample workflow updated: '.str_replace('_', ' ', $event).'.');
    }

    public function assign(Request $request, Sample $sample)
    {
        abort_unless(in_array($request->user()->role, ['laboratory_manager', 'zonal_laboratory_administrator', ...self::ADMIN_ROLES], true), 403);

        $data = $request->validate([
            'laboratory_section' => ['required', 'string', 'max:120'],
            'assigned_technician' => [
                'required',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', 'laboratory_technician')->where('is_active', true)),
            ],
            'laboratory_test_ids' => ['nullable', 'array'],
            'laboratory_test_ids.*' => ['integer', Rule::exists('laboratory_tests', 'id')->where(fn ($query) => $query->where('is_active', true))],
        ]);

        if ($sample->transport_status !== 'received' || $sample->quality_status !== 'accepted') {
            throw ValidationException::withMessages(['assigned_technician' => 'Only received, quality-accepted samples can be assigned for testing.']);
        }
        if (!$sample->laboratory_id || !$sample->laboratory?->is_active) {
            throw ValidationException::withMessages(['assigned_technician' => 'The sample must be assigned to an active registered laboratory.']);
        }
        if ($sample->test_status === 'approved') {
            throw ValidationException::withMessages(['assigned_technician' => 'The final approved sample cannot be reassigned.']);
        }

        $testIds = array_values(array_unique($data['laboratory_test_ids'] ?? []));
        foreach ($testIds as $testId) {
            $alreadyOpen = LaboratoryTestAssignment::where('sample_id', $sample->id)
                ->where('laboratory_test_id', $testId)
                ->whereIn('status', ['assigned', 'in_progress', 'submitted_for_review', 'approved'])
                ->exists();
            if ($alreadyOpen) {
                throw ValidationException::withMessages(['laboratory_test_ids' => 'A selected test already has an active or approved assignment for this sample.']);
            }
        }

        $sample->update([
            'laboratory_section' => $data['laboratory_section'],
            'assigned_technician' => $data['assigned_technician'],
            'assigned_by' => $request->user()->id,
            'assigned_at' => now(),
        ]);

        foreach ($testIds as $testId) {
            LaboratoryTestAssignment::create([
                'sample_id' => $sample->id,
                'laboratory_test_id' => $testId,
                'laboratory_id' => $sample->laboratory_id,
                'assigned_technician' => $data['assigned_technician'],
                'assigned_by' => $request->user()->id,
                'status' => 'assigned',
                'assigned_at' => now(),
            ]);
        }

        $this->recordMovement(
            $sample,
            $request,
            'assigned_for_testing',
            $sample->laboratory?->name,
            $sample->laboratory_section,
            'Assigned to '.$sample->assignedTechnician?->name.' by '.$request->user()->name.'.'
        );
        $this->audit($request, 'sample.assigned_for_testing', $sample, 'Assigned '.$sample->sample_code.' to '.$sample->assignedTechnician?->name.' ('.$sample->laboratory_section.').');
        $this->notifyStakeholders($sample, 'Sample assigned for testing', $sample->sample_code.' was assigned to you in '.$sample->laboratory_section.'.', [$sample->assigned_technician]);

        return redirect()->route('samples.index')->with('status', 'Sample assigned to '.$sample->assignedTechnician?->name.' for '.$sample->laboratory_section.'.');
    }

    private function recordMovement(Sample $sample, Request $request, string $event, ?string $from, ?string $to, ?string $notes = null, ?string $receivedBy = null, ?string $condition = null): void
    {
        SampleMovement::create([
            'sample_id' => $sample->id,
            'event_type' => $event,
            'from_location' => $from,
            'to_location' => $to,
            'received_by' => $receivedBy,
            'condition_on_transfer' => $condition,
            'notes' => $notes,
            'recorded_by' => $request->user()->id,
            'event_at' => now(),
        ]);
    }

    private function audit(Request $request, string $action, Sample $sample, string $description): void
    {
        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => $action,
            'entity_type' => 'sample',
            'entity_id' => (string) $sample->id,
            'ip_address' => $request->ip(),
            'description' => $description,
            'created_at' => now(),
        ]);
    }

    private function notifyStakeholders(Sample $sample, string $title, string $message, array $extraUserIds = []): void
    {
        $sample->loadMissing('diseaseCase');
        $userIds = array_filter(array_unique(array_merge(
            [$sample->collected_by, $sample->diseaseCase?->reported_by],
            $extraUserIds,
            User::where('is_active', true)
                ->whereIn('role', [...self::LAB_ROLES, ...self::LEADERSHIP_ROLES])
                ->pluck('id')->all()
        )));

        foreach ($userIds as $userId) {
            UserNotification::create([
                'user_id' => $userId,
                'title' => $title,
                'message' => $message,
                'category' => 'laboratory',
            ]);
        }
    }
}

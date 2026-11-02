<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Laboratory;
use App\Models\LaboratoryTestResult;
use App\Models\Sample;
use App\Models\SampleMovement;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LaboratoryResultController extends Controller
{
    private const ADMIN_ROLES = ['system_admin', 'administrator', 'admin'];
    private const LAB_REVIEW_ROLES = ['laboratory_manager', 'zonal_laboratory_administrator'];

    public function index(Request $request)
    {
        $query = LaboratoryTestResult::with(['sample.diseaseCase', 'laboratory', 'sample.assignedTechnician'])->latest();
        if ($request->user()->role === 'laboratory_technician') {
            $query->where('assigned_technician', $request->user()->id);
        }

        return view('laboratory.index', ['results' => $query->paginate(15)]);
    }

    public function create(Request $request)
    {
        abort_unless($request->user()->role === 'laboratory_technician', 403);

        return view('laboratory.create', [
            'samples' => Sample::where('quality_status', 'accepted')
                ->where('transport_status', 'received')
                ->where('assigned_technician', $request->user()->id)
                ->where('test_status', '!=', 'approved')
                ->with('laboratory')
                ->orderBy('sample_code')
                ->get(),
            'laboratories' => Laboratory::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->role === 'laboratory_technician', 403);

        $data = $request->validate([
            'sample_id' => ['required', 'exists:samples,id'],
            'laboratory_id' => ['required', 'exists:laboratories,id'],
            'test_name' => ['required', 'string', 'max:180'],
            'method' => ['nullable', 'string', 'max:180'],
            'observations' => ['nullable', 'string', 'max:5000'],
            'qualitative_result' => ['nullable', 'string', 'max:180'],
            'numeric_result' => ['nullable', 'numeric'],
            'numeric_unit' => ['nullable', 'string', 'max:50'],
            'technician_comments' => ['nullable', 'string', 'max:3000'],
        ]);

        $sample = Sample::with('diseaseCase')->findOrFail($data['sample_id']);
        if ($sample->quality_status !== 'accepted' || $sample->transport_status !== 'received') {
            throw ValidationException::withMessages(['sample_id' => 'The sample must be received and quality-accepted before testing.']);
        }
        if ((int) $sample->assigned_technician !== (int) $request->user()->id) {
            throw ValidationException::withMessages(['sample_id' => 'Only the technician assigned to this sample may record its laboratory test. Ask the laboratory manager to assign it first.']);
        }
        if (!$sample->laboratory_id || (int) $sample->laboratory_id !== (int) $data['laboratory_id']) {
            throw ValidationException::withMessages(['laboratory_id' => 'The selected laboratory must match the sample destination.']);
        }
        if ($sample->test_status === 'approved') {
            throw ValidationException::withMessages(['sample_id' => 'The sample has already been finally approved and released.']);
        }
        if (blank($data['qualitative_result'] ?? null) && !isset($data['numeric_result']) && blank($data['observations'] ?? null)) {
            throw ValidationException::withMessages(['qualitative_result' => 'Enter at least one finding: a qualitative result, a numeric result, or observations.']);
        }

        $data['result_code'] = 'RES-'.now()->format('ymd').'-'.Str::upper(Str::random(6));
        $data['assigned_technician'] = $request->user()->id;
        $data['status'] = 'submitted_for_review';
        $data['tested_at'] = now();
        $result = LaboratoryTestResult::create($data);

        $sample->update([
            'test_status' => 'completed',
            'testing_started_at' => $sample->testing_started_at ?: now(),
            'testing_completed_at' => now(),
        ]);
        SampleMovement::create([
            'sample_id' => $sample->id,
            'event_type' => 'test_submitted_for_review',
            'from_location' => $sample->laboratory_section ?: $sample->laboratory?->name,
            'to_location' => 'Laboratory manager review',
            'notes' => $result->result_code.' · '.$result->test_name.' submitted by '.$request->user()->name.'.',
            'recorded_by' => $request->user()->id,
            'event_at' => now(),
        ]);

        $this->audit($request, 'laboratory.result_submitted', $result, 'Submitted result '.$result->result_code.' for sample '.$sample->sample_code.'.');
        $this->notifyStakeholders($sample, 'Laboratory result awaiting review', $result->result_code.' for sample '.$sample->sample_code.' was submitted by '.$request->user()->name.' and is awaiting independent review.');

        return redirect()->route('laboratory.index')->with('status', 'Test result submitted for independent laboratory manager review.');
    }

    public function approve(Request $request, LaboratoryTestResult $result)
    {
        abort_unless(in_array($request->user()->role, [...self::LAB_REVIEW_ROLES, ...self::ADMIN_ROLES], true), 403);
        abort_unless($result->status === 'submitted_for_review', 422, 'Only results awaiting review can be approved or rejected.');

        if ((int) $result->assigned_technician === (int) $request->user()->id) {
            throw ValidationException::withMessages([
                'decision' => 'The person who performed a laboratory test cannot approve or reject the same result. An independent authorized reviewer must decide.',
            ]);
        }

        $data = $request->validate([
            'decision' => ['required', 'in:approve,reject'],
            'review_comments' => ['required', 'string', 'max:3000'],
        ]);

        $sample = $result->sample()->with(['diseaseCase.reporter', 'collector', 'assignedTechnician'])->firstOrFail();
        $approved = $data['decision'] === 'approve';

        $result->update([
            'reviewed_by' => $request->user()->id,
            'review_comments' => $data['review_comments'],
            'status' => $approved ? 'approved' : 'rejected',
            'approved_at' => $approved ? now() : null,
        ]);

        if ($approved) {
            // A rejected historical attempt does not block a later approved repeat.
            // The latest result for each named test is the operative result.
            $latestByTest = $sample->results()
                ->orderByDesc('id')
                ->get()
                ->groupBy('test_name')
                ->map(fn ($testResults) => $testResults->first());
            $allLatestResultsApproved = $latestByTest->isNotEmpty()
                && $latestByTest->every(fn (LaboratoryTestResult $item) => $item->status === 'approved');

            if ($allLatestResultsApproved) {
                $approvedResults = $latestByTest->values();
                $summaries = $approvedResults->map(function (LaboratoryTestResult $item) {
                    $finding = $item->qualitative_result
                        ?: ($item->numeric_result !== null ? $item->numeric_result.' '.$item->numeric_unit : $item->observations);
                    return $item->test_name.': '.($finding ?: 'No result text recorded');
                })->implode('; ');

                $sample->update([
                    'test_status' => 'approved',
                    'result_summary' => $summaries,
                    'approved_at' => now(),
                    'released_at' => now(),
                ]);

                $classifications = $approvedResults->map(function (LaboratoryTestResult $item) {
                    $finding = strtolower((string) ($item->qualitative_result ?: $item->observations));
                    if (preg_match('/\\b(negative|not detected|absent)\\b/', $finding)) {
                        return 'negative';
                    }
                    if (preg_match('/\\b(positive|detected|confirmed)\\b/', $finding)) {
                        return 'positive';
                    }
                    return 'inconclusive';
                });
                $caseStatus = $classifications->contains('positive')
                    ? 'confirmed'
                    : ($classifications->every(fn ($value) => $value === 'negative') ? 'not_confirmed' : 'laboratory_result_approved');
                $sample->diseaseCase()->update(['status' => $caseStatus]);

                SampleMovement::create([
                    'sample_id' => $sample->id,
                    'event_type' => 'final_result_released',
                    'from_location' => 'Laboratory manager review',
                    'to_location' => 'Disease surveillance / authorized case team',
                    'notes' => 'All latest recorded tests have been approved. Final result: '.$summaries,
                    'recorded_by' => $request->user()->id,
                    'event_at' => now(),
                ]);
                $this->notifyStakeholders($sample, 'Final laboratory result released', 'Sample '.$sample->sample_code.' has completed testing and independent review. Final result: '.$summaries.'. Case status: '.$caseStatus.'.');
            } else {
                $sample->update(['test_status' => 'completed']);
                SampleMovement::create([
                    'sample_id' => $sample->id,
                    'event_type' => 'test_result_approved',
                    'from_location' => 'Laboratory manager review',
                    'to_location' => 'Laboratory workflow',
                    'notes' => $result->result_code.' was approved; at least one other test remains pending or requires repeat testing.',
                    'recorded_by' => $request->user()->id,
                    'event_at' => now(),
                ]);
                $this->notifyStakeholders($sample, 'Laboratory test approved', $result->result_code.' was approved. Sample '.$sample->sample_code.' remains in the laboratory workflow until all latest test results are approved.');
            }
        } else {
            $sample->update([
                'test_status' => 'in_progress',
                'testing_completed_at' => null,
            ]);
            SampleMovement::create([
                'sample_id' => $sample->id,
                'event_type' => 'result_returned_for_correction',
                'from_location' => 'Laboratory manager review',
                'to_location' => $sample->laboratory_section ?: $sample->laboratory?->name,
                'notes' => $result->result_code.' returned to the assigned technician. Review comments: '.$data['review_comments'],
                'recorded_by' => $request->user()->id,
                'event_at' => now(),
            ]);
            $this->notifyStakeholders($sample, 'Laboratory result returned for correction', $result->result_code.' for sample '.$sample->sample_code.' was returned by the reviewer. Comments: '.$data['review_comments']);
        }

        $this->audit($request, 'laboratory.result_'.$data['decision'], $result, ucfirst($data['decision']).' result '.$result->result_code.' for sample '.$sample->sample_code.'.');

        return redirect()->route('laboratory.index')->with('status', $approved
            ? 'Laboratory review recorded. The sample is released as final only after all latest recorded tests are approved.'
            : 'Result returned to the assigned technician for correction or repeat testing.');
    }

    private function audit(Request $request, string $action, LaboratoryTestResult $result, string $description): void
    {
        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => $action,
            'entity_type' => 'laboratory_test_result',
            'entity_id' => (string) $result->id,
            'ip_address' => $request->ip(),
            'description' => $description,
            'created_at' => now(),
        ]);
    }

    private function notifyStakeholders(Sample $sample, string $title, string $message): void
    {
        $sample->loadMissing(['diseaseCase', 'collector']);
        $userIds = array_filter(array_unique(array_merge(
            [$sample->collected_by, $sample->diseaseCase?->reported_by, $sample->assigned_technician],
            User::where('is_active', true)
                ->whereIn('role', ['laboratory_manager', 'zonal_laboratory_administrator', 'regional_veterinary_officer', 'mlf_officer'])
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

<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Laboratory;
use App\Models\LaboratoryTestResult;
use App\Models\Sample;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LaboratoryResultController extends Controller
{
    public function index()
    {
        return view('laboratory.index', ['results' => LaboratoryTestResult::with(['sample.diseaseCase', 'laboratory'])->latest()->paginate(15)]);
    }

    public function create()
    {
        return view('laboratory.create', ['samples' => Sample::where('quality_status', 'accepted')->where('transport_status', 'received')->orderBy('sample_code')->get(), 'laboratories' => Laboratory::where('is_active', true)->orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
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
        $sample = Sample::findOrFail($data['sample_id']);
        if ($sample->quality_status !== 'accepted' || $sample->transport_status !== 'received') {
            throw ValidationException::withMessages(['sample_id' => 'The sample must be received by a laboratory and quality-accepted before testing.']);
        }
        if ($sample->laboratory_id && (int) $sample->laboratory_id !== (int) $data['laboratory_id']) {
            throw ValidationException::withMessages(['laboratory_id' => 'The selected laboratory does not match the sample destination.']);
        }
        $data['result_code'] = 'LAB-'.now()->format('ymd').'-'.Str::upper(Str::random(6));
        $data['assigned_technician'] = $request->user()->id;
        $data['status'] = 'submitted_for_review';
        $data['tested_at'] = now();
        $result = LaboratoryTestResult::create($data);
        Sample::whereKey($data['sample_id'])->update(['test_status' => 'completed']);
        AuditLog::create(['user_id' => $request->user()->id, 'action' => 'laboratory.result_submitted', 'entity_type' => 'laboratory_test_result', 'entity_id' => (string) $result->id, 'ip_address' => $request->ip(), 'description' => 'Submitted result '.$result->result_code, 'created_at' => now()]);
        return redirect()->route('laboratory.index')->with('status', 'Laboratory result submitted for review.');
    }

    public function approve(Request $request, LaboratoryTestResult $result)
    {
        abort_unless(in_array($request->user()->role, ['laboratory_manager', 'system_admin', 'administrator', 'admin'], true), 403);
        abort_unless($result->status === 'submitted_for_review', 422, 'Only results awaiting review can be approved or rejected.');\n        $data = $request->validate(['decision' => ['required', 'in:approve,reject'], 'review_comments' => ['required', 'string', 'max:3000']]);
        $result->update(['reviewed_by' => $request->user()->id, 'review_comments' => $data['review_comments'], 'status' => $data['decision'] === 'approve' ? 'approved' : 'rejected', 'approved_at' => $data['decision'] === 'approve' ? now() : null]);
        if ($data['decision'] === 'approve') {
            $result->sample()->update(['test_status' => 'approved', 'result_summary' => $result->qualitative_result ?: $result->observations, 'approved_at' => now()]);
            $result->sample->diseaseCase()->update(['status' => 'confirmed']);
        }
        AuditLog::create(['user_id' => $request->user()->id, 'action' => 'laboratory.result_'.$data['decision'], 'entity_type' => 'laboratory_test_result', 'entity_id' => (string) $result->id, 'ip_address' => $request->ip(), 'description' => ucfirst($data['decision']).' laboratory result '.$result->result_code, 'created_at' => now()]);
        return redirect()->route('laboratory.index')->with('status', 'Laboratory review recorded.');
    }
}

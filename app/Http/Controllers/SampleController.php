<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\DiseaseCase;
use App\Models\Laboratory;
use App\Models\Sample;
use App\Models\SampleMovement;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SampleController extends Controller
{
    public function index()
    {
        return view('samples.index', ['samples' => Sample::with(['diseaseCase.farm', 'laboratory'])->latest()->paginate(15)]);
    }

    public function create()
    {
        return view('samples.create', ['cases' => DiseaseCase::with('farm')->whereNotIn('status', ['closed'])->whereHas('investigation')->latest()->get(), 'laboratories' => Laboratory::where('is_active', true)->orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'disease_case_id' => ['required', 'exists:disease_cases,id'],
            'laboratory_id' => ['nullable', 'exists:laboratories,id'],
            'collection_date' => ['required', 'date', 'before_or_equal:today'],
            'sample_type' => ['required', 'string', 'max:120'],
            'collection_notes' => ['nullable', 'string', 'max:3000'],
        ]);
        $case = DiseaseCase::with('investigation')->findOrFail($data['disease_case_id']);
        if (!$case->investigation) {
            throw ValidationException::withMessages([
                'disease_case_id' => 'A disease investigation must be recorded before a sample can be registered.',
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
        SampleMovement::create(['sample_id' => $sample->id, 'event_type' => 'collected', 'from_location' => 'Field collection', 'to_location' => $sample->laboratory?->name, 'notes' => $data['collection_notes'] ?? null, 'recorded_by' => $request->user()->id, 'event_at' => now()]);
        $sample->diseaseCase()->update(['status' => 'sample_collected']);
        AuditLog::create(['user_id' => $request->user()->id, 'action' => 'sample.collected', 'entity_type' => 'sample', 'entity_id' => (string) $sample->id, 'ip_address' => $request->ip(), 'description' => 'Collected sample '.$code, 'created_at' => now()]);
        return redirect()->route('samples.index')->with('status', 'Sample registered. Sample ID: '.$code);
    }

    public function update(Request $request, Sample $sample)
    {
        $data = $request->validate([
            'event_type' => ['required', 'in:dispatched,received,accepted,rejected,damaged,contaminated,insufficient_volume,recollection_required'],
            'to_location' => ['nullable', 'string', 'max:180'],
            'received_by' => ['nullable', 'string', 'max:160'],
            'condition_on_transfer' => ['nullable', 'string', 'max:180'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'rejection_reason' => ['nullable', 'required_if:event_type,rejected', 'string', 'max:1000'],
        ]);
        $qualityEvents = ['accepted', 'rejected', 'damaged', 'contaminated', 'insufficient_volume', 'recollection_required'];
        if (in_array($data['event_type'], $qualityEvents, true) && $sample->transport_status !== 'received') {
            throw ValidationException::withMessages([
                'event_type' => 'Record sample receipt at the laboratory before recording a quality decision.',
            ]);
        }
        if ($data['event_type'] === 'dispatched' && in_array($sample->transport_status, ['in_transit', 'received'], true)) {
            throw ValidationException::withMessages([
                'event_type' => 'This sample has already been dispatched or received; record the next custody event instead.',
            ]);
        }

        $sample->update([
            'transport_status' => $data['event_type'] === 'dispatched' ? 'in_transit' : (in_array($data['event_type'], ['received','accepted','rejected','damaged','contaminated','insufficient_volume','recollection_required'], true) ? 'received' : $sample->transport_status),
            'quality_status' => in_array($data['event_type'], ['accepted','rejected','damaged','contaminated','insufficient_volume','recollection_required'], true) ? $data['event_type'] : $sample->quality_status,
            'rejection_reason' => $data['rejection_reason'] ?? $sample->rejection_reason,
            'received_at' => $data['event_type'] === 'received' ? now() : $sample->received_at,
        ]);
        SampleMovement::create(['sample_id' => $sample->id, 'event_type' => $data['event_type'], 'to_location' => $data['to_location'] ?? null, 'received_by' => $data['received_by'] ?? null, 'condition_on_transfer' => $data['condition_on_transfer'] ?? null, 'notes' => $data['notes'] ?? null, 'recorded_by' => $request->user()->id, 'event_at' => now()]);
        if (in_array($data['event_type'], ['received', 'accepted'], true)) {
            User::where('is_active', true)->whereIn('role', ['laboratory_technician', 'laboratory_manager'])->get()->each(function (User $recipient) use ($sample) {
                UserNotification::create(['user_id' => $recipient->id, 'title' => 'Sample received / quality updated', 'message' => $sample->sample_code.' has a new custody event. Check sample quality and the laboratory queue.', 'category' => 'laboratory']);
            });
        }
        AuditLog::create(['user_id' => $request->user()->id, 'action' => 'sample.movement_recorded', 'entity_type' => 'sample', 'entity_id' => (string) $sample->id, 'ip_address' => $request->ip(), 'description' => 'Sample '.$sample->sample_code.': '.$data['event_type'], 'created_at' => now()]);
        return redirect()->route('samples.index')->with('status', 'Sample custody and status updated.');
    }
}

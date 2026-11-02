<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\DiseaseCase;
use App\Models\Investigation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class InvestigationController extends Controller
{
    public function index()
    {
        return view('investigations.index', ['investigations' => Investigation::with(['diseaseCase.farm', 'assignee'])->latest()->paginate(15)]);
    }

    public function create()
    {
        return view('investigations.create', [
            'cases' => DiseaseCase::with('farm')->whereDoesntHave('investigation')->latest()->get(),
            'officers' => User::where('is_active', true)->whereIn('role', ['field_officer', 'administrator', 'admin', 'system_admin'])->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'disease_case_id' => ['required', 'exists:disease_cases,id', 'unique:investigations,disease_case_id'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'field_observations' => ['required', 'string', 'max:5000'],
            'epidemiological_findings' => ['nullable', 'string', 'max:5000'],
            'recommended_actions' => ['nullable', 'string', 'max:5000'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ]);
        $data['investigation_code'] = 'INV-'.now()->format('ymd').'-'.Str::upper(Str::random(6));
        $data['started_by'] = $request->user()->id;
        $data['investigated_at'] = now();
        $data['status'] = 'under_investigation';
        $investigation = Investigation::create($data);
        $case = DiseaseCase::findOrFail($data['disease_case_id']);
        $case->update(['status' => 'under_investigation']);
        AuditLog::create(['user_id' => $request->user()->id, 'action' => 'investigation.created', 'entity_type' => 'investigation', 'entity_id' => (string) $investigation->id, 'ip_address' => $request->ip(), 'description' => 'Started investigation '.$investigation->investigation_code, 'created_at' => now()]);
        return redirect()->route('investigations.index')->with('status', 'Investigation recorded successfully.');
    }

    public function update(Request $request, Investigation $investigation)
    {
        $data = $request->validate([
            'assigned_to' => ['nullable', 'exists:users,id'],
            'status' => ['required', 'in:under_investigation,sample_collected,completed,closed'],
            'field_observations' => ['required', 'string', 'max:5000'],
            'epidemiological_findings' => ['nullable', 'string', 'max:5000'],
            'recommended_actions' => ['nullable', 'string', 'max:5000'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ]);
        $investigation->update($data);
        AuditLog::create(['user_id' => $request->user()->id, 'action' => 'investigation.updated', 'entity_type' => 'investigation', 'entity_id' => (string) $investigation->id, 'ip_address' => $request->ip(), 'description' => 'Updated investigation '.$investigation->investigation_code, 'created_at' => now()]);
        return redirect()->route('investigations.index')->with('status', 'Investigation updated.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\DiseaseCase;
use App\Models\Farm;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class DiseaseCaseController extends Controller
{
    public function index(Request $request)
    {
        $query = DiseaseCase::with('farm')->latest();

        if ($request->user()->role === 'farmer') {
            abort_unless($request->user()->farmer_id, 403, 'Your account is not linked to a farmer record.');
            $farmerId = $request->user()->farmer_id;
            $query->where(function ($q) use ($request, $farmerId) {
                $q->where('reported_by', $request->user()->id)
                    ->orWhereHas('farm', fn ($farmQuery) => $farmQuery->where('farmer_id', $farmerId));
            });
        }

        return view('cases.index', ['cases' => $query->paginate(15)]);
    }

    public function create(Request $request)
    {
        $farms = Farm::query();
        if ($request->user()->role === 'farmer') {
            abort_unless($request->user()->farmer_id, 403, 'Your account is not linked to a farmer record.');
            $farms->where('farmer_id', $request->user()->farmer_id);
        }

        return view('cases.create', ['farms' => $farms->orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        $farmRule = Rule::exists('farms', 'id');
        if ($request->user()->role === 'farmer') {
            abort_unless($request->user()->farmer_id, 403, 'Your account is not linked to a farmer record.');
            $farmRule = Rule::exists('farms', 'id')->where(
                fn ($query) => $query->where('farmer_id', $request->user()->farmer_id)
            );
        }

        $data = $request->validate([
            'farm_id' => ['required', $farmRule],
            'disease_suspected' => ['required', 'string', 'max:160'],
            'date_observed' => ['required', 'date', 'before_or_equal:today'],
            'symptoms' => ['required', 'string', 'max:5000'],
            'birds_affected' => ['required', 'integer', 'min:1'],
            'birds_dead' => ['required', 'integer', 'min:0', 'lte:birds_affected'],
            'severity' => ['required', 'in:low,medium,high,critical'],
        ]);

        $data['case_code'] = 'CASE-'.now()->format('ymd').'-'.Str::upper(Str::random(5));
        $data['reported_by'] = $request->user()->id;
        $data['status'] = 'reported';

        $case = DiseaseCase::create($data);
        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'disease_case.created',
            'entity_type' => 'disease_case',
            'entity_id' => (string) $case->id,
            'ip_address' => $request->ip(),
            'description' => 'Reported disease case '.$case->case_code,
            'created_at' => now(),
        ]);

        return redirect()->route('cases.index')->with('status', 'Disease case reported successfully.');
    }
}

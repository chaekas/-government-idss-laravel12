<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Farmer;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FarmerController extends Controller
{
    public function index(Request $request)
    {
        $farmers = Farmer::query()
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%'.$request->string('q')->toString().'%';
                $query->where(function ($nested) use ($term) {
                    $nested->where('farmer_code', 'like', $term)
                        ->orWhere('national_id', 'like', $term)
                        ->orWhere('phone', 'like', $term)
                        ->orWhere('full_name', 'like', $term);
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('farmers.index', compact('farmers'));
    }

    public function create()
    {
        return view('farmers.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:160'],
            'sex' => ['nullable', 'string', 'max:30'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'nationality' => ['nullable', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:160'],
            'national_id' => ['nullable', 'string', 'max:100', 'unique:farmers,national_id'],
            'region' => ['required', 'string', 'max:100'],
            'district' => ['required', 'string', 'max:100'],
            'ward' => ['nullable', 'string', 'max:100'],
            'village' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:active,inactive,deceased,suspended'],
        ]);

        $data['farmer_code'] = 'FRM-'.now()->format('Y').'-'.Str::upper(Str::random(6));
        $farmer = Farmer::create($data);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'farmer.created',
            'entity_type' => 'farmer',
            'entity_id' => (string) $farmer->id,
            'ip_address' => $request->ip(),
            'description' => 'Registered farmer '.$farmer->farmer_code,
            'created_at' => now(),
        ]);

        return redirect()->route('farmers.index')->with('status', 'Farmer registered successfully. Farmer ID: '.$farmer->farmer_code);
    }
}

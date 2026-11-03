<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Farmer;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

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

        $potentialDuplicate = Farmer::whereRaw('LOWER(full_name) = ?', [mb_strtolower($data['full_name'])])
            ->where('phone', $data['phone'])
            ->first();
        if ($potentialDuplicate) {
            throw ValidationException::withMessages([
                'phone' => 'A farmer with the same name and mobile number already exists ('.$potentialDuplicate->farmer_code.'). Verify the identity before continuing.',
            ]);
        }

        $data['farmer_code'] = 'FRM-'.now()->format('Y').'-'.Str::upper(Str::random(6));
        $data['created_by'] = $request->user()->id;
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

    public function edit(Farmer $farmer)
    {
        return view('farmers.create', compact('farmer'));
    }

    public function update(Request $request, Farmer $farmer)
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:160'],
            'sex' => ['nullable', 'string', 'max:30'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'nationality' => ['nullable', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:160'],
            'national_id' => ['nullable', 'string', 'max:100', Rule::unique('farmers', 'national_id')->ignore($farmer->id)],
            'region' => ['required', 'string', 'max:100'],
            'district' => ['required', 'string', 'max:100'],
            'ward' => ['nullable', 'string', 'max:100'],
            'village' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:active,inactive,deceased,suspended'],
        ]);

        $duplicate = Farmer::whereRaw('LOWER(full_name) = ?', [mb_strtolower($data['full_name'])])
            ->where('phone', $data['phone'])->whereKeyNot($farmer->id)->first();
        if ($duplicate) {
            throw ValidationException::withMessages([
                'phone' => 'Another farmer with the same name and mobile number exists ('.$duplicate->farmer_code.'). Verify the identity before updating.',
            ]);
        }

        $data['modified_by'] = $request->user()->id;
        $farmer->update($data);
        AuditLog::create([
            'user_id' => $request->user()->id, 'action' => 'farmer.updated',
            'entity_type' => 'farmer', 'entity_id' => (string) $farmer->id,
            'ip_address' => $request->ip(), 'description' => 'Updated farmer '.$farmer->farmer_code,
            'created_at' => now(),
        ]);

        return redirect()->route('farmers.index')->with('status', 'Farmer record updated.');
    }

    public function destroy(Request $request, Farmer $farmer)
    {
        abort_unless(in_array($request->user()->role, ['system_admin','super_admin','super-admin','administrator','admin','regional_veterinary_officer'], true), 403);
        $farmer->update(['status' => 'inactive', 'archived_at' => now(), 'modified_by' => $request->user()->id]);
        AuditLog::create([
            'user_id' => $request->user()->id, 'action' => 'farmer.archived',
            'entity_type' => 'farmer', 'entity_id' => (string) $farmer->id,
            'ip_address' => $request->ip(), 'description' => 'Archived farmer '.$farmer->farmer_code,
            'created_at' => now(),
        ]);
        return redirect()->route('farmers.index')->with('status', 'Farmer archived; historical farm and case records were preserved.');
    }


}

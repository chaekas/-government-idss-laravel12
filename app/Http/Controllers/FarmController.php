<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Farm;
use App\Models\FarmLocation;
use App\Models\FarmType;
use App\Models\Farmer;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class FarmController extends Controller
{
    public function index(Request $request)
    {
        $farms = Farm::with(['farmer', 'farmType'])
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%'.$request->string('q')->toString().'%';
                $query->where(function ($nested) use ($term) {
                    $nested->where('farm_code', 'like', $term)
                        ->orWhere('name', 'like', $term)
                        ->orWhere('region', 'like', $term)
                        ->orWhere('district', 'like', $term);
                })->orWhereHas('farmer', fn ($farmer) => $farmer->where('full_name', 'like', $term));
            })
            ->latest()->paginate(15)->withQueryString();

        return view('farms.index', compact('farms'));
    }

    public function create()
    {
        return view('farms.create', [
            'farmers' => Farmer::where('status', 'active')->orderBy('full_name')->get(),
            'farmTypes' => FarmType::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'farmer_id' => ['required', 'exists:farmers,id'],
            'farm_type_id' => ['required', 'exists:farm_types,id'],
            'name' => ['required', 'string', 'max:160'],
            'region' => ['required', 'string', 'max:100'],
            'district' => ['required', 'string', 'max:100'],
            'ward' => ['nullable', 'string', 'max:100'],
            'village' => ['nullable', 'string', 'max:100'],
            'administrative_area_id' => ['nullable', 'exists:administrative_areas,id'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'poultry_type' => ['nullable', 'string', 'max:100'],
            'bird_count' => ['required', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'duplicate_verification_confirmed' => ['sometimes', 'boolean'],
        ]);

        $potentialDuplicate = null;
        if (($data['latitude'] ?? null) !== null && ($data['longitude'] ?? null) !== null) {
            $potentialDuplicate = Farm::where('farmer_id', $data['farmer_id'])
                ->where('latitude', $data['latitude'])
                ->where('longitude', $data['longitude'])
                ->first();
        }

        $duplicateVerified = (bool) ($data['duplicate_verification_confirmed'] ?? false);
        if ($potentialDuplicate && !$duplicateVerified) {
            throw ValidationException::withMessages([
                'latitude' => 'A farm with the same owner and GPS coordinates already exists ('.$potentialDuplicate->farm_code.'). Verify the location and confirm whether this is a separate farm before continuing.',
            ]);
        }

        unset($data['duplicate_verification_confirmed']);
        $data['farm_code'] = 'FARM-'.now()->format('Y').'-'.Str::upper(Str::random(6));
        $data['created_by'] = $request->user()->id;
        if ($duplicateVerified) {
            $data['duplicate_verified_at'] = now();
            $data['duplicate_verified_by'] = $request->user()->id;
        }

        $farm = Farm::create($data);
        $farm->farmers()->syncWithoutDetaching([
            $data['farmer_id'] => ['ownership_type' => 'owner', 'is_primary' => true, 'effective_from' => now()->toDateString()],
        ]);

        FarmLocation::create([
            'farm_id' => $farm->id,
            'administrative_area_id' => $data['administrative_area_id'] ?? null,
            'location_type' => 'primary',
            'region' => $data['region'],
            'district' => $data['district'],
            'ward' => $data['ward'] ?? null,
            'village' => $data['village'] ?? null,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'is_primary' => true,
        ]);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'farm.created',
            'entity_type' => 'farm',
            'entity_id' => (string) $farm->id,
            'ip_address' => $request->ip(),
            'description' => 'Registered farm '.$farm->farm_code,
            'metadata' => $duplicateVerified ? ['duplicate_verification' => 'confirmed'] : null,
            'created_at' => now(),
        ]);

        return redirect()->route('farms.index')->with('status', 'Farm registered successfully. Farm ID: '.$farm->farm_code);
    }

    public function edit(Farm $farm)
    {
        return view('farms.create', [
            'farm' => $farm,
            'farmers' => Farmer::where('status', 'active')->orderBy('full_name')->get(),
            'farmTypes' => FarmType::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Farm $farm)
    {
        $data = $request->validate([
            'farmer_id' => ['required', 'exists:farmers,id'],
            'farm_type_id' => ['required', 'exists:farm_types,id'],
            'name' => ['required', 'string', 'max:160'],
            'region' => ['required', 'string', 'max:100'],
            'district' => ['required', 'string', 'max:100'],
            'ward' => ['nullable', 'string', 'max:100'],
            'village' => ['nullable', 'string', 'max:100'],
            'administrative_area_id' => ['nullable', 'exists:administrative_areas,id'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'poultry_type' => ['nullable', 'string', 'max:100'],
            'bird_count' => ['required', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'duplicate_verification_confirmed' => ['sometimes', 'boolean'],
        ]);

        $duplicate = null;
        if (($data['latitude'] ?? null) !== null && ($data['longitude'] ?? null) !== null) {
            $duplicate = Farm::where('farmer_id', $data['farmer_id'])
                ->where('latitude', $data['latitude'])->where('longitude', $data['longitude'])
                ->where('id', '<>', $farm->id)->first();
        }
        $duplicateVerified = (bool) ($data['duplicate_verification_confirmed'] ?? false);
        if ($duplicate && !$duplicateVerified) {
            throw ValidationException::withMessages([
                'latitude' => 'A different farm with this owner and GPS coordinates exists ('.$duplicate->farm_code.'). Verify whether this is a separate farm before saving.',
            ]);
        }

        unset($data['duplicate_verification_confirmed']);
        $newFarmerId = (int) $data['farmer_id'];
        $oldFarmerId = (int) $farm->farmer_id;
        $data['modified_by'] = $request->user()->id;
        if ($duplicateVerified) {
            $data['duplicate_verified_at'] = now();
            $data['duplicate_verified_by'] = $request->user()->id;
        }

        DB::transaction(function () use ($farm, $data, $newFarmerId, $oldFarmerId, $request) {
            $farm->update($data);
            if ($newFarmerId !== $oldFarmerId) {
                $farm->farmers()->updateExistingPivot($oldFarmerId, [
                    'effective_to' => now()->toDateString(), 'is_primary' => false,
                ]);
                $farm->farmers()->syncWithoutDetaching([
                    $newFarmerId => ['ownership_type' => 'owner', 'is_primary' => true, 'effective_from' => now()->toDateString(), 'effective_to' => null],
                ]);
            }
            $farm->locations()->where('is_primary', true)->update(['is_primary' => false]);
            FarmLocation::create([
                'farm_id' => $farm->id, 'administrative_area_id' => $data['administrative_area_id'] ?? null,
                'location_type' => 'primary', 'region' => $data['region'], 'district' => $data['district'],
                'ward' => $data['ward'] ?? null, 'village' => $data['village'] ?? null,
                'latitude' => $data['latitude'] ?? null, 'longitude' => $data['longitude'] ?? null, 'is_primary' => true,
            ]);
            AuditLog::create([
                'user_id' => $request->user()->id, 'action' => 'farm.updated', 'entity_type' => 'farm',
                'entity_id' => (string) $farm->id, 'ip_address' => $request->ip(),
                'description' => 'Updated farm '.$farm->farm_code.' and retained location/ownership history.',
                'created_at' => now(),
            ]);
        });

        return redirect()->route('farms.index')->with('status', 'Farm record updated. Previous ownership and location history were retained.');
    }

    public function destroy(Request $request, Farm $farm)
    {
        abort_unless(in_array($request->user()->role, ['system_admin','super_admin','super-admin','administrator','admin','regional_veterinary_officer'], true), 403);
        $farm->update(['status' => 'inactive', 'archived_at' => now(), 'modified_by' => $request->user()->id]);
        AuditLog::create([
            'user_id' => $request->user()->id, 'action' => 'farm.archived', 'entity_type' => 'farm',
            'entity_id' => (string) $farm->id, 'ip_address' => $request->ip(),
            'description' => 'Archived farm '.$farm->farm_code.'; associated animal and case history retained.',
            'created_at' => now(),
        ]);
        return redirect()->route('farms.index')->with('status', 'Farm archived; animal and disease history were preserved.');
    }


}

<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Farm;
use App\Models\FarmLocation;
use App\Models\FarmType;
use App\Models\Farmer;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
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
}

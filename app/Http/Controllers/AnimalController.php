<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\AnimalIdentification;
use App\Models\AnimalMovement;
use App\Models\AuditLog;
use App\Models\Farm;
use App\Models\Species;
use App\Models\Breed;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AnimalController extends Controller
{
    public function index(Request $request)
    {
        $animals = Animal::with(['farm', 'farmer', 'identifications'])
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%'.$request->string('q')->toString().'%';
                $query->where(function ($nested) use ($term) {
                    $nested->where('animal_code', 'like', $term)
                        ->orWhere('species', 'like', $term)
                        ->orWhere('external_identifier', 'like', $term)
                        ->orWhereHas('identifications', fn ($ids) => $ids->where('identifier_value', 'like', $term));
                });
            })->latest()->paginate(15)->withQueryString();

        return view('animals.index', compact('animals'));
    }

    public function create()
    {
        return view('animals.create', [
            'farms' => Farm::with('farmer')->orderBy('name')->get(),
            'speciesOptions' => Species::where('is_active', true)->orderBy('name')->get(),
            'breedOptions' => Breed::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'farm_id' => ['required', 'exists:farms,id'],
            'species' => ['nullable', 'required_without:species_id', 'string', 'max:80'],
            'species_id' => ['nullable', 'exists:species,id'],
            'breed' => ['nullable', 'string', 'max:100'],
            'breed_id' => ['nullable', 'exists:breeds,id'],
            'sex' => ['nullable', 'in:female,male,unknown'],
            'date_of_birth' => ['nullable', 'date', 'before_or_equal:today'],
            'identification_method' => ['nullable', 'string', 'max:100'],
            'external_identifier' => ['nullable', 'string', 'max:150', 'unique:animals,external_identifier'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ]);

        if (!empty($data['breed_id']) && !empty($data['species_id']) && !Breed::whereKey($data['breed_id'])->where('species_id', $data['species_id'])->exists()) {
            return back()->withInput()->withErrors(['breed_id' => 'The selected breed does not belong to the selected species.']);
        }

        $farm = Farm::with('farmer')->findOrFail($data['farm_id']);
        $data['farmer_id'] = $farm->farmer_id;
        if (!empty($data['species_id'])) {
            $data['species'] = Species::findOrFail($data['species_id'])->name;
        }
        if (!empty($data['breed_id'])) {
            $data['breed'] = Breed::findOrFail($data['breed_id'])->name;
        }

        $data['animal_code'] = 'ANI-'.now()->format('Y').'-'.Str::upper(Str::random(8));
        $data['created_by'] = $request->user()->id;
        $animal = Animal::create($data);

        if (!empty($data['external_identifier'])) {
            AnimalIdentification::create([
                'animal_id' => $animal->id,
                'identification_type' => $data['identification_method'] ?: 'external',
                'identifier_value' => $data['external_identifier'],
                'status' => 'active',
                'issued_at' => now()->toDateString(),
            ]);
        }

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'animal.created',
            'entity_type' => 'animal',
            'entity_id' => (string) $animal->id,
            'ip_address' => $request->ip(),
            'description' => 'Registered animal '.$animal->animal_code,
            'created_at' => now(),
        ]);

        return redirect()->route('animals.index')->with('status', 'Animal registered. Animal ID: '.$animal->animal_code);
    }

    public function edit(Animal $animal)
    {
        return view('animals.create', [
            'animal' => $animal,
            'farms' => Farm::with('farmer')->orderBy('name')->get(),
            'speciesOptions' => Species::where('is_active', true)->orderBy('name')->get(),
            'breedOptions' => Breed::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Animal $animal)
    {
        $data = $request->validate([
            'farm_id' => ['required', 'exists:farms,id'],
            'species' => ['nullable', 'required_without:species_id', 'string', 'max:80'],
            'species_id' => ['nullable', 'exists:species,id'],
            'breed' => ['nullable', 'string', 'max:100'],
            'breed_id' => ['nullable', 'exists:breeds,id'],
            'sex' => ['nullable', 'in:female,male,unknown'],
            'date_of_birth' => ['nullable', 'date', 'before_or_equal:today'],
            'identification_method' => ['nullable', 'string', 'max:100'],
            'external_identifier' => ['nullable', 'string', 'max:150', Rule::unique('animals', 'external_identifier')->ignore($animal->id)],
            'notes' => ['nullable', 'string', 'max:3000'],
        ]);

        if (!empty($data['breed_id']) && !empty($data['species_id']) && !Breed::whereKey($data['breed_id'])->where('species_id', $data['species_id'])->exists()) {
            return back()->withInput()->withErrors(['breed_id' => 'The selected breed does not belong to the selected species.']);
        }

        $farm = Farm::with('farmer')->findOrFail($data['farm_id']);
        $oldFarmId = (int) $animal->farm_id;
        $data['farmer_id'] = $farm->farmer_id;
        $data['species'] = !empty($data['species_id']) ? Species::findOrFail($data['species_id'])->name : $data['species'];
        $data['breed'] = !empty($data['breed_id']) ? Breed::findOrFail($data['breed_id'])->name : ($data['breed'] ?? null);
        $data['modified_by'] = $request->user()->id;

        DB::transaction(function () use ($animal, $data, $farm, $oldFarmId, $request) {
            $animal->update($data);
            if ((int) $farm->id !== $oldFarmId) {
                AnimalMovement::create([
                    'animal_id' => $animal->id, 'from_farm_id' => $oldFarmId, 'to_farm_id' => $farm->id,
                    'movement_reason' => 'registry_update', 'moved_at' => now(),
                    'recorded_by' => $request->user()->id, 'notes' => 'Farm association updated through animal registry.',
                ]);
            }

            $newIdentifier = $data['external_identifier'] ?? null;
            $activeIdentifier = $animal->identifications()->where('status', 'active')->first();
            if ($activeIdentifier && $activeIdentifier->identifier_value !== $newIdentifier) {
                $activeIdentifier->update(['status' => 'retired', 'retired_at' => now()->toDateString()]);
            }
            if ($newIdentifier && (!$activeIdentifier || $activeIdentifier->identifier_value !== $newIdentifier)) {
                AnimalIdentification::create([
                    'animal_id' => $animal->id, 'identification_type' => $data['identification_method'] ?: 'external',
                    'identifier_value' => $newIdentifier, 'status' => 'active', 'issued_at' => now()->toDateString(),
                ]);
            }

            AuditLog::create([
                'user_id' => $request->user()->id, 'action' => 'animal.updated', 'entity_type' => 'animal',
                'entity_id' => (string) $animal->id, 'ip_address' => $request->ip(),
                'description' => 'Updated animal '.$animal->animal_code.'; identity and movement history retained.',
                'created_at' => now(),
            ]);
        });

        return redirect()->route('animals.index')->with('status', 'Animal record updated. Previous identification and movement history were retained.');
    }

    public function destroy(Request $request, Animal $animal)
    {
        abort_unless(in_array($request->user()->role, ['system_admin','super_admin','super-admin','administrator','admin','regional_veterinary_officer'], true), 403);
        $animal->update(['record_status' => 'archived', 'ownership_status' => 'inactive', 'archived_at' => now(), 'modified_by' => $request->user()->id]);
        AuditLog::create([
            'user_id' => $request->user()->id, 'action' => 'animal.archived', 'entity_type' => 'animal',
            'entity_id' => (string) $animal->id, 'ip_address' => $request->ip(),
            'description' => 'Archived animal '.$animal->animal_code.'; health and identification history retained.',
            'created_at' => now(),
        ]);
        return redirect()->route('animals.index')->with('status', 'Animal archived; health, ownership and movement history were preserved.');
    }


}

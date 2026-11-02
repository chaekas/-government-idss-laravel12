<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\AnimalIdentification;
use App\Models\AuditLog;
use App\Models\Farm;
use App\Models\Species;
use App\Models\Breed;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

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
            'species' => ['required', 'string', 'max:80'],
            'species_id' => ['nullable', 'exists:species,id'],
            'breed' => ['nullable', 'string', 'max:100'],
            'breed_id' => ['nullable', 'exists:breeds,id'],
            'sex' => ['nullable', 'in:female,male,unknown'],
            'date_of_birth' => ['nullable', 'date', 'before_or_equal:today'],
            'identification_method' => ['nullable', 'string', 'max:100'],
            'external_identifier' => ['nullable', 'string', 'max:150', 'unique:animals,external_identifier'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ]);

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
}

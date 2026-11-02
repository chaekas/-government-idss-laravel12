<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\AuditLog;
use App\Models\Farm;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AnimalController extends Controller
{
    public function index(Request $request)
    {
        $animals = Animal::with('farm')->when($request->filled('q'), function ($query) use ($request) {
            $term = '%'.$request->string('q')->toString().'%';
            $query->where(function ($q) use ($term) {
                $q->where('animal_code', 'like', $term)->orWhere('species', 'like', $term)->orWhere('external_identifier', 'like', $term);
            });
        })->latest()->paginate(15)->withQueryString();
        return view('animals.index', compact('animals'));
    }

    public function create()
    {
        return view('animals.create', ['farms' => Farm::orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'farm_id' => ['required', 'exists:farms,id'],
            'species' => ['required', 'string', 'max:80'],
            'breed' => ['nullable', 'string', 'max:100'],
            'sex' => ['nullable', 'in:female,male,unknown'],
            'date_of_birth' => ['nullable', 'date', 'before_or_equal:today'],
            'identification_method' => ['nullable', 'string', 'max:100'],
            'external_identifier' => ['nullable', 'string', 'max:150', 'unique:animals,external_identifier'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ]);
        $data['animal_code'] = 'ANI-'.now()->format('ymd').'-'.Str::upper(Str::random(6));
        $animal = Animal::create($data);
        AuditLog::create(['user_id' => $request->user()->id, 'action' => 'animal.created', 'entity_type' => 'animal', 'entity_id' => (string) $animal->id, 'ip_address' => $request->ip(), 'description' => 'Registered animal '.$animal->animal_code, 'created_at' => now()]);
        return redirect()->route('animals.index')->with('status', 'Animal registered. Animal ID: '.$animal->animal_code);
    }
}

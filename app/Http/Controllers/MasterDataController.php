<?php

namespace App\Http\Controllers;

use App\Models\AdministrativeArea;
use App\Models\Department;
use App\Models\Office;
use App\Models\Organization;
use App\Models\AuditLog;
use App\Models\Breed;
use App\Models\DiseaseCategory;
use App\Models\DiseaseSymptom;
use App\Models\FarmType;
use App\Models\LaboratoryTest;
use App\Models\SampleType;
use App\Models\Species;
use App\Models\TestMethod;
use App\Models\SurveillanceAlertRule;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MasterDataController extends Controller
{
    private function definitions(): array
    {
        return [
            'organizations' => ['title' => 'Organizations', 'model' => Organization::class, 'fields' => ['code','name','organization_type','is_active']],
            'departments' => ['title' => 'Departments', 'model' => Department::class, 'fields' => ['organization_id','code','name','is_active']],
            'offices' => ['title' => 'Offices', 'model' => Office::class, 'fields' => ['organization_id','department_id','administrative_area_id','code','name','office_type','is_active']],
            'surveillance-alert-rules' => ['title' => 'Surveillance alert rules', 'model' => SurveillanceAlertRule::class, 'fields' => ['code','name','severity','case_threshold','within_hours','notify_field_officers','notify_regional_officers','notify_ministry_officers','is_active']],
            'farm-types' => ['title' => 'Farm types', 'model' => FarmType::class, 'fields' => ['code','name','description','sort_order','is_active']],
            'species' => ['title' => 'Species', 'model' => Species::class, 'fields' => ['code','name','description','is_active']],
            'breeds' => ['title' => 'Breeds', 'model' => Breed::class, 'fields' => ['species_id','code','name','description','is_active']],
            'disease-categories' => ['title' => 'Disease categories', 'model' => DiseaseCategory::class, 'fields' => ['code','name','description','is_active']],
            'disease-symptoms' => ['title' => 'Disease symptoms', 'model' => DiseaseSymptom::class, 'fields' => ['code','name','description','is_active']],
            'sample-types' => ['title' => 'Sample types', 'model' => SampleType::class, 'fields' => ['code','name','description','is_active']],
            'laboratory-tests' => ['title' => 'Laboratory tests', 'model' => LaboratoryTest::class, 'fields' => ['code','name','disease_category_id','specimen_requirements','description','is_active']],
            'test-methods' => ['title' => 'Test methods', 'model' => TestMethod::class, 'fields' => ['code','name','standard_reference','is_active']],
            'administrative-areas' => ['title' => 'Administrative areas', 'model' => AdministrativeArea::class, 'fields' => ['parent_id','area_type','code','name','source_reference','is_active']],
        ];
    }

    private function definition(string $type): array
    {
        abort_unless(isset($this->definitions()[$type]), 404);
        return $this->definitions()[$type];
    }

    public function index(Request $request, string $type)
    {
        $definition = $this->definition($type);
        $model = $definition['model'];
        $records = $model::query()->when($request->filled('q'), function ($query) use ($request) {
            $term = '%'.$request->string('q')->toString().'%';
            $query->where(function ($nested) use ($term) {
                $nested->where('code', 'like', $term)->orWhere('name', 'like', $term);
            });
        })->orderBy('name')->paginate(20)->withQueryString();

        return view('master-data.index', compact('type', 'definition', 'records'));
    }

    public function create(string $type)
    {
        $definition = $this->definition($type);
        $options = $this->formOptions();
        return view('master-data.form', compact('type', 'definition', 'options'));
    }

    public function store(Request $request, string $type)
    {
        $definition = $this->definition($type);
        $data = $this->validatedData($request, $type);
        $modelClass = $definition['model'];
        $record = $modelClass::create($data);
        $this->audit($request, 'master_data.created', $type, $record->getKey());
        return redirect()->route('master-data.index', $type)->with('status', $definition['title'].' record created.');
    }

    public function edit(string $type, int $record)
    {
        $definition = $this->definition($type);
        $modelClass = $definition['model'];
        $item = $modelClass::findOrFail($record);
        $options = $this->formOptions();
        return view('master-data.form', compact('type', 'definition', 'item', 'options'));
    }

    public function update(Request $request, string $type, int $record)
    {
        $definition = $this->definition($type);
        $modelClass = $definition['model'];
        $item = $modelClass::findOrFail($record);
        $item->update($this->validatedData($request, $type, $item->getKey()));
        $this->audit($request, 'master_data.updated', $type, $item->getKey());
        return redirect()->route('master-data.index', $type)->with('status', $definition['title'].' record updated.');
    }

    public function destroy(Request $request, string $type, int $record)
    {
        $definition = $this->definition($type);
        $modelClass = $definition['model'];
        $item = $modelClass::findOrFail($record);
        $item->delete();
        $this->audit($request, 'master_data.archived', $type, $item->getKey());
        return redirect()->route('master-data.index', $type)->with('status', 'Reference record archived.');
    }

    private function formOptions(): array
    {
        return [
            'organizations' => Organization::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'departments' => Department::where('is_active', true)->orderBy('name')->get(['id', 'organization_id', 'name']),
            'species' => Species::orderBy('name')->get(['id', 'name']),
            'diseaseCategories' => DiseaseCategory::orderBy('name')->get(['id', 'name']),
            'administrativeAreas' => AdministrativeArea::orderBy('name')->get(['id', 'name', 'area_type']),
        ];
    }

    private function audit(Request $request, string $action, string $type, int $id): void
    {
        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => $action,
            'entity_type' => 'master_data:'.$type,
            'entity_id' => (string) $id,
            'ip_address' => $request->ip(),
            'description' => ucfirst(str_replace('-', ' ', $type)).' reference record '.$action,
            'created_at' => now(),
        ]);
    }

    private function validatedData(Request $request, string $type, ?int $ignoreId = null): array
    {
        $unique = fn (string $table, string $column = 'code') => Rule::unique($table, $column)->ignore($ignoreId);
        $rules = match ($type) {
            'organizations' => ['code' => ['required','string','max:80',$unique('organizations')], 'name' => ['required','string','max:180'], 'organization_type' => ['nullable','string','max:80'], 'is_active' => ['required','boolean']],
            'departments' => ['organization_id' => ['nullable','exists:organizations,id'], 'code' => ['required','string','max:80',$unique('departments')], 'name' => ['required','string','max:180'], 'is_active' => ['required','boolean']],
            'offices' => ['organization_id' => ['nullable','exists:organizations,id'], 'department_id' => ['nullable','exists:departments,id'], 'administrative_area_id' => ['nullable','exists:administrative_areas,id'], 'code' => ['required','string','max:80',$unique('offices')], 'name' => ['required','string','max:180'], 'office_type' => ['nullable','string','max:80'], 'is_active' => ['required','boolean']],
            'surveillance-alert-rules' => ['code' => ['required','string','max:80',$unique('surveillance_alert_rules')], 'name' => ['required','string','max:160'], 'severity' => ['required',Rule::in(['low','medium','high','critical'])], 'case_threshold' => ['required','integer','min:1','max:100000'], 'within_hours' => ['required','integer','min:1','max:8760'], 'notify_field_officers' => ['required','boolean'], 'notify_regional_officers' => ['required','boolean'], 'notify_ministry_officers' => ['required','boolean'], 'is_active' => ['required','boolean']],
            'farm-types' => ['code' => ['required','string','max:80',$unique('farm_types')], 'name' => ['required','string','max:120',$unique('farm_types','name')], 'description' => ['nullable','string','max:2000'], 'sort_order' => ['nullable','integer','min:0'], 'is_active' => ['required','boolean']],
            'species' => ['code' => ['required','string','max:80',$unique('species')], 'name' => ['required','string','max:120',$unique('species','name')], 'description' => ['nullable','string','max:2000'], 'is_active' => ['required','boolean']],
            'breeds' => ['species_id' => ['required','exists:species,id'], 'code' => ['required','string','max:80'], 'name' => ['required','string','max:120'], 'description' => ['nullable','string','max:2000'], 'is_active' => ['required','boolean']],
            'disease-categories' => ['code' => ['required','string','max:80',$unique('disease_categories')], 'name' => ['required','string','max:160',$unique('disease_categories','name')], 'description' => ['nullable','string','max:2000'], 'is_active' => ['required','boolean']],
            'disease-symptoms' => ['code' => ['required','string','max:80',$unique('disease_symptoms')], 'name' => ['required','string','max:160',$unique('disease_symptoms','name')], 'description' => ['nullable','string','max:2000'], 'is_active' => ['required','boolean']],
            'sample-types' => ['code' => ['required','string','max:80',$unique('sample_types')], 'name' => ['required','string','max:160',$unique('sample_types','name')], 'description' => ['nullable','string','max:2000'], 'is_active' => ['required','boolean']],
            'laboratory-tests' => ['code' => ['required','string','max:80',$unique('laboratory_tests')], 'name' => ['required','string','max:180'], 'disease_category_id' => ['nullable','exists:disease_categories,id'], 'specimen_requirements' => ['nullable','string','max:255'], 'description' => ['nullable','string','max:2000'], 'is_active' => ['required','boolean']],
            'test-methods' => ['code' => ['required','string','max:80',$unique('test_methods')], 'name' => ['required','string','max:160'], 'standard_reference' => ['nullable','string','max:180'], 'is_active' => ['required','boolean']],
            'administrative-areas' => ['parent_id' => ['nullable','exists:administrative_areas,id'], 'area_type' => ['required',Rule::in(['region','district','ward','village'])], 'code' => ['required','string','max:80',$unique('administrative_areas')], 'name' => ['required','string','max:160'], 'source_reference' => ['nullable','string','max:180'], 'is_active' => ['required','boolean']],
            default => abort(404),
        };

        $data = $request->validate($rules);
        if ($type === 'offices' && !empty($data['department_id']) && !empty($data['organization_id'])) {
            if (!Department::whereKey($data['department_id'])->where('organization_id', $data['organization_id'])->exists()) { throw ValidationException::withMessages(['department_id' => 'The selected department does not belong to the selected organization.']); }
        }
        if ($type === 'breeds') {
            $exists = Breed::where('species_id', $data['species_id'])->where('code', $data['code'])->when($ignoreId, fn ($q) => $q->where('id', '<>', $ignoreId))->exists();
            abort_if($exists, 422, 'The breed code already exists for this species.');
        }
        return $data;
    }
}

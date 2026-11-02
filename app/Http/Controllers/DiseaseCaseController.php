<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\DiseaseCase;
use App\Models\DiseaseCaseStatusHistory;
use App\Models\Farm;
use App\Models\Animal;
use App\Models\DiseaseCategory;
use App\Models\DiseaseSymptom;
use App\Models\AdministrativeArea;
use App\Models\User;
use App\Models\UserNotification;
use App\Models\SurveillanceAlertRule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DiseaseCaseController extends Controller
{
    private const STAFF_ROLES = ['field_officer','regional_veterinary_officer','mlf_officer','system_admin','super_admin','super-admin','administrator','admin'];

    public function index(Request $request)
    {
        $query = DiseaseCase::with(['farm','diseaseCategory','reporter','investigation'])
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->string('q')->toString().'%';
                $q->where(fn ($nested) => $nested->where('case_code','like',$term)->orWhere('disease_suspected','like',$term)->orWhereHas('farm',fn($farm)=>$farm->where('name','like',$term)->orWhere('farm_code','like',$term)));
            })
            ->when($request->filled('status'), fn($q)=>$q->where('status',$request->string('status')->toString()))
            ->when($request->filled('severity'), fn($q)=>$q->where('severity',$request->string('severity')->toString()))
            ->when($request->filled('from'), fn($q)=>$q->whereDate('date_observed','>=',$request->date('from')))
            ->when($request->filled('to'), fn($q)=>$q->whereDate('date_observed','<=',$request->date('to')))
            ->latest('reported_at');

        if ($request->user()->role === 'farmer') {
            abort_unless($request->user()->farmer_id,403,'Your account is not linked to a farmer record.');
            $query->whereHas('farm',fn($farm)=>$farm->where('farmer_id',$request->user()->farmer_id));
        }

        return view('cases.index',['cases'=>$query->paginate(20)->withQueryString()]);
    }

    public function create(Request $request)
    {
        $farms = Farm::with('farmer')->where('status','active');
        if ($request->user()->role === 'farmer') {
            abort_unless($request->user()->farmer_id,403,'Your account is not linked to a farmer record.');
            $farmerId=$request->user()->farmer_id;
            $farms->where(fn($q)=>$q->where('farmer_id',$farmerId)->orWhereHas('farmers',fn($owners)=>$owners->where('farmers.id',$farmerId)->whereNull('farmer_farm.effective_to)));
        }
        return view('cases.create',[
            'farms'=>$farms->orderBy('name')->get(),
            'animals'=>Animal::where('ownership_status','active')->orderBy('animal_code')->get(),
            'categories'=>DiseaseCategory::where('is_active',true)->orderBy('name')->get(),
            'symptoms'=>DiseaseSymptom::where('is_active',true)->orderBy('name')->get(),
            'areas'=>AdministrativeArea::where('is_active',true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $farmRule = Rule::exists('farms','id')->where(fn($q)=>$q->where('status','active'));
        if ($request->user()->role === 'farmer') {
            abort_unless($request->user()->farmer_id,403,'Your account is not linked to a farmer record.');
            $farmerId=$request->user()->farmer_id;
            $farmRule=Rule::exists('farms','id')->where(fn($q)=>$q->where('status','active'));
            $requestedFarmId=$request->input('farm_id');
            if($requestedFarmId && !Farm::whereKey($requestedFarmId)->where(fn($q)=>$q->where('farmer_id',$farmerId)->orWhereHas('farmers',fn($owners)=>$owners->where('farmers.id',$farmerId)->whereNull('farmer_farm.effective_to')))->exists()) {
                throw ValidationException::withMessages(['farm_id'=>'You may report disease only for a farm currently linked to your farmer account.']);
            }
        }

        $data=$request->validate([
            'farm_id'=>['required',$farmRule],
            'animal_id'=>['nullable','exists:animals,id'],
            'disease_category_id'=>['nullable','exists:disease_categories,id'],
            'administrative_area_id'=>['nullable','exists:administrative_areas,id'],
            'disease_suspected'=>['required','string','max:160'],
            'affected_species'=>['required','string','max:120'],
            'date_observed'=>['required','date','before_or_equal:today'],
            'symptoms'=>['required','string','max:5000'],
            'symptom_ids'=>['nullable','array'],
            'symptom_ids.*'=>['integer','distinct','exists:disease_symptoms,id'],
            'birds_affected'=>['required','integer','min:1'],
            'birds_dead'=>['required','integer','min:0','lte:birds_affected'],
            'severity'=>['required','in:low,medium,high,critical'],
            'latitude'=>['nullable','numeric','between:-90,90','required_with:longitude'],
            'longitude'=>['nullable','numeric','between:-180,180','required_with:latitude'],
            'location_description'=>['nullable','string','max:255'],
            'attachments'=>['nullable','array','max:5'],
            'attachments.*'=>['file','mimes:jpg,jpeg,png,pdf','max:10240'],
        ]);

        $farm=Farm::findOrFail($data['farm_id']);
        if($request->user()->role==='farmer' && (int)$farm->farmer_id !== (int)$request->user()->farmer_id && !$farm->farmers()->where('farmers.id',$request->user()->farmer_id)->whereNull('farmer_farm.effective_to')->exists()) throw ValidationException::withMessages(['farm_id'=>'You may only report against a farm linked to your farmer account.']);
        if (!empty($data['animal_id']) && !Animal::whereKey($data['animal_id'])->where('farm_id',$farm->id)->exists()) {
            throw ValidationException::withMessages(['animal_id'=>'The selected animal does not belong to the selected farm.']);
        }
        if (!isset($data['latitude']) && !isset($data['longitude']) && $farm->latitude === null && $farm->longitude === null) {
            throw ValidationException::withMessages(['latitude'=>'Capture the outbreak GPS coordinates or register coordinates on the farm record.']);
        }
        if (!empty($data['symptom_ids']) && DiseaseSymptom::whereIn('id',$data['symptom_ids'])->where('is_active',true)->count() !== count($data['symptom_ids'])) {
            throw ValidationException::withMessages(['symptom_ids'=>'Select only active symptom catalogue entries.']);
        }

        $files=$request->file('attachments',[]);
        unset($data['attachments'],$data['symptom_ids']);
        $data['case_code']='CASE-'.now()->format('ymd').'-'.Str::upper(Str::random(6));
        $data['reported_by']=$request->user()->id;
        $data['reported_at']=now();
        $data['reporting_channel']=$request->user()->role === 'farmer' ? 'farmer_portal' : 'staff_portal';
        $data['status']='reported';
        $data['latitude']=$data['latitude'] ?? $farm->latitude;
        $data['longitude']=$data['longitude'] ?? $farm->longitude;

        DB::transaction(function () use ($data,$files,$request,&$case) {
            $case=DiseaseCase::create($data);
            DiseaseCaseStatusHistory::create(['disease_case_id'=>$case->id,'from_status'=>null,'to_status'=>'reported','reason'=>'Initial disease case report','changed_by'=>$request->user()->id,'changed_at'=>now()]);
            $case->symptoms()->sync($request->input('symptom_ids',[]));
            foreach($files as $file) {
                $path=$file->store('surveillance/cases/'.$case->id,'local');
                $case->attachments()->create(['uploaded_by'=>$request->user()->id,'original_name'=>$file->getClientOriginalName(),'file_path'=>$path,'mime_type'=>$file->getMimeType() ?: 'application/octet-stream','file_size'=>$file->getSize(),'uploaded_at'=>now()]);
            }
            AuditLog::create(['user_id'=>$request->user()->id,'action'=>'disease_case.created','entity_type'=>'disease_case','entity_id'=>(string)$case->id,'ip_address'=>$request->ip(),'description'=>'Reported disease case '.$case->case_code,'created_at'=>now()]);
        });

        $this->notifySurveillanceUsers($case,'New suspected disease case',$case->case_code.' was reported at '.$farm->name.' ('.$farm->district.', '.$farm->region.'). The report is suspected and requires field investigation.','surveillance');
        return redirect()->route('cases.index')->with('status','Disease case reported successfully. Case ID: '.$case->case_code);
    }

    public function edit(Request $request,DiseaseCase $case)
    {
        $this->authorizeCase($request,$case);
        abort_if($case->investigation()->exists(),403,'A disease report cannot be edited after investigation has begun.');
        return view('cases.edit',[
            'case'=>$case->load(['symptoms','attachments']),
            'farms'=>Farm::where('status','active')->orderBy('name')->get(),
            'animals'=>Animal::where('farm_id',$case->farm_id)->orderBy('animal_code')->get(),
            'categories'=>DiseaseCategory::where('is_active',true)->orderBy('name')->get(),
            'symptoms'=>DiseaseSymptom::where('is_active',true)->orderBy('name')->get(),
            'areas'=>AdministrativeArea::where('is_active',true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request,DiseaseCase $case)
    {
        $this->authorizeCase($request,$case);
        abort_if($case->investigation()->exists(),403,'A disease report cannot be edited after investigation has begun.');
        $data=$request->validate([
            'farm_id'=>['required','exists:farms,id'],'animal_id'=>['nullable','exists:animals,id'],
            'disease_category_id'=>['nullable','exists:disease_categories,id'],'administrative_area_id'=>['nullable','exists:administrative_areas,id'],
            'disease_suspected'=>['required','string','max:160'],'affected_species'=>['required','string','max:120'],
            'date_observed'=>['required','date','before_or_equal:today'],'symptoms'=>['required','string','max:5000'],
            'symptom_ids'=>['nullable','array'],'symptom_ids.*'=>['integer','distinct','exists:disease_symptoms,id'],
            'birds_affected'=>['required','integer','min:1'],'birds_dead'=>['required','integer','min:0','lte:birds_affected'],
            'severity'=>['required','in:low,medium,high,critical'],'latitude'=>['nullable','numeric','between:-90,90','required_with:longitude'],
            'longitude'=>['nullable','numeric','between:-180,180','required_with:latitude'],'location_description'=>['nullable','string','max:255'],
            'attachments'=>['nullable','array','max:5'],'attachments.*'=>['file','mimes:jpg,jpeg,png,pdf','max:10240'],
        ]);
        $farm=Farm::findOrFail($data['farm_id']);
        if($request->user()->role==='farmer' && !$farm->farmers()->where('farmers.id',$request->user()->farmer_id)->whereNull('farmer_farm.effective_to')->exists()) throw ValidationException::withMessages(['farm_id'=>'You may only update a report to a farm linked to your farmer account.']);
        if (!empty($data['animal_id']) && !Animal::whereKey($data['animal_id'])->where('farm_id',$farm->id)->exists()) throw ValidationException::withMessages(['animal_id'=>'The selected animal does not belong to the selected farm.']);
        if (empty($data['latitude']) && empty($data['longitude']) && empty($farm->latitude) && empty($farm->longitude)) throw ValidationException::withMessages(['latitude'=>'Capture the outbreak GPS coordinates or register coordinates on the farm record.']);
        $files=$request->file('attachments',[]); $symptoms=$data['symptom_ids'] ?? [];
        unset($data['attachments'],$data['symptom_ids']);
        $data['latitude']=$data['latitude'] ?? $farm->latitude; $data['longitude']=$data['longitude'] ?? $farm->longitude; $data['modified_by']=$request->user()->id;
        DB::transaction(function () use($case,$data,$files,$symptoms,$request) {
            $case->update($data); $case->symptoms()->sync($symptoms);
            foreach($files as $file){$path=$file->store('surveillance/cases/'.$case->id,'local');$case->attachments()->create(['uploaded_by'=>$request->user()->id,'original_name'=>$file->getClientOriginalName(),'file_path'=>$path,'mime_type'=>$file->getMimeType() ?: 'application/octet-stream','file_size'=>$file->getSize(),'uploaded_at'=>now()]);}
            AuditLog::create(['user_id'=>$request->user()->id,'action'=>'disease_case.updated','entity_type'=>'disease_case','entity_id'=>(string)$case->id,'ip_address'=>$request->ip(),'description'=>'Updated disease case '.$case->case_code,'created_at'=>now()]);
        });
        return redirect()->route('cases.index')->with('status','Disease case updated.');
    }

    private function authorizeCase(Request $request,DiseaseCase $case): void
    {
        if($request->user()->role==='farmer') abort_unless((int)$case->farm?->farmer_id === (int)$request->user()->farmer_id || $case->farm?->farmers()->where('farmers.id',$request->user()->farmer_id)->whereNull('farmer_farm.effective_to')->exists(),403);
        else abort_unless(in_array($request->user()->role,self::STAFF_ROLES,true),403);
    }

    private function notifySurveillanceUsers(DiseaseCase $case,string $title,string $message,string $category): void
    {
        $recipients=User::where('is_active',true)
            ->whereIn('role',['field_officer','regional_veterinary_officer'])
            ->where('id','<>',$case->reported_by)->get()->keyBy('id');

        $levels=['low'=>1,'medium'=>2,'high'=>3,'critical'=>4];
        foreach(SurveillanceAlertRule::where('is_active',true)->get() as $rule) {
            if(($levels[$case->severity]??0)<($levels[$rule->severity]??99)) continue;
            $farm=$case->farm;
            $matchingCases=DiseaseCase::where('disease_suspected',$case->disease_suspected)
                ->where('reported_at','>=',now()->subHours($rule->within_hours))
                ->whereHas('farm',fn($q)=>$q->where('district',$farm?->district)->where('region',$farm?->region))
                ->count();
            if($matchingCases < $rule->case_threshold) continue;
            $roles=[];
            if($rule->notify_field_officers) $roles[]='field_officer';
            if($rule->notify_regional_officers) $roles[]='regional_veterinary_officer';
            if($rule->notify_ministry_officers) $roles=array_merge($roles,['mlf_officer','system_admin']);
            User::where('is_active',true)->whereIn('role',$roles)->where('id','<>',$case->reported_by)->get()->each(fn(User $user)=>$recipients->put($user->id,$user));
        }

        foreach($recipients as $recipient) {
            UserNotification::create([
                'user_id'=>$recipient->id,'title'=>$title,'message'=>$message,
                'category'=>$category,
            ]);
        }
    }}

<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\DiseaseCase;
use App\Models\DiseaseCategory;
use App\Models\AdministrativeArea;
use App\Models\Outbreak;
use App\Models\OutbreakStatusHistory;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Services\UserDataScope;

class OutbreakController extends Controller
{
    public function index(Request $request)
    {
        $query=Outbreak::with(['diseaseCases.farm','diseaseCategory','statusHistory.changedBy'])->when($request->filled('q'),function($q)use($request){
            $term='%'.$request->string('q')->toString().'%';
            $q->where(fn($n)=>$n->where('outbreak_code','like',$term)->orWhere('disease_name','like',$term)->orWhere('district','like',$term)->orWhere('region','like',$term));
        })->when($request->filled('status'),fn($q)=>$q->where('status',$request->string('status')->toString()))->latest();
        $query = app(UserDataScope::class)->outbreaks($query, $request->user());
        return view('outbreaks.index',['outbreaks'=>$query->paginate(15)->withQueryString()]);
    }

    public function create(Request $request)
    {
        $cases = app(UserDataScope::class)->cases(DiseaseCase::with('farm')->whereIn('status',['reported','under_investigation','sample_collected','laboratory_testing','confirmed'])->latest(), $request->user());
        return view('outbreaks.create',[
            'cases'=>$cases->get(),
            'categories'=>DiseaseCategory::where('is_active',true)->orderBy('name')->get(),
            'areas'=>AdministrativeArea::where('is_active',true)
                ->when(!$request->user()->hasRole(['system_admin','super_admin','super-admin','administrator','admin','mlf_officer']), fn ($areas) => $areas->whereIn('id', $request->user()->accessibleAdministrativeAreaIds() ?: [-1]))
                ->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data=$this->validated($request);
        $caseIds=$data['case_ids']??[]; unset($data['case_ids']);
        $cases = collect();
        if($caseIds){
            $cases=app(UserDataScope::class)->cases(DiseaseCase::with('farm')->whereIn('id',$caseIds), $request->user())->get();
            if($cases->count()!==count($caseIds)) throw ValidationException::withMessages(['case_ids'=>'One or more selected cases are invalid or outside your assigned geographic scope.']);
            foreach($cases as $case) if($case->farm?->district!==$data['district'] || $case->farm?->region!==$data['region']) throw ValidationException::withMessages(['case_ids'=>'Only disease cases from the outbreak district and region can be linked.']);
            $caseAreaIds = $cases->map(fn ($case) => $case->administrative_area_id ?: $case->farm?->administrative_area_id)->filter()->unique()->values();
            if (empty($data['administrative_area_id']) && $caseAreaIds->count() === 1) $data['administrative_area_id'] = $caseAreaIds->first();
            if ($caseAreaIds->count() > 1 || ($caseAreaIds->isNotEmpty() && (int) $data['administrative_area_id'] !== (int) $caseAreaIds->first())) {
                throw ValidationException::withMessages(['administrative_area_id' => 'The outbreak area must match the linked disease cases.']);
            }
            $data['cases_count']=$cases->count(); $data['deaths_count']=$cases->sum('birds_dead');
        }
        $this->authorizeArea($request, $data['administrative_area_id'] ?? null);
        $data['outbreak_code']='OUT-'.now()->format('ymd').'-'.Str::upper(Str::random(6));
        $data['reported_by']=$request->user()->id; $data['status']='suspected';
        DB::transaction(function()use($data,$caseIds,$request,&$outbreak){
            $outbreak=Outbreak::create($data);
            $outbreak->diseaseCases()->sync($caseIds);
            OutbreakStatusHistory::create(['outbreak_id'=>$outbreak->id,'from_status'=>null,'to_status'=>'suspected','reason'=>'Initial suspected outbreak record.','changed_by'=>$request->user()->id,'changed_at'=>now()]);
            AuditLog::create(['user_id'=>$request->user()->id,'action'=>'outbreak.created','entity_type'=>'outbreak','entity_id'=>(string)$outbreak->id,'ip_address'=>$request->ip(),'description'=>'Recorded suspected outbreak '.$outbreak->outbreak_code,'created_at'=>now()]);
        });
        $this->notifyOutbreakStakeholders($outbreak,'Suspected disease outbreak recorded',$outbreak->outbreak_code.' — '.$outbreak->disease_name.' in '.$outbreak->district.', '.$outbreak->region.'. Verify through the official investigation workflow.');
        return redirect()->route('outbreaks.index')->with('status','Suspected outbreak recorded. ID: '.$outbreak->outbreak_code);
    }

    public function update(Request $request,Outbreak $outbreak)
    {
        $this->authorizeOutbreak($request, $outbreak);
        abort_unless(in_array($request->user()->role,['field_officer','regional_veterinary_officer','mlf_officer','system_admin','super_admin','super-admin','administrator','admin'],true),403);
        abort_if($outbreak->status==='closed',422,'Closed outbreak records are locked.');
        $data=$this->validated($request,true);
        if($request->user()->role==='field_officer' && $data['status']!==$outbreak->status) abort(403,'Only regional or Ministry surveillance leadership may change outbreak status.');
        $caseIds=$data['case_ids']??[]; unset($data['case_ids']);
        if($caseIds){
            $cases=app(UserDataScope::class)->cases(DiseaseCase::with('farm')->whereIn('id',$caseIds), $request->user())->get();
            if($cases->count()!==count($caseIds)) throw ValidationException::withMessages(['case_ids'=>'One or more selected cases are invalid or outside your assigned geographic scope.']);
            foreach($cases as $case) if($case->farm?->district!==$data['district'] || $case->farm?->region!==$data['region']) throw ValidationException::withMessages(['case_ids'=>'Only disease cases from the outbreak district and region can be linked.']);
            $caseAreaIds = $cases->map(fn ($case) => $case->administrative_area_id ?: $case->farm?->administrative_area_id)->filter()->unique()->values();
            if (!$data['administrative_area_id'] && $caseAreaIds->count() === 1) $data['administrative_area_id'] = $caseAreaIds->first();
            if ($caseAreaIds->count() > 1 || ($caseAreaIds->isNotEmpty() && (int) ($data['administrative_area_id'] ?? 0) !== (int) $caseAreaIds->first())) {
                throw ValidationException::withMessages(['administrative_area_id' => 'The outbreak area must match the linked disease cases.']);
            }
        }
        $data['administrative_area_id'] = $data['administrative_area_id'] ?? $outbreak->administrative_area_id;
        $this->authorizeArea($request, $data['administrative_area_id'] ?? null);
        $previousStatus=$outbreak->status;
        $data['modified_by']=$request->user()->id;
        if($data['status']==='active' && !$outbreak->confirmed_at) $data['confirmed_at']=now();
        if($data['status']==='closed') $data['closed_at']=now();
        DB::transaction(function()use($outbreak,$data,$caseIds,$request,$previousStatus){
            $outbreak->update($data); $outbreak->diseaseCases()->sync($caseIds);
            if($previousStatus!==$outbreak->status) OutbreakStatusHistory::create(['outbreak_id'=>$outbreak->id,'from_status'=>$previousStatus,'to_status'=>$outbreak->status,'reason'=>$data['response_actions'] ?? null,'changed_by'=>$request->user()->id,'changed_at'=>now()]);
            AuditLog::create(['user_id'=>$request->user()->id,'action'=>'outbreak.updated','entity_type'=>'outbreak','entity_id'=>(string)$outbreak->id,'ip_address'=>$request->ip(),'description'=>'Updated outbreak '.$outbreak->outbreak_code.' to '.$outbreak->status,'created_at'=>now()]);
        });
        $this->notifyOutbreakStakeholders($outbreak,'Disease outbreak status updated',$outbreak->outbreak_code.' is now '.$outbreak->status.'.');
        return redirect()->route('outbreaks.index')->with('status','Outbreak record updated.');
    }

    private function authorizeArea(Request $request, ?int $areaId): void
    {
        if ($request->user()->hasRole(['system_admin','super_admin','super-admin','administrator','admin','mlf_officer'])) return;
        $allowed = $request->user()->accessibleAdministrativeAreaIds();
        if (!$areaId || !in_array((int) $areaId, $allowed, true)) {
            throw ValidationException::withMessages(['administrative_area_id' => 'Select an administrative area assigned to your account.']);
        }
    }

    private function authorizeOutbreak(Request $request, Outbreak $outbreak): void
    {
        abort_unless(app(UserDataScope::class)->outbreaks(Outbreak::query()->whereKey($outbreak->id), $request->user())->exists(), 404);
    }

    private function validated(Request $request,bool $updating=false): array
    {
        return $request->validate([
            'disease_name'=>['required','string','max:160'],'disease_category_id'=>['nullable','exists:disease_categories,id'],
            'administrative_area_id'=>['nullable','exists:administrative_areas,id'],
            'region'=>['required','string','max:100'],'district'=>['required','string','max:100'],
            'latitude'=>['nullable','numeric','between:-90,90','required_with:longitude'],'longitude'=>['nullable','numeric','between:-180,180','required_with:latitude'],
            'start_date'=>['required','date','before_or_equal:today'],'cases_count'=>['required','integer','min:1'],'deaths_count'=>['required','integer','min:0'],
            'severity'=>['required','in:low,medium,high,critical'],
            'status'=>[$updating?'required':'sometimes','in:suspected,active,closed'],
            'response_actions'=>['nullable','string','max:5000'],
            'case_ids'=>['nullable','array'],'case_ids.*'=>['integer','distinct','exists:disease_cases,id'],
        ]);
    }

    private function notifyOutbreakStakeholders(Outbreak $outbreak,string $title,string $message): void
    {
        User::where('is_active',true)->whereIn('role',['field_officer','regional_veterinary_officer','mlf_officer','system_admin'])->get()->each(fn(User $recipient)=>UserNotification::create(['user_id'=>$recipient->id,'title'=>$title,'message'=>$message,'category'=>'outbreak']));
    }
}

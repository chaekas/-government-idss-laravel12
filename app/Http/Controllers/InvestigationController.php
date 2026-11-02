<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\DiseaseCase;
use App\Models\Investigation;
use App\Models\InvestigationHistory;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use App\Services\DiseaseCaseWorkflow;

class InvestigationController extends Controller
{
    private const ASSIGNEE_ROLES=['field_officer'];
    private const APPROVER_ROLES=['regional_veterinary_officer','mlf_officer','system_admin','super_admin','super-admin','administrator','admin'];

    public function index(Request $request)
    {
        $query=Investigation::with(['diseaseCase.farm','assignee','approver','attachments','history.changedBy'])->latest();
        if($request->user()->role==='field_officer') $query->where('assigned_to',$request->user()->id)->orWhere('started_by',$request->user()->id);
        return view('investigations.index',['investigations'=>$query->paginate(15)->withQueryString()]);
    }

    public function create()
    {
        return view('investigations.create',[
            'cases'=>DiseaseCase::with('farm')->whereIn('status',['reported','under_investigation'])->whereDoesntHave('investigation')->latest()->get(),
            'officers'=>User::where('is_active',true)->whereIn('role',self::ASSIGNEE_ROLES)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data=$request->validate([
            'disease_case_id'=>['required','exists:disease_cases,id','unique:investigations,disease_case_id'],
            'assigned_to'=>['required',Rule::exists('users','id')->where(fn($q)=>$q->whereIn('role',self::ASSIGNEE_ROLES)->where('is_active',true))],
            'investigated_at'=>['required','date','before_or_equal:today'],
            'field_observations'=>['required','string','max:5000'],
            'epidemiological_findings'=>['nullable','string','max:5000'],
            'possible_source'=>['nullable','string','max:3000'],
            'transmission_route'=>['nullable','string','max:3000'],
            'biosecurity_observations'=>['nullable','string','max:3000'],
            'immediate_control_measures'=>['nullable','string','max:3000'],
            'recommended_actions'=>['nullable','string','max:5000'],
            'latitude'=>['nullable','numeric','between:-90,90','required_with:longitude'],
            'longitude'=>['nullable','numeric','between:-180,180','required_with:latitude'],
            'attachments'=>['nullable','array','max:10'],
            'attachments.*'=>['file','mimes:jpg,jpeg,png,pdf,doc,docx','max:15360'],
        ]);
        $case=DiseaseCase::with('farm')->findOrFail($data['disease_case_id']);
        if($data['investigated_at'] < $case->date_observed->toDateString()) throw ValidationException::withMessages(['investigated_at'=>'Investigation date cannot precede the disease report observation date.']);
        if($case->investigation()->exists()) throw ValidationException::withMessages(['disease_case_id'=>'An investigation already exists for this case.']);
        $files=$request->file('attachments',[]); unset($data['attachments']);
        $data['investigation_code']='INV-'.now()->format('ymd').'-'.Str::upper(Str::random(6));
        $data['started_by']=$request->user()->id;
        $data['status']='under_investigation';

        DB::transaction(function() use($data,$files,$request,$case,&$investigation){
            $investigation=Investigation::create($data);
            DiseaseCaseWorkflow::transition($case,'under_investigation',$request->user()->id,'Investigation '.$investigation->investigation_code.' initiated.');
            foreach($files as $file){$path=$file->store('surveillance/investigations/'.$investigation->id,'local');$investigation->attachments()->create(['uploaded_by'=>$request->user()->id,'original_name'=>$file->getClientOriginalName(),'file_path'=>$path,'mime_type'=>$file->getMimeType() ?: 'application/octet-stream','file_size'=>$file->getSize(),'uploaded_at'=>now()]);}
            InvestigationHistory::create(['investigation_id'=>$investigation->id,'event_type'=>'created','new_values'=>$data,'notes'=>'Investigation initiated and assigned.','changed_by'=>$request->user()->id,'changed_at'=>now()]);
            AuditLog::create(['user_id'=>$request->user()->id,'action'=>'investigation.created','entity_type'=>'investigation','entity_id'=>(string)$investigation->id,'ip_address'=>$request->ip(),'description'=>'Started investigation '.$investigation->investigation_code,'created_at'=>now()]);
        });
        $assignee=User::find($data['assigned_to']);
        UserNotification::create(['user_id'=>$assignee->id,'title'=>'Disease investigation assigned','message'=>$investigation->investigation_code.' has been assigned to you for case '.$case->case_code.'.','category'=>'surveillance']);
        return redirect()->route('investigations.index')->with('status','Investigation created and assigned. ID: '.$investigation->investigation_code);
    }

    public function update(Request $request,Investigation $investigation)
    {
        abort_unless(!$investigation->approved_at,403,'Approved investigation reports are locked.');
        abort_unless(in_array($request->user()->role,['field_officer','regional_veterinary_officer','system_admin','super_admin','super-admin','administrator','admin'],true),403);
        if($request->user()->role==='field_officer') abort_unless((int)$investigation->assigned_to===(int)$request->user()->id,403);
        $data=$request->validate([
            'assigned_to'=>['required',Rule::exists('users','id')->where(fn($q)=>$q->whereIn('role',self::ASSIGNEE_ROLES)->where('is_active',true))],
            'investigated_at'=>['required','date','before_or_equal:today'],
            'status'=>['required','in:under_investigation,sample_collected,completed'],
            'field_observations'=>['required','string','max:5000'],
            'epidemiological_findings'=>['nullable','string','max:5000'],
            'possible_source'=>['nullable','string','max:3000'],
            'transmission_route'=>['nullable','string','max:3000'],
            'biosecurity_observations'=>['nullable','string','max:3000'],
            'immediate_control_measures'=>['nullable','string','max:3000'],
            'recommended_actions'=>['nullable','string','max:5000'],
            'latitude'=>['nullable','numeric','between:-90,90','required_with:longitude'],
            'longitude'=>['nullable','numeric','between:-180,180','required_with:latitude'],
            'attachments'=>['nullable','array','max:10'],
            'attachments.*'=>['file','mimes:jpg,jpeg,png,pdf,doc,docx','max:15360'],
        ]);
        $case=$investigation->diseaseCase;
        if($data['investigated_at'] < $case->date_observed->toDateString()) throw ValidationException::withMessages(['investigated_at'=>'Investigation date cannot precede the disease report observation date.']);
        if($data['status']==='completed' && blank($data['recommended_actions'])) throw ValidationException::withMessages(['recommended_actions'=>'At least one recommendation is required before completing an investigation.']);
        $files=$request->file('attachments',[]); unset($data['attachments']);
        $previous=$investigation->only(array_keys($data));
        DB::transaction(function() use($investigation,$data,$files,$request,$previous){
            $investigation->update($data);
            foreach($files as $file){$path=$file->store('surveillance/investigations/'.$investigation->id,'local');$investigation->attachments()->create(['uploaded_by'=>$request->user()->id,'original_name'=>$file->getClientOriginalName(),'file_path'=>$path,'mime_type'=>$file->getMimeType() ?: 'application/octet-stream','file_size'=>$file->getSize(),'uploaded_at'=>now()]);}
            InvestigationHistory::create(['investigation_id'=>$investigation->id,'event_type'=>'updated','previous_values'=>$previous,'new_values'=>$data,'notes'=>'Investigation details updated.','changed_by'=>$request->user()->id,'changed_at'=>now()]);
            AuditLog::create(['user_id'=>$request->user()->id,'action'=>'investigation.updated','entity_type'=>'investigation','entity_id'=>(string)$investigation->id,'ip_address'=>$request->ip(),'description'=>'Updated investigation '.$investigation->investigation_code,'created_at'=>now()]);
        });
        return redirect()->route('investigations.index')->with('status','Investigation updated and history recorded.');
    }

    public function report(Request $request,Investigation $investigation)
    {
        $role=$request->user()->role;
        abort_unless(in_array($role,['field_officer','regional_veterinary_officer','mlf_officer','system_admin','super_admin','super-admin','administrator','admin'],true),403);
        if($role==='field_officer') abort_unless((int)$investigation->assigned_to===(int)$request->user()->id || (int)$investigation->started_by===(int)$request->user()->id,403);
        return view('investigations.report',['investigation'=>$investigation->load(['diseaseCase.farm','assignee','approver','attachments','history.changedBy'])]);
    }

    public function approve(Request $request,Investigation $investigation)
    {
        abort_unless(in_array($request->user()->role,self::APPROVER_ROLES,true),403);
        abort_if($investigation->approved_at,422,'This investigation has already been approved.');
        abort_if($investigation->status!=='completed',422,'Complete the investigation and record recommendations before approval.');
        abort_if(blank($investigation->recommended_actions),422,'At least one recommendation is required before approval.');
        if(in_array($request->user()->role,['regional_veterinary_officer','mlf_officer'],true) && ((int)$investigation->started_by===(int)$request->user()->id || (int)$investigation->assigned_to===(int)$request->user()->id)) {
            throw ValidationException::withMessages(['approval'=>'The investigator or assigned officer cannot approve their own investigation report.']);
        }
        $data=$request->validate(['approval_comments'=>['required','string','max:3000']]);
        $previous=['status'=>$investigation->status,'approved_at'=>null,'approved_by'=>null];
        $investigation->update(['approved_by'=>$request->user()->id,'approved_at'=>now(),'status'=>'approved']);
        InvestigationHistory::create(['investigation_id'=>$investigation->id,'event_type'=>'approved','previous_values'=>$previous,'new_values'=>['status'=>'approved','approved_by'=>$request->user()->id,'approved_at'=>now()->toDateTimeString()],'notes'=>$data['approval_comments'],'changed_by'=>$request->user()->id,'changed_at'=>now()]);
        AuditLog::create(['user_id'=>$request->user()->id,'action'=>'investigation.approved','entity_type'=>'investigation','entity_id'=>(string)$investigation->id,'ip_address'=>$request->ip(),'description'=>'Approved investigation '.$investigation->investigation_code,'created_at'=>now()]);
        return redirect()->route('investigations.index')->with('status','Investigation report approved and locked.');
    }
}

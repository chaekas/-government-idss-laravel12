<?php

namespace App\Http\Controllers;

use App\Models\DiseaseCaseAttachment;
use App\Models\InvestigationAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SurveillanceAttachmentController extends Controller
{
    public function caseFile(Request $request,DiseaseCaseAttachment $attachment)
    {
        $case=$attachment->diseaseCase()->with('farm')->firstOrFail();
        $role=$request->user()->role;
        if($role==='farmer') abort_unless($case->farm?->farmers()->where('farmers.id',$request->user()->farmer_id)->exists(),403);
        else abort_unless(in_array($role,['field_officer','regional_veterinary_officer','mlf_officer','system_admin','super_admin','super-admin','administrator','admin'],true),403);
        abort_unless(Storage::disk('local')->exists($attachment->file_path),404);
        return Storage::disk('local')->download($attachment->file_path,$attachment->original_name,['Content-Type'=>$attachment->mime_type]);
    }

    public function investigationFile(Request $request,InvestigationAttachment $attachment)
    {
        $investigation=$attachment->investigation()->firstOrFail();
        $role=$request->user()->role;
        abort_unless(in_array($role,['field_officer','regional_veterinary_officer','mlf_officer','system_admin','super_admin','super-admin','administrator','admin'],true),403);
        if($role==='field_officer') abort_unless((int)$investigation->assigned_to===(int)$request->user()->id || (int)$investigation->started_by===(int)$request->user()->id,403);
        abort_unless(Storage::disk('local')->exists($attachment->file_path),404);
        return Storage::disk('local')->download($attachment->file_path,$attachment->original_name,['Content-Type'=>$attachment->mime_type]);
    }
}

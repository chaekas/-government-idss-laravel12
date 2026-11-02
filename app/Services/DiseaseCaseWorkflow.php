<?php

namespace App\Services;

use App\Models\DiseaseCase;
use App\Models\DiseaseCaseStatusHistory;

class DiseaseCaseWorkflow
{
    public static function transition(DiseaseCase $case,string $status,?int $userId,?string $reason=null): void
    {
        if($case->status===$status) return;
        $previous=$case->status;
        $case->update(['status'=>$status,'modified_by'=>$userId]);
        DiseaseCaseStatusHistory::create([
            'disease_case_id'=>$case->id,'from_status'=>$previous,'to_status'=>$status,
            'reason'=>$reason,'changed_by'=>$userId,'changed_at'=>now(),
        ]);
    }
}

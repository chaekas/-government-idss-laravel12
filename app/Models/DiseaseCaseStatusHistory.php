<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiseaseCaseStatusHistory extends Model
{
    protected $table = 'disease_case_status_history';
    protected $fillable = ['disease_case_id','from_status','to_status','reason','changed_by','changed_at'];
    protected function casts(): array { return ['changed_at' => 'datetime']; }
    public function diseaseCase(): BelongsTo { return $this->belongsTo(DiseaseCase::class); }
    public function changedBy(): BelongsTo { return $this->belongsTo(User::class, 'changed_by'); }
}

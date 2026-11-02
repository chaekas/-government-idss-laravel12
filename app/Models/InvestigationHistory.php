<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvestigationHistory extends Model
{
    protected $fillable = ['investigation_id','event_type','previous_values','new_values','notes','changed_by','changed_at'];
    protected function casts(): array { return ['previous_values' => 'array', 'new_values' => 'array', 'changed_at' => 'datetime']; }
    public function investigation(): BelongsTo { return $this->belongsTo(Investigation::class); }
    public function changedBy(): BelongsTo { return $this->belongsTo(User::class, 'changed_by'); }
}

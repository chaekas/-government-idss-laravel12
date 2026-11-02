<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LaboratoryTestResult extends Model
{
    protected $fillable = ['result_code', 'sample_id', 'laboratory_id', 'assigned_technician', 'reviewed_by', 'test_name', 'method', 'observations', 'qualitative_result', 'numeric_result', 'numeric_unit', 'status', 'technician_comments', 'review_comments', 'tested_at', 'approved_at'];

    protected function casts(): array { return ['numeric_result' => 'decimal:4', 'tested_at' => 'datetime', 'approved_at' => 'datetime']; }
    public function sample(): BelongsTo { return $this->belongsTo(Sample::class); }
    public function laboratory(): BelongsTo { return $this->belongsTo(Laboratory::class); }
}

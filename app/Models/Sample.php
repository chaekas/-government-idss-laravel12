<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sample extends Model
{
    protected $fillable = ['sample_code', 'barcode_value', 'disease_case_id', 'laboratory_id', 'collected_by', 'collection_date', 'received_at', 'sample_type', 'collection_notes', 'transport_status', 'quality_status', 'rejection_reason', 'test_status', 'result_summary', 'approved_at'];
    protected function casts(): array { return ['collection_date' => 'date', 'received_at' => 'datetime', 'approved_at' => 'datetime']; }
    public function diseaseCase(): BelongsTo { return $this->belongsTo(DiseaseCase::class); }
    public function laboratory(): BelongsTo { return $this->belongsTo(Laboratory::class); }
    public function movements(): HasMany { return $this->hasMany(SampleMovement::class)->latest('event_at'); }
    public function results(): HasMany { return $this->hasMany(LaboratoryTestResult::class); }
}

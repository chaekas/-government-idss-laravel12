<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sample extends Model
{
    protected $fillable = [
        'sample_code', 'barcode_value', 'disease_case_id', 'laboratory_id', 'collected_by',
        'collection_date', 'received_at', 'sample_type', 'sample_type_id', 'collection_notes', 'transport_status',
        'quality_status', 'rejection_reason', 'test_status', 'result_summary', 'approved_at',
        'laboratory_reference_number', 'laboratory_section', 'assigned_technician', 'assigned_by',
        'assigned_at', 'testing_started_at', 'testing_completed_at', 'released_at', 'packaged_at',
    ];

    protected function casts(): array
    {
        return [
            'collection_date' => 'date',
            'received_at' => 'datetime',
            'approved_at' => 'datetime',
            'assigned_at' => 'datetime',
            'testing_started_at' => 'datetime',
            'testing_completed_at' => 'datetime',
            'released_at' => 'datetime',
            'packaged_at' => 'datetime',
        ];
    }

    public function diseaseCase(): BelongsTo { return $this->belongsTo(DiseaseCase::class); }
    public function sampleType(): BelongsTo { return $this->belongsTo(SampleType::class); }
    public function laboratory(): BelongsTo { return $this->belongsTo(Laboratory::class); }
    public function collector(): BelongsTo { return $this->belongsTo(User::class, 'collected_by'); }
    public function assignedTechnician(): BelongsTo { return $this->belongsTo(User::class, 'assigned_technician'); }
    public function assignedBy(): BelongsTo { return $this->belongsTo(User::class, 'assigned_by'); }
    public function movements(): HasMany { return $this->hasMany(SampleMovement::class)->latest('event_at'); }
    public function results(): HasMany { return $this->hasMany(LaboratoryTestResult::class); }
    public function transportations(): HasMany { return $this->hasMany(SampleTransportation::class); }
    public function storageRecords(): HasMany { return $this->hasMany(SampleStorageRecord::class); }
    public function testAssignments(): HasMany { return $this->hasMany(LaboratoryTestAssignment::class); }
}

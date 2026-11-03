<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LaboratoryCorrectiveAction extends Model
{
    protected $fillable = [
        'action_code', 'laboratory_id', 'quality_control_record_id', 'laboratory_equipment_id',
        'equipment_calibration_id', 'assigned_to', 'created_by', 'priority', 'status',
        'description', 'due_date', 'resolution', 'resolved_at',
    ];

    protected function casts(): array
    {
        return ['due_date' => 'date', 'resolved_at' => 'datetime'];
    }

    public function laboratory(): BelongsTo { return $this->belongsTo(Laboratory::class); }
    public function qualityControlRecord(): BelongsTo { return $this->belongsTo(QualityControlRecord::class); }
    public function equipment(): BelongsTo { return $this->belongsTo(LaboratoryEquipment::class, 'laboratory_equipment_id'); }
    public function calibration(): BelongsTo { return $this->belongsTo(LaboratoryEquipmentCalibration::class, 'equipment_calibration_id'); }
    public function assignee(): BelongsTo { return $this->belongsTo(User::class, 'assigned_to'); }
}

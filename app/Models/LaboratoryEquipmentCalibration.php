<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LaboratoryEquipmentCalibration extends Model
{
    protected $fillable = [
        'laboratory_equipment_id', 'performed_by', 'calibration_date', 'due_date',
        'outcome', 'certificate_reference', 'findings', 'corrective_action',
    ];

    protected function casts(): array
    {
        return ['calibration_date' => 'date', 'due_date' => 'date'];
    }

    public function equipment(): BelongsTo { return $this->belongsTo(LaboratoryEquipment::class, 'laboratory_equipment_id'); }
    public function performedBy(): BelongsTo { return $this->belongsTo(User::class, 'performed_by'); }
}

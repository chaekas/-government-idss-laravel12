<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class LaboratoryEquipment extends Model {
 protected $fillable=['laboratory_id','equipment_code','name','manufacturer','model','serial_number','last_calibrated_at','calibration_due_at','status'];
 protected function casts(): array { return ['last_calibrated_at'=>'date','calibration_due_at'=>'date']; }
 public function laboratory(): BelongsTo { return $this->belongsTo(Laboratory::class); }
 public function calibrations(): \Illuminate\Database\Eloquent\Relations\HasMany { return $this->hasMany(LaboratoryEquipmentCalibration::class, 'laboratory_equipment_id')->latest('calibration_date'); }
 public function correctiveActions(): \Illuminate\Database\Eloquent\Relations\HasMany { return $this->hasMany(LaboratoryCorrectiveAction::class, 'laboratory_equipment_id'); }
}
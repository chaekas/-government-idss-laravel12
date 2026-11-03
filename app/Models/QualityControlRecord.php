<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class QualityControlRecord extends Model {
 protected $fillable=['laboratory_id','laboratory_test_id','sample_id','qc_type','outcome','findings','corrective_action','recorded_by','performed_at'];
 protected function casts(): array { return ['performed_at'=>'datetime']; }
 public function laboratory(): BelongsTo { return $this->belongsTo(Laboratory::class); }
 public function test(): BelongsTo { return $this->belongsTo(LaboratoryTest::class,'laboratory_test_id'); }
 public function sample(): BelongsTo { return $this->belongsTo(Sample::class); }
 public function correctiveActions(): \Illuminate\Database\Eloquent\Relations\HasMany { return $this->hasMany(LaboratoryCorrectiveAction::class); }
}
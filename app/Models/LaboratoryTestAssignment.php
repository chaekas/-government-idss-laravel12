<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class LaboratoryTestAssignment extends Model {
 protected $fillable=['sample_id','laboratory_test_id','laboratory_id','assigned_technician','assigned_by','status','assigned_at','completed_at'];
 protected function casts(): array { return ['assigned_at'=>'datetime','completed_at'=>'datetime']; }
 public function sample(): BelongsTo { return $this->belongsTo(Sample::class); }
 public function test(): BelongsTo { return $this->belongsTo(LaboratoryTest::class,'laboratory_test_id'); }
 public function laboratory(): BelongsTo { return $this->belongsTo(Laboratory::class); }
 public function results(): \Illuminate\Database\Eloquent\Relations\HasMany { return $this->hasMany(LaboratoryTestResult::class, 'laboratory_test_assignment_id'); }
 public function technician(): BelongsTo { return $this->belongsTo(User::class, 'assigned_technician'); }
}
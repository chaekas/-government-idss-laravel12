<?php
namespace App\\Models;
use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;
class DiseaseCase extends Model {
 protected $fillable=['case_code','farm_id','reported_by','disease_suspected','date_observed','symptoms','birds_affected','birds_dead','severity','status','investigation_notes'];
 protected function casts(): array { return ['date_observed'=>'date']; }
 public function farm(): BelongsTo { return $this->belongsTo(Farm::class); }
 public function reporter(): BelongsTo { return $this->belongsTo(User::class,'reported_by'); }
}

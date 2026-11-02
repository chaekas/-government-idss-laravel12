<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class AnimalMovement extends Model {
 protected $fillable=['animal_id','from_farm_id','to_farm_id','movement_reason','moved_at','recorded_by','notes'];
 protected function casts(): array { return ['moved_at'=>'datetime']; }
 public function animal(): BelongsTo { return $this->belongsTo(Animal::class); }
}
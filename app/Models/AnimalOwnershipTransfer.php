<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class AnimalOwnershipTransfer extends Model {
 protected $fillable=['animal_id','from_farmer_id','to_farmer_id','transferred_at','recorded_by','notes'];
 protected function casts(): array { return ['transferred_at'=>'date']; }
 public function animal(): BelongsTo { return $this->belongsTo(Animal::class); }
}
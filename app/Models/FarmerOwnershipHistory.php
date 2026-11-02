<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class FarmerOwnershipHistory extends Model {
 protected $fillable=['farmer_id','event_type','details','recorded_by','recorded_at'];
 protected function casts(): array { return ['recorded_at'=>'datetime']; }
 public function farmer(): BelongsTo { return $this->belongsTo(Farmer::class); }
}
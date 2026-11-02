<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class SampleTransportation extends Model {
 protected $fillable=['sample_id','consignment_reference','courier_name','vehicle_reference','dispatched_at','received_at','temperature_condition','notes'];
 protected function casts(): array { return ['dispatched_at'=>'datetime','received_at'=>'datetime']; }
 public function sample(): BelongsTo { return $this->belongsTo(Sample::class); }
}
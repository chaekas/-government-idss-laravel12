<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class SampleStorageRecord extends Model {
 protected $fillable=['sample_id','storage_location','storage_condition','stored_at','removed_at','recorded_by','notes'];
 protected function casts(): array { return ['stored_at'=>'datetime','removed_at'=>'datetime']; }
 public function sample(): BelongsTo { return $this->belongsTo(Sample::class); }
}
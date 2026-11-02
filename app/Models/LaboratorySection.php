<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class LaboratorySection extends Model {
 protected $fillable=['laboratory_id','code','name','is_active'];
 protected function casts(): array { return ['is_active'=>'boolean']; }
 public function laboratory(): BelongsTo { return $this->belongsTo(Laboratory::class); }
}
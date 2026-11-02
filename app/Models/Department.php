<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Department extends Model {
 use SoftDeletes;
 protected $fillable=['organization_id','code','name','is_active'];
 protected function casts(): array { return ['is_active'=>'boolean']; }
 public function organization(): BelongsTo { return $this->belongsTo(Organization::class); }
}
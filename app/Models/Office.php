<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Office extends Model {
 use SoftDeletes;
 protected $fillable=['organization_id','department_id','administrative_area_id','code','name','office_type','is_active'];
 protected function casts(): array { return ['is_active'=>'boolean']; }
 public function organization(): BelongsTo { return $this->belongsTo(Organization::class); }
 public function department(): BelongsTo { return $this->belongsTo(Department::class); }
 public function administrativeArea(): BelongsTo { return $this->belongsTo(AdministrativeArea::class); }
}
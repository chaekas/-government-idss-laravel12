<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class FarmerContact extends Model {
 protected $fillable=['farmer_id','contact_type','contact_value','is_primary','is_verified'];
 protected function casts(): array { return ['is_primary'=>'boolean','is_verified'=>'boolean']; }
 public function farmer(): BelongsTo { return $this->belongsTo(Farmer::class); }
}
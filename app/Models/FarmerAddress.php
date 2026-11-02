<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class FarmerAddress extends Model {
 protected $fillable=['farmer_id','administrative_area_id','address_type','address_line','region','district','ward','village','is_primary'];
 protected function casts(): array { return ['is_primary'=>'boolean']; }
 public function farmer(): BelongsTo { return $this->belongsTo(Farmer::class); }
 public function administrativeArea(): BelongsTo { return $this->belongsTo(AdministrativeArea::class); }
}
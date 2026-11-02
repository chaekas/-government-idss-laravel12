<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class FarmLocation extends Model {
 protected $fillable=['farm_id','administrative_area_id','location_type','address_line','region','district','ward','village','latitude','longitude','is_primary'];
 protected function casts(): array { return ['latitude'=>'decimal:7','longitude'=>'decimal:7','is_primary'=>'boolean']; }
 public function farm(): BelongsTo { return $this->belongsTo(Farm::class); }
 public function administrativeArea(): BelongsTo { return $this->belongsTo(AdministrativeArea::class); }
}
<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Farm extends Model {
 protected $fillable=['farm_code','farmer_id','name','region','district','ward','village','latitude','longitude','poultry_type','bird_count','notes'];
 public function farmer(): BelongsTo { return $this->belongsTo(Farmer::class); }
}

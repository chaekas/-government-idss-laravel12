<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class VaccinationCampaign extends Model {
 protected $fillable=['campaign_code','name','administrative_area_id','start_date','end_date','status','description'];
 protected function casts(): array { return ['start_date'=>'date','end_date'=>'date']; }
 public function administrativeArea(): BelongsTo { return $this->belongsTo(AdministrativeArea::class); }
 public function vaccinations(): HasMany { return $this->hasMany(Vaccination::class); }
}
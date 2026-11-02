<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Vaccination extends Model {
 protected $fillable=['animal_id','vaccination_campaign_id','vaccine_name','batch_number','vaccinated_at','vaccinator_id','dose','notes'];
 protected function casts(): array { return ['vaccinated_at'=>'date']; }
 public function animal(): BelongsTo { return $this->belongsTo(Animal::class); }
}
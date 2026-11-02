<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class AnimalDiseaseRecord extends Model {
 protected $fillable=['animal_id','disease_category_id','disease_name','observed_at','health_status','treatment','notes','recorded_by'];
 protected function casts(): array { return ['observed_at'=>'date']; }
 public function animal(): BelongsTo { return $this->belongsTo(Animal::class); }
 public function diseaseCategory(): BelongsTo { return $this->belongsTo(DiseaseCategory::class); }
}
<?php
namespace App\\Models;
use Illuminate\\Database\\Eloquent\\Model;
class DiseaseCase extends Model {
 protected $fillable=['case_code','farm_id','reported_by','disease_suspected','date_observed','symptoms','birds_affected','birds_dead','severity','status','investigation_notes'];
 protected function casts(): array { return ['date_observed'=>'date']; }
}

<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class AnimalIdentification extends Model {
 protected $fillable=['animal_id','identification_type','identifier_value','status','issued_at','retired_at','replaced_by_id'];
 protected function casts(): array { return ['issued_at'=>'date','retired_at'=>'date']; }
 public function animal(): BelongsTo { return $this->belongsTo(Animal::class); }
}
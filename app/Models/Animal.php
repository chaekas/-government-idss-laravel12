<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Animal extends Model
{
    protected $fillable = [
        'animal_code', 'farm_id', 'farmer_id', 'species_id', 'breed_id', 'species', 'breed',
        'sex', 'date_of_birth', 'identification_method',
        'external_identifier', 'ownership_status', 'notes', 'archived_at',
    ];

    protected function casts(): array
    {
        return ['date_of_birth' => 'date', 'date_of_death' => 'date', 'archived_at' => 'datetime'];
    }

    public function farm(): BelongsTo { return $this->belongsTo(Farm::class); }
    public function farmer(): BelongsTo { return $this->belongsTo(Farmer::class); }
    public function speciesRecord(): BelongsTo { return $this->belongsTo(Species::class, 'species_id'); }
    public function breedRecord(): BelongsTo { return $this->belongsTo(Breed::class, 'breed_id'); }
    public function identifications(): HasMany { return $this->hasMany(AnimalIdentification::class); }
    public function movements(): HasMany { return $this->hasMany(AnimalMovement::class); }
    public function ownershipTransfers(): HasMany { return $this->hasMany(AnimalOwnershipTransfer::class); }
    public function vaccinations(): HasMany { return $this->hasMany(Vaccination::class); }
    public function diseaseHistory(): HasMany { return $this->hasMany(AnimalDiseaseRecord::class); }
}

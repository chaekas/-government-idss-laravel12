<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Farm extends Model
{
    protected $fillable = [
        'farm_code', 'farmer_id', 'name', 'region', 'district', 'ward', 'village',
        'latitude', 'longitude', 'poultry_type', 'bird_count', 'notes', 'farm_type_id',
        'farm_size', 'farm_size_unit', 'administrative_area_id', 'status', 'archived_at',
    ];

    protected function casts(): array
    {
        return ['latitude' => 'decimal:7', 'longitude' => 'decimal:7', 'farm_size' => 'decimal:3', 'archived_at' => 'datetime'];
    }

    public function farmer(): BelongsTo { return $this->belongsTo(Farmer::class); }
    public function farmers(): BelongsToMany
    {
        return $this->belongsToMany(Farmer::class, 'farmer_farm')->withPivot(['ownership_type', 'ownership_share', 'effective_from', 'effective_to', 'is_primary'])->withTimestamps();
    }
    public function farmType(): BelongsTo { return $this->belongsTo(FarmType::class); }
    public function administrativeArea(): BelongsTo { return $this->belongsTo(AdministrativeArea::class); }
    public function animals(): HasMany { return $this->hasMany(Animal::class); }
    public function locations(): HasMany { return $this->hasMany(FarmLocation::class); }
}

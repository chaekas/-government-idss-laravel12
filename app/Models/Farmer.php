<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Farmer extends Model
{
    protected $fillable = [
        'farmer_code', 'full_name', 'phone', 'email', 'national_id', 'region', 'district',
        'ward', 'village', 'address', 'sex', 'date_of_birth', 'nationality', 'farmer_type',
        'status', 'archived_at',
    ];

    protected function casts(): array
    {
        return ['date_of_birth' => 'date', 'archived_at' => 'datetime'];
    }

    public function farms(): BelongsToMany
    {
        return $this->belongsToMany(Farm::class)->withPivot(['ownership_type', 'ownership_share', 'effective_from', 'effective_to', 'is_primary'])->withTimestamps();
    }

    public function contacts(): HasMany { return $this->hasMany(FarmerContact::class); }
    public function addresses(): HasMany { return $this->hasMany(FarmerAddress::class); }
    public function ownershipHistory(): HasMany { return $this->hasMany(FarmerOwnershipHistory::class); }
}

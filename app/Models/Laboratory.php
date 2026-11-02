<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Laboratory extends Model
{
    protected $fillable = ['code', 'name', 'level', 'region', 'district', 'address', 'contact_email', 'contact_phone', 'is_active'];
    protected function casts(): array { return ['is_active' => 'boolean']; }
    public function samples(): HasMany { return $this->hasMany(Sample::class); }
    public function testResults(): HasMany { return $this->hasMany(LaboratoryTestResult::class); }
    public function sections(): HasMany { return $this->hasMany(LaboratorySection::class); }
    public function equipment(): HasMany { return $this->hasMany(LaboratoryEquipment::class); }
    public function qualityControlRecords(): HasMany { return $this->hasMany(QualityControlRecord::class); }
    public function technicians(): BelongsToMany { return $this->belongsToMany(User::class)->withPivot(['assignment_role', 'effective_from', 'effective_to'])->withTimestamps(); }
}

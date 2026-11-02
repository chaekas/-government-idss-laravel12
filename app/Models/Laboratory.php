<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Laboratory extends Model
{
    protected $fillable = ['code', 'name', 'level', 'region', 'district', 'address', 'contact_email', 'contact_phone', 'is_active'];
    protected function casts(): array { return ['is_active' => 'boolean']; }
    public function samples(): HasMany { return $this->hasMany(Sample::class); }
    public function testResults(): HasMany { return $this->hasMany(LaboratoryTestResult::class); }
}

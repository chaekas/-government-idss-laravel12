<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TestMethod extends Model
{
    protected $fillable = ['code', 'name', 'standard_reference', 'is_active'];
    protected function casts(): array { return ['is_active' => 'boolean']; }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TestMethod extends Model
{
    use SoftDeletes;
    protected $fillable = ['code', 'name', 'standard_reference', 'is_active'];
    protected function casts(): array { return ['is_active' => 'boolean']; }
}

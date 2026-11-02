<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiseaseCategory extends Model
{
    protected $fillable = ['code', 'name', 'description', 'is_active'];
    protected function casts(): array { return ['is_active' => 'boolean']; }
}

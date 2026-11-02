<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LaboratoryTest extends Model
{
    use SoftDeletes;
    protected $fillable = ['code', 'name', 'disease_category_id', 'specimen_requirements', 'is_active', 'description'];
    protected function casts(): array { return ['is_active' => 'boolean']; }
    public function diseaseCategory(): BelongsTo { return $this->belongsTo(DiseaseCategory::class); }
}

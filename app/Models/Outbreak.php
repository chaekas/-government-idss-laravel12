<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;\nuse Illuminate\Database\Eloquent\Relations\BelongsTo;

class Outbreak extends Model
{
    protected $fillable = ['outbreak_code', 'disease_name', 'region', 'district', 'latitude', 'longitude', 'start_date', 'cases_count', 'deaths_count', 'severity', 'status', 'response_actions', 'reported_by', 'disease_category_id', 'administrative_area_id', 'confirmed_at', 'closed_at', 'modified_by'];

    protected function casts(): array { return ['start_date' => 'date', 'confirmed_at' => 'datetime', 'closed_at' => 'datetime', 'latitude' => 'decimal:7', 'longitude' => 'decimal:7']; }

    public function diseaseCategory(): BelongsTo { return $this->belongsTo(DiseaseCategory::class); }\n    public function administrativeArea(): BelongsTo { return $this->belongsTo(AdministrativeArea::class); }\n    public function reporter(): BelongsTo { return $this->belongsTo(User::class, 'reported_by'); }\n    public function diseaseCases(): BelongsToMany { return $this->belongsToMany(DiseaseCase::class, 'outbreak_case', 'outbreak_id', 'disease_case_id')->withTimestamps(); }
}

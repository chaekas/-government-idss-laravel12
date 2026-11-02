<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Outbreak extends Model
{
    protected $fillable = ['outbreak_code', 'disease_name', 'region', 'district', 'latitude', 'longitude', 'start_date', 'cases_count', 'deaths_count', 'severity', 'status', 'response_actions', 'reported_by'];

    protected function casts(): array { return ['start_date' => 'date', 'latitude' => 'decimal:7', 'longitude' => 'decimal:7']; }

    public function diseaseCases(): BelongsToMany { return $this->belongsToMany(DiseaseCase::class, 'outbreak_case', 'outbreak_id', 'disease_case_id')->withTimestamps(); }
}

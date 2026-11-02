<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Investigation extends Model
{
    protected $fillable = ['investigation_code', 'disease_case_id', 'assigned_to', 'started_by', 'investigated_at', 'status', 'field_observations', 'epidemiological_findings', 'recommended_actions', 'latitude', 'longitude'];

    protected function casts(): array
    {
        return ['investigated_at' => 'datetime', 'latitude' => 'decimal:7', 'longitude' => 'decimal:7'];
    }

    public function diseaseCase(): BelongsTo { return $this->belongsTo(DiseaseCase::class); }
    public function assignee(): BelongsTo { return $this->belongsTo(User::class, 'assigned_to'); }
}

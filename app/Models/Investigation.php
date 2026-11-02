<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;\nuse Illuminate\Database\Eloquent\Relations\HasMany;

class Investigation extends Model
{
    protected $fillable = ['investigation_code', 'disease_case_id', 'assigned_to', 'started_by', 'investigated_at', 'status', 'field_observations', 'epidemiological_findings', 'recommended_actions', 'latitude', 'longitude', 'possible_source', 'transmission_route', 'biosecurity_observations', 'immediate_control_measures', 'approved_by', 'approved_at', 'closed_at'];

    protected function casts(): array
    {
        return ['investigated_at' => 'datetime', 'approved_at' => 'datetime', 'closed_at' => 'datetime', 'latitude' => 'decimal:7', 'longitude' => 'decimal:7'];
    }

    public function diseaseCase(): BelongsTo { return $this->belongsTo(DiseaseCase::class); }
    public function assignee(): BelongsTo { return $this->belongsTo(User::class, 'assigned_to'); }\n    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }\n    public function history(): HasMany { return $this->hasMany(InvestigationHistory::class)->orderBy('changed_at'); }\n    public function attachments(): HasMany { return $this->hasMany(InvestigationAttachment::class); }
}

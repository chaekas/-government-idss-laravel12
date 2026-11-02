<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;\nuse Illuminate\Database\Eloquent\Relations\HasMany;

class DiseaseCase extends Model
{
    protected $fillable = ['case_code', 'farm_id', 'animal_id', 'disease_category_id', 'administrative_area_id', 'reported_by', 'disease_suspected', 'date_observed', 'symptoms', 'birds_affected', 'birds_dead', 'severity', 'status', 'investigation_notes'];

    protected function casts(): array { return ['date_observed' => 'date', 'reported_at' => 'datetime', 'latitude' => 'decimal:7', 'longitude' => 'decimal:7']; }
    public function farm(): BelongsTo { return $this->belongsTo(Farm::class); }
    public function reporter(): BelongsTo { return $this->belongsTo(User::class, 'reported_by'); }
    public function investigation(): HasOne { return $this->hasOne(Investigation::class); }
    public function samples(): HasMany { return $this->hasMany(Sample::class); }
    public function animal(): BelongsTo { return $this->belongsTo(Animal::class); }
    public function diseaseCategory(): BelongsTo { return $this->belongsTo(DiseaseCategory::class); }
    public function symptoms(): BelongsToMany { return $this->belongsToMany(DiseaseSymptom::class, 'disease_case_symptom', 'disease_case_id', 'disease_symptom_id')->withTimestamps(); }\n    public function statusHistory(): HasMany { return $this->hasMany(DiseaseCaseStatusHistory::class)->orderBy('changed_at'); }\n    public function attachments(): HasMany { return $this->hasMany(DiseaseCaseAttachment::class); }
}

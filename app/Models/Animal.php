<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Animal extends Model
{
    protected $fillable = ['animal_code', 'farm_id', 'species', 'breed', 'sex', 'date_of_birth', 'identification_method', 'external_identifier', 'ownership_status', 'notes'];

    protected function casts(): array
    {
        return ['date_of_birth' => 'date'];
    }

    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }
}

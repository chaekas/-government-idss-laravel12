<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Breed extends Model
{
    use SoftDeletes;
    protected $fillable = ['species_id', 'code', 'name', 'description', 'is_active'];
    protected function casts(): array { return ['is_active' => 'boolean']; }
    public function species(): BelongsTo { return $this->belongsTo(Species::class); }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SampleMovement extends Model
{
    protected $fillable = ['sample_id', 'event_type', 'from_location', 'to_location', 'condition_on_transfer', 'received_by', 'notes', 'recorded_by', 'event_at'];

    protected function casts(): array { return ['event_at' => 'datetime']; }
    public function sample(): BelongsTo { return $this->belongsTo(Sample::class); }
    public function recordedBy(): BelongsTo { return $this->belongsTo(User::class, 'recorded_by'); }
}

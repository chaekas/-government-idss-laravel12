<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduledReport extends Model
{
    protected $fillable = [
        'name', 'report_type', 'frequency', 'filters', 'recipient_user_ids',
        'next_run_at', 'last_run_at', 'is_active', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'filters' => 'array',
            'recipient_user_ids' => 'array',
            'next_run_at' => 'datetime',
            'last_run_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}

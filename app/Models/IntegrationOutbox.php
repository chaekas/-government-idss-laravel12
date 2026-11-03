<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IntegrationOutbox extends Model
{
    protected $table = 'integration_outbox';
    protected $fillable = [
        'provider','channel','event_type','recipient','subject','payload','status',
        'attempts','max_attempts','available_at','sent_at','external_reference','last_error',
    ];
    protected function casts(): array
    {
        return ['payload' => 'array', 'available_at' => 'datetime', 'sent_at' => 'datetime'];
    }
}

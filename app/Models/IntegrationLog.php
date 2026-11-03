<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IntegrationLog extends Model
{
    protected $fillable = [
        'provider','operation','status','external_reference','request_metadata',
        'response_metadata','error_message','occurred_at',
    ];
    protected function casts(): array
    {
        return ['request_metadata' => 'array', 'response_metadata' => 'array', 'occurred_at' => 'datetime'];
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutbreakStatusHistory extends Model
{
    protected $table = 'outbreak_status_history';
    protected $fillable = ['outbreak_id','from_status','to_status','reason','changed_by','changed_at'];
    protected function casts(): array { return ['changed_at' => 'datetime']; }
    public function outbreak(): BelongsTo { return $this->belongsTo(Outbreak::class); }
    public function changedBy(): BelongsTo { return $this->belongsTo(User::class, 'changed_by'); }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvestigationAttachment extends Model
{
    protected $fillable = ['investigation_id','uploaded_by','original_name','file_path','mime_type','file_size','uploaded_at'];
    protected function casts(): array { return ['uploaded_at' => 'datetime']; }
    public function investigation(): BelongsTo { return $this->belongsTo(Investigation::class); }
    public function uploader(): BelongsTo { return $this->belongsTo(User::class, 'uploaded_by'); }
}

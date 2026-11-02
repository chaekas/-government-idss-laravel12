<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiseaseCaseAttachment extends Model
{
    protected $fillable = ['disease_case_id','uploaded_by','original_name','file_path','mime_type','file_size','uploaded_at'];
    protected function casts(): array { return ['uploaded_at' => 'datetime']; }
    public function diseaseCase(): BelongsTo { return $this->belongsTo(DiseaseCase::class); }
    public function uploader(): BelongsTo { return $this->belongsTo(User::class, 'uploaded_by'); }
}

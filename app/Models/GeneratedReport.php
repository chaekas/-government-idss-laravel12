<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class GeneratedReport extends Model {
 protected $fillable=['report_definition_id','generated_by','file_path','file_format','parameters','generated_at'];
 protected function casts(): array { return ['parameters'=>'array','generated_at'=>'datetime']; }
 public function definition(): BelongsTo { return $this->belongsTo(ReportDefinition::class,'report_definition_id'); }
}
<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ReportDefinition extends Model {
 protected $fillable=['code','name','report_type','configuration','is_active'];
 protected function casts(): array { return ['configuration'=>'array','is_active'=>'boolean']; }
}
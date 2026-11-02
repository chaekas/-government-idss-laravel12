<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class DashboardDefinition extends Model {
 protected $fillable=['code','name','audience_role','configuration','is_active'];
 protected function casts(): array { return ['configuration'=>'array','is_active'=>'boolean']; }
}
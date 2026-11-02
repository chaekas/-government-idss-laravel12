<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SurveillanceAlertRule extends Model
{
    protected $fillable = ['code','name','severity','case_threshold','within_hours','notify_field_officers','notify_regional_officers','notify_ministry_officers','is_active','updated_by'];
    protected function casts(): array { return ['notify_field_officers' => 'boolean','notify_regional_officers' => 'boolean','notify_ministry_officers' => 'boolean','is_active' => 'boolean']; }
}

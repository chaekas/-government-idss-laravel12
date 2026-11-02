<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'role', 'is_active', 'farmer_id', 'organization_id', 'department_id', 'office_id', 'mobile_number', 'archived_at'];
    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'archived_at' => 'datetime',
        ];
    }

    public function farmer(): BelongsTo { return $this->belongsTo(Farmer::class); }
    public function organization(): BelongsTo { return $this->belongsTo(Organization::class); }
    public function department(): BelongsTo { return $this->belongsTo(Department::class); }
    public function office(): BelongsTo { return $this->belongsTo(Office::class); }
    public function roles(): BelongsToMany { return $this->belongsToMany(Role::class)->withTimestamps(); }
    public function notifications(): HasMany { return $this->hasMany(UserNotification::class); }
}

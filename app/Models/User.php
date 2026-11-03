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
    public function administrativeAreas(): BelongsToMany { return $this->belongsToMany(AdministrativeArea::class, 'user_administrative_area')->withPivot(['effective_from', 'effective_to'])->wherePivotNull('effective_to')->withTimestamps(); }
    public function laboratories(): BelongsToMany { return $this->belongsToMany(Laboratory::class)->withPivot(['assignment_role', 'effective_from', 'effective_to'])->wherePivotNull('effective_to')->withTimestamps(); }

    public function hasRole(string|array $roles): bool
    {
        $roles = (array) $roles;
        if (in_array($this->role, $roles, true)) return true;
        return $this->roles()->whereIn('name', $roles)->exists();
    }

    public function accessibleAdministrativeAreaIds(): array
    {
        $pending = $this->administrativeAreas()
            ->wherePivotNull('effective_to')
            ->where('administrative_areas.is_active', true)
            ->pluck('administrative_areas.id')
            ->all();

        if (!$pending) return [];

        $children = AdministrativeArea::where('is_active', true)->get(['id', 'parent_id'])->groupBy('parent_id');
        $seen = [];
        while ($pending) {
            $id = (int) array_pop($pending);
            if (isset($seen[$id])) continue;
            $seen[$id] = true;
            foreach ($children->get($id, collect()) as $child) {
                $pending[] = (int) $child->id;
            }
        }

        return array_map('intval', array_keys($seen));
    }

    public function activeLaboratoryIds(): array
    {
        return $this->laboratories()
            ->wherePivotNull('effective_to')
            ->where('laboratories.is_active', true)
            ->pluck('laboratories.id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }
    public function notifications(): HasMany { return $this->hasMany(UserNotification::class); }
}

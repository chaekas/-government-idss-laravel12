<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class UserDataScope
{
    private const GLOBAL_ROLES = ['system_admin','super_admin','super-admin','administrator','admin','mlf_officer'];
    private const AREA_ROLES = ['field_officer','regional_veterinary_officer'];
    private const LAB_ROLES = ['laboratory_technician','laboratory_manager','zonal_laboratory_administrator'];

    private function global(User $user): bool
    {
        return $user->hasRole(self::GLOBAL_ROLES);
    }

    private function deny(Builder $query): Builder
    {
        return $query->whereRaw('1 = 0');
    }

    private function applyCaseArea(Builder $query, User $user): Builder
    {
        $ids = $user->accessibleAdministrativeAreaIds();
        if (!$ids) return $this->deny($query);

        return $query->where(function (Builder $cases) use ($ids) {
            $cases->whereIn('administrative_area_id', $ids)
                ->orWhereHas('farm', fn (Builder $farms) => $farms->whereIn('administrative_area_id', $ids));
        });
    }

    public function cases(Builder $query, User $user): Builder
    {
        if ($this->global($user)) return $query;

        if ($user->hasRole('farmer')) {
            return $query->whereHas('farm', fn (Builder $farms) => $farms->where('farmer_id', $user->farmer_id)->orWhereHas('farmers', fn (Builder $farmers) => $farmers->where('farmers.id', $user->farmer_id)->whereNull('farmer_farm.effective_to')));
        }

        if ($user->hasRole(self::AREA_ROLES)) return $this->applyCaseArea($query, $user);

        if ($user->hasRole(self::LAB_ROLES)) {
            if ($user->hasRole('laboratory_technician')) {
                return $query->whereHas('samples', fn (Builder $samples) => $samples->where('assigned_technician', $user->id));
            }
            $labIds = $user->activeLaboratoryIds();
            return $labIds ? $query->whereHas('samples', fn (Builder $samples) => $samples->whereIn('laboratory_id', $labIds)) : $this->deny($query);
        }

        return $this->deny($query);
    }

    public function farms(Builder $query, User $user): Builder
    {
        if ($this->global($user)) return $query;
        if ($user->hasRole('farmer')) {
            return $query->where(fn (Builder $farms) => $farms->where('farmer_id', $user->farmer_id)->orWhereHas('farmers', fn (Builder $farmers) => $farmers->where('farmers.id', $user->farmer_id)->whereNull('farmer_farm.effective_to')));
        }
        if ($user->hasRole(self::AREA_ROLES)) {
            $ids = $user->accessibleAdministrativeAreaIds();
            return $ids ? $query->whereIn('administrative_area_id', $ids) : $this->deny($query);
        }
        return $this->deny($query);
    }

    public function investigations(Builder $query, User $user): Builder
    {
        if ($this->global($user)) return $query;
        if ($user->hasRole('farmer')) {
            return $query->whereHas('diseaseCase.farm.farmers', fn (Builder $farmers) => $farmers->where('farmers.id', $user->farmer_id)->whereNull('farmer_farm.effective_to'));
        }
        if ($user->hasRole(self::AREA_ROLES)) {
            $ids = $user->accessibleAdministrativeAreaIds();
            if (!$ids) return $query->where('assigned_to', $user->id);
            return $query->where(function (Builder $items) use ($user, $ids) {
                $items->where('assigned_to', $user->id)
                    ->orWhereHas('diseaseCase', function (Builder $cases) use ($ids) {
                        $cases->whereIn('administrative_area_id', $ids)
                            ->orWhereHas('farm', fn (Builder $farms) => $farms->whereIn('administrative_area_id', $ids));
                    });
            });
        }
        return $this->deny($query);
    }

    public function samples(Builder $query, User $user): Builder
    {
        if ($this->global($user)) return $query;
        if ($user->hasRole('farmer')) {
            return $query->whereHas('diseaseCase.farm.farmers', fn (Builder $farmers) => $farmers->where('farmers.id', $user->farmer_id)->whereNull('farmer_farm.effective_to'));
        }
        if ($user->hasRole('field_officer')) {
            $ids = $user->accessibleAdministrativeAreaIds();
            return $ids
                ? $query->whereHas('diseaseCase', fn (Builder $cases) => $cases->whereIn('administrative_area_id', $ids)->orWhereHas('farm', fn (Builder $farms) => $farms->whereIn('administrative_area_id', $ids)))
                : $this->deny($query);
        }
        if ($user->hasRole('regional_veterinary_officer')) {
            $ids = $user->accessibleAdministrativeAreaIds();
            return $ids
                ? $query->whereHas('diseaseCase', fn (Builder $cases) => $cases->whereIn('administrative_area_id', $ids)->orWhereHas('farm', fn (Builder $farms) => $farms->whereIn('administrative_area_id', $ids)))
                : $this->deny($query);
        }
        if ($user->hasRole('laboratory_technician')) {
            $labIds = $user->activeLaboratoryIds();
            return $query->where(function (Builder $samples) use ($user, $labIds) {
                $samples->where('assigned_technician', $user->id);
                if ($labIds) {
                    $samples->orWhere(function (Builder $incoming) use ($labIds) {
                        $incoming->whereIn('laboratory_id', $labIds)
                            ->where(function (Builder $status) {
                                $status->where('transport_status', 'in_transit')
                                    ->orWhere(fn (Builder $received) => $received->where('transport_status', 'received')->where('quality_status', 'pending'));
                            });
                    });
                }
            });
        }
        if ($user->hasRole(['laboratory_manager','zonal_laboratory_administrator'])) {
            $labIds = $user->activeLaboratoryIds();
            return $labIds ? $query->whereIn('laboratory_id', $labIds) : $this->deny($query);
        }
        return $this->deny($query);
    }

    public function outbreaks(Builder $query, User $user): Builder
    {
        if ($this->global($user)) return $query;
        if ($user->hasRole(self::AREA_ROLES)) {
            $ids = $user->accessibleAdministrativeAreaIds();
            return $ids ? $query->whereIn('administrative_area_id', $ids) : $this->deny($query);
        }
        return $this->deny($query);
    }

    public function laboratoryResults(Builder $query, User $user): Builder
    {
        if ($this->global($user)) return $query;
        if ($user->hasRole('laboratory_technician')) return $query->where('assigned_technician', $user->id);
        if ($user->hasRole(['laboratory_manager','zonal_laboratory_administrator'])) {
            $labIds = $user->activeLaboratoryIds();
            return $labIds ? $query->whereIn('laboratory_id', $labIds) : $this->deny($query);
        }
        if ($user->hasRole(self::AREA_ROLES)) {
            $ids = $user->accessibleAdministrativeAreaIds();
            return $ids ? $query->whereHas('sample.diseaseCase', fn (Builder $cases) => $cases->whereIn('administrative_area_id', $ids)->orWhereHas('farm', fn (Builder $farms) => $farms->whereIn('administrative_area_id', $ids))) : $this->deny($query);
        }
        return $this->deny($query);
    }
}

<?php

namespace App\Services\PelakuUmkm;

use App\Models\Umkm\Umkm;
use App\Models\Umkm\UmkmUserLink;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class PelakuWorkspaceAccessService
{
    public function canAccess(User $user): bool
    {
        if (! $user->isActive()) {
            return false;
        }

        if (! $user->hasRole('pelaku_umkm')) {
            return false;
        }

        if (! $user->hasPermission('umkm.workspace.access')) {
            return false;
        }

        return $this->bindingQuery($user)->exists();
    }

    public function bindingQuery(User $user): Builder
    {
        return UmkmUserLink::query()
            ->activeVerified()
            ->where('user_id', $user->id);
    }

    public function ownedUmkmQuery(User $user): Builder
    {
        return Umkm::query()
            ->whereHas('userLinks', function (Builder $query) use ($user): void {
                $query->where('user_id', $user->id)
                    ->activeVerified();
            });
    }

    public function owns(User $user, Umkm $umkm): bool
    {
        return $this->bindingQuery($user)
            ->where('umkm_id', $umkm->id)
            ->exists();
    }
}
<?php

namespace App\Policies\Umkm;

use App\Models\Umkm\Umkm;
use App\Models\Umkm\UmkmUserLink;
use App\Models\User;
use App\Policies\Concerns\SensitiveFieldGuard;

class UmkmPolicy
{
    use SensitiveFieldGuard;

    public function viewAny(User $user): bool
    {
        if ($user->hasPermission('umkm.read.official')) {
            return true;
        }

        return $this->hasVerifiedWorkspaceBinding($user);
    }

    public function view(User $user, Umkm $umkm): bool
    {
        if ($user->hasPermission('umkm.read.official')) {
            return true;
        }

        if (! $this->hasVerifiedWorkspaceBinding($user)) {
            return false;
        }

        return UmkmUserLink::query()
            ->activeVerified()
            ->where('user_id', $user->id)
            ->where('umkm_id', $umkm->id)
            ->exists();
    }

    public function update(User $user, Umkm $umkm): bool
    {
        return $user->hasPermission('umkm.write.official');
    }

    private function hasVerifiedWorkspaceBinding(User $user): bool
    {
        if (! $user->isActive()
            || ! $user->hasRole('pelaku_umkm')
            || ! $user->hasPermission('umkm.workspace.access')) {
            return false;
        }

        return UmkmUserLink::query()
            ->activeVerified()
            ->where('user_id', $user->id)
            ->exists();
    }
}
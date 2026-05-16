<?php
namespace App\Policies;
use App\Models\Umkm;use App\Models\User;use App\Policies\Concerns\SensitiveFieldGuard;
class UmkmPolicy { use SensitiveFieldGuard; public function viewAny(User $user): bool { return $user->hasPermission('umkm.read.official'); } public function view(User $user, Umkm $umkm): bool { if($user->hasPermission('umkm.read.official')) return true; return $user->hasRole('pelaku_umkm') && $umkm->userLinks()->where('user_id',$user->id)->exists(); } public function update(User $user, Umkm $umkm): bool { return $user->hasPermission('umkm.write.official') || ($user->hasRole('pelaku_umkm') && $umkm->userLinks()->where('user_id',$user->id)->exists()); }}

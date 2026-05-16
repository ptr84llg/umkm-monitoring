<?php
namespace App\Policies;
use App\Models\UmkmLocation;use App\Models\User;
class UmkmLocationPolicy { public function view(User $u, UmkmLocation $l): bool { return $u->hasPermission('umkm.read.official'); } public function update(User $u): bool { return $u->hasPermission('umkm.write.official'); }}

<?php
namespace App\Policies;
use App\Models\UmkmLegality;use App\Models\User;
class UmkmLegalityPolicy { public function view(User $u, UmkmLegality $l): bool { return $u->hasPermission('umkm.read.official'); } public function update(User $u): bool { return $u->hasPermission('umkm.write.official'); }}

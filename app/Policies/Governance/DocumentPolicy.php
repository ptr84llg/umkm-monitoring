<?php
namespace App\Policies\Governance;
use App\Models\User;
class DocumentPolicy { public function view(User $u): bool { return $u->hasPermission('umkm.sensitive.document'); }}


<?php
namespace App\Policies\Governance;
use App\Models\User;
class AuditPolicy { public function view(User $u): bool { return $u->hasPermission('audit.read'); }}


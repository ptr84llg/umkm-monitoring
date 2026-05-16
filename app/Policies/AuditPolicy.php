<?php
namespace App\Policies;
use App\Models\User;
class AuditPolicy { public function view(User $u): bool { return $u->hasPermission('audit.read'); }}


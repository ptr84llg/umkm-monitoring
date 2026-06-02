<?php
namespace App\Policies\Governance;
use App\Models\User;
class ExportPolicy { public function export(User $u): bool { return $u->hasPermission('export.sensitive'); }}


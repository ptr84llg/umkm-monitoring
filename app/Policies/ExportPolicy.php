<?php
namespace App\Policies;
use App\Models\User;
class ExportPolicy { public function export(User $u): bool { return $u->hasPermission('export.sensitive'); }}


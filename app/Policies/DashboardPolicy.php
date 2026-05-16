<?php
namespace App\Policies;
use App\Models\User;
class DashboardPolicy { public function viewExecutive(User $u): bool { return $u->hasPermission('dashboard.view.executive'); }}


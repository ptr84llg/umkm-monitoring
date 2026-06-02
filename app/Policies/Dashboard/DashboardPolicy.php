<?php
namespace App\Policies\Dashboard;
use App\Models\User;
class DashboardPolicy { public function viewExecutive(User $u): bool { return $u->hasPermission('dashboard.view.executive'); }}


<?php

namespace Tests\Feature;

use App\Models\Access\Permission;
use App\Models\Access\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AdminDinasRouteActivationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dinas_dashboard_route_is_registered_with_required_guards(): void
    {
        $this->assertTrue(Route::has('admin-dinas.dashboard'));

        $route = Route::getRoutes()->getByName('admin-dinas.dashboard');

        $this->assertNotNull($route);
        $this->assertSame('admin-dinas/dashboard', $route->uri());

        $middleware = $route->gatherMiddleware();

        $this->assertContains('auth', $middleware);
        $this->assertContains('role:admin_dinas', $middleware);
        $this->assertContains('permission:umkm.read.official', $middleware);
    }

    public function test_guest_is_redirected_to_public_root_from_admin_dinas_dashboard(): void
    {
        $this->get('/admin-dinas/dashboard')
            ->assertRedirect('/');
    }

    public function test_admin_dinas_without_read_permission_is_forbidden(): void
    {
        $user = $this->createUser('admin-dinas-no-permission@example.test');

        $role = Role::query()->create([
            'code' => 'admin_dinas',
            'name' => 'Admin Dinas',
            'is_active' => true,
        ]);

        $user->roles()->attach($role->id);

        $this->actingAs($user)
            ->get('/admin-dinas/dashboard')
            ->assertForbidden();
    }

    public function test_admin_dinas_with_read_permission_can_open_dashboard(): void
    {
        $user = $this->createUser('admin-dinas-read@example.test');

        $role = Role::query()->create([
            'code' => 'admin_dinas',
            'name' => 'Admin Dinas',
            'is_active' => true,
        ]);

        $permission = Permission::query()->create([
            'code' => 'umkm.read.official',
            'name' => 'Read Official UMKM',
            'module' => 'umkm',
        ]);

        $role->permissions()->attach($permission->id);
        $user->roles()->attach($role->id);

        $this->actingAs($user)
            ->get('/admin-dinas/dashboard')
            ->assertOk()
            ->assertSee('Dashboard Admin Dinas');
    }

    private function createUser(string $email): User
    {
        return User::query()->create([
            'name' => 'Admin Dinas Test',
            'email' => $email,
            'password' => 'test-password',
            'is_active' => true,
        ]);
    }
}
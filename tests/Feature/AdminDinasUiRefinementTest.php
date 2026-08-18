<?php

namespace Tests\Feature;

use App\Models\Access\Permission;
use App\Models\Access\Role;
use App\Models\Umkm\Umkm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDinasUiRefinementTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_is_an_executive_summary_without_long_financial_detail_table(): void
    {
        $user = $this->createAdminDinasUser('ui-dashboard@example.test', true);

        $this->actingAs($user)
            ->get('/admin-dinas/dashboard')
            ->assertOk()
            ->assertSee('Ringkasan Pembinaan UMKM')
            ->assertSee('Buka Data UMKM')
            ->assertSee('Buka Ringkasan Data')
            ->assertSee('Cakupan Keuangan')
            ->assertDontSee('Detail Nilai Keuangan Terdata')
            ->assertDontSee('Sumber Pinjaman yang Terdata');
    }

    public function test_data_umkm_has_primary_filters_active_context_and_custom_pagination_copy(): void
    {
        $user = $this->createAdminDinasUser('ui-data@example.test', true);

        Umkm::query()->create([
            'umkm_code' => 'UI-001',
            'business_name' => 'UMKM UI TEST',
            'status_data' => 'resmi',
            'quality_status' => 'lengkap_terpetakan',
        ]);

        $this->actingAs($user)
            ->get('/admin-dinas/umkm')
            ->assertOk()
            ->assertDontSee('Filter Lanjutan')
            ->assertSee('Pilihan Saat Ini')
            ->assertSee('Cari UMKM')
            ->assertSee('Kecamatan')
            ->assertSee('Kelurahan')
            ->assertSee('Kategori')
            ->assertSee('Jenis Usaha')
            ->assertSee('Pemasaran')
            ->assertSee('Kualitas Data')
            ->assertSee('Halaman 1 dari 1')
            ->assertSee('UMKM UI TEST');
    }

    public function test_analytics_uses_refined_visual_bars_and_explains_quality_overlap(): void
    {
        $user = $this->createAdminDinasUser('ui-analytics@example.test', true);

        $response = $this->actingAs($user)
            ->get('/admin-dinas/analytics')
            ->assertOk()
            ->assertSee('Satu UMKM dapat memiliki lebih dari satu kelompok catatan kualitas');

        $this->assertStringNotContainsString('<progress', $response->getContent());

        $viewSource = file_get_contents(
            resource_path('views/pages/admin-dinas/analytics/index.blade.php')
        );

        $this->assertIsString($viewSource);
        $this->assertStringContainsString('progress-bar bg-success', $viewSource);
        $this->assertStringContainsString('progress-bar bg-warning', $viewSource);
        $this->assertStringContainsString(
            "\$data['legality_identified'] ?? 0",
            $viewSource
        );
    }

    public function test_financial_page_keeps_source_literal_quality_separation_copy(): void
    {
        $user = $this->createAdminDinasUser('ui-financial@example.test', true);

        $this->actingAs($user)
            ->get('/admin-dinas/analytics/financial')
            ->assertOk()
            ->assertSee('0 berbeda dari belum tersedia')
            ->assertSee('Sumber Pinjaman yang Tercatat')
            ->assertSee('Catatan Kualitas Sumber Pinjaman')
            ->assertSee('tidak diubah menjadi Mekaar, KUR, atau kategori lain')
            ->assertSee('Rincian Nilai Keuangan yang Tercatat');
    }

    private function createAdminDinasUser(string $email, bool $financial): User
    {
        $user = User::query()->create([
            'name' => 'Admin Dinas UI Test',
            'email' => $email,
            'username' => str_replace(['@', '.'], '-', $email),
            'password' => 'test-password',
            'is_active' => true,
        ]);

        $role = Role::query()->firstOrCreate(
            ['code' => 'admin_dinas'],
            ['name' => 'Admin Dinas', 'is_active' => true]
        );

        $read = Permission::query()->firstOrCreate(
            ['code' => 'umkm.read.official'],
            ['name' => 'Read Official UMKM', 'module' => 'umkm']
        );

        $role->permissions()->syncWithoutDetaching([$read->id]);

        if ($financial) {
            $permission = Permission::query()->firstOrCreate(
                ['code' => 'umkm.sensitive.financial'],
                ['name' => 'View Sensitive Financial', 'module' => 'umkm']
            );

            $role->permissions()->syncWithoutDetaching([$permission->id]);
        }

        $user->roles()->attach($role->id);

        return $user;
    }
}

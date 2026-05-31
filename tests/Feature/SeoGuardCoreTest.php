<?php

namespace Tests\Feature;

use App\Support\Seo\SeoManager;
use Tests\TestCase;

class SeoGuardCoreTest extends TestCase
{
    public function test_seo_guard_core_files_exist(): void
    {
        $this->assertFileExists(config_path('umkm-seo.php'));
        $this->assertFileExists(app_path('Support/Seo/SeoMeta.php'));
        $this->assertFileExists(app_path('Support/Seo/SeoManager.php'));
        $this->assertFileExists(resource_path('views/components/umkm/seo-meta.blade.php'));
    }

    public function test_seo_manager_defaults_to_noindex_when_indexing_is_not_enabled(): void
    {
        config(['umkm-seo.indexing_enabled' => false]);

        $meta = SeoManager::make([
            'area' => 'public',
            'robots' => 'public',
        ])->toArray();

        $this->assertSame('noindex,nofollow,noarchive', $meta['robots']);
    }

    public function test_private_area_is_always_noindex(): void
    {
        config(['umkm-seo.indexing_enabled' => true]);

        $meta = SeoManager::make([
            'area' => 'private',
        ])->toArray();

        $this->assertStringContainsString('noindex', $meta['robots']);
        $this->assertStringContainsString('nofollow', $meta['robots']);
    }

    public function test_layouts_use_seo_component_with_safe_area_rules(): void
    {
        $public = file_get_contents(resource_path('views/layouts/public.blade.php'));
        $auth = file_get_contents(resource_path('views/layouts/auth.blade.php'));
        $dashboard = file_get_contents(resource_path('views/layouts/dashboard.blade.php'));

        $this->assertStringContainsString('<x-umkm.seo-meta', $public);
        $this->assertStringContainsString('area="public"', $public);

        $this->assertStringContainsString('<x-umkm.seo-meta', $auth);
        $this->assertStringContainsString('noindex,nofollow', $auth);

        $this->assertStringContainsString('<x-umkm.seo-meta', $dashboard);
        $this->assertStringContainsString('noindex,nofollow', $dashboard);
    }

    public function test_seo_component_does_not_add_inline_javascript_or_structured_data_yet(): void
    {
        $component = file_get_contents(resource_path('views/components/umkm/seo-meta.blade.php'));

        $this->assertStringNotContainsString('application/ld+json', $component);
        $this->assertStringNotContainsString('<script', strtolower($component));
    }

    public function test_public_landing_renders_standard_title_and_description_meta(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('<title>', false);
        $response->assertSee('<meta name="description"', false);
        $response->assertSee('<meta name="robots"', false);
        $response->assertSee('property="og:title"', false);
        $response->assertSee('name="twitter:title"', false);
    }

    public function test_public_layout_does_not_disable_title_and_description_rendering(): void
    {
        $public = file_get_contents(resource_path('views/layouts/public.blade.php'));

        $this->assertStringContainsString('<x-umkm.seo-meta', $public);
        $this->assertStringContainsString('area="public"', $public);
        $this->assertStringNotContainsString(':render-title="false"', $public);
        $this->assertStringNotContainsString(':render-description="false"', $public);
    }

    public function test_seo_manager_resolves_public_share_image_metadata(): void
    {
        config([
            'umkm-seo.default_image' => '/assets/img/public/share/umkm-monitoring-og.png',
            'umkm-seo.default_image_width' => 1200,
            'umkm-seo.default_image_height' => 630,
            'umkm-seo.default_image_alt' => 'UMKM Monitoring - Sistem Monitoring UMKM Berbasis Data',
        ]);

        $meta = SeoManager::make([
            'area' => 'public',
            'robots' => 'public',
        ])->toArray();

        $this->assertNotEmpty($meta['image']);
        $this->assertStringEndsWith('/assets/img/public/share/umkm-monitoring-og.png', $meta['image']);
        $this->assertSame(1200, $meta['image_width']);
        $this->assertSame(630, $meta['image_height']);
        $this->assertSame('UMKM Monitoring - Sistem Monitoring UMKM Berbasis Data', $meta['image_alt']);
    }

    public function test_public_landing_renders_public_share_image_metadata(): void
    {
        $this->assertFileExists(public_path('assets/img/public/share/umkm-monitoring-og.png'));

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('property="og:image"', false);
        $response->assertSee('property="og:image:width"', false);
        $response->assertSee('property="og:image:height"', false);
        $response->assertSee('property="og:image:alt"', false);
        $response->assertSee('name="twitter:image"', false);
        $response->assertSee('name="twitter:image:alt"', false);
    }
}

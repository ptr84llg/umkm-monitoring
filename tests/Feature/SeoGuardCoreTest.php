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
}

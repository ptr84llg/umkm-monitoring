<?php

namespace App\Services\System;

use App\Models\System\SystemSetting;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class ThemeService
{
    public function settingKey(): string
    {
        return (string) config('umkm-theme.setting_key', 'ui.active_theme');
    }

    public function defaultKey(): string
    {
        $default = (string) config('umkm-theme.default', 'green');

        return $this->isAllowed($default) ? $default : 'green';
    }

    public function allowedKeys(): array
    {
        return array_keys((array) config('umkm-theme.themes', []));
    }

    public function isAllowed(string $themeKey): bool
    {
        return in_array($themeKey, $this->allowedKeys(), true);
    }

    public function options(): array
    {
        $active = $this->activeKey();

        return collect((array) config('umkm-theme.themes', []))
            ->map(function (array $theme, string $key) use ($active): array {
                return [
                    'key' => $key,
                    'label' => (string) ($theme['label'] ?? $key),
                    'description' => (string) ($theme['description'] ?? ''),
                    'file' => (string) ($theme['file'] ?? ''),
                    'tone' => (string) ($theme['tone'] ?? ''),
                    'active' => $key === $active,
                ];
            })
            ->values()
            ->all();
    }

    public function activeKey(): string
    {
        $default = $this->defaultKey();

        try {
            return Cache::remember('umkm.system.active_theme', now()->addMinutes(10), function () use ($default): string {
                if (! Schema::hasTable('system_settings')) {
                    return $default;
                }

                $value = SystemSetting::query()
                    ->where('key', $this->settingKey())
                    ->value('value');

                if (! is_string($value) || ! $this->isAllowed($value)) {
                    return $default;
                }

                return $value;
            });
        } catch (Throwable) {
            return $default;
        }
    }

    public function setActiveTheme(string $themeKey, ?User $actor = null): array
    {
        $themeKey = trim($themeKey);

        if (! $this->isAllowed($themeKey)) {
            throw new InvalidArgumentException('Tema yang dipilih tidak termasuk daftar resmi sistem.');
        }

        if (! Schema::hasTable('system_settings')) {
            throw new RuntimeException('Tabel system_settings belum tersedia. Jalankan migration terlebih dahulu.');
        }

        $before = $this->activeKey();

        SystemSetting::query()->updateOrCreate(
            ['key' => $this->settingKey()],
            [
                'setting_group' => 'appearance',
                'label' => 'Tema aktif sistem',
                'value' => $themeKey,
                'is_public' => true,
                'updated_by_user_id' => $actor?->id,
            ]
        );

        Cache::forget('umkm.system.active_theme');

        return [
            'before' => $before,
            'after' => $themeKey,
        ];
    }
}
<?php

namespace App\Providers;

use App\Services\System\ThemeService;
use Illuminate\Support\ServiceProvider;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        try {
            view()->share('activeTheme', app(ThemeService::class)->activeKey());
        } catch (Throwable) {
            view()->share('activeTheme', config('umkm-theme.default', 'green'));
        }
    }
}
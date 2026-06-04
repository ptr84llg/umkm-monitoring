<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class LandingComponentController extends Controller
{
    public function heroPreviewBoard(): JsonResponse
    {
        return $this->component(
            'landing-hero-preview-board',
            'partials.public.landing.components.hero-preview-board'
        );
    }

    public function dashboardPreview(): JsonResponse
    {
        return $this->component(
            'landing-dashboard-preview',
            'partials.public.landing.components.dashboard-preview'
        );
    }


    public function ctaSection(): JsonResponse
    {
        return $this->component(
            'landing-cta-section',
            'partials.public.landing.components.cta-section'
        );
    }

    public function regionModal(): JsonResponse
    {
        return $this->component(
            'landing-region-modal',
            'partials.public.landing.components.region-modal'
        );
    }

    private function component(string $component, string $view): JsonResponse
    {
        return response()
            ->json([
                'ok' => true,
                'component' => $component,
                'html' => view($view)->render(),
            ])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
    }
}



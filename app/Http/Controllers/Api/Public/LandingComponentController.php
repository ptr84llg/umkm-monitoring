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
            'partials.public.landing.hero-preview-board'
        );
    }

    public function dashboardPreview(): JsonResponse
    {
        return $this->component(
            'landing-dashboard-preview',
            'partials.public.landing.dashboard-preview'
        );
    }

    public function summarySection(): JsonResponse
    {
        return $this->component(
            'landing-summary-section',
            'partials.public.landing.summary-section'
        );
    }

    public function ctaSection(): JsonResponse
    {
        return $this->component(
            'landing-cta-section',
            'partials.public.landing.cta-section'
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

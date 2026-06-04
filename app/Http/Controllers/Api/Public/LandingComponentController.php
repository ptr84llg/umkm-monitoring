<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Support\PublicLanding\PublicLandingData;
use Illuminate\Http\JsonResponse;

class LandingComponentController extends Controller
{
    public function heroPreviewBoard(): JsonResponse
    {
        return $this->component(
            'landing-hero-preview-board',
            'partials.public.landing.components.hero-preview-board',
            [
                'publicLandingMap' => PublicLandingData::mapPreview(),
            ]
        );
    }

    public function dashboardPreview(): JsonResponse
    {
        return $this->component(
            'landing-dashboard-preview',
            'partials.public.landing.components.dashboard-preview',
            [
                'publicLandingAnalytics' => PublicLandingData::analytics(),
            ]
        );
    }

    public function ctaSection(): JsonResponse
    {
        return $this->component(
            'landing-cta-section',
            'partials.public.landing.components.cta-section',
            [
                'publicLandingSummary' => PublicLandingData::summary(),
            ]
        );
    }

    public function regionModal(): JsonResponse
    {
        return $this->component(
            'landing-region-modal',
            'partials.public.landing.components.region-modal'
        );
    }

    private function component(string $component, string $view, array $data = []): JsonResponse
    {
        return response()
            ->json([
                'ok' => true,
                'component' => $component,
                'scope' => 'public-safe',
                'html' => view($view, $data)->render(),
            ])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
    }
}
<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class LandingComponentController extends Controller
{
    public function heroPreviewBoard(): JsonResponse
    {
        $html = view('partials.public.landing.hero-preview-board')->render();

        return response()
            ->json([
                'ok' => true,
                'component' => 'landing-hero-preview-board',
                'html' => $html,
            ])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
    }
}

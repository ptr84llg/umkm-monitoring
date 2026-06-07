<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Support\PublicLanding\PublicLandingAggregateCore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LandingPreviewController extends Controller
{
    public function data(Request $request): JsonResponse
    {
        $payload = PublicLandingAggregateCore::payload($request->only([
            'scope',
            'mode',
            'detail_card',
            'label',
            'province_code',
            'city_code',
            'district_code',
            'village_code',
        ]));

        return response()
            ->json([
                'ok' => true,
                'data' => $payload,
            ])
            ->header('X-UMKM-Public-Safe', '1')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate');
    }
}
<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Audit\SecurityEventLog;
use App\Support\PublicLanding\PublicLandingRegionGeometry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LandingRegionMapController extends Controller
{
    public function geometry(Request $request): JsonResponse
    {
        $this->guardLandingAjax($request);

        $validated = $request->validate([
            'scope' => ['nullable', Rule::in(['city', 'district', 'village'])],
            'district_code' => ['nullable', 'string', 'max:20', 'regex:/^[0-9.]+$/'],
            'village_code' => ['nullable', 'string', 'max:24', 'regex:/^[0-9.]+$/'],
            'district_name' => ['nullable', 'string', 'max:120'],
            'village_name' => ['nullable', 'string', 'max:120'],
        ]);

        $payload = PublicLandingRegionGeometry::payload($validated);

        return response()
            ->json([
                'ok' => true,
                'data' => $payload,
            ])
            ->header('X-UMKM-Public-Safe', '1')
            ->header('Cache-Control', 'no-store, private');
    }

    protected function guardLandingAjax(Request $request): void
    {
        $accept = strtolower((string) $request->headers->get('Accept', ''));
        $requestedWith = strtolower((string) $request->headers->get('X-Requested-With', ''));
        $umkmRequest = strtolower((string) $request->headers->get('X-UMKM-Request', ''));

        $validAccept = str_contains($accept, 'application/json') || $request->expectsJson();
        $validAjax = $requestedWith === 'xmlhttprequest';
        $validUmkmRequest = $umkmRequest === 'internal';

        if ($validAccept && $validAjax && $validUmkmRequest) {
            return;
        }

        SecurityEventLog::query()->create([
            'actor_user_id' => $request->user()?->id,
            'event_type' => 'landing_region_map_invalid_ajax_header',
            'severity' => 'medium',
            'event_detail' => 'Landing region map request blocked due to invalid AJAX headers.',
            'ip_address' => $request->ip(),
            'event_time' => now(),
        ]);

        abort(403);
    }
}

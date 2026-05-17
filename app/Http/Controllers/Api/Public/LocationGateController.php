<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\SecurityEventLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LocationGateController extends Controller
{
    public function verify(Request $request): JsonResponse
    {
        if (! (bool) config('umkm.location_gate.enabled', true)) {
            return response()->json([
                'ok' => true,
                'message' => 'Location gate dinonaktifkan melalui konfigurasi.',
                'data' => [
                    'verified' => true,
                    'bypass' => true,
                ],
            ]);
        }

        $validator = Validator::make($request->all(), [
            'status' => ['required', 'string', 'in:granted'],
            'permission' => ['required', 'string', 'in:granted'],
            'checked_at' => ['nullable', 'date'],
            'position' => ['required', 'array'],
            'position.latitude' => ['required', 'numeric', 'between:-90,90'],
            'position.longitude' => ['required', 'numeric', 'between:-180,180'],
            'position.accuracy' => ['required', 'numeric', 'min:0'],
            'position.timestamp' => ['nullable', 'numeric'],
        ]);

        if ($validator->fails()) {
            $this->logEvent(
                $request,
                'location_gate_rejected_payload',
                'medium',
                'Location gate rejected because verification payload was incomplete or invalid.'
            );

            return response()
                ->json([
                    'ok' => false,
                    'message' => 'Data lokasi belum lengkap untuk diverifikasi. Aktifkan lokasi, tunggu hingga koordinat terbaca, lalu coba ulang.',
                    'data' => [
                        'verified' => false,
                        'reason' => 'invalid_location_payload',
                    ],
                ], 422)
                ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
                ->header('Pragma', 'no-cache');
        }

        $validated = $validator->validated();

        $accuracy = data_get($validated, 'position.accuracy');
        $maxAccuracy = (float) config('umkm.location_gate.max_accuracy_meters', 10000);

        if ($accuracy !== null && (float) $accuracy > $maxAccuracy) {
            $this->logEvent(
                $request,
                'location_gate_rejected_accuracy',
                'medium',
                'Location gate rejected because reported accuracy exceeded configured limit.'
            );

            return response()
                ->json([
                    'ok' => false,
                    'message' => 'Akurasi lokasi belum memenuhi batas validasi sistem. Aktifkan lokasi dengan akurasi lebih baik lalu coba ulang.',
                    'data' => [
                        'verified' => false,
                        'reason' => 'accuracy_exceeded',
                    ],
                ])
                ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
                ->header('Pragma', 'no-cache');
        }

        $ttlMinutes = max(1, (int) config('umkm.location_gate.ttl_minutes', 15));
        $precision = max(4, min(7, (int) config('umkm.location_gate.coordinate_precision', 6)));
        $now = now();
        $expiresAt = $now->copy()->addMinutes($ttlMinutes);

        $sessionPayload = [
            'verified' => true,
            'status' => 'granted',
            'permission' => 'granted',
            'verified_at' => $now->toIso8601String(),
            'expires_at' => $expiresAt->toIso8601String(),
            'ip_hash' => $this->fingerprint($request->ip()),
            'user_agent_hash' => $this->fingerprint($request->userAgent() ?? ''),
            'position' => [
                'latitude' => round((float) data_get($validated, 'position.latitude'), $precision),
                'longitude' => round((float) data_get($validated, 'position.longitude'), $precision),
                'accuracy' => $accuracy !== null ? round((float) $accuracy, 2) : null,
                'timestamp' => data_get($validated, 'position.timestamp'),
            ],
        ];

        $request->session()->put($this->sessionKey(), $sessionPayload);

        $this->logEvent(
            $request,
            'location_gate_verified',
            'low',
            'Location gate session verified before login route access.'
        );

        return response()->json([
            'ok' => true,
            'message' => 'Validasi lokasi berhasil.',
            'data' => [
                'verified' => true,
                'verified_at' => $sessionPayload['verified_at'],
                'expires_at' => $sessionPayload['expires_at'],
            ],
        ]);
    }

    public function clear(Request $request): JsonResponse
    {
        $request->session()->forget($this->sessionKey());

        $this->logEvent(
            $request,
            'location_gate_cleared',
            'low',
            'Location gate session cleared by frontend state change.'
        );

        return response()->json([
            'ok' => true,
            'message' => 'Status validasi lokasi dibersihkan.',
        ]);
    }

    private function sessionKey(): string
    {
        return (string) config('umkm.location_gate.session_key', 'umkm.location_gate');
    }

    private function fingerprint(?string $value): string
    {
        return hash_hmac('sha256', (string) $value, (string) config('app.key'));
    }

    private function logEvent(Request $request, string $type, string $severity, string $detail): void
    {
        SecurityEventLog::query()->create([
            'actor_user_id' => $request->user()?->id,
            'event_type' => $type,
            'severity' => $severity,
            'event_detail' => $detail,
            'ip_address' => $request->ip(),
            'event_time' => now(),
        ]);
    }
}

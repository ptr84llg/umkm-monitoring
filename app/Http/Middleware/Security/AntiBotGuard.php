<?php

namespace App\Http\Middleware\Security;

use App\Models\Audit\SecurityEventLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class AntiBotGuard
{
    private const BLOCK_SCORE = 60;

    private const SAFE_MESSAGE = 'Login belum dapat diproses. Tunggu beberapa saat, lalu coba kembali.';

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('post')) {
            return $next($request);
        }

        $evaluation = $this->evaluate($request);

        if ($evaluation['score'] < self::BLOCK_SCORE) {
            return $next($request);
        }

        $this->logBotRisk($request, $evaluation['score'], $evaluation['signals']);

        if ($this->expectsSafeJson($request)) {
            return response()->json([
                'ok' => false,
                'message' => self::SAFE_MESSAGE,
            ], 429);
        }

        return back()
            ->withErrors([
                $this->loginField($request) => self::SAFE_MESSAGE,
            ])
            ->onlyInput($this->loginField($request));
    }

    private function evaluate(Request $request): array
    {
        $score = 0;
        $signals = [];

        if ($request->filled('website')) {
            $score += 80;
            $signals[] = 'honeypot_filled';
        }

        $tts = $request->input('tts');

        if ($tts !== null && $tts !== '') {
            if (! is_numeric($tts)) {
                $score += 40;
                $signals[] = 'invalid_tts';
            } else {
                $ttsValue = (float) $tts;

                if ($ttsValue > 0 && $ttsValue < 2) {
                    $score += 45;
                    $signals[] = 'too_fast_submit';
                }

                if ($ttsValue > 3600) {
                    $score += 20;
                    $signals[] = 'stale_tts';
                }
            }
        }

        if ($request->userAgent() === null || trim((string) $request->userAgent()) === '') {
            $score += 20;
            $signals[] = 'empty_user_agent';
        }

        if (config('umkm.captcha.provider', 'none') !== 'none' && ! $request->filled('captcha_token')) {
            $score += 60;
            $signals[] = 'captcha_token_missing';
        }

        return [
            'score' => $score,
            'signals' => $signals,
        ];
    }

    private function logBotRisk(Request $request, int $score, array $signals): void
    {
        try {
            SecurityEventLog::query()->create([
                'actor_user_id' => $request->user()?->id,
                'event_type' => 'bot_risk_blocked',
                'severity' => 'high',
                'event_detail' => 'Blocked by anti bot guard. score='.$score.' signals='.implode(',', $signals),
                'ip_address' => $request->ip(),
                'event_time' => now(),
            ]);
        } catch (Throwable) {
            // Logging failure must not expose implementation details to the requester.
        }
    }

    private function expectsSafeJson(Request $request): bool
    {
        return $request->expectsJson()
            || $request->ajax()
            || $request->header('X-UMKM-Request') === 'internal';
    }

    private function loginField(Request $request): string
    {
        return $request->has('identifier') ? 'identifier' : 'email';
    }
}

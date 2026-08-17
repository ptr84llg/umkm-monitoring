<?php

namespace Tests\Feature;

use App\Http\Middleware\Security\EnforceSingleDeviceSession;
use App\Models\Auth\AuthDeviceSession;
use App\Models\Auth\AuthOtpChallenge;
use App\Models\User;
use App\Services\Auth\LoginOtpService;
use App\Services\Auth\SingleDeviceSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class Checkpoint10FSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_single_device_activation_replaces_previous_session_and_middleware_rejects_stale_session(): void
    {
        foreach (['user_devices', 'auth_device_sessions'] as $table) {
            $this->assertTrue(Schema::hasTable($table));
        }

        $user = User::query()->create([
            'name' => 'Single Device User',
            'email' => 'single-device@example.test',
            'password' => 'SingleDevicePassword123',
            'is_active' => true,
        ]);

        $service = app(SingleDeviceSessionService::class);
        $firstRequest = $this->requestWithDevice('device-one-10f');
        $firstSession = $service->activate($user, $firstRequest, 'manual');

        $this->assertSame('active', $firstSession->fresh()->status);
        $this->assertNotNull($user->fresh()->current_device_id);

        $secondRequest = $this->requestWithDevice('device-two-10f');
        $secondSession = $service->activate($user->fresh(), $secondRequest, 'google');

        $this->assertSame('replaced', $firstSession->fresh()->status);
        $this->assertNotNull($firstSession->fresh()->revoked_at);
        $this->assertSame('active', $secondSession->fresh()->status);
        $this->assertSame($secondSession->user_device_id, $user->fresh()->current_device_id);
        $this->assertSame(1, AuthDeviceSession::query()->where('user_id', $user->id)->where('status', 'active')->count());

        $middleware = app(EnforceSingleDeviceSession::class);

        $firstRequest->setUserResolver(fn () => $user->fresh());
        $firstRequest->headers->set('Accept', 'application/json');
        $staleResponse = $middleware->handle($firstRequest, fn () => response('should-not-pass'));
        $this->assertSame(401, $staleResponse->getStatusCode());

        $secondRequest->setUserResolver(fn () => $user->fresh());
        $currentResponse = $middleware->handle($secondRequest, fn () => response('current'));
        $this->assertSame(200, $currentResponse->getStatusCode());
        $this->assertSame('current', $currentResponse->getContent());
    }

    public function test_login_otp_has_expiry_attempt_limit_hash_storage_and_resend_cooldown(): void
    {
        Mail::fake();

        $user = User::query()->create([
            'name' => 'OTP User',
            'email' => 'otp-10f@example.test',
            'password' => 'OtpPassword123',
            'is_active' => true,
        ]);

        $request = $this->requestWithDevice('otp-device-10f');
        $service = app(LoginOtpService::class);
        $issued = $service->createLoginChallenge($user, $request, '/');
        $challenge = $issued['challenge']->fresh();

        $this->assertSame(5, (int) $challenge->max_attempts);
        $this->assertSame(0, (int) $challenge->attempt_count);
        $this->assertSame(64, strlen((string) $challenge->otp_hash));
        $this->assertSame(64, strlen((string) $challenge->challenge_token_hash));
        $this->assertNotSame($issued['session_payload']['challenge_token'], $challenge->challenge_token_hash);
        $this->assertTrue($challenge->expires_at->isFuture());
        $this->assertGreaterThan(0, $service->resendCooldownSecondsForPayload($issued['session_payload'], $challenge));

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $result = $service->verifyLoginChallenge(
                $user,
                (string) $issued['session_payload']['challenge_token'],
                '000000',
                $request
            );
            $this->assertFalse($result['ok']);
        }

        $challenge = AuthOtpChallenge::query()->findOrFail($challenge->id);
        $this->assertSame(5, (int) $challenge->attempt_count);
        $this->assertSame('locked', $challenge->status);
        $this->assertNotNull($challenge->cancelled_at);
    }

    private function requestWithDevice(string $deviceId): Request
    {
        $request = Request::create('/', 'GET');
        $request->headers->set('User-Agent', 'Mozilla/5.0 Checkpoint10F Chrome/140.0');
        $request->headers->set('Accept-Language', 'id-ID,id;q=0.9');
        $request->headers->set('X-UMKM-Device-Id', $deviceId);

        $store = new Store('checkpoint10f', new ArraySessionHandler(120));
        $store->start();
        $request->setLaravelSession($store);

        return $request;
    }
}
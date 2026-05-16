<?php
namespace Tests\Unit;
use App\Http\Middleware\{AntiBotGuard,SafeErrorResponder,SecureHeaders};use App\Support\Security\RateLimitProfiles;use PHPUnit\Framework\TestCase;
class SecurityHardeningTest extends TestCase { public function test_security_hardening_classes_exist(): void { $this->assertTrue(class_exists(SecureHeaders::class)); $this->assertTrue(class_exists(AntiBotGuard::class)); $this->assertTrue(class_exists(SafeErrorResponder::class)); $this->assertArrayHasKey('export', RateLimitProfiles::profiles()); }}

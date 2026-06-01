@extends('layouts.auth')

@php
    $assetProfile = 'base';
    $assetModules = ['loader', 'location', 'session'];
    $pageCss = ['auth/login.css'];
    $pageJs = ['auth/otp-challenge.js', 'auth-login-anti-bot.js'];

    $otpContext = $otpContext ?? 'login';
    $verifyAction = $verifyAction ?? route('login.otp.verify');
    $resendAction = $resendAction ?? route('login.otp.resend');
    $returnUrl = $returnUrl ?? route('login');
    $returnLabel = $returnLabel ?? 'Kembali ke login';
    $brandSubtitle = $brandSubtitle ?? 'Verifikasi Akses Internal';
    $eyebrow = $eyebrow ?? 'Verifikasi OTP';
    $title = $title ?? 'Masukkan Kode OTP';
    $subtitle = $subtitle ?? ('Kode OTP dikirim ke '.$maskedEmail.'. Gunakan kode terbaru untuk menyelesaikan login.');
    $submitLabel = $submitLabel ?? 'Verifikasi dan Masuk';
    $successTitle = $successTitle ?? 'Verifikasi berhasil';
    $successMessage = $successMessage ?? 'Mengalihkan ke dashboard.';
    $loadingTitle = $loadingTitle ?? 'Memverifikasi OTP';
    $loadingMessage = $loadingMessage ?? 'Sistem sedang memvalidasi kode OTP.';
    $resendLoadingTitle = $resendLoadingTitle ?? 'Mengirim OTP Baru';
    $resendLoadingMessage = $resendLoadingMessage ?? 'Sistem sedang mengirim ulang kode OTP.';
    $note = $note ?? 'Verifikasi OTP hanya aktif setelah proses membutuhkan perlindungan tambahan. Jika halaman ini dibuka tanpa sesi verifikasi, sistem akan mengarahkan kembali ke proses yang sesuai.';
@endphp

@section('title', 'Verifikasi OTP | Monitoring UMKM')

@section('content')
<section class="auth-login-page auth-login-premium"
         data-auth-otp-page
         data-auth-otp-context="{{ $otpContext }}"
         data-auth-login-url="{{ route('login') }}"
         data-auth-otp-expires-at="{{ $expiresAt }}"
         data-auth-otp-resend-available-at="{{ $resendAvailableAt }}"
         data-auth-otp-verify-loading-title="{{ $loadingTitle }}"
         data-auth-otp-verify-loading-message="{{ $loadingMessage }}"
         data-auth-otp-resend-loading-title="{{ $resendLoadingTitle }}"
         data-auth-otp-resend-loading-message="{{ $resendLoadingMessage }}"
         data-auth-otp-success-title="{{ $successTitle }}"
         data-auth-otp-success-message="{{ $successMessage }}">
    <div class="auth-background" aria-hidden="true">
        <span class="auth-gradient auth-gradient-a"></span>
        <span class="auth-gradient auth-gradient-b"></span>
    </div>

    <div class="container py-4 py-xl-5 auth-container">
        <div class="card border-0 auth-shell">
            <div class="card-body p-4 p-xl-5">
                <div class="row g-2 align-items-center">
                    <div class="col-12 col-lg-6 mx-lg-auto">
                        <a href="{{ url('/') }}" class="d-inline-flex align-items-center gap-3 text-decoration-none auth-brand-link mb-4">
                            <span class="auth-brand-mark">MU</span>
                            <span class="auth-brand-text">
                                <strong class="d-block">Monitoring UMKM</strong>
                                <small class="d-block">{{ $brandSubtitle }}</small>
                            </span>
                        </a>

                        <div class="card border-0 shadow-sm auth-login-card">
                            <div class="card-body p-4 p-xl-5">
                                <div class="d-flex align-items-start justify-content-between gap-3 mb-4">
                                    <div>
                                        <span class="auth-card-eyebrow">{{ $eyebrow }}</span>
                                        <h1 class="h3 fw-bold auth-card-title mt-2 mb-2">{{ $title }}</h1>
                                        <p class="auth-card-subtitle mb-0">{{ $subtitle }}</p>
                                    </div>
                                    <span class="badge rounded-pill auth-card-badge" data-auth-otp-status-badge>OTP Aktif</span>
                                </div>

                                <div class="auth-otp-timer-grid" role="status" aria-live="polite">
                                    <div class="auth-otp-timer-card">
                                        <span>Kode berlaku</span>
                                        <strong data-auth-otp-expiry-countdown>--:--</strong>
                                    </div>
                                    <div class="auth-otp-timer-card">
                                        <span>Kirim ulang</span>
                                        <strong data-auth-otp-resend-countdown>--:--</strong>
                                    </div>
                                </div>

                                @if (session('status'))
                                    <div class="alert alert-success" role="status">{{ session('status') }}</div>
                                @endif

                                @if ($errors->any())
                                    <div class="alert alert-danger" role="alert">
                                        Verifikasi belum berhasil. Periksa kembali kode OTP yang dikirim.
                                    </div>
                                @endif

                                <form method="POST" action="{{ $verifyAction }}" data-auth-otp-form data-umkm-anti-bot-form novalidate>
                                    @csrf
                                    <input type="hidden" name="challenge_token" value="{{ $challengeToken }}" data-auth-otp-challenge-token>
                                    <input type="hidden" name="otp_code" value="" data-auth-otp-code>
                                    <div class="visually-hidden" aria-hidden="true" data-umkm-login-honeypot>
                                        <label for="otp_website">Website</label>
                                        <input type="text" id="otp_website" name="website" value="" tabindex="-1" autocomplete="off">
                                    </div>
                                    <input type="hidden" name="tts" value="0" data-umkm-login-tts>

                                    <div class="mb-3">
                                        <label class="form-label">Kode OTP 6 Digit</label>
                                        <div class="auth-otp-code-grid" role="group" aria-label="Kode OTP 6 digit" data-auth-otp-digit-group>
                                            <input type="text" class="auth-otp-digit" inputmode="numeric" pattern="[0-9]*" maxlength="1" autocomplete="one-time-code" data-auth-otp-digit aria-label="Digit OTP 1" autofocus>
                                            <input type="text" class="auth-otp-digit" inputmode="numeric" pattern="[0-9]*" maxlength="1" autocomplete="one-time-code" data-auth-otp-digit aria-label="Digit OTP 2">
                                            <input type="text" class="auth-otp-digit" inputmode="numeric" pattern="[0-9]*" maxlength="1" autocomplete="one-time-code" data-auth-otp-digit aria-label="Digit OTP 3">
                                            <input type="text" class="auth-otp-digit" inputmode="numeric" pattern="[0-9]*" maxlength="1" autocomplete="one-time-code" data-auth-otp-digit aria-label="Digit OTP 4">
                                            <input type="text" class="auth-otp-digit" inputmode="numeric" pattern="[0-9]*" maxlength="1" autocomplete="one-time-code" data-auth-otp-digit aria-label="Digit OTP 5">
                                            <input type="text" class="auth-otp-digit" inputmode="numeric" pattern="[0-9]*" maxlength="1" autocomplete="one-time-code" data-auth-otp-digit aria-label="Digit OTP 6">
                                        </div>
                                        @error('otp_code')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                        <div class="form-text auth-field-hint" data-auth-otp-helper>
                                            Masukkan 6 digit kode OTP. Jangan membagikan kode kepada pihak lain.
                                        </div>
                                    </div>

                                    <button type="submit" class="btn btn-primary w-100 py-3 auth-submit" data-auth-otp-submit disabled>
                                        <span data-auth-otp-submit-text>{{ $submitLabel }}</span>
                                    </button>
                                </form>

                                <div class="auth-otp-resend-area"
                                     data-auth-otp-resend-area
                                     data-auth-otp-resend-action="{{ $resendAction }}"
                                     data-auth-otp-resend-csrf="{{ csrf_token() }}"
                                     data-auth-otp-resend-token-value="{{ $challengeToken }}"></div>

                                <div class="auth-return-row">
                                    <a href="{{ $returnUrl }}" class="auth-return-action" aria-label="{{ $returnLabel }}">
                                        <span class="auth-action-icon" aria-hidden="true">
                                            <svg viewBox="0 0 24 24"><path d="M10.8 5.2 4 12l6.8 6.8 1.4-1.4L7.8 13H20v-2H7.8l4.4-4.4-1.4-1.4Z"/></svg>
                                        </span>
                                        <span>{{ $returnLabel }}</span>
                                    </a>
                                </div>

                                <div class="rounded-4 p-3 mt-3 auth-form-note">
                                    <strong>Catatan keamanan:</strong> {{ $note }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
   </div>
</section>
@endsection
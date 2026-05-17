@props([
    'id' => 'umkmReadinessLoader',
    'title' => 'Menyiapkan halaman',
    'subtitle' => 'Sistem sedang memeriksa kesiapan komponen halaman sebelum konten ditampilkan.',
    'footnote' => 'Progress 100% berarti seluruh tahapan kesiapan telah diproses. Status terbatas atau gagal tetap ditangani dengan fallback sesuai kebutuhan halaman.',
    'lines' => [],
    'autoHide' => true,
    'hideDelay' => 420,
    'minVisible' => 1200,
    'lineDelay' => 80,
    'completeDelay' => 240,
    'smokeGuard' => true,
])

@php
    $safeId = preg_replace('/[^A-Za-z0-9\-_]/', '', $id) ?: 'umkmReadinessLoader';

    $defaultLines = [
        [
            'key' => 'page-structure',
            'label' => 'Struktur halaman',
            'description' => 'Memeriksa struktur dasar halaman.',
            'check' => 'dom',
            'required' => true,
        ],
        [
            'key' => 'core-system',
            'label' => 'Core sistem',
            'description' => 'Memeriksa kesiapan core UI sistem.',
            'check' => 'core',
            'required' => true,
        ],
    ];

    $readinessLines = is_array($lines) && count($lines) > 0 ? $lines : $defaultLines;

    $readinessJson = json_encode(
        $readinessLines,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_APOS | JSON_HEX_QUOT
    ) ?: '[]';

    $readinessPayload = base64_encode($readinessJson);
@endphp

<div
    id="{{ $safeId }}"
    class="umkm-readiness-loader"
    data-umkm-readiness-loader
    data-umkm-readiness-lines-base64="{{ $readinessPayload }}"
    data-umkm-readiness-auto-hide="{{ $autoHide ? 'true' : 'false' }}"
    data-umkm-readiness-hide-delay="{{ (int) $hideDelay }}"
    data-umkm-readiness-min-visible="{{ (int) $minVisible }}"
    data-umkm-readiness-line-delay="{{ (int) $lineDelay }}"
    data-umkm-readiness-complete-delay="{{ (int) $completeDelay }}"
    data-umkm-readiness-smoke-guard="{{ $smokeGuard ? 'true' : 'false' }}"
    role="status"
    aria-live="polite"
>
    <section class="umkm-readiness-card" aria-labelledby="{{ $safeId }}Title">
        <div class="umkm-readiness-glow umkm-readiness-glow-a" aria-hidden="true"></div>
        <div class="umkm-readiness-glow umkm-readiness-glow-b" aria-hidden="true"></div>

        <div class="umkm-readiness-shell">
            <div class="umkm-readiness-visual" aria-hidden="true">
                <div class="umkm-readiness-orbit">
                    <svg class="umkm-readiness-ring" viewBox="0 0 120 120">
                        <circle class="umkm-readiness-ring-track" cx="60" cy="60" r="50"></circle>
                        <circle class="umkm-readiness-ring-value" cx="60" cy="60" r="50" data-umkm-readiness-ring></circle>
                    </svg>

                    <div class="umkm-readiness-percent-wrap">
                        <strong class="umkm-readiness-percent" data-umkm-readiness-percent>0%</strong>
                        <span>siap</span>
                    </div>
                </div>

                <div class="umkm-readiness-stage" data-umkm-readiness-stage>
                    <span class="umkm-readiness-stage-icon" data-umkm-readiness-active-icon>•</span>
                </div>
            </div>

            <div class="umkm-readiness-content">
                <div class="umkm-readiness-head">
                    <span class="umkm-readiness-mark" aria-hidden="true">
                        <svg viewBox="0 0 24 24">
                            <path d="M12 2 3 7v10l9 5 9-5V7l-9-5Zm0 2.3L17.8 7 12 9.7 6.2 7 12 4.3ZM5 8.6l6 2.8v7.9l-6-3.4V8.6Zm8 10.7v-7.9l6-2.8v7.3l-6 3.4Z"/>
                        </svg>
                    </span>

                    <div>
                        <h2 id="{{ $safeId }}Title" class="umkm-readiness-title">{{ $title }}</h2>
                        <p class="umkm-readiness-subtitle">{{ $subtitle }}</p>
                    </div>
                </div>

                <div class="umkm-readiness-active" data-umkm-readiness-active>
                    <span class="umkm-readiness-active-badge" data-umkm-readiness-active-badge>menunggu</span>
                    <strong data-umkm-readiness-active-title>Menyiapkan kesiapan halaman</strong>
                    <span data-umkm-readiness-active-message>Mohon tunggu, sistem sedang menyiapkan tampilan awal.</span>
                </div>

                <div class="umkm-readiness-progress" aria-label="Progress kesiapan halaman">
                    <div class="umkm-readiness-track">
                        <div class="umkm-readiness-bar" data-umkm-readiness-bar></div>
                    </div>
                </div>

                <div class="umkm-readiness-summary" aria-label="Ringkasan status kesiapan">
                    <span class="umkm-readiness-chip is-success">
                        <strong data-umkm-readiness-count="success">0</strong>
                        <small>siap</small>
                    </span>
                    <span class="umkm-readiness-chip is-limited">
                        <strong data-umkm-readiness-count="limited">0</strong>
                        <small>terbatas</small>
                    </span>
                    <span class="umkm-readiness-chip is-skipped">
                        <strong data-umkm-readiness-count="skipped">0</strong>
                        <small>dilewati</small>
                    </span>
                    <span class="umkm-readiness-chip is-failed">
                        <strong data-umkm-readiness-count="failed">0</strong>
                        <small>gagal</small>
                    </span>
                </div>

                <details class="umkm-readiness-detail" data-umkm-readiness-detail>
                    <summary>
                        <span>Detail kesiapan</span>
                        <small data-umkm-readiness-detail-summary>0/0 diproses</small>
                    </summary>

                    <ul class="umkm-readiness-lines" data-umkm-readiness-list></ul>
                </details>

                <p class="umkm-readiness-foot">{{ $footnote }}</p>
            </div>
        </div>
    </section>
</div>

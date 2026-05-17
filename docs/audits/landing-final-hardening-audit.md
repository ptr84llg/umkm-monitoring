# Landing Final Hardening Audit

## Status

Audit ini dibuat setelah perbaikan landing menuju pola Minimal DOM Exposure, AJAX-Based Component Loading, Server-Side Authority, dan CSP-safe rendering. Audit ini mengunci posisi bahwa landing sudah tidak lagi bergantung pada DOM awal yang terlalu besar dan tidak lagi menaruh preview data utama di JavaScript frontend.

## Ringkasan Keputusan

1. Landing tetap memakai JavaScript karena browser membutuhkan script untuk UI, AJAX, chart, modal, dan event interaksi.
2. JavaScript tidak dianggap rahasia, sehingga isinya harus dibatasi hanya untuk UI bridge dan renderer ringan.
3. Backend menjadi pusat validasi, agregasi public-safe, chart payload, dan fragment rendering.
4. Fragment HTML dari backend tidak boleh membawa inline script atau inline style.
5. CSP tetap ketat dan tidak dilonggarkan.
6. Location gate tetap menjadi pengunci akses login.
7. Tombol login tidak boleh menjadi tombol statis di DOM.
8. Endpoint publik tetap harus melewati middleware internal, origin, referer, fetch metadata, throttle, dan audit.

## Matriks Audit File

| File | Baris | Inline Style | Inline Script | Direct Fetch | Public Metric Marker | Component Placeholder |
|---|---:|---:|---:|---:|---:|---:|
| `resources\views\landing.blade.php` | 367 | 0 | 0 | 0 | 0 | 16 |
| `app\Http\Controllers\Api\Public\LandingComponentController.php` | 54 | 0 | 0 | 0 | 0 | 0 |
| `app\Http\Controllers\Api\Public\LandingPreviewController.php` | 326 | 0 | 0 | 0 | 0 | 0 |
| `public\assets\js\pages\public\landing.js` | 7 | 0 | 0 | 0 | 0 | 0 |
| `public\assets\js\pages\public\landing\landing-state.js` | 165 | 0 | 0 | 0 | 0 | 0 |
| `public\assets\js\pages\public\landing\landing-navigation.js` | 200 | 0 | 0 | 0 | 0 | 0 |
| `public\assets\js\pages\public\landing\landing-chart.js` | 275 | 0 | 0 | 0 | 0 | 0 |
| `public\assets\js\pages\public\landing\landing-region.js` | 818 | 0 | 0 | 0 | 5 | 0 |
| `public\assets\js\pages\public\landing\landing-location-bridge.js` | 25 | 0 | 0 | 0 | 0 | 0 |
| `public\assets\js\pages\public\landing\landing-components.js` | 61 | 0 | 0 | 0 | 0 | 0 |
| `public\assets\js\pages\public\landing\landing-boot.js` | 22 | 0 | 0 | 0 | 1 | 0 |
| `resources\views\partials\public\landing\fragments\metrics.blade.php` | 11 | 0 | 0 | 0 | 3 | 0 |
| `resources\views\partials\public\landing\fragments\indicators.blade.php` | 30 | 0 | 0 | 0 | 0 | 0 |
| `resources\views\partials\public\landing\fragments\areas.blade.php` | 25 | 0 | 0 | 0 | 0 | 0 |
| `resources\views\partials\public\landing\fragments\empty-state.blade.php` | 9 | 0 | 0 | 0 | 0 | 0 |

## Hasil yang Dikunci

1. landing.blade.php berfungsi sebagai shell dan placeholder komponen.
2. Hero preview board, dashboard preview, summary section, dan CTA dimuat melalui AJAX component.
3. LandingComponentController merender partial menjadi HTML fragment.
4. LandingPreviewController menghasilkan preview data, chart payload, dan fragment public-safe.
5. landing-region.js hanya memanggil endpoint dan menukar fragment/teks ke container.
6. Fragment indikator sudah tidak memakai style="".
7. Fallback JS sudah tidak memakai .style.width.
8. Progress bar indikator memakai class CSS progress-fill-*.
9. Chart masih memakai Chart.js, sehingga chart payload public-safe tetap dikirim ke browser.
10. Tidak ada pelonggaran CSP.

## Batasan Lanjutan

Jika nanti ada tambahan fitur landing, aturan berikut tetap berlaku:

1. Jangan menaruh data internal UMKM di Blade atau JavaScript.
2. Jangan menaruh rumus, bobot, ranking, skor, ambang indikator, query, atau otorisasi di frontend.
3. Jangan membuat endpoint publik tanpa middleware internal.
4. Jangan memakai etch() langsung di page JS.
5. Jangan menambahkan inline script atau inline style.
6. Jangan membuat tombol login statis yang melewati location gate.
7. Jangan memindahkan validasi final dari backend ke frontend.

## Posisi Berikutnya

Setelah audit ini, landing dapat dianggap cukup untuk sementara. Langkah berikutnya boleh masuk ke **Login-1 — Audit Login Existing Foundation**, tetapi tetap dimulai dari audit, bukan redesign atau pembuatan fitur baru.

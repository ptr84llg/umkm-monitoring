# Landing JS Responsibility Audit

## Status Audit

Audit ini dibuat setelah refactor landing menuju pola **AJAX component**, **modular JavaScript**, dan **backend-driven preview data**. Tujuannya adalah memastikan setiap file JavaScript memiliki batas tanggung jawab yang jelas, sehingga script yang terlihat di browser hanya berperan sebagai UI bridge, event handler, renderer ringan, atau pemanggil endpoint internal.

## Prinsip yang Dikunci

1. **Core JS** hanya boleh menjadi infrastruktur umum lintas halaman.
2. **Landing Page JS** hanya boleh mengatur UI, event, AJAX call, dan render ringan.
3. **Backend** tetap menjadi pusat validasi, agregasi, pemilihan data, chart payload, partial rendering, dan keputusan akses.
4. JavaScript yang berjalan di browser memang akan terlihat di Network/DevTools, sehingga isinya tidak boleh dianggap rahasia.
5. Data internal, query, otorisasi, validasi final, rumus penting, bobot, ranking, dan payload lengkap tidak boleh ditempatkan di JavaScript frontend.

## Matriks File dan Tanggung Jawab

| File | Kategori | Peran yang Diizinkan | Baris | Pola Sensitif | Pola Data/Preview |
|---|---|---|---:|---:|---:|
| `public\assets\js\core\umkm-ui.js` | Core JS | Infrastruktur umum lintas halaman; tidak boleh menjadi tempat data internal atau keputusan final. | 100 | 0 | 0 |
| `public\assets\js\core\umkm-ajax.js` | Core JS | Infrastruktur umum lintas halaman; tidak boleh menjadi tempat data internal atau keputusan final. | 175 | 9 | 0 |
| `public\assets\js\core\umkm-security.js` | Core JS | Infrastruktur umum lintas halaman; tidak boleh menjadi tempat data internal atau keputusan final. | 145 | 6 | 0 |
| `public\assets\js\core\umkm-modal.js` | Core JS | Infrastruktur umum lintas halaman; tidak boleh menjadi tempat data internal atau keputusan final. | 290 | 0 | 0 |
| `public\assets\js\core\umkm-confirm.js` | Core JS | Infrastruktur umum lintas halaman; tidak boleh menjadi tempat data internal atau keputusan final. | 4 | 0 | 0 |
| `public\assets\js\core\umkm-toast.js` | Core JS | Infrastruktur umum lintas halaman; tidak boleh menjadi tempat data internal atau keputusan final. | 4 | 0 | 0 |
| `public\assets\js\core\umkm-loader.js` | Core JS | Infrastruktur umum lintas halaman; tidak boleh menjadi tempat data internal atau keputusan final. | 527 | 0 | 0 |
| `public\assets\js\core\umkm-component-loader.js` | Core JS | Infrastruktur umum lintas halaman; tidak boleh menjadi tempat data internal atau keputusan final. | 374 | 0 | 0 |
| `public\assets\js\core\umkm-readiness.js` | Core JS | Infrastruktur umum lintas halaman; tidak boleh menjadi tempat data internal atau keputusan final. | 760 | 1 | 0 |
| `public\assets\js\core\umkm-location.js` | Core JS | Infrastruktur umum lintas halaman; tidak boleh menjadi tempat data internal atau keputusan final. | 245 | 7 | 0 |
| `public\assets\js\core\umkm-location-gate.js` | Core JS | Infrastruktur umum lintas halaman; tidak boleh menjadi tempat data internal atau keputusan final. | 926 | 5 | 0 |
| `public\assets\js\pages\public\landing.js` | Landing Page JS | Legacy bridge/no-op; bukan pusat logic landing. | 7 | 0 | 0 |
| `public\assets\js\pages\public\landing\landing-state.js` | Landing Page JS | Selector, state minimal, endpoint path, helper umum. | 165 | 0 | 0 |
| `public\assets\js\pages\public\landing\landing-navigation.js` | Landing Page JS | Navigasi, reveal, parallax, counter, dan tilt UI. | 200 | 2 | 0 |
| `public\assets\js\pages\public\landing\landing-chart.js` | Landing Page JS | Renderer Chart.js dari response backend public-safe. | 275 | 0 | 6 |
| `public\assets\js\pages\public\landing\landing-region.js` | Landing Page JS | Request preview data, render UI ringan, dan event modal wilayah. | 765 | 9 | 3 |
| `public\assets\js\pages\public\landing\landing-location-bridge.js` | Landing Page JS | Inisialisasi location gate public. | 25 | 0 | 0 |
| `public\assets\js\pages\public\landing\landing-components.js` | Landing Page JS | Re-init setelah AJAX component dimuat. | 61 | 0 | 0 |
| `public\assets\js\pages\public\landing\landing-boot.js` | Landing Page JS | Urutan boot halaman. | 22 | 0 | 1 |
| `app\Http\Controllers\Api\Public\LandingComponentController.php` | Backend | Render Blade partial menjadi HTML fragment. | 54 | 1 | 0 |
| `app\Http\Controllers\Api\Public\LandingPreviewController.php` | Backend | Validasi parameter, agregasi public-safe, dan pembuatan chart payload. | 307 | 7 | 6 |
| `app\Http\Controllers\Api\Public\LandingRegionController.php` | Backend | Konteks wilayah publik dan daftar children region. | 296 | 1 | 0 |
| `app\Http\Controllers\Api\Public\LocationGateController.php` | Backend | Verifikasi final location gate di backend. | 164 | 3 | 0 |
## Interpretasi Audit

### Core JS

Core JS tetap dimuat di browser karena menjadi pondasi UI dan request internal. File seperti `umkm-ajax.js`, `umkm-component-loader.js`, `umkm-readiness.js`, dan `umkm-location-gate.js` boleh mengatur mekanisme request, loader, modal, feedback, dan status frontend. Namun, core tidak boleh memuat data internal UMKM, query database, role/permission final, rumus indikator, atau keputusan keamanan final.

### Landing Page JS

Landing Page JS sekarang sudah dipisah ke beberapa modul kecil. Modul `landing-state.js` hanya menyimpan selector, endpoint path, dan state minimal. Modul `landing-chart.js` hanya menggambar chart dari response backend. Modul `landing-region.js` hanya meminta preview data dan merender UI ringan. Modul `landing-components.js` hanya melakukan re-init setelah fragment AJAX masuk ke DOM. Modul `landing-boot.js` hanya mengatur urutan inisialisasi.

### Backend

Backend sekarang memegang tanggung jawab yang lebih tepat. `LandingPreviewController` menangani validasi parameter, pembuatan preview public-safe, dan chart payload. `LandingComponentController` merender Blade partial menjadi HTML fragment. `LandingRegionController` melayani konteks wilayah. `LocationGateController` tetap menjadi pengunci final location gate.

## Status Setelah Refactor

1. `landing.blade.php` sudah menjadi shell + placeholder untuk beberapa komponen.
2. Section hero preview board, dashboard preview, summary section, dan CTA sudah dimuat melalui AJAX component.
3. `landing.js` lama tidak lagi menjadi pusat logic.
4. Landing JS sudah dipisah menjadi modul kecil.
5. `DEFAULT_CHART_MODES` sudah tidak berada di frontend sebagai sumber data utama.
6. `buildPreview` frontend sudah tidak menjadi pusat perhitungan preview.
7. Preview dan chart payload sudah dipindahkan ke backend melalui `LandingPreviewController`.
8. Endpoint preview tetap memakai middleware internal, origin, referer, fetch metadata, throttle, dan audit.

## Batasan yang Tetap Harus Dijaga

1. Jangan menaruh data internal UMKM pada JS.
2. Jangan menaruh rumus, bobot, ranking, skor, atau ambang indikator pada JS.
3. Jangan membuat tombol login statis di DOM.
4. Jangan melewati location gate.
5. Jangan memakai fetch langsung pada page JS.
6. Jangan menambahkan inline script di Blade atau fragment AJAX.
7. Jangan membuka endpoint publik tanpa middleware internal.

## Rekomendasi Lanjutan

Tahap berikutnya dapat masuk ke **Landing Fragment Renderer 4** jika ingin mengurangi kerja render frontend lebih jauh. Pada tahap itu, backend dapat mengirim HTML fragment kecil untuk metric, area stats, indicator list, dan chart summary. Frontend cukup menerima response dan mengganti isi container. Untuk chart interaktif berbasis Chart.js, data chart tetap akan terlihat sebagai response public-safe karena browser membutuhkan data untuk menggambar chart.

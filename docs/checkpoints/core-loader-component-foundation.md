# Checkpoint Core Loader dan Component Loader

## Status

Checkpoint ini mengunci posisi kerja setelah penyelesaian fondasi Core Loader, Readiness Loader, dan AJAX Component Loader pada Sistem Monitoring UMKM.

Posisi ini dibuat sebelum masuk ke batch Login-1 agar pekerjaan login tidak bercampur dengan penyelesaian fondasi loader, asset dependency, dan prinsip pemuatan komponen berbasis AJAX.

## Commit Terkait

Urutan commit yang menjadi dasar checkpoint ini:

1. `abae147` — Refine core readiness loader UI
2. `fcb38f5` — Normalize readiness loader file endings
3. `4a154ad` — Add core AJAX component loader foundation
4. `2e6ace4` — Harden recursive core asset dependencies
5. `167a22d` — Connect readiness loader with AJAX component awareness

## Keputusan Struktur yang Dikunci

### 1. Server-Side Authority

Backend menjadi pusat keputusan untuk akses, otorisasi, validasi, filtering, masking, audit, perhitungan, dan response data. Frontend tidak boleh menjadi tempat keputusan final untuk data internal, data sensitif, logic keamanan, rumus indikator, atau otorisasi.

### 2. AJAX-Based Component Loading

Komponen halaman yang berisi data, modal, grafik, tabel, peta, atau panel dinamis dapat dimuat melalui AJAX/internal API. Pemuatan komponen harus melalui core request internal, bukan fetch bebas atau script inline.

### 3. Minimal DOM Exposure

DOM awal halaman cukup berisi shell, placeholder, skeleton, loader, dan metadata minimal non-sensitif. Data internal, data lengkap UMKM, koordinat presisi, logic penting, payload penuh, atau detail keamanan tidak boleh ditanam langsung ke Blade/DOM awal.

## Readiness Loader

Readiness Loader telah diperbaiki menjadi lebih compact dan informatif. Progress tetap berada di tengah, status aktif ditampilkan ringkas, detail readiness masuk ke area detail/collapse, dan tampilan tidak lagi memanjang secara vertikal.

Readiness Loader tidak menggantikan keamanan backend. Fungsinya adalah lapisan UX untuk menunjukkan proses kesiapan shell halaman, modul core, location/access gate, dan kesiapan placeholder komponen.

## Component Loader

Core AJAX Component Loader telah ditambahkan melalui:

`public/assets/js/core/umkm-component-loader.js`

Fungsinya:

1. membaca placeholder `data-umkm-component`;
2. memuat komponen melalui `UMKM.ajax`;
3. membatasi endpoint ke same-origin;
4. menghapus elemen berbahaya dari fragment HTML seperti `script`, `noscript`, `iframe`, `object`, dan `embed`;
5. memberikan status component: pending, loading, loaded, failed, skipped;
6. mendukung pemuatan setelah readiness loader selesai.

## Asset Loader

`resources/views/partials/asset-loader.blade.php` telah diperkuat agar dependency modul diproses secara rekursif. Hal ini menjaga agar dependency berlapis seperti `readiness -> componentLoader -> ajax + loader` tetap dimuat dengan urutan aman.

## Batasan yang Tetap Dijaga

Checkpoint ini tidak mengubah:

1. login;
2. route;
3. controller;
4. middleware;
5. database;
6. migration;
7. seeder;
8. vendor/plugin;
9. endpoint data baru;
10. struktur keamanan backend.

## Smoke Test yang Sudah Clean

Pengujian yang sudah dinyatakan clean:

1. Readiness Loader tampil normal.
2. Progress ring dan status aktif berjalan.
3. Detail readiness tidak memanjang secara default.
4. Component Loader ready.
5. Asset dependency berjalan.
6. Landing tetap tampil normal.
7. Tombol login tetap tunduk pada location gate.
8. Console tidak menampilkan error merah.

## Posisi Berikutnya

Setelah checkpoint ini, pekerjaan dapat diarahkan ke Login-1 dengan tetap memegang prinsip:

1. login tidak boleh melewati location gate;
2. tombol login tidak boleh statis di DOM sebelum akses lokasi valid;
3. backend tetap menjadi pengunci final;
4. semua request sensitif melewati guard internal;
5. DOM login tidak boleh memuat data internal atau logic penting secara langsung;
6. script tetap modular melalui core asset loader;
7. tidak ada inline script/style pada Blade.

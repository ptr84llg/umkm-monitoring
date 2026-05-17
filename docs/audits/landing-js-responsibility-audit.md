# Landing DOM Exposure Audit 1

## Status Audit

Audit ini dibuat setelah fondasi Readiness Loader dan AJAX Component Loader selesai. Tujuannya adalah menentukan bagian landing.blade.php yang masih terlalu penuh di DOM awal dan harus dipindahkan secara bertahap ke pola AJAX component loading.

## Ringkasan Temuan Otomatis

| Item | Hasil |
|---|---:|
| Jumlah baris landing.blade.php | 679 |
| Jumlah SVG inline | 29 |
| Jumlah atribut data-* | 67 |
| Jumlah komponen modal Blade | 2 |
| Jumlah metric DOM publik | 3 |
| Jumlah elemen chart/dashboard preview | 8 |
| Jumlah elemen region/public preview | 7 |
| Jumlah login mount | 4 |
| Jumlah placeholder data-umkm-component | 0 |
| Jumlah baris landing.js | 1627 |
| Jumlah blok DEFAULT_CHART_MODES | 4 |
| Jumlah logic preview/chart/region pada JS | 13 |

## Kesimpulan Audit

landing.blade.php masih belum mengikuti Minimal DOM Exposure secara penuh. File tersebut masih memuat struktur besar untuk header, offcanvas, hero, preview board, metrics, area data wilayah, indikator, dashboard preview, CTA, dan modal. Sebagian konten masih layak tetap berada di DOM awal karena bersifat public shell, tetapi bagian preview data, grafik, ringkasan wilayah, indikator, modal berat, dan area yang nanti mengandung data backend harus dipindahkan bertahap ke AJAX component.

## Klasifikasi Bagian Landing

### Tetap Boleh Berada di DOM Awal

1. Shell .umkm-landing.
2. Background gradient dekoratif.
3. Header public.
4. Brand dan menu dasar.
5. Login mount kosong, selama tombol login tetap dirender oleh location gate.
6. Hero copy statis yang tidak mengandung data internal.
7. Container dan placeholder komponen.
8. Skeleton atau loader ringan.

### Harus Dipindah Bertahap ke AJAX Component

1. Hero preview board.
2. Metric preview UMKM.
3. Data wilayah preview.
4. Indikator preview.
5. Empty state preview yang berkaitan dengan data.
6. Dashboard chart area.
7. Chart summary.
8. Panel ringkasan dinamis.
9. Region-driven preview content.
10. Modal/area yang berisi struktur panjang dan tidak harus ada sejak initial DOM.

### Perlu Diproses Backend/Internal API pada Batch Berikutnya

1. Angka agregat UMKM.
2. UMKM aktif.
3. Perlu validasi.
4. Bidang dominan.
5. Wilayah terpantau.
6. Data chart.
7. Data area/wilayah.
8. Indikator bidang usaha.
9. Status data tersedia/tidak tersedia.

## Masalah Saat Ini

1. AJAX Component Loader sudah ada, tetapi belum diterapkan pada landing content.
2. Readiness Loader sudah aware terhadap data-umkm-component, tetapi landing belum banyak memakai placeholder tersebut.
3. landing.js masih menyimpan data preview default dan logic visual preview di frontend.
4. Beberapa angka dan payload simulasi masih berada di DOM/JS public.
5. DOM awal masih terlalu panjang untuk arah Minimal DOM Exposure.

## Urutan Refactor yang Dikunci

### Batch Berikutnya: Landing Component Refactor 1

Target:
1. membuat endpoint internal public component untuk hero preview board;
2. memindahkan struktur hero preview board dari DOM awal ke partial fragment;
3. mengganti bagian tersebut di landing.blade.php menjadi placeholder data-umkm-component;
4. memastikan component dimuat melalui UMKM.ajax;
5. memastikan fragment tidak membawa script inline;
6. memastikan location gate dan login mount tidak terganggu.

### Batch Setelahnya: Landing Preview Data Refactor 2

Target:
1. memindahkan angka preview dari DOM/JS statis ke backend response minimal;
2. mengurangi DEFAULT_CHART_MODES di frontend secara bertahap;
3. memastikan data chart publik adalah agregat dan tidak sensitif;
4. tetap tidak membuka data internal UMKM.

### Batch Setelahnya: Landing Modal/Region Refactor 3

Target:
1. audit modal region selector;
2. pisahkan shell modal dan body dinamis;
3. region context tetap melalui internal API;
4. modal tidak membawa payload besar di DOM awal.

## Batasan Keamanan

Refactor landing tidak boleh:
1. menghapus location gate;
2. membuat tombol login statis di DOM;
3. menaruh data internal UMKM di Blade;
4. menaruh rumus/ambang indikator di JavaScript;
5. membuat endpoint bebas tanpa guard;
6. memakai fetch langsung di page JS;
7. menambahkan script inline pada fragment AJAX.

## Keputusan

Sebelum masuk Login-1, landing harus masuk tahap refactor DOM exposure. Fondasi AJAX yang sudah dibuat harus diterapkan dulu pada landing/public shell agar tujuan Minimal DOM Exposure benar-benar berjalan, bukan hanya tersedia sebagai core yang belum dipakai.

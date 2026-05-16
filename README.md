# UMKM Monitoring

Fondasi awal proyek Laravel 13 untuk sistem monitoring UMKM berbasis data dan visual analitik interaktif.

## Gambaran Singkat

Project ini dikembangkan untuk mendukung penelitian berjudul:

**Model Sistem Informasi Berbasis Data dengan Visual Analitik Interaktif untuk Monitoring Kinerja dan Pengambilan Keputusan UMKM**

Sistem dirancang untuk mengelola data UMKM, mendukung pemantauan kinerja, menyediakan dashboard visual analitik interaktif, serta memfasilitasi proses survei pengguna dan validasi ahli.

## Catatan Keamanan Repository

Repository ini bersifat public untuk kebutuhan pemeriksaan struktur kerja dan pendampingan pengembangan. Oleh karena itu, beberapa bagian tidak dipublikasikan, antara lain:

- file `.env`;
- folder `vendor`;
- folder `node_modules`;
- folder `database`;
- file cache, log, dan konfigurasi lokal.

Struktur database, migration, seeder, dan konfigurasi sensitif tetap dikelola secara lokal.

## Teknologi Awal

- Laravel 13
- PHP 8.3
- Bootstrap
- Vite
- Composer
- NPM
- Visual Analytics berbasis komponen interaktif

## Status Pengembangan

Tahap saat ini adalah fondasi awal project. Fokus pemeriksaan berikutnya meliputi:

1. struktur folder dan modul;
2. route dan controller;
3. layout public, auth, dan dashboard;
4. pola UI core;
5. keamanan akses dan role;
6. pola request internal;
7. modul data UMKM;
8. modul survei pengguna;
9. modul validasi ahli;
10. dashboard visual analitik.

## Catatan

Folder `database` tidak dipublikasikan pada repository ini untuk menjaga kerahasiaan struktur basis data selama tahap awal pengembangan.
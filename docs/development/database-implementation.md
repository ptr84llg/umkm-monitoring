# Implementasi Database Inti Tahap 2

Tahap ini menyiapkan migration dan model inti untuk kontrol akses, data inti UMKM, periode monitoring, usulan pembaruan, audit, log API internal, keamanan, ekspor, akses file, dan akses sensitif.

## Prinsip
- Pemisahan data resmi dan data usulan melalui `umkms` dan `umkm_update_submissions`.
- Data penting memakai foreign key, timestamps, dan soft delete saat relevan.
- Status data menggunakan enum operasional untuk alur validasi.

@php
    use Illuminate\Support\Facades\Route;

    $assetProfile = $assetProfile ?? 'full';

    $assetModules = array_values(array_unique(array_merge($assetModules ?? [], [
        'session',
        'internalLayout',
    ])));

    $dashboardUser = auth()->user();
    $dashboardUserName = trim((string) ($dashboardUser?->name ?? 'Pengguna'));
    $dashboardUserEmail = $dashboardUser?->email;
    $dashboardUserInitial = strtoupper(substr($dashboardUserName !== '' ? $dashboardUserName : 'U', 0, 1));
    $dashboardCurrentRoute = Route::currentRouteName();
    $dashboardHomeUrl = url('/');
    $dashboardRoleKey = 'user';
    $dashboardRoleLabel = 'Pengguna';
    $dashboardRoleHint = 'Ruang kerja internal';

    $dashboardServerNow = now();
    $dashboardServerTimezone = (string) config('app.timezone', 'Asia/Jakarta');
    $dashboardServerEpochMs = $dashboardServerNow->getTimestamp() * 1000;
    $dashboardServerOffset = $dashboardServerNow->format('P');
    $dashboardServerDateLabel = $dashboardServerNow->translatedFormat('l, d F Y');
    $dashboardServerTimeLabel = $dashboardServerNow->format('H:i:s');

    if ($dashboardUser?->hasRole('admin_utama')) {
        $dashboardRoleKey = 'admin_utama';
        $dashboardRoleLabel = 'Admin Utama';
        $dashboardRoleHint = 'Kendali sistem dan tata kelola';
    } elseif ($dashboardUser?->hasRole('admin_dinas')) {
        $dashboardRoleKey = 'admin_dinas';
        $dashboardRoleLabel = 'Admin Dinas';
        $dashboardRoleHint = 'Pembinaan dan validasi wilayah';
    } elseif ($dashboardUser?->hasRole('kepala_dinas')) {
        $dashboardRoleKey = 'kepala_dinas';
        $dashboardRoleLabel = 'Kepala Dinas';
        $dashboardRoleHint = 'Monitoring eksekutif';
    } elseif ($dashboardUser?->hasRole('pelaku_umkm')) {
        $dashboardRoleKey = 'pelaku_umkm';
        $dashboardRoleLabel = 'Pelaku UMKM';
        $dashboardRoleHint = 'Data usaha dan pelaporan';
    } elseif ($dashboardUser?->hasRole('validator_ahli')) {
        $dashboardRoleKey = 'validator_ahli';
        $dashboardRoleLabel = 'Validator Ahli';
        $dashboardRoleHint = 'Validasi instrumen dan artefak';
    }

    $dashboardHomeCandidates = [
        'admin_utama' => 'admin-utama.dashboard',
        'admin_dinas' => 'admin-dinas.dashboard',
        'kepala_dinas' => 'kepala-dinas.dashboard',
        'pelaku_umkm' => 'pelaku-umkm.dashboard',
        'validator_ahli' => 'expert.validator.list',
    ];

    $dashboardHomeRoute = $dashboardHomeCandidates[$dashboardRoleKey] ?? null;

    if (is_string($dashboardHomeRoute) && Route::has($dashboardHomeRoute)) {
        $dashboardHomeUrl = route($dashboardHomeRoute);
    }

    $internalMenuBlueprint = [
        'admin_utama' => [
            [
                'label' => 'Utama',
                'summary' => 'Akses awal ruang kendali sistem.',
                'items' => [
                    [
                        'title' => 'Dashboard',
                        'description' => 'Kendali sistem',
                        'detail' => 'Ringkasan sistem, keamanan, konfigurasi, kualitas data, pengguna, dan kesiapan modul.',
                        'route' => 'admin-utama.dashboard',
                        'permission' => 'dashboard.view.executive',
                        'icon' => 'dashboard',
                    ],
                ],
            ],
            [
                'label' => 'Tata Kelola',
                'summary' => 'Modul pengelolaan inti untuk Admin Utama.',
                'items' => [
                    [
                        'title' => 'Akses',
                        'description' => 'Akun, role, permission',
                        'detail' => 'Pengelolaan akun internal, role, permission, sesi/perangkat, dan audit akses berbasis kewenangan.',
                        'route' => 'admin-utama.access.index',
                        'permission' => 'access.manage',
                        'icon' => 'shield',
                    ],
                    [
                        'title' => 'Referensi',
                        'description' => 'Wilayah, KBLI, master data',
                        'detail' => 'Pengaturan referensi wilayah, KBLI, dan data dasar pendukung sistem monitoring UMKM.',
                        'route' => null,
                        'permission' => 'reference.manage',
                        'icon' => 'database',
                    ],
                    [
                        'title' => 'Governance',
                        'description' => 'Setting, theme, keamanan, audit',
                        'detail' => 'Pengaturan sistem, theme, keamanan, audit log, dan tata kelola operasional sistem.',
                        'route' => 'admin-utama.governance.settings',
                        'permission' => 'system.manage',
                        'icon' => 'settings',
                    ],
                    [
                        'title' => 'Publikasi',
                        'description' => 'Pengumuman dan konten',
                        'detail' => 'Pengelolaan informasi publik, pengumuman, dan narasi sistem yang telah disanitasi.',
                        'route' => null,
                        'permission' => 'content.manage',
                        'icon' => 'megaphone',
                    ],
                    [
                        'title' => 'Validasi',
                        'description' => 'Survei dan ahli',
                        'detail' => 'Pengaturan instrumen survei, validasi ahli, dan status penilaian terkontrol.',
                        'route' => null,
                        'permission' => 'validation.manage',
                        'icon' => 'check',
                    ],
                ],
            ],
        ],
        'admin_dinas' => [
            [
                'label' => 'Operasional',
                'summary' => 'Pembinaan, validasi, dan monitoring wilayah.',
                'items' => [
                    [
                        'title' => 'Dashboard',
                        'description' => 'Ringkasan pembinaan',
                        'detail' => 'Ikhtisar pembinaan, validasi, dan perkembangan data UMKM pada wilayah kerja.',
                        'route' => 'admin-dinas.dashboard',
                        'permission' => null,
                        'icon' => 'dashboard',
                    ],
                    [
                        'title' => 'Data UMKM',
                        'description' => 'Validasi dan pembinaan',
                        'detail' => 'Pemeriksaan data usaha, status legalitas, dan usulan perubahan data dari pelaku UMKM.',
                        'route' => null,
                        'permission' => 'umkm.verify',
                        'icon' => 'store',
                    ],
                    [
                        'title' => 'Analitik',
                        'description' => 'Kinerja dan wilayah',
                        'detail' => 'Analisis data wilayah, bidang usaha, dan indikator perkembangan UMKM.',
                        'route' => null,
                        'permission' => 'dashboard.view.operational',
                        'icon' => 'chart',
                    ],
                ],
            ],
        ],
        'kepala_dinas' => [
            [
                'label' => 'Eksekutif',
                'summary' => 'Monitoring keputusan dan laporan strategis.',
                'items' => [
                    [
                        'title' => 'Dashboard',
                        'description' => 'Monitoring keputusan',
                        'detail' => 'Ringkasan eksekutif untuk pemantauan kinerja, wilayah, dan pengambilan keputusan.',
                        'route' => 'kepala-dinas.dashboard',
                        'permission' => null,
                        'icon' => 'dashboard',
                    ],
                    [
                        'title' => 'Laporan',
                        'description' => 'Ringkasan strategis',
                        'detail' => 'Laporan ringkas untuk evaluasi program dan kebutuhan pengambilan keputusan pimpinan.',
                        'route' => null,
                        'permission' => 'report.view.executive',
                        'icon' => 'document',
                    ],
                ],
            ],
        ],
        'pelaku_umkm' => [
            [
                'label' => 'Usaha Saya',
                'summary' => 'Pengelolaan data usaha dan pelaporan.',
                'items' => [
                    [
                        'title' => 'Dashboard',
                        'description' => 'Ringkasan usaha',
                        'detail' => 'Ikhtisar profil usaha, status data, dan informasi perkembangan yang relevan.',
                        'route' => 'pelaku-umkm.dashboard',
                        'permission' => null,
                        'icon' => 'dashboard',
                    ],
                    [
                        'title' => 'Profil Usaha',
                        'description' => 'Identitas dan lokasi',
                        'detail' => 'Pengelolaan identitas usaha, legalitas, lokasi, dan data yang dapat diajukan untuk validasi.',
                        'route' => null,
                        'permission' => 'umkm.profile.manage',
                        'icon' => 'store',
                    ],
                    [
                        'title' => 'Pelaporan',
                        'description' => 'Kinerja dan transaksi',
                        'detail' => 'Pelaporan data perkembangan usaha secara terkontrol sesuai kewenangan pelaku UMKM.',
                        'route' => null,
                        'permission' => 'umkm.report.submit',
                        'icon' => 'document',
                    ],
                ],
            ],
        ],
        'validator_ahli' => [
            [
                'label' => 'Validasi Ahli',
                'summary' => 'Penilaian instrumen dan artefak validasi.',
                'items' => [
                    [
                        'title' => 'Instrumen',
                        'description' => 'Daftar validasi',
                        'detail' => 'Daftar instrumen penilaian yang diberikan kepada validator ahli.',
                        'route' => 'expert.validator.list',
                        'permission' => 'validation.expert.fill',
                        'icon' => 'check',
                    ],
                    [
                        'title' => 'Riwayat',
                        'description' => 'Penilaian tersimpan',
                        'detail' => 'Riwayat hasil penilaian yang sudah tersimpan dan dikunci sesuai status submit.',
                        'route' => null,
                        'permission' => 'validation.expert.fill',
                        'icon' => 'document',
                    ],
                ],
            ],
        ],
    ];

    $dashboardMenuSections = $internalMenuBlueprint[$dashboardRoleKey] ?? [
        [
            'label' => 'Ruang Kerja',
            'summary' => 'Menu internal belum dikonfigurasi.',
            'items' => [
                [
                    'title' => 'Dashboard',
                    'description' => 'Menu belum dikonfigurasi',
                    'detail' => 'Menu dashboard akan mengikuti role pengguna setelah konfigurasi modul tersedia.',
                    'route' => $dashboardHomeRoute,
                    'permission' => null,
                    'icon' => 'dashboard',
                ],
            ],
        ],
    ];

    $dashboardMenuItems = collect($dashboardMenuSections)->flatMap(fn ($section) => $section['items'] ?? [])->values();
    $dashboardFeaturedItem = $dashboardMenuItems->first(fn ($item) => ! empty($item['route']) && is_string($item['route']) && Route::has($item['route'])) ?? $dashboardMenuItems->first();

    $dashboardSubmenuFor = function (array $menuItem) use ($dashboardRoleKey): array {
        $title = mb_strtolower((string) ($menuItem['title'] ?? ''));

        $submenuMap = [
            'dashboard' => [
                ['title' => 'Ringkasan Kendali', 'description' => 'Ikhtisar kondisi utama sesuai ruang kerja pengguna.', 'state' => 'Aktif'],
                ['title' => 'Status Kesiapan', 'description' => 'Informasi ringkas kesiapan data, modul, dan aktivitas penting.', 'state' => 'Ringkasan'],
                ['title' => 'Arah Tindak Lanjut', 'description' => 'Petunjuk awal untuk masuk ke modul pengelolaan berikutnya.', 'state' => 'Navigasi'],
            ],
            'akses' => [
                ['title' => 'Akun Pengguna', 'description' => 'Identitas akun, status akses, dan koneksi login pengguna.', 'state' => 'Read-only'],
                ['title' => 'Peran', 'description' => 'Kelompok kewenangan yang membedakan cakupan kerja pengguna.', 'state' => 'Matriks'],
                ['title' => 'Izin Akses', 'description' => 'Izin tindakan per modul yang menjadi dasar pembatasan akses.', 'state' => 'PBAC'],
                ['title' => 'Penetapan Akses', 'description' => 'Relasi akun, role, dan permission untuk pengaturan akses bertingkat.', 'state' => 'Terjadwal'],
                ['title' => 'Sesi & Perangkat', 'description' => 'Pemantauan akses perangkat dan sesi login pengguna.', 'state' => 'Terjadwal'],
                ['title' => 'Audit Akses', 'description' => 'Jejak aktivitas penting yang terkait dengan akses pengguna.', 'state' => 'Pratinjau'],
            ],
            'referensi' => [
                ['title' => 'Wilayah', 'description' => 'Provinsi, kabupaten/kota, kecamatan, dan kelurahan/desa.', 'state' => 'Terjadwal'],
                ['title' => 'KBLI', 'description' => 'Klasifikasi bidang usaha untuk pengelompokan UMKM.', 'state' => 'Terjadwal'],
                ['title' => 'Kategori Usaha', 'description' => 'Referensi pendukung untuk segmentasi dan pelaporan.', 'state' => 'Terjadwal'],
            ],
            'governance' => [
                ['title' => 'Pengaturan Sistem', 'description' => 'Konfigurasi umum dan kebijakan operasional sistem.', 'state' => 'Aktif'],
                ['title' => 'Tema Sistem', 'description' => 'Pemilihan tampilan visual yang berlaku pada ruang kerja internal.', 'state' => 'Aktif'],
                ['title' => 'Keamanan', 'description' => 'Pengaturan pembatasan akses dan kesiapan guard.', 'state' => 'Terjaga'],
                ['title' => 'Audit Tata Kelola', 'description' => 'Jejak perubahan konfigurasi penting.', 'state' => 'Terkontrol'],
            ],
            'publikasi' => [
                ['title' => 'Pengumuman', 'description' => 'Informasi resmi yang dapat ditampilkan kepada publik.', 'state' => 'Terjadwal'],
                ['title' => 'Narasi Sistem', 'description' => 'Konten penjelasan sistem yang aman untuk pengguna umum.', 'state' => 'Terjadwal'],
                ['title' => 'Konten Terpublikasi', 'description' => 'Materi yang sudah melalui sanitasi dan persetujuan.', 'state' => 'Terjadwal'],
            ],
            'validasi' => [
                ['title' => 'Instrumen Survei', 'description' => 'Daftar instrumen pengumpulan penilaian dan masukan.', 'state' => 'Terjadwal'],
                ['title' => 'Validator Ahli', 'description' => 'Akses validasi untuk ahli sistem informasi, visualisasi, dan keamanan.', 'state' => 'Terjadwal'],
                ['title' => 'Hasil Validasi', 'description' => 'Ringkasan hasil penilaian yang sudah dikirim.', 'state' => 'Terjadwal'],
            ],
            'data umkm' => [
                ['title' => 'Profil UMKM', 'description' => 'Identitas usaha, legalitas, kategori, dan status data.', 'state' => 'Terjadwal'],
                ['title' => 'Validasi Data', 'description' => 'Pemeriksaan usulan dan perubahan data UMKM.', 'state' => 'Terjadwal'],
                ['title' => 'Pembinaan', 'description' => 'Catatan pendampingan dan tindak lanjut wilayah.', 'state' => 'Terjadwal'],
            ],
            'analitik' => [
                ['title' => 'Grafik Kinerja', 'description' => 'Visualisasi perkembangan usaha dan indikator utama.', 'state' => 'Terjadwal'],
                ['title' => 'Peta Sebaran', 'description' => 'Distribusi lokasi UMKM berbasis wilayah.', 'state' => 'Terjadwal'],
                ['title' => 'Filter Wilayah', 'description' => 'Penyaringan data berdasarkan cakupan kewenangan.', 'state' => 'Terjadwal'],
            ],
            'laporan' => [
                ['title' => 'Ringkasan Eksekutif', 'description' => 'Laporan singkat untuk pimpinan dan evaluasi program.', 'state' => 'Terjadwal'],
                ['title' => 'Tren Wilayah', 'description' => 'Perkembangan data berdasarkan wilayah dan kategori.', 'state' => 'Terjadwal'],
            ],
            'profil usaha' => [
                ['title' => 'Identitas Usaha', 'description' => 'Data dasar usaha yang dapat diajukan untuk validasi.', 'state' => 'Terjadwal'],
                ['title' => 'Lokasi Usaha', 'description' => 'Wilayah dan titik lokasi sesuai ketentuan validasi.', 'state' => 'Terjadwal'],
                ['title' => 'Legalitas', 'description' => 'Nomor dan dokumen pendukung usaha.', 'state' => 'Terjadwal'],
            ],
            'pelaporan' => [
                ['title' => 'Kinerja Usaha', 'description' => 'Pelaporan perkembangan usaha secara berkala.', 'state' => 'Terjadwal'],
                ['title' => 'Transaksi', 'description' => 'Informasi transaksi sesuai format yang disediakan.', 'state' => 'Terjadwal'],
            ],
            'instrumen' => [
                ['title' => 'Daftar Instrumen', 'description' => 'Instrumen penilaian yang ditugaskan kepada validator.', 'state' => 'Aktif'],
                ['title' => 'Form Penilaian', 'description' => 'Pengisian penilaian sesuai bidang keahlian.', 'state' => 'Terkontrol'],
            ],
            'riwayat' => [
                ['title' => 'Penilaian Tersimpan', 'description' => 'Riwayat hasil validasi yang sudah dikirim.', 'state' => 'Tersimpan'],
                ['title' => 'Status Submit', 'description' => 'Informasi penguncian hasil penilaian.', 'state' => 'Terkontrol'],
            ],
        ];

        return $submenuMap[$title] ?? [
            ['title' => 'Ringkasan Menu', 'description' => (string) ($menuItem['detail'] ?? $menuItem['description'] ?? 'Cakupan menu mengikuti kewenangan pengguna.'), 'state' => 'Info'],
        ];
    };

    $dashboardRoleBadgeClass = [
        'admin_utama' => 'dashboard-role-admin-utama',
        'admin_dinas' => 'dashboard-role-admin-dinas',
        'kepala_dinas' => 'dashboard-role-kepala-dinas',
        'pelaku_umkm' => 'dashboard-role-pelaku-umkm',
        'validator_ahli' => 'dashboard-role-validator-ahli',
    ][$dashboardRoleKey] ?? 'dashboard-role-user';

    $dashboardIcon = function (string $icon): string {
        return match ($icon) {
            'shield' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2 5 5v6c0 5 3 9 7 11 4-2 7-6 7-11V5l-7-3Zm0 4 3 1.3V11c0 3-1.4 5.4-3 6.8-1.6-1.4-3-3.8-3-6.8V7.3L12 6Z"/></svg>',
            'database' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3C7 3 4 4.8 4 7v10c0 2.2 3 4 8 4s8-1.8 8-4V7c0-2.2-3-4-8-4Zm0 2c4 0 6 1.2 6 2s-2 2-6 2-6-1.2-6-2 2-2 6-2Zm0 14c-4 0-6-1.2-6-2v-2.2c1.4.9 3.5 1.2 6 1.2s4.6-.3 6-1.2V17c0 .8-2 2-6 2Zm0-5c-4 0-6-1.2-6-2V9.8c1.4.9 3.5 1.2 6 1.2s4.6-.3 6-1.2V12c0 .8-2 2-6 2Z"/></svg>',
            'settings' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19.4 13.5c.1-.5.1-1 .1-1.5s0-1-.1-1.5l2.1-1.6-2-3.5-2.5 1a8 8 0 0 0-2.6-1.5L14 2h-4l-.4 2.9A8 8 0 0 0 7 6.4l-2.5-1-2 3.5 2.1 1.6c-.1.5-.1 1-.1 1.5s0 1 .1 1.5l-2.1 1.6 2 3.5 2.5-1a8 8 0 0 0 2.6 1.5L10 22h4l.4-2.9a8 8 0 0 0 2.6-1.5l2.5 1 2-3.5-2.1-1.6ZM12 15.5A3.5 3.5 0 1 1 12 8a3.5 3.5 0 0 1 0 7.5Z"/></svg>',
            'megaphone' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 4v14h-2l-8-4H7v4H4v-4H3V8h8l8-4h2ZM7 10v2h4.5l5.5 2.8V7.2L11.5 10H7Z"/></svg>',
            'check' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9.5 16.2 5.8 12.5 4.4 13.9l5.1 5.1L20 8.5 18.6 7.1 9.5 16.2Z"/></svg>',
            'chart' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 19h16v2H2V3h2v16Zm3-2V9h3v8H7Zm5 0V5h3v12h-3Zm5 0v-6h3v6h-3Z"/></svg>',
            'store' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4h16l1 6v2h-1v8H4v-8H3v-2l1-6Zm2 10v4h12v-4H6Zm-.6-8-.6 4h14.4l-.6-4H5.4Z"/></svg>',
            'document' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 2h9l5 5v15H6V2Zm8 1.5V8h4.5L14 3.5ZM8 12h8v2H8v-2Zm0 4h8v2H8v-2Z"/></svg>',
            default => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 13h7V4H4v9Zm0 7h7v-5H4v5Zm9 0h7v-9h-7v9Zm0-16v5h7V4h-7Z"/></svg>',
        };
    };

    $dashboardMenuDisplay = function (array $menuItem): array {
        $originalTitle = (string) ($menuItem['title'] ?? 'Menu');
        $key = mb_strtolower($originalTitle);

        $titleMap = [
            'dashboard' => 'Dasbor',
            'akses' => 'Akses',
            'referensi' => 'Referensi',
            'governance' => 'Tata Kelola',
            'publikasi' => 'Publikasi',
            'validasi' => 'Validasi',
            'data umkm' => 'Data UMKM',
            'analitik' => 'Analitik',
            'laporan' => 'Laporan',
            'profil usaha' => 'Profil Usaha',
            'pelaporan' => 'Pelaporan',
            'instrumen' => 'Instrumen',
            'riwayat' => 'Riwayat',
        ];

        $descriptionMap = [
            'dashboard' => 'Ringkasan kendali, status, dan kesiapan ruang kerja.',
            'akses' => 'Akun, peran, izin akses, sesi, dan audit pengguna.',
            'referensi' => 'Wilayah, KBLI, kategori, dan data pendukung UMKM.',
            'governance' => 'Pengaturan sistem, tema, keamanan, dan tata kelola.',
            'publikasi' => 'Pengumuman, narasi publik, dan konten informasi.',
            'validasi' => 'Instrumen survei, validator ahli, dan hasil penilaian.',
            'data umkm' => 'Profil, legalitas, lokasi, dan status data UMKM.',
            'analitik' => 'Grafik, peta, indikator, dan ringkasan kinerja.',
            'laporan' => 'Laporan ringkas dan informasi evaluasi sesuai kewenangan.',
            'profil usaha' => 'Identitas, lokasi, legalitas, dan data usaha.',
            'pelaporan' => 'Pelaporan berkala perkembangan dan aktivitas usaha.',
            'instrumen' => 'Instrumen penilaian sesuai bidang validasi.',
            'riwayat' => 'Riwayat submit dan hasil penilaian yang tersimpan.',
        ];

        $detailMap = [
            'dashboard' => 'Menu dasbor membantu pengguna melihat ringkasan kondisi ruang kerja, status kesiapan modul, dan arah tindak lanjut sesuai kewenangan.',
            'akses' => 'Menu akses digunakan untuk membaca struktur akun, peran, izin akses, relasi kewenangan, sesi/perangkat, dan audit akses secara terkontrol.',
            'referensi' => 'Menu referensi memuat data dasar yang menjadi acuan klasifikasi, wilayah, kategori, dan pengelompokan informasi UMKM.',
            'governance' => 'Menu tata kelola digunakan untuk mengelola pengaturan sistem, tema tampilan, kebijakan keamanan, dan jejak perubahan konfigurasi penting.',
            'publikasi' => 'Menu publikasi digunakan untuk menyiapkan informasi, pengumuman, dan narasi sistem yang aman untuk ditampilkan kepada pengguna.',
            'validasi' => 'Menu validasi digunakan untuk mengelola instrumen, akses validator ahli, proses penilaian, dan ringkasan hasil validasi.',
            'data umkm' => 'Menu data UMKM digunakan untuk melihat dan mengelola cakupan informasi usaha sesuai kewenangan wilayah dan status validasi.',
            'analitik' => 'Menu analitik membantu membaca indikator, visualisasi, peta sebaran, dan ringkasan kinerja UMKM.',
            'laporan' => 'Menu laporan menyajikan ringkasan informasi untuk evaluasi, pemantauan, dan pengambilan keputusan sesuai peran pengguna.',
            'profil usaha' => 'Menu profil usaha digunakan oleh pelaku UMKM untuk melihat dan mengajukan pembaruan informasi usaha yang perlu divalidasi.',
            'pelaporan' => 'Menu pelaporan digunakan untuk menyampaikan perkembangan usaha sesuai format dan periode yang ditentukan.',
            'instrumen' => 'Menu instrumen digunakan validator untuk membaca dan mengisi penilaian sesuai bidang keahlian.',
            'riwayat' => 'Menu riwayat membantu validator melihat hasil penilaian dan status submit yang telah tersimpan.',
        ];

        $displayTitle = $titleMap[$key] ?? $originalTitle;
        $description = $descriptionMap[$key] ?? (string) ($menuItem['description'] ?? 'Cakupan menu mengikuti kewenangan pengguna.');
        $detail = $detailMap[$key] ?? (string) ($menuItem['detail'] ?? $description);

        return [
            'title' => $displayTitle,
            'description' => $description,
            'detail' => $detail,
        ];
    };

    $dashboardFeaturedDisplay = is_array($dashboardFeaturedItem ?? null)
        ? $dashboardMenuDisplay($dashboardFeaturedItem)
        : [
            'title' => 'Ruang Kerja',
            'description' => 'Pilih menu untuk melihat cakupan kerja.',
            'detail' => 'Pilih menu untuk melihat cakupan kerja dan informasi singkat.',
        ];
@endphp
<!doctype html>
<html lang="id" data-umkm-theme="{{ $activeTheme ?? 'green' }}">
<head>
    <x-umkm.seo-meta area="private" robots="noindex,nofollow,noarchive,nosnippet" :render-title="false" :render-description="false" />
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="umkm-client" content="dashboard">
    <meta name="umkm-security-profile" content="{{ $assetProfile }}">
    <title>@yield('title', 'Ruang Kerja | Monitoring UMKM')</title>
    @include('partials.asset-loader')
</head>
<body class="layout-dashboard"
      data-dashboard-shell
      data-umkm-session-guard
      data-umkm-session-lifetime-minutes="{{ (int) config('session.lifetime', 60) }}"
      data-umkm-session-warning-seconds="{{ (int) config('umkm.security.session_warning_seconds', 300) }}"
      data-umkm-session-redirect-url="{{ url('/') }}"
      data-umkm-session-keep-alive-url="{{ route('session.keep-alive') }}">
    <div class="dashboard-shell" data-dashboard-shell-frame data-mega-menu="closed" data-mobile-menu="closed">
        <header class="dashboard-topbar" data-dashboard-topbar>
            <div class="dashboard-topbar-inner">
                <div class="dashboard-topbar-start">
                    <a class="dashboard-brand" href="{{ $dashboardHomeUrl }}" aria-label="Ruang Kerja Monitoring UMKM">
                        <span class="dashboard-brand-mark">MU</span>
                        <span class="dashboard-brand-copy">
                            <strong>Ruang Kerja</strong>
                            <small>Monitoring UMKM</small>
                        </span>
                    </a>

                    <button type="button"
                            class="dashboard-mega-trigger"
                            data-dashboard-mega-toggle
                            aria-controls="dashboard-mega-menu"
                            aria-expanded="false">
                        <span class="dashboard-mega-trigger-icon">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M4 13h7V4H4v9Zm0 7h7v-5H4v5Zm9 0h7v-9h-7v9Zm0-16v5h7V4h-7Z"/>
                            </svg>
                        </span>
                        <span class="dashboard-mega-trigger-copy">
                            <strong>Menu Sistem</strong>
                            <small>{{ $dashboardRoleLabel }}</small>
                        </span>
                        <svg class="dashboard-mega-trigger-caret" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="m7 10 5 5 5-5H7Z"/>
                        </svg>
                    </button>

                    <button type="button"
                            class="dashboard-icon-button dashboard-menu-toggle"
                            data-dashboard-menu-toggle
                            aria-label="Buka menu ruang kerja"
                            aria-controls="dashboard-offcanvas"
                            aria-expanded="false">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M4 6.5h16v2H4v-2Zm0 4.5h16v2H4v-2Zm0 4.5h16v2H4v-2Z"/>
                        </svg>
                    </button>
                </div>

                <div class="dashboard-topbar-actions">
                    <a class="dashboard-topbar-link d-none d-lg-inline-flex" href="{{ url('/') }}">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M12 3 3 10.5V21h6v-6h6v6h6V10.5L12 3Z"/>
                        </svg>
                        <span>Beranda Publik</span>
                    </a>

                    <a class="dashboard-topbar-link d-none d-md-inline-flex" href="{{ $dashboardHomeUrl }}">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M4 13h7V4H4v9Zm0 7h7v-5H4v5Zm9 0h7v-9h-7v9Zm0-16v5h7V4h-7Z"/>
                        </svg>
                        <span>Ruang Kerja</span>
                    </a>

                    <div class="dashboard-notification-wrap">
                        <button type="button"
                                class="dashboard-icon-button dashboard-notification-button"
                                data-dashboard-panel-toggle="notifications"
                                aria-label="Tampilkan ringkasan aktivitas"
                                aria-expanded="false">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M12 22a2.5 2.5 0 0 0 2.45-2h-4.9A2.5 2.5 0 0 0 12 22Zm7-6V11a7 7 0 0 0-5-6.7V3a2 2 0 1 0-4 0v1.3A7 7 0 0 0 5 11v5l-2 2v1h18v-1l-2-2Z"/>
                            </svg>
                            <span class="dashboard-dot" aria-hidden="true"></span>
                        </button>

                        <div class="dashboard-floating-panel" data-dashboard-panel="notifications" hidden>
                            <div class="dashboard-floating-panel-header">
                                <strong>Aktivitas</strong>
                                <span>Ringkas</span>
                            </div>
                            <div class="dashboard-floating-panel-body">
                                <p>Panel aktivitas internal disiapkan sebagai shell. Data detail akan dimuat melalui modul terotorisasi pada batch berikutnya.</p>
                            </div>
                        </div>
                    </div>

                    <div class="dashboard-user-chip {{ $dashboardRoleBadgeClass }}" title="{{ $dashboardUserEmail }}">
                        <span class="dashboard-user-avatar">{{ $dashboardUserInitial }}</span>
                        <span class="dashboard-user-copy">
                            <strong>{{ $dashboardUserName }}</strong>
                            <small>{{ $dashboardRoleLabel }}</small>
                        </span>
                    </div>

                    <form method="POST" action="{{ route('logout') }}" class="dashboard-logout-form">
                        @csrf
                        <button type="submit" class="dashboard-logout-btn">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M16 13v-2H7V8l-5 4 5 4v-3h9Zm-2-10h6a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-6v-2h6V5h-6V3Z"/>
                            </svg>
                            <span>Keluar</span>
                        </button>
                    </form>
                </div>
            </div>
        </header>

        <div id="dashboard-mega-menu"
             class="dashboard-mega-layer"
             data-dashboard-mega-menu
             aria-hidden="true">
            <button type="button"
                    class="dashboard-mega-backdrop"
                    data-dashboard-mega-backdrop
                    aria-label="Tutup menu sistem"></button>

            <div class="dashboard-mega-panel"
                 role="dialog"
                 aria-modal="false"
                 aria-label="Mega menu sistem"
                 data-dashboard-server-panel
                 data-server-epoch-ms="{{ $dashboardServerEpochMs }}"
                 data-server-timezone="{{ $dashboardServerTimezone }}"
                 data-server-offset="{{ $dashboardServerOffset }}">
                <div class="container-fluid px-0">
                    <div class="row g-3 align-items-stretch dashboard-mega-bootstrap-row">
                        <div class="col-12 col-xl-3">
                            <div class="card h-100 border-0 shadow-sm dashboard-mega-card dashboard-mega-clock-panel">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center gap-3 mb-3">
                                        <span class="dashboard-user-avatar flex-shrink-0">{{ $dashboardUserInitial }}</span>
                                        <div class="min-w-0">
                                            <div class="fw-bold text-truncate">{{ $dashboardRoleLabel }}</div>
                                            <small class="text-muted d-block text-truncate">{{ $dashboardRoleHint }}</small>
                                        </div>
                                    </div>

                                    <div class="rounded-4 p-3 dashboard-mega-clock-card dashboard-mega-clock-card-compact">
                                        <div class="d-flex align-items-center justify-content-between gap-2 mb-1">
                                            <span class="dashboard-mega-clock-kicker">Waktu Server</span>
                                            <span class="badge rounded-pill dashboard-mega-soft-badge">Aktif</span>
                                        </div>
                                        <strong class="dashboard-mega-clock d-block" data-dashboard-server-clock>{{ $dashboardServerTimeLabel }}</strong>
                                        <small class="dashboard-mega-date d-block" data-dashboard-server-date>{{ $dashboardServerDateLabel }}</small>

                                        <div class="row g-2 mt-2 dashboard-mega-clock-meta">
                                            <div class="col-12">
                                                <div class="border rounded-3 p-2 bg-white bg-opacity-50">
                                                    <span class="d-block">Server</span>
                                                    <strong class="d-block">{{ $dashboardServerTimezone }}</strong>
                                                    <small>UTC{{ $dashboardServerOffset }}</small>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <div class="border rounded-3 p-2 bg-white bg-opacity-50">
                                                    <span class="d-block">Perangkat</span>
                                                    <strong class="d-block" data-dashboard-local-timezone>Mendeteksi...</strong>
                                                    <small data-dashboard-local-offset>Zona lokal</small>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <div class="border rounded-3 p-2 bg-white bg-opacity-50">
                                                    <span class="d-block">Selisih zonasi</span>
                                                    <strong class="d-block" data-dashboard-time-difference>Memeriksa waktu lokal...</strong>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-xl-3">
                            <nav class="card h-100 border-0 shadow-sm dashboard-mega-card" aria-label="Menu utama ruang kerja">
                                <div class="card-body p-3">
                                    <div class="mb-3">
                                        <span class="dashboard-mega-section-label">Menu Utama</span>
                                        <small class="text-muted d-block mt-1">Pilih area kerja sesuai kewenangan.</small>
                                    </div>

                                    <div class="list-group list-group-flush dashboard-mega-main-list">
                                        @foreach ($dashboardMenuItems as $menuItem)
                                            @php
                                                $menuRoute = $menuItem['route'] ?? null;
                                                $menuPermission = $menuItem['permission'] ?? null;
                                                $menuIcon = $menuItem['icon'] ?? 'dashboard';
                                                $routeExists = is_string($menuRoute) && Route::has($menuRoute);
                                                $permissionAllowed = empty($menuPermission) || (bool) $dashboardUser?->hasPermission($menuPermission);
                                                $menuEnabled = $routeExists && $permissionAllowed;
                                                $menuHref = $menuEnabled ? route($menuRoute) : '#';
                                                $menuActive = $routeExists && is_string($dashboardCurrentRoute) && $dashboardCurrentRoute === $menuRoute;
                                                $menuSubmenus = $dashboardSubmenuFor($menuItem);
                                                $menuDisplay = $dashboardMenuDisplay($menuItem);
                                            @endphp

                                            <a href="{{ $menuHref }}"
                                               class="list-group-item list-group-item-action border-0 rounded-3 mb-2 px-2 py-2 dashboard-mega-main-item {{ $menuActive ? 'active is-active' : '' }} {{ $menuEnabled ? '' : 'disabled is-disabled' }}"
                                               data-dashboard-mega-item
                                               data-menu-title="{{ $menuDisplay['title'] ?? 'Menu' }}"
                                               data-menu-description="{{ $menuDisplay['description'] ?? 'Belum tersedia' }}"
                                               data-menu-detail="{{ $menuDisplay['detail'] ?? $menuDisplay['description'] ?? 'Belum tersedia' }}"
                                               data-menu-status="{{ $menuEnabled ? 'Tersedia' : 'Dalam penyiapan' }}"
                                               data-menu-submenus="{{ base64_encode(json_encode($menuSubmenus, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) }}"
                                               @if (! $menuEnabled) aria-disabled="true" tabindex="-1" @endif>
                                                <div class="d-flex align-items-start gap-2">
                                                    <span class="dashboard-menu-icon flex-shrink-0">{!! $dashboardIcon($menuIcon) !!}</span>
                                                    <span class="min-w-0 flex-grow-1">
                                                        <strong class="d-block text-truncate">{{ $menuDisplay['title'] ?? 'Menu' }}</strong>
                                                        <small class="d-block text-muted text-truncate">{{ $menuDisplay['description'] ?? 'Belum tersedia' }}</small>
                                                    </span>
                                                    @if (! $menuEnabled)
                                                        <span class="badge rounded-pill dashboard-mega-soft-badge flex-shrink-0">Soon</span>
                                                    @endif
                                                </div>
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            </nav>
                        </div>

                        <div class="col-12 col-xl-3">
                            <section class="card h-100 border-0 shadow-sm dashboard-mega-card" aria-label="Submenu dari menu terpilih">
                                <div class="card-body p-3">
                                    <div class="mb-3">
                                        <span class="dashboard-mega-section-label">Submenu</span>
                                        <small class="text-muted d-block mt-1" data-dashboard-mega-submenu-title>{{ $dashboardFeaturedDisplay['title'] ?? 'Ruang Kerja' }}</small>
                                    </div>

                                    <div class="list-group list-group-flush dashboard-mega-submenu-list" data-dashboard-mega-submenu-list>
                                        @foreach ($dashboardSubmenuFor($dashboardFeaturedItem ?? []) as $submenu)
                                            <div class="list-group-item border rounded-3 mb-2 p-2 dashboard-mega-submenu-row">
                                                <div class="d-flex align-items-start gap-2">
                                                    <span class="dashboard-mega-submenu-symbol flex-shrink-0" aria-hidden="true">•</span>
                                                    <div class="min-w-0 flex-grow-1">
                                                        <div class="d-flex align-items-center justify-content-between gap-2">
                                                            <strong class="d-block text-truncate">{{ $submenu['title'] ?? 'Submenu' }}</strong>
                                                            <span class="badge rounded-pill dashboard-mega-soft-badge flex-shrink-0">{{ $submenu['state'] ?? 'Info' }}</span>
                                                        </div>
                                                        <small class="text-muted d-block dashboard-mega-two-line">{{ $submenu['description'] ?? 'Cakupan menu mengikuti kewenangan pengguna.' }}</small>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </section>
                        </div>

                        <div class="col-12 col-xl-3">
                            <aside class="card h-100 border-0 shadow-sm dashboard-mega-card dashboard-mega-preview-card">
                                <div class="card-body p-3">
                                    <span class="dashboard-mega-section-label">Konteks Menu</span>
                                    <h3 class="h4 fw-bold mt-3 mb-3" data-dashboard-mega-preview-title>{{ $dashboardFeaturedDisplay['title'] ?? 'Ruang Kerja' }}</h3>
                                    <p class="text-muted mb-3" data-dashboard-mega-preview-description>
                                        {{ $dashboardFeaturedDisplay['detail'] ?? $dashboardFeaturedDisplay['description'] ?? 'Pilih menu untuk melihat cakupan kerja dan informasi singkat.' }}
                                    </p>

                                    <div class="d-flex flex-wrap gap-2 mb-3 dashboard-mega-preview-scope" data-dashboard-mega-preview-scope>
                                        @foreach ($dashboardSubmenuFor($dashboardFeaturedItem ?? []) as $submenu)
                                            <span class="badge rounded-pill dashboard-mega-scope-badge">{{ $submenu['title'] ?? 'Cakupan' }}</span>
                                        @endforeach
                                    </div>

                                    <div class="border rounded-4 p-3 dashboard-mega-preview-note dashboard-mega-preview-scope-note" data-dashboard-mega-preview-note>
                                        <strong class="d-block mb-1">Cakupan penggunaan</strong>
                                        <span class="text-muted d-block">Gunakan menu ini untuk memahami ruang kerja, cakupan data, dan informasi yang dapat dikelola sesuai peran pengguna.</span>
                                    </div>
                                </div>
                            </aside>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div id="dashboard-offcanvas"
             class="dashboard-offcanvas"
             data-dashboard-offcanvas
             aria-hidden="true"
             aria-label="Menu ruang kerja tablet dan ponsel">
            <div class="dashboard-offcanvas-panel" role="dialog" aria-modal="true" aria-labelledby="dashboard-offcanvas-title">
                <div class="dashboard-offcanvas-head">
                    <div class="dashboard-offcanvas-title">
                        <span class="dashboard-user-avatar">{{ $dashboardUserInitial }}</span>
                        <span>
                            <strong id="dashboard-offcanvas-title">{{ $dashboardRoleLabel }}</strong>
                            <small>{{ $dashboardRoleHint }}</small>
                        </span>
                    </div>
                    <button type="button"
                            class="dashboard-icon-button"
                            data-dashboard-menu-close
                            aria-label="Tutup menu ruang kerja">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="m6.4 5 5.6 5.6L17.6 5 19 6.4 13.4 12l5.6 5.6-1.4 1.4-5.6-5.6L6.4 19 5 17.6l5.6-5.6L5 6.4 6.4 5Z"/>
                        </svg>
                    </button>
                </div>

                <nav class="dashboard-offcanvas-menu" aria-label="Navigasi internal">
                    @foreach ($dashboardMenuSections as $section)
                        <section class="dashboard-offcanvas-section">
                            <div class="dashboard-menu-cluster-label">{{ $section['label'] ?? 'Menu' }}</div>

                            <div class="dashboard-offcanvas-list">
                                @foreach (($section['items'] ?? []) as $menuItem)
                                    @php
                                        $menuRoute = $menuItem['route'] ?? null;
                                        $menuPermission = $menuItem['permission'] ?? null;
                                        $menuIcon = $menuItem['icon'] ?? 'dashboard';
                                        $routeExists = is_string($menuRoute) && Route::has($menuRoute);
                                        $permissionAllowed = empty($menuPermission) || (bool) $dashboardUser?->hasPermission($menuPermission);
                                        $menuEnabled = $routeExists && $permissionAllowed;
                                        $menuHref = $menuEnabled ? route($menuRoute) : '#';
                                        $menuActive = $routeExists && is_string($dashboardCurrentRoute) && $dashboardCurrentRoute === $menuRoute;
                                    @endphp

                                    <a href="{{ $menuHref }}"
                                       class="dashboard-offcanvas-item {{ $menuActive ? 'is-active' : '' }} {{ $menuEnabled ? '' : 'is-disabled' }}"
                                       @if (! $menuEnabled) aria-disabled="true" tabindex="-1" @endif>
                                        <span class="dashboard-menu-icon">{!! $dashboardIcon($menuIcon) !!}</span>
                                        <span class="dashboard-menu-copy">
                                            <strong>{{ $menuDisplay['title'] ?? 'Menu' }}</strong>
                                            <small>{{ $menuDisplay['description'] ?? 'Belum tersedia' }}</small>
                                        </span>
                                        @if (! $menuEnabled)
                                            <span class="dashboard-menu-state">Soon</span>
                                        @endif
                                    </a>
                                @endforeach
                            </div>
                        </section>
                    @endforeach
                </nav>
            </div>

            <button type="button"
                    class="dashboard-offcanvas-backdrop"
                    data-dashboard-offcanvas-backdrop
                    aria-label="Tutup menu ruang kerja"></button>
        </div>

        <main class="dashboard-main">
            <div class="dashboard-main-inner">
                <section class="dashboard-page-head">
                    <div>
                        <span class="dashboard-page-kicker">{{ $dashboardRoleLabel }}</span>
                        <h1>@yield('page_title', View::yieldContent('title', 'Ruang Kerja'))</h1>
                    </div>
                    <div class="dashboard-page-meta">
                        <span>{{ now()->translatedFormat('d M Y') }}</span>
                        <span>{{ $dashboardRoleHint }}</span>
                    </div>
                </section>

                <div class="dashboard-content">
                    @yield('content')
                </div>

                <footer class="dashboard-footer">
                    <span>Monitoring UMKM</span>
                    <span>Internal layout core · backend guard tetap otoritas final</span>
                </footer>
            </div>
        </main>
    </div>
</body>
</html>
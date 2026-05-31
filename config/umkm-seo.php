<?php

return [

    /*
    |--------------------------------------------------------------------------
    | UMKM Monitoring SEO Guard Core
    |--------------------------------------------------------------------------
    |
    | Konfigurasi ini hanya mengatur metadata publik yang aman. Konfigurasi ini
    | tidak membuka data internal, tidak membuka endpoint AJAX, tidak membuka
    | route skeleton, dan tidak menggantikan middleware/policy/security guard.
    |
    */

    'site_name' => env('UMKM_SEO_SITE_NAME', 'UMKM Monitoring'),

    'default_title' => env('UMKM_SEO_DEFAULT_TITLE', 'UMKM Monitoring'),

    'title_suffix' => env('UMKM_SEO_TITLE_SUFFIX', 'UMKM Monitoring'),

    'default_description' => env(
        'UMKM_SEO_DEFAULT_DESCRIPTION',
        'Sistem Monitoring UMKM berbasis data dengan visual analitik interaktif untuk mendukung monitoring kinerja dan pengambilan keputusan UMKM.'
    ),

    'default_locale' => env('UMKM_SEO_LOCALE', 'id_ID'),

    'default_type' => env('UMKM_SEO_TYPE', 'website'),

    /*
    |--------------------------------------------------------------------------
    | Public Indexing Switch
    |--------------------------------------------------------------------------
    |
    | Default sengaja false agar local/staging/development tidak terindeks.
    | Aktifkan hanya di production resmi dengan UMKM_SEO_INDEXING=true.
    |
    */

    'indexing_enabled' => (bool) env('UMKM_SEO_INDEXING', false),

    'public_robots_when_indexing_enabled' => 'index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1',

    'public_robots_when_indexing_disabled' => 'noindex,nofollow,noarchive',

    'private_robots' => 'noindex,nofollow,noarchive,nosnippet',

    'ajax_robots' => 'noindex,nofollow,noarchive,nosnippet',

    /*
    |--------------------------------------------------------------------------
    | Public Share Image
    |--------------------------------------------------------------------------
    |
    | Kosongkan bila belum ada gambar publik yang benar-benar aman. Jangan
    | memakai screenshot dashboard internal atau data UMKM sensitif.
    |
    */

    'default_image' => env('UMKM_SEO_DEFAULT_IMAGE', null),
];

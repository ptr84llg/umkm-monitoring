<?php

return [
    'security' => [
        'internal_allowed_origins' => array_filter(array_map('trim', explode(',', (string) env('INTERNAL_ALLOWED_ORIGINS', env('APP_URL', ''))))),
        'internal_allowed_referers' => array_filter(array_map('trim', explode(',', (string) env('INTERNAL_ALLOWED_REFERERS', env('APP_URL', ''))))),
        'fetch_metadata_enforced' => (bool) env('INTERNAL_FETCH_METADATA_ENFORCED', true),
        'api_rate_limit' => (int) env('INTERNAL_API_RATE_LIMIT', 60),
        'api_rate_window' => (int) env('INTERNAL_API_RATE_WINDOW', 1),
    ],

    'location_gate' => [
        'enabled' => (bool) env('LOCATION_GATE_ENABLED', true),
        'session_key' => env('LOCATION_GATE_SESSION_KEY', 'umkm.location_gate'),
        'ttl_minutes' => (int) env('LOCATION_GATE_TTL_MINUTES', 15),
        'max_accuracy_meters' => (float) env('LOCATION_GATE_MAX_ACCURACY_METERS', 10000),
        'coordinate_precision' => (int) env('LOCATION_GATE_COORDINATE_PRECISION', 6),
    ],

    'landing_region' => [
        'enabled' => (bool) env('LANDING_REGION_ENABLE_PUBLIC_SAFE_API', true),
        'province_code' => env('LANDING_REGION_PROVINCE_CODE', '16'),
        'province_name' => env('LANDING_REGION_PROVINCE_NAME', 'Sumatera Selatan'),
        'city_code' => env('LANDING_REGION_CITY_CODE', '16.73'),
        'city_name' => env('LANDING_REGION_CITY_NAME', 'Kota Lubuklinggau'),
        'default_scope' => env('LANDING_REGION_DEFAULT_SCOPE', 'city'),
        'max_children' => (int) env('LANDING_REGION_MAX_CHILDREN', 500),
    ],

    'map' => [
        'provider' => env('MAP_PROVIDER', 'leaflet'),
        'google_maps' => [
            'enabled' => (bool) env('GOOGLE_MAPS_ENABLED', false),
            'api_key' => env('GOOGLE_MAPS_API_KEY'),
        ],
        'leaflet' => [
            'tile_url' => env('LEAFLET_TILE_URL', 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png'),
            'attribution' => env('LEAFLET_ATTRIBUTION', '&copy; OpenStreetMap contributors'),
        ],
    ],

    'captcha' => [
        'provider' => env('CAPTCHA_PROVIDER', 'none'),
        'site_key' => env('CAPTCHA_SITE_KEY'),
        'secret_key' => env('CAPTCHA_SECRET_KEY'),
        'score_threshold' => (float) env('CAPTCHA_SCORE_THRESHOLD', 0.5),
    ],

    'audit' => [
        'enabled' => (bool) env('AUDIT_LOG_ENABLED', true),
        'channel' => env('AUDIT_LOG_CHANNEL', 'stack'),
        'retention_days' => (int) env('AUDIT_LOG_RETENTION_DAYS', 365),
    ],

    'export' => [
        'watermark_enabled' => (bool) env('EXPORT_WATERMARK_ENABLED', true),
        'require_reason' => (bool) env('EXPORT_REQUIRE_REASON', true),
        'max_per_day' => (int) env('EXPORT_MAX_PER_DAY', 20),
    ],

    'oauth' => [
        'google' => [
            'client_id' => env('GOOGLE_CLIENT_ID'),
            'client_secret' => env('GOOGLE_CLIENT_SECRET'),
            'redirect' => env('GOOGLE_REDIRECT_URI'),
        ],
    ],
];

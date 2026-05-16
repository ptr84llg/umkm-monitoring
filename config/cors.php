<?php
return [
    'paths' => ['api/internal/*'],
    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'],
    'allowed_origins' => config('umkm.security.internal_allowed_origins', []),
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['Content-Type', 'X-Requested-With', 'X-CSRF-TOKEN', 'Authorization', 'Origin', 'Referer', 'Sec-Fetch-Site'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];

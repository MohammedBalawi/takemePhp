<?php

return [
    'provider' => env('GEOCODE_PROVIDER', 'nominatim'),
    'user_agent' => env('GEOCODE_USER_AGENT', 'TakeMeAdmin/1.0 (support@takeme.local)'),
    'timeout' => (int) env('GEOCODE_TIMEOUT_SECONDS', 6),
    'cache_ttl_seconds' => 86400,
];

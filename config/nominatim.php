<?php

return [
    'base_url' => env('NOMINATIM_BASE_URL', 'https://nominatim.openstreetmap.org'),
    'user_agent' => env('NOMINATIM_USER_AGENT', 'CRISIMAP-System/1.0'),
    'timeout' => (int) env('NOMINATIM_TIMEOUT', 10),
    'country_code' => env('NOMINATIM_COUNTRY_CODE', 'ph'),
];

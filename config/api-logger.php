<?php

return [
    // Enable or disable API logging
    'enabled' => env('API_LOGGER_ENABLED', true),

    // Header keys to mask in logs
    'masked_headers' => [
        'authorization',
        'cookie',
        'x-api-key',
    ],

    // Body keys to mask in logs
    'masked_body_keys' => [
        'password',
        'token',
        'access_token',
        'refresh_token',
    ],
];

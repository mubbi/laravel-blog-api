<?php

return [
    'api' => [
        'default_rate_limit' => env('DEFAULT_API_RATE_LIMIT', 60),
        'auth_rate_limit' => env('AUTH_API_RATE_LIMIT', 5), // Stricter for auth endpoints
        'sensitive_rate_limit' => env('SENSITIVE_API_RATE_LIMIT', 10), // For sensitive operations
        'admin_rate_limit' => env('ADMIN_API_RATE_LIMIT', 30), // Admin endpoints
    ],
];

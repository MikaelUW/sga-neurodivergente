<?php

// Origens permitidas vêm do FRONTEND_URL (uma ou várias, separadas por vírgula).
$frontendUrls = array_filter(array_map('trim', explode(',', (string) env('FRONTEND_URL', ''))));

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    // Restrito ao(s) domínio(s) do frontend. Em dev inclui o servidor estático.
    'allowed_origins' => $frontendUrls ?: ['http://localhost:5500', 'http://127.0.0.1:5500'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    // Usamos tokens Bearer (não cookies), então credentials pode ficar false.
    'supports_credentials' => false,
];

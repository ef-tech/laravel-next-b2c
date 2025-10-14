<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'up'],

    'allowed_methods' => array_filter(array_map(
        'trim',
        explode(',', env('CORS_ALLOWED_METHODS', '*'))
    )),

    'allowed_origins' => array_filter(array_map(
        'trim',
        explode(',', env('CORS_ALLOWED_ORIGINS', 'http://localhost:13001,http://localhost:13002,http://127.0.0.1:13001,http://127.0.0.1:13002'))
    )),

    'allowed_origins_patterns' => [],

    'allowed_headers' => array_filter(array_map(
        'trim',
        explode(',', env('CORS_ALLOWED_HEADERS', '*'))
    )),

    'exposed_headers' => [],

    'max_age' => (int) env('CORS_MAX_AGE', env('APP_ENV') === 'production' ? 86400 : 600),

    'supports_credentials' => filter_var(
        env('CORS_SUPPORTS_CREDENTIALS', false),
        FILTER_VALIDATE_BOOLEAN
    ),

];

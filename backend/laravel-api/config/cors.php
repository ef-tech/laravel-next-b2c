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

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost:13001',   // user-app（新ポート）
        'http://localhost:13002',   // admin-app（新ポート）
        'http://127.0.0.1:13001',   // user-app（新ポート・127.0.0.1）
        'http://127.0.0.1:13002',   // admin-app（新ポート・127.0.0.1）
        'http://localhost:3000',    // user-app（旧ポート・互換性維持）
        'http://localhost:3001',    // admin-app（旧ポート・互換性維持）
        'http://127.0.0.1:3000',    // ローカルアクセス（旧ポート）
        'http://127.0.0.1:3001',    // ローカルアクセス（旧ポート）
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];

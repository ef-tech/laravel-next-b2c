<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | X-Frame-Options Header
    |--------------------------------------------------------------------------
    |
    | X-Frame-Options ヘッダーの値を設定します。
    | クリックジャッキング攻撃を防止するために使用されます。
    |
    | サポートされる値: DENY, SAMEORIGIN
    |
    */
    'x_frame_options' => env('SECURITY_X_FRAME_OPTIONS', 'DENY'),

    /*
    |--------------------------------------------------------------------------
    | Referrer-Policy Header
    |--------------------------------------------------------------------------
    |
    | Referrer-Policy ヘッダーの値を設定します。
    | リファラー情報の漏洩を防止するために使用されます。
    |
    | サポートされる値: no-referrer, no-referrer-when-downgrade,
    | strict-origin, strict-origin-when-cross-origin, same-origin, etc.
    |
    */
    'referrer_policy' => env('SECURITY_REFERRER_POLICY', 'strict-origin-when-cross-origin'),

    /*
    |--------------------------------------------------------------------------
    | Content Security Policy (CSP)
    |--------------------------------------------------------------------------
    |
    | Content Security Policy (CSP) の設定を定義します。
    | XSS 攻撃を防止するために使用されます。
    |
    */
    'csp' => [
        /*
        | CSP を有効化するかどうか
        */
        'enabled' => env('SECURITY_ENABLE_CSP', false),

        /*
        | CSP モード: 'report-only' (監視のみ) または 'enforce' (強制)
        */
        'mode' => env('SECURITY_CSP_MODE', 'report-only'),

        /*
        | CSP ディレクティブの設定
        */
        'directives' => [
            'default-src' => ["'self'"],
            'object-src' => ["'none'"],
            'frame-ancestors' => ["'none'"],
            'script-src' => array_map('trim', array_filter(explode(',', (string) env('SECURITY_CSP_SCRIPT_SRC', 'self')))),
            'style-src' => array_map('trim', array_filter(explode(',', (string) env('SECURITY_CSP_STYLE_SRC', 'self,unsafe-inline')))),
            'img-src' => array_map('trim', array_filter(explode(',', (string) env('SECURITY_CSP_IMG_SRC', 'self,data:,https:')))),
            'connect-src' => array_map('trim', array_filter(explode(',', (string) env('SECURITY_CSP_CONNECT_SRC', 'self')))),
            'font-src' => array_map('trim', array_filter(explode(',', (string) env('SECURITY_CSP_FONT_SRC', 'self,data:')))),
        ],

        /*
        | CSP 違反レポートの送信先 URI
        */
        'report_uri' => env('SECURITY_CSP_REPORT_URI', '/api/csp/report'),
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP Strict Transport Security (HSTS)
    |--------------------------------------------------------------------------
    |
    | HSTS の設定を定義します。
    | HTTPS 通信を強制し、ダウングレード攻撃を防止するために使用されます。
    |
    */
    'hsts' => [
        /*
        | HSTS を有効化するかどうか
        */
        'enabled' => env('SECURITY_FORCE_HSTS', false),

        /*
        | max-age ディレクティブの値（秒単位）
        | デフォルト: 31536000 (1年)
        */
        'max_age' => (int) env('SECURITY_HSTS_MAX_AGE', 31536000),

        /*
        | includeSubDomains ディレクティブを含めるかどうか
        */
        'include_subdomains' => env('SECURITY_HSTS_INCLUDE_SUBDOMAINS', true),

        /*
        | preload ディレクティブを含めるかどうか
        */
        'preload' => env('SECURITY_HSTS_PRELOAD', true),
    ],
];

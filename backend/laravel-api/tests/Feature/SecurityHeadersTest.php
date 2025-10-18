<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;

describe('SecurityHeaders Middleware', function () {
    beforeEach(function () {
        // セキュリティヘッダー設定をデフォルト値でリセット
        Config::set('security.x_frame_options', 'DENY');
        Config::set('security.referrer_policy', 'strict-origin-when-cross-origin');
        Config::set('security.csp.enabled', false); // 基本テストではCSPを無効化
        Config::set('security.hsts.enabled', false); // 基本テストではHSTSを無効化
    });

    test('基本セキュリティヘッダーが設定されること', function () {
        $response = $this->get('/api/health');

        $response->assertStatus(200);
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    });

    test('X-Frame-Optionsが環境変数から設定されること', function () {
        Config::set('security.x_frame_options', 'SAMEORIGIN');

        $response = $this->get('/api/health');

        $response->assertStatus(200);
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    });

    test('Referrer-Policyが環境変数から設定されること', function () {
        Config::set('security.referrer_policy', 'no-referrer');

        $response = $this->get('/api/health');

        $response->assertStatus(200);
        $response->assertHeader('Referrer-Policy', 'no-referrer');
    });

    test('CSPが無効の場合はCSPヘッダーが設定されないこと', function () {
        Config::set('security.csp.enabled', false);

        $response = $this->get('/api/health');

        $response->assertStatus(200);
        $response->assertHeaderMissing('Content-Security-Policy');
        $response->assertHeaderMissing('Content-Security-Policy-Report-Only');
    });

    test('CSPがReport-Onlyモードの場合は適切なヘッダーが設定されること', function () {
        Config::set('security.csp.enabled', true);
        Config::set('security.csp.mode', 'report-only');
        Config::set('security.csp.directives', [
            'default-src' => ["'self'"],
            'script-src' => ["'self'"],
            'object-src' => ["'none'"],
        ]);

        $response = $this->get('/api/health');

        $response->assertStatus(200);
        $response->assertHeader('Content-Security-Policy-Report-Only');
        $response->assertHeaderMissing('Content-Security-Policy');

        $cspHeader = $response->headers->get('Content-Security-Policy-Report-Only');
        expect($cspHeader)->toContain("default-src 'self'");
        expect($cspHeader)->toContain("script-src 'self'");
        expect($cspHeader)->toContain("object-src 'none'");
    });

    test('CSPがEnforceモードの場合は適切なヘッダーが設定されること', function () {
        Config::set('security.csp.enabled', true);
        Config::set('security.csp.mode', 'enforce');
        Config::set('security.csp.directives', [
            'default-src' => ["'self'"],
            'script-src' => ["'self'"],
            'object-src' => ["'none'"],
        ]);

        $response = $this->get('/api/health');

        $response->assertStatus(200);
        $response->assertHeader('Content-Security-Policy');
        $response->assertHeaderMissing('Content-Security-Policy-Report-Only');

        $cspHeader = $response->headers->get('Content-Security-Policy');
        expect($cspHeader)->toContain("default-src 'self'");
        expect($cspHeader)->toContain("script-src 'self'");
        expect($cspHeader)->toContain("object-src 'none'");
    });

    test('HTTPS環境でHSTSが有効の場合はHSTSヘッダーが設定されること', function () {
        Config::set('security.hsts.enabled', true);
        Config::set('security.hsts.max_age', 31536000);
        Config::set('security.hsts.include_subdomains', true);
        Config::set('security.hsts.preload', true);

        // HTTPSリクエストをシミュレート
        $response = $this->get('/api/health', [
            'HTTP_X_FORWARDED_PROTO' => 'https',
        ]);

        $response->assertStatus(200);
        $response->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
    });

    test('HTTP環境ではHSTSヘッダーが設定されないこと', function () {
        Config::set('security.hsts.enabled', true);

        // HTTPリクエスト（デフォルト）
        $response = $this->get('/api/health');

        $response->assertStatus(200);
        $response->assertHeaderMissing('Strict-Transport-Security');
    });

    test('CSPレポートURIが設定されている場合はreport-uriディレクティブが含まれること', function () {
        Config::set('security.csp.enabled', true);
        Config::set('security.csp.mode', 'enforce');
        Config::set('security.csp.directives', [
            'default-src' => ["'self'"],
        ]);
        Config::set('security.csp.report_uri', '/api/csp/report');

        $response = $this->get('/api/health');

        $response->assertStatus(200);
        $cspHeader = $response->headers->get('Content-Security-Policy');
        expect($cspHeader)->toBeString()->toContain('report-uri /api/csp/report');
    });

    test('複数のディレクティブ値が正しくスペース区切りで連結されること', function () {
        Config::set('security.csp.enabled', true);
        Config::set('security.csp.mode', 'enforce');
        Config::set('security.csp.directives', [
            'script-src' => ["'self'", "'unsafe-inline'", "'unsafe-eval'"],
            'style-src' => ["'self'", 'https://fonts.googleapis.com'],
        ]);

        $response = $this->get('/api/health');

        $response->assertStatus(200);
        $cspHeader = $response->headers->get('Content-Security-Policy');
        expect($cspHeader)->toBeString()->toContain("script-src 'self' 'unsafe-inline' 'unsafe-eval'");
        expect($cspHeader)->toBeString()->toContain("style-src 'self' https://fonts.googleapis.com");
    });
});

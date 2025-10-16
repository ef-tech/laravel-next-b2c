<?php

declare(strict_types=1);

describe('CspReportController', function () {
    test('CSP違反レポートを正常に受信できること', function () {
        $response = $this->postJson('/api/csp/report', [
            'csp-report' => [
                'blocked-uri' => 'https://evil.com/script.js',
                'violated-directive' => 'script-src',
                'original-policy' => "default-src 'self'; script-src 'self'",
                'document-uri' => 'https://example.com/page',
                'referrer' => 'https://example.com',
                'source-file' => 'https://example.com/page',
                'line-number' => 42,
                'column-number' => 10,
                'status-code' => 200,
            ],
        ], [
            'Content-Type' => 'application/csp-report',
        ]);

        $response->assertNoContent();
    });

    test('Content-Typeが不正な場合は400エラーを返すこと', function () {
        $response = $this->postJson('/api/csp/report', [
            'csp-report' => [
                'blocked-uri' => 'https://evil.com/script.js',
                'violated-directive' => 'script-src',
            ],
        ], [
            'Content-Type' => 'application/json',
        ]);

        $response->assertStatus(400);
        $response->assertJson(['error' => 'Invalid Content-Type']);
    });

    test('CSPレポートが空の場合は400エラーを返すこと', function () {
        $response = $this->postJson('/api/csp/report', [], [
            'Content-Type' => 'application/csp-report',
        ]);

        $response->assertStatus(400);
        $response->assertJson(['error' => 'Empty CSP report']);
    });

    test('レート制限が適用されること (100 requests per minute)', function () {
        // 100リクエストは成功
        for ($i = 0; $i < 100; $i++) {
            $response = $this->postJson('/api/csp/report', [
                'csp-report' => [
                    'blocked-uri' => 'https://evil.com/script.js',
                    'violated-directive' => 'script-src',
                ],
            ], [
                'Content-Type' => 'application/csp-report',
            ]);

            $response->assertStatus(204);
        }

        // 101リクエスト目は失敗 (レート制限)
        $response = $this->postJson('/api/csp/report', [
            'csp-report' => [
                'blocked-uri' => 'https://evil.com/script.js',
                'violated-directive' => 'script-src',
            ],
        ], [
            'Content-Type' => 'application/csp-report',
        ]);

        $response->assertStatus(429); // Too Many Requests
    });

    test('CSPレポートがオプションフィールドを含まない場合でもログ記録できること', function () {
        $response = $this->postJson('/api/csp/report', [
            'csp-report' => [
                'blocked-uri' => 'https://evil.com/script.js',
                'violated-directive' => 'script-src',
                'original-policy' => "default-src 'self'",
                'document-uri' => 'https://example.com/page',
            ],
        ], [
            'Content-Type' => 'application/csp-report',
        ]);

        $response->assertNoContent();
    });

    test('User-AgentとIPアドレスがログに記録されること', function () {
        $response = $this->postJson('/api/csp/report', [
            'csp-report' => [
                'blocked-uri' => 'https://evil.com/script.js',
                'violated-directive' => 'script-src',
                'original-policy' => "default-src 'self'",
                'document-uri' => 'https://example.com/page',
            ],
        ], [
            'User-Agent' => 'Mozilla/5.0 (Test Browser)',
            'Content-Type' => 'application/csp-report',
        ]);

        $response->assertNoContent();
    });
});

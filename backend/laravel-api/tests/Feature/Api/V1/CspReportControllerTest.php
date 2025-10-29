<?php

declare(strict_types=1);

use function Pest\Laravel\postJson;
use function Pest\Laravel\withServerVariables;

describe('V1 CSP Report API - POST /api/v1/csp/report', function () {
    test('CSP違反レポートを正常に受信できること', function () {
        $response = postJson('/api/v1/csp/report', [
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

        $response->assertNoContent()
            ->assertHeader('X-API-Version', 'v1');
    });

    test('Content-Type application/jsonでも正常に受信できること（互換性）', function () {
        $response = postJson('/api/v1/csp/report', [
            'csp-report' => [
                'blocked-uri' => 'https://evil.com/script.js',
                'violated-directive' => 'script-src',
                'original-policy' => "default-src 'self'; script-src 'self'",
                'document-uri' => 'https://example.com/page',
            ],
        ], [
            'Content-Type' => 'application/json',
        ]);

        $response->assertNoContent()
            ->assertHeader('X-API-Version', 'v1');
    });

    test('Content-Typeが不正な場合は415エラーを返すこと', function () {
        $response = postJson('/api/v1/csp/report', [
            'csp-report' => [
                'blocked-uri' => 'https://evil.com/script.js',
                'violated-directive' => 'script-src',
            ],
        ], [
            'Content-Type' => 'text/plain',
        ]);

        $response->assertStatus(415)
            ->assertJson(['error' => 'Unsupported Media Type']);
    });

    test('CSPレポートが空の場合は400エラーを返すこと', function () {
        $response = postJson('/api/v1/csp/report', [], [
            'Content-Type' => 'application/csp-report',
        ]);

        $response->assertStatus(400)
            ->assertJson(['error' => 'Empty CSP report']);
    });

    test('CSPレポートがオプションフィールドを含まない場合でもログ記録できること', function () {
        $response = withServerVariables(['REMOTE_ADDR' => '192.168.2.1'])
            ->postJson('/api/v1/csp/report', [
                'csp-report' => [
                    'blocked-uri' => 'https://evil.com/script.js',
                    'violated-directive' => 'script-src',
                    'original-policy' => "default-src 'self'",
                    'document-uri' => 'https://example.com/page',
                ],
            ], [
                'Content-Type' => 'application/csp-report',
            ]);

        $response->assertNoContent()
            ->assertHeader('X-API-Version', 'v1');
    });

    test('User-AgentとIPアドレスがログに記録されること', function () {
        $response = withServerVariables(['REMOTE_ADDR' => '192.168.3.1'])
            ->postJson('/api/v1/csp/report', [
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

        $response->assertNoContent()
            ->assertHeader('X-API-Version', 'v1');
    });
});

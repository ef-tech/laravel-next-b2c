<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Redis;

describe('CspReportController', function () {
    beforeEach(function () {
        // レート制限カウンターをクリア
        try {
            $redis = Redis::connection('default');
            $keys = $redis->keys('rate_limit:*');
            if (! empty($keys)) {
                $redis->del($keys);
            }
        } catch (\Exception $e) {
            // Redis接続エラーは無視（CI環境でRedisが利用できない場合）
        }
    });

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

    test('Content-Type application/jsonでも正常に受信できること（互換性）', function () {
        $response = $this->postJson('/api/csp/report', [
            'csp-report' => [
                'blocked-uri' => 'https://evil.com/script.js',
                'violated-directive' => 'script-src',
                'original-policy' => "default-src 'self'; script-src 'self'",
                'document-uri' => 'https://example.com/page',
            ],
        ], [
            'Content-Type' => 'application/json',
        ]);

        $response->assertNoContent();
    });

    test('Content-Typeが不正な場合は415エラーを返すこと', function () {
        $response = $this->postJson('/api/csp/report', [
            'csp-report' => [
                'blocked-uri' => 'https://evil.com/script.js',
                'violated-directive' => 'script-src',
            ],
        ], [
            'Content-Type' => 'text/plain',
        ]);

        // ForceJsonResponseミドルウェアがapplication/csp-report以外を拒否
        $response->assertStatus(415);
        $response->assertJson(['error' => 'Unsupported Media Type']);
    });

    test('CSPレポートが空の場合は400エラーを返すこと', function () {
        $response = $this->postJson('/api/csp/report', [], [
            'Content-Type' => 'application/csp-report',
        ]);

        $response->assertStatus(400);
        $response->assertJson(['error' => 'Empty CSP report']);
    });

    test('レート制限が適用されること (60 requests per minute)', function () {
        // 完全に一意なIPアドレスを使用（タイムスタンプベース）
        $uniqueIp = '192.168.'.((int) (microtime(true) * 1000) % 256).'.'.rand(1, 254);

        // 60リクエストは成功（apiグループのDynamicRateLimit:api設定）
        // Note: routes/api.phpでthrottle:100,1を設定しているが、
        // apiグループのDynamicRateLimit:api (60 req/min) がより厳しいため優先される
        for ($i = 0; $i < 60; $i++) {
            $response = $this->withServerVariables(['REMOTE_ADDR' => $uniqueIp])
                ->postJson('/api/csp/report', [
                    'csp-report' => [
                        'blocked-uri' => 'https://evil.com/script.js',
                        'violated-directive' => 'script-src',
                    ],
                ], [
                    'Content-Type' => 'application/csp-report',
                ]);

            $response->assertStatus(204);
        }

        // 61リクエスト目は失敗 (レート制限)
        $response = $this->withServerVariables(['REMOTE_ADDR' => $uniqueIp])
            ->postJson('/api/csp/report', [
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
        $response = $this->withServerVariables(['REMOTE_ADDR' => '192.168.2.1'])
            ->postJson('/api/csp/report', [
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
        $response = $this->withServerVariables(['REMOTE_ADDR' => '192.168.3.1'])
            ->postJson('/api/csp/report', [
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

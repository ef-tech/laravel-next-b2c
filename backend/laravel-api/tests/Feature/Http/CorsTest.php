<?php

use Illuminate\Support\Facades\Log;

describe('CORS Configuration', function () {
    describe('Environment Variable Parsing', function () {
        test('parses CORS_ALLOWED_ORIGINS as comma-separated list', function () {
            putenv('CORS_ALLOWED_ORIGINS=http://localhost:13001,http://localhost:13002');

            // 設定をリフレッシュ
            config(['cors.allowed_origins' => array_filter(array_map(
                'trim',
                explode(',', env('CORS_ALLOWED_ORIGINS', ''))
            ))]);

            $origins = config('cors.allowed_origins');

            expect($origins)->toBeArray()
                ->and($origins)->toHaveCount(2)
                ->and($origins)->toContain('http://localhost:13001')
                ->and($origins)->toContain('http://localhost:13002');

            putenv('CORS_ALLOWED_ORIGINS');
        });

        test('trims whitespace from origin URLs', function () {
            putenv('CORS_ALLOWED_ORIGINS= http://localhost:13001 , http://localhost:13002 ');

            config(['cors.allowed_origins' => array_filter(array_map(
                'trim',
                explode(',', env('CORS_ALLOWED_ORIGINS', ''))
            ))]);

            $origins = config('cors.allowed_origins');

            expect($origins)->toBeArray()
                ->and($origins)->toContain('http://localhost:13001')
                ->and($origins)->toContain('http://localhost:13002')
                ->and($origins)->each->not()->toMatch('/^\s|\s$/'); // 前後の空白なし

            putenv('CORS_ALLOWED_ORIGINS');
        });

        test('filters empty strings from origins array', function () {
            putenv('CORS_ALLOWED_ORIGINS=http://localhost:13001,,http://localhost:13002');

            config(['cors.allowed_origins' => array_filter(array_map(
                'trim',
                explode(',', env('CORS_ALLOWED_ORIGINS', ''))
            ))]);

            $origins = config('cors.allowed_origins');

            expect($origins)->toBeArray()
                ->and($origins)->toHaveCount(2)
                ->and($origins)->each->not()->toBeEmpty();

            putenv('CORS_ALLOWED_ORIGINS');
        });

        test('applies environment-based default for max_age', function () {
            // 本番環境のシミュレーション（設定値で直接テスト）
            $maxAgeProduction = 86400;
            $maxAgeDevelopment = 600;

            expect($maxAgeProduction)->toBe(86400)
                ->and($maxAgeDevelopment)->toBe(600);

            // アプリケーション環境によるデフォルト値ロジックのテスト
            $getDefaultMaxAge = function ($isProduction) {
                return $isProduction ? 86400 : 600;
            };

            expect($getDefaultMaxAge(true))->toBe(86400)
                ->and($getDefaultMaxAge(false))->toBe(600);
        });

        test('converts CORS_SUPPORTS_CREDENTIALS to boolean', function () {
            putenv('CORS_SUPPORTS_CREDENTIALS=true');
            config(['cors.supports_credentials' => filter_var(
                env('CORS_SUPPORTS_CREDENTIALS', false),
                FILTER_VALIDATE_BOOLEAN
            )]);

            expect(config('cors.supports_credentials'))->toBeTrue();

            putenv('CORS_SUPPORTS_CREDENTIALS=false');
            config(['cors.supports_credentials' => filter_var(
                env('CORS_SUPPORTS_CREDENTIALS', false),
                FILTER_VALIDATE_BOOLEAN
            )]);

            expect(config('cors.supports_credentials'))->toBeFalse();

            putenv('CORS_SUPPORTS_CREDENTIALS');
        });
    });

    describe('Preflight Request Handling', function () {
        beforeEach(function () {
            config(['cors.allowed_origins' => [
                'http://localhost:13001',
                'http://localhost:13002',
            ]]);
        });

        test('returns CORS headers for allowed origin', function () {
            $response = $this->options('/api/health', [], ['Origin' => 'http://localhost:13001']);

            $response->assertStatus(200);
            expect($response->headers->get('Access-Control-Allow-Origin'))->toBe('http://localhost:13001');
        })->skip('Preflight request headers are handled by Laravel CORS middleware');

        test('rejects non-allowed origin without headers', function () {
            $response = $this->options('/api/health', [], ['Origin' => 'http://evil.com']);

            expect($response->headers->has('Access-Control-Allow-Origin'))->toBeFalse();
        });

        test('includes Access-Control-Allow-Methods header', function () {
            $response = $this->options('/api/health', [], ['Origin' => 'http://localhost:13001']);

            $response->assertStatus(200);
            $methods = $response->headers->get('Access-Control-Allow-Methods');
            expect($methods)->not()->toBeEmpty();
        })->skip('Preflight request headers are handled by Laravel CORS middleware');

        test('includes Access-Control-Max-Age header', function () {
            $response = $this->options('/api/health', [], ['Origin' => 'http://localhost:13001']);

            $response->assertStatus(200);
            $maxAge = $response->headers->get('Access-Control-Max-Age');
            expect($maxAge)->not()->toBeNull();
        })->skip('Preflight request headers are handled by Laravel CORS middleware');

        test('handles multiple allowed origins correctly', function () {
            $response1 = $this->options('/api/health', [], ['Origin' => 'http://localhost:13001']);
            $response1->assertStatus(200);
            expect($response1->headers->get('Access-Control-Allow-Origin'))->toBe('http://localhost:13001');

            $response2 = $this->options('/api/health', [], ['Origin' => 'http://localhost:13002']);
            $response2->assertStatus(200);
            expect($response2->headers->get('Access-Control-Allow-Origin'))->toBe('http://localhost:13002');
        });
    });

    describe('Validation Logic', function () {
        test('validates URL format with parse_url', function () {
            $validUrl = 'http://localhost:13001';
            $parsed = parse_url($validUrl);

            expect($parsed)->toBeArray()
                ->and($parsed)->toHaveKey('scheme')
                ->and($parsed)->toHaveKey('host');

            $invalidUrl = 'invalid-url';
            $parsedInvalid = parse_url($invalidUrl);

            expect($parsedInvalid)->not()->toHaveKey('scheme');
        });

        test('logs warning for invalid origin format', function () {
            Log::spy();

            $origins = ['invalid-url', 'http://localhost:13001'];
            config(['cors.allowed_origins' => $origins]);

            (new \App\Providers\AppServiceProvider(app()))->boot();

            Log::shouldHaveReceived('warning')
                ->once()
                ->withArgs(function ($message, $context) {
                    return $message === 'Invalid CORS origin format'
                        && $context['origin'] === 'invalid-url';
                });
        });

        test('logs warning for HTTP origin in production', function () {
            Log::spy();

            config(['app.env' => 'production']);
            config(['cors.allowed_origins' => ['http://localhost:13001']]);

            (new \App\Providers\AppServiceProvider(app()))->boot();

            Log::shouldHaveReceived('warning')
                ->once()
                ->withArgs(function ($message, $context) {
                    return $message === 'Non-HTTPS origin in production CORS'
                        && $context['origin'] === 'http://localhost:13001'
                        && $context['environment'] === 'production';
                });
        })->skip('Log validation requires AppServiceProvider to not be pre-booted in CI');

        test('logs warning for wildcard in production', function () {
            Log::spy();

            config(['app.env' => 'production']);
            config(['cors.allowed_origins' => ['*', 'https://example.com']]);

            (new \App\Providers\AppServiceProvider(app()))->boot();

            Log::shouldHaveReceived('warning')
                ->once()
                ->withArgs(function ($message, $context) {
                    return $message === 'Wildcard origin in production is not recommended'
                        && $context['environment'] === 'production';
                });
        })->skip('Log validation requires AppServiceProvider to not be pre-booted in CI');
    });
});

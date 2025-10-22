<?php

declare(strict_types=1);

/**
 * レート制限設定ファイルのテスト
 *
 * Requirements: 3.2, 3.7, 3.8, 3.9, 3.11, 13.2
 */
describe('RateLimitConfig', function () {
    it('ratelimit設定ファイルが存在すること', function () {
        $config = config('ratelimit');

        expect($config)->not->toBeNull('ratelimit設定ファイルが読み込まれること');
    });

    it('エンドポイント別レート制限設定が存在すること', function () {
        $endpoints = config('ratelimit.endpoints');

        expect($endpoints)->toBeArray();
        expect($endpoints)->toHaveKey('api');
        expect($endpoints)->toHaveKey('public');
        expect($endpoints)->toHaveKey('webhook');
        expect($endpoints)->toHaveKey('strict');
    });

    it('APIエンドポイントのレート制限設定が正しいこと', function () {
        $api = config('ratelimit.endpoints.api');

        expect($api)->toBeArray();
        expect($api)->toHaveKey('requests');
        expect($api)->toHaveKey('per_minute');
        expect($api)->toHaveKey('by');
        expect($api['requests'])->toBe(60);
        expect($api['per_minute'])->toBe(1);
        expect($api['by'])->toBe('ip');
    });

    it('公開APIエンドポイントのレート制限設定が正しいこと', function () {
        $public = config('ratelimit.endpoints.public');

        expect($public)->toBeArray();
        expect($public)->toHaveKey('requests');
        expect($public)->toHaveKey('per_minute');
        expect($public)->toHaveKey('by');
        expect($public['requests'])->toBe(30);
        expect($public['per_minute'])->toBe(1);
        expect($public['by'])->toBe('ip');
    });

    it('Webhookエンドポイントのレート制限設定が正しいこと', function () {
        $webhook = config('ratelimit.endpoints.webhook');

        expect($webhook)->toBeArray();
        expect($webhook)->toHaveKey('requests');
        expect($webhook)->toHaveKey('per_minute');
        expect($webhook)->toHaveKey('by');
        expect($webhook['requests'])->toBe(100);
        expect($webhook['per_minute'])->toBe(1);
        expect($webhook['by'])->toBe('ip');
    });

    it('Strictエンドポイントのレート制限設定が正しいこと', function () {
        $strict = config('ratelimit.endpoints.strict');

        expect($strict)->toBeArray();
        expect($strict)->toHaveKey('requests');
        expect($strict)->toHaveKey('per_minute');
        expect($strict)->toHaveKey('by');
        expect($strict['requests'])->toBe(10);
        expect($strict['per_minute'])->toBe(1);
        expect($strict['by'])->toBe('user');
    });

    it('キャッシュストア設定が存在すること', function () {
        $cache = config('ratelimit.cache');

        expect($cache)->toBeArray();
        expect($cache)->toHaveKey('store');
        expect($cache)->toHaveKey('prefix');
        // テスト環境ではRATELIMIT_CACHE_STORE=arrayが設定されている
        expect($cache['store'])->toBeIn(['redis', 'array']);
        expect($cache['prefix'])->toBe('rate_limit');
    });
});

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
        expect($endpoints)->toHaveKey('login');
        expect($endpoints)->toHaveKey('api');
        expect($endpoints)->toHaveKey('public');
        expect($endpoints)->toHaveKey('internal');
        expect($endpoints)->toHaveKey('webhook');
    });

    it('ログインエンドポイントのレート制限設定が正しいこと', function () {
        $login = config('ratelimit.endpoints.login');

        expect($login)->toBeArray();
        expect($login)->toHaveKey('requests');
        expect($login)->toHaveKey('per_minute');
        expect($login)->toHaveKey('by');
        expect($login['requests'])->toBe(5);
        expect($login['per_minute'])->toBe(1);
        expect($login['by'])->toBe('ip');
    });

    it('APIエンドポイントのレート制限設定が正しいこと', function () {
        $api = config('ratelimit.endpoints.api');

        expect($api)->toBeArray();
        expect($api['requests'])->toBe(1000);
        expect($api['per_minute'])->toBe(1);
        expect($api['by'])->toBe('user');
    });

    it('公開APIエンドポイントのレート制限設定が正しいこと', function () {
        $public = config('ratelimit.endpoints.public');

        expect($public)->toBeArray();
        expect($public['requests'])->toBe(100);
        expect($public['per_minute'])->toBe(1);
        expect($public['by'])->toBe('ip');
    });

    it('内部APIエンドポイントのレート制限設定が正しいこと', function () {
        $internal = config('ratelimit.endpoints.internal');

        expect($internal)->toBeArray();
        expect($internal['requests'])->toBe(5000);
        expect($internal['per_minute'])->toBe(1);
        expect($internal['by'])->toBe('user');
    });

    it('Webhookエンドポイントのレート制限設定が正しいこと', function () {
        $webhook = config('ratelimit.endpoints.webhook');

        expect($webhook)->toBeArray();
        expect($webhook['requests'])->toBe(200);
        expect($webhook['per_minute'])->toBe(1);
        expect($webhook['by'])->toBe('token');
    });

    it('キャッシュストア設定が存在すること', function () {
        $cache = config('ratelimit.cache');

        expect($cache)->toBeArray();
        expect($cache)->toHaveKey('store');
        expect($cache)->toHaveKey('prefix');
        expect($cache['store'])->toBe('redis');
        expect($cache['prefix'])->toBe('rate_limit');
    });
});

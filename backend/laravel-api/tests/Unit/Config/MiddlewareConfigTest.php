<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;

/**
 * ミドルウェア共通設定ファイルのテスト
 *
 * Requirements: 13.5
 */
describe('MiddlewareConfig', function () {
    it('middleware設定ファイルが存在すること', function () {
        $config = config('middleware');

        expect($config)->not->toBeNull('middleware設定ファイルが読み込まれること');
    });

    it('キャッシュヘッダー設定が存在すること', function () {
        $config = config('middleware.cache');

        expect($config)->toBeArray('cache設定が配列であること');
        expect($config)->toHaveKey('enabled');
        expect($config)->toHaveKey('ttl');
    });

    it('キャッシュTTL設定がエンドポイント別に定義されていること', function () {
        $ttl = config('middleware.cache.ttl');

        expect($ttl)->toBeArray('ttl設定が配列であること');
        expect($ttl)->toHaveKey('/api/health');
        expect($ttl)->toHaveKey('/api/user');
        expect($ttl['/api/health'])->toBe(60);
        expect($ttl['/api/user'])->toBe(300);
    });

    it('ログローテーション日数設定が存在すること', function () {
        $logRotationDays = config('middleware.log_rotation_days');

        expect($logRotationDays)->toBe(30);
    });

    it('機密フィールド設定が存在すること', function () {
        $sensitiveFields = config('middleware.sensitive_fields');

        expect($sensitiveFields)->toBeArray('sensitive_fields設定が配列であること');
        expect($sensitiveFields)->toContain('password');
        expect($sensitiveFields)->toContain('token');
        expect($sensitiveFields)->toContain('secret');
    });

    it('キャッシュ有効化設定が環境変数から読み込まれること', function () {
        Config::set('middleware.cache.enabled', false);
        expect(config('middleware.cache.enabled'))->toBe(false);

        Config::set('middleware.cache.enabled', true);
        expect(config('middleware.cache.enabled'))->toBe(true);
    });
});

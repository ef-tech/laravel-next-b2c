<?php

declare(strict_types=1);

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
});

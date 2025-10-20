<?php

declare(strict_types=1);

/**
 * パフォーマンス監視設定ファイルのテスト
 *
 * Requirements: 2.6, 2.7, 13.4
 */
describe('MonitoringConfig', function () {
    it('monitoring設定ファイルが存在すること', function () {
        $config = config('monitoring');

        expect($config)->not->toBeNull('monitoring設定ファイルが読み込まれること');
    });

    it('メトリクス設定が存在すること', function () {
        $metrics = config('monitoring.metrics');

        expect($metrics)->toBeArray();
        expect($metrics)->toHaveKey('response_time');
    });

    it('レスポンス時間設定が存在すること', function () {
        $responseTime = config('monitoring.metrics.response_time');

        expect($responseTime)->toBeArray();
        expect($responseTime)->toHaveKey('percentiles');
        expect($responseTime)->toHaveKey('alert_threshold');
    });

    it('パーセンタイル設定が正しいこと', function () {
        $percentiles = config('monitoring.metrics.response_time.percentiles');

        expect($percentiles)->toBeArray();
        expect($percentiles)->toContain(50);
        expect($percentiles)->toContain(90);
        expect($percentiles)->toContain(95);
        expect($percentiles)->toContain(99);
    });

    it('アラート閾値が設定されていること', function () {
        $threshold = config('monitoring.metrics.response_time.alert_threshold');

        expect($threshold)->toBe(200);
    });
});

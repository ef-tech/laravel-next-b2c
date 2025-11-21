<?php

declare(strict_types=1);

use Ddd\Infrastructure\Http\Presenters\V1\HealthPresenter;

describe('HealthPresenter', function () {
    test('正常なヘルスチェックレスポンスを生成する', function (): void {
        $timestamp = now();

        $result = HealthPresenter::present($timestamp);

        expect($result)->toBeArray()
            ->and($result)->toHaveKey('status', 'ok')
            ->and($result)->toHaveKey('timestamp')
            ->and($result['timestamp'])->toBe($timestamp->utc()->toIso8601String());
    });

    test('タイムスタンプが正しくISO8601 UTC形式に変換される', function (): void {
        $frozenTime = $this->freezeTimeAt('2021-01-01 00:00:00');

        $result = HealthPresenter::present($frozenTime);

        $this->assertIso8601Timestamp($result['timestamp']);
        expect($result['timestamp'])->toBe('2021-01-01T00:00:00+00:00');
    });
});

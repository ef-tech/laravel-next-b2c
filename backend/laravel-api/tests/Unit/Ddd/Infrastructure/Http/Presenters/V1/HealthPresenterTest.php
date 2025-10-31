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
            ->and($result['timestamp'])->toBe($timestamp->toIso8601String());
    });

    test('タイムスタンプが正しくISO8601形式に変換される', function (): void {
        $timestamp = now()->setTimestamp(1609459200); // 2021-01-01 00:00:00 UTC

        $result = HealthPresenter::present($timestamp);

        expect($result['timestamp'])->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/');
    });
});

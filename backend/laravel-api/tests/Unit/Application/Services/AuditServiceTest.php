<?php

declare(strict_types=1);

use Ddd\Application\Shared\Services\Audit\AuditService;

/**
 * AuditService ポートのテスト
 *
 * Requirements: 6.3, 15.4
 */
describe('AuditService', function () {
    it('recordEventメソッドが存在すること', function () {
        // AuditServiceインターフェースが正しく定義されているか確認
        expect(interface_exists(AuditService::class))
            ->toBeTrue('AuditServiceインターフェースが存在すること');

        expect(method_exists(AuditService::class, 'recordEvent'))
            ->toBeTrue('recordEventメソッドが定義されていること');
    });

    it('recordEventメソッドがarray型を受け取りvoidを返すこと', function () {
        $reflection = new ReflectionMethod(AuditService::class, 'recordEvent');

        // パラメータ数の確認
        expect($reflection->getParameters())
            ->toHaveCount(1, 'パラメータは1つであること');

        // 第1パラメータの型確認
        $firstParam = $reflection->getParameters()[0];
        expect($firstParam->getName())->toBe('event', '第1パラメータ名はeventであること');
        expect($firstParam->getType())->not->toBeNull('第1パラメータに型が指定されていること');
        expect($firstParam->getType()->getName())->toBe('array', '第1パラメータはarray型であること');

        // 戻り値の型確認
        expect($reflection->getReturnType())->not->toBeNull('戻り値の型が指定されていること');
        expect($reflection->getReturnType()->getName())->toBe('void', '戻り値はvoid型であること');
    });
});

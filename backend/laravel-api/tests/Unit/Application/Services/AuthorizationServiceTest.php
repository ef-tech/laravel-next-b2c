<?php

declare(strict_types=1);

use Ddd\Application\Shared\Services\Authorization\AuthorizationService;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * AuthorizationService ポートのテスト
 *
 * Requirements: 5.2, 5.3, 5.7, 15.3
 */
describe('AuthorizationService', function () {
    it('authorizeメソッドが存在すること', function () {
        // AuthorizationServiceインターフェースが正しく定義されているか確認
        expect(interface_exists(AuthorizationService::class))
            ->toBeTrue('AuthorizationServiceインターフェースが存在すること');

        expect(method_exists(AuthorizationService::class, 'authorize'))
            ->toBeTrue('authorizeメソッドが定義されていること');
    });

    it('authorizeメソッドがAuthenticatable型とstring型を受け取りbool型を返すこと', function () {
        $reflection = new ReflectionMethod(AuthorizationService::class, 'authorize');

        // パラメータ数の確認
        expect($reflection->getParameters())
            ->toHaveCount(2, 'パラメータは2つであること');

        // 第1パラメータの型確認
        $firstParam = $reflection->getParameters()[0];
        expect($firstParam->getName())->toBe('user', '第1パラメータ名はuserであること');
        expect($firstParam->getType())->not->toBeNull('第1パラメータに型が指定されていること');
        expect($firstParam->getType()->getName())->toBe(Authenticatable::class, '第1パラメータはAuthenticatable型であること');

        // 第2パラメータの型確認
        $secondParam = $reflection->getParameters()[1];
        expect($secondParam->getName())->toBe('permission', '第2パラメータ名はpermissionであること');
        expect($secondParam->getType())->not->toBeNull('第2パラメータに型が指定されていること');
        expect($secondParam->getType()->getName())->toBe('string', '第2パラメータはstring型であること');

        // 戻り値の型確認
        expect($reflection->getReturnType())->not->toBeNull('戻り値の型が指定されていること');
        expect($reflection->getReturnType()->getName())->toBe('bool', '戻り値はbool型であること');
    });
});

<?php

declare(strict_types=1);

use App\Models\User;
use Ddd\Application\Shared\Services\Authorization\AuthorizationService;
use Ddd\Infrastructure\Persistence\Services\LaravelAuthorizationService;

/**
 * LaravelAuthorizationService 実装のテスト
 *
 * Requirements: 5.2, 6.3, 15.2
 */
describe('LaravelAuthorizationService', function () {
    it('AuthorizationServiceインターフェースを実装していること', function () {
        $service = new LaravelAuthorizationService;

        expect($service)->toBeInstanceOf(AuthorizationService::class);
    });

    it('admin権限を持つユーザーがadmin権限チェックで承認されること', function () {
        $user = new User;
        $user->email = 'admin@example.com';

        $service = new LaravelAuthorizationService;

        expect($service->authorize($user, 'admin'))->toBeTrue();
    });

    it('admin権限を持たないユーザーがadmin権限チェックで拒否されること', function () {
        $user = new User;
        $user->email = 'user@example.com';

        $service = new LaravelAuthorizationService;

        expect($service->authorize($user, 'admin'))->toBeFalse();
    });

    it('一般ユーザーがuser権限チェックで承認されること', function () {
        $user = new User;
        $user->email = 'user@example.com';

        $service = new LaravelAuthorizationService;

        expect($service->authorize($user, 'user'))->toBeTrue();
    });
});

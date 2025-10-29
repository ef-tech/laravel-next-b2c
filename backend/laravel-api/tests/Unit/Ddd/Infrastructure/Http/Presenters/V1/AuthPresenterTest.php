<?php

declare(strict_types=1);

use App\Models\User;
use Ddd\Infrastructure\Http\Presenters\V1\AuthPresenter;

describe('AuthPresenter', function () {
    test('ログイン成功レスポンスを正常に生成する', function (): void {
        $user = new User;
        $user->id = 1;
        $user->name = 'Test User';
        $user->email = 'test@example.com';

        $token = 'plain-text-token-value';

        $result = AuthPresenter::presentLogin($user, $token);

        expect($result)->toBeArray()
            ->and($result)->toHaveKey('token', 'plain-text-token-value')
            ->and($result)->toHaveKey('user')
            ->and($result)->toHaveKey('token_type', 'Bearer')
            ->and($result['user'])->toHaveKey('id', 1)
            ->and($result['user'])->toHaveKey('name', 'Test User')
            ->and($result['user'])->toHaveKey('email', 'test@example.com');
    });

    test('ログアウト成功レスポンスを正常に生成する', function (): void {
        $result = AuthPresenter::presentLogout();

        expect($result)->toBeArray()
            ->and($result)->toHaveKey('message', 'Logged out successfully');
    });

    test('ログインエラーレスポンスを正常に生成する', function (): void {
        $result = AuthPresenter::presentLoginError();

        expect($result)->toBeArray()
            ->and($result)->toHaveKey('message', 'Invalid credentials');
    });

    test('token_typeが常にBearerである', function (): void {
        $user = new User;
        $user->id = 999;
        $user->name = 'Bearer Test';
        $user->email = 'bearer@example.com';

        $result = AuthPresenter::presentLogin($user, 'any-token');

        expect($result['token_type'])->toBe('Bearer');
    });
});

<?php

declare(strict_types=1);

use App\Models\User;
use Ddd\Infrastructure\Http\Presenters\V1\UserPresenter;

describe('UserPresenter', function () {
    test('Userモデルから正常にV1レスポンスを生成する', function (): void {
        $user = new User;
        $user->id = 1;
        $user->name = 'Test User';
        $user->email = 'test@example.com';

        $result = UserPresenter::present($user);

        expect($result)->toBeArray()
            ->and($result)->toHaveKey('id', 1)
            ->and($result)->toHaveKey('name', 'Test User')
            ->and($result)->toHaveKey('email', 'test@example.com');
    });

    test('bigint主キーが正しく文字列として返される', function (): void {
        $user = new User;
        $user->id = 9223372036854775807; // max bigint value
        $user->name = 'Big ID User';
        $user->email = 'bigid@example.com';

        $result = UserPresenter::present($user);

        expect($result['id'])->toBe(9223372036854775807);
    });

    test('必須フィールドが全て含まれている', function (): void {
        $user = new User;
        $user->id = 999;
        $user->name = 'Required Fields User';
        $user->email = 'required@example.com';

        $result = UserPresenter::present($user);

        expect($result)->toHaveKeys(['id', 'name', 'email'])
            ->and(count($result))->toBe(3);
    });
});

<?php

declare(strict_types=1);

use App\Http\Requests\Api\V1\RegisterUserRequest;
use Illuminate\Support\Facades\Validator;

describe('V1 RegisterUserRequest', function () {
    // Unit testではDB依存のunique検証を除外する
    $getRulesWithoutUnique = function () {
        $request = new RegisterUserRequest;
        $rules = $request->rules();
        // emailルールからuniqueを除去
        $rules['email'] = array_filter($rules['email'], fn ($rule) => ! is_string($rule) || ! str_starts_with($rule, 'unique:'));

        return $rules;
    };

    test('有効なリクエストデータがバリデーションを通過する', function () use ($getRulesWithoutUnique): void {
        $data = [
            'email' => 'test@example.com',
            'name' => 'Test User',
            'password' => 'SecurePassword123',
        ];

        $validator = Validator::make($data, $getRulesWithoutUnique());

        expect($validator->passes())->toBeTrue();
    });

    test('emailが必須である', function () use ($getRulesWithoutUnique): void {
        $data = [
            'name' => 'Test User',
        ];

        $validator = Validator::make($data, $getRulesWithoutUnique());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('email'))->toBeTrue();
    });

    test('emailが有効なメールアドレス形式でなければならない', function () use ($getRulesWithoutUnique): void {
        $data = [
            'email' => 'invalid-email',
            'name' => 'Test User',
            'password' => 'Password123',
        ];

        $validator = Validator::make($data, $getRulesWithoutUnique());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('email'))->toBeTrue();
    });

    test('emailは255文字以下でなければならない', function () use ($getRulesWithoutUnique): void {
        $data = [
            'email' => str_repeat('a', 244).'@example.com', // 256文字
            'name' => 'Test User',
            'password' => 'Password123',
        ];

        $validator = Validator::make($data, $getRulesWithoutUnique());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('email'))->toBeTrue();
    });

    test('nameが必須である', function () use ($getRulesWithoutUnique): void {
        $data = [
            'email' => 'test@example.com',
            'password' => 'Password123',
        ];

        $validator = Validator::make($data, $getRulesWithoutUnique());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('name'))->toBeTrue();
    });

    test('nameは2文字以上でなければならない', function () use ($getRulesWithoutUnique): void {
        $data = [
            'email' => 'test@example.com',
            'name' => 'A',
            'password' => 'Password123',
        ];

        $validator = Validator::make($data, $getRulesWithoutUnique());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('name'))->toBeTrue();
    });

    test('nameは255文字以下でなければならない', function () use ($getRulesWithoutUnique): void {
        $data = [
            'email' => 'test@example.com',
            'name' => str_repeat('a', 256),
            'password' => 'Password123',
        ];

        $validator = Validator::make($data, $getRulesWithoutUnique());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('name'))->toBeTrue();
    });

    test('passwordが必須である', function () use ($getRulesWithoutUnique): void {
        $data = [
            'email' => 'test@example.com',
            'name' => 'Test User',
        ];

        $validator = Validator::make($data, $getRulesWithoutUnique());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('password'))->toBeTrue();
    });

    test('passwordは8文字以上でなければならない', function () use ($getRulesWithoutUnique): void {
        $data = [
            'email' => 'test@example.com',
            'name' => 'Test User',
            'password' => '1234567', // 7文字
        ];

        $validator = Validator::make($data, $getRulesWithoutUnique());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('password'))->toBeTrue();
    });

    test('passwordは255文字以下でなければならない', function () use ($getRulesWithoutUnique): void {
        $data = [
            'email' => 'test@example.com',
            'name' => 'Test User',
            'password' => str_repeat('a', 256),
        ];

        $validator = Validator::make($data, $getRulesWithoutUnique());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('password'))->toBeTrue();
    });

    test('authorizeメソッドがtrueを返す', function (): void {
        $request = new RegisterUserRequest;

        expect($request->authorize())->toBeTrue();
    });
});

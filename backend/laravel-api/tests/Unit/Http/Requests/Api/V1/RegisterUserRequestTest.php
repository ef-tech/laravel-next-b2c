<?php

declare(strict_types=1);

use App\Http\Requests\Api\V1\RegisterUserRequest;
use Illuminate\Support\Facades\Validator;

describe('V1 RegisterUserRequest', function () {
    test('有効なリクエストデータがバリデーションを通過する', function (): void {
        $request = new RegisterUserRequest;
        $data = [
            'email' => 'test@example.com',
            'name' => 'Test User',
        ];

        $validator = Validator::make($data, $request->rules());

        expect($validator->passes())->toBeTrue();
    });

    test('emailが必須である', function (): void {
        $request = new RegisterUserRequest;
        $data = [
            'name' => 'Test User',
        ];

        $validator = Validator::make($data, $request->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('email'))->toBeTrue();
    });

    test('emailが有効なメールアドレス形式でなければならない', function (): void {
        $request = new RegisterUserRequest;
        $data = [
            'email' => 'invalid-email',
            'name' => 'Test User',
        ];

        $validator = Validator::make($data, $request->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('email'))->toBeTrue();
    });

    test('emailは255文字以下でなければならない', function (): void {
        $request = new RegisterUserRequest;
        $data = [
            'email' => str_repeat('a', 244).'@example.com', // 256文字
            'name' => 'Test User',
        ];

        $validator = Validator::make($data, $request->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('email'))->toBeTrue();
    });

    test('nameが必須である', function (): void {
        $request = new RegisterUserRequest;
        $data = [
            'email' => 'test@example.com',
        ];

        $validator = Validator::make($data, $request->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('name'))->toBeTrue();
    });

    test('nameは2文字以上でなければならない', function (): void {
        $request = new RegisterUserRequest;
        $data = [
            'email' => 'test@example.com',
            'name' => 'A',
        ];

        $validator = Validator::make($data, $request->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('name'))->toBeTrue();
    });

    test('nameは255文字以下でなければならない', function (): void {
        $request = new RegisterUserRequest;
        $data = [
            'email' => 'test@example.com',
            'name' => str_repeat('a', 256),
        ];

        $validator = Validator::make($data, $request->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('name'))->toBeTrue();
    });

    test('authorizeメソッドがtrueを返す', function (): void {
        $request = new RegisterUserRequest;

        expect($request->authorize())->toBeTrue();
    });
});

<?php

declare(strict_types=1);

use App\Http\Requests\Api\V1\LoginRequest;
use Illuminate\Support\Facades\Validator;

describe('V1 LoginRequest', function () {
    test('有効なリクエストデータがバリデーションを通過する', function (): void {
        $request = new LoginRequest;
        $data = [
            'email' => 'test@example.com',
            'password' => 'password123',
        ];

        $validator = Validator::make($data, $request->rules());

        expect($validator->passes())->toBeTrue();
    });

    test('emailが必須である', function (): void {
        $request = new LoginRequest;
        $data = [
            'password' => 'password123',
        ];

        $validator = Validator::make($data, $request->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('email'))->toBeTrue();
    });

    test('emailが有効なメールアドレス形式でなければならない', function (): void {
        $request = new LoginRequest;
        $data = [
            'email' => 'invalid-email',
            'password' => 'password123',
        ];

        $validator = Validator::make($data, $request->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('email'))->toBeTrue();
    });

    test('passwordが必須である', function (): void {
        $request = new LoginRequest;
        $data = [
            'email' => 'test@example.com',
        ];

        $validator = Validator::make($data, $request->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('password'))->toBeTrue();
    });

    test('passwordは8文字以上でなければならない', function (): void {
        $request = new LoginRequest;
        $data = [
            'email' => 'test@example.com',
            'password' => 'short',
        ];

        $validator = Validator::make($data, $request->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('password'))->toBeTrue();
    });

    test('authorizeメソッドがtrueを返す', function (): void {
        $request = new LoginRequest;

        expect($request->authorize())->toBeTrue();
    });
});

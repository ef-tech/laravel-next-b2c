<?php

declare(strict_types=1);

use Ddd\Domain\Admin\Exceptions\AccountDisabledException;
use Ddd\Domain\Admin\Exceptions\AdminNotFoundException;
use Ddd\Domain\Admin\Exceptions\InvalidCredentialsException;
use Illuminate\Support\Facades\Route;

beforeEach(function (): void {
    // テスト用ルートを登録
    Route::get('/test/invalid-credentials', function () {
        throw new InvalidCredentialsException;
    });

    Route::get('/test/account-disabled', function () {
        throw new AccountDisabledException;
    });

    Route::get('/test/admin-not-found', function () {
        throw new AdminNotFoundException;
    });

    Route::post('/test/validation', function (\Illuminate\Http\Request $request) {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:8',
        ]);

        return response()->json(['success' => true]);
    });
});

test('InvalidCredentialsException returns 401 with unified error response format', function (): void {
    $response = $this->getJson('/test/invalid-credentials');

    $response->assertStatus(401)
        ->assertJsonStructure([
            'code',
            'message',
            'trace_id',
        ])
        ->assertJson([
            'code' => 'AUTH.INVALID_CREDENTIALS',
            'message' => 'メールアドレスまたはパスワードが正しくありません',
        ]);

    // trace_idが存在することを確認
    expect($response->json('trace_id'))->not->toBeEmpty();
});

test('AccountDisabledException returns 403 with unified error response format', function (): void {
    $response = $this->getJson('/test/account-disabled');

    $response->assertStatus(403)
        ->assertJsonStructure([
            'code',
            'message',
            'trace_id',
        ])
        ->assertJson([
            'code' => 'AUTH.ACCOUNT_DISABLED',
            'message' => 'アカウントが無効化されています',
        ]);

    expect($response->json('trace_id'))->not->toBeEmpty();
});

test('AdminNotFoundException returns 404 with unified error response format', function (): void {
    $response = $this->getJson('/test/admin-not-found');

    $response->assertStatus(404)
        ->assertJsonStructure([
            'code',
            'message',
            'trace_id',
        ])
        ->assertJson([
            'code' => 'ADMIN_NOT_FOUND',
            'message' => 'Admin not found',
        ]);

    expect($response->json('trace_id'))->not->toBeEmpty();
});

test('ValidationException returns 422 with unified error response format including field errors', function (): void {
    $response = $this->postJson('/test/validation', []);

    $response->assertStatus(422)
        ->assertJsonStructure([
            'code',
            'message',
            'errors',
            'trace_id',
        ])
        ->assertJson([
            'code' => 'VALIDATION_ERROR',
            'message' => '入力内容に誤りがあります',
        ]);

    // フィールド別エラーが存在することを確認
    expect($response->json('errors'))->toHaveKeys(['email', 'password']);
    expect($response->json('trace_id'))->not->toBeEmpty();
});

test('Exception handler uses X-Request-Id header as trace_id when present', function (): void {
    $requestId = '550e8400-e29b-41d4-a716-446655440000';

    $response = $this->withHeader('X-Request-Id', $requestId)
        ->getJson('/test/invalid-credentials');

    $response->assertStatus(401)
        ->assertJson([
            'trace_id' => $requestId,
        ]);
});

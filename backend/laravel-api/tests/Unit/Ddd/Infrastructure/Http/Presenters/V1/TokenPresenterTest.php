<?php

declare(strict_types=1);

use Ddd\Infrastructure\Http\Presenters\V1\TokenPresenter;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\NewAccessToken;
use Laravel\Sanctum\PersonalAccessToken;

describe('TokenPresenter', function () {
    test('新規作成トークンから正常にV1レスポンスを生成する', function (): void {
        $accessToken = new PersonalAccessToken;
        $accessToken->name = 'API Token';
        $accessToken->created_at = Carbon::parse('2024-01-01 00:00:00');

        $newToken = new NewAccessToken($accessToken, 'plain-text-token-value');

        $result = TokenPresenter::presentNewToken($newToken);

        expect($result)->toBeArray()
            ->and($result)->toHaveKey('token', 'plain-text-token-value')
            ->and($result)->toHaveKey('name', 'API Token')
            ->and($result)->toHaveKey('created_at')
            ->and($result['created_at'])->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/');
    });

    test('既存トークンから正常にV1レスポンスを生成する', function (): void {
        $token = new PersonalAccessToken;
        $token->id = 1;
        $token->name = 'Existing Token';
        $token->created_at = Carbon::parse('2024-01-01 00:00:00');
        $token->last_used_at = Carbon::parse('2024-01-02 12:00:00');

        $result = TokenPresenter::presentToken($token);

        expect($result)->toBeArray()
            ->and($result)->toHaveKey('id', 1)
            ->and($result)->toHaveKey('name', 'Existing Token')
            ->and($result)->toHaveKey('created_at')
            ->and($result)->toHaveKey('last_used_at')
            ->and($result['last_used_at'])->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/');
    });

    test('last_used_atがnullの場合正しく処理される', function (): void {
        $token = new PersonalAccessToken;
        $token->id = 2;
        $token->name = 'Unused Token';
        $token->created_at = Carbon::parse('2024-01-01 00:00:00');
        $token->last_used_at = null;

        $result = TokenPresenter::presentToken($token);

        expect($result)->toBeArray()
            ->and($result)->toHaveKey('last_used_at', null);
    });

    test('トークンリストから正常にV1レスポンス配列を生成する', function (): void {
        $token1 = new PersonalAccessToken;
        $token1->id = 1;
        $token1->name = 'Token 1';
        $token1->created_at = Carbon::parse('2024-01-01 00:00:00');
        $token1->last_used_at = null;

        $token2 = new PersonalAccessToken;
        $token2->id = 2;
        $token2->name = 'Token 2';
        $token2->created_at = Carbon::parse('2024-01-02 00:00:00');
        $token2->last_used_at = Carbon::parse('2024-01-03 00:00:00');

        $tokens = collect([$token1, $token2]);

        $result = TokenPresenter::presentTokenList($tokens);

        expect($result)->toBeArray()
            ->and($result)->toHaveKey('tokens')
            ->and($result['tokens'])->toHaveCount(2)
            ->and($result['tokens'][0])->toHaveKey('id', 1)
            ->and($result['tokens'][1])->toHaveKey('id', 2);
    });

    test('トークン削除成功レスポンスを生成する', function (): void {
        $result = TokenPresenter::presentTokenDeleted();

        expect($result)->toBeArray()
            ->and($result)->toHaveKey('message', 'Token deleted successfully');
    });

    test('全トークン削除成功レスポンスを生成する', function (): void {
        $result = TokenPresenter::presentAllTokensDeleted();

        expect($result)->toBeArray()
            ->and($result)->toHaveKey('message', 'All tokens deleted successfully');
    });

    test('トークン未発見エラーレスポンスを生成する', function (): void {
        $result = TokenPresenter::presentTokenNotFound();

        expect($result)->toBeArray()
            ->and($result)->toHaveKey('message', 'Token not found');
    });
});

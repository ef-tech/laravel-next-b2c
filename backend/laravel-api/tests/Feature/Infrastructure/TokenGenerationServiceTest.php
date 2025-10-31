<?php

declare(strict_types=1);

use App\Models\User;
use Ddd\Domain\User\ValueObjects\UserId;
use Ddd\Infrastructure\Services\TokenGenerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('TokenGenerationService', function () {
    beforeEach(function () {
        $this->service = new TokenGenerationService;
    });

    describe('generateToken', function () {
        test('正常にトークンとユーザーモデルを生成する', function (): void {
            $user = User::factory()->create();
            $userId = UserId::fromInt($user->id);

            $result = $this->service->generateToken($userId);

            expect($result)->toBeArray()
                ->and($result)->toHaveKeys(['token', 'user'])
                ->and($result['token'])->toBeString()
                ->and($result['user'])->toBeInstanceOf(User::class)
                ->and($result['user']->id)->toBe($user->id);
        });

        test('カスタムトークン名を指定できる', function (): void {
            $user = User::factory()->create();
            $userId = UserId::fromInt($user->id);

            $result = $this->service->generateToken($userId, 'Custom Token');

            expect($result['token'])->toBeString();

            // トークン名が正しく設定されているか確認
            $token = $user->tokens()->where('name', 'Custom Token')->first();
            expect($token)->not->toBeNull()
                ->and($token->name)->toBe('Custom Token');
        });

        test('存在しないユーザーIDの場合は例外をスローする', function (): void {
            $userId = UserId::fromInt(999999);

            expect(fn () => $this->service->generateToken($userId))
                ->toThrow(RuntimeException::class, 'User not found: 999999');
        });
    });

    describe('revokeToken', function () {
        test('指定したトークンを削除する', function (): void {
            $user = User::factory()->create();
            $token = $user->createToken('Test Token');
            $userId = UserId::fromInt($user->id);

            expect($user->tokens()->count())->toBe(1);

            $this->service->revokeToken($userId, (string) $token->accessToken->id);

            expect($user->tokens()->count())->toBe(0);
        });

        test('存在しないユーザーIDの場合は例外をスローする', function (): void {
            $userId = UserId::fromInt(999999);

            expect(fn () => $this->service->revokeToken($userId, '1'))
                ->toThrow(RuntimeException::class, 'User not found: 999999');
        });
    });

    describe('revokeAllTokens', function () {
        test('全トークンを削除する', function (): void {
            $user = User::factory()->create();
            $user->createToken('Token 1');
            $user->createToken('Token 2');
            $user->createToken('Token 3');
            $userId = UserId::fromInt($user->id);

            expect($user->tokens()->count())->toBe(3);

            $this->service->revokeAllTokens($userId);

            expect($user->tokens()->count())->toBe(0);
        });

        test('存在しないユーザーIDの場合は例外をスローする', function (): void {
            $userId = UserId::fromInt(999999);

            expect(fn () => $this->service->revokeAllTokens($userId))
                ->toThrow(RuntimeException::class, 'User not found: 999999');
        });
    });
});

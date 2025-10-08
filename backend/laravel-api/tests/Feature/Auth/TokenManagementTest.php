<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('POST /api/tokens', function () {
    it('creates new token with default name', function () {
        // Arrange
        $user = User::factory()->create();
        $existingToken = $user->createToken('Existing Token')->plainTextToken;

        // Act
        $response = $this->withHeader('Authorization', "Bearer {$existingToken}")
            ->postJson('/api/tokens');

        // Assert
        $response->assertCreated()
            ->assertJsonStructure([
                'token',
                'name',
                'created_at',
            ])
            ->assertJson([
                'name' => 'API Token',
            ]);

        expect($response->json('token'))->toBeString()->not->toBeEmpty();
        expect($user->tokens()->count())->toBe(2);
    });

    it('creates new token with custom name', function () {
        // Arrange
        $user = User::factory()->create();
        $token = $user->createToken('Test Token')->plainTextToken;

        // Act
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/tokens', [
                'name' => 'Mobile App Token',
            ]);

        // Assert
        $response->assertCreated()
            ->assertJson([
                'name' => 'Mobile App Token',
            ]);

        expect($user->tokens()->where('name', 'Mobile App Token')->count())->toBe(1);
    });

    it('returns 401 when unauthenticated', function () {
        // Act
        $response = $this->postJson('/api/tokens');

        // Assert
        $response->assertUnauthorized();
    });
});

describe('GET /api/tokens', function () {
    it('returns list of user tokens', function () {
        // Arrange
        $user = User::factory()->create();
        $token1 = $user->createToken('Token 1')->plainTextToken;
        $token2 = $user->createToken('Token 2');

        // Act
        $response = $this->withHeader('Authorization', "Bearer {$token1}")
            ->getJson('/api/tokens');

        // Assert
        $response->assertOk()
            ->assertJsonStructure([
                'tokens' => [
                    '*' => ['id', 'name', 'created_at', 'last_used_at'],
                ],
            ]);

        expect($response->json('tokens'))->toHaveCount(2);
        expect($response->json('tokens.0'))->not->toHaveKey('token');
    });

    it('returns empty array when user has no tokens except current', function () {
        // Arrange
        $user = User::factory()->create();
        $token = $user->createToken('Only Token')->plainTextToken;

        // Act
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/tokens');

        // Assert
        $response->assertOk();
        expect($response->json('tokens'))->toHaveCount(1);
    });

    it('returns 401 when unauthenticated', function () {
        // Act
        $response = $this->getJson('/api/tokens');

        // Assert
        $response->assertUnauthorized();
    });
});

describe('DELETE /api/tokens/{id}', function () {
    it('deletes specific token', function () {
        // Arrange
        $user = User::factory()->create();
        $token1 = $user->createToken('Token 1')->plainTextToken;
        $token2 = $user->createToken('Token 2');
        $tokenId = $token2->accessToken->id;

        expect($user->tokens()->count())->toBe(2);

        // Act
        $response = $this->withHeader('Authorization', "Bearer {$token1}")
            ->deleteJson("/api/tokens/{$tokenId}");

        // Assert
        $response->assertOk()
            ->assertJson([
                'message' => 'Token deleted successfully',
            ]);

        expect($user->tokens()->count())->toBe(1);
        expect($user->tokens()->where('id', $tokenId)->count())->toBe(0);
    });

    it('returns 404 when token does not exist', function () {
        // Arrange
        $user = User::factory()->create();
        $token = $user->createToken('Test Token')->plainTextToken;

        // Act
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson('/api/tokens/99999');

        // Assert
        $response->assertNotFound()
            ->assertJson([
                'message' => 'Token not found',
            ]);
    });

    it('prevents deleting other users tokens', function () {
        // Arrange
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $token1 = $user1->createToken('User 1 Token')->plainTextToken;
        $token2 = $user2->createToken('User 2 Token');
        $token2Id = $token2->accessToken->id;

        // Act
        $response = $this->withHeader('Authorization', "Bearer {$token1}")
            ->deleteJson("/api/tokens/{$token2Id}");

        // Assert
        $response->assertNotFound();
        expect($user2->tokens()->count())->toBe(1);
    });

    it('returns 401 when unauthenticated', function () {
        // Act
        $response = $this->deleteJson('/api/tokens/1');

        // Assert
        $response->assertUnauthorized();
    });
});

describe('DELETE /api/tokens', function () {
    it('deletes all user tokens except current', function () {
        // Arrange
        $user = User::factory()->create();
        $currentToken = $user->createToken('Current Token')->plainTextToken;
        $user->createToken('Token 2');
        $user->createToken('Token 3');

        expect($user->tokens()->count())->toBe(3);

        // Act
        $response = $this->withHeader('Authorization', "Bearer {$currentToken}")
            ->deleteJson('/api/tokens');

        // Assert
        $response->assertOk()
            ->assertJson([
                'message' => 'All tokens deleted successfully',
            ]);

        expect($user->tokens()->count())->toBe(1);
    });

    it('returns 401 when unauthenticated', function () {
        // Act
        $response = $this->deleteJson('/api/tokens');

        // Assert
        $response->assertUnauthorized();
    });
});

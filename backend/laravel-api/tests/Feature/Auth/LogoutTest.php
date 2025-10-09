<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('POST /api/logout', function () {
    it('logs out authenticated user and deletes token', function () {
        // Arrange
        $user = User::factory()->create();
        $token = $user->createToken('Test Token')->plainTextToken;

        // Act
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/logout');

        // Assert
        $response->assertOk()
            ->assertJson([
                'message' => 'Logged out successfully',
            ]);

        // Verify token is deleted
        expect($user->tokens()->count())->toBe(0);
    });

    it('returns 401 when unauthenticated', function () {
        // Act
        $response = $this->postJson('/api/logout');

        // Assert
        $response->assertUnauthorized();
    });

    it('returns 401 with invalid token', function () {
        // Act
        $response = $this->withHeader('Authorization', 'Bearer invalid-token')
            ->postJson('/api/logout');

        // Assert
        $response->assertUnauthorized();
    });

    it('deletes only current token when multiple tokens exist', function () {
        // Arrange
        $user = User::factory()->create();
        $token1 = $user->createToken('Token 1')->plainTextToken;
        $token2 = $user->createToken('Token 2')->plainTextToken;

        expect($user->tokens()->count())->toBe(2);

        // Act - logout with token1
        $response = $this->withHeader('Authorization', "Bearer {$token1}")
            ->postJson('/api/logout');

        // Assert
        $response->assertOk();
        expect($user->tokens()->count())->toBe(1);
        expect($user->tokens()->first()->name)->toBe('Token 2');
    });
});

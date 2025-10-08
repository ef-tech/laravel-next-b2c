<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('GET /api/user', function () {
    it('returns authenticated user information', function () {
        // Arrange
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);
        $token = $user->createToken('Test Token')->plainTextToken;

        // Act
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/user');

        // Assert
        $response->assertOk()
            ->assertJson([
                'id' => $user->id,
                'name' => 'John Doe',
                'email' => 'john@example.com',
            ]);

        expect($response->json())->not->toHaveKey('password');
        expect($response->json())->not->toHaveKey('remember_token');
    });

    it('returns 401 when unauthenticated', function () {
        // Act
        $response = $this->getJson('/api/user');

        // Assert
        $response->assertUnauthorized();
    });

    it('returns 401 with invalid token', function () {
        // Act
        $response = $this->withHeader('Authorization', 'Bearer invalid-token')
            ->getJson('/api/user');

        // Assert
        $response->assertUnauthorized();
    });

    it('returns 401 with expired or deleted token', function () {
        // Arrange
        $user = User::factory()->create();
        $token = $user->createToken('Test Token')->plainTextToken;

        // Delete the token
        $user->tokens()->delete();

        // Act
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/user');

        // Assert
        $response->assertUnauthorized();
    });
});

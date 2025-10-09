<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('POST /api/login', function () {
    it('returns token and user data when credentials are valid', function () {
        // Arrange
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        // Act
        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        // Assert
        $response->assertOk()
            ->assertJsonStructure([
                'token',
                'user' => ['id', 'name', 'email'],
                'token_type',
            ])
            ->assertJson([
                'token_type' => 'Bearer',
                'user' => [
                    'id' => $user->id,
                    'email' => 'test@example.com',
                ],
            ]);

        expect($response->json('token'))->toBeString()->not->toBeEmpty();
    });

    it('returns 401 when email is invalid', function () {
        // Arrange
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        // Act
        $response = $this->postJson('/api/login', [
            'email' => 'invalid@example.com',
            'password' => 'password123',
        ]);

        // Assert
        $response->assertUnauthorized()
            ->assertJson([
                'message' => 'Invalid credentials',
            ]);
    });

    it('returns 401 when password is invalid', function () {
        // Arrange
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        // Act
        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        // Assert
        $response->assertUnauthorized()
            ->assertJson([
                'message' => 'Invalid credentials',
            ]);
    });

    it('returns 422 when email format is invalid', function () {
        // Act
        $response = $this->postJson('/api/login', [
            'email' => 'invalid-email',
            'password' => 'password123',
        ]);

        // Assert
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });

    it('returns 422 when password is too short', function () {
        // Act
        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'short',
        ]);

        // Assert
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    });

    it('returns 422 when email is missing', function () {
        // Act
        $response = $this->postJson('/api/login', [
            'password' => 'password123',
        ]);

        // Assert
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });

    it('returns 422 when password is missing', function () {
        // Act
        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
        ]);

        // Assert
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    });
});

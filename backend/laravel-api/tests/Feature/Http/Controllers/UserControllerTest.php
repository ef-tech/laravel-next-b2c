<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('can register user successfully', function (): void {
    $response = $this->postJson('/api/users', [
        'email' => 'test@example.com',
        'name' => 'Test User',
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['id']);

    $this->assertDatabaseHas('users', [
        'email' => 'test@example.com',
        'name' => 'Test User',
    ]);
});

test('returns 422 when email already exists', function (): void {
    // First registration
    $this->postJson('/api/users', [
        'email' => 'duplicate@example.com',
        'name' => 'First User',
    ]);

    // Attempt duplicate registration
    $response = $this->postJson('/api/users', [
        'email' => 'duplicate@example.com',
        'name' => 'Second User',
    ]);

    $response->assertStatus(422)
        ->assertJson([
            'error' => 'email_already_exists',
        ]);
});

test('returns 422 when email format is invalid', function (): void {
    $response = $this->postJson('/api/users', [
        'email' => 'invalid-email',
        'name' => 'Test User',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('returns 422 when name is too short', function (): void {
    $response = $this->postJson('/api/users', [
        'email' => 'test@example.com',
        'name' => 'A',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});

test('returns 422 when required fields are missing', function (): void {
    $response = $this->postJson('/api/users', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email', 'name']);
});

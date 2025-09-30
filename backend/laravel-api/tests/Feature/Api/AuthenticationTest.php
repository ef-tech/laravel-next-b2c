<?php

declare(strict_types=1);

use App\Models\User;

it('returns profile for authenticated user', function () {
    $user = User::factory()->create([
        'name' => 'Test User',
        'email' => 'test@example.com',
    ]);

    actingAsApi($user);

    $response = $this->getJson('/api/me', jsonHeaders());

    expect($response)
        ->toBeJsonOk()
        ->and($response->json('data.id'))->toBe($user->id)
        ->and($response->json('data.email'))->toBe($user->email);
});

it('rejects unauthenticated access to protected route', function () {
    $this->getJson('/api/me', jsonHeaders())
        ->assertUnauthorized();
});

it('validates token abilities', function () {
    $user = User::factory()->create();

    actingAsApi($user, ['read']);

    $this->getJson('/api/me', jsonHeaders())->assertOk();
    $this->postJson('/api/users', ['name' => 'New User'], jsonHeaders())->assertForbidden();
});

<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('can_generate_api_token_for_user', function () {
    $user = User::factory()->create();

    $token = $user->createToken('test-token');

    expect($token->plainTextToken)->not->toBeNull();
    $this->assertDatabaseHas('personal_access_tokens', [
        'tokenable_id' => $user->id,
        'tokenable_type' => User::class,
        'name' => 'test-token',
    ]);
});

it('can_authenticate_with_sanctum_token', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/user');

    $response->assertStatus(200);
    $response->assertJson([
        'id' => $user->id,
        'email' => $user->email,
    ]);
});

it('cannot_access_protected_route_without_token', function () {
    $response = $this->getJson('/api/user');

    $response->assertStatus(401);
});

it('can_revoke_api_token', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token');

    $user->tokens()->delete();

    $this->assertDatabaseMissing('personal_access_tokens', [
        'tokenable_id' => $user->id,
        'tokenable_type' => User::class,
        'name' => 'test-token',
    ]);
});

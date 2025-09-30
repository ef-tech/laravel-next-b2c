<?php

declare(strict_types=1);

use App\Models\User;

it('creates a resource and returns canonical JSON:API payload', function () {
    $user = User::factory()->create();
    actingAsApi($user);

    $payload = [
        'name' => 'Test Resource',
        'description' => 'Test Description',
    ];

    $response = $this->postJson('/api/resources', $payload, jsonHeaders());

    $response->assertCreated()
        ->assertJsonStructure([
            'data' => ['id', 'name', 'description', 'created_at', 'updated_at'],
        ])
        ->assertJson(fn ($json) => $json->where('data.name', 'Test Resource')
            ->where('data.description', 'Test Description')
            ->has('data.id')
            ->has('data.created_at')
        );
})->skip('API resource endpoint not yet implemented');

it('returns paginated list with metadata', function () {
    User::factory()->count(15)->create();
    $user = User::factory()->create();
    actingAsApi($user);

    $response = $this->getJson('/api/users?page=1&per_page=10', jsonHeaders());

    $response->assertOk()
        ->assertJsonStructure([
            'data' => ['*' => ['id', 'name', 'email']],
            'meta' => ['total', 'per_page', 'current_page', 'last_page'],
            'links' => ['first', 'last', 'prev', 'next'],
        ]);

    expect($response->json('meta.per_page'))->toBe(10)
        ->and(count($response->json('data')))->toBeLessThanOrEqual(10);
})->skip('API users pagination endpoint not yet implemented');

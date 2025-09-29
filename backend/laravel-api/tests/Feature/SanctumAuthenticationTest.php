<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SanctumAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_generate_api_token_for_user(): void
    {
        $user = User::factory()->create();

        $token = $user->createToken('test-token');

        $this->assertNotNull($token->plainTextToken);
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'tokenable_type' => User::class,
            'name' => 'test-token',
        ]);
    }

    public function test_can_authenticate_with_sanctum_token(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/user');

        $response->assertStatus(200);
        $response->assertJson([
            'id' => $user->id,
            'email' => $user->email,
        ]);
    }

    public function test_cannot_access_protected_route_without_token(): void
    {
        $response = $this->getJson('/api/user');

        $response->assertStatus(401);
    }

    public function test_can_revoke_api_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $user->tokens()->delete();

        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'tokenable_type' => User::class,
            'name' => 'test-token',
        ]);
    }
}

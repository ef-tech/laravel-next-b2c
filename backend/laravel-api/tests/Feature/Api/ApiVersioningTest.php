<?php

declare(strict_types=1);

use App\Models\Admin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // Create test user
    $this->user = User::factory()->create([
        'email' => 'user@example.com',
        'password' => 'password',
    ]);
    $this->userToken = $this->user->createToken('user-token')->plainTextToken;

    // Create test admin
    $this->admin = Admin::factory()->create([
        'email' => 'admin@example.com',
        'password' => 'password',
        'role' => 'super_admin',
        'is_active' => true,
    ]);
    $this->adminToken = $this->admin->createToken('admin-token')->plainTextToken;
});

test('Admin v1 login endpoint works correctly', function (): void {
    $response = $this->postJson('/api/v1/admin/login', [
        'email' => 'admin@example.com',
        'password' => 'password',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'token',
            'admin' => ['id', 'name', 'email', 'role', 'is_active'],
        ]);
});

test('Admin v1 dashboard endpoint works correctly', function (): void {
    $response = $this->withHeader('Authorization', "Bearer {$this->adminToken}")
        ->getJson('/api/v1/admin/dashboard');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'admin' => ['id', 'name', 'email', 'role', 'isActive'],
        ]);
});

test('Admin route names have v1 prefix', function (): void {
    expect(route('v1.admin.login'))->toContain('/api/v1/admin/login');
    expect(route('v1.admin.logout'))->toContain('/api/v1/admin/logout');
    expect(route('v1.admin.dashboard'))->toContain('/api/v1/admin/dashboard');
});

test('User v1 login endpoint works correctly', function (): void {
    $response = $this->postJson('/api/v1/user/login', [
        'email' => 'user@example.com',
        'password' => 'password',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'token',
            'user' => ['id', 'name', 'email'],
        ]);
});

test('User v1 profile endpoint works correctly', function (): void {
    $response = $this->withHeader('Authorization', "Bearer {$this->userToken}")
        ->getJson('/api/v1/user/profile');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'id', 'name', 'email',
        ]);
});

test('User route names have v1 prefix', function (): void {
    expect(route('v1.user.login'))->toContain('/api/v1/user/login');
    expect(route('v1.user.logout'))->toContain('/api/v1/user/logout');
    expect(route('v1.user.profile'))->toContain('/api/v1/user/profile');
});

test('Legacy /api/admin/login redirects to v1', function (): void {
    $response = $this->postJson('/api/admin/login', [
        'email' => 'admin@example.com',
        'password' => 'password',
    ]);

    // Permanent redirect (301 or 308)
    expect($response->status())->toBeIn([301, 308]);
    expect($response->headers->get('location'))->toContain('/api/v1/admin/login');
});

test('Legacy /api/login redirects to v1', function (): void {
    $response = $this->postJson('/api/login', [
        'email' => 'user@example.com',
        'password' => 'password',
    ]);

    // Permanent redirect (301 or 308)
    expect($response->status())->toBeIn([301, 308]);
    expect($response->headers->get('location'))->toContain('/api/v1/user/login');
});

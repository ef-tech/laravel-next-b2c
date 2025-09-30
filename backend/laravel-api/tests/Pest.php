<?php

declare(strict_types=1);

use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

uses(TestCase::class)->in('Feature', 'Unit', 'Architecture');

/*
|--------------------------------------------------------------------------
| Custom Expectations
|--------------------------------------------------------------------------
|
| API-specific custom expectations for cleaner test assertions.
|
*/

// HTTP 200 OK + Content-Type: application/json assertion
expect()->extend('toBeJsonOk', function () {
    /** @var TestResponse $response */
    $response = $this->value;

    $response->assertOk();

    // Check Content-Type header (allows charset suffix like "application/json; charset=UTF-8")
    $contentType = $response->headers->get('Content-Type');
    expect($contentType)->toContain('application/json');

    return $this;
});

// CORS headers assertion
expect()->extend('toHaveCors', function (string $origin = '*') {
    /** @var TestResponse $response */
    $response = $this->value;

    $response->assertHeader('Access-Control-Allow-Origin', $origin)
        ->assertHeader('Access-Control-Allow-Methods')
        ->assertHeader('Access-Control-Allow-Headers');

    return $this;
});

/*
|--------------------------------------------------------------------------
| Helper Functions
|--------------------------------------------------------------------------
|
| API-specific helper functions for test setup.
|
*/

/**
 * Set up Sanctum authentication for API tests.
 */
function actingAsApi(\App\Models\User $user, array $abilities = ['*']): void
{
    Sanctum::actingAs($user, $abilities);
}

/**
 * Generate standard JSON headers for API requests.
 */
function jsonHeaders(array $extra = []): array
{
    return array_merge([
        'Accept' => 'application/json',
        'Content-Type' => 'application/json',
    ], $extra);
}

/*
|--------------------------------------------------------------------------
| Global Before Each
|--------------------------------------------------------------------------
|
| Common setup for all Pest tests.
|
*/

beforeEach(function () {
    // Common setup for all tests can be added here if needed
});

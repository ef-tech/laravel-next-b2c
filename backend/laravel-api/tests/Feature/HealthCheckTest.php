<?php

declare(strict_types=1);

use function Pest\Laravel\get;

it('returns 200 OK status code', function () {
    $response = get('/api/health');

    $response->assertStatus(200);
});

it('returns JSON response with status ok', function () {
    $response = get('/api/health');

    $response->assertJson(['status' => 'ok']);
});

it('includes Cache-Control no-store header', function () {
    $response = get('/api/health');

    expect($response->headers->get('Cache-Control'))->toContain('no-store');
});

it('is accessible without authentication', function () {
    $response = get('/api/health');

    $response->assertStatus(200);
});

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

it('is accessible via named route', function () {
    $url = route('health');

    expect($url)->toContain('/api/health');

    $response = get($url);
    $response->assertStatus(200);
});

it('does not apply rate limiting (150 consecutive requests)', function () {
    for ($i = 0; $i < 150; $i++) {
        $response = get('/api/health');
        $response->assertStatus(200);
    }
})->skip('Performance test: Enable when needed');

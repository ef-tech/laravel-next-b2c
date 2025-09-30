<?php

declare(strict_types=1);

it('allows requests from allowed origin', function () {
    $origin = 'http://localhost:3000';

    $response = $this->withHeaders(array_merge(jsonHeaders(), [
        'Origin' => $origin,
    ]))->options('/api/up');

    expect($response)->toHaveCors($origin);
});

it('includes CORS headers in actual requests', function () {
    $response = $this->withHeaders([
        'Origin' => 'http://localhost:3000',
        'Accept' => 'application/json',
    ])->getJson('/api/up');

    $response->assertHeader('Access-Control-Allow-Origin', 'http://localhost:3000');
});

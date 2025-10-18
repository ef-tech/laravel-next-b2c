<?php

describe('CORS Integration Tests', function () {
    describe('Allowed Origin Requests', function () {
        test('returns successful response for allowed origin http://localhost:13001', function () {
            $response = $this->withHeaders([
                'Origin' => 'http://localhost:13001',
            ])->getJson('/api/health');

            $response->assertStatus(200);
            expect($response->headers->get('Access-Control-Allow-Origin'))->toBe('http://localhost:13001');
        });

        test('returns successful response for allowed origin http://localhost:13002', function () {
            $response = $this->withHeaders([
                'Origin' => 'http://localhost:13002',
            ])->getJson('/api/health');

            $response->assertStatus(200);
            expect($response->headers->get('Access-Control-Allow-Origin'))->toBe('http://localhost:13002');
        });

        test('returns successful response for allowed origin http://127.0.0.1:13001', function () {
            $response = $this->withHeaders([
                'Origin' => 'http://127.0.0.1:13001',
            ])->getJson('/api/health');

            $response->assertStatus(200);
            expect($response->headers->get('Access-Control-Allow-Origin'))->toBe('http://127.0.0.1:13001');
        });

        test('returns successful response for allowed origin http://127.0.0.1:13002', function () {
            $response = $this->withHeaders([
                'Origin' => 'http://127.0.0.1:13002',
            ])->getJson('/api/health');

            $response->assertStatus(200);
            expect($response->headers->get('Access-Control-Allow-Origin'))->toBe('http://127.0.0.1:13002');
        });
    });

    describe('Denied Origin Requests', function () {
        test('blocks request from unauthorized origin http://evil.com', function () {
            $response = $this->withHeaders([
                'Origin' => 'http://evil.com',
            ])->getJson('/api/health');

            // Laravel CORS middleware allows the request but does not include CORS headers
            // This means the browser will block the response from being read by JavaScript
            expect($response->headers->has('Access-Control-Allow-Origin'))->toBeFalse();
        });

        test('blocks request from unauthorized origin http://malicious.example.com', function () {
            $response = $this->withHeaders([
                'Origin' => 'http://malicious.example.com',
            ])->getJson('/api/health');

            expect($response->headers->has('Access-Control-Allow-Origin'))->toBeFalse();
        });

        test('blocks request from unauthorized origin https://attacker.com', function () {
            $response = $this->withHeaders([
                'Origin' => 'https://attacker.com',
            ])->getJson('/api/health');

            expect($response->headers->has('Access-Control-Allow-Origin'))->toBeFalse();
        });
    });

    describe('OPTIONS Preflight Requests', function () {
        test('returns correct CORS headers for OPTIONS preflight request from allowed origin', function () {
            $response = $this->options('/api/health', [], [
                'Origin' => 'http://localhost:13001',
                'Access-Control-Request-Method' => 'GET',
                'Access-Control-Request-Headers' => 'Content-Type,Authorization',
            ]);

            $response->assertStatus(204); // OPTIONS requests return 204 No Content
            expect($response->headers->get('Access-Control-Allow-Origin'))->toBe('http://localhost:13001');
            expect($response->headers->get('Access-Control-Allow-Methods'))->not()->toBeNull();
            expect($response->headers->get('Access-Control-Max-Age'))->not()->toBeNull();
        });

        test('returns Access-Control-Allow-Methods header in preflight response', function () {
            $response = $this->options('/api/health', [], [
                'Origin' => 'http://localhost:13001',
                'Access-Control-Request-Method' => 'POST',
            ]);

            $response->assertStatus(204); // OPTIONS requests return 204 No Content
            $allowedMethods = $response->headers->get('Access-Control-Allow-Methods');

            expect($allowedMethods)->not()->toBeNull();
            // CORS middleware returns allowed methods based on configuration
            expect($allowedMethods)->toBeString();
        });

        test('returns Access-Control-Max-Age header in preflight response', function () {
            $response = $this->options('/api/health', [], [
                'Origin' => 'http://localhost:13001',
                'Access-Control-Request-Method' => 'GET',
            ]);

            $response->assertStatus(204); // OPTIONS requests return 204 No Content
            $maxAge = $response->headers->get('Access-Control-Max-Age');

            expect($maxAge)->not()->toBeNull();
            expect((int) $maxAge)->toBeGreaterThan(0);
        });

        test('returns Access-Control-Allow-Headers header in preflight response', function () {
            $response = $this->options('/api/health', [], [
                'Origin' => 'http://localhost:13001',
                'Access-Control-Request-Method' => 'POST',
                'Access-Control-Request-Headers' => 'Content-Type,Authorization',
            ]);

            $response->assertStatus(204); // OPTIONS requests return 204 No Content
            $allowedHeaders = $response->headers->get('Access-Control-Allow-Headers');

            expect($allowedHeaders)->not()->toBeNull();
        });

        test('does not return CORS headers for preflight from unauthorized origin', function () {
            $response = $this->options('/api/health', [], [
                'Origin' => 'http://evil.com',
                'Access-Control-Request-Method' => 'GET',
            ]);

            expect($response->headers->has('Access-Control-Allow-Origin'))->toBeFalse();
        });

        test('handles multiple origins in preflight requests correctly', function () {
            $response1 = $this->options('/api/health', [], [
                'Origin' => 'http://localhost:13001',
                'Access-Control-Request-Method' => 'GET',
            ]);

            expect($response1->headers->get('Access-Control-Allow-Origin'))->toBe('http://localhost:13001');

            $response2 = $this->options('/api/health', [], [
                'Origin' => 'http://localhost:13002',
                'Access-Control-Request-Method' => 'GET',
            ]);

            expect($response2->headers->get('Access-Control-Allow-Origin'))->toBe('http://localhost:13002');
        });
    });

    describe('CORS with Credentials', function () {
        test('does not include Access-Control-Allow-Credentials by default', function () {
            $response = $this->withHeaders([
                'Origin' => 'http://localhost:13001',
            ])->getJson('/api/health');

            $response->assertStatus(200);
            expect($response->headers->has('Access-Control-Allow-Credentials'))->toBeFalse();
        });
    });

    describe('CORS Max-Age Configuration', function () {
        test('returns max-age value based on environment configuration', function () {
            $response = $this->options('/api/health', [], [
                'Origin' => 'http://localhost:13001',
                'Access-Control-Request-Method' => 'GET',
            ]);

            $response->assertStatus(204); // OPTIONS requests return 204 No Content
            $maxAge = (int) $response->headers->get('Access-Control-Max-Age');

            // Default development max-age is 600 seconds
            expect($maxAge)->toBeGreaterThan(0);
            expect($maxAge)->toBeLessThanOrEqual(86400); // Max 24 hours
        });
    });
});

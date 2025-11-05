<?php

use Ddd\Shared\Exceptions\ApplicationException;
use Ddd\Shared\Exceptions\DomainException;
use Ddd\Shared\Exceptions\InfrastructureException;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| E2E Test Support Routes
|--------------------------------------------------------------------------
|
| These routes are only loaded in non-production environments to support
| Playwright E2E tests. They intentionally trigger specific error
| conditions so the frontend can verify its error handling UI.
|
| Mounted at: /api/test (via bootstrap/app.php Route::prefix('api/test'))
| Example endpoint: /api/test/domain-exception
|
*/

// Domain Exception (400)
Route::get('/domain-exception', function () {
    throw new class('Test domain exception message') extends DomainException
    {
        public function getStatusCode(): int
        {
            return 400;
        }

        public function getErrorCode(): string
        {
            return 'DOMAIN-TEST-4001';
        }

        protected function getTitle(): string
        {
            return 'Domain Exception Test';
        }
    };
});

// Application Exception (404)
Route::get('/application-exception', function () {
    throw new class('Test application exception message') extends ApplicationException
    {
        protected int $statusCode = 404;

        protected string $errorCode = 'APP-TEST-4001';

        protected function getTitle(): string
        {
            return 'Application Exception Test';
        }
    };
});

// Infrastructure Exception (503)
Route::get('/infrastructure-exception', function () {
    throw new class('Test infrastructure exception message') extends InfrastructureException
    {
        protected int $statusCode = 503;

        protected string $errorCode = 'INFRA-TEST-5001';

        protected function getTitle(): string
        {
            return 'Infrastructure Exception Test';
        }
    };
});

// Validation Error (422)
Route::post('/validation', function (\Illuminate\Http\Request $request) {
    $request->validate([
        'email' => 'required|email',
        'name' => 'required|string|min:3',
        'age' => 'required|integer|min:18',
    ]);

    return response()->json(['success' => true]);
});

// Authentication Error (401)
Route::get('/auth-error', function () {
    throw new \Illuminate\Auth\AuthenticationException('Unauthenticated.');
});

// Generic 500 Error
Route::get('/generic-exception', function () {
    throw new \RuntimeException('Test generic exception message');
});

// Simulate slow endpoint to trigger client timeout
// Note: E2E tests use 100ms AbortController timeout, so 1 second is sufficient
Route::get('/timeout-endpoint', function () {
    // Sleep 1 second - long enough for 100ms timeout to trigger AbortError
    // but short enough to not block php artisan serve for other tests
    sleep(1);

    return response()->json(['status' => 'delayed']);
});

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
})->middleware('api');

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
})->middleware('api');

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
})->middleware('api');

// Validation Error (422)
Route::post('/validation', function (\Illuminate\Http\Request $request) {
    $request->validate([
        'email' => 'required|email',
        'name' => 'required|string|min:3',
        'age' => 'required|integer|min:18',
    ]);

    return response()->json(['success' => true]);
})->middleware('api');

// Authentication Error (401)
Route::get('/auth-error', function () {
    throw new \Illuminate\Auth\AuthenticationException('Unauthenticated.');
})->middleware('api');

// Generic 500 Error
Route::get('/generic-exception', function () {
    throw new \RuntimeException('Test generic exception message');
})->middleware('api');

// Simulate slow endpoint to trigger client timeout (ApiClient uses 30s timeout)
Route::get('/timeout-endpoint', function () {
    // Sleep a bit longer than client timeout to ensure AbortError occurs
    sleep(35);

    return response()->json(['status' => 'delayed']);
})->middleware('api');

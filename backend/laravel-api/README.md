<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Testing

This project uses **Pest 4** as the testing framework. All tests have been migrated from PHPUnit to Pest for a more expressive and modern testing experience.

### Running Tests

```bash
# Run all tests
composer test

# Run tests with coverage report
composer test-coverage

# Run tests in parallel (faster execution)
composer test-parallel

# Run specific test file
./vendor/bin/pest tests/Feature/ExampleTest.php

# Run tests with filtering
./vendor/bin/pest --filter=authentication
```

### Available Test Commands

- `composer test` - Run all Pest tests
- `composer test-coverage` - Generate coverage report (requires Xdebug or PCOV)
- `composer test-parallel` - Run tests in parallel using multiple processes
- `composer test-shard` - Run tests in sharded mode for CI/CD environments

### Test Structure

- `tests/Unit/` - Unit tests (isolated component tests)
- `tests/Feature/` - Feature tests (integration tests)
- `tests/Architecture/` - Architecture tests (enforcing design rules)

### Writing Tests

Pest uses a function-based syntax that's more expressive than PHPUnit:

```php
it('returns successful response for health check endpoint', function () {
    $response = $this->get('/up');
    $response->assertStatus(200);
});

it('validates user authentication', function () {
    $user = User::factory()->create();

    expect($user->name)->not->toBeEmpty()
        ->and($user->email)->toBeString();
});
```

For more information about Pest, visit [Pest PHP Documentation](https://pestphp.com/docs).

## Middleware Configuration

This project implements a comprehensive middleware stack for API security, performance monitoring, and request handling.

### Middleware Overview

The application uses **12 custom middleware** organized into **6 middleware groups**:

#### Global Middleware
All requests pass through these middleware:
- **SetRequestId** - Generates unique request ID for tracing
- **CorrelationId** - W3C Trace Context support for distributed tracing
- **ForceJsonResponse** - Enforces JSON responses for API endpoints
- **SecurityHeaders** - OWASP-compliant security headers (HSTS, CSP, X-Frame-Options, etc.)
- **RequestLogging** - Structured logging with request/correlation IDs
- **PerformanceMonitoring** - Collects performance metrics for all requests

#### Middleware Groups

| Group | Purpose | Middleware Stack |
|-------|---------|-----------------|
| `api` | Base API endpoints | Global + DynamicRateLimit:api |
| `auth` | Authenticated endpoints | api + auth:sanctum + SanctumTokenVerification + AuditTrail |
| `guest` | Public endpoints | api + DynamicRateLimit:public |
| `internal` | Admin/internal API | api + auth:sanctum + SanctumTokenVerification + AuthorizationCheck:admin + DynamicRateLimit:strict + AuditTrail |
| `webhook` | Webhook receivers | api + IdempotencyKey + DynamicRateLimit:webhook |
| `readonly` | Read-only cached endpoints | api + CacheHeaders + ETag |

### Key Features

#### 1. Rate Limiting
Dynamic rate limiting with Redis-backed storage and graceful degradation:
- **API**: 60 req/min (authenticated users)
- **Public**: 30 req/min (by IP address)
- **Webhook**: 100 req/min (external integrations)
- **Strict**: 10 req/min (admin operations)

```env
RATELIMIT_API_REQUESTS=60
RATELIMIT_PUBLIC_REQUESTS=30
RATELIMIT_WEBHOOK_REQUESTS=100
RATELIMIT_STRICT_REQUESTS=10
```

#### 2. Security Headers
OWASP-compliant security headers with CSP reporting:
- **HSTS** - HTTP Strict Transport Security
- **CSP** - Content Security Policy with violation reporting
- **X-Frame-Options** - Clickjacking protection
- **X-Content-Type-Options** - MIME sniffing protection

```env
SECURITY_HSTS_MAX_AGE=31536000
SECURITY_CSP_ENABLED=true
SECURITY_CSP_REPORT_URI=/api/csp-report
```

#### 3. Idempotency
Duplicate request prevention for webhooks and critical operations:
- Idempotency-Key header support
- 24-hour key retention in Redis
- Cached response replay for duplicate requests

```bash
curl -X POST https://api.example.com/webhook \
  -H "Idempotency-Key: unique-key-123" \
  -H "Authorization: Bearer TOKEN" \
  -d '{"data": "payload"}'
```

#### 4. Performance Monitoring
Automatic performance metrics collection:
- Request duration (milliseconds)
- Memory usage
- SQL query count
- Non-blocking logging via `terminate()` method

#### 5. Audit Trail
Comprehensive audit logging for authenticated operations:
- User identification
- IP address tracking
- Request/Response correlation
- Automatic logging for auth/internal groups

### Configuration Files

| File | Purpose |
|------|---------|
| `config/ratelimit.php` | Rate limiting configuration for all endpoints |
| `config/sanctum.php` | Laravel Sanctum token authentication settings |
| `config/cache.php` | Cache driver configuration (Redis/File) |
| `bootstrap/app.php` | Middleware registration and group definitions |

### Environment Variables

**Required**:
```env
# Redis connection (for rate limiting, idempotency, caching)
REDIS_HOST=redis
REDIS_PORT=6379
REDIS_PASSWORD=null

# Rate limiting
RATELIMIT_API_REQUESTS=60
RATELIMIT_PUBLIC_REQUESTS=30

# Security headers
SECURITY_HSTS_MAX_AGE=31536000
SECURITY_CSP_ENABLED=true
```

**Optional**:
```env
# Cache control
CACHE_PUBLIC_MAX_AGE=3600
CACHE_PRIVATE_MAX_AGE=1800

# Idempotency
IDEMPOTENCY_CACHE_TTL=86400

# Performance monitoring
PERFORMANCE_SLOW_QUERY_THRESHOLD=1000
```

### Usage Examples

#### Apply middleware groups to routes:
```php
// Public API endpoint (guest group)
Route::post('/register', [AuthController::class, 'register'])
    ->middleware(['guest']);

// Authenticated API endpoint (auth group)
Route::get('/user', [UserController::class, 'show'])
    ->middleware(['auth']);

// Admin endpoint (internal group)
Route::post('/admin/users', [AdminController::class, 'createUser'])
    ->middleware(['internal']);

// Webhook endpoint (webhook group)
Route::post('/webhook/stripe', [WebhookController::class, 'stripe'])
    ->middleware(['webhook']);

// Cached readonly endpoint (readonly group)
Route::get('/products', [ProductController::class, 'index'])
    ->middleware(['readonly']);
```

### Documentation

- **[Middleware Implementation Guide](docs/MIDDLEWARE_IMPLEMENTATION.md)** - Detailed implementation patterns and code examples
- **[Middleware Operations Manual](docs/MIDDLEWARE_OPERATIONS.md)** - Monitoring, troubleshooting, and operations guide

### Testing

Comprehensive test coverage for all middleware:
- **79 unit tests** - Individual middleware behavior
- **23 integration tests** - Middleware chain and group interactions
- **10 E2E tests** - End-to-end HTTP request verification

```bash
# Run middleware tests
./vendor/bin/pest tests/Unit/Middleware/
./vendor/bin/pest tests/Feature/Middleware/
./vendor/bin/pest tests/Feature/E2E/

# Check coverage (80%+ achieved)
composer test-coverage
```

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

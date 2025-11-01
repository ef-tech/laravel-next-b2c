<?php

declare(strict_types=1);

use Ddd\Shared\Exceptions\InfrastructureException;

/**
 * InfrastructureException実装テスト
 *
 * Requirements:
 * - 2.3: Infrastructure層で外部システムエラーが発生する時、InfrastructureExceptionのサブクラスが例外を投げること
 * - 2.4: InfrastructureException生成時、getErrorCode()メソッドで独自エラーコードを返却すること
 */

// テスト用具象クラス（外部システムエラー例）
final class DatabaseConnectionException extends InfrastructureException
{
    protected int $statusCode = 503;

    protected string $errorCode = 'INFRA-DB-5001';
}

final class ExternalApiTimeoutException extends InfrastructureException
{
    protected int $statusCode = 504;

    protected string $errorCode = 'INFRA-API-5002';
}

final class ServiceUnavailableException extends InfrastructureException
{
    protected int $statusCode = 502;

    protected string $errorCode = 'INFRA-SERVICE-5003';
}

test('InfrastructureException は基底クラスとして機能する', function () {
    $exception = new DatabaseConnectionException('Failed to connect to the database.');

    expect($exception)->toBeInstanceOf(InfrastructureException::class)
        ->and($exception)->toBeInstanceOf(\Exception::class);
});

test('getStatusCode() がHTTPステータスコードを返却する（500番台）', function () {
    $dbException = new DatabaseConnectionException('Database connection failed');
    expect($dbException->getStatusCode())->toBe(503);

    $timeoutException = new ExternalApiTimeoutException('External API timeout');
    expect($timeoutException->getStatusCode())->toBe(504);

    $serviceException = new ServiceUnavailableException('Service unavailable');
    expect($serviceException->getStatusCode())->toBe(502);
});

test('getErrorCode() がDOMAIN-SUBDOMAIN-CODE形式のエラーコードを返却する', function () {
    $exception = new DatabaseConnectionException('Database connection failed');

    expect($exception->getErrorCode())
        ->toBe('INFRA-DB-5001')
        ->toMatch('/^[A-Z]+-[A-Z]+-[0-9]{4}$/'); // DOMAIN-SUBDOMAIN-CODE形式検証
});

test('toProblemDetails() がRFC 7807形式の配列を生成する', function () {
    $exception = new ExternalApiTimeoutException('The external API request timed out after 30 seconds.');

    // Request ID mockをセット
    request()->headers->set('X-Request-ID', '550e8400-e29b-41d4-a716-446655440000');
    request()->server->set('REQUEST_URI', '/api/v1/orders');

    $problemDetails = $exception->toProblemDetails();

    // RFC 7807必須フィールド
    expect($problemDetails)->toHaveKey('type')
        ->and($problemDetails['type'])->toBeString()
        ->and($problemDetails)->toHaveKey('title')
        ->and($problemDetails['title'])->toBe('ExternalApiTimeoutException') // class_basename()
        ->and($problemDetails)->toHaveKey('status')
        ->and($problemDetails['status'])->toBe(504)
        ->and($problemDetails)->toHaveKey('detail')
        ->and($problemDetails['detail'])->toBe('The external API request timed out after 30 seconds.');

    // 拡張フィールド
    expect($problemDetails)->toHaveKey('error_code')
        ->and($problemDetails['error_code'])->toBe('INFRA-API-5002')
        ->and($problemDetails)->toHaveKey('trace_id')
        ->and($problemDetails['trace_id'])->toBe('550e8400-e29b-41d4-a716-446655440000')
        ->and($problemDetails)->toHaveKey('instance')
        ->and($problemDetails['instance'])->toBe('/api/v1/orders')
        ->and($problemDetails)->toHaveKey('timestamp')
        ->and($problemDetails['timestamp'])->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/'); // ISO 8601形式
});

test('toProblemDetails() のtypeフィールドがエラーコードを含むURIである', function () {
    $exception = new DatabaseConnectionException('Database connection failed.');
    request()->headers->set('X-Request-ID', '550e8400-e29b-41d4-a716-446655440000');

    $problemDetails = $exception->toProblemDetails();

    expect($problemDetails['type'])
        ->toContain(config('app.url'))
        ->toContain('/errors/')
        ->toContain('infra-db-5001'); // 小文字に変換されること
});

test('InfrastructureException は具象クラスとしてインスタンス化できる（デフォルト値）', function () {
    $exception = new class('Infrastructure error occurred') extends InfrastructureException {};

    expect($exception->getStatusCode())->toBe(503) // デフォルト
        ->and($exception->getErrorCode())->toBe('INFRA-0001'); // デフォルト
});

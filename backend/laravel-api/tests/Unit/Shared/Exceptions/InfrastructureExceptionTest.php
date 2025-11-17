<?php

declare(strict_types=1);

use Ddd\Shared\Exceptions\InfrastructureException;

use function Tests\Helpers\assertEnumDefinedTypeUri;
use function Tests\Helpers\assertFallbackTypeUri;
use function Tests\Helpers\assertRfc7807RequiredFields;
use function Tests\Helpers\mockRequestContext;

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

    protected function getTitle(): string
    {
        return 'Database Connection Error';
    }
}

final class ExternalApiTimeoutException extends InfrastructureException
{
    protected int $statusCode = 504;

    protected string $errorCode = 'INFRA-API-5002';

    protected function getTitle(): string
    {
        return 'External API Timeout';
    }
}

final class ServiceUnavailableException extends InfrastructureException
{
    protected int $statusCode = 502;

    protected string $errorCode = 'INFRA-SERVICE-5003';

    protected function getTitle(): string
    {
        return 'Service Unavailable';
    }
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
    mockRequestContext('550e8400-e29b-41d4-a716-446655440000', '/api/v1/orders');

    $problemDetails = $exception->toProblemDetails();

    // RFC 7807必須フィールド
    assertRfc7807RequiredFields(
        $problemDetails,
        expectedTitle: 'External API Timeout',
        expectedStatus: 504,
        expectedDetail: 'The external API request timed out after 30 seconds.',
        expectedErrorCode: 'INFRA-API-5002',
        expectedRequestId: '550e8400-e29b-41d4-a716-446655440000',
        expectedInstance: '/api/v1/orders'
    );
});

test('toProblemDetails() のtypeフィールドがエラーコードを含むURIである', function () {
    $exception = new DatabaseConnectionException('Database connection failed.');
    mockRequestContext('550e8400-e29b-41d4-a716-446655440000', '/api/v1/databases');

    $problemDetails = $exception->toProblemDetails();

    assertFallbackTypeUri($problemDetails, 'infra-db-5001');
});

test('InfrastructureException は具象クラスとしてインスタンス化できる（デフォルト値）', function () {
    $exception = new class('Infrastructure error occurred') extends InfrastructureException
    {
        protected function getTitle(): string
        {
            return 'Infrastructure Error';
        }
    };

    expect($exception->getStatusCode())->toBe(503) // デフォルト
        ->and($exception->getErrorCode())->toBe('INFRA-0001'); // デフォルト
});

// テスト用具象クラス（ErrorCode enum定義済みエラーコード使用）
final class DatabaseUnavailableException extends InfrastructureException
{
    protected int $statusCode = 503;

    protected string $errorCode = 'INFRA-DB-001'; // ErrorCode enumに定義済み

    protected function getTitle(): string
    {
        return 'Database Unavailable';
    }
}

test('[RED] ErrorCode enum定義済みエラーコードでErrorCode::getType()のURIが返される', function () {
    $exception = new DatabaseUnavailableException('Unable to connect to database server');
    mockRequestContext('550e8400-e29b-41d4-a716-446655440000', '/api/v1/products');

    $problemDetails = $exception->toProblemDetails();

    // ErrorCode::INFRA_DB_001->getType()が返すURIを期待
    assertEnumDefinedTypeUri($problemDetails, 'https://example.com/errors/infrastructure/database-unavailable');
});

test('[RED] ErrorCode enum未定義エラーコードでフォールバックURIが返される', function () {
    $exception = new ExternalApiTimeoutException('The external API request timed out after 30 seconds.');
    mockRequestContext('550e8400-e29b-41d4-a716-446655440000', '/api/v1/orders');

    $problemDetails = $exception->toProblemDetails();

    // フォールバックURIが返される（既存の動的URI生成）
    assertFallbackTypeUri($problemDetails, 'infra-api-5002');
});

test('null安全性: ErrorCode::fromString()がnullを返してもフォールバックURIが生成される', function () {
    $exception = new ServiceUnavailableException('Service is temporarily unavailable.');
    mockRequestContext('550e8400-e29b-41d4-a716-446655440000', '/api/v1/services');

    $problemDetails = $exception->toProblemDetails();

    // フォールバックURIが返される（null安全性検証）
    assertFallbackTypeUri($problemDetails, 'infra-service-5003');
});

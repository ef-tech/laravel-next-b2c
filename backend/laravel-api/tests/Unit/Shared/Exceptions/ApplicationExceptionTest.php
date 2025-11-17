<?php

declare(strict_types=1);

use Ddd\Shared\Exceptions\ApplicationException;

use function Tests\Helpers\assertEnumDefinedTypeUri;
use function Tests\Helpers\assertFallbackTypeUri;
use function Tests\Helpers\assertRfc7807RequiredFields;
use function Tests\Helpers\mockRequestContext;

/**
 * ApplicationException実装テスト
 *
 * Requirements:
 * - 2.2: Application層でユースケース実行エラーが発生する時、ApplicationExceptionのサブクラスが例外を投げること
 * - 2.4: ApplicationException生成時、getErrorCode()メソッドで独自エラーコードを返却すること
 */

// テスト用具象クラス（ユースケースエラー例）
final class ResourceNotFoundException extends ApplicationException
{
    protected int $statusCode = 404;

    protected string $errorCode = 'APP-RESOURCE-4001';

    protected function getTitle(): string
    {
        return 'Resource Not Found';
    }
}

final class UnauthorizedAccessException extends ApplicationException
{
    protected int $statusCode = 403;

    protected string $errorCode = 'APP-AUTH-4002';

    protected function getTitle(): string
    {
        return 'Unauthorized Access';
    }
}

test('ApplicationException は基底クラスとして機能する', function () {
    $exception = new ResourceNotFoundException('The requested resource was not found.');

    expect($exception)->toBeInstanceOf(ApplicationException::class)
        ->and($exception)->toBeInstanceOf(\Exception::class);
});

test('getStatusCode() がHTTPステータスコードを返却する（400番台）', function () {
    $notFoundException = new ResourceNotFoundException('Resource not found');
    expect($notFoundException->getStatusCode())->toBe(404);

    $forbiddenException = new UnauthorizedAccessException('Access denied');
    expect($forbiddenException->getStatusCode())->toBe(403);
});

test('getErrorCode() がDOMAIN-SUBDOMAIN-CODE形式のエラーコードを返却する', function () {
    $exception = new ResourceNotFoundException('Resource not found');

    expect($exception->getErrorCode())
        ->toBe('APP-RESOURCE-4001')
        ->toMatch('/^[A-Z]+-[A-Z]+-[0-9]{4}$/'); // DOMAIN-SUBDOMAIN-CODE形式検証
});

test('toProblemDetails() がRFC 7807形式の配列を生成する', function () {
    $exception = new ResourceNotFoundException('The requested user was not found.');

    // Request ID mockをセット
    mockRequestContext('550e8400-e29b-41d4-a716-446655440000', '/api/v1/users/123');

    $problemDetails = $exception->toProblemDetails();

    // RFC 7807必須フィールド
    assertRfc7807RequiredFields(
        $problemDetails,
        expectedTitle: 'Resource Not Found',
        expectedStatus: 404,
        expectedDetail: 'The requested user was not found.',
        expectedErrorCode: 'APP-RESOURCE-4001',
        expectedRequestId: '550e8400-e29b-41d4-a716-446655440000',
        expectedInstance: '/api/v1/users/123'
    );
});

test('toProblemDetails() のtypeフィールドがエラーコードを含むURIである', function () {
    $exception = new UnauthorizedAccessException('You do not have permission to access this resource.');
    mockRequestContext('550e8400-e29b-41d4-a716-446655440000', '/api/v1/resources');

    $problemDetails = $exception->toProblemDetails();

    assertFallbackTypeUri($problemDetails, 'app-auth-4002');
});

test('ApplicationException は具象クラスとしてインスタンス化できる（デフォルト値）', function () {
    $exception = new class('Application error occurred') extends ApplicationException
    {
        protected function getTitle(): string
        {
            return 'Application Error';
        }
    };

    expect($exception->getStatusCode())->toBe(400) // デフォルト
        ->and($exception->getErrorCode())->toBe('APP-0001'); // デフォルト
});

// テスト用具象クラス（ErrorCode enum定義済みエラーコード使用）
final class AuthTokenExpiredException extends ApplicationException
{
    protected int $statusCode = 401;

    protected string $errorCode = 'AUTH-TOKEN-001'; // ErrorCode enumに定義済み

    protected function getTitle(): string
    {
        return 'Token Expired';
    }
}

test('[RED] ErrorCode enum定義済みエラーコードでErrorCode::getType()のURIが返される', function () {
    $exception = new AuthTokenExpiredException('Authentication token has expired');
    mockRequestContext('550e8400-e29b-41d4-a716-446655440000', '/api/v1/users/me');

    $problemDetails = $exception->toProblemDetails();

    // ErrorCode::AUTH_TOKEN_001->getType()が返すURIを期待
    assertEnumDefinedTypeUri($problemDetails, 'https://example.com/errors/auth/token-expired');
});

test('[RED] ErrorCode enum未定義エラーコードでフォールバックURIが返される', function () {
    $exception = new ResourceNotFoundException('The requested resource was not found.');
    mockRequestContext('550e8400-e29b-41d4-a716-446655440000', '/api/v1/resources/999');

    $problemDetails = $exception->toProblemDetails();

    // フォールバックURIが返される（既存の動的URI生成）
    assertFallbackTypeUri($problemDetails, 'app-resource-4001');
});

<?php

declare(strict_types=1);

use Ddd\Shared\Exceptions\ApplicationException;

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
    request()->headers->set('X-Request-ID', '550e8400-e29b-41d4-a716-446655440000');
    request()->server->set('REQUEST_URI', '/api/v1/users/123');

    $problemDetails = $exception->toProblemDetails();

    // RFC 7807必須フィールド
    expect($problemDetails)->toHaveKey('type')
        ->and($problemDetails['type'])->toBeString()
        ->and($problemDetails)->toHaveKey('title')
        ->and($problemDetails['title'])->toBe('Resource Not Found') // getTitle()
        ->and($problemDetails)->toHaveKey('status')
        ->and($problemDetails['status'])->toBe(404)
        ->and($problemDetails)->toHaveKey('detail')
        ->and($problemDetails['detail'])->toBe('The requested user was not found.');

    // 拡張フィールド
    expect($problemDetails)->toHaveKey('error_code')
        ->and($problemDetails['error_code'])->toBe('APP-RESOURCE-4001')
        ->and($problemDetails)->toHaveKey('trace_id')
        ->and($problemDetails['trace_id'])->toBe('550e8400-e29b-41d4-a716-446655440000')
        ->and($problemDetails)->toHaveKey('instance')
        ->and($problemDetails['instance'])->toBe('/api/v1/users/123')
        ->and($problemDetails)->toHaveKey('timestamp')
        ->and($problemDetails['timestamp'])->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/'); // ISO 8601 Zulu形式
});

test('toProblemDetails() のtypeフィールドがエラーコードを含むURIである', function () {
    $exception = new UnauthorizedAccessException('You do not have permission to access this resource.');
    request()->headers->set('X-Request-ID', '550e8400-e29b-41d4-a716-446655440000');

    $problemDetails = $exception->toProblemDetails();

    expect($problemDetails['type'])
        ->toContain(config('app.url'))
        ->toContain('/errors/')
        ->toContain('app-auth-4002'); // 小文字に変換されること
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

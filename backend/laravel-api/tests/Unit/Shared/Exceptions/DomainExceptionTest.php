<?php

declare(strict_types=1);

use Ddd\Shared\Exceptions\DomainException;

use function Tests\Helpers\assertEnumDefinedTypeUri;
use function Tests\Helpers\assertFallbackTypeUri;
use function Tests\Helpers\assertRfc7807RequiredFields;
use function Tests\Helpers\mockRequestContext;

/**
 * DomainException拡張テスト
 *
 * Requirements:
 * - 1.1: RFC 7807準拠のAPIエラーレスポンス生成
 * - 1.6: DomainExceptionがtoProblemDetails()メソッドでカスタムエラーコード・ステータスコードを含むRFC 7807レスポンスを生成すること
 * - 2.1: Domain層でビジネスルール違反が発生する時、DomainExceptionのサブクラスが例外を投げること
 * - 2.4: DomainException生成時、getErrorCode()メソッドで独自エラーコードを返却すること
 */

// テスト用具象クラス（ビジネスルール違反例）
final class UserEmailAlreadyExistsException extends DomainException
{
    public function getStatusCode(): int
    {
        return 409; // Conflict
    }

    public function getErrorCode(): string
    {
        return 'DOMAIN-USER-4001';
    }

    protected function getTitle(): string
    {
        return 'User Email Already Exists';
    }
}

final class InvalidUserAgeException extends DomainException
{
    public function getStatusCode(): int
    {
        return 400; // Bad Request
    }

    public function getErrorCode(): string
    {
        return 'DOMAIN-USER-4002';
    }

    protected function getTitle(): string
    {
        return 'Invalid User Age';
    }
}

test('toProblemDetails() メソッドが RFC 7807形式の配列を生成する', function () {
    $exception = new UserEmailAlreadyExistsException('The email address is already registered.');

    // Request ID mockをセット（SetRequestId middlewareが実行済みと仮定）
    mockRequestContext('550e8400-e29b-41d4-a716-446655440000', '/api/v1/users');

    $problemDetails = $exception->toProblemDetails();

    // RFC 7807必須フィールド
    assertRfc7807RequiredFields(
        $problemDetails,
        expectedTitle: 'User Email Already Exists',
        expectedStatus: 409,
        expectedDetail: 'The email address is already registered.',
        expectedErrorCode: 'DOMAIN-USER-4001',
        expectedRequestId: '550e8400-e29b-41d4-a716-446655440000',
        expectedInstance: '/api/v1/users'
    );
});

test('getErrorType() がエラータイプURIを生成する', function () {
    $exception = new UserEmailAlreadyExistsException('The email address is already registered.');

    $problemDetails = $exception->toProblemDetails();

    assertFallbackTypeUri($problemDetails, 'domain-user-4001');
});

test('getStatusCode() がHTTPステータスコードを返却する（400番台）', function () {
    $exceptionConflict = new UserEmailAlreadyExistsException('Email already exists');
    expect($exceptionConflict->getStatusCode())->toBe(409);

    $exceptionBadRequest = new InvalidUserAgeException('Age must be 18 or older');
    expect($exceptionBadRequest->getStatusCode())->toBe(400);
});

test('getErrorCode() がDOMAIN-SUBDOMAIN-CODE形式のエラーコードを返却する', function () {
    $exception = new UserEmailAlreadyExistsException('Email already exists');

    expect($exception->getErrorCode())
        ->toBe('DOMAIN-USER-4001')
        ->toMatch('/^[A-Z]+-[A-Z]+-[0-9]{4}$/'); // DOMAIN-SUBDOMAIN-CODE形式検証
});

test('getTitle() が抽象メソッドとして定義され、具象クラスで実装される', function () {
    $exception = new UserEmailAlreadyExistsException('Email already exists');

    $problemDetails = $exception->toProblemDetails();

    expect($problemDetails['title'])->toBe('User Email Already Exists');
});

test('toProblemDetails() がtimestampをISO 8601 Zulu形式で返却する', function () {
    $exception = new InvalidUserAgeException('Age must be 18 or older');
    mockRequestContext('550e8400-e29b-41d4-a716-446655440000', '/api/v1/users');

    $problemDetails = $exception->toProblemDetails();

    expect($problemDetails['timestamp'])
        ->toBeString()
        ->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/'); // ISO 8601 Zulu形式（Z終端）
});

// テスト用具象クラス（ErrorCode enum定義済みエラーコード使用）
final class AuthLoginFailedException extends DomainException
{
    public function getStatusCode(): int
    {
        return 401;
    }

    public function getErrorCode(): string
    {
        return 'AUTH-LOGIN-001'; // ErrorCode enumに定義済み
    }

    protected function getTitle(): string
    {
        return 'Unauthorized';
    }
}

test('ErrorCode enum定義済みエラーコードでErrorCode::getType()のURIが返される', function () {
    $exception = new AuthLoginFailedException('Invalid email or password');
    mockRequestContext('550e8400-e29b-41d4-a716-446655440000', '/api/v1/auth/login');

    $problemDetails = $exception->toProblemDetails();

    // ErrorCode::AUTH_LOGIN_001->getType()が返すURIを期待
    assertEnumDefinedTypeUri($problemDetails, 'https://example.com/errors/auth/invalid-credentials');
});

test('ErrorCode enum未定義エラーコードでフォールバックURIが返される', function () {
    $exception = new UserEmailAlreadyExistsException('The email address is already registered.');
    mockRequestContext('550e8400-e29b-41d4-a716-446655440000', '/api/v1/users');

    $problemDetails = $exception->toProblemDetails();

    // フォールバックURIが返される（既存の動的URI生成）
    assertFallbackTypeUri($problemDetails, 'domain-user-4001');
});

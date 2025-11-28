<?php

declare(strict_types=1);

use Ddd\Shared\Exceptions\DomainException;

use function Tests\Helpers\mockRequestContext;

/**
 * フォールバックURI生成時のサニタイズ処理テスト
 *
 * Requirements:
 * - 1.1-1.5: フォールバックURI生成時のサニタイズ処理
 * - 2.1-2.3: 元のエラーコード保持とトレーサビリティ
 * - 3.1-3.3: RFC 3986準拠とURI安全性
 * - 4.1-4.4: 既存動作との互換性と影響範囲の最小化
 */

// テスト用具象クラス: CUSTOM_ERROR_001
final class CustomError001Exception extends DomainException
{
    public function getStatusCode(): int
    {
        return 500;
    }

    public function getErrorCode(): string
    {
        return 'CUSTOM_ERROR_001';
    }

    protected function getTitle(): string
    {
        return 'Custom Error 001';
    }
}

// テスト用具象クラス: CUSTOM_ERROR (アンダースコアのみ)
final class CustomErrorException extends DomainException
{
    public function getStatusCode(): int
    {
        return 500;
    }

    public function getErrorCode(): string
    {
        return 'CUSTOM_ERROR';
    }

    protected function getTitle(): string
    {
        return 'Custom Error';
    }
}

// テスト用具象クラス: CUSTOM@ERROR! (特殊文字)
final class CustomSpecialCharException extends DomainException
{
    public function getStatusCode(): int
    {
        return 500;
    }

    public function getErrorCode(): string
    {
        return 'CUSTOM@ERROR!';
    }

    protected function getTitle(): string
    {
        return 'Custom Special Char Error';
    }
}

// テスト用具象クラス: CUSTOM ERROR (空白)
final class CustomSpaceException extends DomainException
{
    public function getStatusCode(): int
    {
        return 500;
    }

    public function getErrorCode(): string
    {
        return 'CUSTOM ERROR';
    }

    protected function getTitle(): string
    {
        return 'Custom Space Error';
    }
}

// テスト用具象クラス: @#$% (全て特殊文字)
final class SpecialCharsOnlyException extends DomainException
{
    public function getStatusCode(): int
    {
        return 500;
    }

    public function getErrorCode(): string
    {
        return '@#$%';
    }

    protected function getTitle(): string
    {
        return 'Special Chars Only Error';
    }
}

// テスト用具象クラス: ERROR-123-TEST (数字・ハイフン)
final class ErrorWithNumbersAndHyphensException extends DomainException
{
    public function getStatusCode(): int
    {
        return 500;
    }

    public function getErrorCode(): string
    {
        return 'ERROR-123-TEST';
    }

    protected function getTitle(): string
    {
        return 'Error With Numbers And Hyphens';
    }
}

test('サニタイズ処理: CUSTOM_ERROR_001 → customerror001', function () {
    $exception = new CustomError001Exception('Custom error occurred');
    mockRequestContext('550e8400-e29b-41d4-a716-446655440000', '/api/v1/test');

    $problemDetails = $exception->toProblemDetails();

    // type URIがサニタイズ済みであること（アンダースコアと数字が残り、小文字化）
    expect($problemDetails['type'])
        ->toContain(config('app.url'))
        ->toContain('/errors/customerror001'); // サニタイズ後: customerror001

    // error_codeフィールドに元のエラーコードが保持されること
    expect($problemDetails['error_code'])->toBe('CUSTOM_ERROR_001');
});

test('サニタイズ処理: CUSTOM_ERROR → customerror (アンダースコア削除)', function () {
    $exception = new CustomErrorException('Custom error');
    mockRequestContext('550e8400-e29b-41d4-a716-446655440000', '/api/v1/test');

    $problemDetails = $exception->toProblemDetails();

    // type URIがサニタイズ済みであること（アンダースコア削除）
    expect($problemDetails['type'])
        ->toContain('/errors/customerror'); // サニタイズ後: customerror

    // error_codeフィールドに元のエラーコードが保持されること
    expect($problemDetails['error_code'])->toBe('CUSTOM_ERROR');
});

test('サニタイズ処理: CUSTOM@ERROR! → customerror (特殊文字削除)', function () {
    $exception = new CustomSpecialCharException('Custom special char error');
    mockRequestContext('550e8400-e29b-41d4-a716-446655440000', '/api/v1/test');

    $problemDetails = $exception->toProblemDetails();

    // type URIがサニタイズ済みであること（@と!が削除）
    expect($problemDetails['type'])
        ->toContain('/errors/customerror'); // サニタイズ後: customerror

    // error_codeフィールドに元のエラーコードが保持されること
    expect($problemDetails['error_code'])->toBe('CUSTOM@ERROR!');
});

test('サニタイズ処理: CUSTOM ERROR → customerror (空白削除)', function () {
    $exception = new CustomSpaceException('Custom space error');
    mockRequestContext('550e8400-e29b-41d4-a716-446655440000', '/api/v1/test');

    $problemDetails = $exception->toProblemDetails();

    // type URIがサニタイズ済みであること（空白削除）
    expect($problemDetails['type'])
        ->toContain('/errors/customerror'); // サニタイズ後: customerror

    // error_codeフィールドに元のエラーコードが保持されること
    expect($problemDetails['error_code'])->toBe('CUSTOM ERROR');
});

test('サニタイズ処理: @#$% → unknown (全削除、デフォルト値)', function () {
    $exception = new SpecialCharsOnlyException('Special chars only error');
    mockRequestContext('550e8400-e29b-41d4-a716-446655440000', '/api/v1/test');

    $problemDetails = $exception->toProblemDetails();

    // type URIがデフォルト値 'unknown' になること
    expect($problemDetails['type'])
        ->toContain('/errors/unknown'); // サニタイズ後: unknown (デフォルト値)

    // error_codeフィールドに元のエラーコードが保持されること
    expect($problemDetails['error_code'])->toBe('@#$%');
});

test('サニタイズ処理: ERROR-123-TEST → error-123-test (数字・ハイフン保持)', function () {
    $exception = new ErrorWithNumbersAndHyphensException('Error with numbers and hyphens');
    mockRequestContext('550e8400-e29b-41d4-a716-446655440000', '/api/v1/test');

    $problemDetails = $exception->toProblemDetails();

    // type URIがサニタイズ済みであること（数字とハイフンが保持、小文字化）
    expect($problemDetails['type'])
        ->toContain('/errors/error-123-test'); // サニタイズ後: error-123-test

    // error_codeフィールドに元のエラーコードが保持されること
    expect($problemDetails['error_code'])->toBe('ERROR-123-TEST');
});

test('RFC 3986準拠: フォールバックtype URIが [a-z0-9\-] のみを含む', function () {
    $exceptions = [
        new CustomError001Exception('test'),
        new CustomErrorException('test'),
        new CustomSpecialCharException('test'),
        new CustomSpaceException('test'),
        new ErrorWithNumbersAndHyphensException('test'),
    ];

    mockRequestContext('550e8400-e29b-41d4-a716-446655440000', '/api/v1/test');

    foreach ($exceptions as $exception) {
        $problemDetails = $exception->toProblemDetails();
        $typeUri = $problemDetails['type'];

        // type URIのパス部分（/errors/以降）を抽出
        $path = str_replace(config('app.url').'/errors/', '', $typeUri);

        // RFC 3986準拠: [a-z0-9\-] のみを含むことを検証
        expect($path)->toMatch('/^[a-z0-9\-]+$/');
    }
});

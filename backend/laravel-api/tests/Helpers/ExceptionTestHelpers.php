<?php

declare(strict_types=1);

namespace Tests\Helpers;

use App\Enums\ErrorCode;

/**
 * RFC 7807例外テスト用ヘルパー関数
 *
 * 目的: DomainException/ApplicationException/InfrastructureExceptionテストでの重複コード削減
 */

/**
 * Request IDとRequest URIをモックする
 *
 * @param  string  $requestId  Request ID (X-Request-ID header)
 * @param  string  $requestUri  Request URI (REQUEST_URI server variable)
 */
function mockRequestContext(string $requestId, string $requestUri): void
{
    request()->headers->set('X-Request-ID', $requestId);
    request()->server->set('REQUEST_URI', $requestUri);
}

/**
 * RFC 7807必須フィールドのアサーション
 *
 * @param  array<string, mixed>  $problemDetails
 */
function assertRfc7807RequiredFields(
    array $problemDetails,
    string $expectedTitle,
    int $expectedStatus,
    string $expectedDetail,
    string $expectedErrorCode,
    string $expectedRequestId,
    string $expectedInstance
): void {
    expect($problemDetails)->toHaveKey('type')
        ->and($problemDetails['type'])->toBeString()
        ->and($problemDetails)->toHaveKey('title')
        ->and($problemDetails['title'])->toBe($expectedTitle)
        ->and($problemDetails)->toHaveKey('status')
        ->and($problemDetails['status'])->toBe($expectedStatus)
        ->and($problemDetails)->toHaveKey('detail')
        ->and($problemDetails['detail'])->toBe($expectedDetail)
        ->and($problemDetails)->toHaveKey('error_code')
        ->and($problemDetails['error_code'])->toBe($expectedErrorCode)
        ->and($problemDetails)->toHaveKey('trace_id')
        ->and($problemDetails['trace_id'])->toBe($expectedRequestId)
        ->and($problemDetails)->toHaveKey('instance')
        ->and($problemDetails['instance'])->toBe($expectedInstance)
        ->and($problemDetails)->toHaveKey('timestamp')
        ->and($problemDetails['timestamp'])->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/'); // ISO 8601 Zulu
}

/**
 * ErrorCode enum定義済みtype URIのアサーション
 *
 * @param  array<string, mixed>  $problemDetails
 * @param  string  $expectedTypeUri  ErrorCode::getType()で返されるべきURI
 */
function assertEnumDefinedTypeUri(array $problemDetails, string $expectedTypeUri): void
{
    expect($problemDetails['type'])->toBe($expectedTypeUri);
}

/**
 * フォールバックtype URIのアサーション（ErrorCode enum未定義エラーコード用）
 *
 * @param  array<string, mixed>  $problemDetails
 * @param  string  $expectedErrorCodeLowercase  小文字変換されたエラーコード
 */
function assertFallbackTypeUri(array $problemDetails, string $expectedErrorCodeLowercase): void
{
    expect($problemDetails['type'])
        ->toContain(config('app.url'))
        ->toContain('/errors/')
        ->toContain($expectedErrorCodeLowercase); // 小文字に変換されること
}

/**
 * ErrorCode::fromString()の動作検証
 *
 * @param  string  $errorCode  エラーコード文字列
 * @param  ErrorCode|null  $expectedEnum  期待されるenum値（null = 未定義）
 */
function assertErrorCodeFromString(string $errorCode, ?ErrorCode $expectedEnum): void
{
    $result = ErrorCode::fromString($errorCode);

    if ($expectedEnum === null) {
        expect($result)->toBeNull();
    } else {
        expect($result)->toBeInstanceOf(ErrorCode::class)
            ->and($result)->toBe($expectedEnum);
    }
}

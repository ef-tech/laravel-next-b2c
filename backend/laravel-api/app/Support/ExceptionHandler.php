<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * RFC 7807 Problem Details例外ハンドリング用ヘルパー
 */
final class ExceptionHandler
{
    /**
     * HasProblemDetailsトレイトを使用する例外を処理する
     *
     * @param  \Throwable  $e  例外オブジェクト（toProblemDetails(), getErrorCode(), getStatusCode()メソッドを持つ）
     * @param  Request  $request  HTTPリクエスト
     * @param  string  $exceptionType  例外タイプ名（ログ記録用、例: "DomainException"）
     * @return JsonResponse RFC 7807形式のJSONレスポンス
     */
    public static function handleProblemDetailsException(\Throwable $e, Request $request, string $exceptionType): JsonResponse
    {
        // Request IDを取得または生成
        $requestId = $request->header('X-Request-ID') ?: (string) Str::uuid();

        // 構造化ログコンテキストを設定
        Log::withContext([
            'trace_id' => $requestId,
            'error_code' => method_exists($e, 'getErrorCode') ? $e->getErrorCode() : 'unknown',
            'user_id' => LogHelper::hashSensitiveData($request->user()?->getAuthIdentifier()),
            'request_path' => $request->getRequestUri(),
        ]);

        // エラーログを記録
        Log::error("{$exceptionType} occurred", [
            'exception' => get_class($e),
            'message' => $e->getMessage(),
            'status_code' => method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500,
        ]);

        // RFC 7807形式の配列を取得
        $problemDetails = method_exists($e, 'toProblemDetails')
            ? $e->toProblemDetails()
            : [
                'type' => 'about:blank',
                'title' => 'Internal Server Error',
                'status' => 500,
                'detail' => $e->getMessage(),
                'error_code' => 'internal_server_error',
                'trace_id' => $requestId,
                'instance' => $request->getRequestUri(),
                'timestamp' => now()->utc()->toIso8601String(),
            ];

        $statusCode = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;

        // RFC 7807形式のレスポンスを生成
        return response()->json($problemDetails, $statusCode)
            ->header('Content-Type', 'application/problem+json')
            ->header('X-Request-ID', $requestId);
    }
}

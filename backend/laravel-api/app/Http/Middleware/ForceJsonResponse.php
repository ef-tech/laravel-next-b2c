<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ForceJsonResponse Middleware
 *
 * 全てのAPIエンドポイントでJSON形式の入出力を強制し、
 * API仕様の一貫性を保証します。
 * - Acceptヘッダーがapplication/json以外の場合はHTTP 406を返す
 * - POST/PUT/PATCHリクエストでContent-Typeがapplication/json以外の場合はHTTP 415を返す
 * - 全てのエラー応答もJSON形式で返す
 * - Accept: ワイルドカード (* / * や application/ *) もサポート
 *
 * Requirements: 10.1, 10.2, 10.3, 10.4, 10.5
 */
final class ForceJsonResponse
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Accept ヘッダーの検証
        $acceptHeader = $request->header('Accept', '');
        if (! $this->acceptsJson($acceptHeader)) {
            return $this->jsonError('Not Acceptable', 'This endpoint only supports application/json', 406);
        }

        // POST/PUT/PATCH リクエストの Content-Type 検証
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH'], true)) {
            $contentType = $request->header('Content-Type', '');
            if (! $this->isValidContentType($request, $contentType)) {
                return $this->jsonError('Unsupported Media Type', 'Request body must be application/json', 415);
            }
        }

        return $next($request);
    }

    /**
     * Accept ヘッダーが JSON を受け入れるか判定
     *
     * @param  string  $acceptHeader  Accept ヘッダーの値
     * @return bool JSON を受け入れる場合は true
     */
    private function acceptsJson(string $acceptHeader): bool
    {
        if ($acceptHeader === '') {
            return false;
        }

        // Accept: */* または Accept: application/* の場合は許可
        if (str_contains($acceptHeader, '*/*') || str_contains($acceptHeader, 'application/*')) {
            return true;
        }

        // Accept: application/json の場合は許可
        if (str_contains($acceptHeader, 'application/json')) {
            return true;
        }

        return false;
    }

    /**
     * Content-Type ヘッダーが有効か判定
     *
     * @param  Request  $request  リクエスト
     * @param  string  $contentType  Content-Type ヘッダーの値
     * @return bool 有効な Content-Type の場合は true
     */
    private function isValidContentType(Request $request, string $contentType): bool
    {
        // CSPレポートエンドポイントの場合は application/csp-report または application/json を許可
        // W3C CSP Level 3 仕様では application/csp-report が標準だが、
        // 互換性のため application/json も受け付ける
        if ($request->is('api/csp/report')) {
            return str_contains($contentType, 'application/csp-report') ||
                   str_contains($contentType, 'application/json');
        }

        // 通常のエンドポイントは application/json のみ許可
        // Content-Type: application/json または application/json;charset=utf-8 を許可
        return str_contains($contentType, 'application/json');
    }

    /**
     * JSON 形式のエラーレスポンスを生成
     *
     * @param  string  $error  エラー種別
     * @param  string  $message  エラーメッセージ
     * @param  int  $statusCode  HTTP ステータスコード
     * @return Response JSON エラーレスポンス
     */
    private function jsonError(string $error, string $message, int $statusCode): Response
    {
        return response()->json([
            'error' => $error,
            'message' => $message,
        ], $statusCode);
    }
}

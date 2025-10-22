<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ETag Middleware
 *
 * GETリクエストのレスポンスにETagを生成し、
 * 条件付きGETリクエスト（If-None-Match）をサポートします。
 * - レスポンスボディからSHA256ハッシュを生成
 * - ETagヘッダーをレスポンスに設定
 * - If-None-MatchヘッダーとETagが一致する場合はHTTP 304返却
 * - レスポンスボディサイズが1MB以上の場合はスキップ
 * - POST/PUT/PATCH/DELETEリクエストはスキップ
 *
 * Requirements: 9.1, 9.2, 9.3, 9.4, 9.5, 9.6, 9.7
 */
final class ETag
{
    /**
     * ETag生成の最大レスポンスボディサイズ（バイト）
     * 1MB = 1,048,576 bytes
     */
    private const MAX_BODY_SIZE = 1048576;

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // リクエスト処理を実行
        $response = $next($request);

        // GETリクエストのみ処理（POST/PUT/PATCH/DELETEはスキップ）
        if ($request->method() !== 'GET') {
            return $response;
        }

        // レスポンスボディを取得
        $content = $response->getContent();
        if ($content === false) {
            $content = '';
        }

        // レスポンスボディサイズが1MB以上の場合はスキップ
        if (strlen($content) > self::MAX_BODY_SIZE) {
            return $response;
        }

        // レスポンスボディからSHA256ハッシュを生成
        $etag = '"'.hash('sha256', $content).'"';

        // If-None-Matchヘッダーの確認
        $ifNoneMatch = $request->header('If-None-Match');
        if ($ifNoneMatch !== null && $ifNoneMatch === $etag) {
            // ETagが一致する場合: HTTP 304を返す
            $response->setStatusCode(304);
            $response->setContent('');
        }

        // ETagヘッダーを設定
        $response->headers->set('ETag', $etag);

        return $response;
    }
}

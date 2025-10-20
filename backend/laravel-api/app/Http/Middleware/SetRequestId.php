<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Response;

/**
 * SetRequestId Middleware
 *
 * 全てのAPIリクエストに一意なリクエストIDを付与し、分散システム全体でリクエストを追跡可能にします。
 * - リクエストIDをUUIDv4形式で生成
 * - 既存のX-Request-Idヘッダーを継承
 * - リクエストとレスポンスの両方にヘッダーを設定
 * - ログコンテキストにリクエストIDを追加
 *
 * Requirements: 1.1, 1.2, 1.3
 */
final class SetRequestId
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 既存のリクエストIDを取得、なければUUIDv4を生成
        $requestId = $request->header('X-Request-Id')
            ?? (string) Uuid::uuid4();

        // リクエストヘッダーにリクエストIDを設定
        $request->headers->set('X-Request-Id', $requestId);

        // ログコンテキストにリクエストIDを追加
        Log::withContext([
            'request_id' => $requestId,
        ]);

        // 次のミドルウェアへ
        $response = $next($request);

        // レスポンスヘッダーにリクエストIDを設定
        $response->headers->set('X-Request-Id', $requestId);

        return $response;
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\Response;

/**
 * IdempotencyKey Middleware
 *
 * Idempotency-Keyヘッダーによる冪等性保証機能を実装し、
 * 重複リクエストの検出と防止を可能にします。
 * - POST/PUT/PATCH/DELETEリクエストのIdempotency検証
 * - Redisによる24時間キャッシュ管理
 * - ペイロード指紋比較による重複検出
 * - 同一ペイロードの場合はキャッシュ済みレスポンス返却
 * - 異なるペイロードの場合はHTTP 422返却
 * - Webhookエンドポイントでの重複防止
 *
 * Requirements: 7.1, 7.2, 7.3, 7.4, 7.5, 7.6, 7.7, 7.8
 */
final class IdempotencyKey
{
    /**
     * Idempotencyキーの有効期限（秒）
     * 24時間 = 86400秒
     */
    private const TTL_SECONDS = 86400;

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // GETリクエストはスキップ（読み取り専用操作は冪等性保証不要）
        if ($request->method() === 'GET') {
            return $next($request);
        }

        // データ変更操作（POST/PUT/PATCH/DELETE）のみ処理
        if (! in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            return $next($request);
        }

        // Idempotency-Keyヘッダーの取得
        $idempotencyKey = $request->header('Idempotency-Key');
        if (! $idempotencyKey) {
            // Idempotency-Keyヘッダーがない場合は通常処理
            return $next($request);
        }

        // Redisキーの生成: idempotency:{key}:{identifier}
        // 認証済みユーザーはuser_id、未認証はIPアドレスを使用
        $user = $request->user();
        $identifier = $user !== null ? "user:{$user->id}" : "ip:{$request->ip()}";
        $redisKey = sprintf('idempotency:%s:%s', $idempotencyKey, $identifier);

        // リクエストペイロードの指紋を生成
        $payload = $request->all();
        $payloadJson = json_encode($payload);
        if ($payloadJson === false) {
            $payloadJson = '{}';
        }
        $payloadFingerprint = hash('sha256', $payloadJson);

        // Redisから既存のIdempotencyレコードを取得
        $redis = Redis::connection();
        $cached = $redis->get($redisKey);

        if ($cached === null) {
            // 初回リクエスト: レスポンスを生成してRedisに保存
            $response = $next($request);

            $cacheData = [
                'payload_fingerprint' => $payloadFingerprint,
                'response' => [
                    'status' => $response->getStatusCode(),
                    'content' => $response->getContent(),
                    'headers' => $response->headers->all(),
                ],
            ];

            $redis->setex($redisKey, self::TTL_SECONDS, json_encode($cacheData));

            return $response;
        }

        // 2回目以降のリクエスト: ペイロード指紋を比較
        $cachedData = json_decode($cached, true);
        $cachedFingerprint = $cachedData['payload_fingerprint'];

        if ($payloadFingerprint !== $cachedFingerprint) {
            // ペイロード指紋が異なる場合: HTTP 422を返す
            $errorJson = json_encode([
                'error' => 'Idempotency-Key conflict',
                'message' => 'The same Idempotency-Key was used with a different request payload.',
            ]);
            if ($errorJson === false) {
                $errorJson = '{"error":"Idempotency-Key conflict"}';
            }

            return new Response(
                $errorJson,
                422,
                ['Content-Type' => 'application/json']
            );
        }

        // ペイロード指紋が一致する場合: キャッシュ済みレスポンスを返す
        $cachedResponse = $cachedData['response'];

        return new Response(
            $cachedResponse['content'],
            $cachedResponse['status'],
            $cachedResponse['headers']
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * DynamicRateLimit Middleware
 *
 * エンドポイントごとに動的なレート制限を適用し、
 * ブルートフォース攻撃やDDoS攻撃からシステムを保護します。
 * - Redis統合による分散レート制限
 * - エンドポイントタイプ別の制限値適用
 * - レート制限ヘッダー設定（X-RateLimit-*）
 * - レート制限超過時にHTTP 429返却
 * - Redisダウン時のグレースフルデグラデーション
 *
 * Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 3.7, 3.8, 3.9, 3.10, 3.11
 */
final class DynamicRateLimit
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $endpointType  エンドポイントタイプ（api/public/webhook/strict）
     */
    public function handle(Request $request, Closure $next, string $endpointType = 'api'): Response
    {
        try {
            // レート制限設定の取得
            $config = config("ratelimit.endpoints.{$endpointType}");
            if ($config === null) {
                // 設定が存在しない場合はレート制限をスキップ
                return $next($request);
            }

            $maxAttempts = $config['requests'];
            $decayMinutes = $config['per_minute'];

            // レート制限識別子の生成
            $identifier = $this->resolveIdentifier($request, $config['by']);

            // レート制限キーの生成（形式: rate_limit:{endpoint}:{identifier}）
            $key = "rate_limit:{$endpointType}:{$identifier}";

            // Redisストアの取得
            $cache = Cache::store('redis');

            // 現在のリクエスト数を取得
            $attempts = (int) $cache->get($key, 0);

            // TTLの計算（秒単位）
            $ttl = $decayMinutes * 60;
            $resetTime = now()->addSeconds($ttl)->getTimestamp();

            // レート制限超過チェック
            if ($attempts >= $maxAttempts) {
                return $this->buildRateLimitResponse($maxAttempts, 0, $resetTime);
            }

            // リクエストカウントを増加
            $newAttempts = $attempts + 1;
            $cache->put($key, $newAttempts, $ttl);

            // キーのTTLを設定（初回のみ）
            if ($attempts === 0) {
                $cache->add($key.':timer', true, $ttl);
            }

            // 次のミドルウェアへ
            $response = $next($request);

            // レート制限ヘッダーを追加
            return $this->addRateLimitHeaders(
                $response,
                $maxAttempts,
                $maxAttempts - $newAttempts,
                $resetTime
            );
        } catch (\Exception $e) {
            // Redis障害時はレート制限をスキップ（グレースフルデグラデーション）
            Log::warning('Rate limit check failed, skipping', [
                'error' => $e->getMessage(),
                'endpoint_type' => $endpointType,
            ]);

            return $next($request);
        }
    }

    /**
     * レート制限識別子を解決する
     *
     * @param  string  $by  識別子タイプ（ip/user/token）
     */
    private function resolveIdentifier(Request $request, string $by): string
    {
        return match ($by) {
            'user' => (string) ($request->user()->id ?? $request->ip() ?? 'unknown'),
            'token' => $request->bearerToken() ?? $request->ip() ?? 'unknown',
            'ip' => $request->ip() ?? 'unknown',
            default => $request->ip() ?? 'unknown',
        };
    }

    /**
     * レート制限超過レスポンスを構築する
     */
    private function buildRateLimitResponse(int $maxAttempts, int $remaining, int $resetTime): Response
    {
        $response = new Response('Too Many Requests', 429);

        return $this->addRateLimitHeaders($response, $maxAttempts, $remaining, $resetTime);
    }

    /**
     * レート制限ヘッダーを追加する
     */
    private function addRateLimitHeaders(
        Response $response,
        int $maxAttempts,
        int $remaining,
        int $resetTime
    ): Response {
        $response->headers->set('X-RateLimit-Limit', (string) $maxAttempts);
        $response->headers->set('X-RateLimit-Remaining', (string) max(0, $remaining));
        $response->headers->set('X-RateLimit-Reset', (string) $resetTime);

        return $response;
    }
}

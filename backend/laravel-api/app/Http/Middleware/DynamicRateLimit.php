<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Ddd\Application\RateLimit\Contracts\RateLimitMetrics;
use Ddd\Application\RateLimit\Contracts\RateLimitService;
use Ddd\Application\RateLimit\Services\EndpointClassifier;
use Ddd\Application\RateLimit\Services\KeyResolver;
use Ddd\Domain\RateLimit\ValueObjects\EndpointClassification;
use Ddd\Domain\RateLimit\ValueObjects\RateLimitKey;
use Ddd\Domain\RateLimit\ValueObjects\RateLimitResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * DynamicRateLimit Middleware
 *
 * エンドポイントごとに動的なレート制限を適用し、
 * ブルートフォース攻撃やDDoS攻撃からシステムを保護します。
 *
 * Phase 4実装:
 * - Application層サービス統合（EndpointClassifier、KeyResolver、RateLimitService、RateLimitMetrics）
 * - 強化されたHTTPヘッダー（X-RateLimit-Policy、X-RateLimit-Key、Retry-After）
 * - 429レスポンスのJSON形式ボディ
 * - DDD/クリーンアーキテクチャ準拠
 *
 * Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 3.7, 3.8, 3.9, 3.10, 3.11, 3.12
 */
final class DynamicRateLimit
{
    /**
     * @param  EndpointClassifier  $classifier  エンドポイント分類サービス
     * @param  KeyResolver  $keyResolver  レート制限キー解決サービス
     * @param  RateLimitService  $rateLimitService  レート制限チェックサービス
     * @param  RateLimitMetrics  $metrics  レート制限メトリクス記録サービス
     */
    public function __construct(
        private readonly EndpointClassifier $classifier,
        private readonly KeyResolver $keyResolver,
        private readonly RateLimitService $rateLimitService,
        private readonly RateLimitMetrics $metrics,
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            // エンドポイント分類を取得
            $classification = $this->classifier->classify($request);
            $rule = $classification->getRule();

            // レート制限キーを解決
            $key = $this->keyResolver->resolve($request, $classification);

            // レート制限チェック
            $result = $this->rateLimitService->checkLimit($key, $rule);

            // メトリクス記録
            $this->metrics->recordHit($key, $rule, $result->isAllowed(), $result->getAttempts());

            // レート制限超過チェック
            if ($result->isBlocked()) {
                // レート制限ブロックをメトリクスに記録
                $retryAfter = max(0, (int) $result->getResetAt()->diffInSeconds(now()));
                $this->metrics->recordBlock($key, $rule, $result->getAttempts(), $retryAfter);

                // 429レスポンスを返す
                return $this->buildRateLimitResponse($key, $classification, $result);
            }

            // 次のミドルウェアへ
            $response = $next($request);

            // レート制限ヘッダーを追加
            return $this->addRateLimitHeaders($response, $key, $classification, $result);
        } catch (\Exception $e) {
            // 例外発生時はグレースフルデグラデーション（レート制限をスキップ）
            Log::warning('Rate limit check failed, skipping', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $next($request);
        }
    }

    /**
     * レート制限超過レスポンスを構築する（429 Too Many Requests）
     */
    private function buildRateLimitResponse(
        RateLimitKey $key,
        EndpointClassification $classification,
        RateLimitResult $result
    ): Response {
        $retryAfter = max(0, (int) $result->getResetAt()->diffInSeconds(now()));

        // JSONボディを作成
        $body = json_encode([
            'message' => 'Too Many Requests',
            'retry_after' => $retryAfter,
        ], JSON_THROW_ON_ERROR);

        $response = new Response($body, 429, [
            'Content-Type' => 'application/json',
        ]);

        // レート制限ヘッダーを追加
        $response = $this->addRateLimitHeaders($response, $key, $classification, $result);

        // Retry-Afterヘッダーを追加
        $response->headers->set('Retry-After', (string) $retryAfter);

        return $response;
    }

    /**
     * レート制限ヘッダーを追加する
     *
     * 標準ヘッダー:
     * - X-RateLimit-Limit: 最大リクエスト数
     * - X-RateLimit-Remaining: 残りリクエスト数
     * - X-RateLimit-Reset: リセット時刻（Unix timestamp）
     *
     * 拡張ヘッダー:
     * - X-RateLimit-Policy: エンドポイント分類（例: public_unauthenticated）
     * - X-RateLimit-Key: レート制限キー（SHA-256ハッシュ化）
     */
    private function addRateLimitHeaders(
        Response $response,
        RateLimitKey $key,
        EndpointClassification $classification,
        RateLimitResult $result
    ): Response {
        $rule = $classification->getRule();

        // 標準ヘッダー
        $response->headers->set('X-RateLimit-Limit', (string) $rule->getMaxAttempts());
        $response->headers->set('X-RateLimit-Remaining', (string) max(0, $result->getRemaining()));
        $response->headers->set('X-RateLimit-Reset', (string) $result->getResetAt()->getTimestamp());

        // 拡張ヘッダー
        $response->headers->set('X-RateLimit-Policy', $classification->getType());
        $response->headers->set('X-RateLimit-Key', hash('sha256', $key->getKey()));

        return $response;
    }
}

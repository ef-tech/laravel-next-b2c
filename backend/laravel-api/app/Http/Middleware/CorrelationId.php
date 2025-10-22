<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Response;

/**
 * CorrelationId Middleware
 *
 * 分散トレーシング対応のCorrelation IDとW3C Trace Contextを継承・生成し、
 * マイクロサービス間のリクエスト追跡を可能にします。
 * - Correlation IDをUUIDv4形式で生成
 * - 既存のX-Correlation-Idヘッダーを継承
 * - W3C Trace Context（traceparent）を解析してCorrelation IDを抽出
 * - ログコンテキストにCorrelation ID、trace_id、span_idを追加
 *
 * Requirements: 1.4, 1.5, 1.6, 1.7
 */
final class CorrelationId
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $logContext = [];

        // W3C Trace Contextの解析
        $traceparent = $request->header('traceparent');
        if ($traceparent !== null) {
            $traceContext = $this->parseTraceparent($traceparent);
            if ($traceContext !== null) {
                $logContext['trace_id'] = $traceContext['trace_id'];
                $logContext['span_id'] = $traceContext['span_id'];

                // W3C Trace ContextのtraceIdをCorrelation IDとして使用
                $correlationId = $traceContext['trace_id'];
            }
        }

        // Correlation IDの取得または生成
        if (! isset($correlationId)) {
            $correlationId = $request->header('X-Correlation-Id')
                ?? (string) Uuid::uuid4();
        }

        // リクエストヘッダーにCorrelation IDを設定
        $request->headers->set('X-Correlation-Id', $correlationId);

        // ログコンテキストにCorrelation IDを追加
        $logContext['correlation_id'] = $correlationId;
        Log::withContext($logContext);

        // 次のミドルウェアへ
        $response = $next($request);

        // レスポンスヘッダーにCorrelation IDを設定
        $response->headers->set('X-Correlation-Id', $correlationId);

        // W3C Trace Contextが存在する場合はレスポンスヘッダーにも設定
        if ($traceparent !== null) {
            $response->headers->set('traceparent', $traceparent);
        }

        return $response;
    }

    /**
     * W3C Trace Context（traceparent）を解析する
     *
     * traceparent形式: version-trace-id-parent-id-trace-flags
     * 例: 00-0af7651916cd43dd8448eb211c80319c-b7ad6b7169203331-01
     *
     * @return array{trace_id: string, span_id: string}|null
     */
    private function parseTraceparent(string $traceparent): ?array
    {
        $parts = explode('-', $traceparent);

        // W3C Trace Contextは4つのパートで構成される
        if (count($parts) !== 4) {
            return null;
        }

        [$version, $traceId, $parentId, $traceFlags] = $parts;

        // versionは'00'（現在のバージョン）
        if ($version !== '00') {
            return null;
        }

        // trace-idは32文字の16進数
        if (strlen($traceId) !== 32 || ! ctype_xdigit($traceId)) {
            return null;
        }

        // parent-idは16文字の16進数
        if (strlen($parentId) !== 16 || ! ctype_xdigit($parentId)) {
            return null;
        }

        return [
            'trace_id' => $traceId,
            'span_id' => $parentId,
        ];
    }
}

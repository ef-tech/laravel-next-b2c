<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CspReportRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * V1 CSPレポートコントローラー
 *
 * Content Security Policy違反レポートを受信し記録します。
 */
class CspReportController extends Controller
{
    /**
     * CSP違反レポートを受信してログに記録
     */
    public function report(CspReportRequest $request): JsonResponse|Response
    {
        // Content-Type確認（application/csp-report または application/json）
        // charset等のパラメータを含む場合も許可するため str_contains を使用
        $contentType = $request->header('Content-Type');
        $allowedTypes = ['application/csp-report', 'application/json'];

        $isValidContentType = false;
        if ($contentType) {
            foreach ($allowedTypes as $type) {
                if (str_contains($contentType, $type)) {
                    $isValidContentType = true;
                    break;
                }
            }
        }

        if (! $isValidContentType) {
            return response()->json([
                'error' => 'Invalid Content-Type. Expected application/csp-report or application/json',
            ], 400);
        }

        // CSPレポートデータを取得（FormRequestによりバリデーション済み）
        // Note: application/csp-report Content-Typeの場合、Laravelが自動的にJSONデコードしないため、
        // 明示的に$request->json()を使用してJSONボディから取得する
        /** @var array<string, mixed> $cspReport */
        $cspReport = $request->json('csp-report') ?? $request->input('csp-report');

        // セキュリティログチャンネルに記録
        Log::channel('security')->warning('CSP Violation Detected', [
            'blocked_uri' => $cspReport['blocked-uri'] ?? 'unknown',
            'violated_directive' => $cspReport['violated-directive'] ?? 'unknown',
            'original_policy' => $cspReport['original-policy'] ?? 'unknown',
            'document_uri' => $cspReport['document-uri'] ?? 'unknown',
            'referrer' => $cspReport['referrer'] ?? '',
            'source_file' => $cspReport['source-file'] ?? '',
            'line_number' => $cspReport['line-number'] ?? null,
            'column_number' => $cspReport['column-number'] ?? null,
            'status_code' => $cspReport['status-code'] ?? null,
            'user_agent' => $request->userAgent(),
            'ip_address' => $request->ip(),
            'timestamp' => now()->utc()->toIso8601String(),
        ]);

        return response()->noContent();
    }
}

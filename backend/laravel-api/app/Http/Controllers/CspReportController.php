<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class CspReportController extends Controller
{
    /**
     * CSP 違反レポートを受信してログに記録
     *
     * @param  Request  $request  リクエストオブジェクト
     * @return JsonResponse|Response レスポンス
     */
    public function report(Request $request): JsonResponse|Response
    {
        // Content-Type が application/csp-report であることを確認
        if ($request->header('Content-Type') !== 'application/csp-report') {
            return response()->json([
                'error' => 'Invalid Content-Type',
            ], 400);
        }

        // CSP レポートデータを取得
        /** @var array<string, mixed> $cspReport */
        $cspReport = $request->json('csp-report', []);

        if (empty($cspReport)) {
            return response()->json([
                'error' => 'Empty CSP report',
            ], 400);
        }

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
            'timestamp' => now()->toIso8601String(),
        ]);

        return response()->noContent();
    }
}

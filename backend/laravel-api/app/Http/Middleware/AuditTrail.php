<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Ddd\Application\Shared\Services\Audit\AuditService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * AuditTrail Middleware
 *
 * 重要な操作の監査証跡を自動的に記録し、
 * セキュリティインシデント調査とコンプライアンス要件の遵守を可能にします。
 * - データ変更操作（POST/PUT/PATCH/DELETE）の監査イベント記録
 * - Application層のAuditServiceポート経由でイベント発火
 * - 非同期監査ログ記録（terminateメソッド）
 * - 機密データマスキング
 * - GETリクエスト（読み取り専用）では記録しない
 *
 * Requirements: 6.1, 6.2, 6.3, 6.4, 6.5, 6.6, 6.7
 */
final class AuditTrail
{
    /**
     * マスキング対象の機密フィールド
     *
     * @var array<int, string>
     */
    private const SENSITIVE_FIELDS = [
        'password',
        'password_confirmation',
        'token',
        'api_token',
        'access_token',
        'refresh_token',
        'secret',
        'api_key',
    ];

    /**
     * Create a new middleware instance.
     */
    public function __construct(
        private readonly AuditService $auditService
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // リクエスト開始時刻を記録
        $request->attributes->set('audit_start_time', microtime(true));

        return $next($request);
    }

    /**
     * Handle tasks after the response has been sent to the browser.
     *
     * レスポンス送信後に実行される非同期監査ログ記録。
     * クライアントのレスポンス時間に影響を与えない。
     */
    public function terminate(Request $request, Response $response): void
    {
        // 認証済みユーザーの確認
        $user = $request->user();
        if ($user === null) {
            return;
        }

        // GETリクエスト（読み取り専用操作）はスキップ
        if ($request->method() === 'GET') {
            return;
        }

        // データ変更操作（POST/PUT/PATCH/DELETE）のみ記録
        if (! in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            return;
        }

        // 監査イベントデータの構築
        $changes = $request->all();
        $changes = $this->maskSensitiveData($changes);

        $auditEvent = [
            'user_id' => $user->id,
            'action' => $request->method(),
            'resource' => $request->path(),
            'changes' => $changes,
            'ip' => $request->ip(),
            'timestamp' => now()->utc()->toIso8601String(),
        ];

        // Application層のAuditServiceポートを経由してイベント発火
        $this->auditService->recordEvent($auditEvent);
    }

    /**
     * 機密データをマスキングする
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function maskSensitiveData(array $data): array
    {
        foreach (self::SENSITIVE_FIELDS as $field) {
            if (isset($data[$field])) {
                $data[$field] = '***MASKED***';
            }
        }

        return $data;
    }
}

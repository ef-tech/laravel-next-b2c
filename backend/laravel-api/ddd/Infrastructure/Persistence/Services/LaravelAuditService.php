<?php

declare(strict_types=1);

namespace Ddd\Infrastructure\Persistence\Services;

use Ddd\Application\Shared\Services\Audit\AuditService;
use Illuminate\Support\Facades\Log;

/**
 * Laravel Audit Service Implementation
 *
 * AuditServiceポートの具象実装。
 * 監査イベントをログに記録し、機密データをマスキングします。
 *
 * Requirements: 5.2, 6.3, 15.2
 */
final readonly class LaravelAuditService implements AuditService
{
    /**
     * マスキング対象のフィールド名
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
     * 監査イベントを記録する
     *
     * 機密データを自動的にマスキングした上でログに記録します。
     *
     * @param  array<string, mixed>  $event  監査イベント情報
     */
    public function recordEvent(array $event): void
    {
        // 機密データのマスキング
        if (isset($event['changes']) && is_array($event['changes'])) {
            $event['changes'] = $this->maskSensitiveData($event['changes']);
        }

        // ログに記録
        Log::channel('stack')->info('Audit event recorded', $event);
    }

    /**
     * 機密データをマスキングする
     *
     * @param  array<string, mixed>  $data  マスキング対象のデータ
     * @return array<string, mixed> マスキング済みデータ
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

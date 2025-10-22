<?php

declare(strict_types=1);

namespace Ddd\Application\Shared\Services\Audit;

/**
 * Audit Service Port
 *
 * DDD/クリーンアーキテクチャに準拠した監査サービスのインターフェース。
 * HTTP層のミドルウェアからDIされ、重要操作の監査イベントを記録します。
 *
 * Requirements: 6.3, 15.4
 *
 * @psalm-api
 */
interface AuditService
{
    /**
     * 監査イベントを記録する
     *
     * @param  array<string, mixed>  $event  監査イベント情報
     *                                       - user_id: int ユーザーID
     *                                       - action: string アクション（例: 'create', 'update', 'delete'）
     *                                       - resource: string リソース（例: 'user', 'post'）
     *                                       - changes: array|null 変更内容（変更前後の差分）
     *                                       - ip: string IPアドレス
     *                                       - timestamp: string タイムスタンプ
     */
    public function recordEvent(array $event): void;
}

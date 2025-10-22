<?php

declare(strict_types=1);

use Ddd\Application\Shared\Services\Audit\AuditService;
use Ddd\Infrastructure\Persistence\Services\LaravelAuditService;
use Illuminate\Support\Facades\Log;

/**
 * LaravelAuditService 実装のテスト
 *
 * Requirements: 5.2, 6.3, 15.2
 */
describe('LaravelAuditService', function () {
    it('AuditServiceインターフェースを実装していること', function () {
        $service = new LaravelAuditService;

        expect($service)->toBeInstanceOf(AuditService::class);
    });

    it('監査イベントをログに記録できること', function () {
        Log::shouldReceive('channel')
            ->once()
            ->with('stack')
            ->andReturnSelf();

        Log::shouldReceive('info')
            ->once()
            ->with('Audit event recorded', \Mockery::on(function ($event) {
                return isset($event['user_id'])
                    && isset($event['action'])
                    && isset($event['resource'])
                    && $event['user_id'] === 1
                    && $event['action'] === 'create'
                    && $event['resource'] === 'post';
            }));

        $service = new LaravelAuditService;
        $service->recordEvent([
            'user_id' => 1,
            'action' => 'create',
            'resource' => 'post',
            'changes' => ['title' => 'New Post'],
            'ip' => '127.0.0.1',
            'timestamp' => '2025-10-20T00:00:00Z',
        ]);
    });

    it('監査イベントに機密データが含まれていてもマスキングされること', function () {
        Log::shouldReceive('channel')
            ->once()
            ->with('stack')
            ->andReturnSelf();

        Log::shouldReceive('info')
            ->once()
            ->with('Audit event recorded', \Mockery::on(function ($event) {
                return isset($event['changes']['password'])
                    && $event['changes']['password'] === '***MASKED***';
            }));

        $service = new LaravelAuditService;
        $service->recordEvent([
            'user_id' => 1,
            'action' => 'update',
            'resource' => 'user',
            'changes' => ['password' => 'secret123'],
            'ip' => '127.0.0.1',
            'timestamp' => '2025-10-20T00:00:00Z',
        ]);
    });
});

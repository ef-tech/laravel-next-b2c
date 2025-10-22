<?php

declare(strict_types=1);

use App\Http\Middleware\CorrelationId;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Ramsey\Uuid\Uuid;

/**
 * CorrelationId ミドルウェアのテスト
 *
 * Requirements: 1.4, 1.5, 1.6, 1.7
 */
describe('CorrelationId', function () {
    it('Correlation IDが生成されること', function () {
        $middleware = new CorrelationId;
        $request = Request::create('/test', 'GET');

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK');
        });

        expect($response->headers->has('X-Correlation-Id'))->toBeTrue();
        /** @var string $correlationId */
        $correlationId = $response->headers->get('X-Correlation-Id');
        expect(Uuid::isValid($correlationId))->toBeTrue();
    });

    it('既存のCorrelation IDを継承すること', function () {
        $middleware = new CorrelationId;
        $existingId = (string) Uuid::uuid4();
        $request = Request::create('/test', 'GET');
        $request->headers->set('X-Correlation-Id', $existingId);

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK');
        });

        expect($response->headers->get('X-Correlation-Id'))->toBe($existingId);
    });

    it('W3C Trace Context（traceparent）を継承すること', function () {
        $middleware = new CorrelationId;
        $traceparent = '00-0af7651916cd43dd8448eb211c80319c-b7ad6b7169203331-01';
        $request = Request::create('/test', 'GET');
        $request->headers->set('traceparent', $traceparent);

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK');
        });

        expect($response->headers->get('traceparent'))->toBe($traceparent);
    });

    it('W3C Trace ContextのtraceIdをCorrelation IDとして使用すること', function () {
        $middleware = new CorrelationId;
        $traceparent = '00-0af7651916cd43dd8448eb211c80319c-b7ad6b7169203331-01';
        $request = Request::create('/test', 'GET');
        $request->headers->set('traceparent', $traceparent);

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK');
        });

        $correlationId = $response->headers->get('X-Correlation-Id');
        // W3C Trace ContextのtraceId（32文字の16進数）をそのまま使用
        expect($correlationId)->toBe('0af7651916cd43dd8448eb211c80319c');
    });

    it('ログコンテキストにCorrelation IDが追加されること', function () {
        $middleware = new CorrelationId;
        $request = Request::create('/test', 'GET');

        Log::shouldReceive('withContext')
            ->once()
            ->with(\Mockery::on(function ($context) {
                return isset($context['correlation_id']) && Uuid::isValid($context['correlation_id']);
            }));

        $middleware->handle($request, function ($req) {
            return new Response('OK');
        });
    });

    it('traceparentが存在する場合はログコンテキストにtrace_idとspan_idが追加されること', function () {
        $middleware = new CorrelationId;
        $traceparent = '00-0af7651916cd43dd8448eb211c80319c-b7ad6b7169203331-01';
        $request = Request::create('/test', 'GET');
        $request->headers->set('traceparent', $traceparent);

        Log::shouldReceive('withContext')
            ->once()
            ->with(\Mockery::on(function ($context) {
                return isset($context['correlation_id'])
                    && isset($context['trace_id'])
                    && isset($context['span_id'])
                    && $context['trace_id'] === '0af7651916cd43dd8448eb211c80319c'
                    && $context['span_id'] === 'b7ad6b7169203331';
            }));

        $middleware->handle($request, function ($req) {
            return new Response('OK');
        });
    });
});

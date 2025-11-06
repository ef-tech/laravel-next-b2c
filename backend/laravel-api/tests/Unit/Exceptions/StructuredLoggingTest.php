<?php

declare(strict_types=1);

namespace Tests\Unit\Exceptions;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * 構造化ログ機能テスト
 *
 * Exception Handler が Log::withContext() を使用して
 * trace_id、error_code、user_id、request_path をログに追加することを検証
 */
final class StructuredLoggingTest extends TestCase
{
    /**
     * DomainException発生時、構造化ログコンテキストが記録される
     */
    public function test_domain_exception_logs_with_structured_context(): void
    {
        // Arrange
        $requestId = 'test-trace-id-12345';
        $userId = 1;

        // Log::withContext() 呼び出しを記録
        Log::shouldReceive('withContext')
            ->once()
            ->with(\Mockery::on(function (array $context) use ($requestId, $userId) {
                // trace_id が設定されていること
                $this->assertArrayHasKey('trace_id', $context);
                $this->assertSame($requestId, $context['trace_id']);

                // error_code が設定されていること
                $this->assertArrayHasKey('error_code', $context);
                $this->assertSame('validation_error', $context['error_code']); // ValidationException::getErrorCode() returns 'validation_error'

                // user_id が設定されていること（認証済みの場合）
                $this->assertArrayHasKey('user_id', $context);
                $this->assertSame($userId, $context['user_id']);

                // request_path が設定されていること
                $this->assertArrayHasKey('request_path', $context);
                $this->assertSame('/api/v1/test', $context['request_path']);

                return true;
            }))
            ->andReturn(Log::getFacadeRoot());

        // Log::error() 呼び出しを記録
        Log::shouldReceive('error')
            ->once()
            ->with(
                \Mockery::type('string'),
                \Mockery::type('array')
            );

        // Act: DomainExceptionを発生させる
        $request = Request::create('/api/v1/test', 'POST');
        $request->headers->set('X-Request-ID', $requestId);

        // 認証済みユーザーをモック（Request::user()が返すように設定）
        $user = \Mockery::mock(\Illuminate\Contracts\Auth\Authenticatable::class);
        $user->shouldReceive('getAuthIdentifier')->andReturn($userId);
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        $exception = \Ddd\Shared\Exceptions\ValidationException::invalidEmail('test@example.com');

        // Exception Handler を直接呼び出し
        $handler = app(\Illuminate\Contracts\Debug\ExceptionHandler::class);
        $response = $handler->render($request, $exception);

        // Assert
        $this->assertSame(400, $response->getStatusCode()); // ValidationException::getStatusCode() returns 400
    }

    /**
     * 未認証ユーザーの場合、user_id は null になる
     */
    public function test_unauthenticated_request_logs_null_user_id(): void
    {
        // Arrange
        $requestId = 'test-trace-id-67890';

        // Log::withContext() 呼び出しを記録
        Log::shouldReceive('withContext')
            ->once()
            ->with(\Mockery::on(function (array $context) {
                // user_id が null であること
                $this->assertArrayHasKey('user_id', $context);
                $this->assertNull($context['user_id']);

                return true;
            }))
            ->andReturn(Log::getFacadeRoot());

        Log::shouldReceive('error')
            ->once();

        // Act: 未認証リクエストでDomainExceptionを発生
        $request = Request::create('/api/v1/test', 'GET');
        $request->headers->set('X-Request-ID', $requestId);

        $exception = \Ddd\Shared\Exceptions\ValidationException::invalidUserId('-1');

        $handler = app(\Illuminate\Contracts\Debug\ExceptionHandler::class);
        $response = $handler->render($request, $exception);

        // Assert
        $this->assertSame(400, $response->getStatusCode());
    }

    /**
     * Request IDヘッダーがない場合、自動生成されたUUIDが使用される
     */
    public function test_auto_generated_request_id_is_logged(): void
    {
        // Arrange
        Log::shouldReceive('withContext')
            ->once()
            ->with(\Mockery::on(function (array $context) {
                // trace_id が UUID形式であること
                $this->assertArrayHasKey('trace_id', $context);
                $this->assertMatchesRegularExpression(
                    '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
                    $context['trace_id']
                );

                return true;
            }))
            ->andReturn(Log::getFacadeRoot());

        Log::shouldReceive('error')
            ->once();

        // Act: Request IDヘッダーなしでリクエスト
        $request = Request::create('/api/v1/test', 'POST');

        $exception = \Ddd\Shared\Exceptions\ValidationException::invalidName('too short');

        $handler = app(\Illuminate\Contracts\Debug\ExceptionHandler::class);
        $response = $handler->render($request, $exception);

        // Assert
        $this->assertSame(400, $response->getStatusCode());
    }

    /**
     * ValidationException発生時も構造化ログが記録される
     */
    public function test_validation_exception_logs_with_structured_context(): void
    {
        // Arrange
        $requestId = 'validation-test-id';

        Log::shouldReceive('withContext')
            ->once()
            ->with(\Mockery::on(function (array $context) use ($requestId) {
                $this->assertArrayHasKey('trace_id', $context);
                $this->assertSame($requestId, $context['trace_id']);

                $this->assertArrayHasKey('error_code', $context);
                $this->assertSame('validation_error', $context['error_code']);

                return true;
            }))
            ->andReturn(Log::getFacadeRoot());

        Log::shouldReceive('error')
            ->once();

        // Act: ValidationExceptionを発生
        $request = Request::create('/api/v1/users', 'POST');
        $request->headers->set('X-Request-ID', $requestId);

        $exception = \Illuminate\Validation\ValidationException::withMessages([
            'email' => ['The email field is required.'],
        ]);

        $handler = app(\Illuminate\Contracts\Debug\ExceptionHandler::class);
        $response = $handler->render($request, $exception);

        // Assert
        $this->assertSame(422, $response->getStatusCode());
    }

    /**
     * Throwable全般でも構造化ログが記録される
     */
    public function test_generic_throwable_logs_with_structured_context(): void
    {
        // Arrange
        $requestId = 'throwable-test-id';

        Log::shouldReceive('withContext')
            ->once()
            ->with(\Mockery::on(function (array $context) use ($requestId) {
                $this->assertArrayHasKey('trace_id', $context);
                $this->assertSame($requestId, $context['trace_id']);

                $this->assertArrayHasKey('error_code', $context);
                $this->assertSame('internal_server_error', $context['error_code']);

                return true;
            }))
            ->andReturn(Log::getFacadeRoot());

        Log::shouldReceive('error')
            ->once();

        // Act: 汎用Exceptionを発生
        $request = Request::create('/api/v1/test', 'GET');
        $request->headers->set('X-Request-ID', $requestId);

        $exception = new \RuntimeException('Unexpected error');

        $handler = app(\Illuminate\Contracts\Debug\ExceptionHandler::class);
        $response = $handler->render($request, $exception);

        // Assert
        $this->assertSame(500, $response->getStatusCode());
    }
}

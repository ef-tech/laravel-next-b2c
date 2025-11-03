<?php

declare(strict_types=1);

use Ddd\Shared\Exceptions\ApplicationException;
use Ddd\Shared\Exceptions\DomainException;
use Ddd\Shared\Exceptions\InfrastructureException;
use Illuminate\Support\Facades\Route;

/**
 * Exception Handler統合テスト
 *
 * RFC 7807 (Problem Details for HTTP APIs) 準拠のエラーレスポンス生成を検証する
 */

// Task 9.1: Exception Handler統合テスト
describe('Exception Handler RFC 7807 Integration', function () {
    // テスト用の具象DomainExceptionクラス
    beforeEach(function () {
        // テスト用ルートを定義
        Route::get('/test/domain-exception', function () {
            throw new class('Test domain exception message') extends DomainException
            {
                public function getStatusCode(): int
                {
                    return 400;
                }

                public function getErrorCode(): string
                {
                    return 'DOMAIN-TEST-4001';
                }

                protected function getTitle(): string
                {
                    return 'Domain Exception Test';
                }
            };
        });

        Route::get('/test/application-exception', function () {
            throw new class('Test application exception message') extends ApplicationException
            {
                protected int $statusCode = 404;

                protected string $errorCode = 'APP-TEST-4001';

                protected function getTitle(): string
                {
                    return 'Application Exception Test';
                }
            };
        });

        Route::get('/test/infrastructure-exception', function () {
            throw new class('Test infrastructure exception message') extends InfrastructureException
            {
                protected int $statusCode = 503;

                protected string $errorCode = 'INFRA-TEST-5001';

                protected function getTitle(): string
                {
                    return 'Infrastructure Exception Test';
                }
            };
        });
    });

    test('DomainException発生時にRFC 7807形式のレスポンスを返却する', function () {
        $response = $this->withHeader('X-Request-ID', '550e8400-e29b-41d4-a716-446655440000')
            ->get('/test/domain-exception');

        $response->assertStatus(400);

        // RFC 7807必須フィールドの検証
        $response->assertJsonStructure([
            'type',
            'title',
            'status',
            'detail',
        ]);

        // 拡張フィールドの検証
        $response->assertJsonStructure([
            'error_code',
            'trace_id',
            'instance',
            'timestamp',
        ]);

        // フィールド値の検証
        $response->assertJson([
            'title' => 'Domain Exception Test',
            'status' => 400,
            'detail' => 'Test domain exception message',
            'error_code' => 'DOMAIN-TEST-4001',
            'trace_id' => '550e8400-e29b-41d4-a716-446655440000',
            'instance' => '/test/domain-exception',
        ]);

        // type URIフィールドの検証
        expect($response->json('type'))->toContain('/errors/');
        expect($response->json('type'))->toContain('domain-test-4001');

        // timestampがISO 8601形式であることを検証
        expect($response->json('timestamp'))->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(\.\d+)?Z$/');
    });

    test('ApplicationException発生時にRFC 7807形式のレスポンスを返却する', function () {
        $response = $this->withHeader('X-Request-ID', '550e8400-e29b-41d4-a716-446655440001')
            ->get('/test/application-exception');

        $response->assertStatus(404);

        // RFC 7807必須フィールドの検証
        $response->assertJsonStructure([
            'type',
            'title',
            'status',
            'detail',
            'error_code',
            'trace_id',
            'instance',
            'timestamp',
        ]);

        // フィールド値の検証
        $response->assertJson([
            'title' => 'Application Exception Test',
            'status' => 404,
            'detail' => 'Test application exception message',
            'error_code' => 'APP-TEST-4001',
            'trace_id' => '550e8400-e29b-41d4-a716-446655440001',
            'instance' => '/test/application-exception',
        ]);
    });

    test('InfrastructureException発生時にRFC 7807形式のレスポンスを返却する', function () {
        $response = $this->withHeader('X-Request-ID', '550e8400-e29b-41d4-a716-446655440002')
            ->get('/test/infrastructure-exception');

        $response->assertStatus(503);

        // RFC 7807必須フィールドの検証
        $response->assertJsonStructure([
            'type',
            'title',
            'status',
            'detail',
            'error_code',
            'trace_id',
            'instance',
            'timestamp',
        ]);

        // フィールド値の検証
        $response->assertJson([
            'title' => 'Infrastructure Exception Test',
            'status' => 503,
            'detail' => 'Test infrastructure exception message',
            'error_code' => 'INFRA-TEST-5001',
            'trace_id' => '550e8400-e29b-41d4-a716-446655440002',
            'instance' => '/test/infrastructure-exception',
        ]);
    });

    test('Content-Typeヘッダーがapplication/problem+jsonであることを検証する', function () {
        $response = $this->get('/test/domain-exception');

        $response->assertHeader('Content-Type', 'application/problem+json');
    });
});

// Task 9.2: Request ID伝播フローテスト
describe('Request ID Propagation Flow', function () {
    // テスト用ルートを定義
    beforeEach(function () {
        Route::get('/test/exception-with-request-id', function () {
            throw new class('Test exception with Request ID') extends \Ddd\Shared\Exceptions\DomainException
            {
                public function getStatusCode(): int
                {
                    return 400;
                }

                public function getErrorCode(): string
                {
                    return 'DOMAIN-TEST-4001';
                }

                protected function getTitle(): string
                {
                    return 'Test Exception';
                }
            };
        });
    });

    test('Request IDがHTTPヘッダーで渡された場合、同じIDがレスポンスのtrace_idに設定される', function () {
        $requestId = '550e8400-e29b-41d4-a716-446655440000';

        $response = $this->withHeader('X-Request-ID', $requestId)
            ->get('/test/exception-with-request-id');

        $response->assertStatus(400);

        // trace_idがRequest IDと一致すること
        $response->assertJson([
            'trace_id' => $requestId,
        ]);

        // X-Request-IDヘッダーが返却されること
        $response->assertHeader('X-Request-ID', $requestId);
    });

    test('Request IDが渡されない場合、自動生成されたUUIDがtrace_idに設定される', function () {
        $response = $this->get('/test/exception-with-request-id');

        $response->assertStatus(400);

        // trace_idがUUID形式であること
        expect($response->json('trace_id'))
            ->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i');

        // X-Request-IDヘッダーが返却されること
        $response->assertHeader('X-Request-ID');

        // ヘッダーとtrace_idが一致すること
        expect($response->headers->get('X-Request-ID'))->toBe($response->json('trace_id'));
    });

    test('Request IDが構造化ログのコンテキストに含まれる', function () {
        // ログモックを設定
        \Illuminate\Support\Facades\Log::spy();

        $requestId = '550e8400-e29b-41d4-a716-446655440000';

        $this->withHeader('X-Request-ID', $requestId)
            ->get('/test/exception-with-request-id');

        // Log::withContext()にtrace_idが含まれていることを検証
        // Note: withContext()は複数回呼ばれる可能性がある（ミドルウェアやハンドラーで）
        \Illuminate\Support\Facades\Log::shouldHaveReceived('withContext')
            ->atLeast()
            ->once()
            ->with(\Mockery::on(function ($context) use ($requestId) {
                return isset($context['trace_id']) && $context['trace_id'] === $requestId;
            }));
    });
});

// Task 9.4: バリデーションエラー特別処理テスト
describe('Validation Error Special Handling', function () {
    // テスト用ルートを定義
    beforeEach(function () {
        Route::post('/test/validation', function (\Illuminate\Http\Request $request) {
            $request->validate([
                'email' => 'required|email',
                'name' => 'required|string|min:3',
                'age' => 'required|integer|min:18',
            ]);

            return response()->json(['success' => true]);
        });
    });

    test('バリデーションエラー発生時にRFC 7807形式のレスポンスを返却する', function () {
        $requestId = '550e8400-e29b-41d4-a716-446655440000';

        $response = $this->withHeader('X-Request-ID', $requestId)
            ->postJson('/test/validation', [
                'email' => 'invalid-email',
                'name' => 'ab', // min:3 validation error
                // age is missing
            ]);

        $response->assertStatus(422); // Unprocessable Entity

        // RFC 7807必須フィールド
        $response->assertJsonStructure([
            'type',
            'title',
            'status',
            'detail',
            'error_code',
            'trace_id',
            'instance',
            'timestamp',
        ]);

        // フィールド値の検証
        $response->assertJson([
            'title' => 'Validation Error',
            'status' => 422,
            'error_code' => 'validation_error',
            'trace_id' => $requestId,
            'instance' => '/test/validation',
        ]);

        // バリデーションエラー詳細が含まれること
        $response->assertJsonStructure([
            'errors' => [
                'email',
                'name',
                'age',
            ],
        ]);

        // Content-TypeがRFC 7807形式であること
        $response->assertHeader('Content-Type', 'application/problem+json');
        $response->assertHeader('X-Request-ID', $requestId);
    });

    test('バリデーションエラーのerrors詳細が正しいフォーマットで返却される', function () {
        $response = $this->postJson('/test/validation', [
            'email' => 'not-an-email',
            'name' => '',
            'age' => 15, // min:18 validation error
        ]);

        $response->assertStatus(422);

        // errors フィールドが正しい形式であること
        expect($response->json('errors'))->toBeArray()
            ->and($response->json('errors.email'))->toBeArray()
            ->and($response->json('errors.name'))->toBeArray()
            ->and($response->json('errors.age'))->toBeArray();

        // 各フィールドにエラーメッセージが含まれること
        expect($response->json('errors.email.0'))->toContain('email')
            ->and($response->json('errors.name.0'))->toContain('required')
            ->and($response->json('errors.age.0'))->toContain('18');
    });

    test('バリデーションエラーログが構造化ログコンテキストで記録される', function () {
        \Illuminate\Support\Facades\Log::spy();

        $requestId = '550e8400-e29b-41d4-a716-446655440000';

        $this->withHeader('X-Request-ID', $requestId)
            ->postJson('/test/validation', [
                'email' => 'invalid',
            ]);

        // Log::withContext()にtrace_idとerror_codeが含まれていることを検証
        \Illuminate\Support\Facades\Log::shouldHaveReceived('withContext')
            ->atLeast()
            ->once()
            ->with(\Mockery::on(function ($context) use ($requestId) {
                return isset($context['trace_id'])
                    && $context['trace_id'] === $requestId
                    && isset($context['error_code'])
                    && $context['error_code'] === 'validation_error';
            }));
    });
});

// Task 9.5: 環境別エラーマスキングテスト
describe('Environment-specific Error Masking', function () {
    // テスト用ルートを定義
    beforeEach(function () {
        Route::get('/test/generic-exception', function () {
            throw new \RuntimeException('Sensitive internal error information: Database password is "secret123"');
        });
    });

    test('本番環境（production）では内部エラー詳細がマスクされる', function () {
        // 本番環境に設定
        config(['app.env' => 'production']);
        config(['app.debug' => false]);

        $requestId = '550e8400-e29b-41d4-a716-446655440000';

        $response = $this->withHeader('X-Request-ID', $requestId)
            ->get('/test/generic-exception');

        $response->assertStatus(500);

        // RFC 7807必須フィールド
        $response->assertJsonStructure([
            'type',
            'title',
            'status',
            'detail',
            'error_code',
            'trace_id',
            'instance',
            'timestamp',
        ]);

        // 本番環境ではエラー詳細がマスクされること
        $response->assertJson([
            'title' => 'Internal Server Error',
            'status' => 500,
            'detail' => 'An internal server error occurred. Please try again later.',
            'error_code' => 'internal_server_error',
            'trace_id' => $requestId,
        ]);

        // debugフィールドが含まれないこと（本番環境）
        expect($response->json('debug'))->toBeNull();

        // 元のエラーメッセージが含まれていないこと
        expect($response->json('detail'))->not->toContain('secret123')
            ->and($response->json('detail'))->not->toContain('Sensitive');
    });

    test('開発環境（local + debug=true）では内部エラー詳細が表示される', function () {
        // 開発環境に設定
        config(['app.env' => 'local']);
        config(['app.debug' => true]);

        $requestId = '550e8400-e29b-41d4-a716-446655440000';

        $response = $this->withHeader('X-Request-ID', $requestId)
            ->get('/test/generic-exception');

        $response->assertStatus(500);

        // 開発環境では実際のエラーメッセージが表示されること
        $response->assertJson([
            'status' => 500,
            'error_code' => 'internal_server_error',
            'trace_id' => $requestId,
        ]);

        // 元のエラーメッセージが含まれること
        expect($response->json('detail'))->toContain('Sensitive internal error information');

        // debugフィールドが含まれること（開発環境 + debug=true）
        expect($response->json('debug'))->toBeArray()
            ->and($response->json('debug.exception'))->toBe(\RuntimeException::class)
            ->and($response->json('debug.file'))->toBeString()
            ->and($response->json('debug.line'))->toBeInt()
            ->and($response->json('debug.trace'))->toBeArray();
    });

    test('開発環境でもdebug=falseの場合はデバッグ情報が含まれない', function () {
        // 開発環境だがdebug=false
        config(['app.env' => 'local']);
        config(['app.debug' => false]);

        $response = $this->get('/test/generic-exception');

        $response->assertStatus(500);

        // 実際のエラーメッセージは表示されるが、debugフィールドは含まれない
        expect($response->json('detail'))->toContain('Sensitive internal error information');
        expect($response->json('debug'))->toBeNull();
    });
});

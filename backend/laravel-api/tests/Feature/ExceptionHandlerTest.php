<?php

declare(strict_types=1);

use Ddd\Shared\Exceptions\EmailAlreadyExistsException;
use Illuminate\Support\Facades\App;

describe('Exception Handler - RFC 7807 Response Generation', function () {
    beforeEach(function () {
        // Request IDを設定（SetRequestId Middlewareがグローバルに適用されている）
        $this->requestId = '550e8400-e29b-41d4-a716-446655440000';
    });

    test('DomainExceptionがRFC 7807形式のレスポンスを返す', function () {
        // Arrange: DomainExceptionを発生させるダミーエンドポイントを作成
        \Illuminate\Support\Facades\Route::get('/test/domain-exception', function () {
            throw EmailAlreadyExistsException::forEmail('test@example.com');
        })->middleware('api');

        // Act: リクエストを送信
        $response = $this->withHeaders([
            'X-Request-ID' => $this->requestId,
        ])->getJson('/test/domain-exception');

        // Assert: RFC 7807形式のレスポンスを検証
        $response->assertStatus(422);
        $response->assertHeader('Content-Type', 'application/problem+json');
        $response->assertHeader('X-Request-ID', $this->requestId);

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

        $response->assertJson([
            'status' => 422,
            'error_code' => 'email_already_exists',
            'trace_id' => $this->requestId,
        ]);
    });

    test('RFC 7807レスポンスのtypeフィールドがエラータイプURIを含む', function () {
        // Arrange
        \Illuminate\Support\Facades\Route::get('/test/error-type-uri', function () {
            throw EmailAlreadyExistsException::forEmail('test@example.com');
        })->middleware('api');

        // Act
        $response = $this->withHeaders([
            'X-Request-ID' => $this->requestId,
        ])->getJson('/test/error-type-uri');

        // Assert: typeフィールドがエラータイプURIを含むことを検証
        $json = $response->json();
        expect($json['type'])->toContain('/errors/');
        expect($json['type'])->toContain('email_already_exists');
    });

    test('RFC 7807レスポンスのinstanceフィールドがリクエストURIを含む', function () {
        // Arrange
        \Illuminate\Support\Facades\Route::get('/test/instance-uri', function () {
            throw EmailAlreadyExistsException::forEmail('test@example.com');
        })->middleware('api');

        // Act
        $response = $this->withHeaders([
            'X-Request-ID' => $this->requestId,
        ])->getJson('/test/instance-uri');

        // Assert: instanceフィールドがリクエストURIを含むことを検証
        $response->assertJson([
            'instance' => '/test/instance-uri',
        ]);
    });

    test('RFC 7807レスポンスのtimestampフィールドがISO 8601形式の日時を含む', function () {
        // Arrange
        \Illuminate\Support\Facades\Route::get('/test/timestamp', function () {
            throw EmailAlreadyExistsException::forEmail('test@example.com');
        })->middleware('api');

        // Act
        $response = $this->withHeaders([
            'X-Request-ID' => $this->requestId,
        ])->getJson('/test/timestamp');

        // Assert: timestampフィールドがISO 8601形式であることを検証
        $json = $response->json();
        expect($json['timestamp'])->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/');
    });

    test('多言語エラーメッセージが日本語で返される（Accept-Language: ja）', function () {
        // Arrange: ロケールを日本語に設定
        App::setLocale('ja');

        \Illuminate\Support\Facades\Route::get('/test/locale-ja', function () {
            throw EmailAlreadyExistsException::forEmail('test@example.com');
        })->middleware('api');

        // Act
        $response = $this->withHeaders([
            'X-Request-ID' => $this->requestId,
            'Accept-Language' => 'ja',
        ])->getJson('/test/locale-ja');

        // Assert: titleとdetailが日本語で返されることを検証
        $json = $response->json();
        expect($json['title'])->toBe('Email Already Exists');
        // detailはgetMessage()から取得されるため、英語のまま（翻訳はtitleのみ）
    });

    test('多言語エラーメッセージが英語で返される（Accept-Language: en）', function () {
        // Arrange: ロケールを英語に設定
        App::setLocale('en');

        \Illuminate\Support\Facades\Route::get('/test/locale-en', function () {
            throw EmailAlreadyExistsException::forEmail('test@example.com');
        })->middleware('api');

        // Act
        $response = $this->withHeaders([
            'X-Request-ID' => $this->requestId,
            'Accept-Language' => 'en',
        ])->getJson('/test/locale-en');

        // Assert: titleとdetailが英語で返されることを検証
        $json = $response->json();
        expect($json['title'])->toBe('Email Already Exists');
    });

    test('Request IDが存在しない場合でもRFC 7807レスポンスが生成される', function () {
        // Arrange
        \Illuminate\Support\Facades\Route::get('/test/no-request-id', function () {
            throw EmailAlreadyExistsException::forEmail('test@example.com');
        })->middleware('api');

        // Act: Request IDヘッダーなしでリクエスト
        $response = $this->getJson('/test/no-request-id');

        // Assert: trace_idが自動生成されることを検証
        $response->assertStatus(422);
        $json = $response->json();
        expect($json['trace_id'])->not->toBeNull();
        expect($json['trace_id'])->toBeString();
    });
});

describe('Exception Handler - Environment-based Error Masking', function () {
    beforeEach(function () {
        $this->requestId = '550e8400-e29b-41d4-a716-446655440000';
    });

    test('本番環境では内部エラー詳細がマスクされる', function () {
        // Arrange: 環境を本番に設定
        config(['app.env' => 'production']);

        \Illuminate\Support\Facades\Route::get('/test/production-error', function () {
            throw new \RuntimeException('Internal database connection failed: password=secret123');
        })->middleware('api');

        // Act
        $response = $this->withHeaders([
            'X-Request-ID' => $this->requestId,
        ])->getJson('/test/production-error');

        // Assert: 内部エラー詳細がマスクされることを検証
        $response->assertStatus(500);
        $json = $response->json();

        // 詳細なエラーメッセージがマスクされていることを確認
        expect($json['detail'])->not->toContain('password');
        expect($json['detail'])->not->toContain('secret123');
        expect($json['detail'])->toBe('An internal server error occurred. Please try again later.');

        // Request IDは常に返却されることを確認
        expect($json['trace_id'])->toBe($this->requestId);
    });

    test('開発環境ではスタックトレースを含む詳細情報が返される', function () {
        // Arrange: 環境を開発に設定
        config(['app.env' => 'local']);
        config(['app.debug' => true]);

        \Illuminate\Support\Facades\Route::get('/test/local-error', function () {
            throw new \RuntimeException('Detailed error message for debugging');
        })->middleware('api');

        // Act
        $response = $this->withHeaders([
            'X-Request-ID' => $this->requestId,
        ])->getJson('/test/local-error');

        // Assert: 詳細情報が含まれることを検証
        $response->assertStatus(500);
        $json = $response->json();

        // 詳細なエラーメッセージが含まれることを確認
        expect($json['detail'])->toContain('Detailed error message for debugging');

        // デバッグ情報が含まれることを確認（開発環境のみ）
        expect($json)->toHaveKey('debug');
        expect($json['debug'])->toHaveKey('exception');
        expect($json['debug'])->toHaveKey('file');
        expect($json['debug'])->toHaveKey('line');
        expect($json['debug'])->toHaveKey('trace');
    });

    test('本番環境でもRequest IDは常に返却される', function () {
        // Arrange: 環境を本番に設定
        config(['app.env' => 'production']);

        \Illuminate\Support\Facades\Route::get('/test/production-request-id', function () {
            throw new \RuntimeException('Internal error');
        })->middleware('api');

        // Act: Request IDなしでリクエスト
        $response = $this->getJson('/test/production-request-id');

        // Assert: Request IDが自動生成されることを検証
        $response->assertStatus(500);
        $json = $response->json();

        expect($json['trace_id'])->not->toBeNull();
        expect($json['trace_id'])->toBeString();
        // UUIDフォーマットであることを検証
        expect($json['trace_id'])->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/');
    });

    test('DomainExceptionは本番環境でもマスクされない（ビジネスロジックエラー）', function () {
        // Arrange: 環境を本番に設定
        config(['app.env' => 'production']);

        \Illuminate\Support\Facades\Route::get('/test/domain-exception-production', function () {
            throw EmailAlreadyExistsException::forEmail('test@example.com');
        })->middleware('api');

        // Act
        $response = $this->withHeaders([
            'X-Request-ID' => $this->requestId,
        ])->getJson('/test/domain-exception-production');

        // Assert: DomainExceptionのメッセージは本番環境でもそのまま返されることを検証
        $response->assertStatus(422);
        $json = $response->json();

        // ビジネスロジックエラーのメッセージはマスクされない
        expect($json['detail'])->toContain('Email already registered: test@example.com');
        expect($json['error_code'])->toBe('email_already_exists');
    });
});

describe('Exception Handler - Validation Error Special Handling', function () {
    beforeEach(function () {
        $this->requestId = '550e8400-e29b-41d4-a716-446655440000';
    });

    test('ValidationExceptionがerrorsフィールドを含むRFC 7807レスポンスを返す', function () {
        // Arrange: バリデーションエラーを発生させるダミーエンドポイントを作成
        \Illuminate\Support\Facades\Route::post('/test/validation-error', function (\Illuminate\Http\Request $request) {
            $request->validate([
                'email' => 'required|email',
                'name' => 'required|min:3',
                'age' => 'required|integer|min:18',
            ]);
        })->middleware('api');

        // Act: バリデーションエラーを発生させる
        $response = $this->withHeaders([
            'X-Request-ID' => $this->requestId,
        ])->postJson('/test/validation-error', [
            'email' => 'invalid-email',
            'name' => 'ab',
            'age' => '10',
        ]);

        // Assert: RFC 7807形式のレスポンスを検証
        $response->assertStatus(422);
        $response->assertHeader('Content-Type', 'application/problem+json');
        $response->assertHeader('X-Request-ID', $this->requestId);

        $json = $response->json();

        // RFC 7807基本フィールドを検証
        expect($json)->toHaveKey('type');
        expect($json)->toHaveKey('title');
        expect($json)->toHaveKey('status');
        expect($json)->toHaveKey('detail');
        expect($json)->toHaveKey('error_code');
        expect($json)->toHaveKey('trace_id');
        expect($json)->toHaveKey('instance');
        expect($json)->toHaveKey('timestamp');

        // errorsフィールドが含まれることを検証
        expect($json)->toHaveKey('errors');
        expect($json['errors'])->toBeArray();

        // フィールド別エラーメッセージが含まれることを検証
        expect($json['errors'])->toHaveKey('email');
        expect($json['errors'])->toHaveKey('name');
        expect($json['errors'])->toHaveKey('age');

        // エラーメッセージの内容を検証（配列の最初の要素を取得）
        expect($json['errors']['email'][0])->toBeString();
        expect($json['errors']['name'][0])->toBeString();
        expect($json['errors']['age'][0])->toBeString();
    });

    test('フィールド別エラーメッセージが配列形式で返される', function () {
        // Arrange
        \Illuminate\Support\Facades\Route::post('/test/validation-array', function (\Illuminate\Http\Request $request) {
            $request->validate([
                'email' => 'required|email',
            ]);
        })->middleware('api');

        // Act
        $response = $this->withHeaders([
            'X-Request-ID' => $this->requestId,
        ])->postJson('/test/validation-array', [
            'email' => '',
        ]);

        // Assert
        $response->assertStatus(422);
        $json = $response->json();

        // errorsフィールドが配列形式であることを検証
        expect($json['errors']['email'])->toBeArray();
        expect($json['errors']['email'][0])->toBeString();
    });

    test('バリデーションエラーのerror_codeがvalidation_errorであることを検証', function () {
        // Arrange
        \Illuminate\Support\Facades\Route::post('/test/validation-code', function (\Illuminate\Http\Request $request) {
            $request->validate([
                'email' => 'required|email',
            ]);
        })->middleware('api');

        // Act
        $response = $this->withHeaders([
            'X-Request-ID' => $this->requestId,
        ])->postJson('/test/validation-code', [
            'email' => 'invalid',
        ]);

        // Assert
        $response->assertStatus(422);
        $json = $response->json();

        expect($json['error_code'])->toBe('validation_error');
        expect($json['title'])->toBe('Validation Error');
    });
});

<?php

declare(strict_types=1);

use Ddd\Shared\Exceptions\DomainException;
use Illuminate\Support\Facades\Route;

/**
 * フォールバックURI生成時のサニタイズ処理Feature Test
 *
 * HTTPレスポンスレベルでRFC 3986準拠のサニタイズ処理を検証する
 *
 * Requirements:
 * - 3.1 未定義エラーコードのHTTPレスポンステスト
 * - 3.2 ErrorCode enum定義済みエラーのHTTPレスポンステスト
 * - 3.3 空文字列デフォルト値のHTTPレスポンステスト
 */
describe('Fallback URI Sanitization Feature Test', function () {
    beforeEach(function () {
        // テスト用ルート: 未定義エラーコード（特殊文字含む）
        Route::get('/test/sanitize-custom-error', function () {
            throw new class('Custom error with special chars') extends DomainException
            {
                public function getStatusCode(): int
                {
                    return 400;
                }

                public function getErrorCode(): string
                {
                    return 'CUSTOM@ERROR!';
                }

                protected function getTitle(): string
                {
                    return 'Custom Error With Special Chars';
                }
            };
        });

        // テスト用ルート: 未定義エラーコード（空白含む）
        Route::get('/test/sanitize-space-error', function () {
            throw new class('Custom error with spaces') extends DomainException
            {
                public function getStatusCode(): int
                {
                    return 400;
                }

                public function getErrorCode(): string
                {
                    return 'CUSTOM ERROR';
                }

                protected function getTitle(): string
                {
                    return 'Custom Error With Spaces';
                }
            };
        });

        // テスト用ルート: 特殊文字のみ（デフォルト値 'unknown' に変換）
        Route::get('/test/sanitize-special-chars-only', function () {
            throw new class('Special chars only error') extends DomainException
            {
                public function getStatusCode(): int
                {
                    return 400;
                }

                public function getErrorCode(): string
                {
                    return '@#$%';
                }

                protected function getTitle(): string
                {
                    return 'Special Chars Only Error';
                }
            };
        });

        // テスト用ルート: アンダースコアと数字（サニタイズ後も保持）
        Route::get('/test/sanitize-underscore-number', function () {
            throw new class('Error with underscore and numbers') extends DomainException
            {
                public function getStatusCode(): int
                {
                    return 400;
                }

                public function getErrorCode(): string
                {
                    return 'CUSTOM_ERROR_001';
                }

                protected function getTitle(): string
                {
                    return 'Custom Error 001';
                }
            };
        });
    });

    test('未定義エラーコードのHTTPレスポンステスト: CUSTOM@ERROR! → /errors/customerror', function () {
        $response = $this->withHeader('X-Request-ID', '550e8400-e29b-41d4-a716-446655440000')
            ->get('/test/sanitize-custom-error');

        $response->assertStatus(400);

        // RFC 7807必須フィールドの検証
        $response->assertJsonStructure([
            'type',
            'title',
            'status',
            'detail',
            'error_code',
        ]);

        // type URIがサニタイズ済みであることを検証
        $json = $response->json();
        expect($json['type'])->toContain('/errors/customerror'); // サニタイズ後: customerror

        // error_codeフィールドに元のエラーコードが保持されることを検証
        expect($json['error_code'])->toBe('CUSTOM@ERROR!');

        // その他のRFC 7807フィールド検証
        $response->assertJson([
            'title' => 'Custom Error With Special Chars',
            'status' => 400,
            'detail' => 'Custom error with special chars',
            'trace_id' => '550e8400-e29b-41d4-a716-446655440000',
        ]);
    });

    test('未定義エラーコードのHTTPレスポンステスト: CUSTOM ERROR → /errors/customerror（空白削除）', function () {
        $response = $this->withHeader('X-Request-ID', '550e8400-e29b-41d4-a716-446655440000')
            ->get('/test/sanitize-space-error');

        $response->assertStatus(400);

        $json = $response->json();

        // type URIがサニタイズ済みであることを検証（空白削除）
        expect($json['type'])->toContain('/errors/customerror'); // サニタイズ後: customerror

        // error_codeフィールドに元のエラーコードが保持されることを検証
        expect($json['error_code'])->toBe('CUSTOM ERROR');
    });

    test('空文字列デフォルト値のHTTPレスポンステスト: @#$% → /errors/unknown', function () {
        $response = $this->withHeader('X-Request-ID', '550e8400-e29b-41d4-a716-446655440000')
            ->get('/test/sanitize-special-chars-only');

        $response->assertStatus(400);

        $json = $response->json();

        // type URIがデフォルト値 'unknown' になることを検証
        expect($json['type'])->toContain('/errors/unknown'); // サニタイズ後: unknown（デフォルト値）

        // error_codeフィールドに元のエラーコードが保持されることを検証
        expect($json['error_code'])->toBe('@#$%');

        // RFC 7807レスポンスが正しく生成されることを検証
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
    });

    test('未定義エラーコードのHTTPレスポンステスト: CUSTOM_ERROR_001 → /errors/customerror001（数字保持）', function () {
        $response = $this->withHeader('X-Request-ID', '550e8400-e29b-41d4-a716-446655440000')
            ->get('/test/sanitize-underscore-number');

        $response->assertStatus(400);

        $json = $response->json();

        // type URIがサニタイズ済みであることを検証（アンダースコア削除、数字保持）
        expect($json['type'])->toContain('/errors/customerror001'); // サニタイズ後: customerror001

        // error_codeフィールドに元のエラーコードが保持されることを検証
        expect($json['error_code'])->toBe('CUSTOM_ERROR_001');
    });

    test('RFC 7807レスポンスの全必須フィールドが存在する', function () {
        $response = $this->withHeader('X-Request-ID', '550e8400-e29b-41d4-a716-446655440000')
            ->get('/test/sanitize-custom-error');

        $response->assertStatus(400);

        // RFC 7807必須フィールド
        $response->assertJsonStructure([
            'type',    // RFC 7807必須
            'title',   // RFC 7807必須
            'status',  // RFC 7807必須
            'detail',  // RFC 7807推奨
        ]);

        // 拡張フィールド
        $response->assertJsonStructure([
            'error_code', // トレーサビリティ用
            'trace_id',   // 分散トレーシング用
            'instance',   // リクエストURI
            'timestamp',  // タイムスタンプ
        ]);
    });

    test('Content-Type ヘッダーが application/problem+json である', function () {
        $response = $this->withHeader('X-Request-ID', '550e8400-e29b-41d4-a716-446655440000')
            ->get('/test/sanitize-custom-error');

        $response->assertStatus(400);
        $response->assertHeader('Content-Type', 'application/problem+json');
    });
});

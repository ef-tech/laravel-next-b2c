<?php

declare(strict_types=1);

use App\Bootstrap\ValidateEnvironment;
use Illuminate\Foundation\Application;

describe('ValidateEnvironment', function () {
    beforeEach(function () {
        // テスト用の環境変数をバックアップ
        $this->originalEnv = $_ENV;
    });

    afterEach(function () {
        // 環境変数を復元
        $_ENV = $this->originalEnv;
    });

    describe('正常系', function () {
        test('全ての必須環境変数が設定されている場合、例外が発生しないこと', function () {
            // 必須環境変数を設定
            $_ENV['APP_NAME'] = 'Laravel';
            $_ENV['APP_ENV'] = 'local';
            $_ENV['APP_KEY'] = 'base64:test_key_32_characters_long';
            $_ENV['APP_DEBUG'] = 'true';
            $_ENV['APP_URL'] = 'http://localhost';
            $_ENV['DB_CONNECTION'] = 'sqlite';
            $_ENV['CORS_ALLOWED_ORIGINS'] = 'http://localhost:13001,http://localhost:13002';

            $app = Mockery::mock(Application::class);

            expect(function () use ($app) {
                $bootstrapper = new ValidateEnvironment;
                $bootstrapper->bootstrap($app);
            })->not->toThrow(RuntimeException::class);
        });

        test('ENV_VALIDATION_SKIP=trueの場合、バリデーションがスキップされること', function () {
            // 必須環境変数を設定しない（エラーになるはず）
            $_ENV['ENV_VALIDATION_SKIP'] = 'true';
            $_ENV['APP_ENV'] = 'local';

            $app = Mockery::mock(Application::class);

            // スキップされるため例外が発生しない
            expect(function () use ($app) {
                $bootstrapper = new ValidateEnvironment;
                $bootstrapper->bootstrap($app);
            })->not->toThrow(RuntimeException::class);
        });

        test('ENV_VALIDATION_MODE=warningの場合、エラーがあっても例外が発生しないこと', function () {
            // 必須環境変数を設定しない（通常はエラー）
            $_ENV['ENV_VALIDATION_MODE'] = 'warning';
            $_ENV['APP_ENV'] = 'local';

            $app = Mockery::mock(Application::class);

            // 警告モードのため例外が発生しない
            expect(function () use ($app) {
                $bootstrapper = new ValidateEnvironment;
                $bootstrapper->bootstrap($app);
            })->not->toThrow(RuntimeException::class);
        });
    });

    describe('異常系', function () {
        test('必須環境変数が設定されていない場合、RuntimeExceptionが発生すること', function () {
            // 環境変数をクリアしてから必要な変数のみ設定
            $_ENV = [];
            // APP_NAMEを設定しない
            $_ENV['APP_ENV'] = 'local';
            $_ENV['APP_KEY'] = 'base64:test_key_32_characters_long';
            $_ENV['APP_DEBUG'] = 'true';
            $_ENV['APP_URL'] = 'http://localhost';
            $_ENV['DB_CONNECTION'] = 'sqlite';
            $_ENV['CORS_ALLOWED_ORIGINS'] = 'http://localhost:13001';

            $app = Mockery::mock(Application::class);

            expect(function () use ($app) {
                $bootstrapper = new ValidateEnvironment;
                $bootstrapper->bootstrap($app);
            })->toThrow(RuntimeException::class);
        });

        test('環境変数の型が不正な場合、RuntimeExceptionが発生すること', function () {
            $_ENV['APP_NAME'] = 'Laravel';
            $_ENV['APP_ENV'] = 'invalid_env'; // 許可されていない値
            $_ENV['APP_KEY'] = 'base64:test_key_32_characters_long';
            $_ENV['APP_DEBUG'] = 'true';
            $_ENV['APP_URL'] = 'http://localhost';
            $_ENV['DB_CONNECTION'] = 'sqlite';
            $_ENV['CORS_ALLOWED_ORIGINS'] = 'http://localhost:13001';

            $app = Mockery::mock(Application::class);

            expect(function () use ($app) {
                $bootstrapper = new ValidateEnvironment;
                $bootstrapper->bootstrap($app);
            })->toThrow(RuntimeException::class);
        });

        test('例外メッセージにエラー詳細が含まれること', function () {
            // 環境変数をクリアしてから必要な変数のみ設定
            $_ENV = [];
            // APP_NAMEを設定しない
            $_ENV['APP_ENV'] = 'local';
            $_ENV['APP_KEY'] = 'base64:test_key_32_characters_long';
            $_ENV['APP_DEBUG'] = 'true';
            $_ENV['APP_URL'] = 'http://localhost';
            $_ENV['DB_CONNECTION'] = 'sqlite';
            $_ENV['CORS_ALLOWED_ORIGINS'] = 'http://localhost:13001';

            $app = Mockery::mock(Application::class);

            try {
                $bootstrapper = new ValidateEnvironment;
                $bootstrapper->bootstrap($app);
                throw new Exception('Expected RuntimeException was not thrown');
            } catch (RuntimeException $e) {
                expect($e->getMessage())->toContain('Environment variable validation failed');
                expect($e->getMessage())->toContain('APP_NAME');
            }
        });

        test('条件付き必須変数のチェックが機能すること', function () {
            $_ENV['APP_NAME'] = 'Laravel';
            $_ENV['APP_ENV'] = 'local';
            $_ENV['APP_KEY'] = 'base64:test_key_32_characters_long';
            $_ENV['APP_DEBUG'] = 'true';
            $_ENV['APP_URL'] = 'http://localhost';
            $_ENV['DB_CONNECTION'] = 'pgsql'; // PostgreSQL を選択
            // DB_HOSTを設定しない（条件付き必須でエラーになるはず）
            $_ENV['CORS_ALLOWED_ORIGINS'] = 'http://localhost:13001';

            $app = Mockery::mock(Application::class);

            expect(function () use ($app) {
                $bootstrapper = new ValidateEnvironment;
                $bootstrapper->bootstrap($app);
            })->toThrow(RuntimeException::class);
        });
    });
});

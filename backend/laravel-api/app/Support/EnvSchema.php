<?php

declare(strict_types=1);

namespace App\Support;

class EnvSchema
{
    /**
     * 環境変数スキーマ定義を取得
     *
     * @return array<string, array<string, mixed>>
     */
    public static function getSchema(): array
    {
        return [
            // ============================================
            // Laravel Application Configuration
            // ============================================
            'APP_NAME' => [
                'required' => true,
                'type' => 'string',
                'default' => 'Laravel',
                'description' => 'アプリケーション名（ログ、メール送信元名等に使用）',
            ],

            'APP_ENV' => [
                'required' => true,
                'type' => 'string',
                'allowed_values' => ['local', 'testing', 'staging', 'production'],
                'default' => 'local',
                'description' => 'アプリケーション実行環境',
            ],

            'APP_KEY' => [
                'required' => true,
                'type' => 'string',
                'security_level' => 'high',
                'description' => 'Laravel暗号化キー（AES-256-CBC、base64エンコード32文字）',
            ],

            'APP_DEBUG' => [
                'required' => true,
                'type' => 'boolean',
                'default' => true,
                'description' => 'デバッグモード（true=詳細エラー表示、false=汎用エラーページ）',
            ],

            'APP_URL' => [
                'required' => true,
                'type' => 'url',
                'default' => 'http://localhost',
                'description' => 'アプリケーションのベースURL（URL生成、CORS、Sanctum等で使用）',
            ],

            // ============================================
            // Database Configuration
            // ============================================
            'DB_CONNECTION' => [
                'required' => true,
                'type' => 'string',
                'allowed_values' => ['sqlite', 'pgsql', 'mysql', 'pgsql_testing'],
                'default' => 'sqlite',
                'description' => 'データベース接続ドライバ',
            ],

            'DB_HOST' => [
                'required' => false,
                'type' => 'string',
                'required_if' => [
                    'DB_CONNECTION' => ['pgsql', 'mysql'],
                ],
                'description' => 'データベースホスト名',
            ],

            'DB_PORT' => [
                'required' => false,
                'type' => 'integer',
                'required_if' => [
                    'DB_CONNECTION' => ['pgsql', 'mysql'],
                ],
                'description' => 'データベースポート番号',
            ],

            'DB_DATABASE' => [
                'required' => false,
                'type' => 'string',
                'required_if' => [
                    'DB_CONNECTION' => ['pgsql', 'mysql'],
                ],
                'description' => 'データベース名',
            ],

            'DB_USERNAME' => [
                'required' => false,
                'type' => 'string',
                'required_if' => [
                    'DB_CONNECTION' => ['pgsql', 'mysql'],
                ],
                'security_level' => 'medium',
                'description' => 'データベースユーザー名',
            ],

            'DB_PASSWORD' => [
                'required' => false,
                'type' => 'string',
                'required_if' => [
                    'DB_CONNECTION' => ['pgsql', 'mysql'],
                ],
                'security_level' => 'high',
                'description' => 'データベースパスワード',
            ],

            // ============================================
            // Laravel Sanctum Configuration
            // ============================================
            'SANCTUM_STATEFUL_DOMAINS' => [
                'required' => false,
                'type' => 'string',
                'default' => 'localhost:13001,localhost:13002',
                'description' => 'Sanctum Stateful Domains（SPA認証用）',
            ],

            'SANCTUM_EXPIRATION' => [
                'required' => false,
                'type' => 'integer',
                'default' => 60,
                'description' => 'Sanctumトークン有効期限（日数）',
            ],

            // ============================================
            // CORS Configuration
            // ============================================
            'CORS_ALLOWED_ORIGINS' => [
                'required' => true,
                'type' => 'string',
                'default' => 'http://localhost:13001,http://localhost:13002',
                'description' => 'CORS許可オリジンのカンマ区切りリスト',
            ],

            'CORS_ALLOWED_METHODS' => [
                'required' => false,
                'type' => 'string',
                'default' => 'GET,POST,PUT,DELETE,PATCH,OPTIONS',
                'description' => 'CORS許可HTTPメソッドのカンマ区切りリスト',
            ],

            'CORS_ALLOWED_HEADERS' => [
                'required' => false,
                'type' => 'string',
                'default' => 'Content-Type,Authorization,X-Requested-With',
                'description' => 'CORS許可ヘッダーのカンマ区切りリスト',
            ],
        ];
    }
}

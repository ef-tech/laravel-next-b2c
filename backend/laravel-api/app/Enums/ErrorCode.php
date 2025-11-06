<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * エラーコードEnum
 *
 * このファイルは自動生成されます。手動で編集しないでください。
 * 生成元: shared/error-codes.json
 * 生成コマンド: npm run generate:error-types
 *
 * @generated
 */
enum ErrorCode: string
{
    /** ログイン認証失敗（メールアドレスまたはパスワードが正しくない） */
    case AUTH_LOGIN_001 = 'AUTH-LOGIN-001';

    /** 認証トークンの有効期限切れ */
    case AUTH_TOKEN_001 = 'AUTH-TOKEN-001';

    /** 無効な認証トークン */
    case AUTH_TOKEN_002 = 'AUTH-TOKEN-002';

    /** 権限不足 */
    case AUTH_PERMISSION_001 = 'AUTH-PERMISSION-001';

    /** 入力バリデーションエラー */
    case VAL_INPUT_001 = 'VAL-INPUT-001';

    /** メールアドレス形式が不正 */
    case VAL_EMAIL_001 = 'VAL-EMAIL-001';

    /** リソースが見つからない */
    case BIZ_RESOURCE_001 = 'BIZ-RESOURCE-001';

    /** リソースの重複 */
    case BIZ_CONFLICT_001 = 'BIZ-CONFLICT-001';

    /** データベース接続エラー */
    case INFRA_DB_001 = 'INFRA-DB-001';

    /** 外部API呼び出しエラー */
    case INFRA_API_001 = 'INFRA-API-001';

    /** リクエストタイムアウト */
    case INFRA_TIMEOUT_001 = 'INFRA-TIMEOUT-001';

    /**
     * HTTPステータスコードを取得
     */
    public function getHttpStatus(): int
    {
        return match ($this) {
            self::AUTH_LOGIN_001 => 401,
            self::AUTH_TOKEN_001 => 401,
            self::AUTH_TOKEN_002 => 401,
            self::AUTH_PERMISSION_001 => 403,
            self::VAL_INPUT_001 => 422,
            self::VAL_EMAIL_001 => 422,
            self::BIZ_RESOURCE_001 => 404,
            self::BIZ_CONFLICT_001 => 409,
            self::INFRA_DB_001 => 503,
            self::INFRA_API_001 => 502,
            self::INFRA_TIMEOUT_001 => 504,
        };
    }

    /**
     * RFC 7807 type URIを取得
     */
    public function getType(): string
    {
        return match ($this) {
            self::AUTH_LOGIN_001 => 'https://example.com/errors/auth/invalid-credentials',
            self::AUTH_TOKEN_001 => 'https://example.com/errors/auth/token-expired',
            self::AUTH_TOKEN_002 => 'https://example.com/errors/auth/token-invalid',
            self::AUTH_PERMISSION_001 => 'https://example.com/errors/auth/insufficient-permissions',
            self::VAL_INPUT_001 => 'https://example.com/errors/validation/invalid-input',
            self::VAL_EMAIL_001 => 'https://example.com/errors/validation/invalid-email',
            self::BIZ_RESOURCE_001 => 'https://example.com/errors/business/resource-not-found',
            self::BIZ_CONFLICT_001 => 'https://example.com/errors/business/resource-conflict',
            self::INFRA_DB_001 => 'https://example.com/errors/infrastructure/database-unavailable',
            self::INFRA_API_001 => 'https://example.com/errors/infrastructure/external-api-error',
            self::INFRA_TIMEOUT_001 => 'https://example.com/errors/infrastructure/request-timeout',
        };
    }

    /**
     * デフォルトメッセージを取得
     */
    public function getDefaultMessage(): string
    {
        return match ($this) {
            self::AUTH_LOGIN_001 => 'Invalid email or password',
            self::AUTH_TOKEN_001 => 'Authentication token has expired',
            self::AUTH_TOKEN_002 => 'Invalid authentication token',
            self::AUTH_PERMISSION_001 => 'Insufficient permissions',
            self::VAL_INPUT_001 => 'Validation failed',
            self::VAL_EMAIL_001 => 'Invalid email format',
            self::BIZ_RESOURCE_001 => 'Resource not found',
            self::BIZ_CONFLICT_001 => 'Resource already exists',
            self::INFRA_DB_001 => 'Database connection failed',
            self::INFRA_API_001 => 'External API request failed',
            self::INFRA_TIMEOUT_001 => 'Request timeout',
        };
    }

    /**
     * 翻訳キーを取得
     */
    public function getTranslationKey(): string
    {
        return match ($this) {
            self::AUTH_LOGIN_001 => 'errors.auth.invalid_credentials',
            self::AUTH_TOKEN_001 => 'errors.auth.token_expired',
            self::AUTH_TOKEN_002 => 'errors.auth.token_invalid',
            self::AUTH_PERMISSION_001 => 'errors.auth.insufficient_permissions',
            self::VAL_INPUT_001 => 'errors.validation.invalid_input',
            self::VAL_EMAIL_001 => 'errors.validation.invalid_email',
            self::BIZ_RESOURCE_001 => 'errors.business.resource_not_found',
            self::BIZ_CONFLICT_001 => 'errors.business.resource_conflict',
            self::INFRA_DB_001 => 'errors.infrastructure.database_unavailable',
            self::INFRA_API_001 => 'errors.infrastructure.external_api_error',
            self::INFRA_TIMEOUT_001 => 'errors.infrastructure.request_timeout',
        };
    }

    /**
     * カテゴリーを取得
     */
    public function getCategory(): ErrorCategory
    {
        return match ($this) {
            self::AUTH_LOGIN_001 => ErrorCategory::AUTH,
            self::AUTH_TOKEN_001 => ErrorCategory::AUTH,
            self::AUTH_TOKEN_002 => ErrorCategory::AUTH,
            self::AUTH_PERMISSION_001 => ErrorCategory::AUTH,
            self::VAL_INPUT_001 => ErrorCategory::VAL,
            self::VAL_EMAIL_001 => ErrorCategory::VAL,
            self::BIZ_RESOURCE_001 => ErrorCategory::BIZ,
            self::BIZ_CONFLICT_001 => ErrorCategory::BIZ,
            self::INFRA_DB_001 => ErrorCategory::INFRA,
            self::INFRA_API_001 => ErrorCategory::INFRA,
            self::INFRA_TIMEOUT_001 => ErrorCategory::INFRA,
        };
    }

    /**
     * エラーコード文字列から対応するEnumケースを取得
     */
    public static function fromString(string $code): ?self
    {
        return self::tryFrom($code);
    }
}

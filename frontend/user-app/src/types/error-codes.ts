/**
 * エラーコード型定義
 *
 * このファイルは自動生成されます。手動で編集しないでください。
 * 生成元: shared/error-codes.json
 * 生成コマンド: npm run generate:error-types
 *
 * @generated
 */

/**
 * エラーカテゴリー
 */
export type ErrorCategory = "AUTH" | "VAL" | "BIZ" | "INFRA";

/**
 * エラーコード
 */
export type ErrorCode =
  | "AUTH-LOGIN-001"
  | "AUTH-TOKEN-001"
  | "AUTH-TOKEN-002"
  | "AUTH-PERMISSION-001"
  | "VAL-INPUT-001"
  | "VAL-EMAIL-001"
  | "BIZ-RESOURCE-001"
  | "BIZ-CONFLICT-001"
  | "INFRA-DB-001"
  | "INFRA-API-001"
  | "INFRA-TIMEOUT-001";

/**
 * RFC 7807 Problem Details 型定義
 */
export interface RFC7807Problem {
  /** RFC 7807 type URI */
  type: string;
  /** 人間が読めるエラータイトル */
  title: string;
  /** HTTPステータスコード */
  status: number;
  /** エラーの詳細説明 */
  detail: string;
  /** エラーコード (DOMAIN-SUBDOMAIN-CODE形式) */
  error_code: ErrorCode;
  /** Request ID (トレーサビリティ用) */
  trace_id: string;
  /** エラーが発生したリソースのURI (オプション) */
  instance?: string;
  /** エラー発生時刻のタイムスタンプ (オプション) */
  timestamp?: string;
  /** バリデーションエラーの詳細 (オプション) */
  errors?: Record<string, string[]>;
}

/**
 * エラーコード定義
 */
export interface ErrorCodeDefinition {
  code: ErrorCode;
  http_status: number;
  type: string;
  default_message: string;
  translation_key: string;
  category: ErrorCategory;
  description?: string;
  resolution?: string;
}

/**
 * エラーコード定義マップ
 */
export const ERROR_CODE_DEFINITIONS: Record<ErrorCode, ErrorCodeDefinition> = {
  "AUTH-LOGIN-001": {
    code: "AUTH-LOGIN-001",
    http_status: 401,
    type: "https://example.com/errors/auth/invalid-credentials",
    default_message: "Invalid email or password",
    translation_key: "errors.auth.invalid_credentials",
    category: "AUTH",
    description: "ログイン認証失敗（メールアドレスまたはパスワードが正しくない）",
    resolution: "メールアドレスとパスワードを確認してください",
  },
  "AUTH-TOKEN-001": {
    code: "AUTH-TOKEN-001",
    http_status: 401,
    type: "https://example.com/errors/auth/token-expired",
    default_message: "Authentication token has expired",
    translation_key: "errors.auth.token_expired",
    category: "AUTH",
    description: "認証トークンの有効期限切れ",
    resolution: "再度ログインしてください",
  },
  "AUTH-TOKEN-002": {
    code: "AUTH-TOKEN-002",
    http_status: 401,
    type: "https://example.com/errors/auth/token-invalid",
    default_message: "Invalid authentication token",
    translation_key: "errors.auth.token_invalid",
    category: "AUTH",
    description: "無効な認証トークン",
    resolution: "再度ログインしてください",
  },
  "AUTH-PERMISSION-001": {
    code: "AUTH-PERMISSION-001",
    http_status: 403,
    type: "https://example.com/errors/auth/insufficient-permissions",
    default_message: "Insufficient permissions",
    translation_key: "errors.auth.insufficient_permissions",
    category: "AUTH",
    description: "権限不足",
    resolution: "このアクションを実行する権限がありません",
  },
  "VAL-INPUT-001": {
    code: "VAL-INPUT-001",
    http_status: 422,
    type: "https://example.com/errors/validation/invalid-input",
    default_message: "Validation failed",
    translation_key: "errors.validation.invalid_input",
    category: "VAL",
    description: "入力バリデーションエラー",
    resolution: "入力内容を確認してください",
  },
  "VAL-EMAIL-001": {
    code: "VAL-EMAIL-001",
    http_status: 422,
    type: "https://example.com/errors/validation/invalid-email",
    default_message: "Invalid email format",
    translation_key: "errors.validation.invalid_email",
    category: "VAL",
    description: "メールアドレス形式が不正",
    resolution: "正しいメールアドレス形式で入力してください",
  },
  "BIZ-RESOURCE-001": {
    code: "BIZ-RESOURCE-001",
    http_status: 404,
    type: "https://example.com/errors/business/resource-not-found",
    default_message: "Resource not found",
    translation_key: "errors.business.resource_not_found",
    category: "BIZ",
    description: "リソースが見つからない",
    resolution: "指定されたリソースが存在しません",
  },
  "BIZ-CONFLICT-001": {
    code: "BIZ-CONFLICT-001",
    http_status: 409,
    type: "https://example.com/errors/business/resource-conflict",
    default_message: "Resource already exists",
    translation_key: "errors.business.resource_conflict",
    category: "BIZ",
    description: "リソースの重複",
    resolution: "すでに同じリソースが存在します",
  },
  "INFRA-DB-001": {
    code: "INFRA-DB-001",
    http_status: 503,
    type: "https://example.com/errors/infrastructure/database-unavailable",
    default_message: "Database connection failed",
    translation_key: "errors.infrastructure.database_unavailable",
    category: "INFRA",
    description: "データベース接続エラー",
    resolution: "しばらくしてから再度お試しください",
  },
  "INFRA-API-001": {
    code: "INFRA-API-001",
    http_status: 502,
    type: "https://example.com/errors/infrastructure/external-api-error",
    default_message: "External API request failed",
    translation_key: "errors.infrastructure.external_api_error",
    category: "INFRA",
    description: "外部API呼び出しエラー",
    resolution: "外部サービスとの通信に失敗しました",
  },
  "INFRA-TIMEOUT-001": {
    code: "INFRA-TIMEOUT-001",
    http_status: 504,
    type: "https://example.com/errors/infrastructure/request-timeout",
    default_message: "Request timeout",
    translation_key: "errors.infrastructure.request_timeout",
    category: "INFRA",
    description: "リクエストタイムアウト",
    resolution: "処理に時間がかかりすぎました。再度お試しください",
  },
} as const;

/**
 * カテゴリー別エラーコード
 */
export const ERROR_CODES_BY_CATEGORY: Record<ErrorCategory, ErrorCode[]> = {
  AUTH: ["AUTH-LOGIN-001", "AUTH-TOKEN-001", "AUTH-TOKEN-002", "AUTH-PERMISSION-001"],
  VAL: ["VAL-INPUT-001", "VAL-EMAIL-001"],
  BIZ: ["BIZ-RESOURCE-001", "BIZ-CONFLICT-001"],
  INFRA: ["INFRA-DB-001", "INFRA-API-001", "INFRA-TIMEOUT-001"],
};

/**
 * HTTPステータスコードからエラーコードを取得
 */
export function getErrorCodesByStatus(status: number): ErrorCode[] {
  return Object.entries(ERROR_CODE_DEFINITIONS)
    .filter(([_, def]) => def.http_status === status)
    .map(([code]) => code as ErrorCode);
}

/**
 * エラーコードからエラーコード定義を取得
 */
export function getErrorCodeDefinition(code: ErrorCode): ErrorCodeDefinition | undefined {
  return ERROR_CODE_DEFINITIONS[code];
}

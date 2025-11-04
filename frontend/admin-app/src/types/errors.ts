/**
 * RFC 7807 Problem Details型定義
 *
 * @see https://datatracker.ietf.org/doc/html/rfc7807
 */

/**
 * RFC 7807 Problem Detailsインターフェース
 *
 * APIエラーレスポンスの標準形式を定義します。
 */
export interface RFC7807Problem {
  /**
   * エラータイプURI（例: "https://api.example.com/errors/validation_error"）
   */
  type: string;

  /**
   * エラータイトル（人間が読める短い要約）
   */
  title: string;

  /**
   * HTTPステータスコード（例: 400, 401, 422, 500）
   */
  status: number;

  /**
   * エラー詳細メッセージ（人間が読める詳細な説明）
   */
  detail: string;

  /**
   * 独自エラーコード（例: "validation_error", "email_already_exists"）
   */
  error_code: string;

  /**
   * トレースID（Request ID）
   * ログ追跡用の一意な識別子
   */
  trace_id: string;

  /**
   * エラーが発生したリクエストURI（例: "/api/v1/users"）
   */
  instance: string;

  /**
   * エラー発生時刻（ISO 8601形式）
   */
  timestamp: string;

  /**
   * バリデーションエラー詳細（オプショナル）
   * フィールド名 → エラーメッセージ配列のマッピング
   */
  errors?: Record<string, string[]>;

  /**
   * デバッグ情報（開発環境のみ、オプショナル）
   */
  debug?: {
    exception: string;
    file: string;
    line: number;
    trace: unknown[];
  };
}

/**
 * アプリケーションエラー基底インターフェース
 *
 * ApiErrorとNetworkErrorの共通インターフェース
 */
export interface AppError {
  /**
   * エラーメッセージ
   */
  readonly message: string;

  /**
   * エラー名
   */
  readonly name: string;

  /**
   * ユーザー向け表示メッセージを取得する
   */
  getDisplayMessage(): string;
}

/**
 * ApiError
 *
 * RFC 7807 Problem Details準拠のAPIエラークラス
 */

import type { AppError, RFC7807Problem } from '../types/errors';

// Re-export RFC7807Problem for convenience
export type { RFC7807Problem };

/**
 * APIエラークラス
 *
 * RFC 7807形式のAPIエラーレスポンスを型安全に扱うためのクラス
 */
export class ApiError extends Error implements AppError {
  /**
   * エラータイプURI
   */
  readonly type: string;

  /**
   * エラータイトル
   */
  readonly title: string;

  /**
   * HTTPステータスコード
   */
  readonly status: number;

  /**
   * エラー詳細メッセージ
   */
  readonly detail: string;

  /**
   * 独自エラーコード
   */
  readonly errorCode: string;

  /**
   * トレースID（Request ID）
   */
  readonly requestId: string;

  /**
   * エラーが発生したリクエストURI
   */
  readonly instance: string;

  /**
   * エラー発生時刻（ISO 8601形式）
   */
  readonly timestamp: string;

  /**
   * バリデーションエラー詳細
   */
  readonly validationErrors?: Record<string, string[]>;

  /**
   * デバッグ情報（開発環境のみ）
   */
  readonly debug?: {
    exception: string;
    file: string;
    line: number;
    trace: unknown[];
  };

  /**
   * コンストラクタ
   *
   * @param problem RFC 7807 Problem Detailsオブジェクト
   */
  constructor(problem: RFC7807Problem) {
    super(problem.detail);
    this.name = 'ApiError';

    this.type = problem.type;
    this.title = problem.title;
    this.status = problem.status;
    this.detail = problem.detail;
    this.errorCode = problem.error_code;
    this.requestId = problem.trace_id;
    this.instance = problem.instance;
    this.timestamp = problem.timestamp;
    this.validationErrors = problem.errors;
    this.debug = problem.debug;

    // IMPORTANT: Store problem data in cause for Next.js Error Boundary serialization
    // Next.js serializes Error.cause, allowing us to preserve custom properties
    // across client-server boundaries
    this.cause = problem;

    // Error.captureStackTraceが利用可能な場合はスタックトレースを設定
    if (Error.captureStackTrace) {
      Error.captureStackTrace(this, ApiError);
    }
  }

  /**
   * バリデーションエラーかどうかを判定する
   *
   * @returns バリデーションエラー（422）の場合true
   */
  isValidationError(): boolean {
    return this.status === 422;
  }

  /**
   * 認証エラーかどうかを判定する
   *
   * @returns 認証エラー（401）の場合true
   */
  isAuthenticationError(): boolean {
    return this.status === 401;
  }

  /**
   * 認可エラーかどうかを判定する
   *
   * @returns 認可エラー（403）の場合true
   */
  isAuthorizationError(): boolean {
    return this.status === 403;
  }

  /**
   * Not Foundエラーかどうかを判定する
   *
   * @returns Not Found（404）の場合true
   */
  isNotFoundError(): boolean {
    return this.status === 404;
  }

  /**
   * サーバーエラーかどうかを判定する
   *
   * @returns サーバーエラー（500系）の場合true
   */
  isServerError(): boolean {
    return this.status >= 500 && this.status < 600;
  }

  /**
   * ユーザー向け表示メッセージを取得する
   *
   * バリデーションエラーの場合は最初のエラーメッセージを返し、
   * それ以外の場合はdetailメッセージを返す
   *
   * @returns ユーザー向け表示メッセージ
   */
  getDisplayMessage(): string {
    // バリデーションエラーの場合、最初のフィールドの最初のエラーメッセージを返す
    if (this.validationErrors) {
      const firstField = Object.keys(this.validationErrors)[0];
      if (firstField && this.validationErrors[firstField]) {
        const firstError = this.validationErrors[firstField]![0];
        if (firstError) {
          return firstError;
        }
      }
    }

    // それ以外の場合はdetailメッセージを返す
    return this.detail;
  }

  /**
   * JSON形式に変換する
   *
   * @returns JSON形式のオブジェクト
   */
  toJSON(): RFC7807Problem {
    return {
      type: this.type,
      title: this.title,
      status: this.status,
      detail: this.detail,
      error_code: this.errorCode,
      trace_id: this.requestId,
      instance: this.instance,
      timestamp: this.timestamp,
      errors: this.validationErrors,
      debug: this.debug,
    };
  }
}

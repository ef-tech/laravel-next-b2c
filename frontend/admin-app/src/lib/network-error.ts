/**
 * NetworkError
 *
 * ネットワークエラー（接続エラー、タイムアウト等）を表すクラス
 */

import type { AppError } from "../types/errors";

/**
 * ネットワークエラークラス
 *
 * Fetch APIのネットワークエラーを型安全に扱うためのクラス
 */
export class NetworkError extends Error implements AppError {
  /**
   * 元のエラー
   */
  readonly originalError: Error;

  /**
   * 再試行可能かどうか
   */
  readonly isRetryable: boolean;

  /**
   * プライベートコンストラクタ（ファクトリーメソッド経由で生成）
   *
   * @param message エラーメッセージ
   * @param originalError 元のエラー
   * @param isRetryable 再試行可能かどうか
   */
  private constructor(message: string, originalError: Error, isRetryable: boolean) {
    super(message);
    this.name = "NetworkError";
    this.originalError = originalError;
    this.isRetryable = isRetryable;

    // Error.captureStackTraceが利用可能な場合はスタックトレースを設定
    if (Error.captureStackTrace) {
      Error.captureStackTrace(this, NetworkError);
    }
  }

  /**
   * Fetch APIエラーからNetworkErrorを生成する
   *
   * @param error Fetch APIエラー
   * @returns NetworkErrorインスタンス
   */
  static fromFetchError(error: Error): NetworkError {
    // TypeError: Failed to fetch (ネットワーク接続エラー)
    if (error instanceof TypeError && error.message.includes("Failed to fetch")) {
      return new NetworkError("Network connection failed", error, true);
    }

    // AbortError (タイムアウト)
    if (error.name === "AbortError") {
      return new NetworkError("Request timeout", error, true);
    }

    // TimeoutError
    if (error.name === "TimeoutError") {
      return new NetworkError("Request timeout", error, true);
    }

    // その他のエラー
    return new NetworkError(error.message || "Network error occurred", error, false);
  }

  /**
   * タイムアウトエラーかどうかを判定する
   *
   * @returns タイムアウトエラーの場合true
   */
  isTimeout(): boolean {
    return this.originalError.name === "AbortError" || this.originalError.name === "TimeoutError";
  }

  /**
   * 接続エラーかどうかを判定する
   *
   * @returns 接続エラーの場合true
   */
  isConnectionError(): boolean {
    return (
      this.originalError instanceof TypeError &&
      this.originalError.message.includes("Failed to fetch")
    );
  }

  /**
   * ユーザー向け表示メッセージを取得する
   *
   * @param t - Optional translation function from useTranslations()
   * @returns ユーザー向け表示メッセージ
   */
  getDisplayMessage(t?: (key: string) => string): string {
    // Backward compatibility: return hardcoded Japanese messages when t is not provided
    if (!t) {
      if (this.isTimeout()) {
        return "リクエストがタイムアウトしました。しばらくしてから再度お試しください。";
      }

      if (this.isConnectionError()) {
        return "ネットワーク接続に問題が発生しました。インターネット接続を確認して再度お試しください。";
      }

      return "予期しないエラーが発生しました。しばらくしてから再度お試しください。";
    }

    // i18n-enabled path: use translation keys
    if (this.isTimeout()) {
      return t("network.timeout");
    }

    if (this.isConnectionError()) {
      return t("network.connection");
    }

    return t("network.unknown");
  }
}

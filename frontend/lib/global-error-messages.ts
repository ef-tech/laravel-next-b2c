/**
 * Global Error Boundaries 静的メッセージ辞書（共通モジュール）
 *
 * ## 目的
 * User AppとAdmin Appの`global-error.tsx`で使用する静的メッセージ辞書を提供します。
 * global-error.tsxでは`next-intl`が利用できないため、独自の辞書を定義しています。
 *
 * ## 使用方法
 * ```typescript
 * import { globalErrorMessages, type Locale } from '@/../../lib/global-error-messages';
 *
 * const locale: Locale = 'ja';
 * const t = globalErrorMessages[locale];
 * console.log(t.boundary.title); // "エラーが発生しました"
 * ```
 *
 * ## メッセージカテゴリ
 * - **network**: ネットワークエラーメッセージ（timeout, connection, unknown）
 * - **boundary**: Error Boundary UI要素（title, retry, status等）
 * - **validation**: バリデーションエラータイトル
 * - **global**: 汎用エラーメッセージ（title, retry, errorId等）
 *
 * ## 対応言語
 * - 日本語（ja）
 * - 英語（en）
 *
 * @module frontend/lib/global-error-messages
 */

/**
 * サポートされるロケール型
 * - `ja`: 日本語
 * - `en`: 英語
 */
export type Locale = "ja" | "en";

/**
 * グローバルエラーメッセージ構造型
 */
export interface GlobalErrorMessages {
  network: {
    timeout: string;
    connection: string;
    unknown: string;
  };
  boundary: {
    title: string;
    retry: string;
    home: string;
    status: string;
    requestId: string;
    networkError: string;
    timeout: string;
    connectionError: string;
    retryableMessage: string;
  };
  validation: {
    title: string;
  };
  global: {
    title: string;
    retry: string;
    errorId: string;
    contactMessage: string;
  };
}

/**
 * グローバルエラーメッセージ辞書
 *
 * 日本語（ja）と英語（en）の2言語に対応した静的メッセージ辞書です。
 * `as const`型アサーションにより、TypeScriptの型推論が最適化されます。
 *
 * @example
 * ```typescript
 * const t = globalErrorMessages.ja;
 * console.log(t.network.timeout); // "リクエストがタイムアウトしました。しばらくしてから再度お試しください。"
 * ```
 */
export const globalErrorMessages = {
  ja: {
    network: {
      timeout: "リクエストがタイムアウトしました。しばらくしてから再度お試しください。",
      connection:
        "ネットワーク接続に問題が発生しました。インターネット接続を確認して再度お試しください。",
      unknown: "予期しないエラーが発生しました。しばらくしてから再度お試しください。",
    },
    boundary: {
      title: "エラーが発生しました",
      retry: "再試行",
      home: "ホームに戻る",
      status: "ステータスコード",
      requestId: "Request ID",
      networkError: "ネットワークエラー",
      timeout: "タイムアウト",
      connectionError: "接続エラー",
      retryableMessage: "このエラーは再試行可能です。しばらくしてから再度お試しください。",
    },
    validation: {
      title: "入力エラー",
    },
    global: {
      title: "予期しないエラーが発生しました",
      retry: "再試行",
      errorId: "Error ID",
      contactMessage: "お問い合わせの際は、このIDをお伝えください",
    },
  },
  en: {
    network: {
      timeout: "The request timed out. Please try again later.",
      connection:
        "A network connection problem occurred. Please check your internet connection and try again.",
      unknown: "An unexpected error occurred. Please try again later.",
    },
    boundary: {
      title: "An error occurred",
      retry: "Retry",
      home: "Go to Home",
      status: "Status Code",
      requestId: "Request ID",
      networkError: "Network Error",
      timeout: "Timeout",
      connectionError: "Connection Error",
      retryableMessage: "This error is retryable. Please try again later.",
    },
    validation: {
      title: "Validation Errors",
    },
    global: {
      title: "An unexpected error occurred",
      retry: "Retry",
      errorId: "Error ID",
      contactMessage: "Please provide this ID when contacting support",
    },
  },
} as const;

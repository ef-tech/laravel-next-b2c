/**
 * Global Error Boundaries 静的メッセージ辞書（共通モジュール）
 *
 * User AppとAdmin Appの`global-error.tsx`で使用する静的メッセージ辞書を提供します。
 * Next.js App RouterのGlobal Error Boundaryでは`next-intl`のuseTranslations()フックが
 * 利用できないため、独自の静的辞書を定義し、DRY原則に基づいて共通化しています。
 *
 * @example
 * ```typescript
 * import { globalErrorMessages, type Locale } from '@/../../lib/global-error-messages';
 *
 * // ロケールに応じたメッセージ取得
 * const locale: Locale = 'ja';
 * const t = globalErrorMessages[locale];
 *
 * // カテゴリ別メッセージ参照
 * console.log(t.boundary.title);  // "エラーが発生しました"
 * console.log(t.network.timeout); // "リクエストがタイムアウトしました。..."
 * console.log(t.validation.title); // "入力エラー"
 * console.log(t.global.retry);    // "再試行"
 * ```
 *
 * @remarks
 * **メッセージカテゴリ**:
 * - `network`: ネットワークエラーメッセージ（timeout, connection, unknown）
 * - `boundary`: Error Boundary UI要素（title, retry, status, requestId等）
 * - `validation`: バリデーションエラー表示用タイトル
 * - `global`: 汎用エラーメッセージ（title, retry, errorId, contactMessage）
 *
 * **対応言語**:
 * - 日本語（ja）
 * - 英語（en）
 *
 * @module frontend/lib/global-error-messages
 */

/**
 * サポートされるロケール型
 *
 * Global Error Boundaryで使用可能な言語識別子。
 *
 * @remarks
 * - `ja`: 日本語
 * - `en`: 英語
 */
export type Locale = "ja" | "en";

/**
 * グローバルエラーメッセージ構造型
 *
 * Global Error Boundaryで表示される全てのエラーメッセージの型定義。
 * 4つのカテゴリ（network, boundary, validation, global）に分類されています。
 *
 * @remarks
 * この型は`as const`型アサーションと組み合わせることで、
 * TypeScriptコンパイラによる厳格な型チェックを実現します。
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
 * `as const`型アサーションにより、TypeScriptの型推論が最適化され、
 * 各メッセージが文字列リテラル型として扱われます。
 *
 * @remarks
 * **使用上の注意**:
 * - メッセージ内容の変更は、このファイルを編集するだけで全アプリに反映されます
 * - 新しいメッセージカテゴリを追加する場合は、`GlobalErrorMessages`型も更新してください
 * - 日本語と英語の両方のメッセージを必ず提供してください
 *
 * @example
 * ```typescript
 * // 日本語メッセージ取得
 * const t = globalErrorMessages.ja;
 * console.log(t.network.timeout);
 * // => "リクエストがタイムアウトしました。しばらくしてから再度お試しください。"
 *
 * // 英語メッセージ取得
 * const tEn = globalErrorMessages.en;
 * console.log(tEn.network.timeout);
 * // => "The request timed out. Please try again later."
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

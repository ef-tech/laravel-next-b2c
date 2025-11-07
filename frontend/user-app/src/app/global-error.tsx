"use client";

/**
 * Global Error Boundary for User App
 *
 * アプリケーション全体のGlobal Error Boundary（Next.js App Router）
 * - ルートレイアウトのエラーをキャッチする
 * - ApiErrorを検出してRFC 7807情報を画面表示
 * - NetworkErrorを検出してネットワークエラーメッセージと再試行ボタンを表示
 * - Request ID（trace_id）をユーザーに提示
 * - reset()による再試行機能
 * - 本番環境では内部エラー詳細をマスク
 * - i18n対応（静的辞書による多言語化）
 *
 * Note: global-error.tsx must include <html> and <body> tags
 * because it replaces the root layout when activated.
 */

import { useEffect, useState } from "react";
import { ApiError } from "@/lib/api-error";
import type { RFC7807Problem } from "@/types/errors";
import { NetworkError } from "@/lib/network-error";

/**
 * 静的メッセージ辞書（ja、en）
 * global-error.tsxではnext-intlが使用できないため、独自の辞書を定義
 */
const messages = {
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

type Locale = keyof typeof messages;

/**
 * ブラウザロケールを検出
 * 1. NEXT_LOCALE Cookie をチェック（next-intl middleware が設定）
 * 2. document.documentElement.lang をチェック
 * 3. navigator.languages をチェック
 * 4. デフォルトは 'ja'
 */
const detectLocale = (): Locale => {
  if (typeof window === "undefined") {
    return "ja";
  }

  // 1. NEXT_LOCALE Cookie をチェック
  const cookies = document.cookie.split(";");
  for (const cookie of cookies) {
    const [name, value] = cookie.trim().split("=");
    if (name === "NEXT_LOCALE") {
      if (value === "en") {
        return "en";
      }
      if (value === "ja") {
        return "ja";
      }
    }
  }

  // 2. document.documentElement.lang をチェック
  const htmlLang = document.documentElement.lang;
  if (htmlLang && htmlLang.startsWith("en")) {
    return "en";
  }
  if (htmlLang && htmlLang.startsWith("ja")) {
    return "ja";
  }

  // 3. navigator.languages をチェック
  if (navigator.languages) {
    for (const lang of navigator.languages) {
      if (lang.startsWith("en")) {
        return "en";
      }
      if (lang.startsWith("ja")) {
        return "ja";
      }
    }
  }

  // 4. デフォルトは 'ja'
  return "ja";
};

interface GlobalErrorProps {
  error: Error & { digest?: string };
  reset: () => void;
}

export default function GlobalError({ error, reset }: GlobalErrorProps) {
  // ロケール状態管理 - 初期値として検出
  const [locale, setLocale] = useState<Locale>(detectLocale);

  useEffect(() => {
    // エラーをコンソールにログ出力（開発環境用）
    console.error("Global Error Boundary caught an error:", error);

    // ロケールを再検出して更新（error変更時）
    setLocale(detectLocale());
  }, [error]);

  // 本番環境判定
  const isProduction = process.env.NODE_ENV === "production";

  // 翻訳関数（メッセージ辞書から取得）
  const t = messages[locale];

  // IMPORTANT: Reconstruct ApiError from error.cause if properties are missing
  // This handles Next.js error serialization where custom properties are lost
  let apiError: ApiError | null = null;

  if (error instanceof ApiError) {
    // ApiError instance detected
    if (error.title && error.status) {
      // Properties intact - use as-is
      apiError = error;
    } else if (error.cause && typeof error.cause === "object") {
      // ApiError instance but properties lost - reconstruct from cause
      try {
        apiError = new ApiError(error.cause as RFC7807Problem);
      } catch (e) {
        console.error("Failed to reconstruct ApiError from cause:", e);
        apiError = error; // Fallback to original even with undefined properties
      }
    } else {
      // ApiError instance but no cause - use as-is (may have undefined properties)
      apiError = error;
    }
  } else if (error.name === "ApiError" || error.constructor?.name === "ApiError") {
    // Not instanceof but has ApiError name - try to reconstruct from cause
    if (error.cause && typeof error.cause === "object") {
      try {
        apiError = new ApiError(error.cause as RFC7807Problem);
      } catch (e) {
        console.error("Failed to reconstruct ApiError from name check:", e);
      }
    }
  } else if (error.cause && typeof error.cause === "object" && "status" in error.cause) {
    // Generic error with RFC 7807 data in cause
    try {
      apiError = new ApiError(error.cause as RFC7807Problem);
    } catch (e) {
      console.error("Failed to reconstruct ApiError from generic cause:", e);
    }
  }

  // ApiError の場合
  if (apiError) {
    return (
      <html lang={locale}>
        <body>
          <div className="flex min-h-screen items-center justify-center bg-gray-50 px-4 sm:px-6 lg:px-8">
            <div className="w-full max-w-md space-y-8">
              <div className="rounded-lg bg-white p-6 shadow-lg">
                <div className="mb-4 flex items-center">
                  <div className="flex-shrink-0">
                    <svg
                      className="h-12 w-12 text-red-500"
                      fill="none"
                      viewBox="0 0 24 24"
                      stroke="currentColor"
                    >
                      <path
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        strokeWidth={2}
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                      />
                    </svg>
                  </div>
                  <div className="ml-4">
                    <h2 className="text-lg font-semibold text-gray-900">
                      {apiError.title || t.boundary.title}
                    </h2>
                    <p className="text-sm text-gray-500">
                      {t.boundary.status}: {apiError.status}
                    </p>
                  </div>
                </div>

                <div className="mb-4">
                  <p className="mb-2 text-gray-700">{apiError.getDisplayMessage()}</p>

                  {/* バリデーションエラーの詳細表示 */}
                  {apiError.validationErrors && (
                    <div className="mt-4 rounded-md bg-red-50 p-4">
                      <h3 className="mb-2 text-sm font-medium text-red-800">
                        {t.validation.title}:
                      </h3>
                      <ul className="list-inside list-disc space-y-1">
                        {Object.entries(apiError.validationErrors).map(([field, messages]) => (
                          <li key={field} className="text-sm text-red-700">
                            <span className="font-medium">{field}:</span> {messages.join(", ")}
                          </li>
                        ))}
                      </ul>
                    </div>
                  )}

                  {/* Request ID（trace_id）表示 */}
                  <div className="mt-4 rounded-md bg-gray-100 p-3">
                    <p className="text-xs text-gray-600">
                      <span className="font-medium">{t.boundary.requestId}:</span>{" "}
                      <code className="rounded bg-gray-200 px-2 py-1">{apiError.requestId}</code>
                    </p>
                    <p className="mt-1 text-xs text-gray-500">{t.global.contactMessage}</p>
                  </div>

                  {/* 開発環境のみ：詳細情報表示 */}
                  {!isProduction && apiError.debug && (
                    <details className="mt-4 rounded-md bg-yellow-50 p-3">
                      <summary className="cursor-pointer text-sm font-medium text-yellow-800">
                        開発者向け情報（本番環境では非表示）
                      </summary>
                      <div className="mt-2 text-xs text-yellow-700">
                        <p>
                          <span className="font-medium">Exception:</span> {apiError.debug.exception}
                        </p>
                        <p>
                          <span className="font-medium">File:</span> {apiError.debug.file}:
                          {apiError.debug.line}
                        </p>
                      </div>
                    </details>
                  )}
                </div>

                <button
                  onClick={reset}
                  className="w-full rounded-md bg-blue-600 px-4 py-2 font-medium text-white transition duration-150 ease-in-out hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:outline-none"
                >
                  {t.boundary.retry}
                </button>
              </div>
            </div>
          </div>
        </body>
      </html>
    );
  }

  // NetworkError の場合
  // Check both instanceof and name for Jest compatibility
  if (error instanceof NetworkError || error.name === "NetworkError") {
    // Type assertion for NetworkError methods
    const networkError = error as NetworkError;

    return (
      <html lang={locale}>
        <body>
          <div className="flex min-h-screen items-center justify-center bg-gray-50 px-4 sm:px-6 lg:px-8">
            <div className="w-full max-w-md space-y-8">
              <div className="rounded-lg bg-white p-6 shadow-lg">
                <div className="mb-4 flex items-center">
                  <div className="flex-shrink-0">
                    <svg
                      className="h-12 w-12 text-orange-500"
                      fill="none"
                      viewBox="0 0 24 24"
                      stroke="currentColor"
                    >
                      <path
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        strokeWidth={2}
                        d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"
                      />
                    </svg>
                  </div>
                  <div className="ml-4">
                    <h2 className="text-lg font-semibold text-gray-900">
                      {t.boundary.networkError}
                    </h2>
                    <p className="text-sm text-gray-500">
                      {networkError.isTimeout()
                        ? t.boundary.timeout
                        : networkError.isConnectionError()
                          ? t.boundary.connectionError
                          : t.boundary.networkError}
                    </p>
                  </div>
                </div>

                <div className="mb-4">
                  <p className="mb-2 text-gray-700">
                    {networkError.isTimeout()
                      ? t.network.timeout
                      : networkError.isConnectionError()
                        ? t.network.connection
                        : t.network.unknown}
                  </p>

                  {networkError.isRetryable && (
                    <div className="mt-4 rounded-md bg-blue-50 p-3">
                      <p className="text-sm text-blue-800">
                        <svg
                          className="mr-2 inline h-5 w-5"
                          fill="currentColor"
                          viewBox="0 0 20 20"
                        >
                          <path
                            fillRule="evenodd"
                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                            clipRule="evenodd"
                          />
                        </svg>
                        {t.boundary.retryableMessage}
                      </p>
                    </div>
                  )}
                </div>

                <button
                  onClick={reset}
                  className="w-full rounded-md bg-blue-600 px-4 py-2 font-medium text-white transition duration-150 ease-in-out hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:outline-none"
                >
                  {t.boundary.retry}
                </button>
              </div>
            </div>
          </div>
        </body>
      </html>
    );
  }

  // その他のエラー（汎用Error）
  return (
    <html lang={locale}>
      <body>
        <div className="flex min-h-screen items-center justify-center bg-gray-50 px-4 sm:px-6 lg:px-8">
          <div className="w-full max-w-md space-y-8">
            <div className="rounded-lg bg-white p-6 shadow-lg">
              <div className="mb-4 flex items-center">
                <div className="flex-shrink-0">
                  <svg
                    className="h-12 w-12 text-gray-500"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                  >
                    <path
                      strokeLinecap="round"
                      strokeLinejoin="round"
                      strokeWidth={2}
                      d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                    />
                  </svg>
                </div>
                <div className="ml-4">
                  <h2 className="text-lg font-semibold text-gray-900">{t.global.title}</h2>
                  <p className="text-sm text-gray-500">{error.name}</p>
                </div>
              </div>

              <div className="mb-4">
                {/* 本番環境ではエラーメッセージをマスク */}
                <p className="mb-2 text-gray-700">
                  {isProduction ? t.network.unknown : error.message}
                </p>

                {/* digest（Next.js Error ID）がある場合は表示 */}
                {error.digest && (
                  <div className="mt-4 rounded-md bg-gray-100 p-3">
                    <p className="text-xs text-gray-600">
                      <span className="font-medium">{t.global.errorId}:</span>{" "}
                      <code className="rounded bg-gray-200 px-2 py-1">{error.digest}</code>
                    </p>
                    <p className="mt-1 text-xs text-gray-500">{t.global.contactMessage}</p>
                  </div>
                )}

                {/* 開発環境のみ：スタックトレース表示 */}
                {!isProduction && error.stack && (
                  <details className="mt-4 rounded-md bg-yellow-50 p-3">
                    <summary className="cursor-pointer text-sm font-medium text-yellow-800">
                      スタックトレース（開発環境のみ）
                    </summary>
                    <pre className="mt-2 overflow-x-auto text-xs text-yellow-700">
                      {error.stack}
                    </pre>
                  </details>
                )}
              </div>

              <button
                onClick={reset}
                className="w-full rounded-md bg-blue-600 px-4 py-2 font-medium text-white transition duration-150 ease-in-out hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:outline-none"
              >
                {t.global.retry}
              </button>
            </div>
          </div>
        </div>
      </body>
    </html>
  );
}

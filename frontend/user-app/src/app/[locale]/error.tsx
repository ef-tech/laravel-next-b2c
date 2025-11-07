"use client";

/**
 * Error Boundary for User App
 *
 * セグメント用Error Boundary（Next.js App Router）
 * - ApiErrorを検出してRFC 7807情報を画面表示
 * - NetworkErrorを検出してネットワークエラーメッセージと再試行ボタンを表示
 * - Request ID（trace_id）をユーザーに提示
 * - reset()による再試行機能
 * - 本番環境では内部エラー詳細をマスク
 * - i18n対応（next-intl）
 */

import { useEffect } from "react";
import Link from "next/link";
import { useTranslations } from "next-intl";
import { ApiError } from "@/lib/api-error";
import type { RFC7807Problem } from "@/types/errors";
import { NetworkError } from "@/lib/network-error";

interface ErrorProps {
  error: Error & { digest?: string };
  reset: () => void;
}

export default function Error({ error, reset }: ErrorProps) {
  const t = useTranslations("errors");

  useEffect(() => {
    // エラーをコンソールにログ出力（開発環境用）
    console.error("Error Boundary caught an error:", error);
    console.error("Error name:", error.name);
    console.error("Error instanceof ApiError:", error instanceof ApiError);
    console.error("Error instanceof NetworkError:", error instanceof NetworkError);
    console.error("Error constructor name:", error.constructor.name);

    // ApiErrorのプロパティをログ出力
    if (error instanceof ApiError) {
      console.error("ApiError properties:", {
        title: error.title,
        status: error.status,
        detail: error.detail,
        requestId: error.requestId,
        errorCode: error.errorCode,
      });
      console.error("ApiError toJSON():", error.toJSON());
    }

    // 401エラーの場合、ログインページにリダイレクト
    // ApiErrorまたはerror.causeから401を検出
    const is401Error =
      (error instanceof ApiError && error.status === 401) ||
      (error.cause &&
        typeof error.cause === "object" &&
        "status" in error.cause &&
        error.cause.status === 401);

    if (is401Error) {
      console.error("401 Unauthorized detected - redirecting to login page");
      // ログインページにリダイレクト（現在のURLをreturn_urlとして保存）
      const currentUrl = encodeURIComponent(window.location.pathname + window.location.search);
      window.location.href = `/login?return_url=${currentUrl}`;
    }
  }, [error]);

  // 本番環境判定
  const isProduction = process.env.NODE_ENV === "production";

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
                <h2 className="text-lg font-semibold text-gray-900">{t("boundary.title")}</h2>
                <p className="text-sm text-gray-500">
                  {t("boundary.status")}: {apiError.status}
                </p>
              </div>
            </div>

            <div className="mb-4">
              {/* RFC 7807 Error Title */}
              {apiError.title && <p className="mb-2 font-medium text-gray-900">{apiError.title}</p>}
              <p className="mb-2 text-gray-700">{apiError.getDisplayMessage()}</p>

              {/* バリデーションエラーの詳細表示 */}
              {apiError.validationErrors && (
                <div className="mt-4 rounded-md bg-red-50 p-4">
                  <h3 className="mb-2 text-sm font-medium text-red-800">
                    {t("validation.title")}:
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
                  <span className="font-medium">{t("boundary.requestId")}:</span>{" "}
                  <code className="rounded bg-gray-200 px-2 py-1">{apiError.requestId}</code>
                </p>
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

            <div className="flex gap-3">
              <button
                onClick={reset}
                className="flex-1 rounded-md bg-blue-600 px-4 py-2 font-medium text-white transition duration-150 ease-in-out hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:outline-none"
              >
                {t("boundary.retry")}
              </button>
              <Link
                href="/"
                className="flex-1 rounded-md bg-gray-600 px-4 py-2 text-center font-medium text-white transition duration-150 ease-in-out hover:bg-gray-700 focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 focus:outline-none"
              >
                {t("boundary.home")}
              </Link>
            </div>
          </div>
        </div>
      </div>
    );
  }

  // NetworkError の場合
  if (error instanceof NetworkError) {
    return (
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
                  {t("boundary.networkError")}
                </h2>
                <p className="text-sm text-gray-500">
                  {error.isTimeout()
                    ? t("boundary.timeout")
                    : error.isConnectionError()
                      ? t("boundary.connectionError")
                      : t("boundary.networkError")}
                </p>
              </div>
            </div>

            <div className="mb-4">
              <p className="mb-2 text-gray-700">{error.getDisplayMessage(t)}</p>

              {error.isRetryable && (
                <div className="mt-4 rounded-md bg-blue-50 p-3">
                  <p className="text-sm text-blue-800">
                    <svg className="mr-2 inline h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                      <path
                        fillRule="evenodd"
                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                        clipRule="evenodd"
                      />
                    </svg>
                    {t("boundary.retryableMessage")}
                  </p>
                </div>
              )}
            </div>

            <div className="flex gap-3">
              <button
                onClick={reset}
                className="flex-1 rounded-md bg-blue-600 px-4 py-2 font-medium text-white transition duration-150 ease-in-out hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:outline-none"
              >
                {t("boundary.retry")}
              </button>
              <Link
                href="/"
                className="flex-1 rounded-md bg-gray-600 px-4 py-2 text-center font-medium text-white transition duration-150 ease-in-out hover:bg-gray-700 focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 focus:outline-none"
              >
                {t("boundary.home")}
              </Link>
            </div>
          </div>
        </div>
      </div>
    );
  }

  // その他のエラー（汎用Error）
  // digestがない場合、一意なIDを生成する
  const errorId =
    error.digest || `error-${Date.now()}-${Math.random().toString(36).substring(2, 9)}`;

  return (
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
              <h2 className="text-lg font-semibold text-gray-900">{t("global.title")}</h2>
              <p className="text-sm text-gray-500">{error.name}</p>
            </div>
          </div>

          <div className="mb-4">
            {/* 本番環境ではエラーメッセージをマスク */}
            <p className="mb-2 text-gray-700">
              {isProduction ? t("network.unknown") : error.message}
            </p>

            {/* Error ID表示（digest または生成したID） */}
            <div className="mt-4 rounded-md bg-gray-100 p-3">
              <p className="text-xs text-gray-600">
                <span className="font-medium">{t("global.errorId")}:</span>{" "}
                <code className="rounded bg-gray-200 px-2 py-1">{errorId}</code>
              </p>
              <p className="mt-1 text-xs text-gray-500">{t("global.contactMessage")}</p>
            </div>

            {/* 開発環境のみ：スタックトレース表示 */}
            {!isProduction && error.stack && (
              <details className="mt-4 rounded-md bg-yellow-50 p-3">
                <summary className="cursor-pointer text-sm font-medium text-yellow-800">
                  スタックトレース（開発環境のみ）
                </summary>
                <pre className="mt-2 overflow-x-auto text-xs text-yellow-700">{error.stack}</pre>
              </details>
            )}
          </div>

          <div className="flex gap-3">
            <button
              onClick={reset}
              className="flex-1 rounded-md bg-blue-600 px-4 py-2 font-medium text-white transition duration-150 ease-in-out hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:outline-none"
            >
              {t("global.retry")}
            </button>
            <Link
              href="/"
              className="flex-1 rounded-md bg-gray-600 px-4 py-2 text-center font-medium text-white transition duration-150 ease-in-out hover:bg-gray-700 focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 focus:outline-none"
            >
              {t("boundary.home")}
            </Link>
          </div>
        </div>
      </div>
    </div>
  );
}

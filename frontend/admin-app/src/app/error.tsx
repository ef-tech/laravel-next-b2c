"use client";

/**
 * Error Boundary for Admin App
 *
 * セグメント用Error Boundary（Next.js App Router）
 * - ApiErrorを検出してRFC 7807情報を画面表示
 * - NetworkErrorを検出してネットワークエラーメッセージと再試行ボタンを表示
 * - Request ID（trace_id）をユーザーに提示
 * - reset()による再試行機能
 * - 本番環境では内部エラー詳細をマスク
 */

import { useEffect } from "react";
import { ApiError } from "../../../lib/api-error";
import { NetworkError } from "../../../lib/network-error";

interface ErrorProps {
  error: Error & { digest?: string };
  reset: () => void;
}

export default function Error({ error, reset }: ErrorProps) {
  useEffect(() => {
    // エラーをコンソールにログ出力（開発環境用）
    console.error("Error Boundary caught an error:", error);
  }, [error]);

  // 本番環境判定
  const isProduction = process.env.NODE_ENV === "production";

  // ApiError の場合
  if (error instanceof ApiError) {
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
                <h2 className="text-lg font-semibold text-gray-900">
                  {error.title || "エラーが発生しました"}
                </h2>
                <p className="text-sm text-gray-500">ステータスコード: {error.status}</p>
              </div>
            </div>

            <div className="mb-4">
              <p className="mb-2 text-gray-700">{error.getDisplayMessage()}</p>

              {/* バリデーションエラーの詳細表示 */}
              {error.validationErrors && (
                <div className="mt-4 rounded-md bg-red-50 p-4">
                  <h3 className="mb-2 text-sm font-medium text-red-800">入力エラー:</h3>
                  <ul className="list-inside list-disc space-y-1">
                    {Object.entries(error.validationErrors).map(([field, messages]) => (
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
                  <span className="font-medium">Request ID:</span>{" "}
                  <code className="rounded bg-gray-200 px-2 py-1">{error.requestId}</code>
                </p>
                <p className="mt-1 text-xs text-gray-500">
                  お問い合わせの際は、このIDをお伝えください
                </p>
              </div>

              {/* 開発環境のみ：詳細情報表示 */}
              {!isProduction && error.debug && (
                <details className="mt-4 rounded-md bg-yellow-50 p-3">
                  <summary className="cursor-pointer text-sm font-medium text-yellow-800">
                    開発者向け情報（本番環境では非表示）
                  </summary>
                  <div className="mt-2 text-xs text-yellow-700">
                    <p>
                      <span className="font-medium">Exception:</span> {error.debug.exception}
                    </p>
                    <p>
                      <span className="font-medium">File:</span> {error.debug.file}:
                      {error.debug.line}
                    </p>
                  </div>
                </details>
              )}
            </div>

            <button
              onClick={reset}
              className="w-full rounded-md bg-blue-600 px-4 py-2 font-medium text-white transition duration-150 ease-in-out hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:outline-none"
            >
              再試行
            </button>
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
                <h2 className="text-lg font-semibold text-gray-900">ネットワークエラー</h2>
                <p className="text-sm text-gray-500">
                  {error.isTimeout()
                    ? "タイムアウト"
                    : error.isConnectionError()
                      ? "接続エラー"
                      : "ネットワークエラー"}
                </p>
              </div>
            </div>

            <div className="mb-4">
              <p className="mb-2 text-gray-700">{error.getDisplayMessage()}</p>

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
                    このエラーは再試行可能です。しばらくしてから再度お試しください。
                  </p>
                </div>
              )}
            </div>

            <button
              onClick={reset}
              className="w-full rounded-md bg-blue-600 px-4 py-2 font-medium text-white transition duration-150 ease-in-out hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:outline-none"
            >
              再試行
            </button>
          </div>
        </div>
      </div>
    );
  }

  // その他のエラー（汎用Error）
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
              <h2 className="text-lg font-semibold text-gray-900">
                予期しないエラーが発生しました
              </h2>
              <p className="text-sm text-gray-500">{error.name}</p>
            </div>
          </div>

          <div className="mb-4">
            {/* 本番環境ではエラーメッセージをマスク */}
            <p className="mb-2 text-gray-700">
              {isProduction
                ? "申し訳ございませんが、エラーが発生しました。しばらくしてから再度お試しください。"
                : error.message}
            </p>

            {/* digest（Next.js Error ID）がある場合は表示 */}
            {error.digest && (
              <div className="mt-4 rounded-md bg-gray-100 p-3">
                <p className="text-xs text-gray-600">
                  <span className="font-medium">Error ID:</span>{" "}
                  <code className="rounded bg-gray-200 px-2 py-1">{error.digest}</code>
                </p>
                <p className="mt-1 text-xs text-gray-500">
                  お問い合わせの際は、このIDをお伝えください
                </p>
              </div>
            )}

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

          <button
            onClick={reset}
            className="w-full rounded-md bg-blue-600 px-4 py-2 font-medium text-white transition duration-150 ease-in-out hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:outline-none"
          >
            再試行
          </button>
        </div>
      </div>
    </div>
  );
}

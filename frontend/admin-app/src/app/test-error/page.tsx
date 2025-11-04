"use client";

/**
 * Test Error Page (Development Only)
 *
 * E2Eテスト用のエラートリガーページ
 * - APIエラー（400, 404, 422, 500, 503）
 * - ネットワークエラー（タイムアウト、接続エラー）
 * - 認証エラー（401）
 * - バリデーションエラー
 *
 * 注意: このページは開発環境専用です
 */

import { useState } from "react";
import { ApiClient } from "@/lib/api-client";

const API_BASE_URL = process.env.NEXT_PUBLIC_API_URL || "http://localhost:13000";

export default function TestErrorPage() {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<Error | null>(null);

  // エラーをスローしてError Boundaryをトリガー
  if (error) {
    throw error;
  }

  const triggerError = async (errorType: string) => {
    setLoading(true);
    setError(null);

    try {
      const client = new ApiClient(API_BASE_URL);

      switch (errorType) {
        case "400-domain":
          // Domain Exception (400 Bad Request)
          await client.request("/test/domain-exception", { method: "GET" });
          break;

        case "404-application":
          // Application Exception (404 Not Found)
          await client.request("/test/application-exception", { method: "GET" });
          break;

        case "503-infrastructure":
          // Infrastructure Exception (503 Service Unavailable)
          await client.request("/test/infrastructure-exception", { method: "GET" });
          break;

        case "422-validation":
          // Validation Error (422 Unprocessable Entity)
          await client.request("/test/validation", {
            method: "POST",
            body: JSON.stringify({
              email: "invalid-email",
              name: "ab", // min:3 validation error
              // age is missing
            }),
          });
          break;

        case "401-auth":
          // Authentication Error (401 Unauthorized)
          await client.request("/test/auth-error", { method: "GET" });
          break;

        case "500-generic":
          // Generic 500 Error
          await client.request("/test/generic-exception", { method: "GET" });
          break;

        case "network-timeout":
          // Network Timeout (AbortError)
          await client.request("/test/slow-endpoint", {
            method: "GET",
            timeout: 100, // 100ms timeout
          });
          break;

        case "network-connection":
          // Network Connection Error (fetch to invalid URL)
          await fetch("http://invalid-domain-for-test.example.com/api");
          break;

        default:
          throw new Error("Unknown error type");
      }
    } catch (err) {
      // エラーをキャプチャしてError Boundaryにスロー
      setError(err as Error);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen bg-gray-50 p-8">
      <div className="mx-auto max-w-4xl">
        <h1 className="mb-6 text-3xl font-bold text-gray-900">Test Error Page</h1>
        <p className="mb-8 text-gray-600">
          このページはE2Eテスト用のエラートリガーページです。各ボタンをクリックすると対応するエラーが発生し、Error
          Boundaryがトリガーされます。
        </p>

        {loading && (
          <div className="mb-4 rounded-md bg-blue-50 p-4">
            <p className="text-blue-800">リクエスト中...</p>
          </div>
        )}

        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
          {/* API Errors */}
          <div className="rounded-lg bg-white p-6 shadow">
            <h2 className="mb-4 text-xl font-semibold text-gray-900">API Errors</h2>
            <div className="space-y-2">
              <button
                onClick={() => triggerError("400-domain")}
                disabled={loading}
                className="w-full rounded-md bg-red-600 px-4 py-2 text-white hover:bg-red-700 disabled:opacity-50"
                data-testid="trigger-400-error"
              >
                400 Domain Exception
              </button>
              <button
                onClick={() => triggerError("404-application")}
                disabled={loading}
                className="w-full rounded-md bg-orange-600 px-4 py-2 text-white hover:bg-orange-700 disabled:opacity-50"
                data-testid="trigger-404-error"
              >
                404 Application Exception
              </button>
              <button
                onClick={() => triggerError("503-infrastructure")}
                disabled={loading}
                className="w-full rounded-md bg-purple-600 px-4 py-2 text-white hover:bg-purple-700 disabled:opacity-50"
                data-testid="trigger-503-error"
              >
                503 Infrastructure Exception
              </button>
            </div>
          </div>

          {/* Validation Errors */}
          <div className="rounded-lg bg-white p-6 shadow">
            <h2 className="mb-4 text-xl font-semibold text-gray-900">Validation Errors</h2>
            <div className="space-y-2">
              <button
                onClick={() => triggerError("422-validation")}
                disabled={loading}
                className="w-full rounded-md bg-yellow-600 px-4 py-2 text-white hover:bg-yellow-700 disabled:opacity-50"
                data-testid="trigger-422-error"
              >
                422 Validation Error
              </button>
            </div>
          </div>

          {/* Authentication Errors */}
          <div className="rounded-lg bg-white p-6 shadow">
            <h2 className="mb-4 text-xl font-semibold text-gray-900">Authentication Errors</h2>
            <div className="space-y-2">
              <button
                onClick={() => triggerError("401-auth")}
                disabled={loading}
                className="w-full rounded-md bg-blue-600 px-4 py-2 text-white hover:bg-blue-700 disabled:opacity-50"
                data-testid="trigger-401-error"
              >
                401 Authentication Error
              </button>
            </div>
          </div>

          {/* Generic Errors */}
          <div className="rounded-lg bg-white p-6 shadow">
            <h2 className="mb-4 text-xl font-semibold text-gray-900">Generic Errors</h2>
            <div className="space-y-2">
              <button
                onClick={() => triggerError("500-generic")}
                disabled={loading}
                className="w-full rounded-md bg-gray-600 px-4 py-2 text-white hover:bg-gray-700 disabled:opacity-50"
                data-testid="trigger-500-error"
              >
                500 Generic Exception
              </button>
            </div>
          </div>

          {/* Network Errors */}
          <div className="col-span-1 rounded-lg bg-white p-6 shadow md:col-span-2">
            <h2 className="mb-4 text-xl font-semibold text-gray-900">Network Errors</h2>
            <div className="grid grid-cols-1 gap-2 md:grid-cols-2">
              <button
                onClick={() => triggerError("network-timeout")}
                disabled={loading}
                className="rounded-md bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700 disabled:opacity-50"
                data-testid="trigger-timeout-error"
              >
                Network Timeout
              </button>
              <button
                onClick={() => triggerError("network-connection")}
                disabled={loading}
                className="rounded-md bg-pink-600 px-4 py-2 text-white hover:bg-pink-700 disabled:opacity-50"
                data-testid="trigger-connection-error"
              >
                Network Connection Error
              </button>
            </div>
          </div>
        </div>

        <div className="mt-8 rounded-md bg-yellow-50 p-4">
          <p className="text-sm text-yellow-800">
            <strong>注意:</strong> このページは開発環境専用です。本番環境では無効化してください。
          </p>
        </div>
      </div>
    </div>
  );
}

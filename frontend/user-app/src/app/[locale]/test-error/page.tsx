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

import { useState, useRef } from "react";
import { ApiClient } from "@shared/api-client";
import { ApiError } from "@shared/api-error";
import { NetworkError } from "@shared/network-error";

// Force dynamic rendering to ensure environment variables are read at runtime
export const dynamic = "force-dynamic";

export default function TestErrorPage() {
  const [loading, setLoading] = useState(false);
  const [hasError, setHasError] = useState(false);
  const errorRef = useRef<Error | null>(null);

  // Read API URL at runtime in component (not at module level)
  const API_BASE_URL = process.env.NEXT_PUBLIC_API_URL || "http://localhost:13000";

  // エラーをスローしてError Boundaryをトリガー
  // Error.cause パターンにより、Next.jsが自動的に cause を保持します
  if (hasError && errorRef.current) {
    const err = errorRef.current;
    console.error("=== test-error page: About to throw error ===");
    console.error("Error type:", err.constructor.name);
    console.error("Error instanceof ApiError:", err instanceof ApiError);

    // ApiErrorの場合、プロパティ確認のためログ出力
    if (err instanceof ApiError) {
      console.error("ApiError properties:", {
        title: err.title,
        status: err.status,
        requestId: err.requestId,
        cause: err.cause,
      });
    }

    // Error.cause パターンを使用しているため、単純に throw するだけで OK
    // Error Boundary が error.cause から ApiError を再構築します
    throw err;
  }

  const triggerError = async (errorType: string) => {
    setLoading(true);
    setHasError(false);
    errorRef.current = null;

    try {
      const client = new ApiClient(API_BASE_URL);

      switch (errorType) {
        case "400-domain":
          // Domain Exception (400 Bad Request)
          await client.get("/api/test/domain-exception");
          break;

        case "404-application":
          // Application Exception (404 Not Found)
          await client.get("/api/test/application-exception");
          break;

        case "503-infrastructure":
          // Infrastructure Exception (503 Service Unavailable)
          await client.get("/api/test/infrastructure-exception");
          break;

        case "422-validation":
          // Validation Error (422 Unprocessable Entity)
          await client.post("/api/test/validation", {
            email: "invalid-email",
            name: "ab", // min:3 validation error
            // age is missing
          });
          break;

        case "401-auth":
          // Authentication Error (401 Unauthorized)
          await client.get("/api/test/auth-error");
          break;

        case "500-generic":
          // Generic 500 Error - throw plain Error (not ApiError)
          // This simulates an unexpected error that is not structured as RFC 7807
          throw new Error("Unexpected generic error occurred");
          break;

        case "network-timeout":
          // Network Timeout (AbortError)
          try {
            const controller = new AbortController();
            // 100ms timeout - backend sleeps for 35s, ensuring AbortError occurs
            const timeoutId = setTimeout(() => controller.abort(), 100);
            // Use Laravel slow endpoint that sleeps for 35 seconds
            await fetch(`${API_BASE_URL}/api/test/timeout-endpoint`, {
              signal: controller.signal,
            });
            clearTimeout(timeoutId);
          } catch (fetchErr) {
            // Convert AbortError to NetworkError for proper Error Boundary handling
            throw NetworkError.fromFetchError(fetchErr as Error);
          }
          break;

        case "network-connection":
          // Network Connection Error (fetch to invalid URL)
          try {
            await fetch("http://invalid-domain-for-test.example.com/api");
          } catch (fetchErr) {
            // Convert TypeError to NetworkError for proper Error Boundary handling
            throw NetworkError.fromFetchError(fetchErr as Error);
          }
          break;

        default:
          throw new Error("Unknown error type");
      }
      // エラーが発生しなかった場合はloadingを解除
      setLoading(false);
    } catch (err) {
      // エラーをキャプチャしてuseRefに保存（シリアライズを防ぐ）
      // 次のレンダリングで throw される
      setLoading(false);
      errorRef.current = err as Error;
      setHasError(true);
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

        {/* Debug: Show API URL */}
        <div className="mb-4 rounded-md bg-yellow-50 p-4">
          <p className="text-sm text-yellow-800">
            <strong>Debug - API URL:</strong> {API_BASE_URL}
          </p>
        </div>

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

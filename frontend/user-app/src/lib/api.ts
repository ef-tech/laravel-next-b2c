import { env } from "./env";
import { handleApiError, ApiError } from "./api-error-handler";

/**
 * APIバージョニングされたエンドポイントURLを構築
 *
 * @param endpoint - APIエンドポイント（例: "admin/login", "admin/dashboard"）
 * @returns 完全なAPIエンドポイントURL（例: "http://localhost:13000/api/v1/admin/login"）
 *
 * @example
 * const loginUrl = buildApiUrl("admin/login");
 * // => "http://localhost:13000/api/v1/admin/login"
 *
 * const dashboardUrl = buildApiUrl("admin/dashboard");
 * // => "http://localhost:13000/api/v1/admin/dashboard"
 */
export function buildApiUrl(endpoint: string): string {
  const baseUrl = env.NEXT_PUBLIC_API_URL;
  const version = env.NEXT_PUBLIC_API_VERSION;

  // エンドポイントの先頭のスラッシュを削除（あれば）
  const cleanEndpoint = endpoint.startsWith("/") ? endpoint.slice(1) : endpoint;

  return `${baseUrl}/api/${version}/${cleanEndpoint}`;
}

/**
 * エラーコード別処理分岐ヘルパー
 */
export const ErrorHandlers = {
  /**
   * 認証エラーかどうかを判定
   */
  isAuthError(error: unknown): boolean {
    return (
      error instanceof ApiError &&
      (error.code === "AUTH.INVALID_CREDENTIALS" ||
        error.code === "AUTH.TOKEN_EXPIRED" ||
        error.code === "AUTH.ACCOUNT_DISABLED")
    );
  },

  /**
   * バリデーションエラーかどうかを判定
   */
  isValidationError(error: unknown): boolean {
    return error instanceof ApiError && error.code === "VALIDATION_ERROR";
  },

  /**
   * 認証情報不正エラーかどうかを判定
   */
  isInvalidCredentials(error: unknown): boolean {
    return error instanceof ApiError && error.code === "AUTH.INVALID_CREDENTIALS";
  },

  /**
   * アカウント無効化エラーかどうかを判定
   */
  isAccountDisabled(error: unknown): boolean {
    return error instanceof ApiError && error.code === "AUTH.ACCOUNT_DISABLED";
  },

  /**
   * トークン期限切れエラーかどうかを判定
   */
  isTokenExpired(error: unknown): boolean {
    return error instanceof ApiError && error.code === "AUTH.TOKEN_EXPIRED";
  },
};

interface User {
  id: number;
  name: string;
  email: string;
}

export async function fetchUsers(): Promise<User[]> {
  const response = await fetch("/api/users");

  if (!response.ok) {
    await handleApiError(response);
  }

  const users = await response.json();
  return users;
}

// Re-export for convenience
export { ApiError, handleApiError };

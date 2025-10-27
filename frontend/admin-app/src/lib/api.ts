import { env } from "./env";

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

interface User {
  id: number;
  name: string;
  email: string;
}

export async function fetchUsers(): Promise<User[]> {
  const response = await fetch("/api/users");

  if (!response.ok) {
    throw new Error("Failed to fetch users");
  }

  const users = await response.json();
  return users;
}

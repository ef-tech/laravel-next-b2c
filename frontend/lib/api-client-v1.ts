/**
 * API V1 Client
 *
 * Laravel API V1エンドポイント用のHTTPクライアント
 */

import type {
  V1ApiFetchOptions,
  V1ApiResponse,
  V1CspReportRequest,
  V1HealthResponse,
  V1LoginRequest,
  V1LoginResponse,
  V1LogoutResponse,
  V1RegisterRequest,
  V1RegisterResponse,
  V1TokenDestroyAllResponse,
  V1TokenDestroyResponse,
  V1TokensResponse,
  V1TokenStoreRequest,
  V1TokenStoreResponse,
  V1UserResponse,
} from '../types/api/v1';

/**
 * API V1 Base URL
 */
const API_V1_BASE_URL =
  process.env.NEXT_PUBLIC_API_V1_BASE_URL ||
  process.env.NEXT_PUBLIC_API_BASE_URL ||
  'http://localhost:13000/api/v1';

/**
 * Fetch wrapper for V1 API
 */
async function fetchV1<T>(
  endpoint: string,
  options?: V1ApiFetchOptions
): Promise<V1ApiResponse<T>> {
  const url = `${API_V1_BASE_URL}${endpoint}`;

  // RFC 7807準拠: application/problem+jsonを優先的にサポート
  // Content Negotiation: problem+jsonを先頭に配置し、後方互換性のためapplication/jsonも含める
  // @see https://www.rfc-editor.org/rfc/rfc7807 (Problem Details for HTTP APIs)
  // @see https://www.rfc-editor.org/rfc/rfc7231#section-5.3.2 (Accept header)
  const headers: Record<string, string> = {
    'Content-Type': 'application/json',
    Accept: 'application/problem+json, application/json',
    ...options?.headers,
  };

  if (options?.token) {
    headers.Authorization = `Bearer ${options.token}`;
  }

  const fetchOptions: RequestInit = {
    method: options?.method || 'GET',
    headers,
  };

  if (options?.body && options.method !== 'GET') {
    fetchOptions.body = JSON.stringify(options.body);
  }

  const response = await fetch(url, fetchOptions);

  if (response.status === 204) {
    return {} as T;
  }

  const data = await response.json();

  if (!response.ok) {
    throw new Error(data.message || 'API request failed');
  }

  return data;
}

// ========================================
// Health Check
// ========================================

/**
 * GET /api/v1/health
 */
export async function getHealth(): Promise<V1ApiResponse<V1HealthResponse>> {
  return fetchV1<V1HealthResponse>('/health');
}

// ========================================
// Authentication
// ========================================

/**
 * POST /api/v1/users
 */
export async function register(
  request: V1RegisterRequest
): Promise<V1ApiResponse<V1RegisterResponse>> {
  return fetchV1<V1RegisterResponse>('/users', {
    method: 'POST',
    body: request,
  });
}

/**
 * POST /api/v1/login
 */
export async function login(
  request: V1LoginRequest
): Promise<V1ApiResponse<V1LoginResponse>> {
  return fetchV1<V1LoginResponse>('/login', {
    method: 'POST',
    body: request,
  });
}

/**
 * POST /api/v1/logout
 */
export async function logout(token: string): Promise<V1ApiResponse<V1LogoutResponse>> {
  return fetchV1<V1LogoutResponse>('/logout', {
    method: 'POST',
    token,
  });
}

/**
 * GET /api/v1/user
 */
export async function getUser(token: string): Promise<V1ApiResponse<V1UserResponse>> {
  return fetchV1<V1UserResponse>('/user', {
    token,
  });
}

// ========================================
// Token Management
// ========================================

/**
 * POST /api/v1/tokens
 */
export async function createToken(
  token: string,
  request?: V1TokenStoreRequest
): Promise<V1ApiResponse<V1TokenStoreResponse>> {
  return fetchV1<V1TokenStoreResponse>('/tokens', {
    method: 'POST',
    token,
    body: request,
  });
}

/**
 * GET /api/v1/tokens
 */
export async function getTokens(token: string): Promise<V1ApiResponse<V1TokensResponse>> {
  return fetchV1<V1TokensResponse>('/tokens', {
    token,
  });
}

/**
 * DELETE /api/v1/tokens/{id}
 */
export async function deleteToken(
  token: string,
  id: number
): Promise<V1ApiResponse<V1TokenDestroyResponse>> {
  return fetchV1<V1TokenDestroyResponse>(`/tokens/${id}`, {
    method: 'DELETE',
    token,
  });
}

/**
 * DELETE /api/v1/tokens
 */
export async function deleteAllTokens(
  token: string
): Promise<V1ApiResponse<V1TokenDestroyAllResponse>> {
  return fetchV1<V1TokenDestroyAllResponse>('/tokens', {
    method: 'DELETE',
    token,
  });
}

// ========================================
// CSP Reporting
// ========================================

/**
 * POST /api/v1/csp/report
 */
export async function reportCspViolation(
  request: V1CspReportRequest
): Promise<void> {
  await fetchV1<void>('/csp/report', {
    method: 'POST',
    body: request,
  });
}

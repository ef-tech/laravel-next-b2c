/**
 * API V1 Type Definitions
 *
 * Laravel API V1エンドポイントのTypeScript型定義
 */

// ========================================
// Common Types
// ========================================

/**
 * V1 API Error Response
 */
export interface V1ErrorResponse {
  error?: string;
  message: string;
  errors?: Record<string, string[]>; // バリデーションエラー
}

/**
 * V1 User
 */
export interface V1User {
  id: number;
  name: string;
  email: string;
  email_verified_at: string | null;
  created_at: string;
  updated_at: string;
}

/**
 * V1 Personal Access Token
 */
export interface V1Token {
  id: number;
  name: string;
  created_at: string;
  last_used_at: string | null;
}

// ========================================
// Health Check
// ========================================

/**
 * GET /api/v1/health
 */
export interface V1HealthResponse {
  status: 'ok';
  version?: string;
  timestamp?: string;
}

// ========================================
// Authentication
// ========================================

/**
 * POST /api/v1/register
 */
export interface V1RegisterRequest {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
}

export interface V1RegisterResponse {
  token: string;
  user: V1User;
}

/**
 * POST /api/v1/login
 */
export interface V1LoginRequest {
  email: string;
  password: string;
}

export interface V1LoginResponse {
  token: string;
  user: V1User;
}

/**
 * POST /api/v1/logout
 */
export interface V1LogoutResponse {
  message: string;
}

/**
 * GET /api/v1/user
 */
export type V1UserResponse = V1User;

// ========================================
// Token Management
// ========================================

/**
 * POST /api/v1/tokens
 */
export interface V1TokenStoreRequest {
  name?: string;
}

export interface V1TokenStoreResponse {
  token: string;
  name: string;
  created_at: string;
}

/**
 * GET /api/v1/tokens
 */
export interface V1TokensResponse {
  tokens: V1Token[];
}

/**
 * DELETE /api/v1/tokens/{id}
 */
export interface V1TokenDestroyResponse {
  message: string;
}

/**
 * DELETE /api/v1/tokens
 */
export interface V1TokenDestroyAllResponse {
  message: string;
}

// ========================================
// CSP Reporting
// ========================================

/**
 * POST /api/v1/csp-report
 */
export interface V1CspReport {
  'blocked-uri'?: string;
  'document-uri': string;
  'violated-directive': string;
  'original-policy'?: string;
  referrer?: string;
  'source-file'?: string;
  'line-number'?: number;
  'column-number'?: number;
  'status-code'?: number;
}

export interface V1CspReportRequest {
  'csp-report': V1CspReport;
}

// ========================================
// API Client Helper Types
// ========================================

/**
 * V1 API Response (Generic)
 */
export type V1ApiResponse<T> = T | V1ErrorResponse;

/**
 * V1 API Fetch Options
 */
export interface V1ApiFetchOptions {
  method?: 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE';
  headers?: Record<string, string>;
  body?: unknown;
  token?: string;
}

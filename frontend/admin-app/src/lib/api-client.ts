/**
 * ApiClient
 *
 * 統一APIクライアント（fetch wrapper）
 * - X-Request-IDヘッダー自動生成
 * - Accept-Languageヘッダー自動付与
 * - RFC 7807レスポンス解析
 * - ネットワークエラーハンドリング
 * - 30秒タイムアウト管理
 */

import { ApiError } from "./api-error";
import { NetworkError } from "./network-error";
import type { RFC7807Problem } from "../types/errors";

/**
 * APIクライアントクラス
 */
export class ApiClient {
  private readonly baseURL: string;
  private readonly timeout: number = 30000; // 30秒

  /**
   * コンストラクタ
   *
   * @param baseURL ベースURL（例: https://api.example.com）
   */
  constructor(baseURL: string) {
    this.baseURL = baseURL;
  }

  /**
   * 統一されたAPIリクエスト機能
   *
   * @param path リクエストパス（例: /users/1）
   * @param options fetchオプション
   * @returns レスポンスJSON
   */
  async request<T = unknown>(path: string, options: RequestInit = {}): Promise<T> {
    // AbortControllerによるタイムアウト管理
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), this.timeout);

    try {
      // ヘッダーをマージ
      const headers = this.buildHeaders(options.headers);

      // fetchリクエスト実行
      const response = await fetch(`${this.baseURL}${path}`, {
        ...options,
        headers,
        signal: controller.signal,
      });

      clearTimeout(timeoutId);

      // エラーレスポンスの場合、RFC 7807形式として解析
      if (!response.ok) {
        await this.handleErrorResponse(response);
      }

      // 正常レスポンスの場合、JSONデータを返す
      return (await response.json()) as T;
    } catch (error) {
      clearTimeout(timeoutId);

      // ApiErrorは既にthrowされているのでそのまま再throw
      if (error instanceof ApiError) {
        throw error;
      }

      // ネットワークエラーハンドリング
      if (error instanceof Error) {
        throw NetworkError.fromFetchError(error);
      }

      throw error;
    }
  }

  /**
   * GETリクエスト
   *
   * @param path リクエストパス
   * @param options fetchオプション
   * @returns レスポンスJSON
   */
  async get<T = unknown>(path: string, options: RequestInit = {}): Promise<T> {
    return this.request<T>(path, { ...options, method: "GET" });
  }

  /**
   * POSTリクエスト
   *
   * @param path リクエストパス
   * @param data リクエストボディデータ
   * @param options fetchオプション
   * @returns レスポンスJSON
   */
  async post<T = unknown>(path: string, data?: unknown, options: RequestInit = {}): Promise<T> {
    return this.request<T>(path, {
      ...options,
      method: "POST",
      body: data ? JSON.stringify(data) : undefined,
      headers: {
        ...options.headers,
        "Content-Type": "application/json",
      },
    });
  }

  /**
   * PUTリクエスト
   *
   * @param path リクエストパス
   * @param data リクエストボディデータ
   * @param options fetchオプション
   * @returns レスポンスJSON
   */
  async put<T = unknown>(path: string, data?: unknown, options: RequestInit = {}): Promise<T> {
    return this.request<T>(path, {
      ...options,
      method: "PUT",
      body: data ? JSON.stringify(data) : undefined,
      headers: {
        ...options.headers,
        "Content-Type": "application/json",
      },
    });
  }

  /**
   * DELETEリクエスト
   *
   * @param path リクエストパス
   * @param options fetchオプション
   * @returns レスポンスJSON
   */
  async delete<T = unknown>(path: string, options: RequestInit = {}): Promise<T> {
    return this.request<T>(path, { ...options, method: "DELETE" });
  }

  /**
   * ヘッダーを構築する
   *
   * @param customHeaders カスタムヘッダー
   * @returns マージされたヘッダー
   */
  private buildHeaders(customHeaders?: HeadersInit): Headers {
    const headers = new Headers(customHeaders);

    // X-Request-IDヘッダー自動生成（crypto.randomUUID()使用）
    if (!headers.has("X-Request-ID")) {
      headers.set("X-Request-ID", crypto.randomUUID());
    }

    // Accept-Languageヘッダー自動付与（ブラウザ言語設定取得）
    if (!headers.has("Accept-Language")) {
      headers.set("Accept-Language", this.getBrowserLanguage());
    }

    // Accept: application/problem+jsonヘッダーを設定
    if (!headers.has("Accept")) {
      headers.set("Accept", "application/problem+json");
    }

    return headers;
  }

  /**
   * ブラウザ言語設定を取得する
   *
   * @returns 言語コード（例: ja, en）
   */
  private getBrowserLanguage(): string {
    // navigator.languageからブラウザ言語設定を取得
    // 例: "ja", "ja-JP", "en-US" → "ja", "ja-JP", "en-US"
    return navigator.language || "en";
  }

  /**
   * エラーレスポンスをハンドリングする
   *
   * @param response fetchレスポンス
   */
  private async handleErrorResponse(response: Response): Promise<never> {
    try {
      // RFC 7807形式のレスポンスとして解析
      const problem: RFC7807Problem = await response.json();

      // ApiErrorインスタンスを生成してthrow
      throw new ApiError(problem);
    } catch (error) {
      // JSON解析失敗時は元のエラーをthrow
      if (error instanceof ApiError) {
        throw error;
      }

      // JSON解析に失敗した場合、汎用的なApiErrorを生成
      const genericProblem: RFC7807Problem = {
        type: "about:blank",
        title: "Unknown Error",
        status: response.status,
        detail: `HTTP ${response.status}: ${response.statusText}`,
        error_code: "unknown_error",
        trace_id: response.headers.get("X-Request-ID") || "unknown",
        instance: new URL(response.url).pathname,
        timestamp: new Date().toISOString(),
      };

      throw new ApiError(genericProblem);
    }
  }
}

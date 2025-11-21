/**
 * ApiClient Unit Tests
 *
 * 統一APIクライアント（fetch wrapper）の機能テスト
 * - X-Request-IDヘッダー自動生成
 * - Accept-Languageヘッダー自動付与
 * - RFC 7807レスポンス解析
 * - ネットワークエラーハンドリング
 * - RESTful APIメソッド（GET/POST/PUT/DELETE）
 */

import { describe, expect, it, beforeEach, afterEach } from "@jest/globals";
import { ApiClient } from "../../../../lib/api-client";
import { ApiError } from "../../../../lib/api-error";
import { NetworkError } from "../../../../lib/network-error";
import type { RFC7807Problem } from "../../../../types/errors";

// グローバルfetchのモック
let mockFetch: jest.Mock;

beforeEach(() => {
  mockFetch = jest.fn();
  global.fetch = mockFetch;
});

afterEach(() => {
  jest.restoreAllMocks();
});

describe("ApiClient", () => {
  describe("コンストラクタとベースURL設定", () => {
    it("baseURLを設定してインスタンスを生成できる", () => {
      const client = new ApiClient("https://api.example.com");
      expect(client).toBeInstanceOf(ApiClient);
    });
  });

  describe("request() メソッド - 基本機能", () => {
    it("X-Request-IDヘッダーが自動生成される", async () => {
      const client = new ApiClient("https://api.example.com");

      mockFetch.mockResolvedValueOnce({
        ok: true,
        status: 200,
        json: async () => ({ data: "success" }),
      });

      await client.request("/test", { method: "GET" });

      // Headers オブジェクトの検証
      const callArgs = mockFetch.mock.calls[0];
      const headers = callArgs?.[1]?.headers as Headers;
      expect(headers.get("X-Request-ID")).toMatch(
        /^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/,
      );
    });

    it("Accept-Languageヘッダーが自動付与される", async () => {
      const client = new ApiClient("https://api.example.com");

      mockFetch.mockResolvedValueOnce({
        ok: true,
        status: 200,
        json: async () => ({ data: "success" }),
      });

      await client.request("/test", { method: "GET" });

      // Headers オブジェクトの検証
      const callArgs = mockFetch.mock.calls[0];
      const headers = callArgs?.[1]?.headers as Headers;
      expect(headers.get("Accept-Language")).toBeTruthy();
      expect(typeof headers.get("Accept-Language")).toBe("string");
    });

    it("AcceptヘッダーがRFC 7807準拠で設定される", async () => {
      const client = new ApiClient("https://api.example.com");

      mockFetch.mockResolvedValueOnce({
        ok: true,
        status: 200,
        json: async () => ({ data: "success" }),
      });

      await client.request("/test", { method: "GET" });

      // Headers オブジェクトの検証
      // RFC 7807準拠: application/problem+jsonを優先的にサポート
      // Content Negotiation: problem+jsonを先頭に配置し、後方互換性のためapplication/jsonも含める
      const callArgs = mockFetch.mock.calls[0];
      const headers = callArgs?.[1]?.headers as Headers;
      expect(headers.get("Accept")).toBe("application/problem+json, application/json");
    });

    it("30秒タイムアウトが設定される（AbortController使用）", async () => {
      const client = new ApiClient("https://api.example.com");

      mockFetch.mockResolvedValueOnce({
        ok: true,
        status: 200,
        json: async () => ({ data: "success" }),
      });

      await client.request("/test", { method: "GET" });

      expect(mockFetch).toHaveBeenCalledWith(
        "https://api.example.com/test",
        expect.objectContaining({
          signal: expect.any(AbortSignal),
        }),
      );
    });

    it("正常レスポンス（200 OK）の場合、JSONデータを返す", async () => {
      const client = new ApiClient("https://api.example.com");
      const responseData = { id: 1, name: "Test User" };

      mockFetch.mockResolvedValueOnce({
        ok: true,
        status: 200,
        json: async () => responseData,
      });

      const result = await client.request("/users/1", { method: "GET" });

      expect(result).toEqual(responseData);
    });
  });

  describe("request() メソッド - RFC 7807レスポンス解析", () => {
    it("4xxレスポンスをRFC 7807形式として解析してApiErrorをthrowする", async () => {
      const client = new ApiClient("https://api.example.com");
      const problem: RFC7807Problem = {
        type: "https://api.example.com/errors/validation_error",
        title: "Validation Error",
        status: 422,
        detail: "The given data was invalid.",
        error_code: "validation_error",
        trace_id: "test-trace-id",
        instance: "/api/v1/users",
        timestamp: "2025-11-03T00:00:00Z",
        errors: {
          email: ["The email field is required."],
        },
      };

      mockFetch.mockResolvedValueOnce({
        ok: false,
        status: 422,
        json: async () => problem,
      });

      await expect(client.request("/users", { method: "POST" })).rejects.toThrow(ApiError);
    });

    it("5xxレスポンスをRFC 7807形式として解析してApiErrorをthrowする", async () => {
      const client = new ApiClient("https://api.example.com");
      const problem: RFC7807Problem = {
        type: "https://api.example.com/errors/internal_server_error",
        title: "Internal Server Error",
        status: 500,
        detail: "An internal server error occurred.",
        error_code: "internal_server_error",
        trace_id: "test-trace-id",
        instance: "/api/v1/users",
        timestamp: "2025-11-03T00:00:00Z",
      };

      mockFetch.mockResolvedValueOnce({
        ok: false,
        status: 500,
        json: async () => problem,
      });

      await expect(client.request("/users", { method: "GET" })).rejects.toThrow(ApiError);
    });

    it("バリデーションエラー（errorsフィールド）を適切に解析する", async () => {
      const client = new ApiClient("https://api.example.com");
      const problem: RFC7807Problem = {
        type: "https://api.example.com/errors/validation_error",
        title: "Validation Error",
        status: 422,
        detail: "The given data was invalid.",
        error_code: "validation_error",
        trace_id: "test-trace-id",
        instance: "/api/v1/users",
        timestamp: "2025-11-03T00:00:00Z",
        errors: {
          email: ["The email field is required.", "The email must be a valid email address."],
          name: ["The name field is required."],
        },
      };

      mockFetch.mockResolvedValueOnce({
        ok: false,
        status: 422,
        json: async () => problem,
      });

      const errorPromise = client.request("/users", { method: "POST" });
      await expect(errorPromise).rejects.toThrow(ApiError);

      const error = await errorPromise.catch((e) => e);
      expect(error).toBeInstanceOf(ApiError);
      expect(error.validationErrors).toEqual(problem.errors);
      expect(error.validationErrors?.email).toHaveLength(2);
      expect(error.validationErrors?.name).toHaveLength(1);
    });
  });

  describe("request() メソッド - ネットワークエラーハンドリング", () => {
    it("TypeError: Failed to fetch をキャッチしてNetworkErrorをthrowする", async () => {
      const client = new ApiClient("https://api.example.com");

      mockFetch.mockRejectedValueOnce(new TypeError("Failed to fetch"));

      await expect(client.request("/test", { method: "GET" })).rejects.toThrow(NetworkError);
    });

    it("AbortError（タイムアウト）をキャッチしてNetworkErrorをthrowする", async () => {
      const client = new ApiClient("https://api.example.com");

      const abortError = new DOMException("The operation was aborted", "AbortError");
      mockFetch.mockRejectedValueOnce(abortError);

      await expect(client.request("/test", { method: "GET" })).rejects.toThrow(NetworkError);
    });

    it("その他のFetch APIエラーを適切にハンドリングする", async () => {
      const client = new ApiClient("https://api.example.com");

      mockFetch.mockRejectedValueOnce(new Error("Unknown network error"));

      await expect(client.request("/test", { method: "GET" })).rejects.toThrow(NetworkError);
    });
  });

  describe("RESTful APIメソッド", () => {
    it("get() メソッドでGETリクエストを実行できる", async () => {
      const client = new ApiClient("https://api.example.com");
      const responseData = { id: 1, name: "Test User" };

      mockFetch.mockResolvedValueOnce({
        ok: true,
        status: 200,
        json: async () => responseData,
      });

      const result = await client.get("/users/1");

      expect(result).toEqual(responseData);
      expect(mockFetch).toHaveBeenCalledWith(
        "https://api.example.com/users/1",
        expect.objectContaining({
          method: "GET",
        }),
      );
    });

    it("post() メソッドでPOSTリクエストを実行できる", async () => {
      const client = new ApiClient("https://api.example.com");
      const requestData = { name: "New User", email: "user@example.com" };
      const responseData = { id: 2, ...requestData };

      mockFetch.mockResolvedValueOnce({
        ok: true,
        status: 201,
        json: async () => responseData,
      });

      const result = await client.post("/users", requestData);

      expect(result).toEqual(responseData);
      expect(mockFetch).toHaveBeenCalledWith(
        "https://api.example.com/users",
        expect.objectContaining({
          method: "POST",
          body: JSON.stringify(requestData),
        }),
      );

      // Headers オブジェクトの検証
      const callArgs = mockFetch.mock.calls[0];
      const headers = callArgs?.[1]?.headers as Headers;
      expect(headers.get("Content-Type")).toBe("application/json");
    });

    it("put() メソッドでPUTリクエストを実行できる", async () => {
      const client = new ApiClient("https://api.example.com");
      const requestData = { name: "Updated User", email: "updated@example.com" };
      const responseData = { id: 1, ...requestData };

      mockFetch.mockResolvedValueOnce({
        ok: true,
        status: 200,
        json: async () => responseData,
      });

      const result = await client.put("/users/1", requestData);

      expect(result).toEqual(responseData);
      expect(mockFetch).toHaveBeenCalledWith(
        "https://api.example.com/users/1",
        expect.objectContaining({
          method: "PUT",
          body: JSON.stringify(requestData),
        }),
      );

      // Headers オブジェクトの検証
      const callArgs = mockFetch.mock.calls[0];
      const headers = callArgs?.[1]?.headers as Headers;
      expect(headers.get("Content-Type")).toBe("application/json");
    });

    it("delete() メソッドでDELETEリクエストを実行できる", async () => {
      const client = new ApiClient("https://api.example.com");

      mockFetch.mockResolvedValueOnce({
        ok: true,
        status: 204,
        json: async () => ({}),
      });

      await client.delete("/users/1");

      expect(mockFetch).toHaveBeenCalledWith(
        "https://api.example.com/users/1",
        expect.objectContaining({
          method: "DELETE",
        }),
      );
    });
  });

  describe("カスタムヘッダー追加", () => {
    it("request()でカスタムヘッダーを追加できる", async () => {
      const client = new ApiClient("https://api.example.com");

      mockFetch.mockResolvedValueOnce({
        ok: true,
        status: 200,
        json: async () => ({ data: "success" }),
      });

      await client.request("/test", {
        method: "GET",
        headers: {
          "X-Custom-Header": "custom-value",
        },
      });

      expect(mockFetch).toHaveBeenCalledWith(
        "https://api.example.com/test",
        expect.objectContaining({
          method: "GET",
        }),
      );

      // Headers オブジェクトの検証
      const callArgs = mockFetch.mock.calls[0];
      const headers = callArgs?.[1]?.headers as Headers;
      expect(headers.get("X-Custom-Header")).toBe("custom-value");
      expect(headers.get("X-Request-ID")).toMatch(
        /^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/,
      );
    });
  });
});

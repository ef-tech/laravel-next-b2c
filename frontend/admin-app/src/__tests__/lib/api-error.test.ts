/**
 * ApiError Unit Tests
 *
 * RFC 7807レスポンスからApiErrorインスタンスを生成し、
 * 型安全なエラーハンドリングを提供するクラスのテスト
 */

import { describe, expect, it } from "@jest/globals";
import { ApiError } from "../../../../lib/api-error";
import type { RFC7807Problem } from "../../../../types/errors";

describe("ApiError", () => {
  describe("RFC 7807レスポンスからインスタンス生成", () => {
    it("RFC 7807レスポンスから全プロパティを正しく設定する", () => {
      const problem: RFC7807Problem = {
        type: "https://api.example.com/errors/validation_error",
        title: "Validation Error",
        status: 422,
        detail: "The given data was invalid.",
        error_code: "validation_error",
        trace_id: "550e8400-e29b-41d4-a716-446655440000",
        instance: "/api/v1/users",
        timestamp: "2025-11-03T00:00:00Z",
      };

      const error = new ApiError(problem);

      expect(error.type).toBe(problem.type);
      expect(error.title).toBe(problem.title);
      expect(error.status).toBe(problem.status);
      expect(error.detail).toBe(problem.detail);
      expect(error.errorCode).toBe(problem.error_code);
      expect(error.requestId).toBe(problem.trace_id);
      expect(error.instance).toBe(problem.instance);
      expect(error.timestamp).toBe(problem.timestamp);
      expect(error.message).toBe(problem.detail);
      expect(error.name).toBe("ApiError");
    });

    it("バリデーションエラーの場合、errorsフィールドを設定する", () => {
      const problem: RFC7807Problem = {
        type: "https://api.example.com/errors/validation_error",
        title: "Validation Error",
        status: 422,
        detail: "The given data was invalid.",
        error_code: "validation_error",
        trace_id: "550e8400-e29b-41d4-a716-446655440000",
        instance: "/api/v1/users",
        timestamp: "2025-11-03T00:00:00Z",
        errors: {
          email: ["The email field is required.", "The email must be a valid email address."],
          name: ["The name field is required."],
        },
      };

      const error = new ApiError(problem);

      expect(error.validationErrors).toEqual(problem.errors);
      expect(error.validationErrors?.email).toHaveLength(2);
      expect(error.validationErrors?.name).toHaveLength(1);
    });

    it("errorsフィールドがない場合、validationErrorsはundefinedになる", () => {
      const problem: RFC7807Problem = {
        type: "https://api.example.com/errors/unauthenticated",
        title: "Unauthenticated",
        status: 401,
        detail: "Unauthenticated.",
        error_code: "unauthenticated",
        trace_id: "550e8400-e29b-41d4-a716-446655440000",
        instance: "/api/v1/user",
        timestamp: "2025-11-03T00:00:00Z",
      };

      const error = new ApiError(problem);

      expect(error.validationErrors).toBeUndefined();
    });
  });

  describe("isValidationError()", () => {
    it("422ステータスコードの場合、trueを返す", () => {
      const problem: RFC7807Problem = {
        type: "https://api.example.com/errors/validation_error",
        title: "Validation Error",
        status: 422,
        detail: "The given data was invalid.",
        error_code: "validation_error",
        trace_id: "550e8400-e29b-41d4-a716-446655440000",
        instance: "/api/v1/users",
        timestamp: "2025-11-03T00:00:00Z",
      };

      const error = new ApiError(problem);

      expect(error.isValidationError()).toBe(true);
    });

    it("422以外のステータスコードの場合、falseを返す", () => {
      const problem: RFC7807Problem = {
        type: "https://api.example.com/errors/unauthenticated",
        title: "Unauthenticated",
        status: 401,
        detail: "Unauthenticated.",
        error_code: "unauthenticated",
        trace_id: "550e8400-e29b-41d4-a716-446655440000",
        instance: "/api/v1/user",
        timestamp: "2025-11-03T00:00:00Z",
      };

      const error = new ApiError(problem);

      expect(error.isValidationError()).toBe(false);
    });
  });

  describe("isAuthenticationError()", () => {
    it("401ステータスコードの場合、trueを返す", () => {
      const problem: RFC7807Problem = {
        type: "https://api.example.com/errors/unauthenticated",
        title: "Unauthenticated",
        status: 401,
        detail: "Unauthenticated.",
        error_code: "unauthenticated",
        trace_id: "550e8400-e29b-41d4-a716-446655440000",
        instance: "/api/v1/user",
        timestamp: "2025-11-03T00:00:00Z",
      };

      const error = new ApiError(problem);

      expect(error.isAuthenticationError()).toBe(true);
    });

    it("401以外のステータスコードの場合、falseを返す", () => {
      const problem: RFC7807Problem = {
        type: "https://api.example.com/errors/validation_error",
        title: "Validation Error",
        status: 422,
        detail: "The given data was invalid.",
        error_code: "validation_error",
        trace_id: "550e8400-e29b-41d4-a716-446655440000",
        instance: "/api/v1/users",
        timestamp: "2025-11-03T00:00:00Z",
      };

      const error = new ApiError(problem);

      expect(error.isAuthenticationError()).toBe(false);
    });
  });

  describe("isNotFoundError()", () => {
    it("404ステータスコードの場合、trueを返す", () => {
      const problem: RFC7807Problem = {
        type: "https://api.example.com/errors/not_found",
        title: "Not Found",
        status: 404,
        detail: "The requested resource was not found.",
        error_code: "not_found",
        trace_id: "550e8400-e29b-41d4-a716-446655440000",
        instance: "/api/v1/users/999",
        timestamp: "2025-11-03T00:00:00Z",
      };

      const error = new ApiError(problem);

      expect(error.isNotFoundError()).toBe(true);
    });

    it("404以外のステータスコードの場合、falseを返す", () => {
      const problem: RFC7807Problem = {
        type: "https://api.example.com/errors/validation_error",
        title: "Validation Error",
        status: 422,
        detail: "The given data was invalid.",
        error_code: "validation_error",
        trace_id: "550e8400-e29b-41d4-a716-446655440000",
        instance: "/api/v1/users",
        timestamp: "2025-11-03T00:00:00Z",
      };

      const error = new ApiError(problem);

      expect(error.isNotFoundError()).toBe(false);
    });
  });

  describe("getDisplayMessage()", () => {
    it("バリデーションエラーの場合、最初のエラーメッセージを返す", () => {
      const problem: RFC7807Problem = {
        type: "https://api.example.com/errors/validation_error",
        title: "Validation Error",
        status: 422,
        detail: "The given data was invalid.",
        error_code: "validation_error",
        trace_id: "550e8400-e29b-41d4-a716-446655440000",
        instance: "/api/v1/users",
        timestamp: "2025-11-03T00:00:00Z",
        errors: {
          email: ["The email field is required."],
          name: ["The name field is required."],
        },
      };

      const error = new ApiError(problem);

      // 最初のフィールドの最初のエラーメッセージを返す
      expect(error.getDisplayMessage()).toBe("The email field is required.");
    });

    it("バリデーションエラーでない場合、detailメッセージを返す", () => {
      const problem: RFC7807Problem = {
        type: "https://api.example.com/errors/unauthenticated",
        title: "Unauthenticated",
        status: 401,
        detail: "Unauthenticated.",
        error_code: "unauthenticated",
        trace_id: "550e8400-e29b-41d4-a716-446655440000",
        instance: "/api/v1/user",
        timestamp: "2025-11-03T00:00:00Z",
      };

      const error = new ApiError(problem);

      expect(error.getDisplayMessage()).toBe("Unauthenticated.");
    });

    it("サーバーエラー（500）の場合、汎用メッセージを返す", () => {
      const problem: RFC7807Problem = {
        type: "https://api.example.com/errors/internal_server_error",
        title: "Internal Server Error",
        status: 500,
        detail: "An internal server error occurred. Please try again later.",
        error_code: "internal_server_error",
        trace_id: "550e8400-e29b-41d4-a716-446655440000",
        instance: "/api/v1/users",
        timestamp: "2025-11-03T00:00:00Z",
      };

      const error = new ApiError(problem);

      expect(error.getDisplayMessage()).toBe(
        "An internal server error occurred. Please try again later.",
      );
    });
  });

  describe("AppErrorインターフェース準拠", () => {
    it("AppErrorインターフェースのメソッドを実装している", () => {
      const problem: RFC7807Problem = {
        type: "https://api.example.com/errors/validation_error",
        title: "Validation Error",
        status: 422,
        detail: "The given data was invalid.",
        error_code: "validation_error",
        trace_id: "550e8400-e29b-41d4-a716-446655440000",
        instance: "/api/v1/users",
        timestamp: "2025-11-03T00:00:00Z",
      };

      const error = new ApiError(problem);

      // AppErrorインターフェースのプロパティ
      expect(error.message).toBeDefined();
      expect(error.name).toBe("ApiError");

      // AppErrorインターフェースのメソッド
      expect(typeof error.getDisplayMessage).toBe("function");
      expect(typeof error.getDisplayMessage()).toBe("string");
    });
  });
});

/**
 * NetworkError Unit Tests
 *
 * Fetch APIエラーからNetworkErrorインスタンスを生成し、
 * ネットワークエラーの種類を判定する機能のテスト
 */

import { describe, expect, it } from "@jest/globals";
import { NetworkError } from "../../../../lib/network-error";

describe("NetworkError", () => {
  describe("fromFetchError() ファクトリーメソッド", () => {
    it("TypeError (Failed to fetch) からNetworkErrorを生成する", () => {
      const fetchError = new TypeError("Failed to fetch");
      const error = NetworkError.fromFetchError(fetchError);

      expect(error).toBeInstanceOf(NetworkError);
      expect(error.name).toBe("NetworkError");
      expect(error.message).toContain("connection");
      expect(error.isRetryable).toBe(true);
    });

    it("AbortError (Timeout) からNetworkErrorを生成する", () => {
      const abortError = new DOMException("The operation was aborted", "AbortError");
      const error = NetworkError.fromFetchError(abortError);

      expect(error).toBeInstanceOf(NetworkError);
      expect(error.name).toBe("NetworkError");
      expect(error.message).toContain("timeout");
      expect(error.isRetryable).toBe(true);
    });

    it("汎用Errorから NetworkErrorを生成する", () => {
      const genericError = new Error("Unknown network error");
      const error = NetworkError.fromFetchError(genericError);

      expect(error).toBeInstanceOf(NetworkError);
      expect(error.name).toBe("NetworkError");
      expect(error.message).toContain("Unknown network error");
      expect(error.isRetryable).toBe(false);
    });
  });

  describe("isTimeout()", () => {
    it("AbortErrorの場合、trueを返す", () => {
      const abortError = new DOMException("The operation was aborted", "AbortError");
      const error = NetworkError.fromFetchError(abortError);

      expect(error.isTimeout()).toBe(true);
    });

    it("TimeoutErrorの場合、trueを返す", () => {
      const timeoutError = new Error("Timeout");
      timeoutError.name = "TimeoutError";
      const error = NetworkError.fromFetchError(timeoutError);

      expect(error.isTimeout()).toBe(true);
    });

    it("AbortError/TimeoutError以外の場合、falseを返す", () => {
      const fetchError = new TypeError("Failed to fetch");
      const error = NetworkError.fromFetchError(fetchError);

      expect(error.isTimeout()).toBe(false);
    });
  });

  describe("isConnectionError()", () => {
    it("TypeError (Failed to fetch) の場合、trueを返す", () => {
      const fetchError = new TypeError("Failed to fetch");
      const error = NetworkError.fromFetchError(fetchError);

      expect(error.isConnectionError()).toBe(true);
    });

    it("TypeError以外の場合、falseを返す", () => {
      const abortError = new DOMException("The operation was aborted", "AbortError");
      const error = NetworkError.fromFetchError(abortError);

      expect(error.isConnectionError()).toBe(false);
    });
  });

  describe("getDisplayMessage()", () => {
    it("タイムアウトエラーの場合、タイムアウトメッセージを返す", () => {
      const abortError = new DOMException("The operation was aborted", "AbortError");
      const error = NetworkError.fromFetchError(abortError);

      expect(error.getDisplayMessage().toLowerCase()).toContain("time");
    });

    it("接続エラーの場合、接続エラーメッセージを返す", () => {
      const fetchError = new TypeError("Failed to fetch");
      const error = NetworkError.fromFetchError(fetchError);

      expect(error.getDisplayMessage().toLowerCase()).toContain("network");
    });

    it("その他のエラーの場合、汎用エラーメッセージを返す", () => {
      const genericError = new Error("Unknown error");
      const error = NetworkError.fromFetchError(genericError);

      expect(error.getDisplayMessage()).toContain("error");
    });
  });

  describe("isRetryableプロパティ", () => {
    it("TypeError (Failed to fetch) の場合、isRetryableがtrueになる", () => {
      const fetchError = new TypeError("Failed to fetch");
      const error = NetworkError.fromFetchError(fetchError);

      expect(error.isRetryable).toBe(true);
    });

    it("AbortError (Timeout) の場合、isRetryableがtrueになる", () => {
      const abortError = new DOMException("The operation was aborted", "AbortError");
      const error = NetworkError.fromFetchError(abortError);

      expect(error.isRetryable).toBe(true);
    });

    it("汎用Errorの場合、isRetryableがfalseになる", () => {
      const genericError = new Error("Unknown error");
      const error = NetworkError.fromFetchError(genericError);

      expect(error.isRetryable).toBe(false);
    });
  });

  describe("AppErrorインターフェース準拠", () => {
    it("AppErrorインターフェースのメソッドを実装している", () => {
      const fetchError = new TypeError("Failed to fetch");
      const error = NetworkError.fromFetchError(fetchError);

      // AppErrorインターフェースのプロパティ
      expect(error.message).toBeDefined();
      expect(error.name).toBe("NetworkError");

      // AppErrorインターフェースのメソッド
      expect(typeof error.getDisplayMessage).toBe("function");
      expect(typeof error.getDisplayMessage()).toBe("string");
    });
  });
});

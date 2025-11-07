/**
 * NetworkError i18n tests
 *
 * These tests verify that NetworkError.getDisplayMessage() correctly
 * handles both backward-compatible (no translation) and i18n-enabled
 * (with translation function) modes.
 */

import { NetworkError } from "@/../../lib/network-error";

describe("NetworkError.getDisplayMessage()", () => {
  describe("Backward compatibility (no translation function)", () => {
    it("タイムアウトエラーで日本語メッセージを返す", () => {
      const timeoutError = new Error("timeout");
      timeoutError.name = "AbortError";
      const error = NetworkError.fromFetchError(timeoutError);

      expect(error.getDisplayMessage()).toBe(
        "リクエストがタイムアウトしました。しばらくしてから再度お試しください。",
      );
    });

    it("接続エラーで日本語メッセージを返す", () => {
      const connectionError = new TypeError("Failed to fetch");
      const error = NetworkError.fromFetchError(connectionError);

      expect(error.getDisplayMessage()).toBe(
        "ネットワーク接続に問題が発生しました。インターネット接続を確認して再度お試しください。",
      );
    });

    it("不明なエラーで日本語メッセージを返す", () => {
      const unknownError = new Error("Unknown error");
      const error = NetworkError.fromFetchError(unknownError);

      expect(error.getDisplayMessage()).toBe(
        "予期しないエラーが発生しました。しばらくしてから再度お試しください。",
      );
    });
  });

  describe("i18n-enabled (with translation function)", () => {
    const mockTranslations = {
      "network.timeout": "The request timed out. Please try again later.",
      "network.connection":
        "A network connection problem occurred. Please check your internet connection and try again.",
      "network.unknown": "An unexpected error occurred. Please try again later.",
    };

    const mockT = (key: string) => {
      return mockTranslations[key as keyof typeof mockTranslations] || key;
    };

    it("タイムアウトエラーで翻訳メッセージを返す", () => {
      const timeoutError = new Error("timeout");
      timeoutError.name = "AbortError";
      const error = NetworkError.fromFetchError(timeoutError);

      expect(error.getDisplayMessage(mockT)).toBe("The request timed out. Please try again later.");
    });

    it("接続エラーで翻訳メッセージを返す", () => {
      const connectionError = new TypeError("Failed to fetch");
      const error = NetworkError.fromFetchError(connectionError);

      expect(error.getDisplayMessage(mockT)).toBe(
        "A network connection problem occurred. Please check your internet connection and try again.",
      );
    });

    it("不明なエラーで翻訳メッセージを返す", () => {
      const unknownError = new Error("Unknown error");
      const error = NetworkError.fromFetchError(unknownError);

      expect(error.getDisplayMessage(mockT)).toBe(
        "An unexpected error occurred. Please try again later.",
      );
    });

    it("翻訳関数がundefinedの場合は日本語メッセージを返す", () => {
      const timeoutError = new Error("timeout");
      timeoutError.name = "AbortError";
      const error = NetworkError.fromFetchError(timeoutError);

      expect(error.getDisplayMessage(undefined)).toBe(
        "リクエストがタイムアウトしました。しばらくしてから再度お試しください。",
      );
    });
  });

  describe("Translation key mapping", () => {
    let translationKeys: string[] = [];

    const captureT = (key: string) => {
      translationKeys.push(key);
      return key;
    };

    beforeEach(() => {
      translationKeys = [];
    });

    it('タイムアウトエラーで"network.timeout"キーを使用する', () => {
      const timeoutError = new Error("timeout");
      timeoutError.name = "TimeoutError";
      const error = NetworkError.fromFetchError(timeoutError);

      error.getDisplayMessage(captureT);

      expect(translationKeys).toContain("network.timeout");
    });

    it('接続エラーで"network.connection"キーを使用する', () => {
      const connectionError = new TypeError("Failed to fetch");
      const error = NetworkError.fromFetchError(connectionError);

      error.getDisplayMessage(captureT);

      expect(translationKeys).toContain("network.connection");
    });

    it('不明なエラーで"network.unknown"キーを使用する', () => {
      const unknownError = new Error("Something went wrong");
      const error = NetworkError.fromFetchError(unknownError);

      error.getDisplayMessage(captureT);

      expect(translationKeys).toContain("network.unknown");
    });
  });
});

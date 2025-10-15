/**
 * 環境変数バリデーションのテスト（User App）
 *
 * TDD (Test-Driven Development) による実装
 * - RED: テスト失敗
 * - GREEN: テスト成功
 * - REFACTOR: リファクタリング
 */

describe("環境変数バリデーション (User App)", () => {
  const originalEnv = process.env;

  beforeEach(() => {
    // 各テスト前に環境変数をリセット
    jest.resetModules();
    process.env = { ...originalEnv };
  });

  afterAll(() => {
    // テスト終了後に環境変数を復元
    process.env = originalEnv;
  });

  describe("NEXT_PUBLIC_API_URL", () => {
    test("正常なURL形式でバリデーションが成功する", () => {
      process.env.NEXT_PUBLIC_API_URL = "http://localhost:13000";
      process.env.NODE_ENV = "development";

      // src/lib/env.ts をインポートしてバリデーション実行
      expect(() => require("../env")).not.toThrow();
    });

    test("不正なURL形式でバリデーションエラーが発生する", () => {
      process.env.NEXT_PUBLIC_API_URL = "invalid-url";
      process.env.NODE_ENV = "development";

      // src/lib/env.ts をインポートしてバリデーション実行
      expect(() => require("../env")).toThrow("環境変数が正しく設定されていません");
    });

    test("NEXT_PUBLIC_API_URLが未設定の場合、デフォルト値が使用される", () => {
      delete process.env.NEXT_PUBLIC_API_URL;
      process.env.NODE_ENV = "development";

      // src/lib/env.ts をインポートしてバリデーション実行
      expect(() => require("../env")).not.toThrow();

      const { env } = require("../env");
      expect(env.NEXT_PUBLIC_API_URL).toBe("http://localhost:13000");
    });
  });

  describe("NEXT_PUBLIC_APP_NAME", () => {
    test("正常な文字列でバリデーションが成功する", () => {
      process.env.NEXT_PUBLIC_API_URL = "http://localhost:13000";
      process.env.NEXT_PUBLIC_APP_NAME = "User App";
      process.env.NODE_ENV = "development";

      expect(() => require("../env")).not.toThrow();
    });

    test("NEXT_PUBLIC_APP_NAMEが未設定の場合、デフォルト値が使用される", () => {
      process.env.NEXT_PUBLIC_API_URL = "http://localhost:13000";
      delete process.env.NEXT_PUBLIC_APP_NAME;
      process.env.NODE_ENV = "development";

      expect(() => require("../env")).not.toThrow();

      const { env } = require("../env");
      expect(env.NEXT_PUBLIC_APP_NAME).toBe("User App");
    });
  });

  describe("NODE_ENV", () => {
    test("許可されたNODE_ENV値でバリデーションが成功する", () => {
      process.env.NEXT_PUBLIC_API_URL = "http://localhost:13000";
      process.env.NODE_ENV = "production";

      expect(() => require("../env")).not.toThrow();
    });

    test("許可されていないNODE_ENV値でバリデーションエラーが発生する", () => {
      process.env.NEXT_PUBLIC_API_URL = "http://localhost:13000";
      process.env.NODE_ENV = "invalid" as any;

      expect(() => require("../env")).toThrow("環境変数が正しく設定されていません");
    });

    test("NODE_ENVが未設定の場合、デフォルト値が使用される", () => {
      process.env.NEXT_PUBLIC_API_URL = "http://localhost:13000";
      delete process.env.NODE_ENV;

      expect(() => require("../env")).not.toThrow();

      const { env } = require("../env");
      expect(env.NODE_ENV).toBe("development");
    });
  });

  describe("型安全性", () => {
    test("envオブジェクトが正しい型でエクスポートされる", () => {
      process.env.NEXT_PUBLIC_API_URL = "http://localhost:13000";
      process.env.NEXT_PUBLIC_APP_NAME = "User App";
      process.env.NODE_ENV = "development";

      const { env } = require("../env");

      expect(typeof env.NEXT_PUBLIC_API_URL).toBe("string");
      expect(typeof env.NEXT_PUBLIC_APP_NAME).toBe("string");
      expect(typeof env.NODE_ENV).toBe("string");
    });
  });

  describe("エラーメッセージの明瞭性", () => {
    test("バリデーションエラー時に明確なエラーメッセージを表示する", () => {
      process.env.NEXT_PUBLIC_API_URL = "invalid-url";
      process.env.NODE_ENV = "development";

      expect(() => require("../env")).toThrow(
        "環境変数が正しく設定されていません。.env.local を確認してください。",
      );
    });
  });
});

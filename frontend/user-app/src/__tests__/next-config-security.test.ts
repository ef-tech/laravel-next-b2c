import nextConfig from "../../next.config";

describe("User App next.config.ts セキュリティヘッダー", () => {
  describe("headers 設定", () => {
    it("headers 関数が定義されていること", () => {
      expect(nextConfig.headers).toBeDefined();
      expect(typeof nextConfig.headers).toBe("function");
    });

    it("セキュリティヘッダーが正しく設定されていること", async () => {
      if (!nextConfig.headers) {
        throw new Error("headers function is not defined");
      }

      const headers = await nextConfig.headers();

      expect(headers).toHaveLength(1);
      expect(headers[0].source).toBe("/:path*");
      expect(headers[0].headers).toBeDefined();
    });

    it("X-Frame-Options ヘッダーが設定されていること", async () => {
      if (!nextConfig.headers) {
        throw new Error("headers function is not defined");
      }

      const headers = await nextConfig.headers();
      const xFrameOptions = headers[0].headers.find((h) => h.key === "X-Frame-Options");

      expect(xFrameOptions).toBeDefined();
      expect(xFrameOptions?.value).toBe("SAMEORIGIN");
    });

    it("X-Content-Type-Options ヘッダーが設定されていること", async () => {
      if (!nextConfig.headers) {
        throw new Error("headers function is not defined");
      }

      const headers = await nextConfig.headers();
      const xContentTypeOptions = headers[0].headers.find(
        (h) => h.key === "X-Content-Type-Options",
      );

      expect(xContentTypeOptions).toBeDefined();
      expect(xContentTypeOptions?.value).toBe("nosniff");
    });

    it("Referrer-Policy ヘッダーが設定されていること", async () => {
      if (!nextConfig.headers) {
        throw new Error("headers function is not defined");
      }

      const headers = await nextConfig.headers();
      const referrerPolicy = headers[0].headers.find((h) => h.key === "Referrer-Policy");

      expect(referrerPolicy).toBeDefined();
      expect(referrerPolicy?.value).toBe("strict-origin-when-cross-origin");
    });

    it("Content-Security-Policy ヘッダーが設定されていること", async () => {
      if (!nextConfig.headers) {
        throw new Error("headers function is not defined");
      }

      const headers = await nextConfig.headers();
      const csp = headers[0].headers.find((h) => h.key === "Content-Security-Policy");

      expect(csp).toBeDefined();
      expect(csp?.value).toContain("default-src 'self'");
      expect(csp?.value).toContain("script-src");
      expect(csp?.value).toContain("style-src");
      expect(csp?.value).toContain("img-src");
      expect(csp?.value).toContain("connect-src");
      expect(csp?.value).toContain("font-src");
      expect(csp?.value).toContain("object-src 'none'");
      expect(csp?.value).toContain("frame-ancestors 'none'");
    });

    it("Permissions-Policy ヘッダーが設定されていること", async () => {
      if (!nextConfig.headers) {
        throw new Error("headers function is not defined");
      }

      const headers = await nextConfig.headers();
      const permissionsPolicy = headers[0].headers.find((h) => h.key === "Permissions-Policy");

      expect(permissionsPolicy).toBeDefined();
      expect(permissionsPolicy?.value).toContain("geolocation=(self)");
      expect(permissionsPolicy?.value).toContain("camera=()");
      expect(permissionsPolicy?.value).toContain("microphone=()");
      expect(permissionsPolicy?.value).toContain("payment=(self)");
    });

    it("開発環境では unsafe-eval が含まれること", async () => {
      // 開発環境は NODE_ENV=development で判定される想定
      const originalEnv = process.env.NODE_ENV;
      process.env.NODE_ENV = "development";

      // next.config.ts をリロード（動的インポート）
      jest.resetModules();
      const { default: devConfig } = await import("../../next.config");

      if (!devConfig.headers) {
        throw new Error("headers function is not defined");
      }

      const headers = await devConfig.headers();
      const csp = headers[0].headers.find((h) => h.key === "Content-Security-Policy");

      expect(csp?.value).toContain("'unsafe-eval'");
      expect(csp?.value).toContain("ws:");
      expect(csp?.value).toContain("wss:");

      // 元に戻す
      process.env.NODE_ENV = originalEnv;
    });

    it("本番環境では Strict-Transport-Security ヘッダーが設定されること", async () => {
      const originalEnv = process.env.NODE_ENV;
      process.env.NODE_ENV = "production";

      jest.resetModules();
      const { default: prodConfig } = await import("../../next.config");

      if (!prodConfig.headers) {
        throw new Error("headers function is not defined");
      }

      const headers = await prodConfig.headers();
      const hsts = headers[0].headers.find((h) => h.key === "Strict-Transport-Security");

      expect(hsts).toBeDefined();
      expect(hsts?.value).toBe("max-age=31536000; includeSubDomains");

      process.env.NODE_ENV = originalEnv;
    });

    it("本番環境では unsafe-eval が含まれないこと", async () => {
      const originalEnv = process.env.NODE_ENV;
      process.env.NODE_ENV = "production";

      jest.resetModules();
      const { default: prodConfig } = await import("../../next.config");

      if (!prodConfig.headers) {
        throw new Error("headers function is not defined");
      }

      const headers = await prodConfig.headers();
      const csp = headers[0].headers.find((h) => h.key === "Content-Security-Policy");

      expect(csp?.value).not.toContain("'unsafe-eval'");
      expect(csp?.value).not.toContain("ws:");
      expect(csp?.value).not.toContain("wss:");

      process.env.NODE_ENV = originalEnv;
    });
  });
});

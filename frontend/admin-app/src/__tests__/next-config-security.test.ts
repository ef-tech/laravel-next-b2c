import nextConfig from "../../next.config";

describe("Admin App next.config.ts セキュリティヘッダー", () => {
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

    it("X-Frame-Options ヘッダーが DENY に設定されていること（User App より厳格）", async () => {
      if (!nextConfig.headers) {
        throw new Error("headers function is not defined");
      }

      const headers = await nextConfig.headers();
      const xFrameOptions = headers[0].headers.find((h) => h.key === "X-Frame-Options");

      expect(xFrameOptions).toBeDefined();
      expect(xFrameOptions?.value).toBe("DENY");
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

    it("Referrer-Policy ヘッダーが no-referrer に設定されていること（User App より厳格）", async () => {
      if (!nextConfig.headers) {
        throw new Error("headers function is not defined");
      }

      const headers = await nextConfig.headers();
      const referrerPolicy = headers[0].headers.find((h) => h.key === "Referrer-Policy");

      expect(referrerPolicy).toBeDefined();
      expect(referrerPolicy?.value).toBe("no-referrer");
    });

    it("Content-Security-Policy ヘッダーが設定されていること", async () => {
      if (!nextConfig.headers) {
        throw new Error("headers function is not defined");
      }

      const headers = await nextConfig.headers();
      const csp = headers[0].headers.find((h) => h.key === "Content-Security-Policy");

      expect(csp).toBeDefined();
      expect(csp?.value).toContain("default-src 'self'");
      expect(csp?.value).toContain("script-src 'self'");
      expect(csp?.value).toContain("style-src");
      expect(csp?.value).toContain("img-src");
      expect(csp?.value).toContain("connect-src");
      expect(csp?.value).toContain("font-src");
      expect(csp?.value).toContain("object-src 'none'");
      expect(csp?.value).toContain("frame-ancestors 'none'");

      // Admin App では unsafe-eval を許可しない（開発環境でも）
      expect(csp?.value).not.toContain("'unsafe-eval'");
    });

    it("Permissions-Policy ヘッダーが設定されていること（全て禁止）", async () => {
      if (!nextConfig.headers) {
        throw new Error("headers function is not defined");
      }

      const headers = await nextConfig.headers();
      const permissionsPolicy = headers[0].headers.find((h) => h.key === "Permissions-Policy");

      expect(permissionsPolicy).toBeDefined();
      expect(permissionsPolicy?.value).toContain("geolocation=()");
      expect(permissionsPolicy?.value).toContain("camera=()");
      expect(permissionsPolicy?.value).toContain("microphone=()");
      expect(permissionsPolicy?.value).toContain("payment=()");
      expect(permissionsPolicy?.value).toContain("usb=()");
      expect(permissionsPolicy?.value).toContain("bluetooth=()");
    });

    it("X-Permitted-Cross-Domain-Policies ヘッダーが設定されていること", async () => {
      if (!nextConfig.headers) {
        throw new Error("headers function is not defined");
      }

      const headers = await nextConfig.headers();
      const xPermittedCrossDomainPolicies = headers[0].headers.find(
        (h) => h.key === "X-Permitted-Cross-Domain-Policies",
      );

      expect(xPermittedCrossDomainPolicies).toBeDefined();
      expect(xPermittedCrossDomainPolicies?.value).toBe("none");
    });

    it("Cross-Origin-Embedder-Policy ヘッダーが設定されていること", async () => {
      if (!nextConfig.headers) {
        throw new Error("headers function is not defined");
      }

      const headers = await nextConfig.headers();
      const coep = headers[0].headers.find((h) => h.key === "Cross-Origin-Embedder-Policy");

      expect(coep).toBeDefined();
      expect(coep?.value).toBe("require-corp");
    });

    it("Cross-Origin-Opener-Policy ヘッダーが設定されていること", async () => {
      if (!nextConfig.headers) {
        throw new Error("headers function is not defined");
      }

      const headers = await nextConfig.headers();
      const coop = headers[0].headers.find((h) => h.key === "Cross-Origin-Opener-Policy");

      expect(coop).toBeDefined();
      expect(coop?.value).toBe("same-origin");
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

    it("開発環境でも unsafe-eval が含まれないこと（Admin App 固有）", async () => {
      const originalEnv = process.env.NODE_ENV;
      process.env.NODE_ENV = "development";

      jest.resetModules();
      const { default: devConfig } = await import("../../next.config");

      if (!devConfig.headers) {
        throw new Error("headers function is not defined");
      }

      const headers = await devConfig.headers();
      const csp = headers[0].headers.find((h) => h.key === "Content-Security-Policy");

      // Admin App は開発環境でも厳格
      expect(csp?.value).not.toContain("'unsafe-eval'");
      expect(csp?.value).not.toContain("ws:");
      expect(csp?.value).not.toContain("wss:");

      process.env.NODE_ENV = originalEnv;
    });
  });
});

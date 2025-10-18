import {
  getSecurityConfig,
  getAdminSecurityConfig,
  buildCSPString,
  buildPermissionsPolicyString,
  generateNonce,
  type CSPConfig,
  type PermissionsPolicyConfig,
} from "../../../security-config";

describe("security-config", () => {
  describe("getSecurityConfig (User App用)", () => {
    it("開発環境では緩和されたセキュリティ設定を返却すること", () => {
      const config = getSecurityConfig(true);

      expect(config.xFrameOptions).toBe("SAMEORIGIN");
      expect(config.xContentTypeOptions).toBe("nosniff");
      expect(config.referrerPolicy).toBe("strict-origin-when-cross-origin");

      // 開発環境では unsafe-eval を含む
      expect(config.csp.scriptSrc).toContain("'self'");
      expect(config.csp.scriptSrc).toContain("'unsafe-eval'");

      // 開発環境では ws: wss: を含む
      expect(config.csp.connectSrc).toContain("'self'");
      expect(config.csp.connectSrc).toContain("ws:");
      expect(config.csp.connectSrc).toContain("wss:");
    });

    it("本番環境では厳格なセキュリティ設定を返却すること", () => {
      const config = getSecurityConfig(false);

      expect(config.xFrameOptions).toBe("SAMEORIGIN");
      expect(config.xContentTypeOptions).toBe("nosniff");
      expect(config.referrerPolicy).toBe("strict-origin-when-cross-origin");

      // 本番環境では unsafe-eval を含まない
      expect(config.csp.scriptSrc).toContain("'self'");
      expect(config.csp.scriptSrc).not.toContain("'unsafe-eval'");

      // 本番環境では ws: wss: を含まない
      expect(config.csp.connectSrc).toContain("'self'");
      expect(config.csp.connectSrc).not.toContain("ws:");
      expect(config.csp.connectSrc).not.toContain("wss:");
    });

    it("HSTS 設定が本番環境のみ含まれること", () => {
      const devConfig = getSecurityConfig(true);
      const prodConfig = getSecurityConfig(false);

      expect(devConfig.hsts).toBeUndefined();
      expect(prodConfig.hsts).toBeDefined();
      expect(prodConfig.hsts?.maxAge).toBe(31536000);
      expect(prodConfig.hsts?.includeSubDomains).toBe(true);
    });
  });

  describe("getAdminSecurityConfig (Admin App用)", () => {
    it("開発環境でも厳格なセキュリティ設定を返却すること", () => {
      const config = getAdminSecurityConfig(true);

      expect(config.xFrameOptions).toBe("DENY");
      expect(config.xContentTypeOptions).toBe("nosniff");
      expect(config.referrerPolicy).toBe("no-referrer");

      // Admin App は開発環境でも unsafe-eval を含まない
      expect(config.csp.scriptSrc).toContain("'self'");
      expect(config.csp.scriptSrc).not.toContain("'unsafe-eval'");

      // Admin App は開発環境でも ws: wss: を含まない
      expect(config.csp.connectSrc).toContain("'self'");
      expect(config.csp.connectSrc).not.toContain("ws:");
      expect(config.csp.connectSrc).not.toContain("wss:");
    });

    it("本番環境では厳格なセキュリティ設定を返却すること", () => {
      const config = getAdminSecurityConfig(false);

      expect(config.xFrameOptions).toBe("DENY");
      expect(config.xContentTypeOptions).toBe("nosniff");
      expect(config.referrerPolicy).toBe("no-referrer");

      // 本番環境でも同様に厳格
      expect(config.csp.scriptSrc).toContain("'self'");
      expect(config.csp.scriptSrc).not.toContain("'unsafe-eval'");

      expect(config.csp.connectSrc).toContain("'self'");
      expect(config.csp.connectSrc).not.toContain("ws:");
      expect(config.csp.connectSrc).not.toContain("wss:");
    });

    it("Permissions-Policy がすべて禁止されていること", () => {
      const config = getAdminSecurityConfig(true);

      expect(config.permissionsPolicy.geolocation).toBe("");
      expect(config.permissionsPolicy.camera).toBe("");
      expect(config.permissionsPolicy.microphone).toBe("");
      expect(config.permissionsPolicy.payment).toBe("");
      expect(config.permissionsPolicy.usb).toBe("");
      expect(config.permissionsPolicy.bluetooth).toBe("");
    });

    it("HSTS 設定が本番環境のみ含まれること", () => {
      const devConfig = getAdminSecurityConfig(true);
      const prodConfig = getAdminSecurityConfig(false);

      expect(devConfig.hsts).toBeUndefined();
      expect(prodConfig.hsts).toBeDefined();
      expect(prodConfig.hsts?.maxAge).toBe(31536000);
      expect(prodConfig.hsts?.includeSubDomains).toBe(true);
    });
  });

  describe("buildCSPString", () => {
    it("CSP ポリシー文字列を正しく構築すること", () => {
      const config: CSPConfig = {
        defaultSrc: ["'self'"],
        scriptSrc: ["'self'", "'unsafe-eval'"],
        styleSrc: ["'self'", "'unsafe-inline'"],
        imgSrc: ["'self'", "data:", "https:"],
        connectSrc: ["'self'", "ws:", "wss:"],
        fontSrc: ["'self'", "data:"],
        objectSrc: ["'none'"],
        frameAncestors: ["'none'"],
        upgradeInsecureRequests: false,
      };

      const cspString = buildCSPString(config);

      expect(cspString).toContain("default-src 'self'");
      expect(cspString).toContain("script-src 'self' 'unsafe-eval'");
      expect(cspString).toContain("style-src 'self' 'unsafe-inline'");
      expect(cspString).toContain("img-src 'self' data: https:");
      expect(cspString).toContain("connect-src 'self' ws: wss:");
      expect(cspString).toContain("font-src 'self' data:");
      expect(cspString).toContain("object-src 'none'");
      expect(cspString).toContain("frame-ancestors 'none'");

      // セミコロン区切りであることを確認
      expect(cspString.split(";").length).toBeGreaterThan(1);
    });

    it("upgradeInsecureRequests が true の場合、ディレクティブが含まれること", () => {
      const config: CSPConfig = {
        defaultSrc: ["'self'"],
        scriptSrc: ["'self'"],
        styleSrc: ["'self'"],
        imgSrc: ["'self'"],
        connectSrc: ["'self'"],
        fontSrc: ["'self'"],
        objectSrc: ["'none'"],
        frameAncestors: ["'none'"],
        upgradeInsecureRequests: true,
      };

      const cspString = buildCSPString(config);

      expect(cspString).toContain("upgrade-insecure-requests");
    });

    it("reportUri が設定されている場合、report-uri ディレクティブが含まれること", () => {
      const config: CSPConfig = {
        defaultSrc: ["'self'"],
        scriptSrc: ["'self'"],
        styleSrc: ["'self'"],
        imgSrc: ["'self'"],
        connectSrc: ["'self'"],
        fontSrc: ["'self'"],
        objectSrc: ["'none'"],
        frameAncestors: ["'none'"],
        upgradeInsecureRequests: false,
        reportUri: "/api/csp-report",
      };

      const cspString = buildCSPString(config);

      expect(cspString).toContain("report-uri /api/csp-report");
    });
  });

  describe("buildPermissionsPolicyString", () => {
    it("Permissions-Policy 文字列を正しく構築すること", () => {
      const config: PermissionsPolicyConfig = {
        geolocation: "self",
        camera: "",
        microphone: "",
        payment: "self",
      };

      const policyString = buildPermissionsPolicyString(config);

      expect(policyString).toContain("geolocation=(self)");
      expect(policyString).toContain("camera=()");
      expect(policyString).toContain("microphone=()");
      expect(policyString).toContain("payment=(self)");

      // カンマ区切りであることを確認
      expect(policyString.split(",").length).toBe(4);
    });

    it("空の設定オブジェクトの場合、空文字列を返却すること", () => {
      const config: PermissionsPolicyConfig = {};

      const policyString = buildPermissionsPolicyString(config);

      expect(policyString).toBe("");
    });
  });

  describe("generateNonce", () => {
    it("ランダムな nonce 値を生成すること", () => {
      const nonce1 = generateNonce();
      const nonce2 = generateNonce();

      expect(nonce1).not.toBe(nonce2);
      expect(typeof nonce1).toBe("string");
      expect(typeof nonce2).toBe("string");
    });

    it("Base64 形式であることを検証すること", () => {
      const nonce = generateNonce();

      // Base64 形式の正規表現
      const base64Regex = /^[A-Za-z0-9+/]+=*$/;
      expect(base64Regex.test(nonce)).toBe(true);
    });

    it("適切な長さの nonce を生成すること", () => {
      const nonce = generateNonce();

      // 16バイトのランダムデータを Base64 エンコードすると約22-24文字
      expect(nonce.length).toBeGreaterThanOrEqual(16);
      expect(nonce.length).toBeLessThanOrEqual(32);
    });
  });
});

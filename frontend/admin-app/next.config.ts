import type { NextConfig } from "next";
import path from "path";

// Load security config with error handling for test environment
let securityConfig: any;
let buildCSPString: any;
let buildPermissionsPolicyString: any;

try {
  // Try to load actual security config
  const securityConfigModule = require("../security-config.js");
  const isDev = process.env.NODE_ENV === "development";
  securityConfig = securityConfigModule.getAdminSecurityConfig(isDev);
  buildCSPString = securityConfigModule.buildCSPString;
  buildPermissionsPolicyString = securityConfigModule.buildPermissionsPolicyString;
} catch (error) {
  // Fallback for environments where security-config.js is not available
  console.warn("Failed to load security-config.js, using fallback config");
  securityConfig = {
    xFrameOptions: "DENY" as const,
    xContentTypeOptions: "nosniff" as const,
    referrerPolicy: "no-referrer",
    csp: {
      defaultSrc: ["'self'"],
      scriptSrc: ["'self'"],
      styleSrc: ["'self'"],
      imgSrc: ["'self'"],
      connectSrc: ["'self'"],
      fontSrc: ["'self'"],
      objectSrc: ["'none'"],
      frameAncestors: ["'none'"],
      upgradeInsecureRequests: false,
    },
    permissionsPolicy: {},
  };
  buildCSPString = (config: any) => "default-src 'self'";
  buildPermissionsPolicyString = (config: any) => "";
}

const nextConfig: NextConfig = {
  /* config options here */
  output: "standalone",
  // Monorepo環境でのNext.jsビルド警告を解消するために設定
  // "Warning: Next.js inferred your workspace root, but it may not be correct..."を回避
  outputFileTracingRoot: path.join(__dirname, "../../"),

  // セキュリティヘッダー設定（Admin App 用 - User App より厳格）
  async headers() {
    const headers: Array<{ key: string; value: string }> = [
      {
        key: "X-Frame-Options",
        value: securityConfig.xFrameOptions,
      },
      {
        key: "X-Content-Type-Options",
        value: securityConfig.xContentTypeOptions,
      },
      {
        key: "Referrer-Policy",
        value: securityConfig.referrerPolicy,
      },
      {
        key: "Content-Security-Policy",
        value: buildCSPString(securityConfig.csp),
      },
      {
        key: "Permissions-Policy",
        value: buildPermissionsPolicyString(securityConfig.permissionsPolicy),
      },
      {
        key: "X-Permitted-Cross-Domain-Policies",
        value: "none",
      },
      {
        key: "Cross-Origin-Embedder-Policy",
        value: "require-corp",
      },
      {
        key: "Cross-Origin-Opener-Policy",
        value: "same-origin",
      },
    ];

    // 本番環境のみ HSTS を追加
    if (securityConfig.hsts) {
      headers.push({
        key: "Strict-Transport-Security",
        value: `max-age=${securityConfig.hsts.maxAge}; includeSubDomains`,
      });
    }

    return [
      {
        source: "/:path*",
        headers,
      },
    ];
  },
};

export default nextConfig;

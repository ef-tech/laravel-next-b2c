import type { NextConfig } from "next";
import path from "path";
import {
  getSecurityConfig,
  buildCSPString,
  buildPermissionsPolicyString,
} from "../security-config";

const isDev = process.env.NODE_ENV === "development";
const securityConfig = getSecurityConfig(isDev);

const nextConfig: NextConfig = {
  /* config options here */
  output: "standalone",
  // Monorepo環境でのNext.jsビルド警告を解消するために設定
  // "Warning: Next.js inferred your workspace root, but it may not be correct..."を回避
  outputFileTracingRoot: path.join(__dirname, "../../"),

  // セキュリティヘッダー設定
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

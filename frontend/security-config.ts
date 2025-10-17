/**
 * セキュリティ設定共通モジュール
 *
 * User App と Admin App で共通のセキュリティヘッダー設定ロジックを提供
 */

/**
 * CSP (Content Security Policy) 設定
 */
export interface CSPConfig {
  defaultSrc: string[];
  scriptSrc: string[];
  styleSrc: string[];
  imgSrc: string[];
  connectSrc: string[];
  fontSrc: string[];
  objectSrc: string[];
  frameAncestors: string[];
  upgradeInsecureRequests: boolean;
  reportUri?: string;
}

/**
 * Permissions-Policy 設定
 */
export interface PermissionsPolicyConfig {
  geolocation?: string;
  camera?: string;
  microphone?: string;
  payment?: string;
  usb?: string;
  bluetooth?: string;
}

/**
 * セキュリティ設定全体
 */
export interface SecurityConfig {
  xFrameOptions: 'DENY' | 'SAMEORIGIN';
  xContentTypeOptions: 'nosniff';
  referrerPolicy: string;
  csp: CSPConfig;
  permissionsPolicy: PermissionsPolicyConfig;
  hsts?: {
    maxAge: number;
    includeSubDomains: boolean;
  };
}

/**
 * 環境に応じたセキュリティ設定を取得（User App 用）
 *
 * @param isDev - 開発環境フラグ
 * @returns セキュリティ設定オブジェクト
 */
export function getSecurityConfig(isDev: boolean): SecurityConfig {
  const config: SecurityConfig = {
    xFrameOptions: 'SAMEORIGIN',
    xContentTypeOptions: 'nosniff',
    referrerPolicy: 'strict-origin-when-cross-origin',
    csp: {
      defaultSrc: ["'self'"],
      scriptSrc: isDev ? ["'self'", "'unsafe-eval'"] : ["'self'"],
      styleSrc: ["'self'", "'unsafe-inline'"],
      imgSrc: ["'self'", 'data:', 'https:'],
      connectSrc: isDev
        ? ["'self'", 'ws:', 'wss:', 'http://localhost:13000']
        : ["'self'"],
      fontSrc: ["'self'", 'data:'],
      objectSrc: ["'none'"],
      frameAncestors: ["'none'"],
      upgradeInsecureRequests: !isDev,
      reportUri: '/api/csp-report',
    },
    permissionsPolicy: {
      geolocation: 'self',
      camera: '',
      microphone: '',
      payment: 'self',
    },
  };

  // 本番環境のみ HSTS を設定
  if (!isDev) {
    config.hsts = {
      maxAge: 31536000, // 1 year
      includeSubDomains: true,
    };
  }

  return config;
}

/**
 * Admin App 用の厳格なセキュリティ設定を取得
 *
 * Admin App は User App よりも厳格な設定を適用:
 * - X-Frame-Options: DENY（User App は SAMEORIGIN）
 * - Referrer-Policy: no-referrer（User App は strict-origin-when-cross-origin）
 * - CSP: 開発環境でも unsafe-eval を許可しない
 * - Permissions-Policy: すべての API を禁止
 *
 * @param isDev - 開発環境フラグ
 * @returns Admin App 用セキュリティ設定オブジェクト
 */
export function getAdminSecurityConfig(isDev: boolean): SecurityConfig {
  const config: SecurityConfig = {
    xFrameOptions: 'DENY',
    xContentTypeOptions: 'nosniff',
    referrerPolicy: 'no-referrer',
    csp: {
      defaultSrc: ["'self'"],
      // Admin App は開発環境でも unsafe-eval を許可しない
      scriptSrc: ["'self'"],
      styleSrc: ["'self'", "'unsafe-inline'"],
      imgSrc: ["'self'", 'data:', 'https:'],
      // Admin App は開発環境でも ws/wss を許可しない
      connectSrc: ["'self'"],
      fontSrc: ["'self'", 'data:'],
      objectSrc: ["'none'"],
      frameAncestors: ["'none'"],
      upgradeInsecureRequests: !isDev,
      reportUri: '/api/csp-report',
    },
    permissionsPolicy: {
      // Admin App はすべてのブラウザ API を禁止
      geolocation: '',
      camera: '',
      microphone: '',
      payment: '',
      usb: '',
      bluetooth: '',
    },
  };

  // 本番環境のみ HSTS を設定
  if (!isDev) {
    config.hsts = {
      maxAge: 31536000, // 1 year
      includeSubDomains: true,
    };
  }

  return config;
}

/**
 * CSP ポリシー文字列を構築
 *
 * @param config - CSP 設定オブジェクト
 * @returns CSP ポリシー文字列
 */
export function buildCSPString(config: CSPConfig): string {
  const directives: string[] = [];

  // 各ディレクティブを構築
  if (config.defaultSrc.length > 0) {
    directives.push(`default-src ${config.defaultSrc.join(' ')}`);
  }
  if (config.scriptSrc.length > 0) {
    directives.push(`script-src ${config.scriptSrc.join(' ')}`);
  }
  if (config.styleSrc.length > 0) {
    directives.push(`style-src ${config.styleSrc.join(' ')}`);
  }
  if (config.imgSrc.length > 0) {
    directives.push(`img-src ${config.imgSrc.join(' ')}`);
  }
  if (config.connectSrc.length > 0) {
    directives.push(`connect-src ${config.connectSrc.join(' ')}`);
  }
  if (config.fontSrc.length > 0) {
    directives.push(`font-src ${config.fontSrc.join(' ')}`);
  }
  if (config.objectSrc.length > 0) {
    directives.push(`object-src ${config.objectSrc.join(' ')}`);
  }
  if (config.frameAncestors.length > 0) {
    directives.push(`frame-ancestors ${config.frameAncestors.join(' ')}`);
  }

  // upgrade-insecure-requests ディレクティブ
  if (config.upgradeInsecureRequests) {
    directives.push('upgrade-insecure-requests');
  }

  // report-uri ディレクティブ
  if (config.reportUri) {
    directives.push(`report-uri ${config.reportUri}`);
  }

  return directives.join('; ');
}

/**
 * Permissions-Policy 文字列を構築
 *
 * @param config - Permissions-Policy 設定オブジェクト
 * @returns Permissions-Policy 文字列
 */
export function buildPermissionsPolicyString(
  config: PermissionsPolicyConfig
): string {
  const policies: string[] = [];

  Object.entries(config).forEach(([key, value]) => {
    if (value === undefined) return;

    const formattedValue = value === '' ? '()' : `(${value})`;
    policies.push(`${key}=${formattedValue}`);
  });

  return policies.join(', ');
}

/**
 * ランダムな nonce 値を生成（将来の CSP nonce ベース認証用）
 *
 * @returns Base64 エンコードされた nonce 文字列
 */
export function generateNonce(): string {
  // Node.js 環境では crypto モジュールを使用
  if (typeof window === 'undefined') {
    const crypto = require('crypto');
    return crypto.randomBytes(16).toString('base64');
  }

  // ブラウザ環境では Web Crypto API を使用
  const array = new Uint8Array(16);
  crypto.getRandomValues(array);

  // Uint8Array を Base64 に変換
  return btoa(String.fromCharCode(...array));
}

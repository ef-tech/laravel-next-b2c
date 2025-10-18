import { test, expect } from '@playwright/test';

/**
 * Security Headers E2E Tests
 *
 * Tests security headers implementation across all services:
 * - Laravel API: Backend security headers
 * - User App: Frontend security headers
 * - Admin App: Strict frontend security headers
 *
 * These tests verify OWASP-compliant security headers are properly set.
 */

const LARAVEL_API_URL = process.env.E2E_API_URL ?? 'http://localhost:13000';
const USER_APP_URL = process.env.E2E_USER_URL ?? 'http://localhost:13001';
const ADMIN_APP_URL = process.env.E2E_ADMIN_URL ?? 'http://localhost:13002';

test.describe('Laravel API Security Headers', () => {
  test('should return X-Frame-Options header', async ({ request }) => {
    // Act: Make request to Laravel API health endpoint
    const response = await request.get(`${LARAVEL_API_URL}/api/health`);

    // Assert: X-Frame-Options header is present
    const xFrameOptions = response.headers()['x-frame-options'];
    expect(xFrameOptions).toBeDefined();
    expect(xFrameOptions).toMatch(/^(DENY|SAMEORIGIN)$/);
  });

  test('should return X-Content-Type-Options header', async ({ request }) => {
    // Act: Make request to Laravel API
    const response = await request.get(`${LARAVEL_API_URL}/api/health`);

    // Assert: X-Content-Type-Options header is present
    const xContentTypeOptions = response.headers()['x-content-type-options'];
    expect(xContentTypeOptions).toBeDefined();
    expect(xContentTypeOptions).toBe('nosniff');
  });

  test('should return Referrer-Policy header', async ({ request }) => {
    // Act: Make request to Laravel API
    const response = await request.get(`${LARAVEL_API_URL}/api/health`);

    // Assert: Referrer-Policy header is present
    const referrerPolicy = response.headers()['referrer-policy'];
    expect(referrerPolicy).toBeDefined();
    expect(referrerPolicy).toMatch(/^(strict-origin-when-cross-origin|no-referrer)$/);
  });

  test('should return Content-Security-Policy or Content-Security-Policy-Report-Only header', async ({
    request,
  }) => {
    // Act: Make request to Laravel API
    const response = await request.get(`${LARAVEL_API_URL}/api/health`);

    // Assert: CSP header is present (either enforce or report-only mode)
    const csp = response.headers()['content-security-policy'];
    const cspReportOnly = response.headers()['content-security-policy-report-only'];

    // At least one CSP header should be present
    expect(csp || cspReportOnly).toBeDefined();

    // Verify CSP contains required directives
    const cspValue = csp || cspReportOnly || '';
    expect(cspValue).toContain('default-src');
  });

  test('should not return HSTS header in HTTP environment', async ({ request }) => {
    // Arrange: This test assumes development environment uses HTTP
    // In production HTTPS environment, HSTS should be present

    // Act: Make request to Laravel API
    const response = await request.get(`${LARAVEL_API_URL}/api/health`);

    // Assert: HSTS header should not be present in HTTP environment
    const hsts = response.headers()['strict-transport-security'];
    expect(hsts).toBeUndefined();
  });

  test('should return all basic security headers', async ({ request }) => {
    // Act: Make request to Laravel API
    const response = await request.get(`${LARAVEL_API_URL}/api/health`);

    // Assert: All basic security headers are present
    const headers = response.headers();
    expect(headers['x-frame-options']).toBeDefined();
    expect(headers['x-content-type-options']).toBeDefined();
    expect(headers['referrer-policy']).toBeDefined();
    expect(headers['content-security-policy'] || headers['content-security-policy-report-only']).toBeDefined();
  });
});

test.describe('User App Security Headers', () => {
  test('should return X-Frame-Options: SAMEORIGIN', async ({ page }) => {
    // Arrange: Listen for response
    let responseHeaders: Record<string, string> = {};

    page.on('response', async (response) => {
      if (response.url() === USER_APP_URL + '/') {
        responseHeaders = response.headers();
      }
    });

    // Act: Navigate to User App homepage
    await page.goto(USER_APP_URL);

    // Assert: X-Frame-Options is SAMEORIGIN
    expect(responseHeaders['x-frame-options']).toBe('SAMEORIGIN');
  });

  test('should return Content-Security-Policy header', async ({ page }) => {
    // Arrange: Listen for response
    let cspHeader = '';

    page.on('response', async (response) => {
      if (response.url() === USER_APP_URL + '/') {
        cspHeader = response.headers()['content-security-policy'] || '';
      }
    });

    // Act: Navigate to User App homepage
    await page.goto(USER_APP_URL);

    // Assert: CSP header is present and contains script-src directive
    expect(cspHeader).toBeTruthy();
    expect(cspHeader).toContain('script-src');
  });

  test('should return Permissions-Policy header', async ({ page }) => {
    // Arrange: Listen for response
    let permissionsPolicy = '';

    page.on('response', async (response) => {
      if (response.url() === USER_APP_URL + '/') {
        permissionsPolicy = response.headers()['permissions-policy'] || '';
      }
    });

    // Act: Navigate to User App homepage
    await page.goto(USER_APP_URL);

    // Assert: Permissions-Policy header is present
    expect(permissionsPolicy).toBeTruthy();
    expect(permissionsPolicy).toContain('geolocation');
  });
});

test.describe('Admin App Security Headers', () => {
  test('should return X-Frame-Options: DENY', async ({ page }) => {
    // Arrange: Listen for response
    let responseHeaders: Record<string, string> = {};

    page.on('response', async (response) => {
      if (response.url() === ADMIN_APP_URL + '/') {
        responseHeaders = response.headers();
      }
    });

    // Act: Navigate to Admin App homepage
    await page.goto(ADMIN_APP_URL);

    // Assert: X-Frame-Options is DENY (stricter than User App)
    expect(responseHeaders['x-frame-options']).toBe('DENY');
  });

  test('should return stricter CSP than User App', async ({ page }) => {
    // Arrange: Listen for response
    let cspHeader = '';

    page.on('response', async (response) => {
      if (response.url() === ADMIN_APP_URL + '/') {
        cspHeader = response.headers()['content-security-policy'] || '';
      }
    });

    // Act: Navigate to Admin App homepage
    await page.goto(ADMIN_APP_URL);

    // Assert: CSP header is present
    expect(cspHeader).toBeTruthy();

    // Assert: CSP should NOT contain unsafe-inline or unsafe-eval in production
    // In development, these may be present for Next.js hot reload
    if (process.env.NODE_ENV === 'production') {
      expect(cspHeader).not.toContain('unsafe-inline');
      expect(cspHeader).not.toContain('unsafe-eval');
    }
  });

  test('should return Referrer-Policy: no-referrer', async ({ page }) => {
    // Arrange: Listen for response
    let responseHeaders: Record<string, string> = {};

    page.on('response', async (response) => {
      if (response.url() === ADMIN_APP_URL + '/') {
        responseHeaders = response.headers();
      }
    });

    // Act: Navigate to Admin App homepage
    await page.goto(ADMIN_APP_URL);

    // Assert: Referrer-Policy is no-referrer (stricter than User App)
    expect(responseHeaders['referrer-policy']).toBe('no-referrer');
  });

  test('should return additional security headers', async ({ page }) => {
    // Arrange: Listen for response
    let responseHeaders: Record<string, string> = {};

    page.on('response', async (response) => {
      if (response.url() === ADMIN_APP_URL + '/') {
        responseHeaders = response.headers();
      }
    });

    // Act: Navigate to Admin App homepage
    await page.goto(ADMIN_APP_URL);

    // Assert: Additional strict security headers are present
    expect(responseHeaders['x-permitted-cross-domain-policies']).toBe('none');
    expect(responseHeaders['cross-origin-embedder-policy']).toBe('require-corp');
    expect(responseHeaders['cross-origin-opener-policy']).toBe('same-origin');
  });
});

test.describe('CSP Violation Detection', () => {
  test('should not trigger CSP violations on normal page load', async ({ page }) => {
    // Arrange: Collect CSP violations
    const cspViolations: string[] = [];

    page.on('console', (msg) => {
      if (msg.type() === 'error' && msg.text().includes('Content Security Policy')) {
        cspViolations.push(msg.text());
      }
    });

    // Act: Navigate to User App homepage
    await page.goto(USER_APP_URL);
    await page.waitForLoadState('networkidle');

    // Assert: No CSP violations occurred
    expect(cspViolations).toHaveLength(0);
  });

  test('should not trigger CSP violations on Admin App', async ({ page }) => {
    // Arrange: Collect CSP violations
    const cspViolations: string[] = [];

    page.on('console', (msg) => {
      if (msg.type() === 'error' && msg.text().includes('Content Security Policy')) {
        cspViolations.push(msg.text());
      }
    });

    // Act: Navigate to Admin App homepage
    await page.goto(ADMIN_APP_URL);
    await page.waitForLoadState('networkidle');

    // Assert: No CSP violations occurred
    expect(cspViolations).toHaveLength(0);
  });
});

test.describe('CORS Integration', () => {
  test('should allow API requests from User App origin', async ({ request }) => {
    // Arrange: Set Origin header to User App URL
    const userAppOrigin = new URL(USER_APP_URL).origin;

    // Act: Make request with User App origin
    const response = await request.get(`${LARAVEL_API_URL}/api/health`, {
      headers: {
        Origin: userAppOrigin,
      },
    });

    // Assert: Request should succeed
    expect(response.ok()).toBeTruthy();
    expect(response.status()).toBe(200);

    // Assert: CORS headers should be present
    const headers = response.headers();
    expect(headers['access-control-allow-origin']).toBe(userAppOrigin);
    expect(headers['access-control-allow-credentials']).toBe('true');
  });

  test('should block API requests from unauthorized origin', async ({ request }) => {
    // Arrange: Set Origin header to unauthorized domain
    const unauthorizedOrigin = 'https://malicious-site.com';

    // Act: Make request with unauthorized origin
    const response = await request.get(`${LARAVEL_API_URL}/api/health`, {
      headers: {
        Origin: unauthorizedOrigin,
      },
    });

    // Assert: Response should not include CORS headers for unauthorized origin
    const headers = response.headers();

    // CORS should either:
    // 1. Not include Access-Control-Allow-Origin header
    // 2. Not match the unauthorized origin
    if (headers['access-control-allow-origin']) {
      expect(headers['access-control-allow-origin']).not.toBe(unauthorizedOrigin);
    }
  });
});

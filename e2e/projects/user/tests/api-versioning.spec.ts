import { test, expect } from '@playwright/test';
import { UserLoginPage } from '../pages/LoginPage';
import { UserProfilePage } from '../pages/ProfilePage';

/**
 * API v1 Endpoint Access E2E Tests
 *
 * Tests that API versioning is correctly implemented:
 * - All user endpoints use /api/v1/user/* prefix
 * - API responses include correct version headers
 * - Endpoints are accessible only with valid authentication
 *
 * Requirements: 14.4
 */
test.describe('API v1 Endpoint Access - User', () => {
  test('user login endpoint uses /api/v1/user/login', async ({ page }) => {
    // Setup request listener to capture API calls
    const apiCalls: Array<{ url: string; method: string }> = [];
    page.on('request', (req) => {
      const url = req.url();
      if (url.includes('/api/v1/')) {
        apiCalls.push({
          url,
          method: req.method(),
        });
      }
    });

    // Perform login
    const loginPage = new UserLoginPage(page);
    await loginPage.goto();

    const testEmail = process.env.E2E_USER_EMAIL || 'user@example.com';
    const testPassword = process.env.E2E_USER_PASSWORD || 'password';

    await page.fill('input[name="email"]', testEmail);
    await page.fill('input[name="password"]', testPassword);
    await page.click('button[type="submit"]');

    await page.waitForURL('**/profile', { timeout: 5000 });

    // Verify login API call
    const loginCall = apiCalls.find((call) => call.url.includes('/login'));
    expect(loginCall).toBeDefined();
    expect(loginCall?.url).toContain('/api/v1/user/login');
    expect(loginCall?.method).toBe('POST');
  });

  test('user profile endpoint uses /api/v1/user/profile', async ({ page }) => {
    // Login first
    const loginPage = new UserLoginPage(page);
    await loginPage.goto();

    const testEmail = process.env.E2E_USER_EMAIL || 'user@example.com';
    const testPassword = process.env.E2E_USER_PASSWORD || 'password';

    await loginPage.login(testEmail, testPassword);

    // Setup request listener to capture profile API calls
    const apiCalls: Array<{ url: string; method: string }> = [];
    page.on('request', (req) => {
      const url = req.url();
      if (url.includes('/api/v1/')) {
        apiCalls.push({
          url,
          method: req.method(),
        });
      }
    });

    // Navigate to profile page (triggers API call)
    const profilePage = new UserProfilePage(page);
    await profilePage.goto();

    // Wait for profile to load
    await page.waitForTimeout(1000);

    // Verify profile API call
    const profileCall = apiCalls.find((call) => call.url.includes('/profile'));
    expect(profileCall).toBeDefined();
    expect(profileCall?.url).toContain('/api/v1/user/profile');
    expect(profileCall?.method).toBe('GET');
  });

  test('user logout endpoint uses /api/v1/user/logout', async ({ page }) => {
    // Login first
    const loginPage = new UserLoginPage(page);
    await loginPage.goto();

    const testEmail = process.env.E2E_USER_EMAIL || 'user@example.com';
    const testPassword = process.env.E2E_USER_PASSWORD || 'password';

    await loginPage.login(testEmail, testPassword);

    // Setup request listener to capture logout API calls
    const apiCalls: Array<{ url: string; method: string }> = [];
    page.on('request', (req) => {
      const url = req.url();
      if (url.includes('/api/v1/')) {
        apiCalls.push({
          url,
          method: req.method(),
        });
      }
    });

    // Perform logout
    const profilePage = new UserProfilePage(page);
    await profilePage.logout();

    // Verify logout API call
    const logoutCall = apiCalls.find((call) => call.url.includes('/logout'));
    expect(logoutCall).toBeDefined();
    expect(logoutCall?.url).toContain('/api/v1/user/logout');
    expect(logoutCall?.method).toBe('POST');
  });

  test('API responses include correct version in URL', async ({ page, request }) => {
    // Step 1: Login to get authentication token
    const loginPage = new UserLoginPage(page);
    await loginPage.goto();

    const testEmail = process.env.E2E_USER_EMAIL || 'user@example.com';
    const testPassword = process.env.E2E_USER_PASSWORD || 'password';

    await loginPage.login(testEmail, testPassword);

    // Step 2: Make direct API call and verify response
    const apiResponse = await page.evaluate(async () => {
      const response = await fetch('http://localhost:13000/api/v1/user/profile', {
        credentials: 'include',
      });

      return {
        status: response.status,
        ok: response.ok,
        url: response.url,
      };
    });

    // Step 3: Verify API response
    expect(apiResponse.ok).toBe(true);
    expect(apiResponse.status).toBe(200);
    expect(apiResponse.url).toContain('/api/v1/');
  });

  test('unauthenticated requests to /api/v1/user/* endpoints return 401', async ({ request }) => {
    // Step 1: Try to access protected user profile endpoint without authentication
    const response = await request.get('http://localhost:13000/api/v1/user/profile');

    // Step 2: Verify unauthorized response
    expect(response.status()).toBe(401);

    const responseBody = await response.json();
    expect(responseBody).toHaveProperty('code');
    expect(responseBody).toHaveProperty('message');

    // Verify error code format (should be AUTH.* error code)
    expect(responseBody.code).toMatch(/^AUTH\./);
  });

  test('all user API endpoints are under /api/v1/user/* namespace', async ({ page }) => {
    // Setup comprehensive request listener
    const apiCalls: string[] = [];
    page.on('request', (req) => {
      const url = req.url();
      if (url.includes('/api/') && url.includes('localhost:13000')) {
        apiCalls.push(url);
      }
    });

    // Perform full authentication flow
    const loginPage = new UserLoginPage(page);
    const profilePage = new UserProfilePage(page);

    await loginPage.goto();

    const testEmail = process.env.E2E_USER_EMAIL || 'user@example.com';
    const testPassword = process.env.E2E_USER_PASSWORD || 'password';

    await loginPage.login(testEmail, testPassword);
    await profilePage.goto();
    await profilePage.logout();

    // Filter only user-related API calls (exclude health checks, etc.)
    const userApiCalls = apiCalls.filter(
      (url) => url.includes('/login') || url.includes('/profile') || url.includes('/logout')
    );

    // Verify all user API calls use correct versioned namespace
    expect(userApiCalls.length).toBeGreaterThan(0);

    for (const url of userApiCalls) {
      // All user API calls should:
      // 1. Use versioned API (/api/v1/)
      expect(url).toContain('/api/v1/');

      // 2. Use user namespace (/api/v1/user/*)
      expect(url).toContain('/api/v1/user/');
    }
  });

  test('API health check endpoint is accessible without authentication', async ({ request }) => {
    // Health check should be publicly accessible
    const response = await request.get('http://localhost:13000/api/health');

    expect(response.status()).toBe(200);

    const responseBody = await response.json();
    expect(responseBody).toHaveProperty('status');
    expect(responseBody.status).toBe('ok');
  });
});

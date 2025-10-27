import { test, expect } from '@playwright/test';
import { AdminLoginPage } from '../pages/LoginPage';
import { AdminDashboardPage } from '../pages/DashboardPage';

/**
 * API v1 Endpoint Access E2E Tests - Admin
 *
 * Tests that API versioning is correctly implemented for admin:
 * - All admin endpoints use /api/v1/admin/* prefix
 * - API responses include correct version headers
 * - Endpoints are accessible only with valid admin authentication
 *
 * Requirements: 14.4
 */
test.describe('API v1 Endpoint Access - Admin', () => {
  test('admin login endpoint uses /api/v1/admin/login', async ({ page }) => {
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
    const loginPage = new AdminLoginPage(page);
    await loginPage.goto();

    const testEmail = process.env.E2E_ADMIN_EMAIL || 'admin@example.com';
    const testPassword = process.env.E2E_ADMIN_PASSWORD || 'password';

    await page.fill('input[name="email"]', testEmail);
    await page.fill('input[name="password"]', testPassword);
    await page.click('button[type="submit"]');

    await page.waitForURL('**/dashboard', { timeout: 5000 });

    // Verify login API call
    const loginCall = apiCalls.find((call) => call.url.includes('/login'));
    expect(loginCall).toBeDefined();
    expect(loginCall?.url).toContain('/api/v1/admin/login');
    expect(loginCall?.method).toBe('POST');
  });

  test('admin dashboard endpoint uses /api/v1/admin/dashboard', async ({ page }) => {
    // Login first
    const loginPage = new AdminLoginPage(page);
    await loginPage.goto();

    const testEmail = process.env.E2E_ADMIN_EMAIL || 'admin@example.com';
    const testPassword = process.env.E2E_ADMIN_PASSWORD || 'password';

    await loginPage.login(testEmail, testPassword);

    // Setup request listener to capture dashboard API calls
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

    // Navigate to dashboard page (triggers API call)
    const dashboardPage = new AdminDashboardPage(page);
    await dashboardPage.goto();

    // Wait for dashboard to load
    await page.waitForTimeout(1000);

    // Verify dashboard API call
    const dashboardCall = apiCalls.find((call) => call.url.includes('/dashboard'));
    expect(dashboardCall).toBeDefined();
    expect(dashboardCall?.url).toContain('/api/v1/admin/dashboard');
    expect(dashboardCall?.method).toBe('GET');
  });

  test('admin logout endpoint uses /api/v1/admin/logout', async ({ page }) => {
    // Login first
    const loginPage = new AdminLoginPage(page);
    await loginPage.goto();

    const testEmail = process.env.E2E_ADMIN_EMAIL || 'admin@example.com';
    const testPassword = process.env.E2E_ADMIN_PASSWORD || 'password';

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
    const dashboardPage = new AdminDashboardPage(page);
    await dashboardPage.logout();

    // Verify logout API call
    const logoutCall = apiCalls.find((call) => call.url.includes('/logout'));
    expect(logoutCall).toBeDefined();
    expect(logoutCall?.url).toContain('/api/v1/admin/logout');
    expect(logoutCall?.method).toBe('POST');
  });

  test('API responses include correct version in URL', async ({ page }) => {
    // Step 1: Login to get authentication token
    const loginPage = new AdminLoginPage(page);
    await loginPage.goto();

    const testEmail = process.env.E2E_ADMIN_EMAIL || 'admin@example.com';
    const testPassword = process.env.E2E_ADMIN_PASSWORD || 'password';

    await loginPage.login(testEmail, testPassword);

    // Step 2: Make direct API call and verify response
    const apiResponse = await page.evaluate(async () => {
      const response = await fetch('http://localhost:13000/api/v1/admin/dashboard', {
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

  test('unauthenticated requests to /api/v1/admin/* endpoints return 401', async ({ request }) => {
    // Step 1: Try to access protected admin dashboard endpoint without authentication
    const response = await request.get('http://localhost:13000/api/v1/admin/dashboard');

    // Step 2: Verify unauthorized response
    expect(response.status()).toBe(401);

    const responseBody = await response.json();
    expect(responseBody).toHaveProperty('code');
    expect(responseBody).toHaveProperty('message');

    // Verify error code format (should be AUTH.* error code)
    expect(responseBody.code).toMatch(/^AUTH\./);
  });

  test('all admin API endpoints are under /api/v1/admin/* namespace', async ({ page }) => {
    // Setup comprehensive request listener
    const apiCalls: string[] = [];
    page.on('request', (req) => {
      const url = req.url();
      if (url.includes('/api/') && url.includes('localhost:13000')) {
        apiCalls.push(url);
      }
    });

    // Perform full authentication flow
    const loginPage = new AdminLoginPage(page);
    const dashboardPage = new AdminDashboardPage(page);

    await loginPage.goto();

    const testEmail = process.env.E2E_ADMIN_EMAIL || 'admin@example.com';
    const testPassword = process.env.E2E_ADMIN_PASSWORD || 'password';

    await loginPage.login(testEmail, testPassword);
    await dashboardPage.goto();
    await dashboardPage.logout();

    // Filter only admin-related API calls (exclude health checks, etc.)
    const adminApiCalls = apiCalls.filter(
      (url) => url.includes('/login') || url.includes('/dashboard') || url.includes('/logout')
    );

    // Verify all admin API calls use correct versioned namespace
    expect(adminApiCalls.length).toBeGreaterThan(0);

    for (const url of adminApiCalls) {
      // All admin API calls should:
      // 1. Use versioned API (/api/v1/)
      expect(url).toContain('/api/v1/');

      // 2. Use admin namespace (/api/v1/admin/*)
      expect(url).toContain('/api/v1/admin/');
    }
  });

  test('admin endpoints reject user tokens', async ({ browser }) => {
    // Create two browser contexts
    const userContext = await browser.newContext();
    const adminContext = await browser.newContext();

    const userPage = await userContext.newPage();
    const adminPage = await adminContext.newPage();

    try {
      // Step 1: Login as user
      await userPage.goto('http://localhost:13001/login');
      await userPage.waitForSelector('form', { state: 'visible' });

      const userEmail = process.env.E2E_USER_EMAIL || 'user@example.com';
      const userPassword = process.env.E2E_USER_PASSWORD || 'password';

      await userPage.fill('input[name="email"]', userEmail);
      await userPage.fill('input[name="password"]', userPassword);
      await userPage.click('button[type="submit"]');
      await userPage.waitForURL('**/profile', { timeout: 5000 });

      // Step 2: Try to access admin endpoint with user token
      const adminApiResponse = await userPage.evaluate(async () => {
        const response = await fetch('http://localhost:13000/api/v1/admin/dashboard', {
          credentials: 'include',
        });

        return {
          status: response.status,
          ok: response.ok,
        };
      });

      // Step 3: Verify admin endpoint rejects user token
      expect(adminApiResponse.ok).toBe(false);
      expect([401, 403]).toContain(adminApiResponse.status);

      // Step 4: Verify admin can access the same endpoint
      const adminLoginPage = new AdminLoginPage(adminPage);
      await adminLoginPage.goto();

      const adminEmail = process.env.E2E_ADMIN_EMAIL || 'admin@example.com';
      const adminPassword = process.env.E2E_ADMIN_PASSWORD || 'password';

      await adminLoginPage.login(adminEmail, adminPassword);

      const adminValidResponse = await adminPage.evaluate(async () => {
        const response = await fetch('http://localhost:13000/api/v1/admin/dashboard', {
          credentials: 'include',
        });

        return {
          status: response.status,
          ok: response.ok,
        };
      });

      // Step 5: Verify admin token works correctly
      expect(adminValidResponse.ok).toBe(true);
      expect(adminValidResponse.status).toBe(200);
    } finally {
      await userContext.close();
      await adminContext.close();
    }
  });
});

import { test, expect } from '@playwright/test';
import { AdminLoginPage } from '../pages/LoginPage';
import { AdminDashboardPage } from '../pages/DashboardPage';

/**
 * Admin Guard Separation E2E Tests
 *
 * Tests that Admin authentication is properly separated from User:
 * - Admin tokens cannot access User endpoints
 * - Admin sessions are isolated from User sessions
 * - Admin authentication uses correct guard (/api/v1/admin/*)
 *
 * Requirements: 14.3
 */
test.describe('Admin Guard Separation', () => {
  test('admin tokens cannot access user endpoints', async ({ page }) => {
    const loginPage = new AdminLoginPage(page);
    const dashboardPage = new AdminDashboardPage(page);

    // Step 1: Login as admin
    await loginPage.goto();
    const testEmail = process.env.E2E_ADMIN_EMAIL || 'admin@example.com';
    const testPassword = process.env.E2E_ADMIN_PASSWORD || 'password';

    await loginPage.login(testEmail, testPassword);

    // Step 2: Verify admin is logged in
    await expect(page).toHaveURL(/.*\/dashboard/);
    const isDashboardVisible = await dashboardPage.isDashboardVisible();
    expect(isDashboardVisible).toBe(true);

    // Step 3: Try to access user profile endpoint via API
    const userApiResponse = await page.evaluate(async () => {
      try {
        const response = await fetch('http://localhost:13000/api/v1/user/profile', {
          credentials: 'include',
        });
        return {
          status: response.status,
          ok: response.ok,
        };
      } catch (error) {
        return {
          status: 0,
          ok: false,
          error: error instanceof Error ? error.message : 'Unknown error',
        };
      }
    });

    // Step 4: Verify user endpoint is not accessible with admin token
    expect(userApiResponse.ok).toBe(false);
    expect(userApiResponse.status).not.toBe(200);
    // Should be 401 Unauthorized or 403 Forbidden
    expect([401, 403]).toContain(userApiResponse.status);

    // Cleanup: Logout
    await dashboardPage.logout();
  });

  test('admin authentication uses correct guard endpoint', async ({ page }) => {
    const loginPage = new AdminLoginPage(page);

    // Step 1: Navigate to login page
    await loginPage.goto();

    // Step 2: Setup request listener to monitor API calls
    const apiCalls: string[] = [];
    page.on('request', (req) => {
      const url = req.url();
      if (url.includes('/api/v1/')) {
        apiCalls.push(url);
      }
    });

    // Step 3: Perform login
    const testEmail = process.env.E2E_ADMIN_EMAIL || 'admin@example.com';
    const testPassword = process.env.E2E_ADMIN_PASSWORD || 'password';

    await page.fill('input[name="email"]', testEmail);
    await page.fill('input[name="password"]', testPassword);
    await page.click('button[type="submit"]');

    // Wait for navigation
    await page.waitForURL('**/dashboard', { timeout: 5000 });

    // Step 4: Verify login API call used correct admin endpoint
    const loginCalls = apiCalls.filter((url) => url.includes('/login'));
    expect(loginCalls.length).toBeGreaterThan(0);

    const adminLoginCall = loginCalls.find((url) => url.includes('/api/v1/admin/login'));
    expect(adminLoginCall).toBeDefined();

    // Step 5: Verify no user endpoints were called
    const userCalls = apiCalls.filter((url) => url.includes('/api/v1/user/'));
    expect(userCalls.length).toBe(0);
  });

  test('admin cannot navigate to user routes', async ({ page }) => {
    const loginPage = new AdminLoginPage(page);

    // Step 1: Login as admin
    await loginPage.goto();
    const testEmail = process.env.E2E_ADMIN_EMAIL || 'admin@example.com';
    const testPassword = process.env.E2E_ADMIN_PASSWORD || 'password';

    await loginPage.login(testEmail, testPassword);

    // Step 2: Verify admin is on dashboard page
    await expect(page).toHaveURL(/.*\/dashboard/);

    // Step 3: Try to navigate to user profile
    // Note: This test assumes frontend implements route protection
    await page.goto('http://localhost:13001/profile');

    // Step 4: Wait a bit for potential redirect
    await page.waitForTimeout(1000);

    // Step 5: Verify admin is either redirected or sees error
    const currentUrl = page.url();

    // Admin should NOT be on user profile
    expect(currentUrl).not.toContain('13001/profile');

    // Most likely scenarios:
    // 1. Redirected to user login (http://localhost:13001/login)
    // 2. Stayed on admin dashboard (http://localhost:13002/dashboard)
    // 3. Shows 403 error page
    const isOnUserLogin = currentUrl.includes('13001/login');
    const isOnAdminDashboard = currentUrl.includes('13002/dashboard');

    expect(isOnUserLogin || isOnAdminDashboard).toBe(true);
  });

  test('admin session is isolated from user session', async ({ browser }) => {
    // Create two separate browser contexts to simulate admin and user sessions
    const adminContext = await browser.newContext();
    const userContext = await browser.newContext();

    const adminPage = await adminContext.newPage();
    const userPage = await userContext.newPage();

    try {
      // Step 1: Login as admin in first context
      const adminLoginPage = new AdminLoginPage(adminPage);
      await adminLoginPage.goto();

      const adminEmail = process.env.E2E_ADMIN_EMAIL || 'admin@example.com';
      const adminPassword = process.env.E2E_ADMIN_PASSWORD || 'password';

      await adminLoginPage.login(adminEmail, adminPassword);
      await expect(adminPage).toHaveURL(/.*\/dashboard/);

      // Step 2: Login as user in second context
      await userPage.goto('http://localhost:13001/login');
      await userPage.waitForSelector('form', { state: 'visible' });

      const userEmail = process.env.E2E_USER_EMAIL || 'user@example.com';
      const userPassword = process.env.E2E_USER_PASSWORD || 'password';

      await userPage.fill('input[name="email"]', userEmail);
      await userPage.fill('input[name="password"]', userPassword);
      await userPage.click('button[type="submit"]');
      await userPage.waitForURL('**/profile', { timeout: 5000 });

      // Step 3: Verify both sessions are active simultaneously
      const adminDashboardVisible = await adminPage.locator('h1, h2').first().isVisible();
      const userProfileVisible = await userPage.locator('h1, h2').first().isVisible();

      expect(adminDashboardVisible).toBe(true);
      expect(userProfileVisible).toBe(true);

      // Step 4: Verify sessions are truly isolated (no shared cookies/tokens)
      const adminCookies = await adminContext.cookies();
      const userCookies = await userContext.cookies();

      // Admin context should have admin-specific cookies
      const adminSessionCookie = adminCookies.find((c) =>
        c.name.toLowerCase().includes('session')
      );
      const userSessionCookie = userCookies.find((c) =>
        c.name.toLowerCase().includes('session')
      );

      // If session cookies exist, they should be different
      if (adminSessionCookie && userSessionCookie) {
        expect(adminSessionCookie.value).not.toBe(userSessionCookie.value);
      }

      // Step 5: Logout from admin session should not affect user session
      const adminDashboardPage = new AdminDashboardPage(adminPage);
      await adminDashboardPage.logout();

      // Verify admin is logged out
      await expect(adminPage).toHaveURL(/.*\/login/);

      // Verify user is still logged in
      const userStillVisible = await userPage.locator('h1, h2').first().isVisible();
      expect(userStillVisible).toBe(true);
    } finally {
      // Cleanup
      await adminContext.close();
      await userContext.close();
    }
  });
});

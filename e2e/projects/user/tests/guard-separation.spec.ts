import { test, expect } from '@playwright/test';
import { UserLoginPage } from '../pages/LoginPage';
import { UserProfilePage } from '../pages/ProfilePage';

/**
 * User Guard Separation E2E Tests
 *
 * Tests that User authentication is properly separated from Admin:
 * - User tokens cannot access Admin endpoints
 * - User sessions are isolated from Admin sessions
 * - User authentication uses correct guard (/api/v1/user/*)
 *
 * Requirements: 14.3
 */
test.describe('User Guard Separation', () => {
  test('user tokens cannot access admin endpoints', async ({ page }) => {
    const loginPage = new UserLoginPage(page);
    const profilePage = new UserProfilePage(page);

    // Step 1: Login as user
    await loginPage.goto();
    const testEmail = process.env.E2E_USER_EMAIL || 'user@example.com';
    const testPassword = process.env.E2E_USER_PASSWORD || 'password';

    await loginPage.login(testEmail, testPassword);

    // Step 2: Verify user is logged in
    await expect(page).toHaveURL(/.*\/profile/);
    const isProfileVisible = await profilePage.isProfileVisible();
    expect(isProfileVisible).toBe(true);

    // Step 3: Try to access admin dashboard endpoint via API
    const adminApiResponse = await page.evaluate(async () => {
      try {
        const response = await fetch('http://localhost:13000/api/v1/admin/dashboard', {
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

    // Step 4: Verify admin endpoint is not accessible with user token
    expect(adminApiResponse.ok).toBe(false);
    expect(adminApiResponse.status).not.toBe(200);
    // Should be 401 Unauthorized or 403 Forbidden
    expect([401, 403]).toContain(adminApiResponse.status);

    // Cleanup: Logout
    await profilePage.logout();
  });

  test('user authentication uses correct guard endpoint', async ({ page, request }) => {
    const loginPage = new UserLoginPage(page);

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
    const testEmail = process.env.E2E_USER_EMAIL || 'user@example.com';
    const testPassword = process.env.E2E_USER_PASSWORD || 'password';

    await page.fill('input[name="email"]', testEmail);
    await page.fill('input[name="password"]', testPassword);
    await page.click('button[type="submit"]');

    // Wait for navigation
    await page.waitForURL('**/profile', { timeout: 5000 });

    // Step 4: Verify login API call used correct user endpoint
    const loginCalls = apiCalls.filter((url) => url.includes('/login'));
    expect(loginCalls.length).toBeGreaterThan(0);

    const userLoginCall = loginCalls.find((url) => url.includes('/api/v1/user/login'));
    expect(userLoginCall).toBeDefined();

    // Step 5: Verify no admin endpoints were called
    const adminCalls = apiCalls.filter((url) => url.includes('/api/v1/admin/'));
    expect(adminCalls.length).toBe(0);
  });

  test('user cannot navigate to admin routes', async ({ page }) => {
    const loginPage = new UserLoginPage(page);

    // Step 1: Login as user
    await loginPage.goto();
    const testEmail = process.env.E2E_USER_EMAIL || 'user@example.com';
    const testPassword = process.env.E2E_USER_PASSWORD || 'password';

    await loginPage.login(testEmail, testPassword);

    // Step 2: Verify user is on profile page
    await expect(page).toHaveURL(/.*\/profile/);

    // Step 3: Try to navigate to admin dashboard
    // Note: This test assumes frontend implements route protection
    // If no frontend route protection exists, this tests API-level protection
    await page.goto('http://localhost:13002/dashboard');

    // Step 4: Wait a bit for potential redirect
    await page.waitForTimeout(1000);

    // Step 5: Verify user is either redirected or sees error
    // (Implementation detail: frontend may redirect to login or show 403)
    const currentUrl = page.url();

    // User should NOT be on admin dashboard
    expect(currentUrl).not.toContain('13002/dashboard');

    // Most likely scenarios:
    // 1. Redirected to admin login (http://localhost:13002/login)
    // 2. Stayed on user profile (http://localhost:13001/profile)
    // 3. Shows 403 error page
    const isOnAdminLogin = currentUrl.includes('13002/login');
    const isOnUserProfile = currentUrl.includes('13001/profile');

    expect(isOnAdminLogin || isOnUserProfile).toBe(true);
  });

  test('user session is isolated from admin session', async ({ browser }) => {
    // Create two separate browser contexts to simulate user and admin sessions
    const userContext = await browser.newContext();
    const adminContext = await browser.newContext();

    const userPage = await userContext.newPage();
    const adminPage = await adminContext.newPage();

    try {
      // Step 1: Login as user in first context
      const userLoginPage = new UserLoginPage(userPage);
      await userLoginPage.goto();

      const userEmail = process.env.E2E_USER_EMAIL || 'user@example.com';
      const userPassword = process.env.E2E_USER_PASSWORD || 'password';

      await userLoginPage.login(userEmail, userPassword);
      await expect(userPage).toHaveURL(/.*\/profile/);

      // Step 2: Login as admin in second context
      await adminPage.goto('http://localhost:13002/login');
      await adminPage.waitForSelector('form', { state: 'visible' });

      const adminEmail = process.env.E2E_ADMIN_EMAIL || 'admin@example.com';
      const adminPassword = process.env.E2E_ADMIN_PASSWORD || 'password';

      await adminPage.fill('input[name="email"]', adminEmail);
      await adminPage.fill('input[name="password"]', adminPassword);
      await adminPage.click('button[type="submit"]');
      await adminPage.waitForURL('**/dashboard', { timeout: 5000 });

      // Step 3: Verify both sessions are active simultaneously
      const userProfileVisible = await userPage.locator('h1, h2').first().isVisible();
      const adminDashboardVisible = await adminPage.locator('h1, h2').first().isVisible();

      expect(userProfileVisible).toBe(true);
      expect(adminDashboardVisible).toBe(true);

      // Step 4: Verify sessions are truly isolated (no shared cookies/tokens)
      const userCookies = await userContext.cookies();
      const adminCookies = await adminContext.cookies();

      // User context should have user-specific cookies
      const userSessionCookie = userCookies.find((c) =>
        c.name.toLowerCase().includes('session')
      );
      const adminSessionCookie = adminCookies.find((c) =>
        c.name.toLowerCase().includes('session')
      );

      // If session cookies exist, they should be different
      if (userSessionCookie && adminSessionCookie) {
        expect(userSessionCookie.value).not.toBe(adminSessionCookie.value);
      }

      // Step 5: Logout from user session should not affect admin session
      const userProfilePage = new UserProfilePage(userPage);
      await userProfilePage.logout();

      // Verify user is logged out
      await expect(userPage).toHaveURL(/.*\/login/);

      // Verify admin is still logged in
      const adminStillVisible = await adminPage.locator('h1, h2').first().isVisible();
      expect(adminStillVisible).toBe(true);
    } finally {
      // Cleanup
      await userContext.close();
      await adminContext.close();
    }
  });
});

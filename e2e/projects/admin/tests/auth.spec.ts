import { test, expect } from '@playwright/test';
import { AdminLoginPage } from '../pages/LoginPage';
import { AdminDashboardPage } from '../pages/DashboardPage';

/**
 * Admin Authentication E2E Tests
 *
 * Tests the complete admin authentication flow:
 * - Login with valid credentials
 * - Dashboard display after successful login
 * - Logout functionality
 * - Redirect to login page after logout
 *
 * Requirements: 14.2
 */
test.describe('Admin Authentication Flow', () => {
  test('can complete full authentication flow: login → dashboard → logout', async ({ page }) => {
    const loginPage = new AdminLoginPage(page);
    const dashboardPage = new AdminDashboardPage(page);

    // Step 1: Navigate to login page
    await loginPage.goto();

    // Step 2: Perform login with test credentials
    const testEmail = process.env.E2E_ADMIN_EMAIL || 'admin@example.com';
    const testPassword = process.env.E2E_ADMIN_PASSWORD || 'password';

    await loginPage.login(testEmail, testPassword);

    // Step 3: Verify navigation to dashboard page
    await expect(page).toHaveURL(/.*\/dashboard/);

    // Step 4: Verify dashboard page displays admin information
    const isDashboardVisible = await dashboardPage.isDashboardVisible();
    expect(isDashboardVisible).toBe(true);

    // Step 5: Verify admin name is displayed
    const adminName = await dashboardPage.getAdminName();
    expect(adminName).not.toBeNull();
    expect(adminName).toBeTruthy();

    // Step 6: Perform logout
    await dashboardPage.logout();

    // Step 7: Verify redirect to login page
    await expect(page).toHaveURL(/.*\/login/);

    // Step 8: Verify login form is visible after logout
    const isFormVisible = await loginPage.isFormVisible();
    expect(isFormVisible).toBe(true);
  });

  test('displays admin name correctly on dashboard page', async ({ page }) => {
    const loginPage = new AdminLoginPage(page);
    const dashboardPage = new AdminDashboardPage(page);

    // Login
    await loginPage.goto();
    const testEmail = process.env.E2E_ADMIN_EMAIL || 'admin@example.com';
    const testPassword = process.env.E2E_ADMIN_PASSWORD || 'password';

    await loginPage.login(testEmail, testPassword);

    // Verify admin name is displayed
    const adminName = await dashboardPage.getAdminName();
    expect(adminName).not.toBeNull();
    expect(adminName).toBeTruthy();
    // Admin name should not be empty or just whitespace
    expect(adminName?.trim().length).toBeGreaterThan(0);
  });

  test('redirects to login page after logout', async ({ page }) => {
    const loginPage = new AdminLoginPage(page);
    const dashboardPage = new AdminDashboardPage(page);

    // Login first
    await loginPage.goto();
    const testEmail = process.env.E2E_ADMIN_EMAIL || 'admin@example.com';
    const testPassword = process.env.E2E_ADMIN_PASSWORD || 'password';

    await loginPage.login(testEmail, testPassword);

    // Verify we're on dashboard page
    await expect(page).toHaveURL(/.*\/dashboard/);

    // Logout
    await dashboardPage.logout();

    // Verify redirect to login page
    await expect(page).toHaveURL(/.*\/login/);

    // Verify we can see login form (not authenticated)
    const isFormVisible = await loginPage.isFormVisible();
    expect(isFormVisible).toBe(true);
  });

  test('shows error message for invalid credentials', async ({ page }) => {
    const loginPage = new AdminLoginPage(page);

    // Navigate to login page
    await loginPage.goto();

    // Attempt login with invalid credentials
    await page.fill('input[name="email"]', 'invalid@example.com');
    await page.fill('input[name="password"]', 'wrongpassword');
    await page.click('button[type="submit"]');

    // Wait a bit for error message to appear
    await page.waitForTimeout(1000);

    // Verify error message is displayed
    const errorMessage = await loginPage.getErrorMessage();
    expect(errorMessage).not.toBeNull();
    expect(errorMessage).toBeTruthy();

    // Verify we're still on login page (not redirected)
    await expect(page).toHaveURL(/.*\/login/);
  });

  test('login form is visible on page load', async ({ page }) => {
    const loginPage = new AdminLoginPage(page);

    // Navigate to login page
    await loginPage.goto();

    // Verify form is visible
    const isFormVisible = await loginPage.isFormVisible();
    expect(isFormVisible).toBe(true);

    // Verify email and password fields are present
    const emailInput = page.locator('input[name="email"]');
    const passwordInput = page.locator('input[name="password"]');
    const submitButton = page.locator('button[type="submit"]');

    await expect(emailInput).toBeVisible();
    await expect(passwordInput).toBeVisible();
    await expect(submitButton).toBeVisible();
  });
});

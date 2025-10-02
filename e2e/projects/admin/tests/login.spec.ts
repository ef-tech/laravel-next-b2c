import { test, expect } from '@playwright/test';
import { AdminLoginPage } from '../pages/LoginPage';

/**
 * Admin Login E2E Tests
 *
 * Tests the admin login functionality including:
 * - Successful login with valid credentials
 * - Navigation to dashboard after login
 * - Form validation and error handling
 */
test.describe('Admin Login', () => {
  test('can login via UI', async ({ page }) => {
    // Arrange: Create login page instance
    const loginPage = new AdminLoginPage(page);

    // Act: Navigate to login page
    await loginPage.goto();

    // Act: Perform login with credentials from environment
    await loginPage.login(
      process.env.E2E_ADMIN_EMAIL!,
      process.env.E2E_ADMIN_PASSWORD!
    );

    // Assert: Verify navigation to dashboard
    await expect(page).toHaveURL(/.*\/dashboard/);

    // Assert: Verify dashboard content is visible
    await expect(page.locator('[data-testid="dashboard"]')).toBeVisible();
  });

  test('displays error message for invalid credentials', async ({ page }) => {
    // Arrange: Create login page instance
    const loginPage = new AdminLoginPage(page);

    // Act: Navigate to login page
    await loginPage.goto();

    // Act: Attempt login with invalid credentials
    await loginPage.login('invalid@example.com', 'wrongpassword');

    // Assert: Verify error message is displayed
    const errorMessage = await loginPage.getErrorMessage();
    expect(errorMessage).toContain('Invalid credentials');
  });

  test('login form is visible on page load', async ({ page }) => {
    // Arrange: Create login page instance
    const loginPage = new AdminLoginPage(page);

    // Act: Navigate to login page
    await loginPage.goto();

    // Assert: Verify login form is visible
    const isVisible = await loginPage.isFormVisible();
    expect(isVisible).toBe(true);
  });

  test('redirects to dashboard if already authenticated', async ({ page }) => {
    // Note: This test uses the authenticated storage state from global setup

    // Act: Navigate to login page while already authenticated
    await page.goto('/login');

    // Assert: Should redirect to dashboard
    await expect(page).toHaveURL(/.*\/dashboard/);
  });
});

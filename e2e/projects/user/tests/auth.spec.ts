import { test, expect } from '@playwright/test';
import { UserLoginPage } from '../pages/LoginPage';
import { UserProfilePage } from '../pages/ProfilePage';

/**
 * User Authentication E2E Tests
 *
 * Tests the complete user authentication flow:
 * - Login with valid credentials
 * - Profile display after successful login
 * - Logout functionality
 * - Redirect to login page after logout
 *
 * Requirements: 14.1
 */
test.describe('User Authentication Flow', () => {
  test('can complete full authentication flow: login → profile → logout', async ({ page }) => {
    const loginPage = new UserLoginPage(page);
    const profilePage = new UserProfilePage(page);

    // Step 1: Navigate to login page
    await loginPage.goto();

    // Step 2: Perform login with test credentials
    const testEmail = process.env.E2E_USER_EMAIL || 'user@example.com';
    const testPassword = process.env.E2E_USER_PASSWORD || 'password';

    await loginPage.login(testEmail, testPassword);

    // Step 3: Verify navigation to profile page
    await expect(page).toHaveURL(/.*\/profile/);

    // Step 4: Verify profile page displays user information
    const isProfileVisible = await profilePage.isProfileVisible();
    expect(isProfileVisible).toBe(true);

    // Step 5: Verify user name is displayed
    const userName = await profilePage.getUserName();
    expect(userName).not.toBeNull();
    expect(userName).toBeTruthy();

    // Step 6: Perform logout
    await profilePage.logout();

    // Step 7: Verify redirect to login page
    await expect(page).toHaveURL(/.*\/login/);

    // Step 8: Verify login form is visible after logout
    const isFormVisible = await loginPage.isFormVisible();
    expect(isFormVisible).toBe(true);
  });

  test('displays user name correctly on profile page', async ({ page }) => {
    const loginPage = new UserLoginPage(page);
    const profilePage = new UserProfilePage(page);

    // Login
    await loginPage.goto();
    const testEmail = process.env.E2E_USER_EMAIL || 'user@example.com';
    const testPassword = process.env.E2E_USER_PASSWORD || 'password';

    await loginPage.login(testEmail, testPassword);

    // Verify user name is displayed
    const userName = await profilePage.getUserName();
    expect(userName).not.toBeNull();
    expect(userName).toBeTruthy();
    // User name should not be empty or just whitespace
    expect(userName?.trim().length).toBeGreaterThan(0);
  });

  test('redirects to login page after logout', async ({ page }) => {
    const loginPage = new UserLoginPage(page);
    const profilePage = new UserProfilePage(page);

    // Login first
    await loginPage.goto();
    const testEmail = process.env.E2E_USER_EMAIL || 'user@example.com';
    const testPassword = process.env.E2E_USER_PASSWORD || 'password';

    await loginPage.login(testEmail, testPassword);

    // Verify we're on profile page
    await expect(page).toHaveURL(/.*\/profile/);

    // Logout
    await profilePage.logout();

    // Verify redirect to login page
    await expect(page).toHaveURL(/.*\/login/);

    // Verify we can see login form (not authenticated)
    const isFormVisible = await loginPage.isFormVisible();
    expect(isFormVisible).toBe(true);
  });

  test('shows error message for invalid credentials', async ({ page }) => {
    const loginPage = new UserLoginPage(page);

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
    const loginPage = new UserLoginPage(page);

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

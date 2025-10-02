import { type Page, expect } from '@playwright/test';

/**
 * Admin Login Page Object
 *
 * Provides methods to interact with the admin login page
 * using the Page Object Model pattern for reusable test code.
 *
 * @example
 * ```typescript
 * const loginPage = new AdminLoginPage(page);
 * await loginPage.goto();
 * await loginPage.login('admin@example.com', 'password123');
 * ```
 */
export class AdminLoginPage {
  constructor(private readonly page: Page) {}

  /**
   * Navigate to the admin login page
   *
   * Waits for the login form to be visible before proceeding
   */
  async goto() {
    await this.page.goto('/login');
    await this.page.waitForSelector('[data-testid="login-form"]', {
      state: 'visible',
    });
  }

  /**
   * Perform login with email and password
   *
   * Fills in the login form and submits, then waits for
   * successful navigation to the dashboard.
   *
   * @param email - User email address
   * @param password - User password
   */
  async login(email: string, password: string) {
    // Fill email field
    await this.page.fill('[data-testid="email"]', email);

    // Fill password field
    await this.page.fill('[data-testid="password"]', password);

    // Submit login form
    await this.page.click('[data-testid="submit"]');

    // Wait for navigation to dashboard
    await this.page.waitForURL('**/dashboard');
  }

  /**
   * Get error message displayed on login failure
   *
   * @returns Error message text
   */
  async getErrorMessage(): Promise<string> {
    const errorElement = await this.page.locator('[data-testid="error-message"]');
    return await errorElement.textContent() || '';
  }

  /**
   * Check if login form is visible
   *
   * @returns true if form is visible, false otherwise
   */
  async isFormVisible(): Promise<boolean> {
    return await this.page.locator('[data-testid="login-form"]').isVisible();
  }
}

import { type Page } from '@playwright/test';

/**
 * User Login Page Object
 *
 * Provides methods to interact with the user login page
 * using the Page Object Model pattern for reusable test code.
 *
 * @example
 * ```typescript
 * const loginPage = new UserLoginPage(page);
 * await loginPage.goto();
 * await loginPage.login('user@example.com', 'password123');
 * ```
 */
export class UserLoginPage {
  constructor(private readonly page: Page) {}

  /**
   * Navigate to the user login page
   *
   * Waits for the login form to be visible before proceeding
   */
  async goto() {
    await this.page.goto('/login');
    await this.page.waitForSelector('form', { state: 'visible' });
  }

  /**
   * Perform login with email and password
   *
   * Fills in the login form and submits, then waits for
   * successful navigation to the profile page.
   *
   * @param email - User email address
   * @param password - User password
   */
  async login(email: string, password: string) {
    // Fill email field
    await this.page.fill('input[name="email"]', email);

    // Fill password field
    await this.page.fill('input[name="password"]', password);

    // Submit login form
    await this.page.click('button[type="submit"]');

    // Wait for navigation to profile page (increased timeout for CI environment)
    await this.page.waitForURL('**/profile', { timeout: 15000 });
  }

  /**
   * Get error message displayed on login failure
   *
   * @returns Error message text or null if no error
   */
  async getErrorMessage(): Promise<string | null> {
    const errorElement = this.page.locator('.text-red-800, .text-red-600').first();
    const isVisible = await errorElement.isVisible().catch(() => false);

    if (!isVisible) {
      return null;
    }

    return await errorElement.textContent();
  }

  /**
   * Check if login form is visible
   *
   * @returns true if form is visible, false otherwise
   */
  async isFormVisible(): Promise<boolean> {
    return await this.page.locator('form').isVisible();
  }
}

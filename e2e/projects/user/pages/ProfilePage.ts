import { type Page } from '@playwright/test';

/**
 * User Profile Page Object
 *
 * Provides methods to interact with the user profile page
 * using the Page Object Model pattern for reusable test code.
 *
 * @example
 * ```typescript
 * const profilePage = new UserProfilePage(page);
 * await profilePage.goto();
 * const userName = await profilePage.getUserName();
 * ```
 */
export class UserProfilePage {
  constructor(private readonly page: Page) {}

  /**
   * Navigate to the user profile page
   */
  async goto() {
    await this.page.goto('/profile');
    await this.page.waitForLoadState('networkidle');
  }

  /**
   * Get the displayed user name from the profile page
   *
   * @returns User name text
   */
  async getUserName(): Promise<string | null> {
    // Look for heading or text containing user name
    const nameElement = this.page.locator('h1, h2, [data-testid="user-name"]').first();
    const isVisible = await nameElement.isVisible().catch(() => false);

    if (!isVisible) {
      return null;
    }

    return await nameElement.textContent();
  }

  /**
   * Get the displayed user email from the profile page
   *
   * @returns User email text
   */
  async getUserEmail(): Promise<string | null> {
    const emailElement = this.page.locator('[data-testid="user-email"], p:has-text("@")').first();
    const isVisible = await emailElement.isVisible().catch(() => false);

    if (!isVisible) {
      return null;
    }

    return await emailElement.textContent();
  }

  /**
   * Click the logout button
   */
  async logout() {
    await this.page.click('button:has-text("ログアウト"), button:has-text("Logout")');
    // Wait for navigation to login page
    await this.page.waitForURL('**/login', { timeout: 5000 });
  }

  /**
   * Check if profile page is loaded (user is authenticated)
   *
   * @returns true if profile page content is visible
   */
  async isProfileVisible(): Promise<boolean> {
    return await this.page.locator('h1, h2').first().isVisible();
  }
}

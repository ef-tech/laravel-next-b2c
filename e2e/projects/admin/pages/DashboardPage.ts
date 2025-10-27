import { type Page } from '@playwright/test';

/**
 * Admin Dashboard Page Object
 *
 * Provides methods to interact with the admin dashboard page
 * using the Page Object Model pattern for reusable test code.
 *
 * @example
 * ```typescript
 * const dashboardPage = new AdminDashboardPage(page);
 * await dashboardPage.goto();
 * const adminName = await dashboardPage.getAdminName();
 * ```
 */
export class AdminDashboardPage {
  constructor(private readonly page: Page) {}

  /**
   * Navigate to the admin dashboard page
   */
  async goto() {
    await this.page.goto('/dashboard');
    await this.page.waitForLoadState('networkidle');
  }

  /**
   * Get the displayed admin name from the dashboard
   *
   * @returns Admin name text
   */
  async getAdminName(): Promise<string | null> {
    // Look for heading or text containing admin name
    const nameElement = this.page.locator('h1, h2, [data-testid="admin-name"]').first();
    const isVisible = await nameElement.isVisible().catch(() => false);

    if (!isVisible) {
      return null;
    }

    return await nameElement.textContent();
  }

  /**
   * Get the displayed admin email from the dashboard
   *
   * @returns Admin email text
   */
  async getAdminEmail(): Promise<string | null> {
    const emailElement = this.page.locator('[data-testid="admin-email"], p:has-text("@")').first();
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
    await this.page.waitForURL('**/login', { timeout: 15000 });
  }

  /**
   * Check if dashboard page is loaded (admin is authenticated)
   *
   * @returns true if dashboard content is visible
   */
  async isDashboardVisible(): Promise<boolean> {
    return await this.page.locator('h1, h2').first().isVisible();
  }
}

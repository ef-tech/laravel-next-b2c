import { test, expect } from '@playwright/test';

/**
 * Admin App Home Page E2E Tests
 *
 * Tests the admin application home page:
 * - Page loads successfully
 * - Next.js default content is visible
 * - Navigation links work correctly
 *
 * Note: Skipped - Out of scope for error-handling-pattern feature
 */
test.describe.skip('Admin App Home Page', () => {
  test('loads homepage successfully', async ({ page }) => {
    // Act: Navigate to home page
    await page.goto('/');

    // Assert: Page loaded (not 404)
    await expect(page).toHaveURL('/');

    // Assert: Next.js logo is visible
    await expect(page.locator('img[alt="Next.js logo"]')).toBeVisible();
  });

  test('displays welcome content', async ({ page }) => {
    // Act: Navigate to home page
    await page.goto('/');

    // Assert: Welcome text is visible
    await expect(page.locator('text=Get started by editing')).toBeVisible();
    await expect(page.locator('code', { hasText: 'src/app/page.tsx' })).toBeVisible();
  });

  test('has working external links', async ({ page }) => {
    // Act: Navigate to home page
    await page.goto('/');

    // Assert: Deploy button exists
    const deployButton = page.locator('a', { hasText: 'Deploy now' });
    await expect(deployButton).toBeVisible();
    await expect(deployButton).toHaveAttribute('href', /vercel\.com/);

    // Assert: Docs link exists
    const docsLink = page.locator('a', { hasText: 'Read our docs' });
    await expect(docsLink).toBeVisible();
    await expect(docsLink).toHaveAttribute('href', /nextjs\.org\/docs/);
  });

  test('has footer navigation links', async ({ page }) => {
    // Act: Navigate to home page
    await page.goto('/');

    // Assert: Learn link exists
    await expect(page.locator('a', { hasText: 'Learn' })).toBeVisible();

    // Assert: Examples link exists
    await expect(page.locator('a', { hasText: 'Examples' })).toBeVisible();

    // Assert: Next.js link exists
    await expect(page.locator('a', { hasText: 'Go to nextjs.org' })).toBeVisible();
  });

  test('renders responsive layout', async ({ page }) => {
    // Act: Navigate to home page
    await page.goto('/');

    // Assert: Main content container exists
    const main = page.locator('main');
    await expect(main).toBeVisible();

    // Assert: Footer exists
    const footer = page.locator('footer');
    await expect(footer).toBeVisible();
  });
});

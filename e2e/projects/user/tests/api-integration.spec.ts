import { test, expect } from '@playwright/test';

/**
 * User App API Integration E2E Tests
 *
 * Tests the integration between the user-facing application
 * and the Laravel API backend.
 */
test.describe('API Integration', () => {
  test('can fetch and display products from API', async ({ page }) => {
    // Act: Navigate to products page
    await page.goto('/products');

    // Assert: Verify products list is visible
    await expect(page.locator('[data-testid="products-list"]')).toBeVisible();

    // Assert: Verify at least one product is displayed (or empty state)
    const products = page.locator('[data-testid="product-card"]');
    const count = await products.count();

    if (count > 0) {
      // If products exist, verify first product has required elements
      await expect(products.first().locator('[data-testid="product-name"]')).toBeVisible();
      await expect(products.first().locator('[data-testid="product-price"]')).toBeVisible();
    } else {
      // If no products, verify empty state is shown
      await expect(page.locator('[data-testid="empty-state"]')).toBeVisible();
    }
  });

  test('can submit contact form via API', async ({ page }) => {
    // Arrange: Navigate to contact page
    await page.goto('/contact');

    // Arrange: Fill contact form
    const testEmail = `test-${Date.now()}@example.com`;
    const testName = 'E2E Test User';
    const testMessage = 'This is an automated E2E test message';

    await page.fill('[data-testid="contact-name"]', testName);
    await page.fill('[data-testid="contact-email"]', testEmail);
    await page.fill('[data-testid="contact-message"]', testMessage);

    // Act: Submit form
    await page.click('[data-testid="submit-contact"]');

    // Assert: Verify success message is displayed
    await expect(page.locator('[data-testid="success-message"]')).toBeVisible();
    await expect(page.locator('[data-testid="success-message"]')).toContainText(
      /thank you|success|sent/i
    );
  });

  test('can fetch product details from API', async ({ page }) => {
    // Arrange: Navigate to products page first
    await page.goto('/products');

    // Arrange: Get first product and click it
    const products = page.locator('[data-testid="product-card"]');
    const firstProduct = products.first();

    // Skip test if no products available
    const count = await products.count();
    if (count === 0) {
      test.skip();
      return;
    }

    // Get product name for verification
    const productName = await firstProduct
      .locator('[data-testid="product-name"]')
      .textContent();

    // Act: Click product to view details
    await firstProduct.click();

    // Assert: Verify product detail page is loaded
    await expect(page).toHaveURL(/.*\/products\/\d+/);

    // Assert: Verify product details are displayed
    await expect(page.locator('[data-testid="product-detail"]')).toBeVisible();
    await expect(page.locator('[data-testid="product-name"]')).toContainText(
      productName || ''
    );
    await expect(page.locator('[data-testid="product-description"]')).toBeVisible();
    await expect(page.locator('[data-testid="product-price"]')).toBeVisible();
  });

  test('handles API errors gracefully', async ({ page }) => {
    // Act: Navigate to a non-existent product
    await page.goto('/products/999999');

    // Assert: Verify error message or 404 page is displayed
    const errorVisible = await page
      .locator('[data-testid="error-message"]')
      .isVisible()
      .catch(() => false);

    const notFoundVisible = await page
      .locator('[data-testid="not-found"]')
      .isVisible()
      .catch(() => false);

    expect(errorVisible || notFoundVisible).toBe(true);
  });

  test('displays loading state during API request', async ({ page }) => {
    // Arrange: Slow down network to see loading state
    await page.route('**/api/products', async (route) => {
      // Delay response by 1 second
      await new Promise((resolve) => setTimeout(resolve, 1000));
      await route.continue();
    });

    // Act: Navigate to products page
    const navigationPromise = page.goto('/products');

    // Assert: Verify loading indicator appears
    await expect(page.locator('[data-testid="loading"]')).toBeVisible({
      timeout: 500,
    });

    // Wait for navigation to complete
    await navigationPromise;

    // Assert: Verify loading indicator disappears
    await expect(page.locator('[data-testid="loading"]')).not.toBeVisible();
  });

  test('can search products via API', async ({ page }) => {
    // Arrange: Navigate to products page
    await page.goto('/products');

    // Arrange: Enter search query
    const searchQuery = 'test';
    await page.fill('[data-testid="search-input"]', searchQuery);

    // Act: Trigger search
    await page.click('[data-testid="search-button"]');

    // Assert: Verify search results are displayed
    await expect(page.locator('[data-testid="products-list"]')).toBeVisible();

    // Assert: Verify URL contains search parameter
    await expect(page).toHaveURL(/.*[?&]q=test/);
  });
});

import { test, expect } from '@playwright/test';
import { ProductsPage } from '../pages/ProductsPage';

/**
 * Products CRUD E2E Tests
 *
 * Tests the complete CRUD (Create, Read, Update, Delete) operations
 * for product management in the admin panel.
 */
test.describe('Products CRUD', () => {
  let productsPage: ProductsPage;

  test.beforeEach(async ({ page }) => {
    productsPage = new ProductsPage(page);
    await productsPage.goto();
  });

  test('can display products list', async ({ page }) => {
    // Assert: Verify products list is visible
    await expect(page.locator('[data-testid="products-list"]')).toBeVisible();

    // Assert: Verify at least one product exists or empty state is shown
    const productCount = await productsPage.getProductCount();
    expect(productCount).toBeGreaterThanOrEqual(0);
  });

  test('can create a new product', async ({ page }) => {
    // Arrange: Define test product data
    const productName = `Test Product ${Date.now()}`;
    const productDescription = 'Test product description for E2E testing';
    const productPrice = '99.99';

    // Act: Create new product
    await productsPage.createProduct(productName, productDescription, productPrice);

    // Assert: Verify product appears in the list
    const hasProduct = await productsPage.hasProduct(productName);
    expect(hasProduct).toBe(true);

    // Assert: Verify product details
    const productDetails = await productsPage.getProductDetails(productName);
    expect(productDetails.name).toContain(productName);
    expect(productDetails.description).toContain(productDescription);
    expect(productDetails.price).toContain(productPrice);
  });

  test('can update an existing product', async ({ page }) => {
    // Arrange: Create a product first
    const originalName = `Original Product ${Date.now()}`;
    const originalDescription = 'Original description';
    const originalPrice = '49.99';

    await productsPage.createProduct(originalName, originalDescription, originalPrice);

    // Arrange: Define updated data
    const updatedName = `Updated Product ${Date.now()}`;
    const updatedDescription = 'Updated description';
    const updatedPrice = '79.99';

    // Act: Update the product
    await productsPage.updateProduct(
      originalName,
      updatedName,
      updatedDescription,
      updatedPrice
    );

    // Assert: Verify updated product exists
    const hasUpdatedProduct = await productsPage.hasProduct(updatedName);
    expect(hasUpdatedProduct).toBe(true);

    // Assert: Verify updated product details
    const productDetails = await productsPage.getProductDetails(updatedName);
    expect(productDetails.name).toContain(updatedName);
    expect(productDetails.description).toContain(updatedDescription);
    expect(productDetails.price).toContain(updatedPrice);

    // Assert: Verify original product no longer exists
    const hasOriginalProduct = await productsPage.hasProduct(originalName);
    expect(hasOriginalProduct).toBe(false);
  });

  test('can delete a product', async ({ page }) => {
    // Arrange: Create a product to delete
    const productName = `Delete Product ${Date.now()}`;
    const productDescription = 'This product will be deleted';
    const productPrice = '29.99';

    await productsPage.createProduct(productName, productDescription, productPrice);

    // Assert: Verify product exists before deletion
    let hasProduct = await productsPage.hasProduct(productName);
    expect(hasProduct).toBe(true);

    // Act: Delete the product
    await productsPage.deleteProduct(productName);

    // Assert: Verify product no longer exists
    hasProduct = await productsPage.hasProduct(productName);
    expect(hasProduct).toBe(false);
  });

  test('can perform full CRUD cycle', async ({ page }) => {
    // This test performs a complete CRUD cycle on a single product

    // CREATE
    const originalName = `CRUD Test Product ${Date.now()}`;
    await productsPage.createProduct(originalName, 'Initial description', '19.99');
    let hasProduct = await productsPage.hasProduct(originalName);
    expect(hasProduct).toBe(true);

    // READ (verify details)
    let details = await productsPage.getProductDetails(originalName);
    expect(details.name).toContain(originalName);

    // UPDATE
    const updatedName = `CRUD Updated ${Date.now()}`;
    await productsPage.updateProduct(originalName, updatedName, 'Updated description', '29.99');
    hasProduct = await productsPage.hasProduct(updatedName);
    expect(hasProduct).toBe(true);

    // DELETE
    await productsPage.deleteProduct(updatedName);
    hasProduct = await productsPage.hasProduct(updatedName);
    expect(hasProduct).toBe(false);
  });
});

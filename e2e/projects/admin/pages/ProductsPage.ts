import { type Page, expect } from '@playwright/test';

/**
 * Products Page Object
 *
 * Provides methods to interact with the admin products page
 * for CRUD operations testing.
 *
 * @example
 * ```typescript
 * const productsPage = new ProductsPage(page);
 * await productsPage.goto();
 * await productsPage.createProduct('Product Name', 'Description', '99.99');
 * ```
 */
export class ProductsPage {
  constructor(private readonly page: Page) {}

  /**
   * Navigate to the products list page
   */
  async goto() {
    await this.page.goto('/products');
    await this.page.waitForSelector('[data-testid="products-list"]', {
      state: 'visible',
    });
  }

  /**
   * Click the "Create Product" button
   */
  async clickCreateButton() {
    await this.page.click('[data-testid="create-product-button"]');
    await this.page.waitForSelector('[data-testid="product-form"]', {
      state: 'visible',
    });
  }

  /**
   * Fill in the product form
   *
   * @param name - Product name
   * @param description - Product description
   * @param price - Product price
   */
  async fillProductForm(name: string, description: string, price: string) {
    await this.page.fill('[data-testid="product-name"]', name);
    await this.page.fill('[data-testid="product-description"]', description);
    await this.page.fill('[data-testid="product-price"]', price);
  }

  /**
   * Submit the product form
   */
  async submitForm() {
    await this.page.click('[data-testid="submit-product"]');
    await this.page.waitForSelector('[data-testid="products-list"]', {
      state: 'visible',
    });
  }

  /**
   * Create a new product
   *
   * @param name - Product name
   * @param description - Product description
   * @param price - Product price
   */
  async createProduct(name: string, description: string, price: string) {
    await this.clickCreateButton();
    await this.fillProductForm(name, description, price);
    await this.submitForm();
  }

  /**
   * Click edit button for a specific product
   *
   * @param productName - Name of the product to edit
   */
  async clickEditButton(productName: string) {
    const productRow = this.page.locator(`[data-testid="product-row"]`, {
      hasText: productName,
    });
    await productRow.locator('[data-testid="edit-button"]').click();
    await this.page.waitForSelector('[data-testid="product-form"]', {
      state: 'visible',
    });
  }

  /**
   * Update an existing product
   *
   * @param productName - Current name of the product
   * @param newName - New product name
   * @param newDescription - New product description
   * @param newPrice - New product price
   */
  async updateProduct(
    productName: string,
    newName: string,
    newDescription: string,
    newPrice: string
  ) {
    await this.clickEditButton(productName);
    await this.fillProductForm(newName, newDescription, newPrice);
    await this.submitForm();
  }

  /**
   * Delete a product
   *
   * @param productName - Name of the product to delete
   */
  async deleteProduct(productName: string) {
    const productRow = this.page.locator(`[data-testid="product-row"]`, {
      hasText: productName,
    });
    await productRow.locator('[data-testid="delete-button"]').click();

    // Confirm deletion in dialog
    await this.page.click('[data-testid="confirm-delete"]');

    // Wait for product to be removed from list
    await this.page.waitForTimeout(500); // Brief wait for deletion animation
  }

  /**
   * Check if a product exists in the list
   *
   * @param productName - Name of the product to check
   * @returns true if product exists, false otherwise
   */
  async hasProduct(productName: string): Promise<boolean> {
    const productRow = this.page.locator(`[data-testid="product-row"]`, {
      hasText: productName,
    });
    return await productRow.isVisible();
  }

  /**
   * Get total number of products in the list
   *
   * @returns Number of products
   */
  async getProductCount(): Promise<number> {
    const products = this.page.locator('[data-testid="product-row"]');
    return await products.count();
  }

  /**
   * Get product details by name
   *
   * @param productName - Name of the product
   * @returns Object containing product details
   */
  async getProductDetails(productName: string): Promise<{
    name: string;
    description: string;
    price: string;
  }> {
    const productRow = this.page.locator(`[data-testid="product-row"]`, {
      hasText: productName,
    });

    const name = await productRow.locator('[data-testid="product-name"]').textContent() || '';
    const description = await productRow.locator('[data-testid="product-description"]').textContent() || '';
    const price = await productRow.locator('[data-testid="product-price"]').textContent() || '';

    return { name, description, price };
  }
}

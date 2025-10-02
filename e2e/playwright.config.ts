import { defineConfig, devices } from '@playwright/test';
import 'dotenv/config';

/**
 * Playwright E2E Test Configuration
 *
 * Laravel + Next.js B2C Application E2E Testing
 * - Monorepo support (admin-app / user-app)
 * - Laravel Sanctum authentication integration
 * - Parallel execution with sharding support
 */
export default defineConfig({
  testDir: './projects',
  timeout: 60 * 1000, // 60 seconds per test
  expect: {
    timeout: 10 * 1000, // 10 seconds for assertions
  },
  fullyParallel: true,
  forbidOnly: !!process.env.CI,
  workers: process.env.CI ? 4 : undefined,
  retries: process.env.CI ? 2 : 0,
  reporter: [
    ['list'],
    ['html', { open: 'never', outputFolder: 'reports/html' }],
    ['junit', { outputFile: 'reports/junit.xml' }],
  ],
  use: {
    trace: 'retain-on-failure',
    screenshot: 'only-on-failure',
    video: 'retain-on-failure',
    ignoreHTTPSErrors: true,
  },

  projects: [
    {
      name: 'setup',
      testMatch: /global\.setup\.ts/,
    },
    {
      name: 'admin-chromium',
      testDir: './projects/admin/tests',
      use: {
        ...devices['Desktop Chrome'],
        baseURL: process.env.E2E_ADMIN_URL ?? 'http://localhost:3001',
        storageState: 'storage/admin.json',
      },
      dependencies: ['setup'],
    },
    {
      name: 'user-chromium',
      testDir: './projects/user/tests',
      use: {
        ...devices['Desktop Chrome'],
        baseURL: process.env.E2E_USER_URL ?? 'http://localhost:3000',
        storageState: 'storage/user.json',
      },
      dependencies: ['setup'],
    },
  ],
});

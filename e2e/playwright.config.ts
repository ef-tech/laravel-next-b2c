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
  // globalSetup: require.resolve('./fixtures/global-setup'), // Disabled: Enable once Laravel auth endpoints (/api/login, /api/user) are implemented
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
    // Disabled: Laravel auth endpoints not implemented yet
    // {
    //   name: 'setup',
    //   testMatch: /global\.setup\.ts/,
    // },
    {
      name: 'admin-chromium',
      testDir: './projects/admin/tests',
      use: {
        ...devices['Desktop Chrome'],
        baseURL: process.env.E2E_ADMIN_URL ?? 'http://localhost:13002',
        // storageState: 'storage/admin.json', // Disabled: Enable once globalSetup is enabled
      },
      // dependencies: ['setup'], // Disabled: no setup project
    },
    {
      name: 'user-chromium',
      testDir: './projects/user/tests',
      use: {
        ...devices['Desktop Chrome'],
        baseURL: process.env.E2E_USER_URL ?? 'http://localhost:13001',
        // storageState: 'storage/user.json', // Disabled: Enable once globalSetup is enabled
      },
      // dependencies: ['setup'], // Disabled: no setup project
    },
  ],
});

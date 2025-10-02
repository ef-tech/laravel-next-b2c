import { request, type FullConfig } from '@playwright/test';
import { sanctumLogin } from '../helpers/sanctum';
import fs from 'node:fs';
import path from 'node:path';
import 'dotenv/config';

/**
 * Global Setup for E2E Tests
 *
 * Executes before all tests to:
 * 1. Authenticate admin user via Laravel Sanctum
 * 2. Authenticate regular user via Laravel Sanctum
 * 3. Save authentication state to storage files
 *
 * These storage files are loaded by Playwright projects to maintain
 * authenticated sessions across all tests.
 */
async function globalSetup(config: FullConfig) {
  // Validate required environment variables
  const requiredEnvVars = [
    'E2E_ADMIN_EMAIL',
    'E2E_ADMIN_PASSWORD',
    'E2E_USER_EMAIL',
    'E2E_USER_PASSWORD',
  ];

  const missingVars = requiredEnvVars.filter((varName) => !process.env[varName]);
  if (missingVars.length > 0) {
    throw new Error(
      `Missing required environment variables: ${missingVars.join(', ')}\n` +
        'Please ensure .env file is configured with E2E authentication credentials.'
    );
  }

  const apiBaseURL = process.env.E2E_API_URL ?? 'http://localhost:13000';

  // Ensure storage directory exists
  const storageDir = path.join(__dirname, '..', 'storage');
  fs.mkdirSync(storageDir, { recursive: true });

  // Admin authentication
  console.log('🔐 Authenticating admin user...');
  const adminApi = await request.newContext({ baseURL: apiBaseURL });
  try {
    const adminStorageState = await sanctumLogin(
      adminApi,
      process.env.E2E_ADMIN_EMAIL!,
      process.env.E2E_ADMIN_PASSWORD!
    );
    fs.writeFileSync(
      path.join(storageDir, 'admin.json'),
      JSON.stringify(adminStorageState, null, 2)
    );
    console.log('✅ Admin authentication successful');
  } catch (error) {
    console.error('❌ Admin authentication failed:', error);
    throw error;
  } finally {
    await adminApi.dispose();
  }

  // User authentication
  console.log('🔐 Authenticating regular user...');
  const userApi = await request.newContext({ baseURL: apiBaseURL });
  try {
    const userStorageState = await sanctumLogin(
      userApi,
      process.env.E2E_USER_EMAIL!,
      process.env.E2E_USER_PASSWORD!
    );
    fs.writeFileSync(
      path.join(storageDir, 'user.json'),
      JSON.stringify(userStorageState, null, 2)
    );
    console.log('✅ User authentication successful');
  } catch (error) {
    console.error('❌ User authentication failed:', error);
    throw error;
  } finally {
    await userApi.dispose();
  }

  console.log('🎉 Global setup completed');
}

export default globalSetup;

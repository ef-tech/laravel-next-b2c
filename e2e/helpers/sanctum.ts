import type { APIRequestContext } from '@playwright/test';

/**
 * Laravel Sanctum Authentication Helper
 *
 * Handles CSRF token retrieval and login with Sanctum authentication
 *
 * Note: Assumes APIRequestContext is created with baseURL set.
 * All URLs are relative to the baseURL of the context.
 *
 * @param api - Playwright APIRequestContext instance (with baseURL configured)
 * @param email - User email for authentication
 * @param password - User password for authentication
 * @returns Authentication state object with cookies and session
 */
export async function sanctumLogin(
  api: APIRequestContext,
  email: string,
  password: string
) {
  // Step 1: Get CSRF cookie from Laravel Sanctum
  const csrfResponse = await api.get('/sanctum/csrf-cookie', {
    headers: {
      'X-Requested-With': 'XMLHttpRequest',
    },
  });

  if (!csrfResponse.ok()) {
    throw new Error(
      `CSRF token retrieval failed: ${csrfResponse.status()} ${csrfResponse.statusText()}`
    );
  }

  // Step 2: Extract and decode XSRF-TOKEN from storage state
  const storageState = await api.storageState();
  const xsrfCookie = storageState.cookies.find(
    (cookie) => cookie.name === 'XSRF-TOKEN'
  );

  if (!xsrfCookie) {
    throw new Error('XSRF-TOKEN cookie not found after CSRF request');
  }

  const token = decodeURIComponent(xsrfCookie.value);

  // Step 3: Execute login with CSRF token
  const loginResponse = await api.post('/login', {
    headers: {
      'X-Requested-With': 'XMLHttpRequest',
      'X-XSRF-TOKEN': token,
      'Content-Type': 'application/json',
    },
    data: {
      email,
      password,
    },
  });

  if (!loginResponse.ok()) {
    throw new Error(
      `Login failed: ${loginResponse.status()} ${loginResponse.statusText()}`
    );
  }

  // Step 4: Verify authentication status
  const userResponse = await api.get('/api/user', {
    headers: {
      'X-Requested-With': 'XMLHttpRequest',
    },
  });

  if (!userResponse.ok()) {
    throw new Error(
      `Authentication verification failed: ${userResponse.status()} ${userResponse.statusText()}`
    );
  }

  // Step 5: Return authenticated storage state
  return await api.storageState();
}

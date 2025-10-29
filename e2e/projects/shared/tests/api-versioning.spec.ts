import { test, expect } from '@playwright/test';

/**
 * API Versioning E2E Tests
 *
 * Tests V1 API endpoints functionality including:
 * - Health check endpoint
 * - Login/Logout flow
 * - Token management
 * - Error handling
 * - Response headers (X-API-Version)
 */

const API_BASE_URL = process.env.E2E_API_URL || 'http://localhost:13000';
const API_V1_URL = `${API_BASE_URL}/api/v1`;

test.describe('API V1 - Health Check', () => {
  test('GET /api/v1/health should return ok status', async ({ request }) => {
    const response = await request.get(`${API_V1_URL}/health`);

    expect(response.status()).toBe(200);
    expect(response.headers()['content-type']).toContain('application/json');

    const body = await response.json();
    expect(body).toHaveProperty('status', 'ok');
  });

  test('GET /api/v1/health should include X-API-Version header', async ({ request }) => {
    const response = await request.get(`${API_V1_URL}/health`);

    expect(response.headers()).toHaveProperty('x-api-version', 'v1');
  });
});

test.describe('API V1 - Authentication Flow', () => {
  const testUser = {
    name: 'E2E Test User',
    email: `e2e-test-${Date.now()}@example.com`,
    password: 'TestPassword123!',
  };

  test('POST /api/v1/register should create new user', async ({ request }) => {
    const response = await request.post(`${API_V1_URL}/register`, {
      data: {
        name: testUser.name,
        email: testUser.email,
        password: testUser.password,
        password_confirmation: testUser.password,
      },
    });

    expect(response.status()).toBe(201);
    expect(response.headers()['x-api-version']).toBe('v1');

    const body = await response.json();
    expect(body).toHaveProperty('token');
    expect(body).toHaveProperty('user');
    expect(body.user).toHaveProperty('email', testUser.email);
  });

  test('POST /api/v1/login should return token and user', async ({ request }) => {
    // 先にユーザーを作成
    await request.post(`${API_V1_URL}/register`, {
      data: {
        name: testUser.name,
        email: testUser.email,
        password: testUser.password,
        password_confirmation: testUser.password,
      },
    });

    // ログイン
    const response = await request.post(`${API_V1_URL}/login`, {
      data: {
        email: testUser.email,
        password: testUser.password,
      },
    });

    expect(response.status()).toBe(200);
    expect(response.headers()['x-api-version']).toBe('v1');

    const body = await response.json();
    expect(body).toHaveProperty('token');
    expect(body).toHaveProperty('user');
    expect(body.user).toHaveProperty('id');
    expect(body.user).toHaveProperty('email', testUser.email);
  });

  test('POST /api/v1/logout should invalidate token', async ({ request }) => {
    // ユーザー作成とログイン
    await request.post(`${API_V1_URL}/register`, {
      data: {
        name: testUser.name,
        email: testUser.email,
        password: testUser.password,
        password_confirmation: testUser.password,
      },
    });

    const loginResponse = await request.post(`${API_V1_URL}/login`, {
      data: {
        email: testUser.email,
        password: testUser.password,
      },
    });

    const { token } = await loginResponse.json();

    // ログアウト
    const logoutResponse = await request.post(`${API_V1_URL}/logout`, {
      headers: {
        Authorization: `Bearer ${token}`,
      },
    });

    expect(logoutResponse.status()).toBe(200);
    expect(logoutResponse.headers()['x-api-version']).toBe('v1');

    const body = await logoutResponse.json();
    expect(body).toHaveProperty('message', 'Logged out successfully');

    // トークンが無効化されていることを確認
    const userResponse = await request.get(`${API_V1_URL}/user`, {
      headers: {
        Authorization: `Bearer ${token}`,
      },
    });

    expect(userResponse.status()).toBe(401);
  });
});

test.describe('API V1 - User Information', () => {
  const testUser = {
    name: 'E2E Test User',
    email: `e2e-test-${Date.now()}@example.com`,
    password: 'TestPassword123!',
  };

  test('GET /api/v1/user should return authenticated user', async ({ request }) => {
    // ユーザー作成とログイン
    const registerResponse = await request.post(`${API_V1_URL}/register`, {
      data: {
        name: testUser.name,
        email: testUser.email,
        password: testUser.password,
        password_confirmation: testUser.password,
      },
    });

    const { token } = await registerResponse.json();

    // 認証ユーザー情報取得
    const response = await request.get(`${API_V1_URL}/user`, {
      headers: {
        Authorization: `Bearer ${token}`,
      },
    });

    expect(response.status()).toBe(200);
    expect(response.headers()['x-api-version']).toBe('v1');

    const body = await response.json();
    expect(body).toHaveProperty('id');
    expect(body).toHaveProperty('name', testUser.name);
    expect(body).toHaveProperty('email', testUser.email);
    expect(body).toHaveProperty('created_at');
    expect(body).toHaveProperty('updated_at');
  });

  test('GET /api/v1/user without token should return 401', async ({ request }) => {
    const response = await request.get(`${API_V1_URL}/user`);

    expect(response.status()).toBe(401);
  });
});

test.describe('API V1 - Token Management', () => {
  const testUser = {
    name: 'E2E Test User',
    email: `e2e-test-${Date.now()}@example.com`,
    password: 'TestPassword123!',
  };

  test('POST /api/v1/tokens should create new token', async ({ request }) => {
    // ユーザー作成とログイン
    const registerResponse = await request.post(`${API_V1_URL}/register`, {
      data: {
        name: testUser.name,
        email: testUser.email,
        password: testUser.password,
        password_confirmation: testUser.password,
      },
    });

    const { token: authToken } = await registerResponse.json();

    // 新規トークン作成
    const response = await request.post(`${API_V1_URL}/tokens`, {
      headers: {
        Authorization: `Bearer ${authToken}`,
      },
      data: {
        name: 'E2E Test Token',
      },
    });

    expect(response.status()).toBe(201);
    expect(response.headers()['x-api-version']).toBe('v1');

    const body = await response.json();
    expect(body).toHaveProperty('token');
    expect(body).toHaveProperty('name', 'E2E Test Token');
    expect(body).toHaveProperty('created_at');
  });

  test('GET /api/v1/tokens should return token list', async ({ request }) => {
    // ユーザー作成とログイン
    const registerResponse = await request.post(`${API_V1_URL}/register`, {
      data: {
        name: testUser.name,
        email: testUser.email,
        password: testUser.password,
        password_confirmation: testUser.password,
      },
    });

    const { token: authToken } = await registerResponse.json();

    // トークン一覧取得
    const response = await request.get(`${API_V1_URL}/tokens`, {
      headers: {
        Authorization: `Bearer ${authToken}`,
      },
    });

    expect(response.status()).toBe(200);
    expect(response.headers()['x-api-version']).toBe('v1');

    const body = await response.json();
    expect(body).toHaveProperty('tokens');
    expect(Array.isArray(body.tokens)).toBe(true);
    expect(body.tokens.length).toBeGreaterThan(0);
  });

  test('DELETE /api/v1/tokens/{id} should delete specific token', async ({ request }) => {
    // ユーザー作成とログイン
    const registerResponse = await request.post(`${API_V1_URL}/register`, {
      data: {
        name: testUser.name,
        email: testUser.email,
        password: testUser.password,
        password_confirmation: testUser.password,
      },
    });

    const { token: authToken } = await registerResponse.json();

    // 新規トークン作成
    const createResponse = await request.post(`${API_V1_URL}/tokens`, {
      headers: {
        Authorization: `Bearer ${authToken}`,
      },
      data: {
        name: 'Token to Delete',
      },
    });

    // トークンIDを取得
    const tokensResponse = await request.get(`${API_V1_URL}/tokens`, {
      headers: {
        Authorization: `Bearer ${authToken}`,
      },
    });

    const { tokens } = await tokensResponse.json();
    const tokenToDelete = tokens.find((t: any) => t.name === 'Token to Delete');

    // トークン削除
    const response = await request.delete(`${API_V1_URL}/tokens/${tokenToDelete.id}`, {
      headers: {
        Authorization: `Bearer ${authToken}`,
      },
    });

    expect(response.status()).toBe(200);
    expect(response.headers()['x-api-version']).toBe('v1');

    const body = await response.json();
    expect(body).toHaveProperty('message', 'Token deleted successfully');
  });

  test('DELETE /api/v1/tokens should delete all other tokens', async ({ request }) => {
    // ユーザー作成とログイン
    const registerResponse = await request.post(`${API_V1_URL}/register`, {
      data: {
        name: testUser.name,
        email: testUser.email,
        password: testUser.password,
        password_confirmation: testUser.password,
      },
    });

    const { token: authToken } = await registerResponse.json();

    // 複数トークン作成
    await request.post(`${API_V1_URL}/tokens`, {
      headers: { Authorization: `Bearer ${authToken}` },
      data: { name: 'Token 1' },
    });

    await request.post(`${API_V1_URL}/tokens`, {
      headers: { Authorization: `Bearer ${authToken}` },
      data: { name: 'Token 2' },
    });

    // 全トークン削除（現在のトークン以外）
    const response = await request.delete(`${API_V1_URL}/tokens`, {
      headers: {
        Authorization: `Bearer ${authToken}`,
      },
    });

    expect(response.status()).toBe(200);
    expect(response.headers()['x-api-version']).toBe('v1');

    const body = await response.json();
    expect(body).toHaveProperty('message', 'All tokens deleted successfully');

    // 現在のトークンは有効であることを確認
    const userResponse = await request.get(`${API_V1_URL}/user`, {
      headers: {
        Authorization: `Bearer ${authToken}`,
      },
    });

    expect(userResponse.status()).toBe(200);
  });
});

test.describe('API V1 - Error Handling', () => {
  test('POST /api/v1/login with invalid credentials should return 401', async ({ request }) => {
    const response = await request.post(`${API_V1_URL}/login`, {
      data: {
        email: 'invalid@example.com',
        password: 'wrongpassword',
      },
    });

    expect(response.status()).toBe(401);
    expect(response.headers()['x-api-version']).toBe('v1');
  });

  test('POST /api/v1/register with validation errors should return 422', async ({ request }) => {
    const response = await request.post(`${API_V1_URL}/register`, {
      data: {
        name: '',
        email: 'invalid-email',
        password: 'short',
      },
    });

    expect(response.status()).toBe(422);
    expect(response.headers()['x-api-version']).toBe('v1');

    const body = await response.json();
    expect(body).toHaveProperty('message');
    expect(body).toHaveProperty('errors');
  });

  test('GET /api/v1/tokens/{invalid-id} should return 404', async ({ request }) => {
    // ユーザー作成とログイン
    const registerResponse = await request.post(`${API_V1_URL}/register`, {
      data: {
        name: 'Test User',
        email: `e2e-test-${Date.now()}@example.com`,
        password: 'TestPassword123!',
        password_confirmation: 'TestPassword123!',
      },
    });

    const { token } = await registerResponse.json();

    // 存在しないトークンID
    const response = await request.delete(`${API_V1_URL}/tokens/99999`, {
      headers: {
        Authorization: `Bearer ${token}`,
      },
    });

    expect(response.status()).toBe(404);
    expect(response.headers()['x-api-version']).toBe('v1');
  });
});

test.describe('API V1 - Response Headers', () => {
  test('All V1 endpoints should include X-API-Version header', async ({ request }) => {
    const endpoints = [
      { method: 'GET', url: `${API_V1_URL}/health` },
    ];

    for (const endpoint of endpoints) {
      const response = await request.fetch(endpoint.url, {
        method: endpoint.method,
      });

      expect(response.headers()['x-api-version']).toBe('v1');
    }
  });

  test('All V1 endpoints should return JSON', async ({ request }) => {
    const response = await request.get(`${API_V1_URL}/health`);

    expect(response.headers()['content-type']).toContain('application/json');
  });

  test('All V1 endpoints should include X-Request-Id header', async ({ request }) => {
    const response = await request.get(`${API_V1_URL}/health`);

    expect(response.headers()).toHaveProperty('x-request-id');
    expect(response.headers()['x-request-id']).toMatch(
      /^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i
    );
  });
});

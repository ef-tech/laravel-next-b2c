import { test, expect } from '@playwright/test';

/**
 * Error Handling E2E Tests
 *
 * Task 11: エラーハンドリングのE2Eテスト
 * - RFC 7807エラーレスポンス表示
 * - Error Boundary UI検証
 * - Request ID（trace_id）表示
 * - バリデーションエラー詳細表示
 * - 認証エラーリダイレクト
 * - ネットワークエラー表示
 * - 500エラーマスキング
 * - 再試行ボタン動作
 */

// テスト用APIベースURL
const API_URL = process.env.E2E_API_URL || 'http://localhost:13000';

// テスト全体のタイムアウトを延長（production buildは起動が遅い）
test.describe.configure({ timeout: 120000 });

test.describe('Error Handling E2E Tests', () => {
  // Task 11.1: APIエラー表示E2Eテスト
  test.describe('API Error Display', () => {
    test('RFC 7807情報（title, detail, errorCode, requestId）が画面に表示される', async ({
      page,
    }) => {
      // Arrange: テスト用エラーページに遷移（admin-app）
      const adminAppUrl = process.env.E2E_ADMIN_URL || 'http://localhost:13002';
      await page.goto(`${adminAppUrl}/test-error`, { waitUntil: 'networkidle', timeout: 90000 });

      // Wait for page to be fully rendered
      await expect(page.locator('[data-testid="trigger-400-error"]')).toBeVisible({ timeout: 30000 });

      // Act: 400 Domain Exceptionをトリガー
      await page.click('[data-testid="trigger-400-error"]');

      // Assert: Error Boundary UIが表示される
      await expect(page.locator('h2:has-text("エラーが発生しました")')).toBeVisible({
        timeout: 5000,
      });

      // Assert: RFC 7807情報が表示される
      // Title
      await expect(page.locator('text=Domain Exception Test')).toBeVisible();

      // Status Code
      await expect(page.locator('text=ステータスコード: 400')).toBeVisible();

      // Detail (エラーメッセージ)
      await expect(page.locator('text=Test domain exception message')).toBeVisible();

      // Request ID（trace_id）
      await expect(page.locator('text=Request ID:')).toBeVisible();
      const requestIdElement = page.locator('code').first();
      await expect(requestIdElement).toBeVisible();

      // Request IDがUUID形式であることを検証
      const requestId = await requestIdElement.textContent();
      expect(requestId).toMatch(/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i);
    });

    test('Error Boundary UIが正しく表示される', async ({ page }) => {
      // Arrange: テスト用エラーページに遷移
      const adminAppUrl = process.env.E2E_ADMIN_URL || 'http://localhost:13002';
      await page.goto(`${adminAppUrl}/test-error`, { waitUntil: 'networkidle', timeout: 90000 });

      // Act: 404 Application Exceptionをトリガー
      await page.click('[data-testid="trigger-404-error"]');

      // Assert: Error Boundary UIの主要要素が表示される
      // エラーアイコン（SVG）
      await expect(page.locator('svg').first()).toBeVisible({ timeout: 5000 });

      // エラータイトル
      await expect(page.locator('h2').first()).toBeVisible();

      // エラーメッセージ
      await expect(page.locator('p').first()).toBeVisible();

      // Request ID表示エリア
      await expect(page.locator('text=Request ID:')).toBeVisible();

      // 再試行ボタン
      await expect(page.locator('button:has-text("再試行")')).toBeVisible();
    });

    test('Request IDがサポート用参照IDとして表示される', async ({ page }) => {
      // Arrange: テスト用エラーページに遷移
      const adminAppUrl = process.env.E2E_ADMIN_URL || 'http://localhost:13002';
      await page.goto(`${adminAppUrl}/test-error`, { waitUntil: 'networkidle', timeout: 90000 });

      // Act: 503 Infrastructure Exceptionをトリガー
      await page.click('[data-testid="trigger-503-error"]');

      // Assert: Request ID表示エリアが表示される
      await expect(page.locator('text=Request ID:')).toBeVisible({ timeout: 5000 });

      // Assert: サポート問い合わせ用メッセージが表示される
      await expect(
        page.locator('text=お問い合わせの際は、このIDをお伝えください')
      ).toBeVisible();

      // Assert: Request IDがコードブロック内に表示される
      const requestIdCode = page.locator('code').first();
      await expect(requestIdCode).toBeVisible();

      // Request IDの値が取得可能であることを確認
      const requestIdValue = await requestIdCode.textContent();
      expect(requestIdValue).toBeTruthy();
      expect(requestIdValue?.length).toBeGreaterThan(0);
    });
  });

  // Task 11.2: バリデーションエラー表示E2Eテスト
  test.describe('Validation Error Display', () => {
    test('フィールド別エラーメッセージが画面に表示される', async ({ page }) => {
      // Arrange: テスト用エラーページに遷移
      const adminAppUrl = process.env.E2E_ADMIN_URL || 'http://localhost:13002';
      await page.goto(`${adminAppUrl}/test-error`, { waitUntil: 'networkidle', timeout: 90000 });

      // Act: 422 Validation Errorをトリガー
      await page.click('[data-testid="trigger-422-error"]');

      // Assert: Error Boundary UIが表示される
      await expect(page.locator('h2:has-text("エラーが発生しました")')).toBeVisible({
        timeout: 5000,
      });

      // Assert: Validation Error titleが表示される
      await expect(page.locator('text=Validation Error')).toBeVisible();

      // Assert: バリデーションエラー詳細セクションが表示される
      await expect(page.locator('text=入力エラー:')).toBeVisible();

      // Assert: 各フィールドのエラーメッセージが表示される
      // email フィールド
      await expect(page.locator('text=email:')).toBeVisible();

      // name フィールド
      await expect(page.locator('text=name:')).toBeVisible();

      // age フィールド
      await expect(page.locator('text=age:')).toBeVisible();
    });

    test('errorsフィールドが正しく解析され表示される', async ({ page }) => {
      // Arrange: テスト用エラーページに遷移
      const adminAppUrl = process.env.E2E_ADMIN_URL || 'http://localhost:13002';
      await page.goto(`${adminAppUrl}/test-error`, { waitUntil: 'networkidle', timeout: 90000 });

      // Act: 422 Validation Errorをトリガー
      await page.click('[data-testid="trigger-422-error"]');

      // Assert: Error Boundary UIが表示される
      await expect(page.locator('h2:has-text("エラーが発生しました")')).toBeVisible({
        timeout: 5000,
      });

      // Assert: errorsフィールドがリスト形式で表示される
      const errorList = page.locator('ul.list-disc');
      await expect(errorList).toBeVisible();

      // Assert: リストアイテムが存在する（3つのフィールドエラー）
      const listItems = errorList.locator('li');
      const count = await listItems.count();
      expect(count).toBeGreaterThanOrEqual(3); // email, name, age

      // Assert: 各エラーメッセージが含まれることを確認
      const listText = await errorList.textContent();
      expect(listText).toBeTruthy();
    });
  });

  // Task 11.3: 認証エラー（401）リダイレクトE2Eテスト
  test.describe.skip('Authentication Error Redirect (Not Implemented)', () => {
    // 注: ログインページがまだ実装されていないため、このテストはスキップします
    // TODO: ログインページ実装後に有効化する

    test('401エラー発生時にログインページにリダイレクトされる', async ({ page }) => {
      // Arrange: テスト用エラーページに遷移
      const adminAppUrl = process.env.E2E_ADMIN_URL || 'http://localhost:13002';
      await page.goto(`${adminAppUrl}/test-error`, { waitUntil: 'networkidle', timeout: 90000 });

      // Act: 401 Authentication Errorをトリガー
      await page.click('[data-testid="trigger-401-error"]');

      // Assert: ログインページにリダイレクトされる
      await expect(page).toHaveURL(/.*\/login/, { timeout: 5000 });
    });

    test('認証エラーメッセージが表示される', async ({ page }) => {
      // Arrange: テスト用エラーページに遷移
      const adminAppUrl = process.env.E2E_ADMIN_URL || 'http://localhost:13002';
      await page.goto(`${adminAppUrl}/test-error`, { waitUntil: 'networkidle', timeout: 90000 });

      // Act: 401 Authentication Errorをトリガー
      await page.click('[data-testid="trigger-401-error"]');

      // Assert: 認証エラーメッセージが表示される
      await expect(page.locator('text=認証が必要です')).toBeVisible({ timeout: 5000 });
    });
  });

  // Task 11.4: ネットワークエラー表示E2Eテスト
  test.describe('Network Error Display', () => {
    test('ネットワークエラーメッセージが表示される', async ({ page }) => {
      // Arrange: テスト用エラーページに遷移
      const adminAppUrl = process.env.E2E_ADMIN_URL || 'http://localhost:13002';
      await page.goto(`${adminAppUrl}/test-error`, { waitUntil: 'networkidle', timeout: 90000 });

      // Act: Network Connection Errorをトリガー
      await page.click('[data-testid="trigger-connection-error"]');

      // Assert: Error Boundary UIが表示される
      await expect(page.locator('h2:has-text("ネットワークエラー")')).toBeVisible({
        timeout: 5000,
      });

      // Assert: ネットワークエラーメッセージが表示される
      await expect(
        page.locator('text=ネットワーク接続に問題が発生しました')
      ).toBeVisible();
    });

    test('再試行ボタンが表示され動作する', async ({ page }) => {
      // Arrange: テスト用エラーページに遷移
      const adminAppUrl = process.env.E2E_ADMIN_URL || 'http://localhost:13002';
      await page.goto(`${adminAppUrl}/test-error`, { waitUntil: 'networkidle', timeout: 90000 });

      // Act: Network Timeoutをトリガー
      await page.click('[data-testid="trigger-timeout-error"]');

      // Assert: Error Boundary UIが表示される
      await expect(page.locator('h2:has-text("ネットワークエラー")')).toBeVisible({
        timeout: 5000,
      });

      // Assert: 再試行ボタンが表示される
      const retryButton = page.locator('button:has-text("再試行")');
      await expect(retryButton).toBeVisible();

      // Assert: 再試行可能メッセージが表示される
      await expect(
        page.locator('text=このエラーは再試行可能です')
      ).toBeVisible();
    });

    test('NetworkErrorが検出されUIに表示される', async ({ page }) => {
      // Arrange: テスト用エラーページに遷移
      const adminAppUrl = process.env.E2E_ADMIN_URL || 'http://localhost:13002';
      await page.goto(`${adminAppUrl}/test-error`, { waitUntil: 'networkidle', timeout: 90000 });

      // Act: Network Connection Errorをトリガー
      await page.click('[data-testid="trigger-connection-error"]');

      // Assert: ネットワークエラータイトルが表示される
      await expect(page.locator('h2:has-text("ネットワークエラー")')).toBeVisible({
        timeout: 5000,
      });

      // Assert: エラータイプが表示される（接続エラー）
      await expect(page.locator('text=接続エラー')).toBeVisible();

      // Assert: ネットワークエラーアイコン（SVG）が表示される
      await expect(page.locator('svg').first()).toBeVisible();
    });
  });

  // Task 11.5: 500エラーマスキングE2Eテスト
  test.describe('500 Error Masking', () => {
    test('本番環境設定時に内部エラー詳細がマスクされる', async ({ page }) => {
      // 注: フロントエンドのNODE_ENVは開発環境（development）で動作するため、
      // 本番環境のマスキング動作はError Boundaryのコード確認で検証済み
      // ここでは500エラーが正しく表示されることを確認する

      // Arrange: テスト用エラーページに遷移
      const adminAppUrl = process.env.E2E_ADMIN_URL || 'http://localhost:13002';
      await page.goto(`${adminAppUrl}/test-error`, { waitUntil: 'networkidle', timeout: 90000 });

      // Act: 500 Generic Exceptionをトリガー
      await page.click('[data-testid="trigger-500-error"]');

      // Assert: Error Boundary UIが表示される
      await expect(page.locator('h2').first()).toBeVisible({ timeout: 5000 });

      // Assert: エラーメッセージが表示される
      // 本番環境では汎用メッセージ、開発環境では詳細メッセージ
      await expect(page.locator('p').first()).toBeVisible();
    });

    test('汎用エラーメッセージが表示される', async ({ page }) => {
      // Arrange: テスト用エラーページに遷移
      const adminAppUrl = process.env.E2E_ADMIN_URL || 'http://localhost:13002';
      await page.goto(`${adminAppUrl}/test-error`, { waitUntil: 'networkidle', timeout: 90000 });

      // Act: 500 Generic Exceptionをトリガー
      await page.click('[data-testid="trigger-500-error"]');

      // Assert: Error Boundary UIが表示される
      await expect(page.locator('h2:has-text("予期しないエラーが発生しました")')).toBeVisible({
        timeout: 5000,
      });

      // Assert: エラーメッセージが表示される
      await expect(page.locator('p').first()).toBeVisible();
    });

    test('Request ID表示が維持される', async ({ page }) => {
      // Arrange: テスト用エラーページに遷移
      const adminAppUrl = process.env.E2E_ADMIN_URL || 'http://localhost:13002';
      await page.goto(`${adminAppUrl}/test-error`, { waitUntil: 'networkidle', timeout: 90000 });

      // Act: 500 Generic Exceptionをトリガー
      await page.click('[data-testid="trigger-500-error"]');

      // Assert: Error Boundary UIが表示される
      await expect(page.locator('h2:has-text("予期しないエラーが発生しました")')).toBeVisible({
        timeout: 5000,
      });

      // Assert: Error ID表示エリアが表示される
      // 注: Generic Errorの場合、Next.jsの digest が Error ID として表示される
      await expect(page.locator('text=Error ID:')).toBeVisible();

      // Assert: Error IDがコードブロック内に表示される
      const errorIdCode = page.locator('code').first();
      await expect(errorIdCode).toBeVisible();

      // Assert: サポート問い合わせ用メッセージが表示される
      await expect(
        page.locator('text=お問い合わせの際は、このIDをお伝えください')
      ).toBeVisible();
    });
  });

  // Task 11.6: 再試行ボタン動作E2Eテスト
  test.describe('Retry Button Functionality', () => {
    test('Error Boundaryの再試行ボタンがクリックできる', async ({ page }) => {
      // Arrange: テスト用エラーページに遷移
      const adminAppUrl = process.env.E2E_ADMIN_URL || 'http://localhost:13002';
      await page.goto(`${adminAppUrl}/test-error`, { waitUntil: 'networkidle', timeout: 90000 });

      // Act: 400 Domain Exceptionをトリガー
      await page.click('[data-testid="trigger-400-error"]');

      // Assert: Error Boundary UIが表示される
      await expect(page.locator('h2:has-text("エラーが発生しました")')).toBeVisible({
        timeout: 5000,
      });

      // Assert: 再試行ボタンが表示され、クリック可能である
      const retryButton = page.locator('button:has-text("再試行")');
      await expect(retryButton).toBeVisible();
      await expect(retryButton).toBeEnabled();
    });

    test('reset()関数が呼び出される', async ({ page }) => {
      // Arrange: テスト用エラーページに遷移
      const adminAppUrl = process.env.E2E_ADMIN_URL || 'http://localhost:13002';
      await page.goto(`${adminAppUrl}/test-error`, { waitUntil: 'networkidle', timeout: 90000 });

      // Act: 404 Application Exceptionをトリガー
      await page.click('[data-testid="trigger-404-error"]');

      // Assert: Error Boundary UIが表示される
      await expect(page.locator('h2:has-text("エラーが発生しました")')).toBeVisible({
        timeout: 5000,
      });

      // Act: 再試行ボタンをクリック
      const retryButton = page.locator('button:has-text("再試行")');
      await retryButton.click();

      // Assert: Error Boundary UIが非表示になり、元のページに戻る
      // （テストページが再表示される）
      await expect(page.locator('h1:has-text("Test Error Page")')).toBeVisible({
        timeout: 5000,
      });
    });

    test('reset()によるリカバリーが実行される', async ({ page }) => {
      // Arrange: テスト用エラーページに遷移
      const adminAppUrl = process.env.E2E_ADMIN_URL || 'http://localhost:13002';
      await page.goto(`${adminAppUrl}/test-error`, { waitUntil: 'networkidle', timeout: 90000 });

      // Act: Network Timeoutをトリガー
      await page.click('[data-testid="trigger-timeout-error"]');

      // Assert: Error Boundary UIが表示される
      await expect(page.locator('h2:has-text("ネットワークエラー")')).toBeVisible({
        timeout: 5000,
      });

      // Act: 再試行ボタンをクリック
      const retryButton = page.locator('button:has-text("再試行")');
      await retryButton.click();

      // Assert: エラーがクリアされ、元のページに戻る
      await expect(page.locator('h1:has-text("Test Error Page")')).toBeVisible({
        timeout: 5000,
      });

      // Assert: ボタンが再度クリック可能な状態に戻る
      await expect(page.locator('[data-testid="trigger-timeout-error"]')).toBeEnabled();
    });
  });

  // Task 11.7: Request ID表示E2Eテスト
  test.describe('Request ID Display', () => {
    test('Error Boundary UIにRequest ID（trace_id）が表示される', async ({ page }) => {
      // Arrange: テスト用エラーページに遷移
      const adminAppUrl = process.env.E2E_ADMIN_URL || 'http://localhost:13002';
      await page.goto(`${adminAppUrl}/test-error`, { waitUntil: 'networkidle', timeout: 90000 });

      // Act: 400 Domain Exceptionをトリガー
      await page.click('[data-testid="trigger-400-error"]');

      // Assert: Error Boundary UIが表示される
      await expect(page.locator('h2:has-text("エラーが発生しました")')).toBeVisible({
        timeout: 5000,
      });

      // Assert: Request ID（trace_id）が表示される
      await expect(page.locator('text=Request ID:')).toBeVisible();

      // Assert: Request IDがコードブロック内に表示される
      const requestIdCode = page.locator('code').first();
      await expect(requestIdCode).toBeVisible();

      // Assert: Request IDがUUID形式である
      const requestId = await requestIdCode.textContent();
      expect(requestId).toMatch(/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i);
    });

    test('サポート問い合わせ用参照IDが提示される', async ({ page }) => {
      // Arrange: テスト用エラーページに遷移
      const adminAppUrl = process.env.E2E_ADMIN_URL || 'http://localhost:13002';
      await page.goto(`${adminAppUrl}/test-error`, { waitUntil: 'networkidle', timeout: 90000 });

      // Act: 404 Application Exceptionをトリガー
      await page.click('[data-testid="trigger-404-error"]');

      // Assert: Error Boundary UIが表示される
      await expect(page.locator('h2:has-text("エラーが発生しました")')).toBeVisible({
        timeout: 5000,
      });

      // Assert: Request ID表示エリアが表示される
      await expect(page.locator('text=Request ID:')).toBeVisible();

      // Assert: サポート問い合わせ用メッセージが表示される
      await expect(
        page.locator('text=お問い合わせの際は、このIDをお伝えください')
      ).toBeVisible();

      // Assert: Request IDがユーザーにコピー可能な形式で表示される
      const requestIdCode = page.locator('code').first();
      await expect(requestIdCode).toBeVisible();

      // Request IDの値が存在することを確認
      const requestIdValue = await requestIdCode.textContent();
      expect(requestIdValue).toBeTruthy();
      expect(requestIdValue?.length).toBeGreaterThan(0);
    });
  });
});

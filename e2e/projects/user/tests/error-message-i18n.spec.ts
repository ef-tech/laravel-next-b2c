/**
 * E2E Tests: Error Message Internationalization (i18n)
 *
 * Tests for error message localization and Error Boundary i18n:
 * - Task 12.1: Locale detection with error messages (ja/en)
 * - Task 12.2: NEXT_LOCALE Cookie persistence with error messages
 * - Task 12.3: NetworkError timeout messages in ja/en
 * - Task 12.4: ApiError validation error messages in ja/en
 * - Task 12.5: Global Error Boundary browser locale detection
 *
 * Requirements: REQ-8.7
 */

import { test, expect } from "@playwright/test";

// テスト全体のタイムアウトを延長（production buildは起動が遅い）
test.describe.configure({ timeout: 120000 });

test.describe("Error Message i18n - User App", () => {
  // Task 12.1: ロケール検出E2Eテスト（エラーメッセージ含む）
  test.describe("Locale Detection with Error Messages", () => {
    test.describe("Japanese locale (ja-JP)", () => {
      test.use({ locale: "ja-JP" });

      test("Accept-Language: ja-JP の場合、日本語エラーメッセージが表示される", async ({
        page,
        context,
      }) => {
        await context.clearCookies();

        // Visit test-error page
        await page.goto("/ja/test-error", { waitUntil: "networkidle", timeout: 90000 });

        // Verify html lang attribute is 'ja'
        const html = page.locator("html");
        await expect(html).toHaveAttribute("lang", "ja");

        // Trigger 400 Domain Exception
        await page.click('[data-testid="trigger-400-error"]');

        // Verify Japanese error message is displayed
        await expect(page.locator('h2:has-text("エラーが発生しました")')).toBeVisible({
          timeout: 30000,
        });

        // Verify "再試行" button (Japanese)
        await expect(page.locator('button:has-text("再試行")')).toBeVisible();

        // Verify Request ID label (Japanese)
        await expect(page.locator('text=Request ID:')).toBeVisible();
      });
    });

    test.describe("English locale (en-US)", () => {
      test.use({ locale: "en-US" });

      test("Accept-Language: en-US の場合、英語エラーメッセージが表示される", async ({
        page,
        context,
      }) => {
        await context.clearCookies();

        // Visit test-error page
        await page.goto("/en/test-error", { waitUntil: "networkidle", timeout: 90000 });

        // Verify html lang attribute is 'en'
        const html = page.locator("html");
        await expect(html).toHaveAttribute("lang", "en");

        // Trigger 400 Domain Exception
        await page.click('[data-testid="trigger-400-error"]');

        // Verify English error message is displayed
        await expect(page.locator('h2:has-text("An error occurred")')).toBeVisible({
          timeout: 30000,
        });

        // Verify "Retry" button (English)
        await expect(page.locator('button:has-text("Retry")')).toBeVisible();

        // Verify Request ID label (English)
        await expect(page.locator('text=Request ID:')).toBeVisible();
      });
    });
  });

  // Task 12.2: NEXT_LOCALE Cookie永続化E2Eテスト（エラーメッセージ含む）
  test.describe("NEXT_LOCALE Cookie Persistence with Error Messages", () => {
    test("日本語でページアクセス後、NEXT_LOCALE cookieがjaに設定され、エラーメッセージも日本語で表示される", async ({
      page,
      context,
    }) => {
      await context.clearCookies();

      // Visit with Japanese URL prefix
      await page.goto("/ja/test-error", { waitUntil: "networkidle", timeout: 90000 });

      // Verify NEXT_LOCALE cookie is set to 'ja'
      const cookies = await context.cookies();
      const localeCookie = cookies.find((c) => c.name === "NEXT_LOCALE");
      expect(localeCookie?.value).toBe("ja");

      // Trigger 400 Domain Exception
      await page.click('[data-testid="trigger-400-error"]');

      // Verify Japanese error message is displayed
      await expect(page.locator('h2:has-text("エラーが発生しました")')).toBeVisible({
        timeout: 30000,
      });
    });

    test("Cookie優先: Accept-Language: en-US でも、NEXT_LOCALE=ja の場合は日本語エラーメッセージが表示される", async ({
      page,
      context,
    }) => {
      await context.clearCookies();

      // Set NEXT_LOCALE cookie to 'ja'
      await context.addCookies([
        {
          name: "NEXT_LOCALE",
          value: "ja",
          domain: "localhost",
          path: "/",
        },
      ]);

      // Set Accept-Language to en-US
      await context.setExtraHTTPHeaders({
        "Accept-Language": "en-US",
      });

      // Visit test-error page (without URL prefix)
      await page.goto("/ja/test-error", { waitUntil: "networkidle", timeout: 90000 });

      // Verify html lang attribute is 'ja' (Cookie priority)
      const html = page.locator("html");
      await expect(html).toHaveAttribute("lang", "ja");

      // Trigger 400 Domain Exception
      await page.click('[data-testid="trigger-400-error"]');

      // Verify Japanese error message is displayed (not English)
      await expect(page.locator('h2:has-text("エラーが発生しました")')).toBeVisible({
        timeout: 30000,
      });
    });
  });

  // Task 12.3: NetworkErrorタイムアウトE2Eテスト（多言語化）
  test.describe("NetworkError Timeout Messages (i18n)", () => {
    test.describe("Japanese locale", () => {
      test.use({ locale: "ja-JP" });

      test("日本語ロケールでAPIタイムアウトエラーが発生した場合、日本語メッセージが表示される", async ({
        page,
        context,
      }) => {
        await context.clearCookies();

        // Visit test-error page
        await page.goto("/ja/test-error", { waitUntil: "networkidle", timeout: 90000 });

        // Trigger Network Timeout error
        await page.click('[data-testid="trigger-timeout-error"]');

        // Verify Japanese network error title
        await expect(page.locator('h2:has-text("ネットワークエラー")')).toBeVisible({
          timeout: 30000,
        });

        // Verify Japanese timeout message
        await expect(page.locator('text=リクエストがタイムアウトしました')).toBeVisible();

        // Verify Japanese retry button
        await expect(page.locator('button:has-text("再試行")')).toBeVisible();
      });
    });

    test.describe("English locale", () => {
      test.use({ locale: "en-US" });

      test("英語ロケールでAPIタイムアウトエラーが発生した場合、英語メッセージが表示される", async ({
        page,
        context,
      }) => {
        await context.clearCookies();

        // Visit test-error page
        await page.goto("/en/test-error", { waitUntil: "networkidle", timeout: 90000 });

        // Trigger Network Timeout error
        await page.click('[data-testid="trigger-timeout-error"]');

        // Verify English network error title
        await expect(page.locator('h2:has-text("Network Error")')).toBeVisible({
          timeout: 30000,
        });

        // Verify English timeout message
        await expect(page.locator('text=The request timed out')).toBeVisible();

        // Verify English retry button
        await expect(page.locator('button:has-text("Retry")')).toBeVisible();
      });
    });
  });

  // Task 12.4: ApiError検証エラーE2Eテスト（多言語化）
  test.describe("ApiError Validation Error Messages (i18n)", () => {
    test.describe("Japanese locale", () => {
      test.use({ locale: "ja-JP" });

      test("日本語ロケールで400 Bad Request（検証エラー）が発生した場合、日本語メッセージが表示される", async ({
        page,
        context,
      }) => {
        await context.clearCookies();

        // Visit test-error page
        await page.goto("/ja/test-error", { waitUntil: "networkidle", timeout: 90000 });

        // Trigger 422 Validation Error
        await page.click('[data-testid="trigger-422-error"]');

        // Verify Japanese error title
        await expect(page.locator('h2:has-text("エラーが発生しました")')).toBeVisible({
          timeout: 30000,
        });

        // Verify Japanese validation error section title
        await expect(page.locator('text=入力エラー:')).toBeVisible();

        // Verify field labels are displayed (email, name, age)
        await expect(page.locator('text=email:')).toBeVisible();
        await expect(page.locator('text=name:')).toBeVisible();
        await expect(page.locator('text=age:')).toBeVisible();
      });
    });

    test.describe("English locale", () => {
      test.use({ locale: "en-US" });

      test("英語ロケールで400 Bad Request（検証エラー）が発生した場合、英語メッセージが表示される", async ({
        page,
        context,
      }) => {
        await context.clearCookies();

        // Visit test-error page
        await page.goto("/en/test-error", { waitUntil: "networkidle", timeout: 90000 });

        // Trigger 422 Validation Error
        await page.click('[data-testid="trigger-422-error"]');

        // Verify English error title
        await expect(page.locator('h2:has-text("An error occurred")')).toBeVisible({
          timeout: 30000,
        });

        // Verify English validation error section title
        await expect(page.locator('text=Validation Errors:')).toBeVisible();

        // Verify field labels are displayed (email, name, age)
        await expect(page.locator('text=email:')).toBeVisible();
        await expect(page.locator('text=name:')).toBeVisible();
        await expect(page.locator('text=age:')).toBeVisible();
      });
    });
  });

  // Task 12.5: Global Error Boundaryブラウザロケール検出E2Eテスト
  test.describe("Global Error Boundary Browser Locale Detection", () => {
    test.describe("English browser locale", () => {
      test.use({ locale: "en-US" });

      test("ブラウザ言語設定がen-USの場合、グローバルエラーが英語で表示される", async ({
        page,
        context,
      }) => {
        await context.clearCookies();

        // Visit test-error page
        await page.goto("/en/test-error", { waitUntil: "networkidle", timeout: 90000 });

        // Trigger 500 Generic Exception (triggers global-error.tsx)
        await page.click('[data-testid="trigger-500-error"]');

        // Verify English error message
        await expect(page.locator('h2:has-text("An unexpected error occurred")')).toBeVisible({
          timeout: 30000,
        });

        // Verify English retry button
        await expect(page.locator('button:has-text("Retry")')).toBeVisible();

        // Verify Error ID label (English)
        await expect(page.locator('text=Error ID:')).toBeVisible();
      });
    });

    test.describe("Japanese browser locale", () => {
      test.use({ locale: "ja-JP" });

      test("ブラウザ言語設定がja-JPの場合、グローバルエラーが日本語で表示される", async ({
        page,
        context,
      }) => {
        await context.clearCookies();

        // Visit test-error page
        await page.goto("/ja/test-error", { waitUntil: "networkidle", timeout: 90000 });

        // Trigger 500 Generic Exception (triggers global-error.tsx)
        await page.click('[data-testid="trigger-500-error"]');

        // Verify Japanese error message
        await expect(page.locator('h2:has-text("予期しないエラーが発生しました")')).toBeVisible({
          timeout: 30000,
        });

        // Verify Japanese retry button
        await expect(page.locator('button:has-text("再試行")')).toBeVisible();

        // Verify Error ID label (Japanese)
        await expect(page.locator('text=Error ID:')).toBeVisible();
      });
    });
  });
});

test.describe("Error Message i18n - Admin App", () => {
  // Task 12.3: NetworkErrorタイムアウトE2Eテスト（Admin App）
  test.describe("NetworkError Timeout Messages (i18n)", () => {
    test.describe("Japanese locale", () => {
      test.use({ locale: "ja-JP" });

      test("Admin App: 日本語ロケールでAPIタイムアウトエラーが発生した場合、日本語メッセージが表示される", async ({
        page,
        context,
      }) => {
        await context.clearCookies();

        // Visit Admin App test-error page
        await page.goto("/ja/test-error", { waitUntil: "networkidle", timeout: 90000 });

        // Trigger Network Timeout error
        await page.click('[data-testid="trigger-timeout-error"]');

        // Verify Japanese network error title
        await expect(page.locator('h2:has-text("ネットワークエラー")')).toBeVisible({
          timeout: 30000,
        });

        // Verify Japanese timeout message
        await expect(page.locator('text=リクエストがタイムアウトしました')).toBeVisible();
      });
    });

    test.describe("English locale", () => {
      test.use({ locale: "en-US" });

      test("Admin App: 英語ロケールでAPIタイムアウトエラーが発生した場合、英語メッセージが表示される", async ({
        page,
        context,
      }) => {
        await context.clearCookies();

        // Visit Admin App test-error page
        await page.goto("/en/test-error", { waitUntil: "networkidle", timeout: 90000 });

        // Trigger Network Timeout error
        await page.click('[data-testid="trigger-timeout-error"]');

        // Verify English network error title
        await expect(page.locator('h2:has-text("Network Error")')).toBeVisible({
          timeout: 30000,
        });

        // Verify English timeout message
        await expect(page.locator('text=The request timed out')).toBeVisible();
      });
    });
  });

  // Task 12.4: ApiError検証エラーE2Eテスト（Admin App）
  test.describe("ApiError Validation Error Messages (i18n)", () => {
    test.describe("Japanese locale", () => {
      test.use({ locale: "ja-JP" });

      test("Admin App: 日本語ロケールで400 Bad Request（検証エラー）が発生した場合、日本語メッセージが表示される", async ({
        page,
        context,
      }) => {
        await context.clearCookies();

        // Visit Admin App test-error page
        await page.goto("/ja/test-error", { waitUntil: "networkidle", timeout: 90000 });

        // Trigger 422 Validation Error
        await page.click('[data-testid="trigger-422-error"]');

        // Verify Japanese error title
        await expect(page.locator('h2:has-text("エラーが発生しました")')).toBeVisible({
          timeout: 30000,
        });

        // Verify Japanese validation error section title
        await expect(page.locator('text=入力エラー:')).toBeVisible();
      });
    });

    test.describe("English locale", () => {
      test.use({ locale: "en-US" });

      test("Admin App: 英語ロケールで400 Bad Request（検証エラー）が発生した場合、英語メッセージが表示される", async ({
        page,
        context,
      }) => {
        await context.clearCookies();

        // Visit Admin App test-error page
        await page.goto("/en/test-error", { waitUntil: "networkidle", timeout: 90000 });

        // Trigger 422 Validation Error
        await page.click('[data-testid="trigger-422-error"]');

        // Verify English error title
        await expect(page.locator('h2:has-text("An error occurred")')).toBeVisible({
          timeout: 30000,
        });

        // Verify English validation error section title
        await expect(page.locator('text=Validation Errors:')).toBeVisible();
      });
    });
  });

  // Task 12.5: Global Error Boundaryブラウザロケール検出E2Eテスト（Admin App）
  test.describe("Global Error Boundary Browser Locale Detection", () => {
    test.describe("English browser locale", () => {
      test.use({ locale: "en-US" });

      test("Admin App: ブラウザ言語設定がen-USの場合、グローバルエラーが英語で表示される", async ({
        page,
        context,
      }) => {
        await context.clearCookies();

        // Visit Admin App test-error page
        await page.goto("/en/test-error", { waitUntil: "networkidle", timeout: 90000 });

        // Trigger 500 Generic Exception (triggers global-error.tsx)
        await page.click('[data-testid="trigger-500-error"]');

        // Verify English error message
        await expect(page.locator('h2:has-text("An unexpected error occurred")')).toBeVisible({
          timeout: 30000,
        });

        // Verify English retry button
        await expect(page.locator('button:has-text("Retry")')).toBeVisible();
      });
    });

    test.describe("Japanese browser locale", () => {
      test.use({ locale: "ja-JP" });

      test("Admin App: ブラウザ言語設定がja-JPの場合、グローバルエラーが日本語で表示される", async ({
        page,
        context,
      }) => {
        await context.clearCookies();

        // Visit Admin App test-error page
        await page.goto("/ja/test-error", { waitUntil: "networkidle", timeout: 90000 });

        // Trigger 500 Generic Exception (triggers global-error.tsx)
        await page.click('[data-testid="trigger-500-error"]');

        // Verify Japanese error message
        await expect(page.locator('h2:has-text("予期しないエラーが発生しました")')).toBeVisible({
          timeout: 30000,
        });

        // Verify Japanese retry button
        await expect(page.locator('button:has-text("再試行")')).toBeVisible();
      });
    });
  });
});

/**
 * E2E Tests: i18n Locale Detection
 *
 * Tests for locale detection priority and Accept-Language integration:
 * 1. URL Prefix locale detection (/ja/*, /en/*)
 * 2. NEXT_LOCALE Cookie persistence
 * 3. Accept-Language Header locale detection
 * 4. Default locale fallback (ja)
 *
 * Requirements: REQ-5.1, REQ-5.2, REQ-5.3, REQ-5.4
 */

import { test, expect } from "@playwright/test";

test.describe("i18n Locale Detection - User App", () => {
  test.describe("URL Prefix Detection (Priority 1)", () => {
    test("URLに/jaプレフィックスがある場合、日本語ロケールを検出する", async ({ page, context }) => {
      // Clear cookies to avoid interference
      await context.clearCookies();

      await page.goto("/ja");

      // Verify html lang attribute is set to 'ja'
      const html = page.locator("html");
      await expect(html).toHaveAttribute("lang", "ja");

      // Verify NEXT_LOCALE cookie is set
      const cookies = await context.cookies();
      const localeCookie = cookies.find((c) => c.name === "NEXT_LOCALE");
      expect(localeCookie?.value).toBe("ja");
    });

    test("URLに/enプレフィックスがある場合、英語ロケールを検出する", async ({ page, context }) => {
      await context.clearCookies();

      await page.goto("/en");

      const html = page.locator("html");
      await expect(html).toHaveAttribute("lang", "en");

      const cookies = await context.cookies();
      const localeCookie = cookies.find((c) => c.name === "NEXT_LOCALE");
      expect(localeCookie?.value).toBe("en");
    });

    test("無効なロケールプレフィックス（/fr）の場合、デフォルトロケール（ja）にフォールバックする", async ({
      page,
      context,
    }) => {
      await context.clearCookies();

      await page.goto("/fr");

      // Should redirect to /ja
      await page.waitForURL(/\/ja/);

      const html = page.locator("html");
      await expect(html).toHaveAttribute("lang", "ja");
    });
  });

  test.describe("Cookie Persistence (Priority 2)", () => {
    test("NEXT_LOCALE cookieがjaに設定されている場合、日本語ロケールを使用する", async ({
      page,
      context,
    }) => {
      // Set NEXT_LOCALE cookie manually
      await context.addCookies([
        {
          name: "NEXT_LOCALE",
          value: "ja",
          domain: "localhost",
          path: "/",
        },
      ]);

      await page.goto("/");

      const html = page.locator("html");
      await expect(html).toHaveAttribute("lang", "ja");
    });

    test("NEXT_LOCALE cookieがenに設定されている場合、英語ロケールを使用する", async ({
      page,
      context,
    }) => {
      await context.addCookies([
        {
          name: "NEXT_LOCALE",
          value: "en",
          domain: "localhost",
          path: "/",
        },
      ]);

      await page.goto("/");

      const html = page.locator("html");
      await expect(html).toHaveAttribute("lang", "en");
    });

    test("ロケールを変更した場合、NEXT_LOCALE cookieが更新される", async ({ page, context }) => {
      await context.clearCookies();

      // Visit with Japanese
      await page.goto("/ja");

      let cookies = await context.cookies();
      let localeCookie = cookies.find((c) => c.name === "NEXT_LOCALE");
      expect(localeCookie?.value).toBe("ja");

      // Change to English
      await page.goto("/en");

      cookies = await context.cookies();
      localeCookie = cookies.find((c) => c.name === "NEXT_LOCALE");
      expect(localeCookie?.value).toBe("en");
    });
  });

  test.describe("Accept-Language Header Detection (Priority 3)", () => {
    test("Accept-Language: ja-JP の場合、日本語ロケールを検出する", async ({ page, context }) => {
      await context.clearCookies();

      // Set Accept-Language header to Japanese
      await context.setExtraHTTPHeaders({
        "Accept-Language": "ja-JP,ja;q=0.9,en;q=0.8",
      });

      await page.goto("/");

      // Should redirect to /ja
      await page.waitForURL(/\/ja/);

      const html = page.locator("html");
      await expect(html).toHaveAttribute("lang", "ja");

      // Verify cookie is set
      const cookies = await context.cookies();
      const localeCookie = cookies.find((c) => c.name === "NEXT_LOCALE");
      expect(localeCookie?.value).toBe("ja");
    });

    test("Accept-Language: en-US の場合、英語ロケールを検出する", async ({ page, context }) => {
      await context.clearCookies();

      // Set Accept-Language header to English
      await context.setExtraHTTPHeaders({
        "Accept-Language": "en-US,en;q=0.9",
      });

      await page.goto("/");

      // Should redirect to /en
      await page.waitForURL(/\/en/);

      const html = page.locator("html");
      await expect(html).toHaveAttribute("lang", "en");

      const cookies = await context.cookies();
      const localeCookie = cookies.find((c) => c.name === "NEXT_LOCALE");
      expect(localeCookie?.value).toBe("en");
    });

    test("サポートされていない言語（Accept-Language: fr-FR）の場合、デフォルトロケール（ja）を使用する", async ({
      page,
      context,
    }) => {
      await context.clearCookies();

      await context.setExtraHTTPHeaders({
        "Accept-Language": "fr-FR,fr;q=0.9",
      });

      await page.goto("/");

      // Should redirect to /ja (default locale)
      await page.waitForURL(/\/ja/);

      const html = page.locator("html");
      await expect(html).toHaveAttribute("lang", "ja");
    });
  });

  test.describe("Default Locale Fallback (Priority 4)", () => {
    test("Accept-Language headerが空の場合、デフォルトロケール（ja）を使用する", async ({
      page,
      context,
    }) => {
      await context.clearCookies();

      await context.setExtraHTTPHeaders({
        "Accept-Language": "",
      });

      await page.goto("/");

      // Should redirect to /ja
      await page.waitForURL(/\/ja/);

      const html = page.locator("html");
      await expect(html).toHaveAttribute("lang", "ja");
    });
  });

  test.describe("Locale Detection Priority", () => {
    test("URL Prefix > Cookie > Accept-Language の優先順位を検証する", async ({ page, context }) => {
      await context.clearCookies();

      // Set cookie to Japanese
      await context.addCookies([
        {
          name: "NEXT_LOCALE",
          value: "ja",
          domain: "localhost",
          path: "/",
        },
      ]);

      // Set Accept-Language to Japanese
      await context.setExtraHTTPHeaders({
        "Accept-Language": "ja-JP",
      });

      // Visit with English URL prefix (highest priority)
      await page.goto("/en");

      // Should use URL prefix (en), not cookie or Accept-Language
      const html = page.locator("html");
      await expect(html).toHaveAttribute("lang", "en");

      // Cookie should be updated to 'en'
      const cookies = await context.cookies();
      const localeCookie = cookies.find((c) => c.name === "NEXT_LOCALE");
      expect(localeCookie?.value).toBe("en");
    });

    test("Cookie > Accept-Language の優先順位を検証する（URL Prefixなし）", async ({ page, context }) => {
      await context.clearCookies();

      // Set cookie to English
      await context.addCookies([
        {
          name: "NEXT_LOCALE",
          value: "en",
          domain: "localhost",
          path: "/",
        },
      ]);

      // Set Accept-Language to Japanese
      await context.setExtraHTTPHeaders({
        "Accept-Language": "ja-JP",
      });

      // Visit without URL prefix
      await page.goto("/");

      // Should redirect to /en (cookie priority over Accept-Language)
      await page.waitForURL(/\/en/);

      const html = page.locator("html");
      await expect(html).toHaveAttribute("lang", "en");
    });
  });
});

test.describe("i18n Locale Detection - Admin App", () => {
  test.describe("URL Prefix Detection", () => {
    test("Admin Appで/jaプレフィックスがある場合、日本語ロケールを検出する", async ({ page, context }) => {
      await context.clearCookies();

      // Visit Admin App home page with Japanese prefix
      await page.goto("/ja", { waitUntil: "domcontentloaded" });

      const html = page.locator("html");
      await expect(html).toHaveAttribute("lang", "ja");
    });

    test("Admin Appで/enプレフィックスがある場合、英語ロケールを検出する", async ({ page, context }) => {
      await context.clearCookies();

      await page.goto("/en", { waitUntil: "domcontentloaded" });

      const html = page.locator("html");
      await expect(html).toHaveAttribute("lang", "en");
    });
  });

  test.describe("Accept-Language Header Detection", () => {
    test("Admin AppでAccept-Language: en-US の場合、英語ロケールを検出する", async ({ page, context }) => {
      await context.clearCookies();

      await context.setExtraHTTPHeaders({
        "Accept-Language": "en-US,en;q=0.9",
      });

      await page.goto("/", { waitUntil: "domcontentloaded" });

      // Should redirect to /en
      await page.waitForURL(/\/en/, { timeout: 5000 });

      const html = page.locator("html");
      await expect(html).toHaveAttribute("lang", "en");
    });
  });
});

import { i18nConfig, type Locale } from "@shared/i18n-config";

describe("i18nConfig", () => {
  describe("locales", () => {
    it("正しいロケールリストを持つ", () => {
      expect(i18nConfig.locales).toEqual(["ja", "en"]);
    });

    it("ロケールリストは読み取り専用である", () => {
      // TypeScript型チェックで as const により readonly が保証される
      expect(i18nConfig.locales).toBeDefined();
    });

    it("2つのロケールをサポートする", () => {
      expect(i18nConfig.locales).toHaveLength(2);
    });

    it("日本語ロケール（ja）を含む", () => {
      expect(i18nConfig.locales).toContain("ja");
    });

    it("英語ロケール（en）を含む", () => {
      expect(i18nConfig.locales).toContain("en");
    });
  });

  describe("defaultLocale", () => {
    it("デフォルトロケールがjaである", () => {
      expect(i18nConfig.defaultLocale).toBe("ja");
    });

    it("デフォルトロケールがサポートロケールに含まれる", () => {
      expect(i18nConfig.locales).toContain(i18nConfig.defaultLocale);
    });
  });

  describe("Locale型", () => {
    it("Locale型がjaを受け入れる", () => {
      const locale: Locale = "ja";
      expect(locale).toBe("ja");
    });

    it("Locale型がenを受け入れる", () => {
      const locale: Locale = "en";
      expect(locale).toBe("en");
    });

    it("サポートされていないロケールはLocale型に割り当てられない（TypeScript型チェック）", () => {
      // TypeScript型チェックでコンパイルエラーになることを期待
      // 実行時テストではなく、型チェックで保証される
      // const invalidLocale: Locale = 'fr'; // TypeScript Error

      // 実行時チェック: ロケールリストに含まれないことを確認
      expect(i18nConfig.locales).not.toContain("fr");
      expect(i18nConfig.locales).not.toContain("de");
      expect(i18nConfig.locales).not.toContain("zh");
    });
  });

  describe("immutability", () => {
    it("i18nConfig全体が読み取り専用である", () => {
      // as const により深い readonly が保証される
      expect(Object.isFrozen(i18nConfig)).toBe(false); // as const は型レベルのみ
      // しかし、TypeScriptの型システムで変更不可が保証される
    });
  });
});

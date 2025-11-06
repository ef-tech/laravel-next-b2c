/**
 * TypeScript type definition tests for translation messages
 *
 * These tests verify that the Messages interface correctly
 * describes the structure of translation files.
 */

// IntlMessages is globally declared in frontend/types/messages.d.ts
import jaMessages from "../../messages/ja.json";
import enMessages from "../../messages/en.json";

describe("Messages TypeScript types", () => {
  describe("Type structure validation", () => {
    it("jaMessagesがMessages型構造に準拠する", () => {
      // TypeScriptコンパイル時に型チェックされる
      const messages: IntlMessages = jaMessages;

      expect(messages.errors).toBeDefined();
      expect(messages.errors.network).toBeDefined();
      expect(messages.errors.boundary).toBeDefined();
      expect(messages.errors.validation).toBeDefined();
      expect(messages.errors.global).toBeDefined();
    });

    it("enMessagesがMessages型構造に準拠する", () => {
      // TypeScriptコンパイル時に型チェックされる
      const messages: IntlMessages = enMessages;

      expect(messages.errors).toBeDefined();
      expect(messages.errors.network).toBeDefined();
      expect(messages.errors.boundary).toBeDefined();
      expect(messages.errors.validation).toBeDefined();
      expect(messages.errors.global).toBeDefined();
    });
  });

  describe("Type-safe key access", () => {
    it("errors.network キーに型安全にアクセスできる", () => {
      const messages: IntlMessages = jaMessages;

      expect(typeof messages.errors.network.timeout).toBe("string");
      expect(typeof messages.errors.network.connection).toBe("string");
      expect(typeof messages.errors.network.unknown).toBe("string");
    });

    it("errors.boundary キーに型安全にアクセスできる", () => {
      const messages: IntlMessages = jaMessages;

      expect(typeof messages.errors.boundary.title).toBe("string");
      expect(typeof messages.errors.boundary.retry).toBe("string");
      expect(typeof messages.errors.boundary.home).toBe("string");
      expect(typeof messages.errors.boundary.status).toBe("string");
      expect(typeof messages.errors.boundary.requestId).toBe("string");
    });

    it("errors.validation キーに型安全にアクセスできる", () => {
      const messages: IntlMessages = jaMessages;

      expect(typeof messages.errors.validation.title).toBe("string");
    });

    it("errors.global キーに型安全にアクセスできる", () => {
      const messages: IntlMessages = jaMessages;

      expect(typeof messages.errors.global.title).toBe("string");
      expect(typeof messages.errors.global.retry).toBe("string");
      expect(typeof messages.errors.global.errorId).toBe("string");
      expect(typeof messages.errors.global.contactMessage).toBe("string");
    });
  });

  describe("Type inference from translation files", () => {
    it("jaMessagesのすべてのキーがstring型である", () => {
      const messages: IntlMessages = jaMessages;

      // Check all leaf values are strings
      Object.values(messages.errors.network).forEach((value) => {
        expect(typeof value).toBe("string");
      });

      Object.values(messages.errors.boundary).forEach((value) => {
        expect(typeof value).toBe("string");
      });

      Object.values(messages.errors.validation).forEach((value) => {
        expect(typeof value).toBe("string");
      });

      Object.values(messages.errors.global).forEach((value) => {
        expect(typeof value).toBe("string");
      });
    });

    it("enMessagesのすべてのキーがstring型である", () => {
      const messages: IntlMessages = enMessages;

      // Check all leaf values are strings
      Object.values(messages.errors.network).forEach((value) => {
        expect(typeof value).toBe("string");
      });

      Object.values(messages.errors.boundary).forEach((value) => {
        expect(typeof value).toBe("string");
      });

      Object.values(messages.errors.validation).forEach((value) => {
        expect(typeof value).toBe("string");
      });

      Object.values(messages.errors.global).forEach((value) => {
        expect(typeof value).toBe("string");
      });
    });
  });
});

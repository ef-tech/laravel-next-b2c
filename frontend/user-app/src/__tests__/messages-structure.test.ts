/**
 * Translation files structure validation tests
 *
 * These tests ensure that all required translation keys exist
 * in both Japanese and English message files.
 */

import jaMessages from "../../messages/ja.json";
import enMessages from "../../messages/en.json";

describe("Translation files structure", () => {
  describe("Japanese messages (ja.json)", () => {
    it("errors オブジェクトを持つ", () => {
      expect(jaMessages).toHaveProperty("errors");
      expect(typeof jaMessages.errors).toBe("object");
    });

    describe("errors.network", () => {
      it("network オブジェクトを持つ", () => {
        expect(jaMessages.errors).toHaveProperty("network");
        expect(typeof jaMessages.errors.network).toBe("object");
      });

      it("timeout メッセージを持つ", () => {
        expect(jaMessages.errors.network).toHaveProperty("timeout");
        expect(typeof jaMessages.errors.network.timeout).toBe("string");
        expect(jaMessages.errors.network.timeout).not.toBe("");
      });

      it("connection メッセージを持つ", () => {
        expect(jaMessages.errors.network).toHaveProperty("connection");
        expect(typeof jaMessages.errors.network.connection).toBe("string");
        expect(jaMessages.errors.network.connection).not.toBe("");
      });

      it("unknown メッセージを持つ", () => {
        expect(jaMessages.errors.network).toHaveProperty("unknown");
        expect(typeof jaMessages.errors.network.unknown).toBe("string");
        expect(jaMessages.errors.network.unknown).not.toBe("");
      });
    });

    describe("errors.boundary", () => {
      it("boundary オブジェクトを持つ", () => {
        expect(jaMessages.errors).toHaveProperty("boundary");
        expect(typeof jaMessages.errors.boundary).toBe("object");
      });

      it("title メッセージを持つ", () => {
        expect(jaMessages.errors.boundary).toHaveProperty("title");
        expect(typeof jaMessages.errors.boundary.title).toBe("string");
        expect(jaMessages.errors.boundary.title).not.toBe("");
      });

      it("retry メッセージを持つ", () => {
        expect(jaMessages.errors.boundary).toHaveProperty("retry");
        expect(typeof jaMessages.errors.boundary.retry).toBe("string");
        expect(jaMessages.errors.boundary.retry).not.toBe("");
      });

      it("home メッセージを持つ", () => {
        expect(jaMessages.errors.boundary).toHaveProperty("home");
        expect(typeof jaMessages.errors.boundary.home).toBe("string");
        expect(jaMessages.errors.boundary.home).not.toBe("");
      });

      it("status メッセージを持つ", () => {
        expect(jaMessages.errors.boundary).toHaveProperty("status");
        expect(typeof jaMessages.errors.boundary.status).toBe("string");
        expect(jaMessages.errors.boundary.status).not.toBe("");
      });

      it("requestId メッセージを持つ", () => {
        expect(jaMessages.errors.boundary).toHaveProperty("requestId");
        expect(typeof jaMessages.errors.boundary.requestId).toBe("string");
        expect(jaMessages.errors.boundary.requestId).not.toBe("");
      });
    });

    describe("errors.validation", () => {
      it("validation オブジェクトを持つ", () => {
        expect(jaMessages.errors).toHaveProperty("validation");
        expect(typeof jaMessages.errors.validation).toBe("object");
      });

      it("title メッセージを持つ", () => {
        expect(jaMessages.errors.validation).toHaveProperty("title");
        expect(typeof jaMessages.errors.validation.title).toBe("string");
        expect(jaMessages.errors.validation.title).not.toBe("");
      });
    });

    describe("errors.global", () => {
      it("global オブジェクトを持つ", () => {
        expect(jaMessages.errors).toHaveProperty("global");
        expect(typeof jaMessages.errors.global).toBe("object");
      });

      it("title メッセージを持つ", () => {
        expect(jaMessages.errors.global).toHaveProperty("title");
        expect(typeof jaMessages.errors.global.title).toBe("string");
        expect(jaMessages.errors.global.title).not.toBe("");
      });

      it("retry メッセージを持つ", () => {
        expect(jaMessages.errors.global).toHaveProperty("retry");
        expect(typeof jaMessages.errors.global.retry).toBe("string");
        expect(jaMessages.errors.global.retry).not.toBe("");
      });

      it("errorId メッセージを持つ", () => {
        expect(jaMessages.errors.global).toHaveProperty("errorId");
        expect(typeof jaMessages.errors.global.errorId).toBe("string");
        expect(jaMessages.errors.global.errorId).not.toBe("");
      });

      it("contactMessage メッセージを持つ", () => {
        expect(jaMessages.errors.global).toHaveProperty("contactMessage");
        expect(typeof jaMessages.errors.global.contactMessage).toBe("string");
        expect(jaMessages.errors.global.contactMessage).not.toBe("");
      });
    });
  });

  describe("English messages (en.json)", () => {
    it("errors オブジェクトを持つ", () => {
      expect(enMessages).toHaveProperty("errors");
      expect(typeof enMessages.errors).toBe("object");
    });

    describe("errors.network", () => {
      it("network オブジェクトを持つ", () => {
        expect(enMessages.errors).toHaveProperty("network");
        expect(typeof enMessages.errors.network).toBe("object");
      });

      it("timeout メッセージを持つ", () => {
        expect(enMessages.errors.network).toHaveProperty("timeout");
        expect(typeof enMessages.errors.network.timeout).toBe("string");
        expect(enMessages.errors.network.timeout).not.toBe("");
      });

      it("connection メッセージを持つ", () => {
        expect(enMessages.errors.network).toHaveProperty("connection");
        expect(typeof enMessages.errors.network.connection).toBe("string");
        expect(enMessages.errors.network.connection).not.toBe("");
      });

      it("unknown メッセージを持つ", () => {
        expect(enMessages.errors.network).toHaveProperty("unknown");
        expect(typeof enMessages.errors.network.unknown).toBe("string");
        expect(enMessages.errors.network.unknown).not.toBe("");
      });
    });

    describe("errors.boundary", () => {
      it("boundary オブジェクトを持つ", () => {
        expect(enMessages.errors).toHaveProperty("boundary");
        expect(typeof enMessages.errors.boundary).toBe("object");
      });

      it("title メッセージを持つ", () => {
        expect(enMessages.errors.boundary).toHaveProperty("title");
        expect(typeof enMessages.errors.boundary.title).toBe("string");
        expect(enMessages.errors.boundary.title).not.toBe("");
      });

      it("retry メッセージを持つ", () => {
        expect(enMessages.errors.boundary).toHaveProperty("retry");
        expect(typeof enMessages.errors.boundary.retry).toBe("string");
        expect(enMessages.errors.boundary.retry).not.toBe("");
      });

      it("home メッセージを持つ", () => {
        expect(enMessages.errors.boundary).toHaveProperty("home");
        expect(typeof enMessages.errors.boundary.home).toBe("string");
        expect(enMessages.errors.boundary.home).not.toBe("");
      });

      it("status メッセージを持つ", () => {
        expect(enMessages.errors.boundary).toHaveProperty("status");
        expect(typeof enMessages.errors.boundary.status).toBe("string");
        expect(enMessages.errors.boundary.status).not.toBe("");
      });

      it("requestId メッセージを持つ", () => {
        expect(enMessages.errors.boundary).toHaveProperty("requestId");
        expect(typeof enMessages.errors.boundary.requestId).toBe("string");
        expect(enMessages.errors.boundary.requestId).not.toBe("");
      });
    });

    describe("errors.validation", () => {
      it("validation オブジェクトを持つ", () => {
        expect(enMessages.errors).toHaveProperty("validation");
        expect(typeof enMessages.errors.validation).toBe("object");
      });

      it("title メッセージを持つ", () => {
        expect(enMessages.errors.validation).toHaveProperty("title");
        expect(typeof enMessages.errors.validation.title).toBe("string");
        expect(enMessages.errors.validation.title).not.toBe("");
      });
    });

    describe("errors.global", () => {
      it("global オブジェクトを持つ", () => {
        expect(enMessages.errors).toHaveProperty("global");
        expect(typeof enMessages.errors.global).toBe("object");
      });

      it("title メッセージを持つ", () => {
        expect(enMessages.errors.global).toHaveProperty("title");
        expect(typeof enMessages.errors.global.title).toBe("string");
        expect(enMessages.errors.global.title).not.toBe("");
      });

      it("retry メッセージを持つ", () => {
        expect(enMessages.errors.global).toHaveProperty("retry");
        expect(typeof enMessages.errors.global.retry).toBe("string");
        expect(enMessages.errors.global.retry).not.toBe("");
      });

      it("errorId メッセージを持つ", () => {
        expect(enMessages.errors.global).toHaveProperty("errorId");
        expect(typeof enMessages.errors.global.errorId).toBe("string");
        expect(enMessages.errors.global.errorId).not.toBe("");
      });

      it("contactMessage メッセージを持つ", () => {
        expect(enMessages.errors.global).toHaveProperty("contactMessage");
        expect(typeof enMessages.errors.global.contactMessage).toBe("string");
        expect(enMessages.errors.global.contactMessage).not.toBe("");
      });
    });
  });

  describe("Key consistency between locales", () => {
    it("日本語と英語で同じキー構造を持つ", () => {
      const jaKeys = JSON.stringify(Object.keys(jaMessages.errors).sort());
      const enKeys = JSON.stringify(Object.keys(enMessages.errors).sort());
      expect(jaKeys).toBe(enKeys);
    });

    it("network キーが一致する", () => {
      const jaNetworkKeys = JSON.stringify(Object.keys(jaMessages.errors.network).sort());
      const enNetworkKeys = JSON.stringify(Object.keys(enMessages.errors.network).sort());
      expect(jaNetworkKeys).toBe(enNetworkKeys);
    });

    it("boundary キーが一致する", () => {
      const jaBoundaryKeys = JSON.stringify(Object.keys(jaMessages.errors.boundary).sort());
      const enBoundaryKeys = JSON.stringify(Object.keys(enMessages.errors.boundary).sort());
      expect(jaBoundaryKeys).toBe(enBoundaryKeys);
    });

    it("validation キーが一致する", () => {
      const jaValidationKeys = JSON.stringify(Object.keys(jaMessages.errors.validation).sort());
      const enValidationKeys = JSON.stringify(Object.keys(enMessages.errors.validation).sort());
      expect(jaValidationKeys).toBe(enValidationKeys);
    });

    it("global キーが一致する", () => {
      const jaGlobalKeys = JSON.stringify(Object.keys(jaMessages.errors.global).sort());
      const enGlobalKeys = JSON.stringify(Object.keys(enMessages.errors.global).sort());
      expect(jaGlobalKeys).toBe(enGlobalKeys);
    });
  });
});

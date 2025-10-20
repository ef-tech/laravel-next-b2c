import { POST } from "../route";

// NextRequestのモック型定義
type MockNextRequest = {
  json: jest.Mock<Promise<unknown>>;
  headers: {
    get: jest.Mock<string | null, [string]>;
  };
};

// NextRequestのモックヘルパー
function createMockRequest(body: unknown, headers: Record<string, string>): MockNextRequest {
  return {
    json: jest.fn().mockResolvedValue(body),
    headers: {
      get: jest.fn((key: string) => headers[key.toLowerCase()] || null),
    },
  };
}

describe("Admin App CSP Report API", () => {
  // console.warn のモック
  const originalConsoleWarn = console.warn;
  let consoleWarnMock: jest.Mock;

  beforeEach(() => {
    consoleWarnMock = jest.fn();
    console.warn = consoleWarnMock;
  });

  afterEach(() => {
    console.warn = originalConsoleWarn;
  });

  describe("POST /api/csp-report", () => {
    it("有効なCSPレポートを受信してHTTP 204を返却すること", async () => {
      const cspReport = {
        "csp-report": {
          "blocked-uri": "https://evil.com/script.js",
          "violated-directive": "script-src",
          "original-policy": "default-src 'self'; script-src 'self'",
          "document-uri": "https://admin.example.com/page",
          referrer: "https://admin.example.com",
          "source-file": "https://admin.example.com/page",
          "line-number": 42,
          "column-number": 10,
          "status-code": 200,
        },
      };

      const request = createMockRequest(cspReport, {
        "content-type": "application/csp-report",
      });

      const response = await POST(request);

      expect(response.status).toBe(204);
      expect(await response.text()).toBe("");
    });

    it("開発環境ではconsole.warn()でレポートを出力すること", async () => {
      // 開発環境を模擬
      const originalEnv = process.env.NODE_ENV;
      process.env.NODE_ENV = "development";

      const cspReport = {
        "csp-report": {
          "blocked-uri": "https://evil.com/script.js",
          "violated-directive": "script-src",
          "document-uri": "https://admin.example.com/page",
        },
      };

      const request = createMockRequest(cspReport, {
        "content-type": "application/csp-report",
      });

      await POST(request);

      expect(consoleWarnMock).toHaveBeenCalled();
      expect(consoleWarnMock).toHaveBeenCalledWith("CSP Violation Report:", expect.any(Object));

      process.env.NODE_ENV = originalEnv;
    });

    it("本番環境ではconsole.warn()を呼び出さないこと", async () => {
      const originalEnv = process.env.NODE_ENV;
      process.env.NODE_ENV = "production";

      const cspReport = {
        "csp-report": {
          "blocked-uri": "https://evil.com/script.js",
          "violated-directive": "script-src",
          "document-uri": "https://admin.example.com/page",
        },
      };

      const request = createMockRequest(cspReport, {
        "content-type": "application/csp-report",
      });

      await POST(request);

      expect(consoleWarnMock).not.toHaveBeenCalled();

      process.env.NODE_ENV = originalEnv;
    });

    it("Content-Typeが不正な場合は400エラーを返すこと", async () => {
      const cspReport = {
        "csp-report": {
          "blocked-uri": "https://evil.com/script.js",
          "violated-directive": "script-src",
        },
      };

      const request = createMockRequest(cspReport, {
        "content-type": "application/json",
      });

      const response = await POST(request);

      expect(response.status).toBe(400);
      const body = await response.json();
      expect(body.error).toBe("Invalid Content-Type");
    });

    it("CSPレポートが空の場合は400エラーを返すこと", async () => {
      const request = createMockRequest(
        {},
        {
          "content-type": "application/csp-report",
        },
      );

      const response = await POST(request);

      expect(response.status).toBe(400);
      const body = await response.json();
      expect(body.error).toBe("Empty CSP report");
    });

    it("オプションフィールドを含まないレポートでも処理できること", async () => {
      const cspReport = {
        "csp-report": {
          "blocked-uri": "https://evil.com/script.js",
          "violated-directive": "script-src",
        },
      };

      const request = createMockRequest(cspReport, {
        "content-type": "application/csp-report",
      });

      const response = await POST(request);

      expect(response.status).toBe(204);
    });

    it("User-AgentとIPアドレスがログに含まれること", async () => {
      const originalEnv = process.env.NODE_ENV;
      process.env.NODE_ENV = "development";

      const cspReport = {
        "csp-report": {
          "blocked-uri": "https://evil.com/script.js",
          "violated-directive": "script-src",
          "document-uri": "https://admin.example.com/page",
        },
      };

      const request = createMockRequest(cspReport, {
        "content-type": "application/csp-report",
        "user-agent": "Mozilla/5.0 (Admin Browser)",
      });

      await POST(request);

      expect(consoleWarnMock).toHaveBeenCalledWith(
        "CSP Violation Report:",
        expect.objectContaining({
          "User-Agent": "Mozilla/5.0 (Admin Browser)",
        }),
      );

      process.env.NODE_ENV = originalEnv;
    });
  });
});

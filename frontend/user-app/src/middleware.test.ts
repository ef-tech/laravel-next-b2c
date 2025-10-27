import { NextRequest, NextResponse } from "next/server";
import { middleware } from "./middleware";

// NextRequest のモックヘルパー
function createMockRequest(url: string, cookies?: Record<string, string>): NextRequest {
  const request = {
    nextUrl: new URL(url),
    cookies: {
      get: (name: string) => {
        if (cookies && cookies[name]) {
          return { value: cookies[name] };
        }
        return undefined;
      },
    },
    url,
  } as NextRequest;

  return request;
}

describe("認証ミドルウェア", () => {
  let redirectSpy: jest.SpyInstance;
  let nextSpy: jest.SpyInstance;

  beforeEach(() => {
    jest.clearAllMocks();
    redirectSpy = jest.spyOn(NextResponse, "redirect");
    nextSpy = jest.spyOn(NextResponse, "next");
  });

  afterEach(() => {
    redirectSpy.mockRestore();
    nextSpy.mockRestore();
  });

  describe("認証が必要なページ", () => {
    it("トークンがない場合、ログイン画面へリダイレクトする", () => {
      const request = createMockRequest("http://localhost:13001/profile");

      middleware(request);

      expect(redirectSpy).toHaveBeenCalled();
      const redirectUrl = redirectSpy.mock.calls[0][0];
      expect(redirectUrl.pathname).toBe("/login");
    });

    it("トークンがある場合、アクセスを許可する", () => {
      const request = createMockRequest("http://localhost:13001/profile", {
        user_token: "valid-token-123",
      });

      middleware(request);

      expect(nextSpy).toHaveBeenCalled();
      expect(redirectSpy).not.toHaveBeenCalled();
    });
  });

  describe("公開ページ", () => {
    it("ログイン画面はトークンなしでアクセス可能", () => {
      const request = createMockRequest("http://localhost:13001/login");

      middleware(request);

      expect(nextSpy).toHaveBeenCalled();
      expect(redirectSpy).not.toHaveBeenCalled();
    });

    it("ホーム画面はトークンなしでアクセス可能", () => {
      const request = createMockRequest("http://localhost:13001/");

      middleware(request);

      expect(nextSpy).toHaveBeenCalled();
      expect(redirectSpy).not.toHaveBeenCalled();
    });
  });

  describe("認証済みユーザー", () => {
    it("トークンがある場合でもログイン画面にアクセス可能", () => {
      const request = createMockRequest("http://localhost:13001/login", {
        user_token: "valid-token-123",
      });

      middleware(request);

      expect(nextSpy).toHaveBeenCalled();
      expect(redirectSpy).not.toHaveBeenCalled();
    });
  });

  describe("APIルート", () => {
    it("APIルートはミドルウェアで処理しない", () => {
      const request = createMockRequest("http://localhost:13001/api/test");

      middleware(request);

      expect(nextSpy).toHaveBeenCalled();
      expect(redirectSpy).not.toHaveBeenCalled();
    });
  });

  describe("静的ファイル", () => {
    it("静的ファイルはミドルウェアで処理しない", () => {
      const request = createMockRequest("http://localhost:13001/_next/static/test.js");

      middleware(request);

      expect(nextSpy).toHaveBeenCalled();
      expect(redirectSpy).not.toHaveBeenCalled();
    });

    it("faviconはミドルウェアで処理しない", () => {
      const request = createMockRequest("http://localhost:13001/favicon.ico");

      middleware(request);

      expect(nextSpy).toHaveBeenCalled();
      expect(redirectSpy).not.toHaveBeenCalled();
    });
  });
});

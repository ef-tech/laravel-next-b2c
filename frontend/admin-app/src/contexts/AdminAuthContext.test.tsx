import { renderHook, act, waitFor } from "@testing-library/react";
import { AdminAuthProvider, useAdminAuth } from "./AdminAuthContext";
import { ApiError } from "@/lib/api-error-handler";

// localStorageのモック
const localStorageMock = (() => {
  let store: Record<string, string> = {};

  return {
    getItem: (key: string) => store[key] || null,
    setItem: (key: string, value: string) => {
      store[key] = value;
    },
    removeItem: (key: string) => {
      delete store[key];
    },
    clear: () => {
      store = {};
    },
  };
})();

Object.defineProperty(window, "localStorage", {
  value: localStorageMock,
});

// global.fetchのモック
const mockFetch = jest.fn();
global.fetch = mockFetch;

describe("AdminAuthContext", () => {
  beforeEach(() => {
    jest.clearAllMocks();
    localStorageMock.clear();
  });

  describe("初期状態", () => {
    it("未認証状態で初期化される", () => {
      const { result } = renderHook(() => useAdminAuth(), {
        wrapper: AdminAuthProvider,
      });

      expect(result.current.admin).toBeNull();
      expect(result.current.token).toBeNull();
      expect(result.current.isAuthenticated).toBe(false);
      expect(result.current.isLoading).toBe(false);
    });
  });

  describe("login", () => {
    const mockLoginResponse = {
      admin: {
        id: 1,
        name: "Admin User",
        email: "admin@example.com",
      },
      token: "test-admin-token-123",
    };

    it("ログイン成功時に管理者情報とトークンを保存する", async () => {
      mockFetch.mockResolvedValueOnce({
        ok: true,
        status: 200,
        json: async () => mockLoginResponse,
      });

      const { result } = renderHook(() => useAdminAuth(), {
        wrapper: AdminAuthProvider,
      });

      await act(async () => {
        await result.current.login("admin@example.com", "password123");
      });

      expect(result.current.admin).toEqual(mockLoginResponse.admin);
      expect(result.current.token).toBe(mockLoginResponse.token);
      expect(result.current.isAuthenticated).toBe(true);
      expect(localStorageMock.getItem("admin_token")).toBe(mockLoginResponse.token);

      // API呼び出し確認
      expect(mockFetch).toHaveBeenCalledWith(
        "http://localhost:13000/api/v1/admin/login",
        expect.objectContaining({
          method: "POST",
          headers: {
            "Content-Type": "application/json",
          },
          body: JSON.stringify({
            email: "admin@example.com",
            password: "password123",
          }),
        }),
      );
    });

    it("ログイン失敗時にApiErrorをスローする", async () => {
      mockFetch.mockResolvedValueOnce({
        ok: false,
        status: 401,
        json: async () => ({
          code: "AUTH.INVALID_CREDENTIALS",
          message: "メールアドレスまたはパスワードが正しくありません",
          trace_id: "req-123",
        }),
      });

      const { result } = renderHook(() => useAdminAuth(), {
        wrapper: AdminAuthProvider,
      });

      await expect(
        act(async () => {
          await result.current.login("admin@example.com", "wrongpassword");
        }),
      ).rejects.toThrow(ApiError);

      expect(result.current.admin).toBeNull();
      expect(result.current.token).toBeNull();
      expect(result.current.isAuthenticated).toBe(false);
      expect(localStorageMock.getItem("admin_token")).toBeNull();
    });

    it("ログイン中はisLoadingがtrueになる", async () => {
      let resolveLogin: (value: any) => void;
      const loginPromise = new Promise((resolve) => {
        resolveLogin = resolve;
      });

      mockFetch.mockReturnValueOnce(
        loginPromise.then(() => ({
          ok: true,
          status: 200,
          json: async () => mockLoginResponse,
        })),
      );

      const { result } = renderHook(() => useAdminAuth(), {
        wrapper: AdminAuthProvider,
      });

      act(() => {
        result.current.login("admin@example.com", "password123");
      });

      // ログイン処理中はisLoadingがtrue
      await waitFor(() => {
        expect(result.current.isLoading).toBe(true);
      });

      // ログイン完了
      act(() => {
        resolveLogin({});
      });

      // ログイン完了後はisLoadingがfalse
      await waitFor(() => {
        expect(result.current.isLoading).toBe(false);
      });
    });
  });

  describe("logout", () => {
    it("ログアウト成功時に管理者情報とトークンをクリアする", async () => {
      // まずログイン処理を行う
      const mockLoginResponse = {
        admin: {
          id: 1,
          name: "Admin User",
          email: "admin@example.com",
        },
        token: "test-admin-token-123",
      };

      mockFetch.mockResolvedValueOnce({
        ok: true,
        status: 200,
        json: async () => mockLoginResponse,
      });

      const { result } = renderHook(() => useAdminAuth(), {
        wrapper: AdminAuthProvider,
      });

      // ログイン実行
      await act(async () => {
        await result.current.login("admin@example.com", "password123");
      });

      // ログアウトAPIのモック設定
      mockFetch.mockResolvedValueOnce({
        ok: true,
        status: 200,
        json: async () => ({ message: "ログアウトしました" }),
      });

      // ログアウト実行
      await act(async () => {
        await result.current.logout();
      });

      expect(result.current.admin).toBeNull();
      expect(result.current.token).toBeNull();
      expect(result.current.isAuthenticated).toBe(false);
      expect(localStorageMock.getItem("admin_token")).toBeNull();

      // API呼び出し確認
      expect(mockFetch).toHaveBeenCalledWith(
        "http://localhost:13000/api/v1/admin/logout",
        expect.objectContaining({
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            Authorization: "Bearer test-admin-token-123",
          },
        }),
      );
    });

    it("ログアウト失敗時でもローカルの状態はクリアする", async () => {
      // まずログイン処理を行う
      const mockLoginResponse = {
        admin: {
          id: 1,
          name: "Admin User",
          email: "admin@example.com",
        },
        token: "test-admin-token-123",
      };

      mockFetch.mockResolvedValueOnce({
        ok: true,
        status: 200,
        json: async () => mockLoginResponse,
      });

      const { result } = renderHook(() => useAdminAuth(), {
        wrapper: AdminAuthProvider,
      });

      // ログイン実行
      await act(async () => {
        await result.current.login("admin@example.com", "password123");
      });

      // ログアウトAPIのモック設定（失敗）
      mockFetch.mockResolvedValueOnce({
        ok: false,
        status: 500,
        statusText: "Internal Server Error",
        json: async () => ({
          code: "INTERNAL_ERROR",
          message: "サーバーエラー",
        }),
      });

      // エラーが発生してもクリアは実行される
      await act(async () => {
        try {
          await result.current.logout();
        } catch {
          // エラーを無視
        }
      });

      expect(result.current.admin).toBeNull();
      expect(result.current.token).toBeNull();
      expect(localStorageMock.getItem("admin_token")).toBeNull();
    });
  });

  describe("fetchAdminInfo", () => {
    const mockAdminInfo = {
      id: 1,
      name: "Admin User",
      email: "admin@example.com",
    };

    it("管理者情報取得成功時に状態を更新する", async () => {
      // まずログインしてトークンを取得
      const mockLoginResponse = {
        admin: mockAdminInfo,
        token: "test-admin-token-123",
      };

      mockFetch.mockResolvedValueOnce({
        ok: true,
        status: 200,
        json: async () => mockLoginResponse,
      });

      const { result } = renderHook(() => useAdminAuth(), {
        wrapper: AdminAuthProvider,
      });

      // ログイン実行
      await act(async () => {
        await result.current.login("admin@example.com", "password123");
      });

      // 管理者情報取得APIのモック設定
      mockFetch.mockResolvedValueOnce({
        ok: true,
        status: 200,
        json: async () => mockAdminInfo,
      });

      await act(async () => {
        await result.current.fetchAdminInfo();
      });

      expect(result.current.admin).toEqual(mockAdminInfo);
      expect(result.current.isAuthenticated).toBe(true);

      // API呼び出し確認
      expect(mockFetch).toHaveBeenCalledWith(
        "http://localhost:13000/api/v1/admin/dashboard",
        expect.objectContaining({
          method: "GET",
          headers: {
            "Content-Type": "application/json",
            Authorization: "Bearer test-admin-token-123",
          },
        }),
      );
    });

    it("トークンがない場合はエラーをスローする", async () => {
      const { result } = renderHook(() => useAdminAuth(), {
        wrapper: AdminAuthProvider,
      });

      await expect(
        act(async () => {
          await result.current.fetchAdminInfo();
        }),
      ).rejects.toThrow("トークンが存在しません");
    });

    it("管理者情報取得失敗時にApiErrorをスローする", async () => {
      // まずログインしてトークンを取得
      const mockLoginResponse = {
        admin: mockAdminInfo,
        token: "invalid-token",
      };

      mockFetch.mockResolvedValueOnce({
        ok: true,
        status: 200,
        json: async () => mockLoginResponse,
      });

      const { result } = renderHook(() => useAdminAuth(), {
        wrapper: AdminAuthProvider,
      });

      // ログイン実行
      await act(async () => {
        await result.current.login("admin@example.com", "password123");
      });

      // 管理者情報取得APIのモック設定（失敗）
      mockFetch.mockResolvedValueOnce({
        ok: false,
        status: 401,
        statusText: "Unauthorized",
        json: async () => ({
          code: "AUTH.TOKEN_EXPIRED",
          message: "トークンの有効期限が切れています",
          trace_id: "req-456",
        }),
      });

      await expect(
        act(async () => {
          await result.current.fetchAdminInfo();
        }),
      ).rejects.toThrow(ApiError);
    });
  });

  describe("トークン復元", () => {
    it("localStorageにトークンがある場合、初期化時に復元する", async () => {
      localStorageMock.setItem("admin_token", "stored-token-123");

      mockFetch.mockResolvedValueOnce({
        ok: true,
        status: 200,
        json: async () => ({
          id: 1,
          name: "Restored Admin",
          email: "restored@example.com",
        }),
      });

      const { result } = renderHook(() => useAdminAuth(), {
        wrapper: AdminAuthProvider,
      });

      // 初期化時に管理者情報取得が呼ばれるまで待機
      await waitFor(() => {
        expect(result.current.admin).toEqual({
          id: 1,
          name: "Restored Admin",
          email: "restored@example.com",
        });
      });

      expect(result.current.token).toBe("stored-token-123");
      expect(result.current.isAuthenticated).toBe(true);
    });

    it("localStorageのトークンが無効な場合、クリアする", async () => {
      localStorageMock.setItem("admin_token", "invalid-token");

      mockFetch.mockResolvedValueOnce({
        ok: false,
        status: 401,
        statusText: "Unauthorized",
        json: async () => ({
          code: "AUTH.TOKEN_EXPIRED",
          message: "トークンの有効期限が切れています",
        }),
      });

      const { result } = renderHook(() => useAdminAuth(), {
        wrapper: AdminAuthProvider,
      });

      // トークン検証完了（isLoadingがfalse）まで待機
      await waitFor(
        () => {
          expect(result.current.isLoading).toBe(false);
        },
        { timeout: 3000 },
      );

      // トークン検証失敗後、状態がクリアされる
      expect(result.current.admin).toBeNull();
      expect(result.current.token).toBeNull();
      expect(result.current.isAuthenticated).toBe(false);
      expect(localStorageMock.getItem("admin_token")).toBeNull();
    });
  });
});

import { renderHook, act, waitFor } from "@testing-library/react";
import { AuthProvider, useAuth } from "./AuthContext";
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

describe("AuthContext", () => {
  beforeEach(() => {
    jest.clearAllMocks();
    localStorageMock.clear();
  });

  describe("初期状態", () => {
    it("未認証状態で初期化される", () => {
      const { result } = renderHook(() => useAuth(), {
        wrapper: AuthProvider,
      });

      expect(result.current.user).toBeNull();
      expect(result.current.token).toBeNull();
      expect(result.current.isAuthenticated).toBe(false);
      expect(result.current.isLoading).toBe(false);
    });
  });

  describe("login", () => {
    const mockLoginResponse = {
      user: {
        id: 1,
        name: "Test User",
        email: "user@example.com",
      },
      token: "test-user-token-123",
    };

    it("ログイン成功時にユーザー情報とトークンを保存する", async () => {
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => mockLoginResponse,
      });

      const { result } = renderHook(() => useAuth(), {
        wrapper: AuthProvider,
      });

      await act(async () => {
        await result.current.login("user@example.com", "password123");
      });

      expect(result.current.user).toEqual(mockLoginResponse.user);
      expect(result.current.token).toBe(mockLoginResponse.token);
      expect(result.current.isAuthenticated).toBe(true);
      expect(localStorageMock.getItem("user_token")).toBe(mockLoginResponse.token);

      // API呼び出し確認
      expect(mockFetch).toHaveBeenCalledWith(
        "http://localhost:13000/api/v1/user/login",
        expect.objectContaining({
          method: "POST",
          headers: {
            "Content-Type": "application/json",
          },
          body: JSON.stringify({
            email: "user@example.com",
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

      const { result } = renderHook(() => useAuth(), {
        wrapper: AuthProvider,
      });

      await expect(
        act(async () => {
          await result.current.login("user@example.com", "wrongpassword");
        }),
      ).rejects.toThrow(ApiError);

      expect(result.current.user).toBeNull();
      expect(result.current.token).toBeNull();
      expect(result.current.isAuthenticated).toBe(false);
      expect(localStorageMock.getItem("user_token")).toBeNull();
    });

    it("ログイン中はisLoadingがtrueになる", async () => {
      let resolveLogin: (value: unknown) => void;
      const loginPromise = new Promise((resolve) => {
        resolveLogin = resolve;
      });

      mockFetch.mockReturnValueOnce(
        loginPromise.then(() => ({
          ok: true,
          json: async () => mockLoginResponse,
        })),
      );

      const { result } = renderHook(() => useAuth(), {
        wrapper: AuthProvider,
      });

      act(() => {
        result.current.login("user@example.com", "password123");
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
    it("ログアウト成功時にユーザー情報とトークンをクリアする", async () => {
      // まずログイン処理を行う
      const mockLoginResponse = {
        user: {
          id: 1,
          name: "Test User",
          email: "user@example.com",
        },
        token: "test-user-token-123",
      };

      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => mockLoginResponse,
      });

      const { result } = renderHook(() => useAuth(), {
        wrapper: AuthProvider,
      });

      // ログイン実行
      await act(async () => {
        await result.current.login("user@example.com", "password123");
      });

      // ログアウトAPIのモック設定
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({ message: "ログアウトしました" }),
      });

      // ログアウト実行
      await act(async () => {
        await result.current.logout();
      });

      expect(result.current.user).toBeNull();
      expect(result.current.token).toBeNull();
      expect(result.current.isAuthenticated).toBe(false);
      expect(localStorageMock.getItem("user_token")).toBeNull();

      // API呼び出し確認
      expect(mockFetch).toHaveBeenCalledWith(
        "http://localhost:13000/api/v1/user/logout",
        expect.objectContaining({
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            Authorization: "Bearer test-user-token-123",
          },
        }),
      );
    });

    it("ログアウト失敗時でもローカルの状態はクリアする", async () => {
      // まずログイン処理を行う
      const mockLoginResponse = {
        user: {
          id: 1,
          name: "Test User",
          email: "user@example.com",
        },
        token: "test-user-token-123",
      };

      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => mockLoginResponse,
      });

      const { result } = renderHook(() => useAuth(), {
        wrapper: AuthProvider,
      });

      // ログイン実行
      await act(async () => {
        await result.current.login("user@example.com", "password123");
      });

      // ログアウトAPIのモック設定（失敗）
      mockFetch.mockResolvedValueOnce({
        ok: false,
        status: 500,
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

      expect(result.current.user).toBeNull();
      expect(result.current.token).toBeNull();
      expect(localStorageMock.getItem("user_token")).toBeNull();
    });
  });

  describe("fetchUserProfile", () => {
    const mockUserProfile = {
      id: 1,
      name: "Test User",
      email: "user@example.com",
    };

    it("プロフィール取得成功時にユーザー情報を更新する", async () => {
      // まずログインしてトークンを取得
      const mockLoginResponse = {
        user: mockUserProfile,
        token: "test-user-token-123",
      };

      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => mockLoginResponse,
      });

      const { result } = renderHook(() => useAuth(), {
        wrapper: AuthProvider,
      });

      // ログイン実行
      await act(async () => {
        await result.current.login("user@example.com", "password123");
      });

      // プロフィール取得APIのモック設定
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => mockUserProfile,
      });

      await act(async () => {
        await result.current.fetchUserProfile();
      });

      expect(result.current.user).toEqual(mockUserProfile);
      expect(result.current.isAuthenticated).toBe(true);

      // API呼び出し確認
      expect(mockFetch).toHaveBeenCalledWith(
        "http://localhost:13000/api/v1/user/profile",
        expect.objectContaining({
          method: "GET",
          headers: {
            "Content-Type": "application/json",
            Authorization: "Bearer test-user-token-123",
          },
        }),
      );
    });

    it("トークンがない場合はエラーをスローする", async () => {
      const { result } = renderHook(() => useAuth(), {
        wrapper: AuthProvider,
      });

      await expect(
        act(async () => {
          await result.current.fetchUserProfile();
        }),
      ).rejects.toThrow("トークンが存在しません");
    });

    it("プロフィール取得失敗時にApiErrorをスローする", async () => {
      // まずログインしてトークンを取得
      const mockLoginResponse = {
        user: mockUserProfile,
        token: "invalid-token",
      };

      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => mockLoginResponse,
      });

      const { result } = renderHook(() => useAuth(), {
        wrapper: AuthProvider,
      });

      // ログイン実行
      await act(async () => {
        await result.current.login("user@example.com", "password123");
      });

      // プロフィール取得APIのモック設定（失敗）
      mockFetch.mockResolvedValueOnce({
        ok: false,
        status: 401,
        json: async () => ({
          code: "AUTH.TOKEN_EXPIRED",
          message: "トークンの有効期限が切れています",
          trace_id: "req-456",
        }),
      });

      await expect(
        act(async () => {
          await result.current.fetchUserProfile();
        }),
      ).rejects.toThrow(ApiError);
    });
  });

  describe("トークン復元", () => {
    it("localStorageにトークンがある場合、初期化時に復元する", async () => {
      localStorageMock.setItem("user_token", "stored-token-123");

      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({
          id: 1,
          name: "Restored User",
          email: "restored@example.com",
        }),
      });

      const { result } = renderHook(() => useAuth(), {
        wrapper: AuthProvider,
      });

      // 初期化時にプロフィール取得が呼ばれるまで待機
      await waitFor(() => {
        expect(result.current.user).toEqual({
          id: 1,
          name: "Restored User",
          email: "restored@example.com",
        });
      });

      expect(result.current.token).toBe("stored-token-123");
      expect(result.current.isAuthenticated).toBe(true);
    });

    it("localStorageのトークンが無効な場合、クリアする", async () => {
      localStorageMock.setItem("user_token", "invalid-token");

      mockFetch.mockResolvedValueOnce({
        ok: false,
        status: 401,
        json: async () => ({
          code: "AUTH.TOKEN_EXPIRED",
          message: "トークンの有効期限が切れています",
        }),
      });

      const { result } = renderHook(() => useAuth(), {
        wrapper: AuthProvider,
      });

      // トークン検証完了（isLoadingがfalse）まで待機
      await waitFor(
        () => {
          expect(result.current.isLoading).toBe(false);
        },
        { timeout: 3000 },
      );

      // トークン検証失敗後、状態がクリアされる
      expect(result.current.user).toBeNull();
      expect(result.current.token).toBeNull();
      expect(result.current.isAuthenticated).toBe(false);
      expect(localStorageMock.getItem("user_token")).toBeNull();
    });
  });
});

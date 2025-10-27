"use client";

import React, { createContext, useContext, useState, useEffect, ReactNode } from "react";
import { buildApiUrl, handleApiError } from "@/lib/api";

/**
 * ユーザー情報の型定義
 */
export interface User {
  id: number;
  name: string;
  email: string;
}

/**
 * ログインレスポンスの型定義
 */
interface LoginResponse {
  user: User;
  token: string;
}

/**
 * AuthContextの型定義
 */
interface AuthContextType {
  user: User | null;
  token: string | null;
  isLoading: boolean;
  isAuthenticated: boolean;
  login: (email: string, password: string) => Promise<void>;
  logout: () => Promise<void>;
  fetchUserProfile: () => Promise<void>;
}

/**
 * AuthContext作成
 */
const AuthContext = createContext<AuthContextType | undefined>(undefined);

/**
 * localStorageのキー定義
 */
const TOKEN_STORAGE_KEY = "user_token";

/**
 * AuthProvider Props
 */
interface AuthProviderProps {
  children: ReactNode;
}

/**
 * AuthProvider コンポーネント
 *
 * User App全体に認証状態を提供するContextプロバイダー
 */
export function AuthProvider({ children }: AuthProviderProps) {
  const [user, setUser] = useState<User | null>(null);
  const [token, setToken] = useState<string | null>(null);
  const [isLoading, setIsLoading] = useState(false);

  // 認証状態の計算
  const isAuthenticated = user !== null && token !== null;

  /**
   * ログイン処理
   *
   * @param email - ユーザーのメールアドレス
   * @param password - ユーザーのパスワード
   */
  const login = async (email: string, password: string): Promise<void> => {
    setIsLoading(true);

    try {
      const response = await fetch(buildApiUrl("user/login"), {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({ email, password }),
      });

      if (!response.ok) {
        await handleApiError(response);
      }

      const data: LoginResponse = await response.json();

      // ユーザー情報とトークンを保存
      setUser(data.user);
      setToken(data.token);

      // localStorageにトークンを保存
      localStorage.setItem(TOKEN_STORAGE_KEY, data.token);
    } finally {
      setIsLoading(false);
    }
  };

  /**
   * ログアウト処理
   */
  const logout = async (): Promise<void> => {
    try {
      if (token) {
        const response = await fetch(buildApiUrl("user/logout"), {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            Authorization: `Bearer ${token}`,
          },
        });

        if (!response.ok) {
          await handleApiError(response);
        }
      }
    } finally {
      // API呼び出しが失敗してもローカルの状態はクリアする
      setUser(null);
      setToken(null);
      localStorage.removeItem(TOKEN_STORAGE_KEY);
    }
  };

  /**
   * ユーザープロフィール取得
   */
  const fetchUserProfile = async (): Promise<void> => {
    if (!token) {
      throw new Error("トークンが存在しません");
    }

    setIsLoading(true);

    try {
      const response = await fetch(buildApiUrl("user/profile"), {
        method: "GET",
        headers: {
          "Content-Type": "application/json",
          Authorization: `Bearer ${token}`,
        },
      });

      if (!response.ok) {
        await handleApiError(response);
      }

      const userData: User = await response.json();
      setUser(userData);
    } finally {
      setIsLoading(false);
    }
  };

  /**
   * 初期化時のトークン復元処理
   */
  useEffect(() => {
    const restoreToken = async () => {
      const storedToken = localStorage.getItem(TOKEN_STORAGE_KEY);

      if (!storedToken) {
        return;
      }

      setToken(storedToken);
      setIsLoading(true);

      try {
        const response = await fetch(buildApiUrl("user/profile"), {
          method: "GET",
          headers: {
            "Content-Type": "application/json",
            Authorization: `Bearer ${storedToken}`,
          },
        });

        if (!response.ok) {
          // トークンが無効な場合はクリア
          localStorage.removeItem(TOKEN_STORAGE_KEY);
          setToken(null);
          return;
        }

        const userData: User = await response.json();
        setUser(userData);
      } catch {
        // エラー時はトークンをクリア
        localStorage.removeItem(TOKEN_STORAGE_KEY);
        setToken(null);
      } finally {
        setIsLoading(false);
      }
    };

    restoreToken();
  }, []);

  const value: AuthContextType = {
    user,
    token,
    isLoading,
    isAuthenticated,
    login,
    logout,
    fetchUserProfile,
  };

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

/**
 * useAuth フック
 *
 * AuthContextにアクセスするためのカスタムフック
 */
export function useAuth(): AuthContextType {
  const context = useContext(AuthContext);

  if (context === undefined) {
    throw new Error("useAuth must be used within an AuthProvider");
  }

  return context;
}

"use client";

import React, { createContext, useContext, useState, useEffect, ReactNode } from "react";
import { handleApiError } from "@/lib/api-error-handler";

/**
 * 管理者情報の型定義
 */
export interface Admin {
  id: number;
  name: string;
  email: string;
}

/**
 * AdminAuthContext の型定義
 */
interface AdminAuthContextType {
  admin: Admin | null;
  token: string | null;
  isLoading: boolean;
  isAuthenticated: boolean;
  login: (email: string, password: string) => Promise<void>;
  logout: () => Promise<void>;
  fetchAdminInfo: () => Promise<void>;
}

/**
 * AdminAuthContext
 */
const AdminAuthContext = createContext<AdminAuthContextType | undefined>(undefined);

/**
 * AdminAuthProvider Props
 */
interface AdminAuthProviderProps {
  children: ReactNode;
}

/**
 * トークン保存キー
 */
const TOKEN_STORAGE_KEY = "admin_token";

/**
 * API Base URL
 */
const API_BASE_URL = process.env.NEXT_PUBLIC_API_URL || "http://localhost:13000";
const API_VERSION = process.env.NEXT_PUBLIC_API_VERSION || "v1";

/**
 * AdminAuthProvider
 *
 * Admin App の認証状態管理を提供します。
 * - login/logout/fetchAdminInfo メソッド
 * - admin、token、isLoading、isAuthenticated 状態管理
 * - トークンを localStorage に保存（key: admin_token）
 * - 初回ロード時のトークン復元
 */
export function AdminAuthProvider({ children }: AdminAuthProviderProps) {
  const [admin, setAdmin] = useState<Admin | null>(null);
  const [token, setToken] = useState<string | null>(null);
  const [isLoading, setIsLoading] = useState(false);

  /**
   * トークン復元処理
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
        // トークン検証のため管理者情報を取得
        const response = await fetch(`${API_BASE_URL}/api/${API_VERSION}/admin/dashboard`, {
          method: "GET",
          headers: {
            "Content-Type": "application/json",
            Authorization: `Bearer ${storedToken}`,
          },
        });

        if (!response.ok) {
          const errorData = await response.json();
          throw handleApiError(errorData, response.status);
        }

        const adminData = await response.json();
        setAdmin(adminData);
      } catch (error) {
        // トークンが無効な場合はクリア
        console.error("Token validation failed:", error);
        setToken(null);
        setAdmin(null);
        localStorage.removeItem(TOKEN_STORAGE_KEY);
      } finally {
        setIsLoading(false);
      }
    };

    restoreToken();
  }, []);

  /**
   * ログイン処理
   */
  const login = async (email: string, password: string): Promise<void> => {
    setIsLoading(true);

    try {
      const response = await fetch(`${API_BASE_URL}/api/${API_VERSION}/admin/login`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({ email, password }),
      });

      if (!response.ok) {
        const errorData = await response.json();
        throw handleApiError(errorData, response.status);
      }

      const data = await response.json();

      setAdmin(data.admin);
      setToken(data.token);
      localStorage.setItem(TOKEN_STORAGE_KEY, data.token);
    } catch (error) {
      console.error("Login failed:", error);
      throw error;
    } finally {
      setIsLoading(false);
    }
  };

  /**
   * ログアウト処理
   */
  const logout = async (): Promise<void> => {
    setIsLoading(true);

    try {
      if (token) {
        const response = await fetch(`${API_BASE_URL}/api/${API_VERSION}/admin/logout`, {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            Authorization: `Bearer ${token}`,
          },
        });

        if (!response.ok) {
          const errorData = await response.json();
          throw handleApiError(errorData, response.status);
        }
      }
    } catch (error) {
      console.error("Logout failed:", error);
      throw error;
    } finally {
      // エラーが発生してもローカルの状態はクリア
      setAdmin(null);
      setToken(null);
      localStorage.removeItem(TOKEN_STORAGE_KEY);
      setIsLoading(false);
    }
  };

  /**
   * 管理者情報取得
   */
  const fetchAdminInfo = async (): Promise<void> => {
    if (!token) {
      throw new Error("トークンが存在しません");
    }

    setIsLoading(true);

    try {
      const response = await fetch(`${API_BASE_URL}/api/${API_VERSION}/admin/dashboard`, {
        method: "GET",
        headers: {
          "Content-Type": "application/json",
          Authorization: `Bearer ${token}`,
        },
      });

      if (!response.ok) {
        const errorData = await response.json();
        throw handleApiError(errorData, response.status);
      }

      const adminData = await response.json();
      setAdmin(adminData);
    } catch (error) {
      console.error("Failed to fetch admin info:", error);
      throw error;
    } finally {
      setIsLoading(false);
    }
  };

  const value: AdminAuthContextType = {
    admin,
    token,
    isLoading,
    isAuthenticated: !!admin && !!token,
    login,
    logout,
    fetchAdminInfo,
  };

  return <AdminAuthContext.Provider value={value}>{children}</AdminAuthContext.Provider>;
}

/**
 * useAdminAuth Hook
 *
 * AdminAuthContext を使用するためのカスタムフック
 */
export function useAdminAuth(): AdminAuthContextType {
  const context = useContext(AdminAuthContext);

  if (context === undefined) {
    throw new Error("useAdminAuth must be used within AdminAuthProvider");
  }

  return context;
}

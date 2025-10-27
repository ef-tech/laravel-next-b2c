"use client";

import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import { useAdminAuth } from "@/contexts/AdminAuthContext";

/**
 * ダッシュボードページ（Admin App）
 *
 * Admin App の認証済み管理者情報表示を提供します。
 * - 認証済み管理者のダッシュボード表示
 * - ログアウト機能
 * - 未認証時は自動的にログイン画面へリダイレクト
 */
export default function DashboardPage() {
  const router = useRouter();
  const { admin, isLoading, logout } = useAdminAuth();
  const [isLoggingOut, setIsLoggingOut] = useState(false);

  // 未認証の場合はログイン画面へリダイレクト
  useEffect(() => {
    if (!isLoading && !admin) {
      router.push("/login");
    }
  }, [admin, isLoading, router]);

  /**
   * ログアウト処理
   */
  const handleLogout = async () => {
    setIsLoggingOut(true);
    try {
      await logout();
      router.push("/login");
    } catch (error) {
      console.error("Logout failed:", error);
      setIsLoggingOut(false);
    }
  };

  // ローディング中
  if (isLoading) {
    return (
      <div className="flex min-h-screen items-center justify-center">
        <p className="text-gray-600">読み込み中...</p>
      </div>
    );
  }

  // 未認証（リダイレクト処理中）
  if (!admin) {
    return null;
  }

  return (
    <div className="min-h-screen bg-gray-50 px-4 py-12 sm:px-6 lg:px-8">
      <div className="mx-auto max-w-4xl">
        {/* ヘッダー */}
        <div className="mb-8">
          <h1 className="text-3xl font-bold text-gray-900">
            管理者ダッシュボード
          </h1>
          <p className="mt-2 text-gray-600">Admin App</p>
        </div>

        {/* 管理者情報カード */}
        <div className="overflow-hidden rounded-lg bg-white shadow">
          <div className="border-b border-gray-200 bg-gray-50 px-6 py-4">
            <h2 className="text-lg font-medium text-gray-900">管理者情報</h2>
          </div>
          <div className="px-6 py-6">
            <dl className="space-y-4">
              <div>
                <dt className="text-sm font-medium text-gray-500">名前</dt>
                <dd className="mt-1 text-base text-gray-900">{admin.name}</dd>
              </div>
              <div>
                <dt className="text-sm font-medium text-gray-500">
                  メールアドレス
                </dt>
                <dd className="mt-1 text-base text-gray-900">{admin.email}</dd>
              </div>
              <div>
                <dt className="text-sm font-medium text-gray-500">役割</dt>
                <dd className="mt-1 text-base text-gray-900">
                  {admin.role || "管理者"}
                </dd>
              </div>
            </dl>
          </div>
        </div>

        {/* ログアウトボタン */}
        <div className="mt-6">
          <button
            onClick={handleLogout}
            disabled={isLoggingOut}
            className="w-full rounded-md border border-transparent bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 focus:ring-2 focus:ring-red-500 focus:ring-offset-2 focus:outline-none disabled:cursor-not-allowed disabled:bg-gray-400 sm:w-auto"
          >
            {isLoggingOut ? "ログアウト中..." : "ログアウト"}
          </button>
        </div>
      </div>
    </div>
  );
}

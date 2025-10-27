"use client";

import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import { useAuth } from "@/contexts/AuthContext";
import LogoutButton from "@/components/LogoutButton";

/**
 * プロフィールページ
 *
 * User App の認証済みユーザー情報表示を提供します。
 * - 認証済みユーザーのプロフィール表示
 * - GET /api/v1/user/profile呼び出し
 * - 未認証時にログイン画面へリダイレクト
 * - エラーハンドリング
 */
export default function ProfilePage() {
  const router = useRouter();
  const { user, isAuthenticated, isLoading, fetchUserProfile } = useAuth();
  const [error, setError] = useState<string>("");

  /**
   * 初回表示時とisAuthenticated変更時の処理
   */
  useEffect(() => {
    const loadProfile = async () => {
      // ローディング中はスキップ
      if (isLoading) {
        return;
      }

      // 未認証の場合はログイン画面へリダイレクト
      if (!isAuthenticated) {
        router.push("/login");
        return;
      }

      // プロフィール取得
      try {
        await fetchUserProfile();
      } catch (err) {
        console.error("Failed to fetch user profile:", err);
        setError("プロフィール情報の取得に失敗しました。");
      }
    };

    loadProfile();
  }, [isAuthenticated, isLoading, fetchUserProfile, router]);

  /**
   * ローディング中の表示
   */
  if (isLoading) {
    return (
      <div className="flex min-h-screen items-center justify-center bg-gray-50">
        <div className="text-center">
          <p className="text-lg text-gray-600">読み込み中...</p>
        </div>
      </div>
    );
  }

  /**
   * エラー表示
   */
  if (error) {
    return (
      <div className="flex min-h-screen items-center justify-center bg-gray-50 px-4 py-12 sm:px-6 lg:px-8">
        <div className="w-full max-w-md space-y-8">
          <div className="rounded-md bg-red-50 p-4">
            <p className="text-sm text-red-800">{error}</p>
          </div>
          <LogoutButton className="w-full" />
        </div>
      </div>
    );
  }

  /**
   * プロフィール表示
   */
  return (
    <div className="min-h-screen bg-gray-50 px-4 py-12 sm:px-6 lg:px-8">
      <div className="mx-auto max-w-3xl">
        <div className="overflow-hidden bg-white shadow sm:rounded-lg">
          <div className="px-4 py-5 sm:px-6">
            <h3 className="text-lg leading-6 font-medium text-gray-900">プロフィール</h3>
            <p className="mt-1 max-w-2xl text-sm text-gray-500">ユーザー情報</p>
          </div>
          <div className="border-t border-gray-200">
            <dl>
              <div className="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt className="text-sm font-medium text-gray-500">ID</dt>
                <dd className="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{user?.id}</dd>
              </div>
              <div className="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt className="text-sm font-medium text-gray-500">名前</dt>
                <dd className="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{user?.name}</dd>
              </div>
              <div className="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt className="text-sm font-medium text-gray-500">メールアドレス</dt>
                <dd className="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{user?.email}</dd>
              </div>
            </dl>
          </div>
        </div>

        <div className="mt-6">
          <LogoutButton className="w-full" />
        </div>
      </div>
    </div>
  );
}

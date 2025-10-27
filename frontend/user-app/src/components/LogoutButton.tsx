"use client";

import { useRouter } from "next/navigation";
import { useAuth } from "@/contexts/AuthContext";

interface LogoutButtonProps {
  className?: string;
  children?: React.ReactNode;
}

/**
 * ログアウトボタンコンポーネント
 *
 * User App のログアウト機能を提供します。
 * - ボタンクリックでログアウト処理を実行
 * - ログアウト成功後にログイン画面へリダイレクト
 * - エラー時もログイン画面へリダイレクト
 * - カスタムクラス名とテキストに対応
 */
export default function LogoutButton({ className = "", children }: LogoutButtonProps) {
  const router = useRouter();
  const { logout, isLoading } = useAuth();

  /**
   * ログアウト処理
   */
  const handleLogout = async () => {
    try {
      await logout();
    } catch (error) {
      // エラーが発生してもログイン画面へリダイレクト
      console.error("Logout error:", error);
    } finally {
      // 成功時もエラー時もログイン画面へリダイレクト
      router.push("/login");
    }
  };

  return (
    <button
      onClick={handleLogout}
      disabled={isLoading}
      className={`rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 focus:ring-2 focus:ring-red-500 focus:ring-offset-2 focus:outline-none disabled:cursor-not-allowed disabled:bg-gray-400 ${className}`}
    >
      {isLoading ? "処理中..." : children || "ログアウト"}
    </button>
  );
}

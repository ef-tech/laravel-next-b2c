"use client";

import { useState, FormEvent } from "react";
import { useRouter } from "next/navigation";
import { useAuth } from "@/contexts/AuthContext";
import { ErrorHandlers } from "@/lib/api";

/**
 * バリデーションエラーの型定義
 */
interface ValidationErrors {
  email?: string;
  password?: string;
}

/**
 * ログインページ
 *
 * User App のログイン画面を提供します。
 * - email/passwordフォーム
 * - クライアント側バリデーション
 * - エラーハンドリング（Task 8.3のErrorHandlers使用）
 * - ログイン成功時にホーム画面へリダイレクト
 */
export default function LoginPage() {
  const router = useRouter();
  const { login, isLoading } = useAuth();

  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [validationErrors, setValidationErrors] = useState<ValidationErrors>({});
  const [apiError, setApiError] = useState<string>("");

  /**
   * バリデーション実行
   */
  const validate = (): boolean => {
    const errors: ValidationErrors = {};

    if (!email) {
      errors.email = "メールアドレスを入力してください";
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
      errors.email = "正しいメールアドレスの形式で入力してください";
    }

    if (!password) {
      errors.password = "パスワードを入力してください";
    } else if (password.length < 8) {
      errors.password = "パスワードは8文字以上で入力してください";
    }

    setValidationErrors(errors);
    return Object.keys(errors).length === 0;
  };

  /**
   * ログイン処理
   */
  const handleSubmit = async (e: FormEvent) => {
    e.preventDefault();

    // バリデーション
    if (!validate()) {
      return;
    }

    setApiError("");

    try {
      await login(email, password);

      // ログイン成功時にホーム画面へリダイレクト
      router.push("/");
    } catch (error) {
      // エラーハンドリング（Task 8.3のErrorHandlers使用）
      if (ErrorHandlers.isInvalidCredentials(error)) {
        setApiError("メールアドレスまたはパスワードが正しくありません");
      } else if (ErrorHandlers.isAccountDisabled(error)) {
        setApiError("アカウントが無効化されています");
      } else {
        setApiError("ログインに失敗しました。しばらくしてから再度お試しください。");
      }
    }
  };

  /**
   * メールアドレス変更時の処理
   */
  const handleEmailChange = (value: string) => {
    setEmail(value);
    // エラーをクリア
    if (validationErrors.email) {
      setValidationErrors((prev) => ({ ...prev, email: undefined }));
    }
    if (apiError) {
      setApiError("");
    }
  };

  /**
   * パスワード変更時の処理
   */
  const handlePasswordChange = (value: string) => {
    setPassword(value);
    // エラーをクリア
    if (validationErrors.password) {
      setValidationErrors((prev) => ({ ...prev, password: undefined }));
    }
    if (apiError) {
      setApiError("");
    }
  };

  return (
    <div className="flex min-h-screen items-center justify-center bg-gray-50 px-4 py-12 sm:px-6 lg:px-8">
      <div className="w-full max-w-md space-y-8">
        <div>
          <h2 className="mt-6 text-center text-3xl font-extrabold text-gray-900">
            User App ログイン
          </h2>
        </div>
        <form className="mt-8 space-y-6" onSubmit={handleSubmit}>
          {/* APIエラーメッセージ */}
          {apiError && (
            <div className="rounded-md bg-red-50 p-4">
              <p className="text-sm text-red-800">{apiError}</p>
            </div>
          )}

          <div className="-space-y-px rounded-md shadow-sm">
            {/* メールアドレス入力 */}
            <div>
              <label htmlFor="email-address" className="sr-only">
                メールアドレス
              </label>
              <input
                id="email-address"
                name="email"
                type="text"
                autoComplete="email"
                className="relative block w-full appearance-none rounded-none rounded-t-md border border-gray-300 px-3 py-2 text-gray-900 placeholder-gray-500 focus:z-10 focus:border-indigo-500 focus:ring-indigo-500 focus:outline-none sm:text-sm"
                placeholder="メールアドレス"
                value={email}
                onChange={(e) => handleEmailChange(e.target.value)}
                disabled={isLoading}
              />
              {validationErrors.email && (
                <p className="mt-1 text-xs text-red-600">{validationErrors.email}</p>
              )}
            </div>

            {/* パスワード入力 */}
            <div>
              <label htmlFor="password" className="sr-only">
                パスワード
              </label>
              <input
                id="password"
                name="password"
                type="password"
                autoComplete="current-password"
                className="relative block w-full appearance-none rounded-none rounded-b-md border border-gray-300 px-3 py-2 text-gray-900 placeholder-gray-500 focus:z-10 focus:border-indigo-500 focus:ring-indigo-500 focus:outline-none sm:text-sm"
                placeholder="パスワード"
                value={password}
                onChange={(e) => handlePasswordChange(e.target.value)}
                disabled={isLoading}
              />
              {validationErrors.password && (
                <p className="mt-1 text-xs text-red-600">{validationErrors.password}</p>
              )}
            </div>
          </div>

          {/* ログインボタン */}
          <div>
            <button
              type="submit"
              disabled={isLoading}
              className="group relative flex w-full justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-none disabled:cursor-not-allowed disabled:bg-gray-400"
            >
              {isLoading ? "処理中..." : "ログイン"}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}

/**
 * 環境変数バリデーションとTypeScript型定義（User App）
 *
 * Zodスキーマを使用して、環境変数の実行時バリデーションと型推論を提供します。
 * ビルド時およびdev起動時に自動的にバリデーションが実行されます。
 *
 * @see design.md セクション3.2.1: Zodスキーマ実装
 */

import { z } from "zod";

/**
 * 環境変数スキーマ定義（User App）
 *
 * User App で使用される環境変数を定義します。
 * - NEXT_PUBLIC_API_URL: Laravel APIのベースURL（クライアント側で使用）
 * - NEXT_PUBLIC_API_VERSION: APIバージョン（エンドポイントURL構築に使用）
 * - NEXT_PUBLIC_APP_NAME: アプリケーション名
 * - NODE_ENV: Next.js実行環境
 */
const envSchema = z.object({
  NEXT_PUBLIC_API_URL: z
    .string()
    .url("NEXT_PUBLIC_API_URL は有効なURL形式である必要があります")
    .default("http://localhost:13000"),

  NEXT_PUBLIC_API_VERSION: z
    .string()
    .regex(/^v\d+$/, "NEXT_PUBLIC_API_VERSION は 'v1', 'v2' のような形式である必要があります")
    .default("v1"),

  NEXT_PUBLIC_APP_NAME: z.string().default("User App"),

  NODE_ENV: z.enum(["development", "production", "test"]).default("development"),
});

/**
 * 環境変数のバリデーション実行
 *
 * process.env から環境変数を読み込み、Zodスキーマでバリデーションを実行します。
 * バリデーションエラー時は、詳細なエラーメッセージを表示してプロセスを停止します。
 */
const parsedEnv = envSchema.safeParse({
  NEXT_PUBLIC_API_URL: process.env.NEXT_PUBLIC_API_URL,
  NEXT_PUBLIC_API_VERSION: process.env.NEXT_PUBLIC_API_VERSION,
  NEXT_PUBLIC_APP_NAME: process.env.NEXT_PUBLIC_APP_NAME,
  NODE_ENV: process.env.NODE_ENV as "development" | "production" | "test",
});

if (!parsedEnv.success) {
  console.error("❌ 環境変数のバリデーションに失敗しました:");
  console.error(parsedEnv.error.flatten().fieldErrors);
  throw new Error("環境変数が正しく設定されていません。.env.local を確認してください。");
}

/**
 * 型安全な環境変数エクスポート
 *
 * TypeScript型推論により、envオブジェクトのプロパティアクセス時に
 * コンパイル時型チェックが有効化されます。
 *
 * @example
 * import { env } from '@/lib/env';
 * const apiUrl = env.NEXT_PUBLIC_API_URL; // string型として推論される
 * const appName = env.NEXT_PUBLIC_APP_NAME; // string型として推論される
 */
export const env = parsedEnv.data;

/**
 * TypeScript型エクスポート
 *
 * Zodスキーマから型を推論し、他のモジュールでも使用可能にします。
 */
export type Env = z.infer<typeof envSchema>;

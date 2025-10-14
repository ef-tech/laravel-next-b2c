import { FlatCompat } from "@eslint/eslintrc";
import eslintConfigPrettier from "eslint-config-prettier";
import jestPlugin from "eslint-plugin-jest";
import testingLibrary from "eslint-plugin-testing-library";
import jestDom from "eslint-plugin-jest-dom";
import globals from "globals";
import { fileURLToPath } from "node:url";
import path from "node:path";

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const compat = new FlatCompat({ baseDirectory: __dirname });

export default [
  // Next.js推奨 + TypeScript推奨
  ...compat.extends("next/core-web-vitals", "next/typescript"),

  // カスタムルール（最小限）
  {
    rules: {
      "no-console": ["warn", { allow: ["warn", "error"] }],
      "no-debugger": "warn",
      "@typescript-eslint/no-unused-vars": [
        "warn",
        { argsIgnorePattern: "^_", varsIgnorePattern: "^_" },
      ],
    },
  },

  // 共通ignore
  {
    ignores: [
      "**/node_modules/**",
      "**/.next/**",
      "**/out/**",
      "**/build/**",
      "**/dist/**",
      "**/*.min.*",
      "**/next-env.d.ts",
      "**/jest.config.js",
    ],
  },

  // テストファイル専用オーバーライド
  {
    files: ["**/*.{test,spec}.{ts,tsx,js,jsx}", "**/__tests__/**/*.{ts,tsx,js,jsx}"],
    plugins: {
      jest: jestPlugin,
      "testing-library": testingLibrary,
      "jest-dom": jestDom,
    },
    languageOptions: {
      globals: globals.jest,
    },
    rules: {
      // Jest推奨ルールセット適用（flat/recommended）
      ...jestPlugin.configs["flat/recommended"].rules,
      // Testing Library推奨ルールセット適用（flat/react）
      ...testingLibrary.configs["flat/react"].rules,
      // Jest-DOM推奨ルールセット適用（flat/recommended）
      ...jestDom.configs["flat/recommended"].rules,

      // テスト特有の調整
      "no-console": "off", // デバッグ容易性優先
      "@typescript-eslint/no-unused-vars": [
        "warn",
        {
          argsIgnorePattern: "^_",
          varsIgnorePattern: "^_",
          caughtErrors: "none",
        },
      ],
      "no-empty-function": "off", // テスト用モック関数（jest.fn()等）での空関数定義を許容

      // Phase 3完了: 全ルールerrorレベル適用（推奨ルールセットのデフォルト）
      // Phase 1-2で段階的に昇格、Phase 3で完全適用
    },
  },

  // Prettier競合ルール無効化（必ず最後）
  eslintConfigPrettier,
];
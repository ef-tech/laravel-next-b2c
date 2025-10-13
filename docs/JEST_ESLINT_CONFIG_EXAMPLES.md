# Jest/Testing Library ESLint設定サンプル集

## 概要

このドキュメントでは、Jest/Testing LibraryのESLint設定における主要なパターンとカスタマイズ例を紹介します。

## 基本設定（現在のプロジェクト設定）

### frontend/.eslint.base.mjs

```javascript
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
      "no-empty-function": "off", // jest.fn()許容

      // 初期フェーズ: 低ノイズルールをwarnレベル維持（Phase 1）
      "testing-library/no-node-access": "warn",
      "testing-library/no-container": "warn",
      "testing-library/no-debugging-utils": "warn",
    },
  },

  // Prettier競合ルール無効化（必ず最後）
  eslintConfigPrettier,
];
```

## カスタマイズ例

### 1. 特定ルールの無効化

プロジェクト要件により特定ルールを無効化する場合：

```javascript
{
  files: ["**/*.{test,spec}.{ts,tsx,js,jsx}"],
  rules: {
    // renderResult分割代入を許可
    "testing-library/prefer-screen-queries": "off",

    // data-testid使用を許可（特定コンポーネントのみ）
    "testing-library/prefer-presence-queries": "off",
  },
}
```

### 2. E2Eテスト用のカスタマイズ

E2Eテスト（Playwright等）向けの別設定：

```javascript
{
  files: ["**/e2e/**/*.{test,spec}.{ts,tsx,js,jsx}"],
  plugins: {
    // E2E用プラグイン（jest非適用）
  },
  rules: {
    // jest系ルールを無効化
    "jest/expect-expect": "off",
    "jest/no-standalone-expect": "off",
  },
}
```

### 3. Phase 2: 低ノイズルール昇格設定

Phase 1完了後のPhase 2設定例：

```javascript
{
  files: ["**/*.{test,spec}.{ts,tsx,js,jsx}"],
  rules: {
    // Phase 2: 低ノイズルールをerrorへ昇格
    "testing-library/no-node-access": "error",      // warn → error
    "testing-library/no-container": "error",        // warn → error
    "testing-library/no-debugging-utils": "error",  // warn → error
  },
}
```

### 4. Phase 3: 全ルールerror設定

Phase 2完了後のPhase 3設定例：

```javascript
{
  files: ["**/*.{test,spec}.{ts,tsx,js,jsx}"],
  rules: {
    // Phase 3: 全ルールerror（推奨ルールセットのデフォルト）
    ...jestPlugin.configs["flat/recommended"].rules,
    ...testingLibrary.configs["flat/react"].rules,
    ...jestDom.configs["flat/recommended"].rules,

    // カスタム調整のみwarn維持（必要に応じて）
    "@typescript-eslint/no-unused-vars": ["warn", {
      argsIgnorePattern: "^_",
      varsIgnorePattern: "^_",
      caughtErrors: "none",
    }],
  },
}
```

### 5. プロジェクト別のファイルパターン拡張

特定ディレクトリのみテストルールを適用：

```javascript
{
  files: [
    "src/**/*.{test,spec}.{ts,tsx,js,jsx}",
    "tests/**/*.{test,spec}.{ts,tsx,js,jsx}",
    "**/__tests__/**/*.{ts,tsx,js,jsx}",
    "**/__mocks__/**/*.{ts,tsx,js,jsx}",  // モックファイルも対象
  ],
  // ... rules
}
```

### 6. CI専用の厳格モード

CI環境でのみ厳格なルール適用：

```javascript
// eslint.config.ci.js
export default [
  ...baseConfig,
  {
    files: ["**/*.{test,spec}.{ts,tsx,js,jsx}"],
    rules: {
      // CI環境では全てerror
      "testing-library/no-node-access": "error",
      "testing-library/no-container": "error",
      "testing-library/no-debugging-utils": "error",
      "testing-library/no-render-in-lifecycle": "error",
      "testing-library/prefer-explicit-assert": "error",
    },
  },
];
```

GitHub Actionsでの使用：

```yaml
- name: Run ESLint (CI mode)
  run: npx eslint . --config eslint.config.ci.js
```

## ルール個別カスタマイズ例

### jest/expect-expect

カスタムアサーション関数を認識：

```javascript
{
  rules: {
    "jest/expect-expect": [
      "error",
      {
        assertFunctionNames: [
          "expect",
          "expectToBeVisible",  // カスタムアサーション
          "expectToBeClickable",
        ],
      },
    ],
  },
}
```

### testing-library/prefer-user-event

userEvent優先度を調整：

```javascript
{
  rules: {
    "testing-library/prefer-user-event": [
      "warn",  // 段階的導入のためwarn
      {
        allowedMethods: ["click", "type"],  // 特定メソッドのみfireEvent許可
      },
    ],
  },
}
```

### jest/no-disabled-tests

CI環境のみ厳格化：

```javascript
{
  rules: {
    "jest/no-disabled-tests": process.env.CI ? "error" : "warn",
    "jest/no-focused-tests": process.env.CI ? "error" : "warn",
  },
}
```

## TypeScript統合パターン

### @typescript-eslint連携

```javascript
{
  files: ["**/*.{test,spec}.{ts,tsx}"],
  rules: {
    // テスト内のany許容（モック型定義の簡略化）
    "@typescript-eslint/no-explicit-any": "off",

    // テストヘルパー関数の戻り値型推論許容
    "@typescript-eslint/explicit-module-boundary-types": "off",

    // テスト用非nullアサーション許容
    "@typescript-eslint/no-non-null-assertion": "off",
  },
}
```

## プロジェクト構成別の推奨設定

### モノレポ構成（現在のプロジェクト）

```
laravel-next-b2c/
├── frontend/
│   ├── .eslint.base.mjs          # 共通設定（テストルール含む）
│   ├── admin-app/
│   │   └── eslint.config.mjs     # .eslint.base.mjsを継承
│   └── user-app/
│       └── eslint.config.mjs     # .eslint.base.mjsを継承
└── package.json                   # モノレポルート
```

### シングルアプリ構成

```
my-app/
├── eslint.config.js              # 全設定を1ファイルに集約
├── src/
│   ├── components/
│   │   └── Button.test.tsx
│   └── __tests__/
└── package.json
```

## トラブルシューティング用設定

### デバッグモード有効化

```javascript
export default [
  // ... 他の設定
  {
    files: ["**/*.{test,spec}.{ts,tsx,js,jsx}"],
    rules: {
      // デバッグ時のみscreen.debug()を許可
      "testing-library/no-debugging-utils": process.env.DEBUG ? "off" : "warn",
    },
  },
];
```

使用例：

```bash
DEBUG=true npm run lint  # デバッグモードでリント実行
```

### ルール一覧出力

現在適用されているルールを確認：

```bash
npx eslint --print-config frontend/admin-app/src/components/Button/Button.test.tsx | grep -E "jest/|testing-library/|jest-dom/"
```

## 関連ドキュメント

- [JEST_ESLINT_INTEGRATION_GUIDE.md](./JEST_ESLINT_INTEGRATION_GUIDE.md) - メインガイド
- [JEST_ESLINT_QUICKSTART.md](./JEST_ESLINT_QUICKSTART.md) - クイックスタート
- [JEST_ESLINT_TROUBLESHOOTING.md](./JEST_ESLINT_TROUBLESHOOTING.md) - トラブルシューティング

## 参考リンク

- [ESLint Flat Config Documentation](https://eslint.org/docs/latest/use/configure/configuration-files)
- [eslint-plugin-jest Rules](https://github.com/jest-community/eslint-plugin-jest#rules)
- [eslint-plugin-testing-library Rules](https://github.com/testing-library/eslint-plugin-testing-library#supported-rules)
- [eslint-plugin-jest-dom Rules](https://github.com/testing-library/eslint-plugin-jest-dom#supported-rules)

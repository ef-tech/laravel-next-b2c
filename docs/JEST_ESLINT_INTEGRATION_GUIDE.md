# Jest/Testing Library ESLint統合ガイド

## 概要

このプロジェクトでは、Jest 29とReact Testing Library 16を使用したテストコードに対して、ESLint 9による静的解析を導入しています。テストコード特有のベストプラクティスを強制し、品質と一貫性を向上させることを目的としています。

## 主な機能

### 導入済みESLintプラグイン

1. **eslint-plugin-jest** (v28.14.0)
   - Jestテストのベストプラクティスを強制
   - 主要ルール: `jest/no-focused-tests`, `jest/expect-expect`, `jest/valid-expect`

2. **eslint-plugin-testing-library** (v6.5.0)
   - Testing Libraryクエリパターンのベストプラクティスを強制
   - 主要ルール: `testing-library/prefer-screen-queries`, `testing-library/await-async-queries`

3. **eslint-plugin-jest-dom** (v5.5.0)
   - Jest-DOMマッチャーの推奨パターンを強制
   - 主要ルール: `jest-dom/prefer-checked`, `jest-dom/prefer-enabled-disabled`

4. **globals** (v15.15.0)
   - Jest標準関数（describe, it, expect等）のグローバル定義を提供

## 設定概要

### テストファイル識別パターン

以下のパターンに一致するファイルにテスト用ESLintルールが適用されます：

```javascript
files: [
  "**/*.{test,spec}.{ts,tsx,js,jsx}",
  "**/__tests__/**/*.{ts,tsx,js,jsx}"
]
```

### 適用ルールセット

- **Jest推奨ルール**: `jestPlugin.configs["flat/recommended"].rules`
- **Testing Library推奨ルール**: `testingLibrary.configs["flat/react"].rules`
- **Jest-DOM推奨ルール**: `jestDom.configs["flat/recommended"].rules`

### テスト特有のカスタマイズ

```javascript
rules: {
  "no-console": "off",  // テストデバッグ容易性優先
  "@typescript-eslint/no-unused-vars": ["warn", {
    argsIgnorePattern: "^_",
    varsIgnorePattern: "^_",
    caughtErrors: "none",
  }],
  "no-empty-function": "off",  // jest.fn()許容
}
```

## パフォーマンス測定結果（Phase 1導入後）

### 実行時間（テストルール導入済み）

- **admin-app**: 約1.5秒（初回）、約1.2秒（キャッシュ有効時）
- **user-app**: 約1.3秒
- **モノレポ全体**: 約2.7秒（並列実行）

### キャッシュ効果

ESLint 9のキャッシュ機能により、2回目以降の実行で約20-30%高速化を確認。

## 段階的ルール昇格戦略

### Phase 1: 初期導入（現在フェーズ）

- **ルールレベル**: warn
- **対象ルール**: 低ノイズルール（no-node-access, no-container, no-debugging-utils）
- **目的**: チーム慣熟と誤検出パターン特定

### Phase 2: 低ノイズルール昇格（計画中）

- **ルールレベル**: warn → error
- **対象ルール**:
  - `testing-library/no-node-access`
  - `testing-library/no-container`
  - `testing-library/no-debugging-utils`
- **開始条件**: Phase 1で1週間以上警告ゼロ維持

### Phase 3: 全ルール昇格（計画中）

- **ルールレベル**: 全てerror
- **開始条件**: Phase 2で1週間以上警告ゼロ維持

## 利用方法

### ローカル開発

```bash
# 全ワークスペースでリント実行
npm run lint

# 特定ワークスペースでリント実行
npm run lint --workspace=frontend/admin-app

# 自動修正付きリント実行
npm run lint:fix --workspace=frontend/admin-app
```

### Pre-commit Hook統合

Huskyとlint-stagedにより、コミット前に自動的にESLintが実行されます。

```json
"lint-staged": {
  "frontend/admin-app/**/*.{js,jsx,ts,tsx}": [
    "eslint --fix --max-warnings=0"
  ],
  "frontend/user-app/**/*.{js,jsx,ts,tsx}": [
    "eslint --fix --max-warnings=0"
  ]
}
```

### CI/CD統合

GitHub Actionsで自動リント検証が実行されます（`.github/workflows/frontend-test.yml`）。

```yaml
jobs:
  lint:
    runs-on: ubuntu-latest
    strategy:
      matrix:
        node-version: [20.x]
        app: [admin-app, user-app]
    steps:
      - name: Run ESLint
        run: npm run lint
        working-directory: frontend/${{ matrix.app }}
```

## 主要ルール解説

### jest/no-focused-tests

focused test（fit, fdescribe）の使用を禁止。

```typescript
// ❌ Bad
fit("focused test", () => {
  expect(true).toBe(true);
});

// ✅ Good
it("normal test", () => {
  expect(true).toBe(true);
});
```

### testing-library/prefer-screen-queries

`screen`オブジェクト経由のクエリを推奨。

```typescript
// ❌ Bad
const { getByRole } = render(<Button />);
getByRole("button");

// ✅ Good
render(<Button />);
screen.getByRole("button");
```

### jest-dom/prefer-checked

checkboxの状態検証には`.toBeChecked()`を推奨。

```typescript
// ❌ Bad
expect(checkbox.checked).toBe(true);

// ✅ Good
expect(checkbox).toBeChecked();
```

## トラブルシューティング

詳細は [JEST_ESLINT_TROUBLESHOOTING.md](./JEST_ESLINT_TROUBLESHOOTING.md) を参照してください。

### よくある問題

1. **`describe is not defined` エラー**
   - 原因: globals.jest設定が適用されていない
   - 解決: テストファイルパターンが`**/*.{test,spec}.{ts,tsx,js,jsx}`に一致するか確認

2. **警告数が多すぎる**
   - 現在はPhase 1（warnレベル）のため、警告は許容される
   - Phase 2/3への昇格前に段階的に修正

3. **パフォーマンス問題**
   - ESLintキャッシュ機能の活用（`--cache`オプション）
   - ファイルパターンによるスコープ制限が有効

## 関連ドキュメント

- [JEST_ESLINT_CONFIG_EXAMPLES.md](./JEST_ESLINT_CONFIG_EXAMPLES.md) - 設定サンプル集
- [JEST_ESLINT_QUICKSTART.md](./JEST_ESLINT_QUICKSTART.md) - クイックスタートガイド
- [JEST_ESLINT_TROUBLESHOOTING.md](./JEST_ESLINT_TROUBLESHOOTING.md) - トラブルシューティング

## 参考リンク

- [eslint-plugin-jest Documentation](https://github.com/jest-community/eslint-plugin-jest)
- [eslint-plugin-testing-library Documentation](https://github.com/testing-library/eslint-plugin-testing-library)
- [eslint-plugin-jest-dom Documentation](https://github.com/testing-library/eslint-plugin-jest-dom)
- [ESLint 9 Configuration Guide](https://eslint.org/docs/latest/use/configure/)

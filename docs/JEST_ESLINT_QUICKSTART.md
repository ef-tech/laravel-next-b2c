# Jest/Testing Library ESLint クイックスタートガイド

## 5分でわかるESLintテストルール

このプロジェクトでは、Jestテストコードに対してESLintによる静的解析を実施しています。

### 何が変わったか？

**Before（Phase 0）:**
```typescript
// テストコードにESLintルールなし
fit("focused test", () => {  // 見逃される
  expect(true).toBe(true);
});
```

**After（Phase 1導入後）:**
```typescript
// テスト専用ESLintルールが適用
fit("focused test", () => {  // ❌ jest/no-focused-tests エラー
  expect(true).toBe(true);
});
```

### 対象ファイル

以下のパターンに一致するファイルが自動的にテストルール対象：

- `*.test.ts` / `*.test.tsx`
- `*.spec.ts` / `*.spec.tsx`
- `__tests__/*.ts` / `__tests__/*.tsx`

## 基本的な使い方

### 1. ローカルでのリント実行

```bash
# モノレポ全体（admin-app + user-app）
npm run lint

# 特定ワークスペースのみ
npm run lint --workspace=frontend/admin-app

# 自動修正付き実行
npm run lint:fix --workspace=frontend/admin-app
```

### 2. エディタ統合

VSCodeを使用している場合、`.vscode/settings.json`で自動保存時にリント実行：

```json
{
  "editor.codeActionsOnSave": {
    "source.fixAll.eslint": true
  },
  "eslint.validate": [
    "javascript",
    "javascriptreact",
    "typescript",
    "typescriptreact"
  ]
}
```

### 3. Pre-commitフックによる自動検証

コミット前に自動的にESLintが実行されます（Husky + lint-staged）。

```bash
git add .
git commit -m "test: Add new test"
# → 自動的にESLintが実行され、エラーがあればコミット中断
```

### 4. CI/CDでの自動検証

Pull Request作成時にGitHub Actionsで自動リント検証が実行されます。

## よくあるエラーと修正方法

### 1. `jest/no-focused-tests` - focused test検出

**エラー:**
```typescript
fit("should work", () => {  // ❌ Unexpected focused test
  expect(true).toBe(true);
});
```

**修正:**
```typescript
it("should work", () => {  // ✅ Good
  expect(true).toBe(true);
});
```

### 2. `testing-library/prefer-screen-queries` - screen使用推奨

**エラー（warn）:**
```typescript
const { getByRole } = render(<Button />);  // ⚠️ Use screen.getByRole instead
const button = getByRole("button");
```

**修正:**
```typescript
render(<Button />);
const button = screen.getByRole("button");  // ✅ Good
```

### 3. `jest/expect-expect` - アサーション不足

**エラー:**
```typescript
it("should render button", () => {  // ❌ No assertions
  render(<Button />);
});
```

**修正:**
```typescript
it("should render button", () => {  // ✅ Good
  render(<Button />);
  expect(screen.getByRole("button")).toBeInTheDocument();
});
```

### 4. `jest-dom/prefer-checked` - 専用マッチャー推奨

**エラー:**
```typescript
expect(checkbox.checked).toBe(true);  // ❌ Use .toBeChecked() instead
```

**修正:**
```typescript
expect(checkbox).toBeChecked();  // ✅ Good
```

### 5. `describe is not defined` - グローバル関数未定義

**原因:**
ファイル名が`*.test.{ts,tsx}`パターンに一致していない。

**修正:**
ファイル名を`ComponentName.test.tsx`に変更するか、`__tests__/`ディレクトリに配置。

## 段階的導入戦略（Phase 1 → 2 → 3）

### 現在: Phase 1（初期導入）

- **ルールレベル**: ほとんどがwarn
- **目的**: チームがルールに慣れる期間
- **対応**: 警告を確認し、段階的に修正（必須ではない）

### 次回: Phase 2（低ノイズルール昇格）

- **ルールレベル**: 3つのルールをerrorへ昇格
- **開始条件**: Phase 1で1週間以上警告ゼロ維持
- **対象ルール**:
  - `testing-library/no-node-access`
  - `testing-library/no-container`
  - `testing-library/no-debugging-utils`

### 将来: Phase 3（全ルール昇格）

- **ルールレベル**: 全ルールerror
- **開始条件**: Phase 2で1週間以上警告ゼロ維持

## コマンドリファレンス

```bash
# リント実行（全ワークスペース）
npm run lint

# リント実行（特定ワークスペース）
npm run lint --workspace=frontend/admin-app
npm run lint --workspace=frontend/user-app

# 自動修正付きリント実行
npm run lint:fix --workspace=frontend/admin-app

# テスト実行（ESLint検証なし）
npm test

# テスト + カバレッジ
npm run test:coverage

# 型チェック
npm run type-check

# 全品質チェック（lint + test + type-check）
npm run lint && npm test && npm run type-check
```

## パフォーマンスチューニング

### ESLintキャッシュ有効化

個別プロジェクトでのリント実行時にキャッシュを有効化：

```bash
# package.json
{
  "scripts": {
    "lint": "eslint . --cache"
  }
}
```

### 並列実行

モノレポ全体で並列リント実行（デフォルトで有効）：

```bash
npm run lint --workspaces  # 自動的にadmin-app/user-app並列実行
```

## トラブルシューティング

### エラーが多すぎる場合

Phase 1では警告レベルのため、徐々に修正すればOKです。

```bash
# 警告数を確認
npm run lint 2>&1 | grep -E "warning|error" | wc -l

# 特定ルールの警告数を確認
npm run lint 2>&1 | grep "testing-library/prefer-screen-queries" | wc -l
```

### 特定ファイルのみリント実行

```bash
npx eslint frontend/admin-app/src/components/Button/Button.test.tsx
```

### 適用されているルール確認

```bash
npx eslint --print-config frontend/admin-app/src/components/Button/Button.test.tsx | grep -E "jest/|testing-library/"
```

### CIでのみエラーになる場合

ローカルとCIで`node_modules`の状態が異なる可能性：

```bash
# 依存関係を完全に再インストール
rm -rf node_modules package-lock.json
npm install
```

## よくある質問（FAQ）

### Q1: 既存のテストコードは修正必須？

**A:** Phase 1では警告レベルのため必須ではありません。Phase 2/3への移行前に段階的に修正してください。

### Q2: `screen.debug()`を使いたい

**A:** Phase 1では`testing-library/no-debugging-utils`がwarnレベルのため使用可能です。ただし、コミット前には削除することを推奨します。

### Q3: モックファイル（`__mocks__`）もリント対象？

**A:** 現在は対象外です。必要に応じて`frontend/.eslint.base.mjs`の`files`パターンに`**/__mocks__/**/*.{ts,tsx,js,jsx}`を追加してください。

### Q4: E2Eテスト（Playwright）も対象？

**A:** E2Eテストは別設定が推奨されます。詳細は [JEST_ESLINT_CONFIG_EXAMPLES.md](./JEST_ESLINT_CONFIG_EXAMPLES.md) の「E2Eテスト用のカスタマイズ」を参照。

### Q5: ルールを一時的に無効化したい

**A:** 例外的なケースでは、コメントディレクティブで無効化可能です：

```typescript
// eslint-disable-next-line testing-library/prefer-screen-queries
const { container } = render(<Button />);
```

ただし、安易な使用は避け、レビュー時に理由を説明してください。

## 関連ドキュメント

- [JEST_ESLINT_INTEGRATION_GUIDE.md](./JEST_ESLINT_INTEGRATION_GUIDE.md) - 詳細ガイド
- [JEST_ESLINT_CONFIG_EXAMPLES.md](./JEST_ESLINT_CONFIG_EXAMPLES.md) - カスタマイズ例
- [JEST_ESLINT_TROUBLESHOOTING.md](./JEST_ESLINT_TROUBLESHOOTING.md) - トラブルシューティング

## サポート

問題が発生した場合は、以下を確認してください：

1. [JEST_ESLINT_TROUBLESHOOTING.md](./JEST_ESLINT_TROUBLESHOOTING.md) のトラブルシューティングガイド
2. GitHub Issue #79（この機能の実装Issue）
3. チーム内Slackチャンネル（#dev-frontend）

---

**Phase 1導入おめでとうございます！段階的にテストコード品質を向上させていきましょう。**

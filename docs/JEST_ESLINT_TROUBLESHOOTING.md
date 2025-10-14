# Jest/Testing Library ESLint トラブルシューティング & ロールバック手順

## 目次

1. [よくある問題と解決方法](#よくある問題と解決方法)
2. [ロールバック手順](#ロールバック手順)
3. [詳細診断手順](#詳細診断手順)
4. [パフォーマンス問題](#パフォーマンス問題)
5. [CI/CD固有の問題](#cicd固有の問題)

## よくある問題と解決方法

### 1. `describe is not defined` / `it is not defined` エラー

**症状:**
```
error  'describe' is not defined  no-undef
error  'it' is not defined        no-undef
error  'expect' is not defined    no-undef
```

**原因:**
- テストファイルが認識されていない（ファイルパターン不一致）
- `globals.jest`設定が適用されていない

**診断:**
```bash
# 現在のファイルに適用されている設定を確認
npx eslint --print-config path/to/YourTest.test.tsx | grep -A10 "globals"
```

**解決方法:**

1. **ファイル名がパターンに一致するか確認:**
   ```bash
   # 正しいパターン
   ✅ Component.test.tsx
   ✅ Component.spec.tsx
   ✅ __tests__/Component.tsx

   # 間違ったパターン
   ❌ Component.tests.tsx  # "test" が複数形
   ❌ Component-test.tsx   # ハイフン区切り
   ❌ Component.test.js    # jsファイルだが、TypeScriptプロジェクトでは推奨されない
   ```

2. **設定ファイル確認:**
   `frontend/.eslint.base.mjs`のfilesパターンを確認：
   ```javascript
   files: [
     "**/*.{test,spec}.{ts,tsx,js,jsx}",
     "**/__tests__/**/*.{ts,tsx,js,jsx}"
   ]
   ```

3. **キャッシュクリア:**
   ```bash
   rm -rf frontend/admin-app/.eslintcache
   rm -rf frontend/user-app/.eslintcache
   npm run lint
   ```

### 2. `testing-library/prefer-screen-queries` 大量警告

**症状:**
```
warning  Use `screen` to query document  testing-library/prefer-screen-queries
```

**原因:**
既存コードがrenderResultから直接クエリを取得している。

**解決方法（段階的）:**

**Phase 1（現在）: warnレベルのため修正は任意**
```typescript
// 現在のコード（警告あり）
const { getByRole } = render(<Button />);
const button = getByRole("button");
```

**Phase 2以降に向けた推奨修正:**
```typescript
// 推奨コード
render(<Button />);
const button = screen.getByRole("button");
```

**一時的に無効化する場合:**
```typescript
// eslint-disable-next-line testing-library/prefer-screen-queries
const { getByRole } = render(<Button />);
```

### 3. `jest/expect-expect` - アサーションなしテスト

**症状:**
```
error  Test has no assertions  jest/expect-expect
```

**原因:**
テスト関数内に`expect()`呼び出しがない。

**解決方法:**

**問題コード:**
```typescript
it("should render button", () => {
  render(<Button />);  // アサーションなし
});
```

**修正コード:**
```typescript
it("should render button", () => {
  render(<Button />);
  expect(screen.getByRole("button")).toBeInTheDocument();
});
```

**カスタムアサーション関数を使用している場合:**
```javascript
// frontend/.eslint.base.mjs
{
  rules: {
    "jest/expect-expect": [
      "error",
      {
        assertFunctionNames: [
          "expect",
          "expectToBeVisible",  // カスタムアサーション
        ],
      },
    ],
  },
}
```

### 4. `jest/no-focused-tests` - focused test検出

**症状:**
```
error  Unexpected focused test  jest/no-focused-tests
```

**原因:**
`fit()`, `fdescribe()`, `test.only()`, `describe.only()`を使用している。

**解決方法:**

**問題コード:**
```typescript
fit("should work", () => {  // ❌
  expect(true).toBe(true);
});

describe.only("MyComponent", () => {  // ❌
  it("should work", () => {
    expect(true).toBe(true);
  });
});
```

**修正コード:**
```typescript
it("should work", () => {  // ✅
  expect(true).toBe(true);
});

describe("MyComponent", () => {  // ✅
  it("should work", () => {
    expect(true).toBe(true);
  });
});
```

### 5. `testing-library/no-debugging-utils` - デバッグ関数残存

**症状:**
```
warning  Remove `screen.debug` before pushing  testing-library/no-debugging-utils
```

**原因:**
`screen.debug()`, `screen.logTestingPlaygroundURL()`がコード内に残っている。

**解決方法:**

**Phase 1（現在）: warnレベルのため一時的に許容**
```typescript
it("should render", () => {
  render(<Button />);
  screen.debug();  // ⚠️ 警告あり
  expect(screen.getByRole("button")).toBeInTheDocument();
});
```

**コミット前に削除推奨:**
```typescript
it("should render", () => {
  render(<Button />);
  // screen.debug();  // デバッグ完了後にコメントアウトまたは削除
  expect(screen.getByRole("button")).toBeInTheDocument();
});
```

### 6. 複数ファイルで警告が大量に出る

**症状:**
```bash
npm run lint
# 100+ warnings表示
```

**診断:**
```bash
# 警告数をカウント
npm run lint 2>&1 | grep -c "warning"

# ルール別の警告数をカウント
npm run lint 2>&1 | grep "testing-library/prefer-screen-queries" | wc -l
```

**解決方法:**

**Phase 1（現在）:**
- 警告はwarnレベルのため、段階的に修正すればOK
- 新規テストコードは推奨パターンに従う

**一括修正（慎重に実施）:**
```bash
# 自動修正可能なルールのみ修正
npm run lint:fix --workspace=frontend/admin-app

# 修正内容を確認
git diff

# 問題なければコミット
git add .
git commit -m "Fix: ESLintテストルール警告修正"
```

## ロールバック手順

### Phase 1完全ロールバック（緊急時）

**手順1: コミット履歴確認**
```bash
git log --oneline -10
# ecf7d55 Feat: ✅ フロントエンドテストコードESLint導入 を見つける
```

**手順2: git revert実行**
```bash
git revert ecf7d55
# または最新のコミットをrevert
git revert HEAD
```

**手順3: 動作確認**
```bash
npm run lint  # テストルールが適用されないことを確認
npm test      # テストが正常動作することを確認
```

**手順4: pushしてCI/CD確認**
```bash
git push origin feature/79/add-test-eslint-rules
```

### 部分的ロールバック

#### 1. テストルールのみ無効化（依存パッケージは維持）

`frontend/.eslint.base.mjs`の該当セクションをコメントアウト：

```javascript
// テストファイル専用オーバーライド
/*
{
  files: ["**/*.{test,spec}.{ts,tsx,js,jsx}", "**/__tests__/**/*.{ts,tsx,js,jsx}"],
  plugins: {
    jest: jestPlugin,
    "testing-library": testingLibrary,
    "jest-dom": jestDom,
  },
  // ... 残りの設定
},
*/
```

#### 2. 特定ルールのみ無効化

`frontend/.eslint.base.mjs`のrulesセクションで無効化：

```javascript
{
  rules: {
    // 問題のあるルールを一時的に無効化
    "testing-library/prefer-screen-queries": "off",
    "testing-library/no-node-access": "off",
  },
}
```

#### 3. 依存パッケージのアンインストール

```bash
npm uninstall eslint-plugin-jest eslint-plugin-testing-library eslint-plugin-jest-dom globals
```

その後、`frontend/.eslint.base.mjs`から該当import文を削除。

## 詳細診断手順

### 1. ESLint設定の検証

```bash
# 特定ファイルに適用されている設定を確認
npx eslint --print-config frontend/admin-app/src/components/Button/Button.test.tsx

# テスト関連ルールのみ抽出
npx eslint --print-config frontend/admin-app/src/components/Button/Button.test.tsx | grep -E "jest/|testing-library/|jest-dom/"
```

### 2. ESLintキャッシュの問題診断

```bash
# キャッシュ削除
rm -rf frontend/admin-app/.eslintcache
rm -rf frontend/user-app/.eslintcache
rm -rf .eslintcache

# キャッシュなしでリント実行
npm run lint
```

### 3. 依存関係の整合性確認

```bash
# インストール済みプラグインバージョン確認
npm ls eslint-plugin-jest
npm ls eslint-plugin-testing-library
npm ls eslint-plugin-jest-dom
npm ls globals

# 期待されるバージョン:
# eslint-plugin-jest@^28.14.0
# eslint-plugin-testing-library@^6.5.0
# eslint-plugin-jest-dom@^5.5.0
# globals@^15.15.0
```

### 4. Node.jsバージョン確認

```bash
node --version
# Expected: v20.x
```

ESLint 9はNode.js 18.18.0以上が必須です。

## パフォーマンス問題

### 症状: リント実行が遅い（5秒以上）

**診断:**
```bash
# 実行時間測定
time npm run lint --workspace=frontend/admin-app
```

**解決方法:**

1. **ESLintキャッシュ有効化:**
   ```json
   // frontend/admin-app/package.json
   {
     "scripts": {
       "lint": "eslint . --cache"
     }
   }
   ```

2. **ファイルパターン最適化:**
   ```javascript
   // frontend/.eslint.base.mjs
   {
     files: [
       "src/**/*.{test,spec}.{ts,tsx}",  // srcディレクトリのみ
       "src/**/__tests__/**/*.{ts,tsx}",
     ],
   }
   ```

3. **並列実行（モノレポ）:**
   ```bash
   npm run lint --workspaces  # 既にデフォルトで有効
   ```

### 症状: CI/CDで実行時間が長い（5分以上）

**解決方法:**

1. **GitHub Actionsキャッシュ最適化:**
   ```yaml
   # .github/workflows/frontend-test.yml
   - name: Cache ESLint
     uses: actions/cache@v4
     with:
       path: |
         frontend/admin-app/.eslintcache
         frontend/user-app/.eslintcache
       key: ${{ runner.os }}-eslint-${{ hashFiles('**/package-lock.json') }}
   ```

2. **Matrix戦略による並列実行（既に実装済み）:**
   ```yaml
   strategy:
     matrix:
       app: [admin-app, user-app]  # 並列実行
   ```

## CI/CD固有の問題

### 症状: CI/CDでのみエラーが発生（ローカルでは成功）

**原因:**
- `node_modules`の状態が異なる
- 環境変数の違い
- キャッシュの不整合

**解決方法:**

1. **依存関係の完全再インストール（ローカル）:**
   ```bash
   rm -rf node_modules package-lock.json
   rm -rf frontend/admin-app/node_modules
   rm -rf frontend/user-app/node_modules
   npm install
   npm run lint
   ```

2. **CI/CDキャッシュクリア:**
   GitHub Actions → Settings → Actions → Caches → Delete all caches

3. **環境変数の確認:**
   ```bash
   # ローカル
   npm run lint

   # CI環境を模倣
   CI=true npm run lint
   ```

### 症状: lint-stagedでエラーが発生

**診断:**
```bash
# lint-stagedを手動実行
npx lint-staged
```

**解決方法:**

1. **lint-staged設定確認（package.json）:**
   ```json
   "lint-staged": {
     "frontend/admin-app/**/*.{js,jsx,ts,tsx}": [
       "bash -c 'files=\"$@\"; files=$(echo \"$files\" | tr \" \" \"\\n\" | grep -v \"jest.config.js\" | tr \"\\n\" \" \"); [ -n \"$files\" ] && eslint --fix --max-warnings=0 $files || true'",
       "prettier --write"
     ]
   }
   ```

2. **Huskyフック確認:**
   ```bash
   cat .husky/pre-commit
   # npx lint-staged が含まれているか確認
   ```

## 緊急時の一時的回避策

### 全テストルールを一時的に無効化

`frontend/.eslint.base.mjs`の末尾に追加：

```javascript
export default [
  // ... 既存設定

  // 🚨 緊急時のみ: 全テストルールを無効化
  {
    files: ["**/*.{test,spec}.{ts,tsx,js,jsx}", "**/__tests__/**/*.{ts,tsx,js,jsx}"],
    rules: {
      "jest/*": "off",
      "testing-library/*": "off",
      "jest-dom/*": "off",
    },
  },
];
```

**注意:** この設定は一時的な回避策です。根本原因を解決後、必ず削除してください。

## サポート連絡先

1. **ドキュメント確認:**
   - [JEST_ESLINT_INTEGRATION_GUIDE.md](./JEST_ESLINT_INTEGRATION_GUIDE.md)
   - [JEST_ESLINT_CONFIG_EXAMPLES.md](./JEST_ESLINT_CONFIG_EXAMPLES.md)
   - [JEST_ESLINT_QUICKSTART.md](./JEST_ESLINT_QUICKSTART.md)

2. **GitHub Issue:**
   - Issue #79: フロントエンドテストコードのリント設定追加

3. **チーム連絡:**
   - Slackチャンネル: #dev-frontend

## ロールバック判断基準

以下の場合、Phase 1のロールバックを検討：

- ✅ 致命的なバグ発覚（テスト実行不可など）
- ✅ CI/CD全体が5日以上停止
- ✅ 開発速度が50%以上低下（計測可能な場合）
- ❌ 警告が多い（Phase 1はwarnレベルのため許容）
- ❌ 一部のルールが厳しい（個別ルール無効化で対応）

---

**問題解決できない場合は、遠慮なくチームに相談してください。**

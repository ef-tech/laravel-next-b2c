# Testing Troubleshooting Guide

Next.js + Jest テスト実行時によくある問題と解決方法。

## Table of Contents

- [よくあるエラーと対処法](#よくあるエラーと対処法)
- [非同期テストのデバッグ](#非同期テストのデバッグ)
- [モック関連の問題](#モック関連の問題)
- [CI/CD失敗時の対応](#cicd失敗時の対応)

## よくあるエラーと対処法

### 1. モック設定忘れ

#### エラー例

```
Error: Cannot find module 'next/cache' from 'src/app/actions.ts'
```

#### 原因

`jest.mock()`でモックを設定していないため、実際のモジュールをインポートしようとしている。

#### 対処法

テストファイルの先頭でモックを設定：

```typescript
jest.mock("next/cache", () => ({
  revalidatePath: jest.fn(),
}));
```

### 2. 非同期処理の未待機

#### エラー例

```
Warning: An update to Component inside a test was not wrapped in act(...)
```

#### 原因

非同期処理（状態更新、API呼び出し）を待たずにテストが終了している。

#### 対処法

`waitFor`を使用して非同期処理を待機：

```typescript
import { renderHook, waitFor } from "@testing-library/react";

it("fetches data", async () => {
  const { result } = renderHook(() => useAuth());

  // 非同期処理を待機
  await waitFor(() => {
    expect(result.current.loading).toBe(false);
  });

  expect(result.current.user).toBeDefined();
});
```

### 3. テストファイルが検出されない

#### エラー例

```
No tests found, exiting with code 1
```

#### 原因

- テストファイル命名規則に従っていない
- `testMatch`パターンに一致しない
- テストファイルが存在しない（意図的に削除された、または初期状態）

#### 重要な動作

このプロジェクトでは**テスト実行の確実性を保証するため**、テストファイルが0件の場合は**意図的に失敗（Exit Code 1）**します。

- **理由**: CI/CDでテストファイルが誤って削除された場合に即座に検知
- **Jest設定**: `--passWithNoTests` オプションを使用していません
- **期待動作**: テストファイルが必ず1件以上存在することを強制

#### 対処法

**1. テストファイル命名規則の確認**

ファイル名を `.test.ts` または `.test.tsx` に変更：

```bash
# Bad
src/components/Button/__tests__/Button.spec.tsx

# Good
src/components/Button/Button.test.tsx
```

**2. testMatch設定の確認**

`jest.base.js` の `testMatch` パターンを確認：

```javascript
testMatch: [
  '<rootDir>/src/**/*.(test|spec).(ts|tsx)',
  '<rootDir>/src/**/*.(test|spec).(js|jsx)',
],
```

**3. テストファイルの存在確認**

```bash
# User App
find frontend/user-app/src -name "*.test.*" -o -name "*.spec.*"

# Admin App
find frontend/admin-app/src -name "*.test.*" -o -name "*.spec.*"

# Jestが検出するテストファイル一覧を確認（推奨）
npx jest --listTests
```

**4. 初期開発時の注意**

新規機能開発時は、最初に最小限のテストファイルを作成してください：

```typescript
// src/components/NewFeature/NewFeature.test.tsx
describe("NewFeature", () => {
  it("renders without crashing", () => {
    expect(true).toBe(true); // 最小限のテスト
  });
});
```

### 4. 型エラー

#### エラー例

```
Property 'toBeInTheDocument' does not exist on type 'Matchers<HTMLElement>'
```

#### 原因

`@testing-library/jest-dom`の型定義が読み込まれていない。

#### 対処法

`jest.setup.ts`で型定義が正しくインポートされているか確認：

```typescript
import "@testing-library/jest-dom";
```

また、各アプリの`tsconfig.json`が`tsconfig.base.json`を継承し、適切な型定義が含まれているか確認：

```json
{
  "extends": "../tsconfig.base.json",
  "compilerOptions": {
    "paths": {
      "@/*": ["./src/*"],
      "@shared/*": ["../lib/*"]
    }
  }
}
```

### 5. CSSモジュールのインポートエラー

#### エラー例

```
SyntaxError: Unexpected token '.'
```

#### 原因

CSSファイルをそのままインポートしようとしている。

#### 対処法

`jest.base.js`の`moduleNameMapper`でCSSをモック：

```javascript
moduleNameMapper: {
  '\\.(css|less|scss|sass)$': 'identity-obj-proxy',
}
```

## 非同期テストのデバッグ

### waitFor使用方法

非同期処理を待機する際は`waitFor`を使用：

```typescript
import { render, screen, waitFor } from '@testing-library/react';

it('loads and displays data', async () => {
  render(<UserList />);

  // ローディング表示を確認
  expect(screen.getByText('Loading...')).toBeInTheDocument();

  // データ表示を待機
  await waitFor(() => {
    expect(screen.getByText('John Doe')).toBeInTheDocument();
  });
});
```

### act警告の対処

#### 警告例

```
Warning: An update to Component inside a test was not wrapped in act(...)
```

#### 対処法1: waitForを使用

```typescript
await waitFor(() => {
  expect(result.current.data).toBeDefined();
});
```

#### 対処法2: act関数でラップ

```typescript
import { act } from "@testing-library/react";

await act(async () => {
  fireEvent.click(button);
});
```

### タイムアウトエラー

#### エラー例

```
Exceeded timeout of 5000 ms for a test
```

#### 対処法

タイムアウト時間を延長：

```typescript
it("long running test", async () => {
  // ...
}, 10000); // 10秒に延長
```

または`waitFor`のタイムアウトオプション：

```typescript
await waitFor(
  () => {
    expect(result.current.data).toBeDefined();
  },
  { timeout: 10000 },
);
```

## モック関連の問題

### jest.mock実行順序

#### 問題

`jest.mock()`は必ずファイルの先頭で実行する必要がある。

#### Bad Example

```typescript
import { saveUser } from "./actions";

// ❌ importの後にモックを設定
jest.mock("next/cache", () => ({
  revalidatePath: jest.fn(),
}));
```

#### Good Example

```typescript
// ✅ importの前にモックを設定
jest.mock("next/cache", () => ({
  revalidatePath: jest.fn(),
}));

import { saveUser } from "./actions";
```

### モックのリセット

複数のテストケースでモックを使用する場合、各テスト後にリセット：

```typescript
describe("API Functions", () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  it("test 1", () => {
    global.fetch = jest.fn(() =>
      Promise.resolve({ ok: true, json: () => Promise.resolve([]) }),
    ) as jest.Mock;
    // テスト
  });

  it("test 2", () => {
    global.fetch = jest.fn(() => Promise.resolve({ ok: false, status: 500 })) as jest.Mock;
    // テスト
  });
});
```

### fetchモックのTypeScript型エラー

#### エラー例

```
Type '() => Promise<{ ok: boolean; json: () => Promise<any[]>; }>' is not assignable to type '(input: RequestInfo | URL, init?: RequestInit) => Promise<Response>'.
```

#### 対処法

`as jest.Mock`で型アサーション：

```typescript
global.fetch = jest.fn(() =>
  Promise.resolve({
    ok: true,
    json: () => Promise.resolve([]),
  }),
) as jest.Mock;
```

### useSearchParamsモックの設定

Next.js Navigation APIをモックする際の正しい設定：

```typescript
import { useSearchParams } from "next/navigation";

jest.mock("next/navigation", () => ({
  useSearchParams: jest.fn(),
}));

describe("useAuth Hook", () => {
  beforeEach(() => {
    const mockSearchParams = {
      get: jest.fn((key: string) => (key === "token" ? "abc123" : null)),
    };
    (useSearchParams as jest.Mock).mockReturnValue(mockSearchParams);
  });

  it("test", () => {
    // テスト
  });
});
```

## CI/CD失敗時の対応

### Node.jsバージョン不一致

#### エラー例

```
Error: The engine "node" is incompatible with this module.
```

#### 対処法

`.github/workflows/frontend-test.yml`でNode.jsバージョンを確認：

```yaml
strategy:
  matrix:
    node-version: [18.x, 20.x]
```

ローカルでもCI/CDと同じバージョンを使用：

```bash
nvm use 20
```

### メモリ不足

#### エラー例

```
FATAL ERROR: Ineffective mark-compacts near heap limit Allocation failed - JavaScript heap out of memory
```

#### 対処法

`maxWorkers`を削減：

```json
{
  "scripts": {
    "test": "jest --maxWorkers=2"
  }
}
```

または環境変数でヒープサイズを増やす：

```bash
export NODE_OPTIONS="--max-old-space-size=4096"
npm test
```

### カバレッジ閾値未達

#### エラー例

```
Jest: "global" coverage threshold for branches (80%) not met: 75%
```

#### 対処法

カバレッジレポートを確認：

```bash
npm test:coverage
open coverage/lcov-report/index.html
```

未テストのコードを特定し、テストを追加。

一時的に閾値を下げる場合は`jest.base.js`を編集：

```javascript
coverageThreshold: {
  global: {
    branches: 75, // 一時的に下げる
    functions: 80,
    lines: 80,
    statements: 80,
  },
},
```

### CI環境でのみ発生するエラー

#### 原因

- ローカルとCIで環境変数が異なる
- タイムゾーンの違い
- ファイルシステムの違い

#### 対処法

GitHub Actionsのログを確認：

```yaml
- name: Run tests with verbose output
  run: npm test -- --verbose
```

ローカルで再現するために同じ環境を構築：

```bash
# Docker使用
docker run -it node:20 /bin/bash
```

### codecovアップロード失敗

#### エラー例

```
Error uploading coverage reports to Codecov
```

#### 対処法

1. カバレッジファイルが生成されているか確認：

```yaml
- name: Check coverage file
  run: ls -la coverage/
```

2. GITHUB_TOKENが設定されているか確認：

```yaml
- uses: codecov/codecov-action@v3
  with:
    token: ${{ secrets.GITHUB_TOKEN }}
```

## デバッグテクニック

### console.log使用

テスト内で`console.log`を使用して状態を確認：

```typescript
it("debug test", () => {
  const { result } = renderHook(() => useAuth());
  console.log("Initial state:", result.current);

  // テスト続行
});
```

### screen.debug()

レンダリング結果をコンソールに出力：

```typescript
import { render, screen } from '@testing-library/react';

it('debug rendering', () => {
  render(<Button>Click</Button>);
  screen.debug(); // DOMツリー全体を出力
});
```

### テストを個別実行

特定のテストのみ実行してデバッグ：

```bash
# 特定ファイルのみ
npm test -- src/components/Button/Button.test.tsx

# 特定のdescribeブロックのみ
npm test -- -t "Button Component"
```

### watchモードでのデバッグ

ファイル変更時に自動再実行：

```bash
npm test:watch
```

watchモード中のオプション：

- `p`: ファイル名パターンでフィルター
- `t`: テスト名パターンでフィルター
- `a`: 全テスト実行
- `q`: 終了

## さらに詳しく

- [Jest公式ドキュメント](https://jestjs.io/docs/getting-started)
- [React Testing Library公式ドキュメント](https://testing-library.com/docs/react-testing-library/intro)
- [Next.js Testing公式ガイド](https://nextjs.org/docs/app/building-your-application/testing/jest)

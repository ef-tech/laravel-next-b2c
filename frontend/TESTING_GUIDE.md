# Testing Guide

Next.js 15.5.4 + React 19.1.0 フロントエンドアプリケーションのテスト記述ガイドライン。

## Table of Contents

- [テストファイル命名規則](#テストファイル命名規則)
- [Arrange-Act-Assertパターン](#arrange-act-assertパターン)
- [モック使用ガイドライン](#モック使用ガイドライン)
- [テストサンプル参照](#テストサンプル参照)
- [test-utils使用方法](#test-utils使用方法)
- [スナップショットテスト運用ルール](#スナップショットテスト運用ルール)

## テストファイル命名規則

### ファイル命名

テストファイルは以下の命名規則に従ってください：

- **拡張子**: `.test.ts` または `.test.tsx`
- **配置**: テスト対象ファイルと同じディレクトリ

```
src/
├── components/
│   └── Button/
│       ├── Button.tsx          # 実装ファイル
│       └── Button.test.tsx     # テストファイル
├── hooks/
│   ├── useAuth.ts
│   └── useAuth.test.ts
└── lib/
    ├── api.ts
    └── api.test.ts
```

### テストケース記述

```typescript
describe("コンポーネント/関数名", () => {
  it("期待される動作を説明する文", () => {
    // テストコード
  });
});
```

**Good Examples:**

```typescript
describe("Button Component", () => {
  it("renders with correct text", () => {
    /* ... */
  });
  it("handles click events", () => {
    /* ... */
  });
});
```

**Bad Examples:**

```typescript
describe("Button", () => {
  it("test1", () => {
    /* ... */
  }); // 説明が不十分
});
```

## Arrange-Act-Assertパターン

テストは**Arrange-Act-Assert**パターンに従って記述してください。

### パターン説明

1. **Arrange（準備）**: テストに必要なデータやモックを準備
2. **Act（実行）**: テスト対象の関数やコンポーネントを実行
3. **Assert（検証）**: 期待される結果を検証

### 実装例

```typescript
it("saves user and revalidates path", async () => {
  // Arrange: テストデータを準備
  const userData = { name: "John Doe", email: "john@example.com" };

  // Act: 関数を実行
  const result = await saveUser(userData);

  // Assert: 期待される結果を検証
  expect(result.success).toBe(true);
  expect(revalidatePath).toHaveBeenCalledWith("/users");
});
```

### ネストしたdescribeを使った構造化

```typescript
describe("fetchUsers", () => {
  describe("when API request succeeds", () => {
    it("returns user list", async () => {
      // Arrange
      global.fetch = jest.fn(() =>
        Promise.resolve({
          ok: true,
          json: () => Promise.resolve([{ id: 1, name: "John" }]),
        }),
      ) as jest.Mock;

      // Act
      const users = await fetchUsers();

      // Assert
      expect(users).toHaveLength(1);
    });
  });

  describe("when API request fails", () => {
    it("throws error", async () => {
      // Arrange
      global.fetch = jest.fn(() => Promise.resolve({ ok: false, status: 500 })) as jest.Mock;

      // Act & Assert
      await expect(fetchUsers()).rejects.toThrow("Failed to fetch users");
    });
  });
});
```

## モック使用ガイドライン

### jest.mockの使用シーン

Next.js固有のモジュール（`next/cache`、`next/navigation`）をモックする際に使用。

```typescript
import { revalidatePath } from "next/cache";
import { useSearchParams } from "next/navigation";

jest.mock("next/cache", () => ({
  revalidatePath: jest.fn(),
}));

jest.mock("next/navigation", () => ({
  useSearchParams: jest.fn(),
}));
```

### global.fetchモックの使用シーン

API呼び出しをテストする際に使用（MSW ESM互換性問題のため代替）。

```typescript
global.fetch = jest.fn(() =>
  Promise.resolve({
    ok: true,
    json: () => Promise.resolve([{ id: 1, name: "John Doe" }]),
  }),
) as jest.Mock;
```

### beforeEach/afterEachでのモッククリア

複数のテストケースでモックを使用する場合、各テスト後にクリアしてください。

```typescript
describe("API Functions", () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  it("test case 1", () => {
    /* ... */
  });
  it("test case 2", () => {
    /* ... */
  });
});
```

## テストサンプル参照

実装パターン別のテストサンプルを用意しています。新規テスト作成時の参考にしてください。

### 1. Client Componentテスト

**参照**: `frontend/admin-app/src/components/Button/Button.test.tsx`

**主な内容**:

- `render`、`screen`、`fireEvent`の使用方法
- イベントハンドラーのテスト
- propsによるレンダリング変化の検証

```typescript
import { render, screen, fireEvent } from '../../../../../test-utils/render';
import { Button } from './Button';

it('handles click events', () => {
  const handleClick = jest.fn();
  render(<Button onClick={handleClick}>Click me</Button>);

  const button = screen.getByText('Click me');
  fireEvent.click(button);

  expect(handleClick).toHaveBeenCalledTimes(1);
});
```

### 2. Server Actionsテスト

**参照**: `frontend/admin-app/src/app/actions.test.ts`

**主な内容**:

- `revalidatePath`のモック方法
- 非同期関数のテスト
- バリデーションエラーハンドリング

```typescript
import { saveUser } from "./actions";
import { revalidatePath } from "next/cache";

jest.mock("next/cache", () => ({
  revalidatePath: jest.fn(),
}));

it("saves user and revalidates path", async () => {
  const userData = { name: "John Doe", email: "john@example.com" };
  const result = await saveUser(userData);

  expect(result.success).toBe(true);
  expect(revalidatePath).toHaveBeenCalledWith("/users");
});
```

### 3. カスタムフックテスト

**参照**: `frontend/admin-app/src/hooks/useAuth.test.ts`

**主な内容**:

- `renderHook`の使用方法
- `waitFor`による非同期処理の待機
- Next.js Navigation APIのモック

```typescript
import { renderHook, waitFor } from "@testing-library/react";
import { useAuth } from "./useAuth";

it("fetches user data on mount", async () => {
  const mockSearchParams = {
    get: jest.fn((key: string) => (key === "token" ? "abc123" : null)),
  };
  (useSearchParams as jest.Mock).mockReturnValue(mockSearchParams);

  global.fetch = jest.fn(() =>
    Promise.resolve({
      ok: true,
      json: () => Promise.resolve({ id: 1, name: "John Doe" }),
    }),
  ) as jest.Mock;

  const { result } = renderHook(() => useAuth());

  await waitFor(() => {
    expect(result.current.loading).toBe(false);
  });

  expect(result.current.user).toEqual({ id: 1, name: "John Doe" });
});
```

### 4. API Fetchテスト

**参照**: `frontend/admin-app/src/lib/api.test.ts`

**主な内容**:

- `global.fetch`のモック方法
- 成功・失敗ケースのテスト
- エラーハンドリング

```typescript
import { fetchUsers } from "./api";

it("fetches users successfully", async () => {
  global.fetch = jest.fn(() =>
    Promise.resolve({
      ok: true,
      json: () => Promise.resolve([{ id: 1, name: "John Doe", email: "john@example.com" }]),
    }),
  ) as jest.Mock;

  const users = await fetchUsers();

  expect(users).toHaveLength(1);
  expect(users[0].name).toBe("John Doe");
});
```

## test-utils使用方法

共通テストユーティリティは `/test-utils/` ディレクトリに配置されています。

### カスタムレンダリング関数

**場所**: `/test-utils/render.tsx`

React Testing Libraryの`render`関数をラップし、将来的にProviderを追加する拡張ポイントを提供。

```typescript
import { render, screen } from '../../../../../test-utils/render';
import { MyComponent } from './MyComponent';

it('renders correctly', () => {
  render(<MyComponent />);
  expect(screen.getByText('Hello')).toBeInTheDocument();
});
```

### 環境変数バリデーション

**方式**: 起動時Zodバリデーション

環境変数の検証は、テストコードではなく**起動時バリデーション**（`check-env.ts` + `env.ts` Zodスキーマ）によって保証されます。

**品質保証層**:

1. **起動時バリデーション**: `npm run dev` / `npm run build` 実行時に自動検証
2. **CI/CD自動検証**: `env-validation.yml` によるPull Request時の自動チェック
3. **手動検証**: `npm run env:check` による差分検出

```bash
# 環境変数の手動検証
npm run env:check

# 不正な環境変数がある場合のエラー例
# 環境変数が正しく設定されていません。.env.local を確認してください。
# 詳細: NEXT_PUBLIC_API_URL: Invalid url
```

### Next.js Routerモック拡張

**場所**: `/test-utils/router.ts`

next-router-mockの拡張ユーティリティ。

```typescript
import { setupRouter } from "../../../../../test-utils/router";

it("handles query parameters", () => {
  setupRouter({ pathname: "/users", query: { id: "123" } });
  // テストコード
});
```

## スナップショットテスト運用ルール

スナップショットテストは**慎重に使用**してください。

### 使用推奨ケース

- シンプルな静的コンポーネント（アイコン、ロゴ等）
- レンダリング結果が頻繁に変わらないコンポーネント

### 使用非推奨ケース

- 動的なコンポーネント（データ依存、状態管理）
- 頻繁に変更されるコンポーネント

### スナップショットテストの例

```typescript
import { render } from '@testing-library/react';
import { Icon } from './Icon';

it('matches snapshot', () => {
  const { container } = render(<Icon name="home" />);
  expect(container.firstChild).toMatchSnapshot();
});
```

### スナップショット更新時の注意

スナップショットが変更された場合、必ず差分を確認してから更新してください。

```bash
# スナップショット更新
npm test -- -u

# 更新前に差分確認
git diff
```

## ベストプラクティス

### 1. テストは独立させる

各テストは他のテストに依存せず、単独で実行可能にしてください。

**Good:**

```typescript
describe('Button', () => {
  it('test 1', () => {
    render(<Button>Click</Button>);
    // 検証
  });

  it('test 2', () => {
    render(<Button variant="secondary">Click</Button>);
    // 検証
  });
});
```

**Bad:**

```typescript
describe('Button', () => {
  let button: HTMLElement;

  it('test 1', () => {
    button = render(<Button>Click</Button>).getByText('Click');
  });

  it('test 2', () => {
    fireEvent.click(button); // test 1に依存
  });
});
```

### 2. ユーザー視点でテスト

実装詳細ではなく、ユーザーから見える動作をテストしてください。

**Good:**

```typescript
it('displays error message when form is invalid', () => {
  render(<LoginForm />);
  fireEvent.click(screen.getByRole('button', { name: 'Login' }));
  expect(screen.getByText('Email is required')).toBeInTheDocument();
});
```

**Bad:**

```typescript
it('sets error state to true', () => {
  const { rerender } = render(<LoginForm />);
  // 内部stateを直接検証するのは避ける
});
```

### 3. テストカバレッジ80%以上を維持

プロジェクトでは80%のカバレッジ閾値を設定しています。

```bash
# カバレッジレポート生成
npm test:coverage

# 結果確認
open coverage/lcov-report/index.html
```

## さらに詳しく

問題が発生した場合は [TESTING_TROUBLESHOOTING.md](./TESTING_TROUBLESHOOTING.md) を参照してください。

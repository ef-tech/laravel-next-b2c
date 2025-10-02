# E2E Test Environment

Playwright 1.47.2 + Laravel Sanctum 認証統合によるE2Eテスト環境

## セットアップ

### 1. 依存関係のインストール

```bash
cd e2e
npm install
npx playwright install chromium
```

### 2. 環境変数の設定

`.env.example`をコピーして`.env`を作成し、環境に応じた値を設定:

```bash
cp .env.example .env
```

`.env`の設定例:

```env
E2E_ADMIN_URL=http://localhost:3001
E2E_USER_URL=http://localhost:3000
E2E_API_URL=http://localhost:8000

E2E_ADMIN_EMAIL=admin@example.com
E2E_ADMIN_PASSWORD=password

E2E_USER_EMAIL=user@example.com
E2E_USER_PASSWORD=password
```

### 3. アプリケーションの起動

E2Eテスト実行前に、以下のサービスが起動している必要があります:

- **Laravel API** (http://localhost:8000)
- **Admin App** (http://localhost:3001)
- **User App** (http://localhost:3000)

```bash
# Laravel API起動 (backend/laravel-api/)
./vendor/bin/sail up -d
# または
php artisan serve

# Frontend起動 (frontend/admin-app/, frontend/user-app/)
npm run dev
```

## テスト実行

### 基本コマンド

```bash
# 全テスト実行
npm test

# UI モードで実行（デバッグ推奨）
npm run test:ui

# デバッグモード
npm run test:debug

# Admin テストのみ実行
npm run test:admin

# User テストのみ実行
npm run test:user

# レポート表示
npm run report
```

### CI環境での実行

```bash
# Shard実行（並列化）
SHARD_INDEX=1 SHARD_TOTAL=4 npm run test:ci
```

## テスト構成

### ディレクトリ構造

```
e2e/
├── fixtures/
│   └── global-setup.ts      # グローバルセットアップ（認証状態生成）
├── helpers/
│   └── sanctum.ts            # Sanctum認証ヘルパー
├── projects/
│   ├── admin/
│   │   ├── pages/            # Page Object Model
│   │   │   ├── LoginPage.ts
│   │   │   └── ProductsPage.ts
│   │   └── tests/            # テストケース
│   │       ├── login.spec.ts
│   │       └── products-crud.spec.ts
│   └── user/
│       └── tests/
│           └── api-integration.spec.ts
├── storage/                  # 認証状態ファイル（自動生成）
│   ├── admin.json
│   └── user.json
├── playwright.config.ts      # Playwright設定
├── package.json
└── tsconfig.json
```

### テストサンプル

#### Admin Login Test (`projects/admin/tests/login.spec.ts`)
- ログインフォーム表示確認
- 正常ログイン
- エラーハンドリング
- 認証済みリダイレクト

#### Products CRUD Test (`projects/admin/tests/products-crud.spec.ts`)
- 商品一覧表示
- 商品新規作成
- 商品編集
- 商品削除
- 完全CRUDサイクル

#### API Integration Test (`projects/user/tests/api-integration.spec.ts`)
- 商品一覧API取得・表示
- お問い合わせフォーム送信
- 商品詳細取得
- APIエラーハンドリング
- ローディング状態確認
- 商品検索機能

## 認証の仕組み

### Global Setup

テスト実行前に`fixtures/global-setup.ts`が自動実行され:

1. Laravel Sanctum経由でAdmin/Userの認証を実行
2. 認証状態を`storage/admin.json`, `storage/user.json`に保存
3. 各テストでこの認証状態を読み込み、ログイン済みでテスト開始

### Sanctum認証フロー

`helpers/sanctum.ts`が以下を実行:

1. `/sanctum/csrf-cookie`でCSRFトークン取得
2. XSRF-TOKENクッキーをデコード
3. `/login`にPOSTリクエスト（CSRF token付き）
4. `/api/user`で認証状態確認
5. 認証済みstorageStateを返却

## Codegen（テスト自動生成）

Playwrightのコード生成機能を使用してテストを自動作成:

```bash
# Admin App用
npm run codegen:admin

# User App用
npm run codegen:user
```

ブラウザが起動し、操作を記録してテストコードを生成します。

## トラブルシューティング

### テストが失敗する場合

1. **アプリケーションが起動しているか確認**
   ```bash
   curl http://localhost:8000/up
   curl http://localhost:3000
   curl http://localhost:3001
   ```

2. **環境変数が正しく設定されているか確認**
   ```bash
   cat e2e/.env
   ```

3. **認証情報が正しいか確認**
   - `.env`の`E2E_ADMIN_EMAIL`/`E2E_ADMIN_PASSWORD`
   - `.env`の`E2E_USER_EMAIL`/`E2E_USER_PASSWORD`

4. **認証状態ファイルを再生成**
   ```bash
   rm -rf e2e/storage/*.json
   npm test
   ```

5. **デバッグモードで実行**
   ```bash
   npm run test:debug
   ```

### ブラウザのインストールエラー

```bash
npx playwright install --with-deps
```

### タイムアウトエラー

`playwright.config.ts`でtimeoutを調整:

```typescript
export default defineConfig({
  timeout: 120 * 1000, // 120秒に延長
  // ...
});
```

## CI/CD統合

### GitHub Actions無料枠対策

初期構築時は`.github/workflows/e2e-tests.yml.disabled`として作成。

運用時は以下の手順で有効化:

```bash
# ワークフローを有効化
mv .github/workflows/e2e-tests.yml.disabled .github/workflows/e2e-tests.yml

# コミット・プッシュ
git add .github/workflows/e2e-tests.yml
git commit -m "Enable E2E tests workflow"
git push
```

### ワークフロートリガー

- **手動実行** (`workflow_dispatch`): 必要時のみ実行
- **自動実行** (`push`): E2E関連ファイル変更時のみ実行
  - `frontend/**`
  - `backend/laravel-api/app/**`
  - `backend/laravel-api/routes/**`
  - `e2e/**`
  - `.github/workflows/e2e-tests.yml`

### Shard実行（並列化）

GitHub Actionsでは4並列実行:

```yaml
strategy:
  matrix:
    shard: [1, 2, 3, 4]
```

## 参考資料

- [Playwright公式ドキュメント](https://playwright.dev/)
- [Laravel Sanctum公式ドキュメント](https://laravel.com/docs/sanctum)
- [Page Object Model パターン](https://playwright.dev/docs/pom)

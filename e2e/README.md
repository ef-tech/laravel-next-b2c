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
E2E_ADMIN_URL=http://localhost:13002
E2E_USER_URL=http://localhost:13001
E2E_API_URL=http://localhost:13000

E2E_ADMIN_EMAIL=admin@example.com
E2E_ADMIN_PASSWORD=password

E2E_USER_EMAIL=user@example.com
E2E_USER_PASSWORD=password
```

**ポート設定について:**
- **ローカル開発**: Laravel Sailを使用するため、APIはポート `13000` を使用
- **CI環境**: `php artisan serve` を使用する場合、ポート `8000` を使用可能
- 環境変数 `E2E_API_URL` で柔軟に切り替え可能
```

### 3. アプリケーションの起動

E2Eテスト実行前に、以下のサービスが起動している必要があります:

- **Laravel API** (http://localhost:13000)
- **Admin App** (http://localhost:13002)
- **User App** (http://localhost:13001)

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

#### ✅ 実装済み・実行可能

**Admin App Home Page** (`projects/admin/tests/home.spec.ts`)
- ホームページ読み込み確認
- Next.jsデフォルトコンテンツ表示確認
- 外部リンク動作確認
- フッターナビゲーション確認
- レスポンシブレイアウト確認

**User App Home Page** (`projects/user/tests/home.spec.ts`)
- ホームページ読み込み確認
- Next.jsデフォルトコンテンツ表示確認
- 外部リンク動作確認
- フッターナビゲーション確認
- レスポンシブレイアウト確認

#### ⏭️ 未実装（実装待ち）

**Admin Login Test** (`projects/admin/tests/login.spec.ts`) - スキップ中
- ログインフォーム表示確認
- 正常ログイン
- エラーハンドリング
- 認証済みリダイレクト
- **TODO**: `/login`ページ実装後に有効化
- **要件**: 以下の `data-testid` 属性をフロントエンドに実装
  - `login-form` - ログインフォーム
  - `email` - メールアドレス入力フィールド
  - `password` - パスワード入力フィールド
  - `submit` - 送信ボタン
  - `error-message` - エラーメッセージ表示エリア

**Products CRUD Test** (`projects/admin/tests/products-crud.spec.ts`) - スキップ中
- 商品一覧表示
- 商品新規作成
- 商品編集
- 商品削除
- 完全CRUDサイクル
- **TODO**: `/products`ページ実装後に有効化
- **要件**: 以下の `data-testid` 属性をフロントエンドに実装
  - `products-list` - 商品一覧コンテナ
  - `product-card` - 商品カード
  - `product-name` - 商品名
  - `product-price` - 商品価格
  - `product-description` - 商品説明
  - `create-product` - 新規作成ボタン
  - `edit-product` - 編集ボタン
  - `delete-product` - 削除ボタン

**API Integration Test** (`projects/user/tests/api-integration.spec.ts`) - スキップ中
- 商品一覧API取得・表示
- お問い合わせフォーム送信
- 商品詳細取得
- APIエラーハンドリング
- ローディング状態確認
- 商品検索機能
- **TODO**: APIエンドポイント実装後に有効化
- **要件**: 以下の `data-testid` 属性をフロントエンドに実装
  - `products-list` - 商品一覧コンテナ
  - `product-card` - 商品カード
  - `empty-state` - 空状態表示
  - `contact-name` - お問い合わせ名前フィールド
  - `contact-email` - お問い合わせメールフィールド
  - `contact-message` - お問い合わせメッセージフィールド
  - `submit-contact` - お問い合わせ送信ボタン
  - `success-message` - 成功メッセージ
  - `error-message` - エラーメッセージ
  - `loading` - ローディングインディケーター
  - `search-input` - 検索入力フィールド
  - `search-button` - 検索ボタン

## 認証の仕組み

### ⚠️ 現在の状態（認証無効化）

**重要**: 現在、Laravel APIの認証エンドポイントが未実装のため、認証機能は一時的に無効化されています。

- `playwright.config.ts` の `globalSetup` と `storageState` がコメントアウト状態
- 認証なしでアクセス可能なページ（ホームページなど）のテストのみ実行可能

#### 有効化手順（Laravel認証API実装後）

1. Laravel側で以下のエンドポイントを実装:
   - `/sanctum/csrf-cookie` - CSRFトークン取得
   - `/api/login` - ログインAPI
   - `/api/user` - 認証確認API
   - `/api/logout` - ログアウトAPI

2. `e2e/playwright.config.ts` の認証設定を有効化:
   ```typescript
   // コメントアウトを解除
   globalSetup: require.resolve('./fixtures/global-setup'),

   // 各プロジェクトのstorageStateも有効化
   storageState: 'storage/admin.json',  // admin-chromium
   storageState: 'storage/user.json',   // user-chromium
   ```

3. スキップ中のテストを有効化:
   - `login.spec.ts` の `.skip` 削除
   - `products-crud.spec.ts` の `.skip` 削除
   - `api-integration.spec.ts` の `.skip` 削除

### Global Setup（実装済み・無効化中）

テスト実行前に`fixtures/global-setup.ts`が自動実行され:

1. Laravel Sanctum経由でAdmin/Userの認証を実行
2. 認証状態を`storage/admin.json`, `storage/user.json`に保存
3. 各テストでこの認証状態を読み込み、ログイン済みでテスト開始

### Sanctum認証フロー（実装済み・無効化中）

`helpers/sanctum.ts`が以下を実行:

1. `/sanctum/csrf-cookie`でCSRFトークン取得
2. XSRF-TOKENクッキーをデコード
3. `/api/login`にPOSTリクエスト（CSRF token付き）
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
   curl http://localhost:13000/up
   curl http://localhost:13001
   curl http://localhost:13002
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

### ✅ GitHub Actions E2Eワークフロー（有効化済み）

E2Eテストは `.github/workflows/e2e-tests.yml` で自動実行されます。

#### ワークフロートリガー

1. **手動実行** (`workflow_dispatch`)
   - GitHub Actionsタブから「E2E Tests」を選択
   - 「Run workflow」ボタンをクリック
   - Shard数を選択（1/2/4/8、デフォルト: 4）

2. **Pull Request作成時** (`pull_request`)
   - mainブランチへのPR作成時に自動実行
   - 対象パス変更時のみ実行:
     - `frontend/**`
     - `backend/laravel-api/app/**`
     - `backend/laravel-api/routes/**`
     - `e2e/**`
     - `.github/workflows/e2e-tests.yml`

3. **mainブランチpush時** (`push`)
   - mainブランチへの直接pushまたはマージ時に自動実行
   - 対象パス変更時のみ実行（PR時と同じ）

#### Shard並列実行

GitHub Actionsでは **4並列実行** がデフォルト:

```yaml
strategy:
  fail-fast: false
  matrix:
    shard: [1, 2, 3, 4]
```

各Shardで以下のコマンドが実行されます:

```bash
npx playwright test --shard=1/4  # Shard 1
npx playwright test --shard=2/4  # Shard 2
npx playwright test --shard=3/4  # Shard 3
npx playwright test --shard=4/4  # Shard 4
```

#### CI環境での実行コマンド

GitHub Actionsで実行される実際のコマンド:

```bash
# 1. サービス起動（個別起動方式）
cd backend/laravel-api
php artisan serve --host=0.0.0.0 --port=13000 &

cd frontend/admin-app
npm run dev &  # ポート13002

cd frontend/user-app
npm run dev &  # ポート13001

# 2. サービス起動待機
npx wait-on \
  http://localhost:13001 \
  http://localhost:13002 \
  http://localhost:13000/up \
  --timeout 120000

# 3. E2E依存関係インストール
cd e2e
npm ci

# 4. Playwrightブラウザインストール
npx playwright install --with-deps

# 5. E2Eテスト実行（Shard分割）
npx playwright test --shard=${{ matrix.shard }}/4
```

#### 環境変数（CI環境）

GitHub Actionsでは以下の環境変数が自動設定されます:

```bash
E2E_ADMIN_URL=http://localhost:13002
E2E_USER_URL=http://localhost:13001
E2E_API_URL=http://localhost:13000
```

認証情報はGitHub Secretsで管理（現在は未設定、認証無効化中）:
- `E2E_ADMIN_EMAIL`
- `E2E_ADMIN_PASSWORD`
- `E2E_USER_EMAIL`
- `E2E_USER_PASSWORD`

#### テストレポート・Artifacts

GitHub Actionsでは自動的にレポートがアップロードされます:

- **Artifacts名**: `playwright-report-1`, `playwright-report-2`, `playwright-report-3`, `playwright-report-4`
- **保存期間**: 30日間
- **内容**:
  - HTMLレポート (`index.html`)
  - JUnitレポート (`junit.xml`)
  - スクリーンショット（失敗時）
  - トレースファイル（失敗時）

**Artifactsダウンロード手順**:
1. GitHub Actionsのワークフロータブにアクセス
2. 実行完了したワークフローを選択
3. 下部の「Artifacts」セクションからダウンロード

#### パフォーマンス

- **実行時間**: 約2分（全4 Shard並列実行）
- **タイムアウト**: 60分（ジョブレベル）
- **wait-on待機**: 120秒タイムアウト

### トラブルシューティング（CI環境）

#### サービス起動失敗

GitHub Actionsログで確認:

```bash
# Laravelサーバー起動確認
http://localhost:13000/up

# Next.jsアプリ起動確認
http://localhost:13001
http://localhost:13002
```

#### wait-onタイムアウト

タイムアウト延長が必要な場合、`.github/workflows/e2e-tests.yml` を修正:

```yaml
- name: Wait for services to be ready
  run: |
    npx wait-on \
      http://localhost:13001 \
      http://localhost:13002 \
      http://localhost:13000/up \
      --timeout 180000  # 120秒 → 180秒に延長
```

#### Playwrightブラウザインストールエラー

ワークフローで自動的に `--with-deps` フラグでインストールされます:

```yaml
- name: Install Playwright browsers
  working-directory: e2e
  run: npx playwright install --with-deps
```

## 参考資料

- [Playwright公式ドキュメント](https://playwright.dev/)
- [Laravel Sanctum公式ドキュメント](https://laravel.com/docs/sanctum)
- [Page Object Model パターン](https://playwright.dev/docs/pom)

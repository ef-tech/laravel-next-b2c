# Requirements Document

## Introduction

本仕様は、Laravel 12 API + Next.js 15.5 + React 19のフルスタックアプリケーションにおけるE2Eテスト環境基盤を構築するものです。現在、ユニットテスト（Pest 4）およびコンポーネントテスト（Jest 29 + React Testing Library 16）は整備済みですが、エンドツーエンドのユーザー操作フローを検証する包括的なテスト環境が欠如しています。

Playwright 1.47.2を採用したE2Eテスト環境を導入することで、以下のビジネス価値を実現します：

- **品質保証の完全化**: フロントエンド・バックエンド統合部分の動作検証による本番環境でのバグ削減
- **開発効率の向上**: 自動化されたリグレッションテストにより、手動テストコストを大幅削減
- **チーム開発の加速**: CI/CD統合により、Pull Request時の自動品質チェックを実現
- **保守性の向上**: Page Object Modelによる再利用可能なテストコード資産の構築

本要件ドキュメントは、モノレポ構成（admin-app/user-app）に対応したE2Eテスト基盤の包括的な要件を定義します。

## Requirements

### Requirement 1: Playwrightテストフレームワークのセットアップ

**Objective:** 開発者として、Playwright最新版を使用したE2Eテスト環境を利用できるようにすることで、ブラウザ自動化による信頼性の高いテストを作成できるようにする

#### Acceptance Criteria

1. WHEN 開発者がe2eディレクトリを作成する THEN E2Eテスト環境 SHALL プロジェクトルートに`e2e/`ディレクトリを配置する
2. WHEN 開発者がPlaywrightをインストールする THEN E2Eテスト環境 SHALL `@playwright/test`バージョン1.47.2以上をインストールする
3. WHEN 開発者がTypeScript設定を行う THEN E2Eテスト環境 SHALL TypeScript 5.6.2以上でPlaywrightの型定義をサポートする
4. WHEN 開発者がdotenv設定を行う THEN E2Eテスト環境 SHALL 環境変数管理のためにdotenv 16.4.5以上を利用可能にする
5. WHERE `e2e/package.json` THE E2Eテスト環境 SHALL 以下のnpmスクリプトを提供する：
   - `test`: 全テスト実行
   - `test:ui`: Playwright UI Modeでのテスト実行
   - `test:debug`: デバッグモードでのテスト実行
   - `test:ci`: CI環境向けdot reporterでのテスト実行
   - `test:admin`: admin-appプロジェクトのみ実行
   - `test:user`: user-appプロジェクトのみ実行
   - `codegen:admin`: admin-app用テストコード生成
   - `codegen:user`: user-app用テストコード生成
   - `report`: HTMLレポート表示

### Requirement 2: Playwright設定ファイルの構築

**Objective:** 開発者として、モノレポ構成に対応したPlaywright設定を利用できるようにすることで、admin-appとuser-appの両方を効率的にテストできるようにする

#### Acceptance Criteria

1. WHEN 開発者がplaywright.config.tsを作成する THEN E2Eテスト環境 SHALL `e2e/playwright.config.ts`にTypeScript設定ファイルを配置する
2. WHERE playwright.config.ts THE E2Eテスト環境 SHALL テストディレクトリを`./projects`に設定する
3. WHERE playwright.config.ts THE E2Eテスト環境 SHALL タイムアウトを60秒、expectタイムアウトを10秒に設定する
4. WHERE playwright.config.ts THE E2Eテスト環境 SHALL `fullyParallel: true`で並列実行を有効化する
5. IF CI環境で実行される THEN E2Eテスト環境 SHALL ワーカー数を4、リトライ回数を2に設定する
6. WHERE playwright.config.ts THE E2Eテスト環境 SHALL 以下のレポーターを設定する：
   - list reporter（コンソール出力）
   - html reporter（HTMLレポート、reports/htmlに出力）
   - junit reporter（JUnit XML、reports/junit.xmlに出力）
7. WHERE playwright.config.ts THE E2Eテスト環境 SHALL 以下のデフォルトuse設定を提供する：
   - `trace: 'retain-on-failure'`（失敗時のみトレース保存）
   - `screenshot: 'only-on-failure'`（失敗時のみスクリーンショット）
   - `video: 'retain-on-failure'`（失敗時のみビデオ録画）
   - `ignoreHTTPSErrors: true`（開発環境用）
8. WHERE playwright.config.ts THE E2Eテスト環境 SHALL `admin-chromium`プロジェクトを定義し、以下を設定する：
   - testDir: `./projects/admin/tests`
   - baseURL: `process.env.E2E_ADMIN_URL ?? 'http://localhost:3001'`
   - storageState: `storage/admin.json`
   - devices: Desktop Chrome
9. WHERE playwright.config.ts THE E2Eテスト環境 SHALL `user-chromium`プロジェクトを定義し、以下を設定する：
   - testDir: `./projects/user/tests`
   - baseURL: `process.env.E2E_USER_URL ?? 'http://localhost:3000'`
   - storageState: `storage/user.json`
   - devices: Desktop Chrome
10. WHERE playwright.config.ts THE E2Eテスト環境 SHALL globalSetupに`./fixtures/global-setup`を指定する

### Requirement 3: Laravel Sanctum認証統合

**Objective:** 開発者として、Laravel Sanctum認証を自動化できるようにすることで、ログイン状態を必要とするE2Eテストを効率的に作成できるようにする

#### Acceptance Criteria

1. WHEN 開発者がSanctum認証ヘルパーを実装する THEN E2Eテスト環境 SHALL `e2e/helpers/sanctum.ts`にsanctumLogin関数を提供する
2. WHEN sanctumLogin関数が実行される THEN E2Eテスト環境 SHALL `/sanctum/csrf-cookie`エンドポイントからCSRFクッキーを取得する
3. WHEN CSRFクッキーを取得する THEN E2Eテスト環境 SHALL `X-Requested-With: XMLHttpRequest`ヘッダーを送信する
4. WHEN CSRFクッキー取得が成功する THEN E2Eテスト環境 SHALL `XSRF-TOKEN`クッキーからトークンを抽出しURLデコードする
5. WHEN ログインAPIを実行する THEN E2Eテスト環境 SHALL `/login`エンドポイントに以下のヘッダーで POST リクエストを送信する：
   - `X-Requested-With: XMLHttpRequest`
   - `X-XSRF-TOKEN: [デコード済みトークン]`
   - `Content-Type: application/json`
6. WHEN ログインAPIが成功する THEN E2Eテスト環境 SHALL `/api/user`エンドポイントで認証状態を確認する
7. WHEN 認証確認が成功する THEN E2Eテスト環境 SHALL storageStateオブジェクトを返却する
8. IF いずれかのAPI呼び出しが失敗する THEN E2Eテスト環境 SHALL エラーメッセージと共に例外をスローする

### Requirement 4: Global Setup認証処理の実装

**Objective:** 開発者として、テスト実行前に自動的に認証状態を準備できるようにすることで、各テストケースでの認証処理を省略し実行時間を短縮する

#### Acceptance Criteria

1. WHEN 開発者がglobal-setupを実装する THEN E2Eテスト環境 SHALL `e2e/fixtures/global-setup.ts`にglobalSetup関数を提供する
2. WHEN globalSetup関数が実行される THEN E2Eテスト環境 SHALL `process.env.E2E_API_URL`から APIベースURLを取得する（デフォルト: `http://localhost:8000`）
3. WHEN globalSetup関数が実行される THEN E2Eテスト環境 SHALL `e2e/storage/`ディレクトリを再帰的に作成する
4. WHEN Admin認証を実行する THEN E2Eテスト環境 SHALL APIベースURLでAPIRequestContextを作成する
5. WHEN Admin認証を実行する THEN E2Eテスト環境 SHALL `process.env.E2E_ADMIN_EMAIL`と`process.env.E2E_ADMIN_PASSWORD`を使用してsanctumLogin関数を呼び出す
6. WHEN Admin認証が成功する THEN E2Eテスト環境 SHALL 認証状態を`storage/admin.json`にJSON形式で保存する
7. WHEN Admin認証が完了する THEN E2Eテスト環境 SHALL APIRequestContextを破棄する
8. WHEN User認証を実行する THEN E2Eテスト環境 SHALL APIベースURLで新しいAPIRequestContextを作成する
9. WHEN User認証を実行する THEN E2Eテスト環境 SHALL `process.env.E2E_USER_EMAIL`と`process.env.E2E_USER_PASSWORD`を使用してsanctumLogin関数を呼び出す
10. WHEN User認証が成功する THEN E2Eテスト環境 SHALL 認証状態を`storage/user.json`にJSON形式で保存する
11. WHEN User認証が完了する THEN E2Eテスト環境 SHALL APIRequestContextを破棄する

### Requirement 5: Page Object Modelパターンの実装

**Objective:** 開発者として、Page Object Modelパターンを使用したテストコードを作成できるようにすることで、保守性が高く再利用可能なテストを記述する

#### Acceptance Criteria

1. WHEN 開発者がPage Objectを作成する THEN E2Eテスト環境 SHALL `e2e/projects/admin/pages/`ディレクトリにPage Objectクラスを配置する
2. WHEN AdminLoginPageを実装する THEN E2Eテスト環境 SHALL constructorでPageオブジェクトを受け取る
3. WHEN AdminLoginPage.goto()が実行される THEN E2Eテスト環境 SHALL `/login`ページにナビゲートする
4. WHEN AdminLoginPage.goto()が実行される THEN E2Eテスト環境 SHALL `data-testid="login-form"`要素の表示を待機する
5. WHEN AdminLoginPage.login()が実行される THEN E2Eテスト環境 SHALL `data-testid="email"`要素にemailパラメータを入力する
6. WHEN AdminLoginPage.login()が実行される THEN E2Eテスト環境 SHALL `data-testid="password"`要素にpasswordパラメータを入力する
7. WHEN AdminLoginPage.login()が実行される THEN E2Eテスト環境 SHALL `data-testid="submit"`要素をクリックする
8. WHEN ログインsubmitが完了する THEN E2Eテスト環境 SHALL URLが`**/dashboard`にマッチするまで待機する
9. WHERE Page Object実装 THE E2Eテスト環境 SHALL TypeScript型定義を使用し、Pageオブジェクトの型安全性を保証する

### Requirement 6: テストサンプルの作成

**Objective:** 開発者として、実用的なE2Eテストサンプルを参照できるようにすることで、新規テストケース作成の指針とする

#### Acceptance Criteria

1. WHEN 開発者がログインテストを作成する THEN E2Eテスト環境 SHALL `e2e/projects/admin/tests/login.spec.ts`にテストファイルを配置する
2. WHERE login.spec.ts THE E2Eテスト環境 SHALL `test.describe('Admin Login')`でテストスイートをグループ化する
3. WHERE login.spec.ts THE E2Eテスト環境 SHALL `test('can login via UI')`でUIログインテストを定義する
4. WHEN ログインテストが実行される THEN E2Eテスト環境 SHALL AdminLoginPageインスタンスを作成する
5. WHEN ログインテストが実行される THEN E2Eテスト環境 SHALL AdminLoginPage.goto()を呼び出しログインページを表示する
6. WHEN ログインテストが実行される THEN E2Eテスト環境 SHALL AdminLoginPage.login()を環境変数の認証情報で呼び出す
7. WHEN ログインが成功する THEN E2Eテスト環境 SHALL `data-testid="dashboard"`要素が表示されることを検証する
8. WHEN 開発者がCRUDテストを作成する THEN E2Eテスト環境 SHALL `e2e/projects/admin/tests/products-crud.spec.ts`にテストファイルを配置する
9. WHEN 開発者がAPI統合テストを作成する THEN E2Eテスト環境 SHALL `e2e/projects/user/tests/api-integration.spec.ts`にテストファイルを配置する
10. WHERE テストファイル THE E2Eテスト環境 SHALL `@playwright/test`から`test`と`expect`をインポートする

### Requirement 7: Docker Compose統合

**Objective:** 開発者として、Docker環境でE2Eテストを実行できるようにすることで、ローカル環境とCI環境の一貫性を保証する

#### Acceptance Criteria

1. WHEN 開発者がDockerfile.e2eを作成する THEN E2Eテスト環境 SHALL `e2e/docker/Dockerfile.e2e`にDockerfileを配置する
2. WHEN 開発者がdocker-compose.ymlを更新する THEN E2Eテスト環境 SHALL `e2e-tests`サービスを追加する
3. WHERE e2e-testsサービス THE E2Eテスト環境 SHALL ベースイメージに`mcr.microsoft.com/playwright:v1.47.2-jammy`を使用する
4. WHERE e2e-testsサービス THE E2Eテスト環境 SHALL working_dirを`/work/e2e`に設定する
5. WHERE e2e-testsサービス THE E2Eテスト環境 SHALL プロジェクトルートを`/work`にマウントする（cached）
6. WHERE e2e-testsサービス THE E2Eテスト環境 SHALL `/work/e2e/node_modules`を名前付きボリュームにマウントする
7. WHERE e2e-testsサービス THE E2Eテスト環境 SHALL 以下の環境変数を設定する：
   - `E2E_ADMIN_URL: http://admin-app:3000`
   - `E2E_USER_URL: http://user-app:3000`
   - `E2E_API_URL: http://laravel-api:80`
   - `E2E_ADMIN_EMAIL: admin@example.com`
   - `E2E_ADMIN_PASSWORD: password`
   - `E2E_USER_EMAIL: user@example.com`
   - `E2E_USER_PASSWORD: password`
   - `CI: '1'`
8. WHERE e2e-testsサービス THE E2Eテスト環境 SHALL depends_onに`admin-app`、`user-app`、`laravel-api`を指定する
9. WHERE e2e-testsサービス THE E2Eテスト環境 SHALL shm_sizeを`1gb`に設定する
10. WHERE e2e-testsサービス THE E2Eテスト環境 SHALL commandで以下を順次実行する：
    - `npm install`
    - `npx playwright install --with-deps`
    - `npm run test:ci`

### Requirement 8: CI/CDパイプラインの構築

**Objective:** 開発者として、GitHub ActionsでE2Eテストを自動実行できるようにすることで、Pull Request時の品質保証を自動化する

#### Acceptance Criteria

1. WHEN 開発者がワークフローを作成する THEN E2Eテスト環境 SHALL `.github/workflows/e2e-tests.yml`にワークフロー定義を配置する
2. WHERE e2e-tests.yml THE E2Eテスト環境 SHALL ワークフロー名を`E2E Tests`に設定する
3. WHERE e2e-tests.yml THE E2Eテスト環境 SHALL トリガーを`push`（mainとdevelopブランチ）と`pull_request`（mainブランチ）に設定する
4. WHERE e2e-tests.yml THE E2Eテスト環境 SHALL ジョブ名を`e2e-tests`に設定する
5. WHERE e2e-testsジョブ THE E2Eテスト環境 SHALL `runs-on: ubuntu-latest`で実行する
6. WHERE e2e-testsジョブ THE E2Eテスト環境 SHALL `timeout-minutes: 60`を設定する
7. WHERE e2e-testsジョブ THE E2Eテスト環境 SHALL strategyのmatrixで`shard: [1, 2, 3, 4]`を定義する
8. WHERE e2e-testsジョブ THE E2Eテスト環境 SHALL `fail-fast: false`を設定する
9. WHEN ワークフローステップが実行される THEN E2Eテスト環境 SHALL `actions/checkout@v4`でリポジトリをチェックアウトする
10. WHEN ワークフローステップが実行される THEN E2Eテスト環境 SHALL `actions/setup-node@v4`でNode.js 20をセットアップする
11. WHEN ワークフローステップが実行される THEN E2Eテスト環境 SHALL `docker-compose up -d --build`でサービスを起動する
12. WHEN ワークフローステップが実行される THEN E2Eテスト環境 SHALL `npx wait-on`で以下のサービスの起動を待機する：
    - `http://localhost:3000`
    - `http://localhost:3001`
    - `http://localhost:8000/up`
13. WHEN ワークフローステップが実行される THEN E2Eテスト環境 SHALL `e2e`ディレクトリで`npm ci`を実行する
14. WHEN ワークフローステップが実行される THEN E2Eテスト環境 SHALL `e2e`ディレクトリで`npx playwright install --with-deps`を実行する
15. WHEN ワークフローステップが実行される THEN E2Eテスト環境 SHALL `e2e`ディレクトリで`npx playwright test --shard=${{ matrix.shard }}/4`を実行する
16. WHEN ワークフローステップが実行される THEN E2Eテスト環境 SHALL 以下の環境変数を設定する：
    - `E2E_ADMIN_URL: http://localhost:3001`
    - `E2E_USER_URL: http://localhost:3000`
    - `E2E_API_URL: http://localhost:8000`
17. WHEN テスト実行が完了する THEN E2Eテスト環境 SHALL `actions/upload-artifact@v4`でテストレポートをアップロードする
18. IF テスト実行ステップが失敗する THEN E2Eテスト環境 SHALL アーティファクトアップロードを実行する（`if: always()`）
19. WHERE アーティファクトアップロード THE E2Eテスト環境 SHALL アーティファクト名を`playwright-report-${{ matrix.shard }}`に設定する
20. WHERE アーティファクトアップロード THE E2Eテスト環境 SHALL パスを`e2e/reports/`に設定する
21. WHERE アーティファクトアップロード THE E2Eテスト環境 SHALL retention-daysを30日に設定する

### Requirement 9: テストデータ管理戦略

**Objective:** 開発者として、テスト実行時のデータ独立性を保証できるようにすることで、フレーキーテスト（不安定なテスト）を回避する

#### Acceptance Criteria

1. WHEN テストが実行される THEN E2Eテスト環境 SHALL 各テストケースが独立したデータセットを使用できるようにする
2. WHEN テストデータを準備する THEN E2Eテスト環境 SHALL Laravel Seederまたはファクトリーを利用してテストデータを生成する
3. WHEN テストが完了する THEN E2Eテスト環境 SHALL 必要に応じてデータベースリセット機構を提供する
4. WHERE 認証状態 THE E2Eテスト環境 SHALL `storage/admin.json`と`storage/user.json`を再利用することで認証処理を省略する
5. IF テストデータ競合が発生する THEN E2Eテスト環境 SHALL 各テストケースで一意のデータ識別子を使用する

### Requirement 10: デバッグ機能とレポート

**Objective:** 開発者として、テスト失敗時のデバッグを効率化できるようにすることで、問題解決の時間を短縮する

#### Acceptance Criteria

1. WHEN テストが失敗する THEN E2Eテスト環境 SHALL トレースファイルを`trace: 'retain-on-failure'`設定で保存する
2. WHEN テストが失敗する THEN E2Eテスト環境 SHALL スクリーンショットを`screenshot: 'only-on-failure'`設定で保存する
3. WHEN テストが失敗する THEN E2Eテスト環境 SHALL ビデオ録画を`video: 'retain-on-failure'`設定で保存する
4. WHEN 開発者がHTMLレポートを確認する THEN E2Eテスト環境 SHALL `npm run report`コマンドでPlaywright HTMLレポートを表示する
5. WHERE HTMLレポート THE E2Eテスト環境 SHALL `reports/html`ディレクトリにレポートを出力する
6. WHERE JUnitレポート THE E2Eテスト環境 SHALL `reports/junit.xml`にXML形式のレポートを出力する
7. WHEN 開発者がUIモードを使用する THEN E2Eテスト環境 SHALL `npm run test:ui`コマンドでPlaywright UI Modeを起動する
8. WHEN 開発者がデバッグモードを使用する THEN E2Eテスト環境 SHALL `npm run test:debug`コマンドでデバッガー付きテスト実行を可能にする
9. WHEN 開発者がコード生成を使用する THEN E2Eテスト環境 SHALL `npm run codegen:admin`または`npm run codegen:user`でテストコードの雛形を生成する

### Requirement 11: パフォーマンスと並列実行

**Objective:** 開発者として、E2Eテストを並列実行できるようにすることで、CI/CD実行時間を最小化する

#### Acceptance Criteria

1. WHERE playwright.config.ts THE E2Eテスト環境 SHALL `fullyParallel: true`でテストの完全並列実行を有効化する
2. IF CI環境で実行される THEN E2Eテスト環境 SHALL ワーカー数を4に設定する
3. IF ローカル環境で実行される THEN E2Eテスト環境 SHALL ワーカー数を`undefined`（自動）に設定する
4. WHERE GitHub Actionsワークフロー THE E2Eテスト環境 SHALL シャーディング（4分割）で並列実行する
5. WHEN シャーディング実行する THEN E2Eテスト環境 SHALL `--shard=${{ matrix.shard }}/4`パラメータを使用する
6. WHEN CI環境でテストが失敗する THEN E2Eテスト環境 SHALL リトライ回数2回で自動再試行する
7. WHERE 認証処理 THE E2Eテスト環境 SHALL globalSetupで認証状態を事前作成することで各テストケースの認証時間を削減する

### Requirement 12: 環境変数管理

**Objective:** 開発者として、環境別の設定を柔軟に管理できるようにすることで、ローカル・Docker・CI環境でシームレスにテストを実行する

#### Acceptance Criteria

1. WHEN ローカル環境でテストを実行する THEN E2Eテスト環境 SHALL 以下のデフォルト環境変数を使用する：
   - `E2E_ADMIN_URL: http://localhost:3001`
   - `E2E_USER_URL: http://localhost:3000`
   - `E2E_API_URL: http://localhost:8000`
2. WHEN Docker環境でテストを実行する THEN E2Eテスト環境 SHALL 以下のDocker内部URL環境変数を使用する：
   - `E2E_ADMIN_URL: http://admin-app:3000`
   - `E2E_USER_URL: http://user-app:3000`
   - `E2E_API_URL: http://laravel-api:80`
3. WHEN CI環境でテストを実行する THEN E2Eテスト環境 SHALL GitHub Actionsワークフローで定義された環境変数を使用する
4. WHERE 認証情報 THE E2Eテスト環境 SHALL 以下の環境変数を要求する：
   - `E2E_ADMIN_EMAIL`
   - `E2E_ADMIN_PASSWORD`
   - `E2E_USER_EMAIL`
   - `E2E_USER_PASSWORD`
5. WHEN 開発者が環境変数を設定する THEN E2Eテスト環境 SHALL `.env`ファイルまたはシェル環境変数からdotenvで読み込む
6. IF 必須環境変数が未設定である THEN E2Eテスト環境 SHALL エラーメッセージを表示してテスト実行を中断する

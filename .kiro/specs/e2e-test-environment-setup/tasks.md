# 実装タスク

## 概要

本タスクリストは、Playwright 1.47.2を基盤としたE2Eテスト環境の実装手順を定義する。モノレポ構成（admin-app/user-app）に対応し、Laravel Sanctum認証統合、Page Object Model、Docker Compose統合を含むE2Eテスト基盤を段階的に構築する。

## GitHub Actions 無料枠対策方針

**初期構築フェーズ**: CI/CDワークフローは `.github/workflows/e2e-tests.yml.disabled` として実装し、ローカル/Docker環境での動作確認に注力する。

**運用フェーズ**: 必要時にワークフローを手動有効化（リネーム）し、トリガー条件を制限して実行する。

**コスト削減戦略**:
- ワークフローは手動トリガー（workflow_dispatch）を優先
- path制限でE2E関連ファイル変更時のみ実行
- 無料枠消費を最小化しつつ、必要時の品質保証を実現

---

- [x] 1. E2Eテスト基盤のディレクトリ構造とPlaywright環境を構築する
- [x] 1.1 プロジェクトルートにe2eディレクトリを作成し、npm環境を初期化する
  - プロジェクトルートに`e2e/`ディレクトリを作成
  - `e2e/package.json`を作成し、プロジェクト名を"e2e"、private: trueに設定
  - Playwright 1.47.2、TypeScript 5.6.2、dotenv 16.4.5をdevDependenciesとしてインストール
  - npmスクリプト（test、test:ui、test:debug、test:ci、test:admin、test:user、codegen:admin、codegen:user、report）を定義
  - _要件: 1.1, 1.2, 1.3, 1.4, 1.5_

- [x] 1.2 TypeScript設定ファイルとディレクトリ構造を作成する
  - `e2e/tsconfig.json`を作成し、Playwright型定義をサポートするTypeScript設定を定義
  - `e2e/projects/admin/pages/`、`e2e/projects/admin/tests/`ディレクトリを作成
  - `e2e/projects/user/tests/`ディレクトリを作成
  - `e2e/fixtures/`、`e2e/helpers/`、`e2e/storage/`ディレクトリを作成
  - `e2e/storage/.gitignore`を作成し、認証状態ファイル（*.json）をGit管理対象外に設定
  - _要件: 1.1, 1.3_

- [x] 2. Playwright設定ファイルを作成し、モノレポ対応のプロジェクト構成を定義する
- [x] 2.1 playwright.config.tsの基本設定を実装する
  - `e2e/playwright.config.ts`を作成し、defineConfigをインポート
  - dotenv/configをインポートして環境変数を読み込む
  - testDirを`./projects`に設定
  - timeout（60秒）、expectタイムアウト（10秒）を設定
  - fullyParallel: trueで並列実行を有効化
  - workers設定（CI環境: 4、ローカル: undefined）
  - retries設定（CI環境: 2、ローカル: 0）
  - _要件: 2.1, 2.2, 2.3, 2.4, 2.5_

- [x] 2.2 レポーター設定とデフォルトuse設定を実装する
  - reporter配列に['list']、['html', { open: 'never', outputFolder: 'reports/html' }]、['junit', { outputFile: 'reports/junit.xml' }]を設定
  - use設定でtrace: 'retain-on-failure'、screenshot: 'only-on-failure'、video: 'retain-on-failure'、ignoreHTTPSErrors: trueを設定
  - _要件: 2.6, 2.7_

- [x] 2.3 setupプロジェクトとadmin/userプロジェクトを定義する
  - setupプロジェクトを作成し、testMatchで`/global\.setup\.ts/`を指定
  - admin-chromiumプロジェクトを作成し、testDir: './projects/admin/tests'、baseURL: process.env.E2E_ADMIN_URL ?? 'http://localhost:3001'、storageState: 'storage/admin.json'、dependencies: ['setup']を設定
  - user-chromiumプロジェクトを作成し、testDir: './projects/user/tests'、baseURL: process.env.E2E_USER_URL ?? 'http://localhost:3000'、storageState: 'storage/user.json'、dependencies: ['setup']を設定
  - 両プロジェクトにDesktop Chromeデバイス設定を適用
  - _要件: 2.8, 2.9, 2.10_

- [x] 3. Laravel Sanctum認証統合ヘルパーを実装する
- [x] 3.1 sanctumLogin関数を実装し、CSRF取得とトークン処理を行う
  - `e2e/helpers/sanctum.ts`を作成
  - APIRequestContextを受け取るsanctumLogin関数を定義
  - `/sanctum/csrf-cookie`エンドポイントにGETリクエストを送信（X-Requested-With: XMLHttpRequestヘッダー）
  - レスポンスが成功しない場合、エラーをスロー
  - storageStateからXSRF-TOKENクッキーを取得し、decodeURIComponentでデコード
  - _要件: 3.1, 3.2, 3.3, 3.4_

- [x] 3.2 ログインAPI実行と認証確認を実装する
  - デコード済みXSRF-TOKENを使用して`/login`エンドポイントにPOSTリクエストを送信
  - リクエストヘッダーにX-Requested-With: XMLHttpRequest、X-XSRF-TOKEN: token、Content-Type: application/jsonを設定
  - リクエストボディにemail、passwordを含む
  - ログイン失敗時、HTTPステータスコードと共にエラーをスロー
  - `/api/user`エンドポイントにGETリクエストを送信して認証状態を確認
  - 認証確認失敗時、エラーをスロー
  - api.storageState()でstorageStateオブジェクトを取得して返却
  - _要件: 3.5, 3.6, 3.7, 3.8_

- [x] 4. Global Setup認証処理を実装し、テスト実行前に認証状態を準備する
- [x] 4.1 globalSetup関数を実装し、環境変数からAPIベースURLを取得する
  - `e2e/fixtures/global-setup.ts`を作成
  - @playwright/testからrequest、FullConfigをインポート
  - sanctum.tsからsanctumLogin関数をインポート
  - node:fs、node:pathをインポート
  - dotenv/configをインポート
  - globalSetup関数を定義し、process.env.E2E_API_URLからAPIベースURL取得（デフォルト: http://localhost:8000）
  - `e2e/storage/`ディレクトリを再帰的に作成
  - _要件: 4.1, 4.2, 4.3_

- [x] 4.2 Admin認証処理を実装し、認証状態を保存する
  - APIベースURLでAPIRequestContextを作成
  - process.env.E2E_ADMIN_EMAIL、process.env.E2E_ADMIN_PASSWORDを使用してsanctumLogin関数を呼び出す
  - 返却されたstorageStateを`storage/admin.json`にJSON形式で保存
  - APIRequestContextを破棄
  - _要件: 4.4, 4.5, 4.6, 4.7_

- [x] 4.3 User認証処理を実装し、認証状態を保存する
  - APIベースURLで新しいAPIRequestContextを作成
  - process.env.E2E_USER_EMAIL、process.env.E2E_USER_PASSWORDを使用してsanctumLogin関数を呼び出す
  - 返却されたstorageStateを`storage/user.json`にJSON形式で保存
  - APIRequestContextを破棄
  - _要件: 4.8, 4.9, 4.10, 4.11_

- [x] 5. Page Object Modelパターンを実装し、再利用可能なページクラスを作成する
- [x] 5.1 AdminLoginPageクラスを実装する
  - `e2e/projects/admin/pages/LoginPage.ts`を作成
  - @playwright/testからPage、expectをインポート
  - AdminLoginPageクラスを定義し、constructorでPageオブジェクトを受け取る
  - goto()メソッドを実装し、`/login`ページにナビゲート後、`data-testid="login-form"`要素の表示を待機
  - login(email: string, password: string)メソッドを実装し、`data-testid="email"`にemail入力、`data-testid="password"`にpassword入力、`data-testid="submit"`をクリック、URLが`**/dashboard`にマッチするまで待機
  - TypeScript型定義を使用し、Pageオブジェクトの型安全性を保証
  - _要件: 5.1, 5.2, 5.3, 5.4, 5.5, 5.6, 5.7, 5.8, 5.9_

- [x] 5.2 ProductsPageクラスを実装する
  - `e2e/projects/admin/pages/ProductsPage.ts`を作成
  - ProductsPageクラスを定義し、constructorでPageオブジェクトを受け取る
  - goto()メソッドを実装し、商品一覧ページにナビゲート
  - 商品作成、編集、削除の各操作メソッドを実装
  - TypeScript型定義を使用
  - _要件: 5.1, 5.9_

- [x] 6. テストサンプルを作成し、E2Eテストの実装パターンを示す
- [x] 6.1 管理者ログインテストを作成する
  - `e2e/projects/admin/tests/login.spec.ts`を作成
  - @playwright/testからtest、expectをインポート
  - AdminLoginPageをインポート
  - test.describe('Admin Login')でテストスイートをグループ化
  - test('can login via UI')でログインテストを定義
  - AdminLoginPageインスタンスを作成し、goto()を呼び出してログインページを表示
  - 環境変数の認証情報でlogin()を呼び出す
  - `data-testid="dashboard"`要素が表示されることを検証
  - _要件: 6.1, 6.2, 6.3, 6.4, 6.5, 6.6, 6.7, 6.10_

- [x] 6.2 商品CRUD操作テストを作成する
  - `e2e/projects/admin/tests/products-crud.spec.ts`を作成
  - ProductsPageをインポート
  - test.describe('Products CRUD')でテストスイートをグループ化
  - 商品一覧表示テスト、新規作成テスト、編集テスト、削除テストをそれぞれ定義
  - ProductsPageの各メソッドを使用してCRUD操作を実行
  - _要件: 6.8, 6.10_

- [x] 6.3 ユーザーアプリAPI統合テストを作成する
  - `e2e/projects/user/tests/api-integration.spec.ts`を作成
  - test.describe('API Integration')でテストスイートをグループ化
  - API GETリクエストテスト（商品一覧取得→画面表示確認）を定義
  - API POSTリクエストテスト（お問い合わせフォーム送信→成功メッセージ表示）を定義
  - _要件: 6.9, 6.10_

- [ ] 7. Docker Compose統合を実装し、Docker環境でE2Eテストを実行可能にする
- [ ] 7.1 Dockerfile.e2eを作成する
  - `e2e/docker/Dockerfile.e2e`を作成（カスタムイメージが必要な場合）
  - mcr.microsoft.com/playwright:v1.47.2-jammyをベースイメージに指定
  - 必要な依存関係をインストール
  - _要件: 7.1, 7.3_

- [ ] 7.2 docker-compose.ymlにe2e-testsサービスを追加する
  - `backend/laravel-api/compose.yaml`（または適切なdocker-compose.yml）にe2e-testsサービスを追加
  - imageにmcr.microsoft.com/playwright:v1.47.2-jammyを指定
  - working_dirを`/work/e2e`に設定
  - プロジェクトルートを`/work`にマウント（cached）
  - `/work/e2e/node_modules`を名前付きボリュームにマウント
  - 環境変数（E2E_ADMIN_URL、E2E_USER_URL、E2E_API_URL、認証情報、CI='1'）を設定
  - depends_onにadmin-app、user-app、laravel-apiを指定
  - shm_sizeを1gbに設定
  - commandで`npm install && npx playwright install --with-deps && npm run test:ci`を実行
  - _要件: 7.2, 7.4, 7.5, 7.6, 7.7, 7.8, 7.9, 7.10_

- [x] 8. GitHub Actions CI/CDワークフローファイルを準備する（初期構築時は無効化）
- [x] 8.1 E2Eテストワークフローファイルを.disabledとして作成する
  - `.github/workflows/e2e-tests.yml.disabled`を作成（GitHub Actions無料枠対策）
  - ワークフロー名を"E2E Tests"に設定
  - トリガーをworkflow_dispatch（手動実行）およびpush（path制限付き）に設定
  - path制限: 'frontend/**', 'backend/laravel-api/app/**', 'backend/laravel-api/routes/**', 'e2e/**', '.github/workflows/e2e-tests.yml'
  - workflow_dispatchにshard_count入力（デフォルト: '4'）を追加
  - ジョブ名をe2e-testsに設定
  - runs-on: ubuntu-latest、timeout-minutes: 60を設定
  - strategyのmatrixでshard: [1, 2, 3, 4]を定義
  - fail-fast: falseを設定
  - _要件: 8.1, 8.2, 8.3, 8.4, 8.5, 8.6, 8.7, 8.8（トリガー条件を制限）_

- [x] 8.2 ワークフローステップを実装する（チェックアウト、Node.jsセットアップ、Docker起動、サービス待機）
  - actions/checkout@v4でリポジトリをチェックアウト
  - actions/setup-node@v4でNode.js 20をセットアップ
  - docker-compose up -d --buildでサービスを起動
  - npx wait-onでhttp://localhost:3000、http://localhost:3001、http://localhost:8000/upの起動を待機
  - _要件: 8.9, 8.10, 8.11, 8.12_

- [x] 8.3 E2Eテスト実行とアーティファクトアップロードを実装する
  - e2eディレクトリでnpm ciを実行
  - e2eディレクトリでnpx playwright install --with-depsを実行
  - e2eディレクトリでnpx playwright test --shard=${{ matrix.shard }}/4を実行
  - 環境変数（E2E_ADMIN_URL、E2E_USER_URL、E2E_API_URL）を設定
  - actions/upload-artifact@v4でテストレポートをアップロード（if: always()）
  - アーティファクト名をplaywright-report-${{ matrix.shard }}に設定
  - パスをe2e/reports/に設定
  - retention-daysを30に設定
  - _要件: 8.13, 8.14, 8.15, 8.16, 8.17, 8.18, 8.19, 8.20, 8.21_

- [ ] 9. テストデータ管理戦略を実装し、データ独立性を保証する
- [ ] 9.1 テストデータ準備機構を実装する
  - Laravel SeederまたはFactoryを利用してテストデータを生成する機構を準備
  - 各テストケースが独立したデータセットを使用できるよう、一意のデータ識別子を使用
  - 認証状態（storage/admin.json、storage/user.json）を再利用することで認証処理を省略
  - 必要に応じてデータベースリセット機構を提供
  - _要件: 9.1, 9.2, 9.3, 9.4, 9.5_

- [ ] 10. デバッグ機能とレポート生成を検証する
- [ ] 10.1 デバッグ機能の動作を確認する
  - トレースファイル、スクリーンショット、ビデオ録画が失敗時に正常保存されることを確認
  - npm run reportコマンドでPlaywright HTMLレポートが表示されることを確認
  - npm run test:uiコマンドでPlaywright UI Modeが起動することを確認
  - npm run test:debugコマンドでデバッガー付きテスト実行が可能なことを確認
  - npm run codegen:adminおよびcodegen:userコマンドでテストコード生成が可能なことを確認
  - _要件: 10.1, 10.2, 10.3, 10.4, 10.5, 10.6, 10.7, 10.8, 10.9_

- [ ] 11. パフォーマンスと並列実行の動作を検証する
- [ ] 11.1 並列実行設定の動作を確認する
  - fullyParallel: trueでテストが完全並列実行されることを確認
  - CI環境でワーカー数4、ローカル環境でワーカー数自動設定が適用されることを確認
  - GitHub Actionsでシャーディング4並列実行が正常動作することを確認
  - CI環境でリトライ回数2が適用されることを確認
  - GlobalSetupで認証状態を事前作成することで各テストケースの認証時間が削減されることを確認
  - _要件: 11.1, 11.2, 11.3, 11.4, 11.5, 11.6, 11.7_

- [x] 12. 環境変数管理を実装し、ローカル・Docker・CI環境での実行を保証する
- [x] 12.1 環境変数設定とエラーハンドリングを実装する
  - ローカル環境のデフォルト環境変数（E2E_ADMIN_URL: http://localhost:3001、E2E_USER_URL: http://localhost:3000、E2E_API_URL: http://localhost:8000）を設定
  - Docker環境の環境変数（E2E_ADMIN_URL: http://admin-app:3000、E2E_USER_URL: http://user-app:3000、E2E_API_URL: http://laravel-api:80）を設定
  - CI環境の環境変数をGitHub Actionsワークフローで定義
  - 認証情報環境変数（E2E_ADMIN_EMAIL、E2E_ADMIN_PASSWORD、E2E_USER_EMAIL、E2E_USER_PASSWORD）を要求
  - .envファイルまたはシェル環境変数からdotenvで読み込む
  - 必須環境変数が未設定の場合、エラーメッセージを表示してテスト実行を中断
  - _要件: 12.1, 12.2, 12.3, 12.4, 12.5, 12.6_

- [ ] 13. ローカル環境とDocker環境でE2Eテストを実行し、動作を検証する
- [ ] 13.1 ローカル環境でテスト実行成功を確認する
  - admin-app、user-app、laravel-apiをローカルで起動
  - 環境変数（認証情報含む）を設定
  - `npm run test`コマンドで全テストが成功することを確認
  - `npm run test:admin`、`npm run test:user`コマンドでプロジェクト別テストが成功することを確認
  - HTMLレポートでテスト結果を確認
  - _要件: 全要件のローカル環境検証_

- [ ] 13.2 Docker環境でテスト実行成功を確認する
  - docker-compose up -d --buildで全サービスを起動
  - e2e-testsサービスが正常実行され、テストが成功することを確認
  - Docker環境の環境変数が正しく適用されることを確認
  - _要件: 全要件のDocker環境検証_

- [ ] 14. ワークフロー動作検証とドキュメント整備を行う
- [ ] 14.1 GitHub Actionsワークフローの手動実行を検証する（オプション）
  - `.github/workflows/e2e-tests.yml.disabled`を`.github/workflows/e2e-tests.yml`にリネーム
  - GitHub Actions UIから手動実行（workflow_dispatch）でワークフローをトリガー
  - 4シャード並列実行が正常動作することを確認
  - アーティファクトにテストレポートがアップロードされることを確認
  - テスト成功時にワークフローが成功ステータスになることを確認
  - 検証完了後、`.github/workflows/e2e-tests.yml.disabled`に戻す（GitHub Actions無料枠対策）
  - _要件: 全要件のCI/CD環境検証（オプション）_

- [ ] 14.2 開発者向けドキュメントを作成する
  - `e2e/README.md`を作成し、以下を記載:
    - セットアップ手順（ローカル/Docker環境）
    - テスト実行方法（npm run test、test:admin、test:user）
    - GitHub Actionsワークフロー有効化手順（リネーム方法）
    - トリガー条件とコスト削減戦略の説明
    - トラブルシューティング
  - プロジェクトルートの`README.md`にE2Eテストセクションを追加
  - `e2e/.env.example`を作成し、環境変数の設定例を提供
  - 新規参加者がREADMEのみでローカル/Docker環境でE2Eテストを実行できることを確認
  - _要件: 全要件の文書化_

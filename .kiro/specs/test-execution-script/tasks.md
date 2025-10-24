# Implementation Tasks

## 概要

本ドキュメントは、テスト実行スクリプト機能の実装タスクを定義します。全16要件を網羅し、3フェーズ（Week 1-3）の段階的実装戦略に従って、自然言語形式でタスクを記述しています。

## タスク一覧

### 1. プロジェクト基盤とディレクトリ構造のセットアップ

プロジェクトの基盤となるディレクトリ構造と共通ライブラリを作成します。これにより、テスト実行スクリプトが統一されたログ出力とレポート保存を実現できます。

**Requirements Mapping**: Requirement 12（共通関数ライブラリ）、Requirement 13（テストレポートディレクトリ構造）

#### 1.1. 共通関数ライブラリの作成

色付きメッセージ出力とログ出力のための共通ライブラリを実装します。`scripts/lib/colors.sh` にANSI色コード定数を定義し、`scripts/lib/logging.sh` にログレベル別出力関数（log_info、log_success、log_warn、log_error、log_debug）を実装します。全てのスクリプトがこれらの関数を使用することで、統一されたログフォーマットを提供します。

**Requirements Mapping**: Requirement 12（共通関数ライブラリ）

#### 1.2. テストレポートディレクトリ構造の作成

テスト結果を整理して保存するためのディレクトリ構造を作成します。`test-results/` ルートディレクトリ配下に、JUnit XMLレポート用の `junit/`、カバレッジレポート用の `coverage/`、統合レポート用の `reports/`、実行ログ用の `logs/` サブディレクトリを作成します。これにより、テスト結果が体系的に整理され、参照が容易になります。

**Requirements Mapping**: Requirement 13（テストレポートディレクトリ構造）

### 2. バックエンドテスト実行スクリプトの実装

Laravel APIのバックエンドテスト（Pest）を実行するための抽象化レイヤーを実装します。DB環境選択、並列実行制御、カバレッジ生成を柔軟に制御できるようにします。

**Requirements Mapping**: Requirement 2（テストスイート選択実行）、Requirement 3（DB環境選択）、Requirement 4（並列実行制御）、Requirement 6（カバレッジレポート生成）、Requirement 7（エラーハンドリング）

#### 2.1. DB環境切り替え機能の実装

SQLite環境とPostgreSQL環境を切り替えてバックエンドテストを実行する機能を実装します。`run_backend_tests()` 関数が第一引数として `sqlite` または `postgres` を受け取り、SQLite環境では既存の `make quick-test` を呼び出し、PostgreSQL環境では既存の `make test-parallel` を呼び出します。これにより、高速開発時はSQLite、本番同等環境テスト時はPostgreSQLを使い分けできます。

**Requirements Mapping**: Requirement 3（DB環境選択）

#### 2.2. 並列実行制御機能の実装

PostgreSQL環境でのテスト並列実行数を制御する機能を実装します。`run_backend_tests()` 関数が第二引数として並列実行数（1-8）を受け取り、`make test-parallel` 実行時に適切な環境変数を設定します。既存の `scripts/parallel-test-setup.sh` スクリプトを呼び出してテスト用DB環境をセットアップします。

**Requirements Mapping**: Requirement 4（並列実行制御）

#### 2.3. カバレッジレポート生成機能の実装

バックエンドテストでコードカバレッジレポートを生成する機能を実装します。`run_backend_tests()` 関数が第三引数としてカバレッジ有効化フラグを受け取り、有効時はPestに `--coverage-html` オプションを追加して `test-results/coverage/backend/` ディレクトリにHTMLレポートを出力します。

**Requirements Mapping**: Requirement 6（カバレッジレポート生成）

#### 2.4. JUnit XMLレポート出力機能の実装

バックエンドテストの結果をJUnit XML形式で出力する機能を実装します。テスト実行完了後、`test-results/junit/backend-test-results.xml` にレポートを保存します。これにより、CI/CDツールとの統合が容易になります。

**Requirements Mapping**: Requirement 5（統合レポート生成）、Requirement 14（テストフレームワーク設定更新）

#### 2.5. エラーハンドリングとログ記録の実装

バックエンドテスト失敗時のエラーハンドリングとログ記録を実装します。`set +e` でエラー時も継続実行し、exit codeを記録、標準出力/標準エラー出力を `test-results/logs/backend.log` にリダイレクトします。これにより、一部テスト失敗時も全テスト実行を継続できます。

**Requirements Mapping**: Requirement 7（エラーハンドリング）

### 3. フロントエンドテスト実行スクリプトの実装

Next.js Admin AppとUser Appのフロントエンドテスト（Jest）を並列実行するための抽象化レイヤーを実装します。

**Requirements Mapping**: Requirement 2（テストスイート選択実行）、Requirement 6（カバレッジレポート生成）、Requirement 7（エラーハンドリング）

#### 3.1. Admin App/User App並列実行機能の実装

Admin AppとUser Appのテストを並列実行する機能を実装します。`run_frontend_tests()` 関数が各アプリのテストをバックグラウンド実行（`&`）し、`wait` で完了を待機します。これにより、フロントエンドテスト全体の実行時間を短縮できます。

**Requirements Mapping**: Requirement 2（テストスイート選択実行）

#### 3.2. カバレッジレポート生成機能の実装

フロントエンドテストでコードカバレッジレポートを生成する機能を実装します。`run_frontend_tests()` 関数が第一引数としてカバレッジ有効化フラグを受け取り、有効時は `npm test -- --coverage` を実行して `test-results/coverage/frontend-admin/` および `test-results/coverage/frontend-user/` ディレクトリにHTMLレポートを出力します。

**Requirements Mapping**: Requirement 6（カバレッジレポート生成）

#### 3.3. JUnit XMLレポート出力機能の実装

フロントエンドテストの結果をJUnit XML形式で出力する機能を実装します。Admin Appのテスト結果を `test-results/junit/frontend-admin-results.xml`、User Appのテスト結果を `test-results/junit/frontend-user-results.xml` に保存します。

**Requirements Mapping**: Requirement 5（統合レポート生成）、Requirement 14（テストフレームワーク設定更新）

#### 3.4. エラーハンドリングとログ記録の実装

フロントエンドテスト失敗時のエラーハンドリングとログ記録を実装します。各アプリのテスト実行時に exit code を記録し、標準出力/標準エラー出力を `test-results/logs/frontend-admin.log` および `test-results/logs/frontend-user.log` にリダイレクトします。

**Requirements Mapping**: Requirement 7（エラーハンドリング）

### 4. E2Eテスト実行スクリプトの実装

Playwright E2Eテストを実行するための抽象化レイヤーを実装します。全サービスのヘルスチェックを実施し、起動完了後にテストを実行します。

**Requirements Mapping**: Requirement 2（テストスイート選択実行）、Requirement 7（エラーハンドリング）、Requirement 10（サービスヘルスチェック）

#### 4.1. サービスヘルスチェック機能の実装

E2Eテスト実行前に全サービス（Laravel API、User App、Admin App）のヘルスチェックを実行する機能を実装します。`check_services_ready()` 関数が各サービスの `/api/health` エンドポイントに対してHTTPリクエストを送信し、最大120秒間リトライします。全サービスが正常に起動するまで待機し、タイムアウト時はエラーを返します。

**Requirements Mapping**: Requirement 10（サービスヘルスチェック）

#### 4.2. Playwrightテスト実行機能の実装

Playwrightテストを実行する機能を実装します。`run_e2e_tests()` 関数がヘルスチェック完了後に `npx playwright test` を実行し、JUnit XMLレポートを `test-results/junit/e2e-test-results.xml` に出力します。並列実行数（Shard数）を引数で制御できるようにします。

**Requirements Mapping**: Requirement 2（テストスイート選択実行）

#### 4.3. エラーハンドリングとログ記録の実装

E2Eテスト失敗時のエラーハンドリングとログ記録を実装します。テスト実行時に exit code を記録し、標準出力/標準エラー出力を `test-results/logs/e2e.log` にリダイレクトします。

**Requirements Mapping**: Requirement 7（エラーハンドリング）

### 5. 統合レポート生成スクリプトの実装

全テストスイートの結果を統合したレポートを生成する機能を実装します。JUnit XMLレポートを解析し、統合サマリーJSON、GitHub Actions Summary Markdownを生成します。

**Requirements Mapping**: Requirement 5（統合レポート生成）、Requirement 9（CI/CD統合）

#### 5.1. JUnit XMLレポート解析機能の実装

JUnit XMLレポートを解析して統合サマリーJSONを生成する機能を実装します。`generate_test_summary_json()` 関数が `test-results/junit/*.xml` を解析し、タイムスタンプ、実行時間、総テスト数、成功数、失敗数、各スイート別結果を含むJSONを `test-results/reports/test-summary.json` に出力します。

**Requirements Mapping**: Requirement 5（統合レポート生成）

#### 5.2. GitHub Actions Summary生成機能の実装

GitHub Actions環境でMarkdown形式の統合サマリーを生成する機能を実装します。`generate_github_summary()` 関数が統合サマリーJSONをMarkdownテーブル形式に変換し、`$GITHUB_STEP_SUMMARY` 環境変数に出力します。これにより、Pull Request画面でテスト結果を視覚的に確認できます。

**Requirements Mapping**: Requirement 5（統合レポート生成）、Requirement 9（CI/CD統合）

#### 5.3. 失敗テスト詳細レポートの実装

失敗したテストの詳細情報を統合サマリーに含める機能を実装します。統合サマリーJSONの `failed_tests` 配列に、スイート名、テスト名、ログファイルパスを記録します。これにより、開発者が失敗原因を迅速に特定できます。

**Requirements Mapping**: Requirement 5（統合レポート生成）

### 6. 診断スクリプトの実装

テスト環境の問題を診断するためのスクリプトを実装します。ポート使用状況、環境変数、Dockerコンテナ状態、データベース接続、ディスク空き容量、メモリ使用状況を確認します。

**Requirements Mapping**: Requirement 11（診断スクリプト）

#### 6.1. ポート競合チェック機能の実装

テスト実行に必要なポート（13000、13001、13002、13432、13379）の使用状況を確認する機能を実装します。`check_ports()` 関数が `lsof` コマンドを使用して各ポートの使用プロセスを表示します。ポート競合がある場合は使用プロセス情報を出力します。

**Requirements Mapping**: Requirement 11（診断スクリプト）

#### 6.2. 環境変数バリデーション機能の実装

必須環境変数の設定状態を確認する機能を実装します。`check_env_vars()` 関数が必須環境変数リストをチェックし、未設定の変数がある場合はエラーメッセージを表示します。

**Requirements Mapping**: Requirement 11（診断スクリプト）

#### 6.3. Docker/DBヘルスチェック機能の実装

Dockerコンテナの起動状態とデータベース接続を確認する機能を実装します。`check_docker()` 関数が `docker ps` でコンテナ状態を確認し、`check_db_connection()` 関数がPostgreSQL接続テストを実行します。

**Requirements Mapping**: Requirement 11（診断スクリプト）

#### 6.4. システムリソース確認機能の実装

ディスク空き容量とメモリ使用状況を確認する機能を実装します。`check_disk_space()` 関数が `df` コマンドで空き容量を確認し、`check_memory()` 関数が `free` または `vm_stat` コマンドでメモリ使用状況を確認します。

**Requirements Mapping**: Requirement 11（診断スクリプト）

### 7. メインオーケストレーションスクリプトの実装

全テストスイートを統合的にオーケストレーションするメインスクリプトを実装します。コマンドライン引数解析、環境バリデーション、並列実行制御、エラーハンドリングを提供します。

**Requirements Mapping**: Requirement 1（統合テストスクリプト実行）、Requirement 2（テストスイート選択実行）、Requirement 3（DB環境選択）、Requirement 4（並列実行制御）、Requirement 7（エラーハンドリング）、Requirement 9（CI/CD統合）

#### 7.1. コマンドライン引数解析機能の実装

コマンドライン引数（`--suite`、`--env`、`--parallel`、`--coverage`、`--report`、`--ci`、`--fast`）を解析する機能を実装します。引数解析ループで各オプションを処理し、対応するシェル変数に値を設定します。`--help` オプションで使用方法を表示します。

**Requirements Mapping**: Requirement 1（統合テストスクリプト実行）、Requirement 2（テストスイート選択実行）、Requirement 3（DB環境選択）、Requirement 4（並列実行制御）

#### 7.2. 環境バリデーション機能の実装

テスト実行前の環境バリデーションを実装します。必須環境変数チェック、ポート競合チェック、並列実行数の範囲チェック（1-8）を実行し、問題がある場合はエラーメッセージを表示して終了します。

**Requirements Mapping**: Requirement 1（統合テストスクリプト実行）

#### 7.3. テストスイート並列実行制御の実装

バックエンドテストとフロントエンドテストを並列実行する制御ロジックを実装します。各テストスイートをバックグラウンド実行（`&`）し、`wait` で完了を待機します。E2Eテストはバックエンド/フロントエンド完了後に順次実行します。

**Requirements Mapping**: Requirement 1（統合テストスクリプト実行）

#### 7.4. エラーハンドリングと終了コード管理の実装

各テストスイートの exit code を記録し、最終的な終了コードを決定するエラーハンドリングロジックを実装します。`set +e` で部分的エラーを許容し、全テスト完了後に失敗したスイートがある場合は exit code 1 を返します。

**Requirements Mapping**: Requirement 7（エラーハンドリング）

#### 7.5. CI/CDモード実装

CI/CD環境向けの特別な挙動を実装します。`--ci` オプション指定時は、テスト完了後にサービスを停止せず維持し、GitHub Actions Summary出力を有効化します。

**Requirements Mapping**: Requirement 9（CI/CD統合）

### 8. テストフレームワーク設定の更新

各テストフレームワーク（Pest、Jest、Playwright）がJUnit XMLレポートを出力するように設定を更新します。

**Requirements Mapping**: Requirement 14（テストフレームワーク設定更新）

#### 8.1. phpunit.xml JUnit出力設定の追加

Pestが使用する `phpunit.xml` にJUnit出力設定を追加します。`<logging>` セクションに `<junit outputFile="../../test-results/junit/backend-test-results.xml"/>` 要素を追加します。既に設定が存在する場合はスキップします。

**Requirements Mapping**: Requirement 14（テストフレームワーク設定更新）

#### 8.2. jest.config.js jest-junitレポータ設定の追加

Admin AppとUser Appの `jest.config.js` にjest-junitレポータ設定を追加します。`reporters` 配列に `['jest-junit', { outputDirectory: '../../test-results/junit', outputName: 'frontend-admin-results.xml' }]` を追加します。既に設定が存在する場合はスキップします。

**Requirements Mapping**: Requirement 14（テストフレームワーク設定更新）

#### 8.3. playwright.config.ts junitレポータ設定の追加

Playwright設定ファイル `e2e/playwright.config.ts` にjunitレポータ設定を追加します。`reporter` 配列に `['junit', { outputFile: '../test-results/junit/e2e-test-results.xml' }]` を追加します。既に設定が存在する場合はスキップします。

**Requirements Mapping**: Requirement 14（テストフレームワーク設定更新）

### 9. Makefileタスクの追加

開発者が統一されたCLIインターフェースでテストを実行できるように、Makefileに新規タスクを追加します。

**Requirements Mapping**: Requirement 8（Makefile統合）

#### 9.1. 基本テスト実行タスクの追加

基本的なテスト実行タスク（`test-all`、`test-all-pgsql`、`test-backend-only`、`test-frontend-only`、`test-e2e-only`）を追加します。各タスクが `./scripts/test/main.sh` を適切な引数で呼び出すように設定します。

**Requirements Mapping**: Requirement 8（Makefile統合）

#### 9.2. カバレッジ/PR向けタスクの追加

カバレッジレポート生成タスク（`test-with-coverage`）とPR前推奨テストタスク（`test-pr`）を追加します。`test-pr` は `make lint` 実行後に全テストをPostgreSQL環境でカバレッジ付きで実行します。

**Requirements Mapping**: Requirement 8（Makefile統合）

#### 9.3. スモークテスト/診断タスクの追加

スモークテストタスク（`test-smoke`）と診断スクリプトタスク（`test-diagnose`）を追加します。これにより、開発者が高速テスト実行と環境診断を簡単に実行できます。

**Requirements Mapping**: Requirement 8（Makefile統合）、Requirement 11（診断スクリプト）

#### 9.4. 既存タスクの保持確認

既存Makefileタスク（`quick-test`、`test-pgsql`、`test-parallel`、`ci-test`）が一切変更されていないことを確認します。新規タスクセクションを別途追加し、既存タスクとの競合を避けます。

**Requirements Mapping**: Requirement 8（Makefile統合）

### 10. スクリプト実行権限の付与

全てのテスト実行スクリプトに実行権限を付与します。開発者がMakefileを経由せず直接スクリプトを実行できるようにします。

**Requirements Mapping**: Requirement 16（スクリプト実行権限）

#### 10.1. テスト実行スクリプト実行権限の付与

`scripts/test/` 配下の全スクリプト（`main.sh`、`test-backend.sh`、`test-frontend.sh`、`test-e2e.sh`、`test-report.sh`、`diagnose.sh`）に実行権限（`chmod +x`）を付与します。

**Requirements Mapping**: Requirement 16（スクリプト実行権限）

#### 10.2. 共通ライブラリスクリプト実行権限の付与

`scripts/lib/` 配下の共通ライブラリスクリプト（`colors.sh`、`logging.sh`）に実行権限を付与します。これにより、スクリプトが `source` コマンドで正常に読み込めます。

**Requirements Mapping**: Requirement 16（スクリプト実行権限）

### 11. GitHub Actionsワークフローの作成

CI/CD環境で統合テストを自動実行するためのGitHub Actionsワークフローを作成します。

**Requirements Mapping**: Requirement 9（CI/CD統合）

#### 11.1. test-integration.ymlワークフロー基本設定の作成

`.github/workflows/test-integration.yml` ファイルを作成し、基本的なワークフロー設定（トリガー、Node.js/PHPバージョンマトリクス、チェックアウト）を実装します。Pull Request作成時とmainブランチへのpush時に自動実行されるように設定します。

**Requirements Mapping**: Requirement 9（CI/CD統合）

#### 11.2. paths-filter統合の実装

`dorny/paths-filter@v3` アクションを使用して変更ファイルを検出する機能を実装します。バックエンドファイル、フロントエンドファイル、E2Eファイルの変更を検出し、必要なテストスイートのみを実行するように条件分岐を設定します。

**Requirements Mapping**: Requirement 9（CI/CD統合）

#### 11.3. テスト実行とArtifactsアップロードの実装

GitHub Actionsワークフロー内でテスト実行コマンド（`make test-all-pgsql --ci`）を実行し、テスト完了後に `actions/upload-artifact@v4` アクションで `test-results/` ディレクトリをアップロードします。

**Requirements Mapping**: Requirement 9（CI/CD統合）

#### 11.4. GitHub Actions Summary出力の実装

`$GITHUB_STEP_SUMMARY` 環境変数を使用してMarkdown形式の統合サマリーを出力する機能をワークフローに統合します。これにより、Pull Request画面でテスト結果が視覚的に表示されます。

**Requirements Mapping**: Requirement 9（CI/CD統合）

### 12. テスト実行ガイドドキュメントの作成

開発者がテスト実行スクリプトをスムーズに使用できるように、包括的なドキュメントを作成します。

**Requirements Mapping**: Requirement 15（ドキュメント整備）

#### 12.1. TESTING_EXECUTION_GUIDE.md作成

`docs/TESTING_EXECUTION_GUIDE.md` にテスト実行ガイドを作成します。クイックスタート、ローカルテスト実行、CI/CD実行、テストスイート別実行方法、レポート確認方法、Makefileタスク一覧を含めます。コード例とスクリーンショットを追加して理解を促進します。

**Requirements Mapping**: Requirement 15（ドキュメント整備）

#### 12.2. TESTING_TROUBLESHOOTING_EXTENDED.md作成

`docs/TESTING_TROUBLESHOOTING_EXTENDED.md` にトラブルシューティングガイドを作成します。よくある問題（ポート競合、DB接続エラー、メモリ不足、並列実行失敗）と解決策、診断スクリプト使用方法、ログ分析方法、エスカレーション手順を含めます。

**Requirements Mapping**: Requirement 15（ドキュメント整備）

#### 12.3. README.md更新

プロジェクトルートの `README.md` に新規コマンド使用方法セクションを追加します。`make test-all`、`make test-all-pgsql`、`make test-pr` などの主要コマンドの使用例を記載し、詳細ドキュメント（`docs/TESTING_EXECUTION_GUIDE.md`）へのリンクを追加します。

**Requirements Mapping**: Requirement 15（ドキュメント整備）

#### 12.4. CLAUDE.md更新

`.kiro/steering/` 配下のプロジェクトコンテキストファイル `CLAUDE.md` のActive Specificationsリストに、本仕様（`test-execution-script`）を追加します。これにより、AI開発支援ツールが本仕様を認識できます。

**Requirements Mapping**: Requirement 15（ドキュメント整備）

## 実装順序と依存関係

タスクは以下の順序で実装することを推奨します。

**Phase 1: 基盤構築（Week 1）**
- タスク1: プロジェクト基盤とディレクトリ構造のセットアップ
- タスク2: バックエンドテスト実行スクリプトの実装
- タスク3: フロントエンドテスト実行スクリプトの実装
- タスク4: E2Eテスト実行スクリプトの実装
- タスク5: 統合レポート生成スクリプトの実装
- タスク6: 診断スクリプトの実装
- タスク7: メインオーケストレーションスクリプトの実装
- タスク10: スクリプト実行権限の付与

**Phase 2: 統合とテスト（Week 2）**
- タスク8: テストフレームワーク設定の更新
- タスク9: Makefileタスクの追加
- ローカル環境での統合テスト実行
- レポート生成確認

**Phase 3: CI/CD統合とドキュメント（Week 3）**
- タスク11: GitHub Actionsワークフローの作成
- タスク12: テスト実行ガイドドキュメントの作成
- Pull Request作成とCI/CD動作確認

## 完了基準

全タスク完了時、以下の基準を満たすこと:

- [ ] 開発者が `make test-all` で全テストを2分以内に実行完了（SQLite環境）
- [ ] PostgreSQL環境での並列実行が正常動作
- [ ] 統合レポートが正しく生成され、GitHub Actions Summaryに表示される
- [ ] 既存Makefileタスクが変更なく維持され、後方互換性が保証される
- [ ] ドキュメントが整備され、開発者がスムーズにテスト実行を開始できる
- [ ] Pull Request作成時にGitHub Actionsワークフローが自動実行される
- [ ] 全16要件の受入基準が満たされる

# Implementation Plan

## Phase 1: 設定ファイル準備と基盤整備 ✅

- [x] 1. テスト専用データベース接続設定の追加
- [x] 1.1 PostgreSQLテスト専用接続設定の実装
  - `config/database.php`の`connections`配列に`pgsql_testing`接続を追加
  - 環境変数`DB_TEST_*`を使用し、未設定時は`DB_*`にフォールバック
  - デフォルトデータベース名を`app_test`に設定
  - PostgreSQL接続最適化設定（`connect_timeout`、`sslmode`、`statement_timeout`等）を継承
  - _Requirements: 1.1, 1.3, 1.4, 1.5, 1.7_

- [x] 1.2 並列テスト用管理系接続設定の実装
  - `config/database.php`の`connections`配列に`pgsql_system`接続を追加
  - 管理系データベース（`postgres`）に接続する設定
  - DB作成・削除操作用の権限設定を含める
  - _Requirements: 1.2, 1.6_

- [x] 2. 環境別テスト設定ファイルの作成
- [x] 2.1 SQLiteテスト環境設定ファイルの作成
  - `.env.testing.sqlite`ファイルを`backend/laravel-api/`に作成
  - `DB_CONNECTION=sqlite`、`DB_DATABASE=:memory:`を設定
  - 共通テスト設定（`CACHE_STORE=array`、`QUEUE_CONNECTION=sync`、`MAIL_MAILER=array`）を含める
  - _Requirements: 2.1, 2.3, 2.5, 2.8_

- [x] 2.2 PostgreSQLテスト環境設定ファイルの作成
  - `.env.testing.pgsql`ファイルを`backend/laravel-api/`に作成
  - `DB_CONNECTION=pgsql_testing`を設定
  - Docker環境用のホスト設定（`DB_TEST_HOST=pgsql`）とポート（`DB_TEST_PORT=13432`）を設定
  - テスト用DB接続情報（`DB_TEST_DATABASE=app_test`、認証情報）を設定
  - 共通テスト設定を含める
  - _Requirements: 2.2, 2.4, 2.5, 2.6, 2.7, 2.8_

- [x] 2.3 環境設定テンプレートファイルの作成
  - `.env.testing.sqlite.example`と`.env.testing.pgsql.example`をテンプレートとして作成
  - パスワード等の機密情報は空白にして提供
  - `.gitignore`に`.env.testing.sqlite`と`.env.testing.pgsql`を追加（テンプレートは除外）
  - _Requirements: 10.4_

- [x] 3. 既存設定との互換性確認
- [x] 3.1 phpunit.xml設定の検証
  - `phpunit.xml`のデフォルト設定（`DB_CONNECTION=sqlite`、`DB_DATABASE=:memory:`）が維持されていることを確認
  - 既存のSQLite in-memory設定に影響がないことを検証
  - _Requirements: 9.1, 9.2_

- [x] 3.2 既存PostgreSQL接続設定の保護
  - 既存の`pgsql`接続設定が変更されていないことを確認
  - テスト用接続設定が本番/開発接続に影響しないことを検証
  - _Requirements: 9.4_

## Phase 2: 自動化スクリプトの改善 ✅

- [x] 4. テスト環境切り替えスクリプトの改善
- [x] 4.1 環境切り替え機能の強化
  - 既存の`scripts/switch-test-env.sh`に`php artisan config:clear`実行を追加
  - 引数チェック機能の強化（`sqlite`/`pgsql`のみ受け付け、デフォルトは`sqlite`）
  - 設定ファイル存在確認とエラーハンドリング強化
  - 成功メッセージの詳細化（接続情報表示を含む）
  - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 3.6_

- [x] 4.2 スクリプト実行権限の設定
  - `chmod +x scripts/switch-test-env.sh`で実行権限を付与
  - スクリプト配置場所が`scripts/switch-test-env.sh`であることを確認
  - _Requirements: 3.7, 3.8_

- [x] 5. 並列テスト用データベース管理スクリプトの改善
- [x] 5.1 並列テスト環境セットアップスクリプトの改善
  - 既存の`scripts/parallel-test-setup.sh`のDB接続設定を`pgsql_testing`に変更
  - 環境変数を`DB_TEST_*`に統一
  - PostgreSQLコンテナ起動確認機能を追加（未起動時はインタラクティブプロンプト）
  - 並列数分のDB作成ループ実装（既存DBを削除後に新規作成）
  - 各DB作成時の成功メッセージ表示
  - Laravel Sail環境での実行を保証
  - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.7, 4.8_

- [x] 5.2 並列テスト環境クリーンアップスクリプトの改善
  - 既存の`scripts/parallel-test-cleanup.sh`のDB命名規則を統一
  - 並列数分のDB削除ループ実装
  - 各DB削除時の確認メッセージ表示
  - PostgreSQLコンテナ未起動時のエラーハンドリング追加
  - _Requirements: 4.5, 4.6, 4.7_

- [x] 5.3 並列テストスクリプト実行権限の設定
  - `chmod +x scripts/parallel-test-setup.sh`で実行権限を付与
  - `chmod +x scripts/parallel-test-cleanup.sh`で実行権限を付与
  - _Requirements: 4.9_

## Phase 3: Makefile統合による運用標準化 ✅

- [x] 6. Makefileターゲットの追加・改善
- [x] 6.1 高速テストターゲットの実装
  - `quick-test`ターゲット: SQLite環境でPestテストを実行
  - 適切なワーキングディレクトリ（`backend/laravel-api`）で実行
  - _Requirements: 5.1, 5.11_

- [x] 6.2 PostgreSQLテストターゲットの実装
  - `test-pgsql`ターゲット: Docker起動確認 → PostgreSQL環境切り替え → Pestテスト実行
  - Docker環境チェック機能を統合（`docker compose ps pgsql | grep -q "Up"`）
  - _Requirements: 5.2, 5.11_

- [x] 6.3 並列テストターゲットの実装
  - `test-parallel`ターゲット: 並列環境セットアップ → Pest並列実行 → クリーンアップ
  - 3つのステップを順次実行する統合フロー
  - _Requirements: 5.3, 5.11_

- [x] 6.4 環境切り替えターゲットの実装
  - `test-switch-sqlite`ターゲット: SQLite環境に切り替え
  - `test-switch-pgsql`ターゲット: PostgreSQL環境に切り替え
  - `switch-test-env.sh`スクリプトを呼び出し
  - _Requirements: 5.4, 5.5_

- [x] 6.5 並列テスト環境管理ターゲットの実装
  - `test-setup`ターゲット: 並列テスト環境セットアップスクリプト実行
  - `test-cleanup`ターゲット: 並列テスト環境クリーンアップスクリプト実行
  - _Requirements: 5.6, 5.7_

- [x] 6.6 カバレッジ・CI統合ターゲットの実装
  - `test-coverage`ターゲット: テストカバレッジレポート生成
  - `ci-test`ターゲット: CI/CD相当の完全テスト（PostgreSQL切り替え → 並列実行 → カバレッジ生成）
  - _Requirements: 5.8, 5.9_

- [x] 6.7 Makefileプロジェクトルート配置の確認
  - Makefileが`laravel-next-b2c/`（プロジェクトルート）に配置されていることを確認
  - 各ターゲットが適切なワーキングディレクトリで実行されることを検証
  - _Requirements: 5.10, 5.11_

## Phase 4: Docker環境でのテスト用DB提供 ✅

- [x] 7. Docker環境でのテストデータベース準備
- [x] 7.1 PostgreSQLコンテナでのテスト用DB作成
  - Laravel Sail環境でPostgreSQLコンテナが起動していることを確認
  - `./vendor/bin/sail psql`コマンドでテスト用DB `app_test`を作成
  - DB所有者を`sail`ユーザーに設定
  - _Requirements: 6.1, 6.2, 6.3_

- [x] 7.2 テスト用DB存在確認機能の実装
  - PostgreSQL `\l`コマンドで`app_test`が表示されることを確認
  - DB存在確認スクリプトまたはMakefileターゲットの追加（オプション）
  - _Requirements: 6.4_

- [x] 7.3 Docker環境エラーハンドリングの実装
  - PostgreSQLコンテナ未起動時の明確なエラーメッセージ表示
  - ポート`13432`でホストに公開されていることを確認
  - _Requirements: 6.5, 6.6_

## Phase 5: CI/CD並列PostgreSQLテスト実行 ✅

- [x] 8. GitHub Actions並列PostgreSQLテストジョブの追加
- [x] 8.1 PostgreSQL Serviceコンテナの設定
  - `.github/workflows/test.yml`にPostgreSQL 17 Serviceコンテナを追加
  - ヘルスチェック機能（`pg_isready -U sail`）を設定
  - ポート`13432:5432`のマッピング設定
  - _Requirements: 7.3, 7.4_

- [x] 8.2 4並列Matrixジョブの実装
  - `strategy.matrix.shard: [1, 2, 3, 4]`で4並列実行設定
  - 各Shardで独立したテスト用DB（`testing_1`〜`testing_4`）を作成
  - DB作成ステップ: `PGPASSWORD=password psql -h 127.0.0.1 -U sail -d postgres -c 'CREATE DATABASE testing_${{ matrix.shard }};'`
  - _Requirements: 7.1, 7.2, 7.5_

- [x] 8.3 並列テスト実行環境変数の設定
  - 環境変数`DB_CONNECTION=pgsql_testing`を設定
  - 環境変数`DB_TEST_DATABASE=testing_${{ matrix.shard }}`を設定
  - その他のPostgreSQL接続環境変数（`DB_TEST_HOST`、`DB_TEST_PORT`、認証情報）を設定
  - _Requirements: 7.6, 7.7_

- [x] 8.4 Pest Shardテスト実行の実装
  - `./vendor/bin/pest --shard=${{ matrix.shard }}/4`コマンドで各Shard実行
  - マイグレーション実行ステップの追加（各Shard用DB）
  - _Requirements: 7.8_

- [x] 8.5 ワークフロートリガー設定の最適化
  - `paths`フィルターでバックエンドファイル変更時のみ実行（`backend/laravel-api/**`）
  - Pull Request作成時の自動実行設定
  - _Requirements: 7.9, 7.1_

- [x] 8.6 ワークフロー配置とファイル名の確認
  - ワークフローファイルが`.github/workflows/test.yml`に配置されていることを確認
  - 既存のテストジョブと共存できることを検証
  - _Requirements: 7.10_

## Phase 6: テスト実行ドキュメント整備 ✅

- [x] 9. テストDB運用ワークフローガイドの作成
- [x] 9.1 テストDB運用ドキュメントの作成
  - `docs/TESTING_DATABASE_WORKFLOW.md`ファイルを作成
  - テスト用DB設定の全体像説明セクションを追加
  - _Requirements: 8.1, 8.2_

- [x] 9.2 ローカル開発環境実行手順の記載
  - SQLite vs PostgreSQL環境の実行手順を説明
  - Docker/Laravel Sail環境のセットアップ手順を説明
  - 環境切り替え方法（`make test-switch-sqlite`、`make test-switch-pgsql`）を記載
  - _Requirements: 8.3, 8.4_

- [x] 9.3 CI/CD環境設定の説明
  - GitHub Actions環境の設定を説明
  - 並列PostgreSQLテスト実行の仕組みを説明
  - _Requirements: 8.5_

- [x] 9.4 トラブルシューティングガイドの追加
  - PostgreSQL接続エラーのトラブルシューティング
  - マイグレーション失敗時の対処法
  - 並列テスト競合時の解決方法
  - _Requirements: 8.6_

- [x] 9.5 推奨運用フローの記載
  - 日常開発: `make quick-test`（SQLite高速テスト）
  - 機能完成時: `make test-pgsql`（PostgreSQL本番同等テスト）
  - PR前: `make ci-test`（CI/CD相当の完全テスト）
  - 各フローの使い分けを説明
  - _Requirements: 8.7_

- [x] 9.6 README.mdテスト実行セクションの更新
  - README.mdにテスト実行セクションを追加または更新
  - Makefileターゲット使用方法を記載
  - 環境切り替え方法を簡潔に説明
  - _Requirements: 8.8, 8.9_

- [x] 9.7 ドキュメントの日本語記述確認
  - 全ドキュメントが日本語で記述されていることを確認
  - 技術用語と説明のバランスを調整
  - _Requirements: 8.10_

## Phase 7: 品質基準とセキュリティ対応 ✅

- [x] 10. コード品質管理とセキュリティ対応
- [x] 10.1 Laravel Pint・Larastanコード品質チェック
  - 全PHPファイル（設定ファイル、スクリプト）がLaravel Pint規約に準拠していることを確認
  - Larastan Level 8静的解析を通過することを確認
  - _Requirements: 10.1, 10.2_

- [x] 10.2 機密情報管理の実装
  - 設定ファイル内の機密情報（パスワード等）が環境変数経由で管理されていることを確認
  - `.env.testing.*`ファイルが`.gitignore`に追加されていることを確認
  - `.env.testing.*.example`テンプレートが提供されていることを確認
  - _Requirements: 10.3, 10.4_

- [x] 10.3 テストDB命名規則によるDB分離の確認
  - テスト用DB名が本番/開発DBと明確に区別されていることを確認（`app_test`、`testing_*`プレフィックス）
  - 本番/開発DBへの誤接続リスクがないことを検証
  - _Requirements: 10.5_

- [x] 10.4 エラーハンドリングの実装
  - 全スクリプトが適切なエラーメッセージを提供することを確認
  - ユーザーエラー、システムエラー、ビジネスロジックエラーの3カテゴリで分類
  - _Requirements: 10.6_

- [x] 10.5 セキュリティベストプラクティスドキュメント化
  - ドキュメント内にセキュリティベストプラクティス（本番DBとの分離、認証情報管理）を説明
  - 機密情報取り扱いのガイドラインを記載
  - _Requirements: 10.7_

## Phase 8: 統合テストと検証 ✅

- [x] 11. 統合テストと動作検証
- [x] 11.1 SQLite環境テストの実行検証
  - `make quick-test`または`make test-sqlite`でSQLite環境テストが成功することを確認
  - 既存Pestテストが全て成功することを検証（52 passed, 6 skipped）
  - `.env.testing.sqlite`設定が正しく適用されていることを確認
  - _Requirements: 9.2, 9.3, 10.8_

- [x] 11.2 PostgreSQL環境テストの実行検証
  - `make test-pgsql`でPostgreSQL環境テストが成功することを確認
  - 既存Pestテストが全て成功することを検証
  - `.env.testing.pgsql`設定が正しく適用されていることを確認
  - _Requirements: 9.2, 9.3, 10.8_

- [x] 11.3 環境切り替え動作の検証
  - SQLite → PostgreSQL切り替えが正常動作することを確認
  - PostgreSQL → SQLite切り替えが正常動作することを確認
  - `.env.testing`が正しく上書きされることを確認
  - `php artisan config:clear`が実行されることを確認
  - _Requirements: 9.2, 9.3_

- [x] 11.4 並列テスト実行の検証
  - `make test-parallel`で並列テストが成功することを確認
  - 4つのDB（`testing_1`〜`testing_4`）が作成されることを確認
  - 各DBにマイグレーションが適用されることを確認
  - クリーンアップ後、テストDBが削除されることを確認
  - _Requirements: 9.2, 9.3_

- [x] 11.5 既存テスト・ヘルパーの互換性検証
  - 既存のテストヘルパー、ファクトリー、シーダーが変更なしで動作することを確認
  - 既存テストコードに影響がないことを検証（52 passed確認済み）
  - _Requirements: 9.5_

- [x] 11.6 CI/CD並列PostgreSQLテストの検証
  - GitHub Actions並列実行が成功することを確認（Pull Request作成時）
  - 4 Shard並列実行が正常動作することを確認
  - PostgreSQL Service起動が成功することを確認
  - 全Shard成功時、ワークフロー成功することを確認
  - _Requirements: 7.1〜7.10, 10.8_

- [x] 11.7 Docker環境統合テストの実施
  - `docker compose up -d pgsql`で起動成功することを確認
  - `app_test`データベース作成成功を確認
  - PostgreSQL接続成功を確認（`psql -U sail -l | grep app_test`）
  - _Requirements: 6.1〜6.6_

- [x] 11.8 最終統合テストの実施
  - `make ci-test`でCI/CD相当の完全テストが成功することを確認
  - PostgreSQL環境切り替え → 並列実行 → カバレッジ生成の全ステップ成功を確認
  - SQLite環境とPostgreSQL環境の両方で全テストが成功することを確認
  - _Requirements: 10.8_

# Requirements Document

## はじめに

本要件は、Laravel 12 + Pest 4のテスト環境において、SQLite in-memoryとPostgreSQL 17の両方を使用した柔軟なテストデータベース環境を提供することを目的としています。現在はSQLite in-memoryをデフォルトとして使用していますが、本番環境はPostgreSQL 17であるため、SQL方言の差異やデータ型（jsonb等）、PostgreSQL固有機能に関する問題を早期発見できないリスクが存在します。

本機能により、日常開発では高速なSQLiteテストを実行し、CI/CDや本番環境互換性検証ではPostgreSQLテストを実行することで、開発効率と品質保証の両立を実現します。

### ビジネス価値
- **開発速度向上**: SQLite in-memoryによる高速フィードバックループの提供
- **品質保証強化**: PostgreSQL本番同等環境での互換性問題の早期発見
- **運用効率化**: 環境切り替えとテスト実行の自動化による開発者負担の軽減
- **CI/CD最適化**: 並列PostgreSQLテスト実行による実行時間の短縮

---

## Requirements

### Requirement 1: テスト専用データベース接続設定
**Objective:** テストエンジニアとして、本番/開発DBと完全に分離されたテスト専用データベース接続を使用したい。これにより、テスト実行時のデータ破損リスクを排除し、安全なテスト環境を確保できる。

#### Acceptance Criteria

1. WHEN 開発者が`config/database.php`を読み込むとき THEN Laravel設定システム SHALL テスト専用PostgreSQL接続設定（`pgsql_testing`）を提供する
2. WHEN 開発者が`config/database.php`を読み込むとき THEN Laravel設定システム SHALL 並列テスト用管理系接続設定（`pgsql_system`）を提供する
3. IF テスト専用PostgreSQL接続設定が存在するとき THEN 接続設定 SHALL 環境変数`DB_TEST_HOST`、`DB_TEST_PORT`、`DB_TEST_DATABASE`、`DB_TEST_USERNAME`、`DB_TEST_PASSWORD`を使用する
4. IF 環境変数`DB_TEST_*`が未設定のとき THEN 接続設定 SHALL 通常の`DB_*`環境変数にフォールバックする
5. WHEN テスト専用データベース名が指定されるとき THEN デフォルト値 SHALL `app_test`とする
6. WHEN 管理系接続が使用されるとき THEN 接続先データベース SHALL `postgres`（PostgreSQL管理DB）とする
7. IF テスト用接続設定が読み込まれるとき THEN 設定 SHALL PostgreSQL接続最適化設定（`connect_timeout`、`sslmode`等）を継承する

---

### Requirement 2: 環境別テスト設定ファイル
**Objective:** 開発者として、SQLiteとPostgreSQLのテスト環境を明確に分離した設定ファイルを使用したい。これにより、環境切り替え時の設定ミスを防止し、一貫したテスト実行を保証できる。

#### Acceptance Criteria

1. WHEN 開発者がSQLiteテスト環境を使用するとき THEN テスト環境 SHALL `.env.testing.sqlite`ファイルを提供する
2. WHEN 開発者がPostgreSQLテスト環境を使用するとき THEN テスト環境 SHALL `.env.testing.pgsql`ファイルを提供する
3. IF `.env.testing.sqlite`が読み込まれるとき THEN 設定 SHALL `DB_CONNECTION=sqlite`および`DB_DATABASE=:memory:`を含む
4. IF `.env.testing.pgsql`が読み込まれるとき THEN 設定 SHALL `DB_CONNECTION=pgsql_testing`およびテスト用DB接続情報を含む
5. WHEN 両環境設定ファイルが読み込まれるとき THEN 共通設定 SHALL `CACHE_STORE=array`、`QUEUE_CONNECTION=sync`、`MAIL_MAILER=array`を含む
6. IF PostgreSQL環境設定が読み込まれるとき THEN ホスト設定 SHALL `pgsql`（Docker環境）をデフォルトとする
7. IF PostgreSQL環境設定が読み込まれるとき THEN ポート設定 SHALL `13432`（統一ポート）をデフォルトとする
8. WHEN 環境設定ファイルが作成されるとき THEN ファイル SHALL `backend/laravel-api/`ディレクトリに配置される

---

### Requirement 3: テスト環境切り替え自動化
**Objective:** 開発者として、ワンコマンドでSQLiteとPostgreSQLのテスト環境を切り替えたい。これにより、手動設定変更のミスを防止し、環境切り替えの時間を短縮できる。

#### Acceptance Criteria

1. WHEN 開発者が`./scripts/switch-test-env.sh sqlite`を実行するとき THEN スクリプト SHALL `.env.testing.sqlite`を`.env.testing`にコピーする
2. WHEN 開発者が`./scripts/switch-test-env.sh pgsql`を実行するとき THEN スクリプト SHALL `.env.testing.pgsql`を`.env.testing`にコピーする
3. IF 指定された環境タイプファイルが存在しないとき THEN スクリプト SHALL エラーメッセージを表示して終了する
4. WHEN 環境切り替えが成功したとき THEN スクリプト SHALL 成功メッセージ「✅ Switched to [環境タイプ] test environment」を表示する
5. WHEN 環境切り替えが完了したとき THEN スクリプト SHALL `php artisan config:clear`を実行してキャッシュをクリアする
6. IF 環境タイプ引数が省略されたとき THEN スクリプト SHALL デフォルトで`sqlite`環境に切り替える
7. WHEN スクリプトが作成されるとき THEN ファイル SHALL 実行権限（`chmod +x`）を持つ
8. WHEN スクリプトが配置されるとき THEN ファイル SHALL `scripts/switch-test-env.sh`に配置される

---

### Requirement 4: 並列テスト用データベース管理
**Objective:** テストエンジニアとして、Pest並列実行時に独立したデータベースを自動作成・削除したい。これにより、並列テスト間のデータ競合を防止し、テスト実行時間を短縮できる。

#### Acceptance Criteria

1. WHEN 開発者が`./scripts/parallel-test-setup.sh 4`を実行するとき THEN スクリプト SHALL 4つの並列テスト用DB（`app_test_1`、`app_test_2`、`app_test_3`、`app_test_4`）を作成する
2. IF 並列数引数が省略されたとき THEN スクリプト SHALL デフォルトで4つのDBを作成する
3. WHEN 並列テスト用DBを作成する前に THEN スクリプト SHALL 既存の同名DBを削除する（`DROP DATABASE IF EXISTS`）
4. WHEN 各DBが作成されたとき THEN スクリプト SHALL 成功メッセージ「✅ Created [DB名]」を表示する
5. WHEN 開発者が`./scripts/parallel-test-cleanup.sh 4`を実行するとき THEN スクリプト SHALL 4つの並列テスト用DBを削除する
6. WHEN 各DBが削除されたとき THEN スクリプト SHALL 確認メッセージ「🗑 Deleted [DB名]」を表示する
7. IF PostgreSQLコンテナが起動していないとき THEN スクリプト SHALL エラーメッセージを表示する
8. WHEN スクリプトが実行されるとき THEN DB操作 SHALL Laravel Sail環境（`./vendor/bin/sail psql`）を使用する
9. WHEN スクリプトが作成されるとき THEN ファイル SHALL 実行権限を持つ

---

### Requirement 5: Makefile統合による運用標準化
**Objective:** 開発者として、複雑なテストコマンドをシンプルなMakefileターゲットで実行したい。これにより、チーム全体で統一されたテスト実行フローを確立し、運用ミスを防止できる。

#### Acceptance Criteria

1. WHEN 開発者が`make quick-test`を実行するとき THEN Makefile SHALL SQLite環境でPestテストを実行する
2. WHEN 開発者が`make test-pgsql`を実行するとき THEN Makefile SHALL PostgreSQL環境に切り替えてPestテストを実行する
3. WHEN 開発者が`make test-parallel`を実行するとき THEN Makefile SHALL 並列テスト環境セットアップ、Pest並列実行、環境クリーンアップを順次実行する
4. WHEN 開発者が`make test-switch-sqlite`を実行するとき THEN Makefile SHALL SQLite環境に切り替える
5. WHEN 開発者が`make test-switch-pgsql`を実行するとき THEN Makefile SHALL PostgreSQL環境に切り替える
6. WHEN 開発者が`make test-setup`を実行するとき THEN Makefile SHALL 並列テスト環境セットアップスクリプトを実行する
7. WHEN 開発者が`make test-cleanup`を実行するとき THEN Makefile SHALL 並列テスト環境クリーンアップスクリプトを実行する
8. WHEN 開発者が`make test-coverage`を実行するとき THEN Makefile SHALL テストカバレッジレポートを生成する
9. WHEN 開発者が`make ci-test`を実行するとき THEN Makefile SHALL CI/CD相当の完全テスト（PostgreSQL環境切り替え、並列実行、カバレッジ生成）を実行する
10. WHEN Makefileが配置されるとき THEN ファイル SHALL プロジェクトルート（`laravel-next-b2c/`）に配置される
11. IF Makefileターゲットが実行されるとき THEN 各コマンド SHALL 適切なワーキングディレクトリ（`backend/laravel-api`等）で実行される

---

### Requirement 6: Docker環境でのテスト用DB提供
**Objective:** 開発者として、Laravel Sail環境でテスト専用PostgreSQLデータベースを使用したい。これにより、本番/開発DBと分離された安全なテスト環境を確保できる。

#### Acceptance Criteria

1. WHEN Laravel Sail環境が起動したとき THEN PostgreSQLコンテナ SHALL テスト用DB `app_test`を提供する
2. IF テスト用DBが存在しないとき THEN 開発者 SHALL `./vendor/bin/sail psql`コマンドでDB作成を実行できる
3. WHEN テスト用DBが作成されるとき THEN DB所有者 SHALL `sail`ユーザーとする
4. WHEN 開発者がDB存在確認を実行するとき THEN PostgreSQL SHALL `\l`コマンドで`app_test`を表示する
5. IF PostgreSQLコンテナが起動していないとき THEN テスト実行 SHALL 明確なエラーメッセージを表示する
6. WHEN PostgreSQLコンテナが起動するとき THEN ポート SHALL `13432`（統一ポート）でホストに公開される

---

### Requirement 7: CI/CD並列PostgreSQLテスト実行
**Objective:** CI/CDエンジニアとして、GitHub Actionsで並列PostgreSQLテストを自動実行したい。これにより、本番環境互換性を保証し、Pull Request時の品質検証を強化できる。

#### Acceptance Criteria

1. WHEN Pull Requestが作成されるとき THEN GitHub Actions SHALL PostgreSQLテストジョブを実行する
2. WHEN PostgreSQLテストジョブが実行されるとき THEN ワークフロー SHALL 4並列マトリクス戦略を使用する
3. WHEN ワークフローが開始されるとき THEN GitHub Actions SHALL PostgreSQL 17 Serviceコンテナを起動する
4. IF PostgreSQL Serviceが起動したとき THEN ヘルスチェック SHALL `pg_isready -U sail`を使用する
5. WHEN 各並列ジョブが実行されるとき THEN ワークフロー SHALL 独立したテスト用DB（`app_test_1`、`app_test_2`等）を作成する
6. WHEN テストが実行されるとき THEN 環境変数 SHALL `DB_CONNECTION=pgsql_testing`を設定する
7. WHEN テストが実行されるとき THEN 環境変数 SHALL `DB_TEST_DATABASE=app_test_[並列番号]`を設定する
8. WHEN Pestテストが実行されるとき THEN コマンド SHALL `--shard=[並列番号]/4`オプションを使用する
9. IF バックエンドファイルが変更されたとき THEN ワークフロー SHALL PostgreSQLテストを実行する
10. WHEN ワークフローが更新されるとき THEN ファイル SHALL `.github/workflows/test.yml`に配置される

---

### Requirement 8: テスト実行ドキュメント整備
**Objective:** 開発者として、テストDB運用ワークフローの包括的なドキュメントを参照したい。これにより、環境セットアップ、トラブルシューティング、推奨フローを迅速に理解できる。

#### Acceptance Criteria

1. WHEN 開発者がテストDB運用ドキュメントを参照するとき THEN ドキュメント SHALL `docs/TESTING_DATABASE_WORKFLOW.md`に配置される
2. WHEN ドキュメントが提供されるとき THEN 内容 SHALL テスト用DB設定の全体像を説明する
3. WHEN ドキュメントが提供されるとき THEN 内容 SHALL ローカル開発環境（SQLite vs PostgreSQL）の実行手順を説明する
4. WHEN ドキュメントが提供されるとき THEN 内容 SHALL Docker/Laravel Sail環境のセットアップ手順を説明する
5. WHEN ドキュメントが提供されるとき THEN 内容 SHALL CI/CD（GitHub Actions）環境の設定を説明する
6. WHEN ドキュメントが提供されるとき THEN 内容 SHALL トラブルシューティングガイド（PostgreSQL接続エラー、マイグレーション失敗、並列テスト競合）を提供する
7. WHEN ドキュメントが提供されるとき THEN 内容 SHALL 推奨運用フロー（日常開発: `make quick-test`、機能完成時: `make test-pgsql`、PR前: `make ci-test`）を説明する
8. WHEN README.mdが更新されるとき THEN テスト実行セクション SHALL Makefileターゲット使用方法を記載する
9. WHEN README.mdが更新されるとき THEN テスト実行セクション SHALL 環境切り替え方法を記載する
10. WHEN ドキュメントが作成されるとき THEN ファイル SHALL 日本語で記述される

---

### Requirement 9: 既存設定との互換性維持
**Objective:** 開発者として、既存のテスト設定とテストコードの動作を維持したい。これにより、新機能導入時の既存テスト破壊を防止し、段階的移行を可能にする。

#### Acceptance Criteria

1. WHEN `phpunit.xml`が読み込まれるとき THEN デフォルト設定 SHALL `DB_CONNECTION=sqlite`および`DB_DATABASE=:memory:`を維持する
2. IF 開発者が環境切り替えを実行しないとき THEN テスト SHALL 既存のSQLite in-memory環境で実行される
3. WHEN 既存テストが実行されるとき THEN テスト SHALL 設定変更なしで正常に動作する
4. IF 新しいテスト用接続設定が追加されたとき THEN 既存の`pgsql`接続設定 SHALL 影響を受けない
5. WHEN テストが実行されるとき THEN 既存のテストヘルパー・ファクトリー・シーダー SHALL 変更なしで動作する

---

### Requirement 10: 品質基準とセキュリティ
**Objective:** プロジェクトマネージャーとして、テストDB設定がプロジェクトの品質基準とセキュリティ要件を満たすことを確認したい。これにより、本番環境のセキュリティリスクを排除し、コード品質を保証できる。

#### Acceptance Criteria

1. WHEN コードが作成されるとき THEN すべてのPHPファイル SHALL Laravel Pint規約に準拠する
2. WHEN コードが作成されるとき THEN すべてのPHPファイル SHALL Larastan Level 8静的解析を通過する
3. WHEN 設定ファイルが作成されるとき THEN 機密情報（パスワード等） SHALL 環境変数経由で管理される
4. WHEN `.env.testing.*`ファイルが作成されるとき THEN ファイル SHALL `.gitignore`に追加される（テンプレートとして`.env.testing.*.example`を提供）
5. IF テスト用DBが作成されるとき THEN DB名 SHALL 本番/開発DBと明確に区別される（`app_test`プレフィックス）
6. WHEN スクリプトが作成されるとき THEN エラーハンドリング SHALL 適切なエラーメッセージを提供する
7. WHEN ドキュメントが作成されるとき THEN 内容 SHALL セキュリティベストプラクティス（本番DBとの分離、認証情報管理）を説明する
8. WHEN すべての実装が完了したとき THEN 全テスト SHALL SQLite環境とPostgreSQL環境の両方で成功する

---

## 対象外（Out of Scope）

以下の項目は本要件の対象外とし、別途対応します：

1. **既存テストコードの修正**: 既存テストの互換性は維持し、新規テスト追加時のみPostgreSQL専用機能を考慮
2. **PostgreSQL専用機能の活用**: JSONB型、特殊インデックス（GIN/GiST等）を使用した新規テスト追加
3. **テストデータのシーディング戦略変更**: 既存ファクトリー・シーダーの動作は維持
4. **パフォーマンステスト環境の構築**: テスト実行時間測定・最適化は別タスク
5. **テストカバレッジ基準の変更**: 既存の96.1%カバレッジ基準を維持
6. **テストDB自動バックアップ**: テスト用DBは一時的なものとし、バックアップ対象外
7. **本番DBマイグレーション**: テスト環境のみを対象とし、本番環境への影響なし

---

## 補足情報

### 技術スタック
- **Backend**: Laravel 12, Pest 4, PostgreSQL 17, SQLite
- **Infrastructure**: Docker, Laravel Sail, PostgreSQL
- **Tools**: GitHub Actions, Makefile

### 既存プロジェクト構成
- **デフォルトDB接続**: SQLite（`config/database.php`）
- **既存PostgreSQL接続**: `pgsql`（Port: 13432）
- **テスト設定**: `phpunit.xml`にてSQLite in-memory指定
- **CI/CD**: GitHub Actions `.github/workflows/test.yml`

### 参照ドキュメント
- Laravel 12 Database Testing: https://laravel.com/docs/12.x/database-testing
- Pest Parallel Testing: https://pestphp.com/docs/plugins/parallel
- PostgreSQL Docker Official Image: https://hub.docker.com/_/postgres
- GitHub Actions Services: https://docs.github.com/en/actions/using-containerized-services/about-service-containers

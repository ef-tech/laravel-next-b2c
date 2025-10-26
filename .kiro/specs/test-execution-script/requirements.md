# Requirements Document

## はじめに

モノレポ構成（Laravel API + Next.js admin-app/user-app + E2Eテスト）において、各テストスイートが個別に実行されており、統合的なテスト実行が困難な状況を解決するため、テスト実行スクリプトを作成します。

現在、開発者はテスト実行時に複数のコマンドを順次実行する必要があり、手動操作のミスや時間ロスが発生しています。既存のMakefileタスク（quick-test、test-pgsql、test-parallel、ci-test）は個別機能に特化しており、全体を通した実行フローが未整備です。

本機能により、単一コマンドで全テストスイート（バックエンド、フロントエンド、E2E）を統合実行し、JUnit/カバレッジレポートの統合生成、CI/CD統合、エラーハンドリングを提供します。これにより、開発者の生産性が向上し、テスト実行の一貫性が保証されます。

## Requirements

### Requirement 1: 統合テストスクリプト実行
**Objective:** 開発者として、単一コマンドで全テストスイート（バックエンド、フロントエンド、E2E）を統合実行できるようにしたい。そうすることで、手動操作のミスを防ぎ、テスト実行時間を短縮できる。

#### Acceptance Criteria

1. WHEN 開発者が `make test-all` コマンドを実行する THEN テスト実行スクリプトは全テストスイート（Backend、Frontend、E2E）を順次実行すること
2. WHEN 開発者が `./scripts/test/main.sh --suite all` コマンドを実行する THEN テスト実行スクリプトは全テストスイート（Backend、Frontend、E2E）を順次実行すること
3. WHEN テスト実行スクリプトが起動する THEN 環境変数バリデーションを実行し、必須環境変数が設定されていない場合はエラーメッセージを表示して終了すること
4. WHEN テスト実行スクリプトが起動する THEN ポート競合チェック（13000、13001、13002、13432、13379）を実行し、競合がある場合はエラーメッセージを表示すること
5. WHEN バックエンドテストとフロントエンドテストを実行する THEN これらのテストを並列実行すること
6. WHEN E2Eテストを実行する THEN 全サービス（Laravel API、User App、Admin App）が起動完了してから実行すること

### Requirement 2: テストスイート選択実行
**Objective:** 開発者として、特定のテストスイートのみを選択して実行できるようにしたい。そうすることで、開発中の機能に関連するテストのみを高速に実行できる。

#### Acceptance Criteria

1. WHEN 開発者が `--suite backend` オプションを指定する THEN テスト実行スクリプトはバックエンドテスト（Pest）のみを実行すること
2. WHEN 開発者が `--suite frontend` オプションを指定する THEN テスト実行スクリプトはフロントエンドテスト（Jest）のみを実行すること
3. WHEN 開発者が `--suite e2e` オプションを指定する THEN テスト実行スクリプトはE2Eテスト（Playwright）のみを実行すること
4. WHEN 開発者が `--suite smoke` オプションを指定する THEN テスト実行スクリプトはスモークテスト（重要機能のみ）を実行すること
5. WHEN 開発者が `make test-backend-only` コマンドを実行する THEN テスト実行スクリプトはバックエンドテストのみを実行すること
6. WHEN 開発者が `make test-frontend-only` コマンドを実行する THEN テスト実行スクリプトはフロントエンドテストのみを実行すること
7. WHEN 開発者が `make test-e2e-only` コマンドを実行する THEN テスト実行スクリプトはE2Eテストのみを実行すること

### Requirement 3: DB環境選択
**Objective:** 開発者として、テスト実行時にDB環境（SQLite/PostgreSQL）を選択できるようにしたい。そうすることで、高速開発時はSQLite、本番同等環境テスト時はPostgreSQLを使い分けできる。

#### Acceptance Criteria

1. WHEN 開発者が `--env sqlite` オプションを指定する THEN テスト実行スクリプトはSQLite環境でバックエンドテストを実行すること
2. WHEN 開発者が `--env postgres` オプションを指定する THEN テスト実行スクリプトはPostgreSQL環境でバックエンドテストを実行すること
3. WHEN 開発者が `make test-all` コマンドを実行する THEN デフォルトでSQLite環境を使用すること
4. WHEN 開発者が `make test-all-pgsql` コマンドを実行する THEN PostgreSQL環境を使用し、並列実行数4でテストを実行すること
5. WHEN PostgreSQL環境でテストを実行する THEN 既存の `make test-parallel` タスクを内部で呼び出すこと

### Requirement 4: 並列実行制御
**Objective:** 開発者として、テスト並列実行数を調整できるようにしたい。そうすることで、マシンリソースに応じた最適なテスト実行速度を実現できる。

#### Acceptance Criteria

1. WHEN 開発者が `--parallel` オプションを指定しない THEN テスト実行スクリプトはデフォルトで並列実行数4を使用すること
2. WHEN 開発者が `--parallel 8` オプションを指定する THEN テスト実行スクリプトは並列実行数8でテストを実行すること
3. WHEN 開発者が `--parallel 1` オプションを指定する THEN テスト実行スクリプトは並列実行せず順次実行すること
4. IF 並列実行数が1-8の範囲外である THEN テスト実行スクリプトはエラーメッセージを表示して終了すること
5. WHEN PostgreSQL環境で並列実行する THEN 既存の `./scripts/parallel-test-setup.sh` スクリプトを呼び出してテスト用DB環境をセットアップすること

### Requirement 5: 統合レポート生成
**Objective:** 開発者として、全テストスイートの結果を統合したレポートを自動生成したい。そうすることで、テスト結果の全体像を一目で把握できる。

#### Acceptance Criteria

1. WHEN 全テストスイートの実行が完了する THEN JUnit XMLレポートを `test-results/junit/` ディレクトリに出力すること
2. WHEN バックエンドテストが完了する THEN JUnit XMLレポートを `test-results/junit/backend-test-results.xml` に出力すること
3. WHEN フロントエンドテスト（Admin App）が完了する THEN JUnit XMLレポートを `test-results/junit/frontend-admin-results.xml` に出力すること
4. WHEN フロントエンドテスト（User App）が完了する THEN JUnit XMLレポートを `test-results/junit/frontend-user-results.xml` に出力すること
5. WHEN E2Eテストが完了する THEN JUnit XMLレポートを `test-results/junit/e2e-test-results.xml` に出力すること
6. WHEN 開発者が `--report` オプションを指定する THEN 統合サマリーを `test-results/reports/test-summary.json` に出力すること
7. WHEN 統合サマリーを生成する THEN タイムスタンプ、実行時間、総テスト数、成功数、失敗数、各スイート別結果を含むこと
8. IF GitHub Actions環境で実行される THEN Markdownサマリーを `$GITHUB_STEP_SUMMARY` に出力すること

### Requirement 6: カバレッジレポート生成
**Objective:** 開発者として、テストカバレッジレポートを統合生成したい。そうすることで、コード品質を継続的に監視できる。

#### Acceptance Criteria

1. WHEN 開発者が `--coverage` オプションを指定する THEN 全テストスイートでカバレッジレポートを生成すること
2. WHEN バックエンドテストでカバレッジを生成する THEN カバレッジレポートを `test-results/coverage/backend/` ディレクトリに出力すること
3. WHEN フロントエンドテスト（Admin App）でカバレッジを生成する THEN カバレッジレポートを `test-results/coverage/frontend-admin/` ディレクトリに出力すること
4. WHEN フロントエンドテスト（User App）でカバレッジを生成する THEN カバレッジレポートを `test-results/coverage/frontend-user/` ディレクトリに出力すること
5. WHEN 開発者が `make test-with-coverage` コマンドを実行する THEN PostgreSQL環境で全テストを実行し、カバレッジレポートを生成すること

### Requirement 7: エラーハンドリング
**Objective:** 開発者として、一部のテストが失敗しても全テストスイートの実行を継続したい。そうすることで、全テスト結果を一度に把握できる。

#### Acceptance Criteria

1. WHEN バックエンドテストが失敗する THEN テスト実行スクリプトはエラーを記録し、フロントエンドテストとE2Eテストの実行を継続すること
2. WHEN フロントエンドテストが失敗する THEN テスト実行スクリプトはエラーを記録し、他のテストスイートの実行を継続すること
3. WHEN E2Eテストが失敗する THEN テスト実行スクリプトはエラーを記録すること
4. WHEN 全テストスイートの実行が完了する THEN 失敗したテストがある場合は非ゼロの終了コードを返すこと
5. WHEN 全テストスイートの実行が完了する THEN 全テストが成功した場合は終了コード0を返すこと
6. WHEN テスト実行中にエラーが発生する THEN ログファイルを `test-results/logs/` ディレクトリに保存すること
7. WHEN バックエンドテストでエラーが発生する THEN ログを `test-results/logs/backend.log` に保存すること
8. WHEN フロントエンドテストでエラーが発生する THEN ログを `test-results/logs/frontend-admin.log` および `test-results/logs/frontend-user.log` に保存すること

### Requirement 8: Makefile統合
**Objective:** 開発者として、既存のMakefileタスクと統合された新規タスクを使用したい。そうすることで、既存のワークフローを維持しながら新機能を利用できる。

#### Acceptance Criteria

1. WHEN 開発者が `make test-all` を実行する THEN テスト実行スクリプトは全テストスイートをSQLite環境で実行すること
2. WHEN 開発者が `make test-all-pgsql` を実行する THEN テスト実行スクリプトは全テストスイートをPostgreSQL環境で並列実行数4で実行すること
3. WHEN 開発者が `make test-backend-only` を実行する THEN テスト実行スクリプトはバックエンドテストのみを実行すること
4. WHEN 開発者が `make test-frontend-only` を実行する THEN テスト実行スクリプトはフロントエンドテストのみを実行すること
5. WHEN 開発者が `make test-e2e-only` を実行する THEN テスト実行スクリプトはE2Eテストのみを実行すること
6. WHEN 開発者が `make test-with-coverage` を実行する THEN テスト実行スクリプトは全テストをPostgreSQL環境で実行し、カバレッジレポートを生成すること
7. WHEN 開発者が `make test-pr` を実行する THEN コード品質チェック（lint）を実行後、全テストをPostgreSQL環境でカバレッジ付きで実行すること
8. WHEN 開発者が `make test-smoke` を実行する THEN スモークテスト（重要機能のみ）を高速実行すること
9. WHEN 開発者が `make test-diagnose` を実行する THEN テスト環境診断スクリプトを実行すること
10. IF 既存のMakefileタスク（quick-test、test-pgsql、test-parallel、ci-test）が存在する THEN これらのタスクは変更せず維持すること

### Requirement 9: CI/CD統合
**Objective:** CI/CD環境で、既存のGitHub Actionsワークフローとシームレスに統合したい。そうすることで、CI/CD環境でも統合テスト実行が可能になる。

#### Acceptance Criteria

1. WHEN 開発者が `--ci` オプションを指定する THEN テスト実行スクリプトはCI/CDモードで実行すること
2. WHEN CI/CDモードで実行する THEN テスト完了後にサービスを停止せず、そのまま維持すること
3. WHEN CI/CD環境で実行する AND `$GITHUB_STEP_SUMMARY` 環境変数が設定されている THEN Markdownサマリーを `$GITHUB_STEP_SUMMARY` に出力すること
4. WHEN GitHub Actionsワークフローで実行する THEN `dorny/paths-filter@v3` アクションを使用して変更ファイルを検出すること
5. IF バックエンドファイルのみ変更されている THEN バックエンドテストとE2Eテストのみを実行すること
6. IF フロントエンドファイルのみ変更されている THEN フロントエンドテストとE2Eテストのみを実行すること
7. WHEN GitHub Actionsワークフローでテストが完了する THEN `actions/upload-artifact@v4` アクションを使用してテスト結果を `test-results/` としてアップロードすること

### Requirement 10: サービスヘルスチェック
**Objective:** E2Eテスト実行前に、全サービスが正常に起動していることを確認したい。そうすることで、サービス起動不良によるテスト失敗を防げる。

#### Acceptance Criteria

1. WHEN E2Eテストを実行する THEN 事前に全サービスのヘルスチェックを実行すること
2. WHEN ヘルスチェックを実行する THEN Laravel API (`http://localhost:13000/api/health`) に対してHTTPリクエストを送信すること
3. WHEN ヘルスチェックを実行する THEN User App (`http://localhost:13001/api/health`) に対してHTTPリクエストを送信すること
4. WHEN ヘルスチェックを実行する THEN Admin App (`http://localhost:13002/api/health`) に対してHTTPリクエストを送信すること
5. IF いずれかのサービスがヘルスチェックに失敗する THEN エラーメッセージを表示してE2Eテストを実行せずに終了すること
6. WHEN ヘルスチェックを実行する THEN 最大120秒間リトライし、全サービスの起動を待機すること

### Requirement 11: 診断スクリプト
**Objective:** 開発者として、テスト環境の問題を診断できるスクリプトを使用したい。そうすることで、テスト失敗時の原因特定が容易になる。

#### Acceptance Criteria

1. WHEN 開発者が `make test-diagnose` を実行する THEN 診断スクリプトが起動すること
2. WHEN 診断スクリプトが実行される THEN ポート使用状況（13000、13001、13002、13432、13379）を確認すること
3. WHEN 診断スクリプトが実行される THEN 必須環境変数の設定状態を確認すること
4. WHEN 診断スクリプトが実行される THEN Dockerコンテナの起動状態を確認すること
5. WHEN 診断スクリプトが実行される THEN データベース接続状態を確認すること
6. WHEN 診断スクリプトが実行される THEN ディスク空き容量を確認すること
7. WHEN 診断スクリプトが実行される THEN メモリ使用状況を確認すること
8. WHEN 診断スクリプトの実行が完了する THEN 診断結果をコンソールに出力すること

### Requirement 12: 共通関数ライブラリ
**Objective:** 開発者として、テストスクリプト間で共通の関数を再利用したい。そうすることで、コードの保守性が向上する。

#### Acceptance Criteria

1. WHEN テスト実行スクリプトが起動する THEN `scripts/lib/colors.sh` を読み込むこと
2. WHEN テスト実行スクリプトが起動する THEN `scripts/lib/logging.sh` を読み込むこと
3. WHEN ログメッセージを出力する THEN `logging.sh` の関数を使用すること
4. WHEN 色付きメッセージを出力する THEN `colors.sh` の関数を使用すること
5. IF `scripts/lib/` ディレクトリが存在しない THEN エラーメッセージを表示して終了すること

### Requirement 13: テストレポートディレクトリ構造
**Objective:** 開発者として、テストレポートが整理されたディレクトリ構造で保存されるようにしたい。そうすることで、レポートの参照が容易になる。

#### Acceptance Criteria

1. WHEN テスト実行スクリプトが起動する THEN `test-results/` ディレクトリが存在しない場合は作成すること
2. WHEN テスト実行スクリプトが起動する THEN `test-results/junit/` ディレクトリを作成すること
3. WHEN テスト実行スクリプトが起動する THEN `test-results/coverage/` ディレクトリを作成すること
4. WHEN テスト実行スクリプトが起動する THEN `test-results/reports/` ディレクトリを作成すること
5. WHEN テスト実行スクリプトが起動する THEN `test-results/logs/` ディレクトリを作成すること
6. WHEN テスト実行が完了する AND `--ci` オプションが指定されていない THEN 一時ファイルを削除すること

### Requirement 14: テストフレームワーク設定更新
**Objective:** 開発者として、各テストフレームワークがJUnit XMLレポートを出力するように設定を更新したい。そうすることで、統合レポートの生成が可能になる。

#### Acceptance Criteria

1. WHEN バックエンドテストを実行する THEN `phpunit.xml` の設定に従ってJUnit XMLレポートを出力すること
2. IF `phpunit.xml` にJUnit出力設定が存在しない THEN `<junit outputFile="../../test-results/junit/backend-test-results.xml"/>` 要素を追加すること
3. WHEN フロントエンドテストを実行する THEN `jest.config.js` の設定に従ってJUnit XMLレポートを出力すること
4. IF `jest.config.js` にjest-junitレポータ設定が存在しない THEN jest-junitレポータを追加すること
5. WHEN E2Eテストを実行する THEN `playwright.config.ts` の設定に従ってJUnit XMLレポートを出力すること
6. IF `playwright.config.ts` にjunitレポータ設定が存在しない THEN junitレポータを追加すること

### Requirement 15: ドキュメント整備
**Objective:** 開発者として、テスト実行スクリプトの使い方とトラブルシューティング方法を理解したい。そうすることで、スムーズにテストを実行できる。

#### Acceptance Criteria

1. WHEN ドキュメントを作成する THEN `docs/TESTING_EXECUTION_GUIDE.md` にテスト実行ガイドを記載すること
2. WHEN テスト実行ガイドを作成する THEN クイックスタート、ローカルテスト実行、CI/CD実行、テストスイート別実行方法、レポート確認方法を含むこと
3. WHEN ドキュメントを作成する THEN `docs/TESTING_TROUBLESHOOTING_EXTENDED.md` にトラブルシューティングガイドを記載すること
4. WHEN トラブルシューティングガイドを作成する THEN よくある問題（ポート競合、DB接続エラー、メモリ不足、並列実行失敗）と解決策を含むこと
5. WHEN ドキュメントを作成する THEN 診断スクリプト使用方法、ログ分析方法、エスカレーション手順を含むこと
6. WHEN ドキュメントを更新する THEN `README.md` に新規コマンドの使用方法を追加すること
7. WHEN ドキュメントを更新する THEN `CLAUDE.md` のActive Specificationsリストに本仕様を追加すること

### Requirement 16: スクリプト実行権限
**Objective:** 開発者として、テスト実行スクリプトを直接実行できるようにしたい。そうすることで、Makefileを経由せず柔軟にスクリプトを実行できる。

#### Acceptance Criteria

1. WHEN テスト実行スクリプトを作成する THEN `scripts/test/main.sh` に実行権限（chmod +x）を付与すること
2. WHEN バックエンドテストスクリプトを作成する THEN `scripts/test/test-backend.sh` に実行権限を付与すること
3. WHEN フロントエンドテストスクリプトを作成する THEN `scripts/test/test-frontend.sh` に実行権限を付与すること
4. WHEN E2Eテストスクリプトを作成する THEN `scripts/test/test-e2e.sh` に実行権限を付与すること
5. WHEN レポート生成スクリプトを作成する THEN `scripts/test/test-report.sh` に実行権限を付与すること
6. WHEN 診断スクリプトを作成する THEN `scripts/test/diagnose.sh` に実行権限を付与すること

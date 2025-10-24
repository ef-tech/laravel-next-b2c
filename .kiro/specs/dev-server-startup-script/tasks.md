# Implementation Plan

## 概要

この実装計画は、開発サーバー起動スクリプトの実装タスクを定義します。全タスクは6つのフェーズに分かれ、段階的に実装を進めます。

## 実装タスク

- [x] 1. Docker Composeプロファイル設定とインフラ基盤構築
- [x] 1.1 Docker Composeプロファイル定義を追加
  - `docker-compose.yml`にプロファイル定義を追加（infra、api、frontendプロファイル）
  - 既存サービス（pgsql、redis、mailpit、minio）にinfraプロファイルを適用
  - Laravel APIサービスにapiプロファイルを適用
  - Next.jsアプリ（admin-app、user-app）にfrontendプロファイルを適用
  - プロファイル未指定時の後方互換性を確保（全サービス起動）
  - _Requirements: 11.1, 11.6_

- [x] 1.2 プロファイル別起動の動作確認
  - infraプロファイル単独起動テスト（PostgreSQL、Redis、Mailpit、MinIO）
  - infra+apiプロファイル起動テスト（インフラ+Laravel API）
  - 全プロファイル起動テスト（infra+api+frontend）
  - 既存`docker compose up -d`の後方互換性確認
  - _Requirements: 11.2, 11.3, 11.4, 11.5_

- [ ] 2. 設定ファイル作成とJSONスキーマ定義
- [x] 2.1 設定ディレクトリとJSONファイルの作成
  - `scripts/dev/config/`ディレクトリを作成
  - `services.json`を作成（Laravel API、Admin App、User Appのサービス定義）
  - `profiles.json`を作成（full、api-only、frontend-onlyプロファイル）
  - `ports.json`を作成（13000-13010ポート定義）
  - _Requirements: 9.1, 9.4, 9.5_

- [x] 2.2 サービス定義とプロファイル定義の詳細設定
  - Laravel APIのDocker/ネイティブコマンド設定、ヘルスチェックURL設定、依存サービス定義（pgsql、redis）
  - Next.js Admin AppのDocker/ネイティブコマンド設定、依存サービス定義（laravel-api）
  - Next.js User AppのDocker/ネイティブコマンド設定、依存サービス定義（laravel-api）
  - fullプロファイルのサービスリスト定義（全サービス）
  - api-onlyプロファイルのサービスリスト定義（laravel-api、インフラのみ）
  - frontend-onlyプロファイルのサービスリスト定義（admin-app、user-app）
  - _Requirements: 3.1, 3.2, 9.1_

- [x] 2.3 JSON設定ファイルのバリデーション
  - 全設定ファイルのJSON構文検証
  - サービス名の一意性検証
  - プロファイル名の有効性検証（full、api-only、frontend-only）
  - 依存関係の循環参照検証
  - ポート番号の範囲検証（13000-13010）
  - _Requirements: 9.3, 9.6_

- [ ] 3. TypeScriptユーティリティ実装（設定管理・ヘルスチェック・ログ管理）
- [x] 3.1 TypeScript環境設定とプロジェクト構造準備
  - `scripts/dev/package.json`を作成（TypeScript、ts-node、concurrently依存関係）
  - `scripts/dev/tsconfig.json`を作成（TypeScript設定、ES Modules対応）
  - 型定義ファイル作成（Config、ServiceDefinition、ProfileDefinition、PortDefinition、Result型）
  - _Requirements: 9.1, 9.2_

- [x] 3.2 設定管理モジュール実装（dev-server.ts）
  - 設定ファイル読み込み機能（loadConfig関数: config/*.jsonを読み込み、JSON解析エラーハンドリング）
  - サービス選択ロジック（selectServices関数: プロファイル解決、個別サービス選択、不正な入力のエラーハンドリング）
  - 依存関係解決機能（resolveDependencies関数: 依存サービス自動追加、循環依存検出）
  - エラー型定義（ConfigLoadError、ServiceSelectionError）
  - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 9.1, 9.4, 9.5_

- [x] 3.3 ヘルスチェックモジュール実装（health-check.ts）
  - 依存ツールチェック機能（checkDependencies関数: Docker、Node.js、PHP、makeのバージョン確認）
  - ポート競合検出機能（checkPorts関数: lsof/ssコマンドでポート使用状況確認、PID・プロセス名取得、クロスプラットフォーム対応）
  - サービスヘルスチェック機能（waitForServices関数: PostgreSQL/Redis/Laravel API/Next.jsアプリのヘルスチェック、リトライロジック、タイムアウト処理）
  - OS検出機能（detectOS関数: macOS、Linux、Windows WSL判定）
  - エラー型定義（DependencyCheckError、HealthCheckError）
  - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5, 5.7, 7.1, 7.2, 7.3, 7.4, 7.5, 12.1, 12.2, 12.3, 12.4, 12.5_

- [x] 3.4 ログ管理モジュール実装（log-manager.ts）
  - ログストリーム作成機能（createLogStream関数: サービスごとのログストリーム生成）
  - ログレベル検出機能（detectLogLevel関数: error/warn/infoキーワード検出）
  - 統合ログ出力機能（outputUnifiedLog関数: プレフィックス付与、カラー出力、エラーログ強調表示）
  - カラー出力制御機能（`--raw`オプション時の無効化）
  - _Requirements: 4.1, 4.2, 4.3, 4.6, 4.7_

- [ ] 3.5 TypeScriptユーティリティのユニットテスト作成
  - dev-server.tsのテスト（loadConfig、selectServices、resolveDependencies）
  - health-check.tsのテスト（checkDependencies、checkPorts、waitForServices、detectOS）
  - log-manager.tsのテスト（detectLogLevel、outputUnifiedLog、カラー出力制御）
  - エラーケーステスト（不正なJSON、循環依存、バージョン不足、ポート競合、ヘルスチェックタイムアウト）
  - _Requirements: すべてのTypeScript実装要件に対応_

- [ ] 4. Bashスクリプト実装（エントリーポイント・Docker管理・プロセス管理）
- [ ] 4.1 メインエントリーポイントスクリプト作成（main.sh）
  - シェバング、実行権限設定
  - コマンドライン引数解析機能（--mode、--profile、--services、--logs、--setup、--skip-setup、--wait-for-health、--kill-ports、--raw、--no-color、--config-dir、--help）
  - ヘルプメッセージ表示機能（--helpオプション）
  - 不正なオプションのエラーハンドリング
  - _Requirements: 10.2, 10.3_

- [ ] 4.2 初回セットアップ統合機能実装（main.sh）
  - セットアップ完了チェック機能（.env、vendor/、node_modules/の存在確認）
  - `make setup`自動実行機能（セットアップ未完了時）
  - セットアップ失敗時のエラーハンドリング
  - `--setup`フラグによる強制実行機能
  - `--skip-setup`フラグによるスキップ機能
  - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5, 6.6, 6.7_

- [ ] 4.3 Docker Compose管理スクリプト作成（docker-manager.sh）
  - プロファイルフラグ生成機能（get_docker_profiles関数: モードとプロファイルに応じた--profileフラグ生成）
  - Docker Compose起動機能（start_docker_compose関数: docker compose --profile ... up -d実行）
  - Docker Compose停止機能（stop_docker_compose関数: docker compose down実行）
  - Docker Composeエラーハンドリング
  - _Requirements: 11.2, 11.3, 11.4, 11.5_

- [ ] 4.4 ネイティブプロセス管理スクリプト作成（process-manager.sh）
  - concurrentlyコマンド生成機能（build_concurrently_command関数: サービスリストからconcurrentlyコマンド生成）
  - ネイティブプロセス起動機能（start_native_processes関数: concurrently実行、カラープレフィックス設定、--kill-othersオプション）
  - シグナルハンドリング（SIGINT/SIGTERM対応、グレースフルシャットダウン）
  - concurrently未インストール時のエラーハンドリング
  - _Requirements: 1.2, 4.1, 4.2, 4.3, 8.1, 8.2, 8.3, 8.4, 8.6_

- [ ] 4.5 メインスクリプトの統合フロー実装（main.sh）
  - 引数解析後のTypeScript呼び出し（dev-server.ts実行、設定読み込み、サービス選択）
  - 依存関係チェック実行（health-check.ts実行、ツールバージョン確認、ポート競合チェック）
  - モード別起動処理（Dockerモード: docker-manager.sh呼び出し、ハイブリッドモード: docker-manager.sh + process-manager.sh呼び出し、ネイティブモード: docker-manager.sh + process-manager.sh呼び出し）
  - ヘルスチェック実行（health-check.ts実行、サービスReady確認）
  - 起動完了メッセージ表示（各サービスURL表示）
  - ログ統合表示（concurrentlyログまたはDocker Composeログ）
  - グレースフルシャットダウン実装（SIGINT/SIGTERM トラップ、全サービス停止、終了コード0）
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 2.1, 2.2, 2.3, 2.4, 2.5, 5.1, 5.2, 5.3, 5.4, 5.5, 5.6, 5.7, 7.1, 7.2, 7.3, 7.4, 7.5, 8.1, 8.2, 8.3, 8.4, 8.5, 8.6_

- [ ] 5. Makefile統合とコマンドエントリーポイント整備
- [ ] 5.1 Makefile新規ターゲット追加
  - `dev`ターゲット追加（ハイブリッドモード、fullプロファイル、`./scripts/dev/main.sh --mode=hybrid --profile=full`）
  - `dev-docker`ターゲット追加（Dockerモード、fullプロファイル、`./scripts/dev/main.sh --mode=docker --profile=full`）
  - `dev-native`ターゲット追加（ネイティブモード、fullプロファイル、`./scripts/dev/main.sh --mode=native --profile=full`）
  - `dev-api`ターゲット追加（api-onlyプロファイル、`./scripts/dev/main.sh --profile=api-only`）
  - `dev-frontend`ターゲット追加（frontend-onlyプロファイル、`./scripts/dev/main.sh --profile=frontend-only`）
  - `infra-up`ターゲット追加（infraプロファイルのみ、`docker compose --profile infra up -d`）
  - `api-up`ターゲット追加（infra+apiプロファイル、`docker compose --profile infra --profile api up -d`）
  - `logs`ターゲット追加（Docker Composeログ表示、`docker compose logs -f $(services)`）
  - _Requirements: 10.1_

- [ ] 5.2 Makefileヘルプメッセージ更新
  - `make help`コマンドに新規ターゲットの説明を追加
  - 各ターゲットの簡潔な説明（1行）を追加
  - 使用例の追加（基本例: `make dev`、詳細例: `./scripts/dev/main.sh --mode=docker --services=laravel-api,admin-app`）
  - _Requirements: 10.1_

- [ ] 6. テストとドキュメント整備
- [ ] 6.1 統合テスト実施（各モード・プロファイル動作確認）
  - Dockerモード全サービス起動テスト（`make dev-docker`）
  - ハイブリッドモード全サービス起動テスト（`make dev`）
  - ネイティブモード全サービス起動テスト（`make dev-native`）
  - API専用プロファイル起動テスト（`make dev-api`）
  - フロントエンド専用プロファイル起動テスト（`make dev-frontend`）
  - 個別サービス選択テスト（`./scripts/dev/main.sh --services=laravel-api,admin-app`）
  - _Requirements: 1.1, 1.2, 1.3, 2.1, 2.2, 2.3, 2.4, 2.5, 3.1, 3.2, 3.3, 3.4, 3.5_

- [ ] 6.2 エラーケーステスト実施
  - ポート競合エラーテスト（ポート13000を手動で占有、エラーメッセージ表示確認、PID・プロセス名表示確認、killコマンド例表示確認）
  - 依存ツール未インストールエラーテスト（Docker未インストール時のエラーメッセージ確認、推奨インストール方法表示確認）
  - 依存ツールバージョン不足エラーテスト（Node.js v16でバージョンエラー確認、アップグレード方法案内確認）
  - 設定ファイル不正エラーテスト（JSON構文エラー時の詳細エラーメッセージ確認、不正なプロファイル名時のエラーメッセージ確認）
  - ヘルスチェックタイムアウトエラーテスト（サービス起動失敗時のタイムアウトエラー確認、最新50行ログ表示確認）
  - `make setup`失敗時のエラーハンドリング確認
  - _Requirements: 5.2, 5.3, 5.4, 5.5, 5.6, 5.7, 6.4, 7.5, 9.2, 9.3, 10.3, 10.4_

- [ ] 6.3 グレースフルシャットダウンテスト
  - SIGINT（Ctrl+C）によるシャットダウンテスト（全サービス正常停止確認、シャットダウンメッセージ表示確認、30秒以内停止確認）
  - SIGTERM（kill -TERM）によるシャットダウンテスト（全サービス正常停止確認、終了コード0確認）
  - タイムアウト後の強制終了テスト（10秒タイムアウト後のSIGKILL確認）
  - エラー時の終了コード1確認
  - _Requirements: 1.4, 8.1, 8.2, 8.3, 8.4, 8.5, 8.6_

- [ ] 6.4 ログ出力テスト
  - 統合ログ表示テスト（プレフィックス付与確認、カラー出力確認、サービスごとの色分け確認）
  - `--logs=separate`オプションテスト（個別ログファイル生成確認、logs/ディレクトリ作成確認）
  - `--logs=quiet`オプションテスト（エラーログのみ表示確認）
  - `--raw`オプションテスト（カラー出力無効化確認、プレーンテキスト出力確認）
  - エラーレベルログ強調表示テスト（赤色強調確認）
  - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 4.7_

- [ ] 6.5 クロスプラットフォーム対応テスト
  - macOS環境での動作確認（lsofコマンド使用確認、全機能正常動作確認）
  - Linux環境での動作確認（ssコマンド使用確認、全機能正常動作確認）
  - Windows WSL環境での動作確認（WSL互換コマンド使用確認、全機能正常動作確認）
  - `--os-check`オプションテスト（OS種類・バージョン・利用可能コマンド表示確認）
  - _Requirements: 12.1, 12.2, 12.3, 12.4, 12.5, 12.6_

- [ ] 6.6 パフォーマンステスト
  - 起動時間計測（依存関係チェック・ポートチェック・設定ファイル読み込みが5秒以内確認）
  - ヘルスチェック完了時間計測（全サービスヘルスチェック完了が90秒以内確認）
  - シャットダウン時間計測（グレースフルシャットダウンが30秒以内確認）
  - ログ出力パフォーマンス計測（ログ出力遅延が100ms以内確認）
  - _Requirements: 非機能要件: パフォーマンス要件_

- [ ] 6.7 README.md更新とドキュメント整備
  - README.mdに「開発サーバー起動」セクション追加（基本的な使用方法: `make dev`、各モード・プロファイルの説明、サービス選択オプションの説明、設定ファイルのカスタマイズ方法、トラブルシューティング）
  - TypeScriptコードのJSDocコメント追加（関数・クラス・インターフェースの仕様説明）
  - Bashスクリプトの説明コメント追加（主要な処理ブロックに説明）
  - 設定ファイルのコメント追加（services.json、profiles.json、ports.jsonのフィールド説明）
  - _Requirements: 非機能要件: ドキュメント要件_

## 完了基準

全タスクが完了し、以下の検証チェックポイントをすべて満たすこと:

### Phase 1完了時
- [ ] `docker compose --profile infra up -d`が正常動作
- [ ] `docker compose --profile infra --profile api up -d`が正常動作
- [ ] `docker compose --profile infra --profile api --profile frontend up -d`が正常動作
- [ ] 既存`docker compose up -d`が後方互換性を維持

### Phase 2完了時
- [ ] `config/services.json`が有効なJSON構文
- [ ] `config/profiles.json`が有効なJSON構文
- [ ] `config/ports.json`が有効なJSON構文
- [ ] 全設定ファイルがバリデーション通過

### Phase 3完了時
- [ ] TypeScriptコンパイルエラーなし
- [ ] ユニットテスト全合格
- [ ] `dev-server.ts`が設定ファイルを正常読み込み
- [ ] `health-check.ts`が依存関係チェック成功

### Phase 4完了時
- [ ] `main.sh`が引数解析成功
- [ ] `docker-manager.sh`がDocker Compose起動成功
- [ ] `process-manager.sh`がconcurrently起動成功
- [ ] 統合テスト全合格

### Phase 5完了時
- [ ] `make dev`が正常動作
- [ ] `make dev-docker`が正常動作
- [ ] `make dev-native`が正常動作
- [ ] `make help`が新規ターゲットを表示

### Phase 6完了時
- [ ] 全モード・全プロファイルで正常動作
- [ ] エラーケースで適切なエラーメッセージ表示
- [ ] グレースフルシャットダウン動作
- [ ] macOS、Linux、Windows WSLで動作確認
- [ ] README.md更新完了

## 注意事項

- 各タスクは順序通りに実装すること（依存関係がある）
- タスク完了時は必ずマークダウンのチェックボックスを更新すること
- エラーが発生した場合は即座に報告し、Rollback手順を確認すること
- すべてのコードはTypeScript型安全性とShellCheck静的解析を通過すること

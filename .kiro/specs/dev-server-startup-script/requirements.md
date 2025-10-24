# Requirements Document

## はじめに

このドキュメントは、Laravel Next.js B2Cテンプレートプロジェクトにおける**開発サーバー起動スクリプト**の要件を定義します。

### ビジネス価値

現在、開発者はLaravel API、Next.js Admin App、Next.js User App、およびインフラサービス（PostgreSQL、Redis、Mailpit、MinIO）を個別に起動する必要があり、開発開始までに複数のコマンド実行と環境確認が必要です。この開発サーバー起動スクリプトにより、**単一コマンドで全サービスを起動**し、開発開始時間を大幅に短縮します。

### 主要目標

1. **開発効率の最大化**: 複数コマンド → 1コマンドで開発環境を起動
2. **柔軟な環境選択**: Docker/ネイティブ/ハイブリッドモードの切り替え
3. **サービス選択の自由度**: 必要なサービスのみを起動（api-only、frontend-only等）
4. **統合ログ管理**: プレフィックス付きカラー出力による視認性向上
5. **堅牢性**: エラーハンドリング、依存関係チェック、ヘルスチェック、グレースフルシャットダウン

### 対象ユーザー

- **プロジェクト開発者**: Laravel/Next.js開発に従事する開発者
- **新規参加者**: プロジェクトに新たに参加する開発者（オンボーディング対象）
- **CI/CD環境**: 自動化されたテスト・ビルド環境（将来的な活用）

---

## Requirements

### Requirement 1: 単一コマンドによる開発サーバー起動

**Objective:** 開発者として、単一のコマンドで全開発サーバーを起動したい。これにより、開発開始までの時間を最小化し、複雑なコマンド手順を記憶する必要をなくす。

#### Acceptance Criteria

1. WHEN 開発者が `make dev` コマンドを実行した THEN 開発サーバースクリプト SHALL デフォルトのハイブリッドモードで全サービスを起動する
2. WHEN 開発サーバースクリプトが起動を開始した THEN スクリプト SHALL 以下のサービスを指定された順序で起動する:
   - インフラサービス（PostgreSQL、Redis、Mailpit、MinIO）
   - Laravel APIサービス（ポート13000）
   - Next.js Admin Appサービス（ポート13002）
   - Next.js User Appサービス（ポート13001）
3. WHEN 全サービスが正常に起動した THEN スクリプト SHALL 起動完了メッセージと各サービスのURLを表示する
4. WHEN 開発者が `Ctrl+C` (SIGINT) または `kill` (SIGTERM) でスクリプトを終了した THEN スクリプト SHALL グレースフルシャットダウンを実行し、全サービスを正常に停止する

---

### Requirement 2: 動作モード切り替え機能

**Objective:** 開発者として、Docker/ネイティブ/ハイブリッドモードを選択して開発環境を起動したい。これにより、開発シナリオに応じた最適な環境を利用できる。

#### Acceptance Criteria

1. WHEN 開発者が `make dev-docker` コマンドを実行した THEN スクリプト SHALL 全サービスをDocker Composeで起動する（Dockerモード）
2. WHEN 開発者が `make dev-native` コマンドを実行した THEN スクリプト SHALL インフラサービスのみDockerで起動し、Laravel APIとNext.jsアプリをネイティブプロセスで起動する（ネイティブモード）
3. WHEN 開発者が `make dev` コマンドを実行した THEN スクリプト SHALL インフラサービスとLaravel APIをDockerで起動し、Next.jsアプリをネイティブプロセスで起動する（ハイブリッドモード、デフォルト）
4. WHEN 開発者が `./scripts/dev/main.sh --mode=docker` のように直接モードを指定した THEN スクリプト SHALL 指定されたモードで起動する
5. IF 開発者がネイティブモードまたはハイブリッドモードを選択した THEN スクリプト SHALL Node.js（v18以上）とPHP（v8.2以上）が利用可能であることを事前チェックする

---

### Requirement 3: サービス選択機能（プロファイル・個別選択）

**Objective:** 開発者として、必要なサービスのみを選択して起動したい。これにより、不要なサービスを起動せず、リソース消費を最小化し、起動時間を短縮できる。

#### Acceptance Criteria

1. WHEN 開発者が `make dev-api` コマンドを実行した THEN スクリプト SHALL インフラサービス（PostgreSQL、Redis）とLaravel APIのみを起動する（api-onlyプロファイル）
2. WHEN 開発者が `make dev-frontend` コマンドを実行した THEN スクリプト SHALL Next.js Admin AppとNext.js User Appのみを起動する（frontend-onlyプロファイル）
3. WHEN 開発者が `./scripts/dev/main.sh --services=laravel-api,admin-app` のように個別サービスを指定した THEN スクリプト SHALL 指定されたサービスと必要な依存サービスのみを起動する
4. WHEN プロファイルまたは個別サービス選択が行われた THEN スクリプト SHALL 起動されるサービスのリストを表示し、確認を求める（`--yes` または `-y` フラグで確認スキップ可能）
5. IF 開発者がサービス選択時に依存関係のあるサービスを指定しなかった THEN スクリプト SHALL 依存サービスを自動的に追加し、その旨を警告メッセージで通知する

---

### Requirement 4: ログ統合管理

**Objective:** 開発者として、全サービスのログを統合表示し、プレフィックス付きカラー出力で視認性を向上させたい。これにより、複数ターミナルを開く必要がなく、デバッグ効率を向上させる。

#### Acceptance Criteria

1. WHEN 開発サーバースクリプトが全サービスを起動した THEN スクリプト SHALL 各サービスのログを単一のターミナルウィンドウで統合表示する
2. WHEN ログが統合表示される THEN スクリプト SHALL 各ログ行に以下の形式でプレフィックスを付与する:
   ```
   [サービス名] | ログメッセージ
   ```
3. WHEN ログが統合表示される THEN スクリプト SHALL サービスごとに異なる色を使用してプレフィックスを表示する（例: infraは青、apiは緑、adminは黄、userはシアン）
4. WHEN 開発者が `--logs=separate` オプションを指定した THEN スクリプト SHALL サービスごとに個別のログファイルを生成する（例: `logs/infra.log`, `logs/api.log`）
5. WHEN 開発者が `--logs=quiet` オプションを指定した THEN スクリプト SHALL ログ出力を抑制し、エラーログのみを表示する
6. WHEN 開発者が `--raw` オプションを指定した THEN スクリプト SHALL カラー出力を無効化し、プレーンテキストでログを出力する（パイプやリダイレクト用）
7. WHEN ログ出力中にエラーレベルのログが検出された THEN スクリプト SHALL エラーログを赤色で強調表示する

---

### Requirement 5: 依存関係チェックとエラーハンドリング

**Objective:** 開発者として、起動前に必要なツールと環境を自動チェックし、問題がある場合は明確なエラーメッセージと解決策を表示してほしい。これにより、環境起動失敗の原因を迅速に特定できる。

#### Acceptance Criteria

1. WHEN 開発サーバースクリプトが起動を開始した THEN スクリプト SHALL 以下の依存ツールの存在とバージョンをチェックする:
   - Docker（v20.10以上）
   - Docker Compose（v2.0以上）
   - Node.js（v18以上、ネイティブ/ハイブリッドモード時）
   - PHP（v8.2以上、ネイティブ/ハイブリッドモード時）
   - make（任意バージョン）
2. IF 必要なツールが見つからない場合 THEN スクリプト SHALL エラーメッセージと推奨インストール方法を表示し、起動を中止する
3. IF 必要なツールのバージョンが要件を満たさない場合 THEN スクリプト SHALL 現在のバージョンと必要なバージョンを表示し、アップグレード方法を案内する
4. WHEN 起動前のポートチェックを実行した THEN スクリプト SHALL 以下のポート（13000、13001、13002、13432、13379、11025、13025、13900、13010）の使用状況を確認する
5. IF ポート競合が検出された場合 THEN スクリプト SHALL 競合しているポート番号、使用中のプロセスID（PID）、プロセス名を表示し、killコマンドの例を提示する
6. WHEN 開発者が `--kill-ports` オプションを指定した THEN スクリプト SHALL 競合しているポートを使用しているプロセスを自動的に終了する（確認プロンプト表示後）
7. IF 起動中にサービスがエラーで終了した場合 THEN スクリプト SHALL エラーが発生したサービス名、エラーメッセージ、最新50行のログを表示し、トラブルシューティングのヒントを提供する

---

### Requirement 6: make setupとの統合（初回セットアップ自動実行）

**Objective:** 開発者として、初回セットアップが未完了の場合、開発サーバー起動時に自動的に `make setup` を実行してほしい。これにより、手動セットアップの手順を省略し、オンボーディングを簡素化する。

#### Acceptance Criteria

1. WHEN 開発サーバースクリプトが起動を開始した THEN スクリプト SHALL 以下のファイル・ディレクトリの存在を確認する:
   - `.env` ファイル（プロジェクトルート）
   - `backend/laravel-api/vendor/` ディレクトリ
   - `node_modules/` ディレクトリ（プロジェクトルート）
2. IF 上記のいずれかが存在しない場合 THEN スクリプト SHALL 「初回セットアップが必要です。`make setup` を実行します」というメッセージを表示する
3. WHEN 初回セットアップが必要と判定された THEN スクリプト SHALL `make setup` コマンドを自動実行し、セットアップの進行状況を表示する
4. IF `make setup` が失敗した場合 THEN スクリプト SHALL セットアップエラーメッセージを表示し、開発サーバーの起動を中止する
5. WHEN `make setup` が正常に完了した THEN スクリプト SHALL 「セットアップが完了しました。開発サーバーを起動します」というメッセージを表示し、起動処理を継続する
6. WHEN 開発者が `--setup` フラグを指定した THEN スクリプト SHALL セットアップ状態に関わらず `make setup` を強制実行する
7. WHEN 開発者が `--skip-setup` フラグを指定した THEN スクリプト SHALL セットアップチェックと自動実行をスキップする（上級ユーザー向け）

---

### Requirement 7: ヘルスチェック機能

**Objective:** 開発者として、全サービスが正常に起動し、通信可能な状態になったことを自動確認してほしい。これにより、起動後すぐに開発を開始できる。

#### Acceptance Criteria

1. WHEN 全サービスの起動が完了した THEN スクリプト SHALL 以下のサービスに対してヘルスチェックを実行する:
   - PostgreSQL: `pg_isready -U sail` コマンド実行
   - Redis: `redis-cli ping` コマンド実行
   - Laravel API: `http://localhost:13000/api/health` エンドポイントへのHTTP GETリクエスト
   - Next.js Admin App: `http://localhost:13002` へのHTTP GETリクエスト
   - Next.js User App: `http://localhost:13001` へのHTTP GETリクエスト
2. WHEN ヘルスチェックを実行する THEN スクリプト SHALL 各サービスの依存関係に基づいて段階的にチェックを実行する（例: PostgreSQL → Redis → Laravel API → Next.jsアプリ）
3. WHEN ヘルスチェックを実行する THEN スクリプト SHALL デフォルトで最大30秒間、5秒間隔でリトライする（`--wait-for-health` オプションで変更可能）
4. IF ヘルスチェックが成功した場合 THEN スクリプト SHALL 「✓ [サービス名] is ready」というメッセージを表示する
5. IF ヘルスチェックがタイムアウトした場合 THEN スクリプト SHALL 「✗ [サービス名] health check failed (timeout: 30s)」というメッセージを表示し、該当サービスのログを出力する
6. WHEN 開発者が `--skip-health-check` フラグを指定した THEN スクリプト SHALL ヘルスチェックをスキップし、起動完了メッセージを即座に表示する

---

### Requirement 8: グレースフルシャットダウン

**Objective:** 開発者として、開発サーバースクリプトを終了する際、全サービスが正常に停止されることを保証してほしい。これにより、ゾンビプロセスやポート占有問題を防ぐ。

#### Acceptance Criteria

1. WHEN 開発者が `Ctrl+C` (SIGINT) を押した THEN スクリプト SHALL シャットダウンプロセスを開始し、「Shutting down services...」というメッセージを表示する
2. WHEN シャットダウンプロセスが開始された THEN スクリプト SHALL 以下の順序でサービスを停止する:
   - Next.jsアプリ（ネイティブプロセス）
   - Laravel API（ネイティブプロセス）
   - Docker Composeサービス（`docker compose down`）
3. WHEN 各サービスを停止する THEN スクリプト SHALL 最大10秒間の正常終了を待機し、タイムアウト後に強制終了（SIGKILL）する
4. WHEN 全サービスが停止した THEN スクリプト SHALL 「All services stopped successfully」というメッセージを表示し、終了コード0で終了する
5. IF シャットダウン中にエラーが発生した場合 THEN スクリプト SHALL エラーメッセージを表示し、終了コード1で終了する
6. WHEN 開発者が `kill -TERM <PID>` (SIGTERM) を送信した THEN スクリプト SHALL SIGINTと同様のグレースフルシャットダウンを実行する

---

### Requirement 9: 設定駆動アーキテクチャ

**Objective:** 開発者として、JSON設定ファイルを編集することで、サービス定義、ポート設定、プロファイルをカスタマイズしたい。これにより、プロジェクト固有の要件に柔軟に対応できる。

#### Acceptance Criteria

1. WHEN 開発サーバースクリプトが起動する THEN スクリプト SHALL 以下の設定ファイルを読み込む:
   - `scripts/dev/config/services.json`: サービス定義（Docker/ネイティブコマンド、ヘルスチェック設定）
   - `scripts/dev/config/ports.json`: ポート定義（サービスごとのポート番号）
   - `scripts/dev/config/profiles.json`: プロファイル定義（full、api-only、frontend-only等）
2. IF 設定ファイルが存在しない場合 THEN スクリプト SHALL デフォルト設定を使用し、「Warning: Configuration file not found, using defaults」という警告メッセージを表示する
3. IF 設定ファイルのJSON構文が不正な場合 THEN スクリプト SHALL 詳細なエラーメッセージ（ファイル名、行番号、エラー内容）を表示し、起動を中止する
4. WHEN 開発者が `services.json` に新しいサービスを追加した THEN スクリプト SHALL 追加されたサービスを認識し、起動オプションに含める
5. WHEN 開発者が `profiles.json` に新しいプロファイルを追加した THEN スクリプト SHALL 追加されたプロファイルを `--profile` オプションで選択可能にする
6. WHEN 設定ファイルが変更された THEN スクリプト SHALL 次回起動時に変更された設定を自動的に反映する（再ビルド不要）
7. WHEN 開発者が `--config-dir` オプションで設定ディレクトリを指定した THEN スクリプト SHALL 指定されたディレクトリから設定ファイルを読み込む（プロジェクト別設定のサポート）

---

### Requirement 10: Makefile統合とヘルプ機能

**Objective:** 開発者として、Makefileから簡潔なコマンドで開発サーバーを起動し、ヘルプコマンドで利用可能なオプションを確認したい。これにより、学習コストを最小化し、チーム全体で統一された操作方法を共有できる。

#### Acceptance Criteria

1. WHEN 開発者が `make help` コマンドを実行した THEN Makefile SHALL 以下の開発サーバー起動コマンドの説明を表示する:
   - `make dev`: ハイブリッドモードで全サービス起動（デフォルト）
   - `make dev-docker`: Dockerモードで全サービス起動
   - `make dev-native`: ネイティブモードで全サービス起動
   - `make dev-api`: API専用プロファイル起動
   - `make dev-frontend`: フロントエンド専用プロファイル起動
   - `make infra-up`: インフラサービスのみ起動
   - `make api-up`: インフラ+APIサービス起動
   - `make logs`: Docker Composeログ表示
2. WHEN 開発者が `./scripts/dev/main.sh --help` コマンドを実行した THEN スクリプト SHALL 以下の詳細ヘルプメッセージを表示する:
   - 利用可能なオプション一覧（`--mode`, `--profile`, `--services`, `--logs`, `--setup`, `--skip-setup`, `--wait-for-health`, `--kill-ports`, `--raw`, `--no-color`, `--config-dir`）
   - 各オプションの説明
   - 使用例（基本例、詳細例）
3. WHEN 開発者が不正なオプションを指定した THEN スクリプト SHALL 「Error: Unknown option '--invalid'」というエラーメッセージとヘルプメッセージを表示し、終了コード1で終了する
4. WHEN 開発者が存在しないプロファイルを指定した THEN スクリプト SHALL 「Error: Profile 'invalid-profile' not found. Available profiles: full, api-only, frontend-only」というエラーメッセージを表示し、終了コード1で終了する

---

### Requirement 11: Docker Compose プロファイル統合

**Objective:** 開発者として、Docker Composeプロファイル機能を活用して、サービスグループを柔軟に起動したい。これにより、既存のDocker Compose設定を最大限活用し、保守性を向上させる。

#### Acceptance Criteria

1. WHEN 開発サーバースクリプトが起動する THEN スクリプト SHALL `docker-compose.yml` に以下のプロファイルが定義されていることを確認する:
   - `infra` プロファイル: PostgreSQL、Redis、Mailpit、MinIO
   - `api` プロファイル: Laravel API
   - `frontend` プロファイル: Next.js Admin App、Next.js User App（オプション）
2. WHEN Dockerモードで全サービスを起動する THEN スクリプト SHALL `docker compose --profile infra --profile api --profile frontend up -d` コマンドを実行する
3. WHEN ハイブリッドモードで起動する THEN スクリプト SHALL `docker compose --profile infra --profile api up -d` コマンドを実行し、Next.jsアプリはネイティブプロセスで起動する
4. WHEN ネイティブモードで起動する THEN スクリプト SHALL `docker compose --profile infra up -d` コマンドを実行し、APIとNext.jsアプリはネイティブプロセスで起動する
5. WHEN 開発者が `make infra-up` コマンドを実行した THEN スクリプト SHALL `docker compose --profile infra up -d` コマンドを実行し、インフラサービスのみを起動する
6. WHEN 既存の `docker-compose.yml` にプロファイルが定義されていない場合 THEN スクリプト SHALL プロファイルを自動追加するか、警告メッセージを表示する（後方互換性維持）

---

### Requirement 12: クロスプラットフォーム対応

**Objective:** 開発者として、macOS、Linux、Windows（WSL）環境で同一のコマンドで開発サーバーを起動したい。これにより、開発者間の環境差異を最小化し、チーム全体で統一された開発体験を提供する。

#### Acceptance Criteria

1. WHEN 開発サーバースクリプトがmacOS環境で実行された THEN スクリプト SHALL macOS固有のコマンド（`lsof`、`pbcopy`等）を使用して正常動作する
2. WHEN 開発サーバースクリプトがLinux環境で実行された THEN スクリプト SHALL Linux固有のコマンド（`ss`、`xclip`等）を使用して正常動作する
3. WHEN 開発サーバースクリプトがWindows WSL環境で実行された THEN スクリプト SHALL WSL互換コマンドを使用して正常動作する
4. WHEN ポート競合チェックを実行する THEN スクリプト SHALL 実行環境（macOS/Linux/WSL）を検出し、適切なコマンド（`lsof` vs `ss`）を使用する
5. IF クロスプラットフォーム対応のために外部ツールが必要な場合 THEN スクリプト SHALL Node.js/TypeScriptでクロスプラットフォームロジックを実装する（Bash限界の場合）
6. WHEN 開発者が `--os-check` オプションを指定した THEN スクリプト SHALL 実行環境のOS種類、バージョン、利用可能なコマンドの詳細情報を表示する

---

## 非機能要件

### パフォーマンス要件

1. WHEN 開発サーバースクリプトが起動する THEN スクリプト SHALL 依存関係チェック、ポートチェック、設定ファイル読み込みを5秒以内に完了する
2. WHEN 全サービスが起動する THEN スクリプト SHALL ヘルスチェック完了まで90秒以内（デフォルトタイムアウト30秒 × 3サービス）に完了する
3. WHEN グレースフルシャットダウンを実行する THEN スクリプト SHALL 全サービスの停止を30秒以内に完了する

### 保守性要件

1. WHEN 新しいサービスをプロジェクトに追加する THEN 開発者 SHALL `services.json` に設定を追加するだけで、スクリプト本体のコード変更なしに統合できる
2. WHEN TypeScriptコードを変更する THEN 開発者 SHALL TypeScript型定義に従い、コンパイルエラーが発生しないことを確認する
3. WHEN Bashスクリプトを変更する THEN 開発者 SHALL ShellCheck静的解析ツールでコード品質を検証する

### セキュリティ要件

1. WHEN 開発サーバースクリプトが環境変数を読み込む THEN スクリプト SHALL 機密情報（APIキー、パスワード等）をログに出力しない
2. WHEN 開発者が `--log-dir` オプションでログを保存する THEN スクリプト SHALL ログファイルに機密情報が含まれていないことを確認する
3. IF セキュリティレビューで環境変数漏洩リスクが指摘された場合 THEN 開発者 SHALL 該当箇所を修正し、再レビューを受ける

### ドキュメント要件

1. WHEN 開発者が `README.md` を参照する THEN ドキュメント SHALL 「開発サーバー起動」セクションに以下の情報を含む:
   - 基本的な使用方法（`make dev`）
   - 各モード・プロファイルの説明
   - サービス選択オプションの説明
   - 設定ファイルのカスタマイズ方法
   - トラブルシューティング（よくあるエラーと解決策）
2. WHEN 開発者がTypeScriptコードを読む THEN コード SHALL JSDocコメントで関数・クラス・インターフェースの仕様を説明する
3. WHEN 開発者がBashスクリプトを読む THEN スクリプト SHALL 主要な処理ブロックに説明コメントを記載する

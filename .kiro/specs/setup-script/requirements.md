# Requirements Document

## Introduction

本ドキュメントは、Laravel 12 + Next.js 15.5モノレポ環境の一括セットアップスクリプトに関する要件を定義します。現在のプロジェクトは Docker Compose統合環境を採用していますが、初回環境構築には複数のコマンド実行と手動設定が必要で、開発者のオンボーディングに時間がかかっています。

本機能は、**`make setup` コマンド一つで完全な開発環境を15分以内に構築**できる自動化スクリプトを提供し、新規参加開発者の初期体験を向上させ、環境構築の失敗リスクを80%削減することを目的とします。

### ビジネス価値
- 新規参加開発者のオンボーディング時間を大幅短縮（30分以上 → 15分以内）
- 環境構築の失敗率削減による開発効率向上
- チーム全体での開発環境統一による環境差異トラブル削減
- テスト環境の迅速な再構築によるCI/CD効率化

## Requirements

### Requirement 1: 前提条件チェック機能
**Objective:** As a セットアップスクリプト利用者, I want システム環境が要件を満たしているかを自動検証できる機能, so that 環境構築の失敗を事前に防止できる

#### Acceptance Criteria

1. WHEN `make setup` が実行される THEN セットアップスクリプト SHALL Dockerバージョンが20.10.0以上であることを確認する
2. WHEN `make setup` が実行される THEN セットアップスクリプト SHALL Docker Composeバージョンが2.0.0以上であることを確認する
3. WHEN `make setup` が実行される THEN セットアップスクリプト SHALL Node.jsバージョンが18.0.0以上であることを確認する
4. WHEN `make setup` が実行される THEN セットアップスクリプト SHALL PHPバージョンが8.4.0以上であることを確認する
5. WHEN `make setup` が実行される THEN セットアップスクリプト SHALL `make` コマンド自体が存在することを確認する
6. WHEN `make setup` が実行される THEN セットアップスクリプト SHALL 以下のポートが利用可能であることを確認する: 13000, 13001, 13002, 13379, 13432, 11025, 13025, 13900, 13010
7. WHEN `make setup` が実行される THEN セットアップスクリプト SHALL ディスク空き容量が最低10GB以上であることを確認する
8. WHEN `make setup` が実行される THEN セットアップスクリプト SHALL 利用可能メモリが最低4GB以上であることを確認する
9. IF 前提条件チェックで要件を満たさない項目が見つかる THEN セットアップスクリプト SHALL 詳細なエラーメッセージと解決方法を表示する
10. IF 前提条件チェックで要件を満たさない項目が見つかる THEN セットアップスクリプト SHALL セットアップ処理を中断する

### Requirement 2: 環境変数セットアップ機能
**Objective:** As a セットアップスクリプト利用者, I want 全サービスの環境変数を自動設定できる機能, so that 手動設定ミスを防止し、迅速に環境を準備できる

#### Acceptance Criteria

1. WHEN `make setup` が実行される THEN セットアップスクリプト SHALL Laravel APIの `.env.example` を `.env` にコピーする（既存ファイルがない場合のみ）
2. WHEN `make setup` が実行される THEN セットアップスクリプト SHALL User Appの `.env.example` を `.env.local` にコピーする（既存ファイルがない場合のみ）
3. WHEN `make setup` が実行される THEN セットアップスクリプト SHALL Admin Appの `.env.example` を `.env.local` にコピーする（既存ファイルがない場合のみ）
4. WHEN Laravel API `.env` ファイルが作成される THEN セットアップスクリプト SHALL `APP_KEY` を自動生成する
5. WHEN 環境変数ファイルが作成される THEN セットアップスクリプト SHALL 環境変数バリデーションを実行し、必須項目の存在を確認する
6. IF 環境変数ファイルが既に存在する THEN セットアップスクリプト SHALL 上書きせず、既存ファイルを保持する旨をログ出力する
7. IF 環境変数バリデーションで必須項目が不足している THEN セットアップスクリプト SHALL 不足項目を一覧表示し、設定ガイドを提示する
8. WHEN 環境変数セットアップが完了する THEN セットアップスクリプト SHALL 機密情報（パスワード、トークン等）を平文でログ出力しない

### Requirement 3: 依存関係インストール機能
**Objective:** As a セットアップスクリプト利用者, I want バックエンド・フロントエンドの全依存関係を自動インストールできる機能, so that 手動インストールの手間を省き、依存関係の不整合を防止できる

#### Acceptance Criteria

1. WHEN `make setup` が実行される THEN セットアップスクリプト SHALL Laravel APIディレクトリで `composer install` を実行する
2. WHEN `make setup` が実行される THEN セットアップスクリプト SHALL モノレポルートで `npm install` を実行する（ワークスペース全体）
3. WHEN Docker イメージのプル処理が開始される THEN セットアップスクリプト SHALL PostgreSQL 17-alpine イメージをプルする
4. WHEN Docker イメージのプル処理が開始される THEN セットアップスクリプト SHALL Redis alpine イメージをプルする
5. WHEN Docker イメージのプル処理が開始される THEN セットアップスクリプト SHALL Mailpit イメージをプルする
6. WHEN Docker イメージのプル処理が開始される THEN セットアップスクリプト SHALL MinIO イメージをプルする
7. IF 依存関係インストールでネットワークエラーが発生する THEN セットアップスクリプト SHALL 最大3回まで自動リトライする
8. IF 依存関係インストールで失敗が継続する THEN セットアップスクリプト SHALL 詳細なエラーログと解決ガイドを表示する
9. WHEN 依存関係インストールが完了する THEN セットアップスクリプト SHALL インストールされたパッケージ数と所要時間をログ出力する

### Requirement 4: サービス起動・初期化機能
**Objective:** As a セットアップスクリプト利用者, I want 全サービスを正しい順序で起動し初期化できる機能, so that 依存関係の問題なく開発環境を立ち上げられる

#### Acceptance Criteria

1. WHEN `make setup` が実行される THEN セットアップスクリプト SHALL `docker compose up -d` で全サービスを起動する
2. WHEN Docker Composeが起動される THEN セットアップスクリプト SHALL PostgreSQLヘルスチェックが成功するまで最大60秒待機する
3. WHEN Docker Composeが起動される THEN セットアップスクリプト SHALL Redisヘルスチェックが成功するまで最大30秒待機する
4. WHEN Docker Composeが起動される THEN セットアップスクリプト SHALL Laravel APIヘルスチェック（`/api/health`）が成功するまで最大90秒待機する
5. WHEN Laravel APIが起動する THEN セットアップスクリプト SHALL データベースマイグレーションを実行する（`php artisan migrate`）
6. WHEN データベースマイグレーションが完了する THEN セットアップスクリプト SHALL シーディングを実行する（`php artisan db:seed`）
7. WHEN バックエンドサービスが起動完了する THEN セットアップスクリプト SHALL User App（ポート13001）のヘルスチェックを確認する
8. WHEN バックエンドサービスが起動完了する THEN セットアップスクリプト SHALL Admin App（ポート13002）のヘルスチェックを確認する
9. IF ヘルスチェックがタイムアウトする THEN セットアップスクリプト SHALL サービスログを出力し、トラブルシューティングガイドを表示する
10. WHEN 全サービスが正常起動する THEN セットアップスクリプト SHALL アクセスURLと次のステップをサマリー表示する

### Requirement 5: エラーハンドリング・リカバリ機能
**Objective:** As a セットアップスクリプト利用者, I want エラーが発生しても適切にリカバリできる機能, so that 環境構築の失敗率を削減し、再実行時の効率を向上できる

#### Acceptance Criteria

1. WHEN `make setup` が複数回実行される THEN セットアップスクリプト SHALL 冪等性を保証し、安全に再実行できる
2. WHEN セットアップの途中でエラーが発生する THEN セットアップスクリプト SHALL エラーの発生ステップと詳細メッセージを表示する
3. WHEN ネットワーク依存処理（Docker pull、npm install等）でエラーが発生する THEN セットアップスクリプト SHALL 指数バックオフで最大3回リトライする
4. WHEN `make setup --from <step>` が実行される THEN セットアップスクリプト SHALL 指定されたステップから部分的に再実行する
5. IF Docker Composeサービス起動でエラーが発生する THEN セットアップスクリプト SHALL 関連サービスのログを自動収集して表示する
6. IF 環境変数バリデーションでエラーが発生する THEN セットアップスクリプト SHALL 不足項目と推奨値を具体的に提示する
7. IF ポート競合エラーが発生する THEN セットアップスクリプト SHALL 競合しているプロセスIDとポート番号を表示する
8. WHEN エラーリカバリが必要な場合 THEN セットアップスクリプト SHALL 解決手順を段階的に提示する
9. WHEN セットアップが中断される THEN セットアップスクリプト SHALL 進捗状態をマーカーファイル（`.setup-progress`）に記録する
10. WHEN 部分的再実行が開始される THEN セットアップスクリプト SHALL マーカーファイルを読み込み、完了済みステップをスキップする

### Requirement 6: 環境差異対応機能
**Objective:** As a セットアップスクリプト利用者, I want macOS/Linux/WSL2の環境差異を自動検出・対応できる機能, so that どの環境でも統一された手順で環境構築できる

#### Acceptance Criteria

1. WHEN `make setup` が実行される THEN セットアップスクリプト SHALL 実行環境（macOS/Linux/WSL2）を自動検出する
2. IF macOS環境で実行される THEN セットアップスクリプト SHALL Homebrewを利用した依存関係チェック・インストールガイドを提供する
3. IF Linux環境で実行される THEN セットアップスクリプト SHALL apt/yumを利用した依存関係チェック・インストールガイドを提供する
4. IF WSL2環境で実行される THEN セットアップスクリプト SHALL Windows Docker Desktopとの連携を確認する
5. IF 環境特有の問題が検出される THEN セットアップスクリプト SHALL 環境別のトラブルシューティングガイドを表示する
6. WHEN ファイルパスを扱う場合 THEN セットアップスクリプト SHALL 環境に応じた適切なパス区切り文字を使用する
7. WHEN シェルコマンドを実行する場合 THEN セットアップスクリプト SHALL Bash 4.0以上の互換性を保証する

### Requirement 7: 進捗表示・ログ機能
**Objective:** As a セットアップスクリプト利用者, I want セットアップ進捗と詳細ログを確認できる機能, so that 現在の状態を把握し、問題発生時のデバッグを容易にできる

#### Acceptance Criteria

1. WHEN 各セットアップステップが開始される THEN セットアップスクリプト SHALL ステップ名と進捗状況（X/Y）を表示する
2. WHEN 各セットアップステップが完了する THEN セットアップスクリプト SHALL 所要時間と結果（成功/失敗）を表示する
3. WHEN 長時間処理が実行される THEN セットアップスクリプト SHALL スピナーまたはプログレスバーを表示する
4. WHEN セットアップが実行される THEN セットアップスクリプト SHALL 詳細ログを `.setup.log` ファイルに記録する
5. WHEN エラーが発生する THEN セットアップスクリプト SHALL エラーログを赤色でハイライト表示する
6. WHEN 警告が発生する THEN セットアップスクリプト SHALL 警告ログを黄色でハイライト表示する
7. WHEN セットアップが完了する THEN セットアップスクリプト SHALL 全体の所要時間とサマリーを表示する
8. IF セットアップが15分を超える THEN セットアップスクリプト SHALL パフォーマンス警告とボトルネック情報を表示する

### Requirement 8: CI/CD統合機能
**Objective:** As a CI/CD管理者, I want セットアップスクリプトをCI/CD環境で実行できる機能, so that 自動テスト環境構築とデプロイパイプラインを統合できる

#### Acceptance Criteria

1. WHEN `make setup --ci` が実行される THEN セットアップスクリプト SHALL 対話的プロンプトなしで完全自動実行する
2. WHEN CI/CDモードで実行される THEN セットアップスクリプト SHALL 環境変数を必須パラメータとして要求する
3. WHEN CI/CDモードで実行される THEN セットアップスクリプト SHALL カラーコード出力を無効化する
4. WHEN CI/CDモードで実行される THEN セットアップスクリプト SHALL タイムアウト時間を環境変数（`SETUP_TIMEOUT`）で制御可能にする
5. IF CI/CD環境でエラーが発生する THEN セットアップスクリプト SHALL 終了コード（0=成功、1以上=失敗）を適切に返す
6. WHEN GitHub Actions環境で実行される THEN セットアップスクリプト SHALL GitHub Actions annotations形式でエラー・警告を出力する

### Requirement 9: ドキュメント整備機能
**Objective:** As a 開発者, I want セットアップ手順とトラブルシューティングガイドを参照できるドキュメント, so that 自己解決能力を向上し、サポート負荷を削減できる

#### Acceptance Criteria

1. WHEN README.mdが更新される THEN ドキュメント SHALL クイックスタートセクションに `make setup` の実行手順を記載する
2. WHEN README.mdが更新される THEN ドキュメント SHALL 前提条件（Docker、Node.js等）のバージョン要件を明記する
3. WHEN トラブルシューティングガイドが作成される THEN ドキュメント SHALL よくあるエラーパターンと解決方法を記載する
4. WHEN トラブルシューティングガイドが作成される THEN ドキュメント SHALL 環境別（macOS/Linux/WSL2）の問題と対処法を記載する
5. WHEN トラブルシューティングガイドが作成される THEN ドキュメント SHALL ポート競合、ディスク容量不足等の具体的対処法を記載する
6. WHEN ドキュメントが更新される THEN ドキュメント SHALL 部分的再実行（`--from <step>`）の使用方法を説明する
7. WHEN ドキュメントが更新される THEN ドキュメント SHALL CI/CDモード（`--ci`）の使用方法を説明する

### Requirement 10: パフォーマンス要件
**Objective:** As a セットアップスクリプト利用者, I want 高速かつ効率的な環境構築, so that オンボーディング時間を最小化できる

#### Acceptance Criteria

1. WHEN クリーン環境で `make setup` が実行される THEN セットアップスクリプト SHALL 15分以内に全環境構築を完了する
2. WHEN 部分的再実行が行われる THEN セットアップスクリプト SHALL 完了済みステップをスキップし、2分以内に再実行を完了する
3. WHEN 依存関係インストールが実行される THEN セットアップスクリプト SHALL Composerキャッシュとnpmキャッシュを活用する
4. WHEN Docker イメージプルが実行される THEN セットアップスクリプト SHALL 並列プルで所要時間を短縮する
5. IF セットアップが10分を超える THEN セットアップスクリプト SHALL パフォーマンス分析情報をログ出力する

### Requirement 11: セキュリティ要件
**Objective:** As a セキュリティ責任者, I want セットアップスクリプトがセキュリティベストプラクティスに準拠する, so that 機密情報漏洩や脆弱性を防止できる

#### Acceptance Criteria

1. WHEN 環境変数ファイルが作成される THEN セットアップスクリプト SHALL `.env` ファイルが `.gitignore` に含まれていることを確認する
2. WHEN ログが出力される THEN セットアップスクリプト SHALL パスワード、トークン、APIキー等の機密情報をマスキングする
3. WHEN 一時ファイルが作成される THEN セットアップスクリプト SHALL セットアップ完了後に一時ファイルを削除する
4. WHEN スクリプトが実行される THEN セットアップスクリプト SHALL root権限を要求しない（Docker操作を除く）
5. IF 機密情報を含むファイルが作成される THEN セットアップスクリプト SHALL ファイルパーミッションを600（所有者のみ読み書き可能）に設定する

# 開発サーバー起動スクリプト テスト手順書

このドキュメントは、開発サーバー起動スクリプトの包括的なテスト手順を提供します。

## 目次

1. [前提条件](#前提条件)
2. [6.1 統合テスト](#61-統合テスト)
3. [6.2 エラーケーステスト](#62-エラーケーステスト)
4. [6.3 グレースフルシャットダウンテスト](#63-グレースフルシャットダウンテスト)
5. [6.4 ログ出力テスト](#64-ログ出力テスト)
6. [6.5 クロスプラットフォーム対応テスト](#65-クロスプラットフォーム対応テスト)
7. [6.6 パフォーマンステスト](#66-パフォーマンステスト)

---

## 前提条件

### 必要なツール

```bash
# バージョン確認
docker --version          # Docker 20.10+
docker compose version    # Docker Compose v2+
node --version           # Node.js 18+
npm --version            # npm 9+
php --version            # PHP 8.4+
composer --version       # Composer 2+
```

### 初期セットアップ

```bash
# リポジトリルートに移動
cd /Users/okumura/Work/src/ef-tech/template/laravel-next-b2c

# セットアップ実行（初回のみ）
make setup

# 既存のサービスを停止
docker compose down
make dev-stop 2>/dev/null || true
```

---

## 6.1 統合テスト

各モード・プロファイルの動作確認を実施します。

### テスト 6.1.1: Dockerモード全サービス起動

**目的**: 全サービスがDockerで正常に起動することを確認

```bash
# 1. 起動
make dev-docker

# 2. 確認項目
# - 起動メッセージが表示されること
# - "Dev Server Started Successfully" メッセージが表示されること

# 3. サービス確認
docker compose ps

# 期待結果:
# - laravel-api: healthy
# - admin-app: healthy
# - user-app: healthy
# - pgsql: healthy
# - redis: healthy
# - mailpit: healthy
# - minio: healthy

# 4. アクセス確認
curl http://localhost:13000/api/health  # Laravel API
curl http://localhost:13001             # User App
curl http://localhost:13002             # Admin App

# 期待結果: 全て正常なレスポンス（200 OK）

# 5. 停止
make dev-stop

# 6. 確認
docker compose ps
# 期待結果: 全サービスが停止していること
```

**✅ チェックポイント**:
- [ ] 全サービスが起動（docker compose ps で healthy）
- [ ] 各URLにアクセス可能
- [ ] 停止コマンドで全サービスが停止

---

### テスト 6.1.2: ハイブリッドモード全サービス起動

**目的**: インフラDocker、アプリネイティブで正常に起動することを確認

```bash
# 1. 起動
make dev

# 2. 確認項目
# - "Starting infrastructure with Docker..." メッセージ表示
# - "Starting applications natively..." メッセージ表示
# - "Dev Server Started Successfully" メッセージ表示

# 3. Dockerサービス確認
docker compose ps

# 期待結果（Dockerで起動）:
# - pgsql: healthy
# - redis: healthy
# - mailpit: healthy
# - minio: healthy

# 4. ネイティブプロセス確認
ps aux | grep "php artisan serve"
ps aux | grep "npm run dev"

# 期待結果:
# - php artisan serve プロセスが実行中
# - npm run dev プロセスが実行中（admin-app, user-app）

# 5. アクセス確認
curl http://localhost:13000/api/health
curl http://localhost:13001
curl http://localhost:13002

# 6. 停止（Ctrl+C または別ターミナルから）
# 別ターミナルで:
make dev-stop
```

**✅ チェックポイント**:
- [ ] インフラサービスがDockerで起動
- [ ] アプリケーションがネイティブプロセスで起動
- [ ] 各URLにアクセス可能
- [ ] Ctrl+Cで全サービスが停止

---

### テスト 6.1.3: ネイティブモード全サービス起動

**目的**: 全サービスがネイティブプロセスで起動することを確認

```bash
# 1. PostgreSQLとRedisを手動起動（ネイティブモードの前提）
docker compose up -d pgsql redis

# 2. ネイティブモードで起動
make dev-native

# 3. プロセス確認
ps aux | grep -E "(php artisan|npm run dev)"

# 期待結果:
# - php artisan serve プロセス
# - npm run dev プロセス（admin-app, user-app）

# 4. アクセス確認
curl http://localhost:13000/api/health
curl http://localhost:13001
curl http://localhost:13002

# 5. 停止（Ctrl+C）
```

**✅ チェックポイント**:
- [ ] 全アプリケーションがネイティブプロセスで起動
- [ ] 各URLにアクセス可能
- [ ] Ctrl+Cで全プロセスが停止

---

### テスト 6.1.4: API専用プロファイル起動

**目的**: APIのみが起動することを確認

```bash
# 1. 起動
make dev-api

# 2. サービス確認
docker compose ps

# 期待結果（起動しているもの）:
# - pgsql: healthy
# - redis: healthy
# - mailpit: healthy
# - minio: healthy
# - laravel-api: healthy（またはネイティブプロセス）

# 期待結果（起動していないもの）:
# - admin-app: なし
# - user-app: なし

# 3. アクセス確認
curl http://localhost:13000/api/health  # 成功
curl http://localhost:13001             # 失敗（接続拒否）
curl http://localhost:13002             # 失敗（接続拒否）

# 4. 停止
make dev-stop
```

**✅ チェックポイント**:
- [ ] APIとインフラのみ起動
- [ ] フロントエンドアプリは起動していない
- [ ] APIにのみアクセス可能

---

### テスト 6.1.5: フロントエンド専用プロファイル起動

**目的**: フロントエンドのみが起動することを確認

```bash
# 1. 起動
make dev-frontend

# 2. サービス確認
docker compose ps

# 期待結果（起動しているもの）:
# - admin-app: healthy（またはネイティブプロセス）
# - user-app: healthy（またはネイティブプロセス）

# 期待結果（起動していないもの）:
# - laravel-api: なし
# - pgsql: なし
# - redis: なし

# 3. アクセス確認
curl http://localhost:13001  # 成功
curl http://localhost:13002  # 成功
curl http://localhost:13000/api/health  # 失敗（接続拒否）

# 4. 停止
make dev-stop
```

**✅ チェックポイント**:
- [ ] フロントエンドアプリのみ起動
- [ ] APIは起動していない
- [ ] フロントエンドにのみアクセス可能

---

### テスト 6.1.6: 個別サービス選択

**目的**: 特定サービスのみを起動できることを確認

```bash
# 1. Laravel APIとAdmin Appのみ起動
./scripts/dev/main.sh --mode hybrid --services laravel-api,admin-app

# 2. サービス確認
docker compose ps
ps aux | grep -E "(php artisan|npm run dev)"

# 期待結果:
# - laravel-api: 起動
# - admin-app: 起動
# - user-app: 未起動

# 3. アクセス確認
curl http://localhost:13000/api/health  # 成功
curl http://localhost:13002             # 成功
curl http://localhost:13001             # 失敗（接続拒否）

# 4. 停止（Ctrl+C）
```

**✅ チェックポイント**:
- [ ] 指定したサービスのみ起動
- [ ] 未指定のサービスは起動していない

---

## 6.2 エラーケーステスト

エラー時の適切なメッセージ表示と処理を確認します。

### テスト 6.2.1: ポート競合エラー

**目的**: ポートが既に使用されている場合のエラー処理を確認

```bash
# 1. ポート13000を手動で占有
python3 -m http.server 13000 &
HTTP_SERVER_PID=$!

# 2. 開発サーバー起動を試行
make dev

# 3. 期待されるエラーメッセージ:
# [WARN] Some ports are in use
# Port 13000 is in use by process XXX (python3)
# Services using conflicting ports may fail to start

# 4. クリーンアップ
kill $HTTP_SERVER_PID
make dev-stop
```

**✅ チェックポイント**:
- [ ] ポート競合が検出される
- [ ] 競合しているプロセス情報が表示される
- [ ] エラーメッセージが明確

---

### テスト 6.2.2: Docker未インストールエラー

**目的**: Docker未インストール時のエラー処理を確認

```bash
# 注意: 実際にDockerをアンインストールする必要はありません
# docker-manager.shのcheck_docker_compose関数を一時的に修正してテスト

# 1. docker-manager.shを編集（テスト用）
# check_docker_compose関数内で強制的にエラーを返すよう修正

# 2. 起動試行
make dev-docker

# 3. 期待されるエラーメッセージ:
# [ERROR] Docker is not installed or not in PATH
# [ERROR] Please install Docker Desktop or Docker Engine

# 4. 修正を戻す
git checkout scripts/dev/docker-manager.sh
```

**✅ チェックポイント**:
- [ ] Docker未インストールが検出される
- [ ] インストール方法が案内される

---

### テスト 6.2.3: 不正なJSON設定エラー

**目的**: JSON構文エラー時のエラー処理を確認

```bash
# 1. 設定ファイルを一時的に壊す
cp scripts/dev/config/services.json scripts/dev/config/services.json.bak
echo "{invalid json" > scripts/dev/config/services.json

# 2. 起動試行
make dev

# 3. 期待されるエラーメッセージ:
# [ERROR] Failed to load configuration
# JSON syntax error in services.json

# 4. 設定を復元
mv scripts/dev/config/services.json.bak scripts/dev/config/services.json
```

**✅ チェックポイント**:
- [ ] JSON構文エラーが検出される
- [ ] エラー箇所が明示される

---

### テスト 6.2.4: 不正なプロファイル名エラー

**目的**: 存在しないプロファイル指定時のエラー処理を確認

```bash
# 1. 存在しないプロファイルを指定
./scripts/dev/main.sh --profile invalid-profile

# 2. 期待されるエラーメッセージ:
# [ERROR] Invalid profile: invalid-profile
# Available profiles: full, api-only, frontend-only, infra-only, minimal

# 3. 確認
echo $?
# 期待結果: 終了コード 1
```

**✅ チェックポイント**:
- [ ] 不正なプロファイル名が検出される
- [ ] 利用可能なプロファイル一覧が表示される
- [ ] 適切な終了コードが返される

---

### テスト 6.2.5: セットアップ未完了エラー

**目的**: セットアップ未完了時の自動セットアップ実行を確認

```bash
# 1. セットアップ状態をリセット（注意: 実際の環境では慎重に）
# 以下はテスト目的のみ
rm backend/laravel-api/.env 2>/dev/null || true

# 2. 起動試行
make dev

# 3. 期待される動作:
# [WARN] Setup not completed
# [INFO] Running automatic setup...
# [INFO] Executing: make setup
# （セットアップが自動実行される）

# 4. セットアップ後に正常起動
# [SUCCESS] Setup completed successfully
# [INFO] Starting services...

# 5. 確認
ls backend/laravel-api/.env
# 期待結果: .envファイルが作成されている
```

**✅ チェックポイント**:
- [ ] セットアップ未完了が検出される
- [ ] 自動的にmake setupが実行される
- [ ] セットアップ完了後にサービスが起動する

---

## 6.3 グレースフルシャットダウンテスト

シグナルハンドリングと正常な終了処理を確認します。

### テスト 6.3.1: SIGINT（Ctrl+C）シャットダウン

**目的**: Ctrl+Cで全サービスが正常に停止することを確認

```bash
# 1. ハイブリッドモードで起動
make dev

# 2. サービスが起動したことを確認
curl http://localhost:13000/api/health

# 3. Ctrl+Cを押す（フォアグラウンド実行の場合）
# または別ターミナルから:
# ps aux | grep "main.sh" | grep -v grep | awk '{print $2}' | xargs kill -INT

# 4. 期待される動作:
# - [INFO] Shutting down services...
# - [INFO] Stopping Docker services...
# - [INFO] Stopping native processes...
# - [SUCCESS] All services stopped successfully

# 5. 確認
docker compose ps
ps aux | grep -E "(php artisan|npm run dev)" | grep -v grep
# 期待結果: 全サービスが停止している

# 6. 終了コード確認
echo $?
# 期待結果: 0
```

**✅ チェックポイント**:
- [ ] Ctrl+Cで停止処理が開始される
- [ ] Dockerサービスが停止される
- [ ] ネイティブプロセスが停止される
- [ ] シャットダウンメッセージが表示される
- [ ] 終了コードが0

---

### テスト 6.3.2: SIGTERM シャットダウン

**目的**: SIGTERM受信時に正常に停止することを確認

```bash
# 1. バックグラウンドで起動
./scripts/dev/main.sh --mode hybrid --profile full &
DEV_PID=$!

# 2. サービス起動を待機
sleep 10

# 3. SIGTERMを送信
kill -TERM $DEV_PID

# 4. 待機
wait $DEV_PID
EXIT_CODE=$?

# 5. 確認
docker compose ps
ps aux | grep -E "(php artisan|npm run dev)" | grep -v grep
# 期待結果: 全サービスが停止している

echo $EXIT_CODE
# 期待結果: 0（正常終了）
```

**✅ チェックポイント**:
- [ ] SIGTERMで停止処理が開始される
- [ ] 全サービスが停止される
- [ ] 終了コードが0

---

### テスト 6.3.3: シャットダウンタイムアウト

**目的**: シャットダウンに時間がかかる場合の処理を確認

```bash
# 注意: このテストは停止処理の実装次第で異なります
# 現在の実装ではタイムアウト後の強制終了は未実装のため、
# 将来的な実装のためのテスト手順として記載

# 1. 起動
make dev

# 2. プロセスIDを確認
ps aux | grep "main.sh"

# 3. Ctrl+Cを押す

# 4. 30秒以内に全サービスが停止することを確認
time make dev-stop

# 期待結果: 30秒以内に完了
```

**✅ チェックポイント**:
- [ ] シャットダウンが30秒以内に完了する
- [ ] タイムアウト時のメッセージが表示される（実装されている場合）

---

## 6.4 ログ出力テスト

ログの統合表示とフォーマットを確認します。

### テスト 6.4.1: 統合ログ表示

**目的**: プレフィックス付きカラー出力が正常に動作することを確認

```bash
# 1. ハイブリッドモードで起動（フォアグラウンド）
make dev

# 2. ログ出力を観察

# 期待される表示:
# [INFO] Starting services in hybrid mode...
# [INFO] Starting infrastructure with Docker...
# [SUCCESS] Docker services started successfully
# [INFO] Starting applications natively...
# （各サービスのログがプレフィックス付きで表示される）

# 3. カラー出力確認
# - [INFO] は青色
# - [SUCCESS] は緑色
# - [WARN] は黄色
# - [ERROR] は赤色

# 4. サービス別プレフィックス確認
# - [Laravel API] ...
# - [Admin App] ...
# - [User App] ...

# 5. 停止（Ctrl+C）
```

**✅ チェックポイント**:
- [ ] ログレベルごとに色分けされている
- [ ] サービスごとにプレフィックスが付与されている
- [ ] ログが統合して表示される

---

### テスト 6.4.2: デバッグログ出力

**目的**: DEBUG=1でデバッグログが表示されることを確認

```bash
# 1. デバッグモードで起動
DEBUG=1 ./scripts/dev/main.sh --mode hybrid --profile full

# 2. 期待される追加ログ:
# [DEBUG] Parsed arguments: MODE=hybrid, PROFILE=full, ...
# [DEBUG] Configuration loaded successfully
# [DEBUG] Docker Compose version: ...
# [DEBUG] Checking port 13000...

# 3. 停止（Ctrl+C）
```

**✅ チェックポイント**:
- [ ] DEBUG=1で詳細ログが表示される
- [ ] 内部処理の状態が確認できる

---

## 6.5 クロスプラットフォーム対応テスト

### テスト 6.5.1: macOS環境での動作確認

**目的**: macOS環境で全機能が正常に動作することを確認

```bash
# 1. OS確認
uname -s
# 期待結果: Darwin

# 2. ポートチェックコマンド確認
lsof -i :13000
# 期待結果: lsofが使用される

# 3. 全機能テスト
make dev
curl http://localhost:13000/api/health
make dev-stop

# 4. ヘルプメッセージ確認
./scripts/dev/main.sh --help
```

**✅ チェックポイント**:
- [ ] macOSで全機能が動作する
- [ ] lsofコマンドが正常に動作する

---

### テスト 6.5.2: Linux環境での動作確認

**目的**: Linux環境で全機能が正常に動作することを確認

```bash
# 1. OS確認
uname -s
# 期待結果: Linux

# 2. ポートチェックコマンド確認
ss -tlnp | grep :13000
# 期待結果: ssが使用される（lsof未インストールの場合）

# 3. 全機能テスト
make dev
curl http://localhost:13000/api/health
make dev-stop
```

**✅ チェックポイント**:
- [ ] Linuxで全機能が動作する
- [ ] ssコマンドが正常に動作する（lsof未インストール時）

---

## 6.6 パフォーマンステスト

起動・停止時間とレスポンス性能を計測します。

### テスト 6.6.1: 起動時間計測

**目的**: 起動処理が5秒以内に完了することを確認

```bash
# 1. Dockerモード起動時間計測
time make dev-docker

# 期待結果:
# - 依存関係チェック: < 2秒
# - 設定ファイル読み込み: < 1秒
# - ポートチェック: < 2秒
# - 合計: < 5秒（Docker起動時間を除く）

# 2. 停止
make dev-stop
```

**✅ チェックポイント**:
- [ ] 起動処理が5秒以内に完了する
- [ ] 各ステップの時間が妥当

---

### テスト 6.6.2: ヘルスチェック完了時間計測

**目的**: 全サービスのヘルスチェックが90秒以内に完了することを確認

```bash
# 1. 起動とヘルスチェック時間を計測
time make dev-docker

# 2. ログから確認
# [INFO] Waiting for services to be ready...
# （最大120秒待機）
# [SUCCESS] All services are healthy

# 期待結果: 90秒以内にヘルスチェック完了

# 3. 停止
make dev-stop
```

**✅ チェックポイント**:
- [ ] ヘルスチェックが90秒以内に完了する
- [ ] 全サービスがhealthy状態になる

---

### テスト 6.6.3: シャットダウン時間計測

**目的**: グレースフルシャットダウンが30秒以内に完了することを確認

```bash
# 1. 起動
make dev

# 2. シャットダウン時間計測
time make dev-stop

# 期待結果: 30秒以内に完了

# 3. 確認
docker compose ps
# 期待結果: 全サービスが停止している
```

**✅ チェックポイント**:
- [ ] シャットダウンが30秒以内に完了する
- [ ] 全サービスが正常に停止する

---

## テスト結果記録

各テストの実施結果を記録してください。

| テストID | テスト名 | 実施日 | 結果 | 備考 |
|---------|---------|--------|------|------|
| 6.1.1 | Dockerモード全サービス起動 | | ⬜ Pass / ❌ Fail | |
| 6.1.2 | ハイブリッドモード全サービス起動 | | ⬜ Pass / ❌ Fail | |
| 6.1.3 | ネイティブモード全サービス起動 | | ⬜ Pass / ❌ Fail | |
| 6.1.4 | API専用プロファイル起動 | | ⬜ Pass / ❌ Fail | |
| 6.1.5 | フロントエンド専用プロファイル起動 | | ⬜ Pass / ❌ Fail | |
| 6.1.6 | 個別サービス選択 | | ⬜ Pass / ❌ Fail | |
| 6.2.1 | ポート競合エラー | | ⬜ Pass / ❌ Fail | |
| 6.2.2 | Docker未インストールエラー | | ⬜ Pass / ❌ Fail | |
| 6.2.3 | 不正なJSON設定エラー | | ⬜ Pass / ❌ Fail | |
| 6.2.4 | 不正なプロファイル名エラー | | ⬜ Pass / ❌ Fail | |
| 6.2.5 | セットアップ未完了エラー | | ⬜ Pass / ❌ Fail | |
| 6.3.1 | SIGINT（Ctrl+C）シャットダウン | | ⬜ Pass / ❌ Fail | |
| 6.3.2 | SIGTERM シャットダウン | | ⬜ Pass / ❌ Fail | |
| 6.3.3 | シャットダウンタイムアウト | | ⬜ Pass / ❌ Fail | |
| 6.4.1 | 統合ログ表示 | | ⬜ Pass / ❌ Fail | |
| 6.4.2 | デバッグログ出力 | | ⬜ Pass / ❌ Fail | |
| 6.5.1 | macOS環境での動作確認 | | ⬜ Pass / ❌ Fail | |
| 6.5.2 | Linux環境での動作確認 | | ⬜ Pass / ❌ Fail | |
| 6.6.1 | 起動時間計測 | | ⬜ Pass / ❌ Fail | 時間: __秒 |
| 6.6.2 | ヘルスチェック完了時間計測 | | ⬜ Pass / ❌ Fail | 時間: __秒 |
| 6.6.3 | シャットダウン時間計測 | | ⬜ Pass / ❌ Fail | 時間: __秒 |

---

## トラブルシューティング

### テスト実行中の一般的な問題

#### 問題: ポートが既に使用されている

```bash
# 使用中のプロセスを確認
lsof -i :13000
lsof -i :13001
lsof -i :13002

# プロセスを停止
kill <PID>

# または全て停止
make dev-stop
docker compose down
```

#### 問題: Docker サービスが起動しない

```bash
# Dockerデーモン確認
docker info

# Docker再起動（macOS Docker Desktop）
# Docker Desktop を再起動

# コンテナとボリュームを完全削除
docker compose down -v
```

#### 問題: ネイティブプロセスが残る

```bash
# プロセス確認
ps aux | grep -E "(php artisan|npm run dev)" | grep -v grep

# 強制終了
pkill -f "php artisan serve"
pkill -f "npm run dev"
```

---

## まとめ

全テストを実施し、結果を記録してください。問題が発見された場合は、GitHub Issueを作成して報告してください。

テスト完了後は、以下を確認してください:
- [ ] 全ての統合テストがPass
- [ ] 主要なエラーケースで適切なメッセージが表示される
- [ ] グレースフルシャットダウンが正常に動作する
- [ ] ログ出力が見やすく整形されている
- [ ] パフォーマンス要件を満たしている

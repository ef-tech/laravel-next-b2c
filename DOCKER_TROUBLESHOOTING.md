# Docker環境 トラブルシューティングガイド

Next.js + Laravel Docker環境の問題解決ガイド

## 目次

- [Dockerビルドエラー](#dockerビルドエラー)
- [Docker Compose起動エラー](#docker-compose起動エラー)
- [実行時エラー](#実行時エラー)
- [ネットワーク接続エラー](#ネットワーク接続エラー)
- [Hot Reload動作不良](#hot-reload動作不良)
- [E2Eテスト接続エラー](#e2eテスト接続エラー)
- [Docker内部ネットワーク接続](#docker内部ネットワーク接続)

---

## Dockerビルドエラー

### 🚨 Next.js standaloneビルド未生成エラー

**症状**:
```
ERROR: failed to compute cache key: "/frontend/admin-app/.next/standalone" not found
COPY failed: file not found in build context or excluded by .dockerignore
```

**原因**: `next.config.ts` に `output: 'standalone'` 設定が不足、またはビルドが失敗している。

**解決方法**:

```bash
# 1. next.config.ts 確認
cat frontend/admin-app/next.config.ts | grep standalone
# output: 'standalone' の記述があることを確認

# 2. ローカルビルドテスト
cd frontend/admin-app
npm run build

# 3. standalone生成確認
ls -la .next/standalone
# → frontend/admin-app/.next/standalone ディレクトリが存在することを確認

# 4. Dockerビルド再実行
docker-compose build admin-app --no-cache
```

**予防策**:
- `next.config.ts` に必ず `output: 'standalone'` を設定
- `.dockerignore` で `.next` を除外していないか確認

---

### 🚨 Dockerfile構文エラー

**症状**:
```
ERROR: failed to solve: failed to read dockerfile: open /var/lib/docker/tmp/buildkit-mount123/Dockerfile: no such file or directory
```

**原因**: Dockerfile のパス指定が間違っている、または Dockerfile が存在しない。

**解決方法**:

```bash
# 1. Dockerfile存在確認
ls -la frontend/admin-app/Dockerfile
ls -la frontend/user-app/Dockerfile

# 2. docker-compose.yml のパス確認
cat docker-compose.yml | grep dockerfile
# → ./frontend/admin-app/Dockerfile が正しいか確認

# 3. ビルドコンテキスト確認
# docker-compose.yml の context が "." （リポジトリルート）になっているか確認
```

**予防策**:
- Dockerfile は各アプリディレクトリ直下に配置
- `context: .` でリポジトリルートをビルドコンテキストに指定

---

### 🚨 依存関係インストールエラー

**症状**:
```
npm ERR! code ERESOLVE
npm ERR! ERESOLVE unable to resolve dependency tree
```

**原因**: package.json の依存関係が競合している。

**解決方法**:

```bash
# 1. ローカルでクリーンインストール
cd frontend/admin-app
rm -rf node_modules package-lock.json
npm install

# 2. package-lock.json をコミット
git add package-lock.json
git commit -m "Fix: 📦 package-lock.json 更新"

# 3. Dockerビルド再実行
docker-compose build admin-app --no-cache
```

---

## Docker Compose起動エラー

### 🚨 ポート競合エラー

**症状**:
```
Error starting userland proxy: listen tcp4 0.0.0.0:3001: bind: address already in use
Error starting userland proxy: listen tcp4 0.0.0.0:13000: bind: address already in use
```

**原因**: 指定されたポートが既に他のプロセスで使用されている。

**解決方法**:

```bash
# 1. ポート使用状況確認
lsof -i :3000   # User App
lsof -i :3001   # Admin App
lsof -i :13000  # Laravel API

# 2. プロセス終了
kill -9 [PID]

# 3. Docker Composeプロセス確認
docker-compose ps
docker-compose down

# 4. 残留コンテナ削除
docker ps -a | grep "admin-app\|user-app\|laravel-api"
docker rm -f [CONTAINER_ID]

# 5. 再起動
docker-compose up -d
```

**予防策**:
- 開発終了時は必ず `docker-compose down` で停止
- ポート競合を避けるため、他のプロジェクトと異なるポート番号を使用

---

### 🚨 依存サービス起動待機タイムアウト

**症状**:
```
ERROR: for admin-app  Container "laravel-api" is unhealthy.
Dependency failed to start: container laravel-api is unhealthy
```

**原因**: 依存サービス（Laravel API）の起動に時間がかかりすぎている。

**解決方法**:

```bash
# 1. Laravel APIログ確認
docker-compose logs laravel-api

# 2. ヘルスチェック状態確認
docker-compose ps
# STATUS列で "healthy" か確認

# 3. Laravel API個別起動テスト
docker-compose up -d pgsql redis mailpit minio
docker-compose up laravel-api

# 4. データベース接続確認
docker-compose exec laravel-api php artisan tinker
# DB::connection()->getPdo();

# 5. 全サービス再起動
docker-compose down
docker-compose up -d
```

---

### 🚨 ボリュームマウントエラー

**症状**:
```
ERROR: for admin-app  Cannot start service admin-app:
OCI runtime create failed: container_linux.go:380: starting container process caused:
process_linux.go:545: container init caused: rootfs_linux.go:76: mounting "/host_mnt/path"
to rootfs at "/app/frontend/admin-app" caused: not a directory
```

**原因**: ホスト側のディレクトリパスが間違っている、または権限不足。

**解決方法**:

```bash
# 1. マウントパス確認
pwd
# → リポジトリルートにいることを確認

# 2. docker-compose.yml のvolumes設定確認
cat docker-compose.yml | grep -A 3 "admin-app:" | grep volumes
# → ./frontend/admin-app:/app/frontend/admin-app が正しいか確認

# 3. ディレクトリ存在確認
ls -la frontend/admin-app

# 4. 権限確認
ls -ld frontend/admin-app
# drwxr-xr-x であることを確認

# 5. Dockerデーモン再起動（macOS/Windows）
# Docker Desktop > Settings > Restart

# 6. 再ビルド
docker-compose down -v
docker-compose up -d --build
```

---

## 実行時エラー

### 🚨 Next.js起動エラー（コンテナ内）

**症状**:
```
docker logs admin-app
Error: Cannot find module '/app/frontend/admin-app/server.js'
```

**原因**: standaloneビルド成果物が正しくコピーされていない。

**解決方法**:

```bash
# 1. Dockerfile COPY確認
cat frontend/admin-app/Dockerfile | grep COPY

# 2. ビルドステージ確認
docker-compose build admin-app --progress=plain 2>&1 | grep "COPY"

# 3. コンテナ内ファイル確認
docker-compose run --rm admin-app ls -la /app/frontend/admin-app
docker-compose run --rm admin-app ls -la /app/frontend/admin-app/.next/standalone

# 4. 再ビルド（キャッシュなし）
docker-compose build admin-app --no-cache
docker-compose up -d admin-app
```

---

### 🚨 Laravel API接続エラー

**症状**:
```
docker logs laravel-api
SQLSTATE[HY000] [2002] Connection refused
```

**原因**: PostgreSQL接続設定が間違っている。

**解決方法**:

```bash
# 1. .env 確認
cat backend/laravel-api/.env | grep DB_
# DB_HOST=pgsql （Docker Composeサービス名）
# DB_PORT=5432
# DB_DATABASE=laravel
# DB_USERNAME=sail
# DB_PASSWORD=secret

# 2. PostgreSQL起動確認
docker-compose ps pgsql
# STATE列で "Up" かつ "healthy" であることを確認

# 3. PostgreSQL接続テスト
docker-compose exec pgsql psql -U sail -d laravel
# \dt で テーブル一覧表示

# 4. Laravel接続確認
docker-compose exec laravel-api php artisan tinker
# DB::connection()->getPdo();

# 5. マイグレーション再実行
docker-compose exec laravel-api php artisan migrate:fresh
```

---

## ネットワーク接続エラー

### 🚨 フロントエンド → Laravel API 接続エラー

**症状**:
```
Admin Appログ: AxiosError: connect ECONNREFUSED 127.0.0.1:13000
User Appログ: Network request failed
```

**原因**: Docker内部ネットワークでのサービス名解決が失敗している。

**解決方法**:

```bash
# 1. 環境変数確認
docker-compose exec admin-app env | grep NEXT_PUBLIC_API_URL
# NEXT_PUBLIC_API_URL=http://laravel-api:13000
# （Docker内部ではサービス名 "laravel-api" を使用）

# 2. docker-compose.yml 確認
cat docker-compose.yml | grep NEXT_PUBLIC_API_URL
# ブラウザ側: http://localhost:13000
# Docker内部: http://laravel-api:13000

# 3. ネットワーク接続テスト
docker-compose exec admin-app wget -O- http://laravel-api:13000/up
# Laravel API応答があることを確認

# 4. 環境変数再設定
# docker-compose.yml の environment セクションを修正
docker-compose down
docker-compose up -d
```

---

### 🚨 CORS エラー（ブラウザコンソール）

**症状**:
```
Access to XMLHttpRequest at 'http://localhost:13000/api/users' from origin 'http://localhost:3001'
has been blocked by CORS policy: No 'Access-Control-Allow-Origin' header is present
```

**原因**: Laravel API の CORS 設定が不足している。

**解決方法**:

```bash
# 1. Laravel CORS設定確認
cat backend/laravel-api/config/cors.php

# 2. .env で許可オリジン設定
cat backend/laravel-api/.env | grep SANCTUM_STATEFUL_DOMAINS
# SANCTUM_STATEFUL_DOMAINS=localhost,localhost:3000,localhost:3001,127.0.0.1,127.0.0.1:3000,127.0.0.1:3001

# 3. Laravel再起動
docker-compose restart laravel-api

# 4. ブラウザキャッシュクリア
# Chrome DevTools > Network > Disable cache
```

---

## Hot Reload動作不良

### 🚨 ファイル変更が反映されない

**症状**: Next.jsソースコード変更が自動的にブラウザに反映されない。

**原因**: Docker volumes設定が不適切、またはHot Reload機能が無効。

**解決方法**:

```bash
# 1. volumes設定確認
cat docker-compose.yml | grep -A 3 "admin-app:" | grep volumes
# → ./frontend/admin-app:/app/frontend/admin-app
# → /app/frontend/admin-app/node_modules （匿名ボリューム）
# → /app/frontend/admin-app/.next （匿名ボリューム）

# 2. 匿名ボリューム確認
docker volume ls | grep admin-app

# 3. ファイル変更検知テスト
# ホスト側でファイル変更
echo "// test" >> frontend/admin-app/src/app/page.tsx

# コンテナ側で変更確認
docker-compose exec admin-app cat /app/frontend/admin-app/src/app/page.tsx
# → "// test" が追加されていることを確認

# 4. Next.js開発サーバーログ確認
docker-compose logs -f admin-app
# → "compiled client and server successfully" メッセージが表示されるか確認

# 5. Hot Reload無効の場合、環境変数追加
# docker-compose.yml の admin-app service に追加:
# environment:
#   - WATCHPACK_POLLING=true
docker-compose up -d admin-app
```

---

## E2Eテスト接続エラー

### 🚨 Playwright が Next.jsアプリに接続できない

**症状**:
```
docker-compose run --rm e2e-tests
TimeoutError: page.goto: Timeout 30000ms exceeded.
```

**原因**: E2Eテストサービスがフロントエンドサービスに接続できていない。

**解決方法**:

```bash
# 1. フロントエンド起動確認
docker-compose ps admin-app user-app
# STATE列で "Up" であることを確認

# 2. E2E環境変数確認
cat docker-compose.yml | grep -A 10 "e2e-tests:" | grep E2E_
# E2E_ADMIN_URL=http://admin-app:3001
# E2E_USER_URL=http://user-app:3000

# 3. Docker内部URL接続テスト
docker-compose run --rm e2e-tests sh -c "wget -O- http://admin-app:3001"
docker-compose run --rm e2e-tests sh -c "wget -O- http://user-app:3000"

# 4. depends_on 設定確認
cat docker-compose.yml | grep -A 5 "e2e-tests:" | grep depends_on
# - admin-app
# - user-app
# - laravel-api

# 5. E2Eテスト再実行
docker-compose run --rm e2e-tests
```

---

### 🚨 Playwright ブラウザインストールエラー

**症状**:
```
browserType.launch: Executable doesn't exist at /ms-playwright/chromium-1084/chrome-linux/chrome
```

**原因**: Playwrightブラウザが正しくインストールされていない。

**解決方法**:

```bash
# 1. Playwright公式イメージ確認
cat docker-compose.yml | grep "playwright:"
# image: mcr.microsoft.com/playwright:v1.47.2-jammy

# 2. コマンド確認
cat docker-compose.yml | grep -A 5 "e2e-tests:" | grep command
# npx playwright install --with-deps が含まれているか確認

# 3. 手動インストール
docker-compose run --rm e2e-tests sh -c "npx playwright install --with-deps chromium"

# 4. イメージ再ビルド
docker-compose down
docker-compose build e2e-tests --no-cache
docker-compose run --rm e2e-tests
```

---

## 一般的なトラブルシューティングコマンド

### デバッグ用コマンド集

```bash
# 全サービス状態確認
docker-compose ps

# 特定サービスログ確認
docker-compose logs -f [service-name]

# コンテナ内シェル起動
docker-compose exec [service-name] sh
docker-compose run --rm [service-name] sh

# ネットワーク確認
docker network ls
docker network inspect laravel-next-b2c_app-network

# ボリューム確認
docker volume ls
docker volume inspect laravel-next-b2c_sail-pgsql

# 完全クリーンアップ
docker-compose down -v --remove-orphans
docker system prune -a --volumes

# 再ビルド（キャッシュなし）
docker-compose build --no-cache
docker-compose up -d
```

---

## サポート

問題が解決しない場合は、以下の情報を添えて GitHub Issues で報告してください：

1. **環境情報**:
   - OS: macOS / Windows / Linux
   - Docker version: `docker --version`
   - Docker Compose version: `docker compose version`

2. **エラーログ**:
   ```bash
   docker-compose logs [service-name] > error.log
   ```

3. **再現手順**: 問題が発生するまでの具体的な操作手順

4. **試した解決策**: このドキュメントで試した内容

---

## Docker内部ネットワーク接続

### 🚨 PostgreSQL/Redis にホストから接続できない

**症状**:
```
psql: error: connection to server at "127.0.0.1", port 5432 failed
redis-cli -h 127.0.0.1 -p 6379: Could not connect to Redis
```

**原因**: PostgreSQL と Redis はDocker内部ネットワーク専用のため、ホストに公開されていません。

**解決方法**:

#### 方法1: Docker経由でアクセス（推奨）

```bash
# PostgreSQL
docker compose exec pgsql psql -U sail -d laravel

# Redis
docker compose exec redis redis-cli
```

#### 方法2: 一時的にポート公開

開発中にホストから直接接続が必要な場合は、`docker-compose.yml` を一時的に変更します:

```yaml
# docker-compose.yml
pgsql:
  ports:
    - '5432:5432'  # 一時的に追加

redis:
  ports:
    - '6379:6379'  # 一時的に追加
```

その後、Docker環境を再起動:

```bash
docker compose down
docker compose up -d --profile infra --profile api
```

**注意**: この設定は Git Worktree 並列開発時にポート衝突を引き起こす可能性があります。作業完了後は設定を元に戻してください。

### 🚨 Laravel API からデータベース接続エラー

**症状**:
```
SQLSTATE[08006] [7] could not translate host name "127.0.0.1" to address
SQLSTATE[HY000] [2002] Connection refused
```

**原因**: `.env` ファイルで `DB_HOST=127.0.0.1` を使用している（Docker環境では service 名を使用する必要があります）。

**解決方法**:

`backend/laravel-api/.env` を確認・修正:

```bash
# ❌ 間違った設定（ホスト環境用）
DB_HOST=127.0.0.1
DB_PORT=5432
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

# ✅ 正しい設定（Docker環境用）
DB_HOST=pgsql         # service名
DB_PORT=5432          # 内部ポート
REDIS_HOST=redis      # service名
REDIS_PORT=6379       # 内部ポート
MAIL_HOST=mailpit     # service名
MAIL_PORT=1025        # 内部ポート
AWS_ENDPOINT=http://minio:9000  # service名
```

設定変更後、Docker環境を再起動:

```bash
docker compose restart laravel-api
```

### 🚨 Git Worktree でポート衝突エラー

**症状**:
```
Error: Bind for 0.0.0.0:14000 failed: port is already allocated
```

**原因**: 複数の Worktree が同じポート番号を使用している。

**解決方法**:

1. **既存の Worktree のポート確認**:

```bash
make worktree-ports
```

2. **新規 Worktree 作成時に WORKTREE_ID を指定**:

```bash
# Worktree 1を作成（ポートレンジ: 13001, 13101, 13201...）
make worktree-create WORKTREE_ID=1 BRANCH=feature/new-feature
```

**注意**: 内部ネットワーク最適化により、PostgreSQL と Redis のポート衝突は完全に解消されています。外部公開ポート（Laravel API、Next.js、Mailpit、MinIO）のみ管理が必要です。

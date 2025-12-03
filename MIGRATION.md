# Git Worktree並列開発環境 - 移行ガイド

## 概要

このドキュメントは、Git Worktree並列開発環境導入に伴う**Breaking Change**の移行手順を説明します。

### Breaking Changeの内容

ポート番号レンジ分離方式の採用により、**全サービスのポート番号が変更**されます。これにより、5-8個のWorktreeで同時にDocker環境を起動できるようになります。

### 影響を受けるユーザー

以下のユーザーは移行作業が必要です：

- ✅ **既に開発環境を構築済みのユーザー** - `.env`ファイルとDocker環境の再構築が必要
- ✅ **ブラウザブックマークでポート番号を保存しているユーザー** - ブックマークURLの更新が必要
- ✅ **カスタムスクリプトでポート番号をハードコードしているユーザー** - スクリプトの更新が必要

### ⚠️ 重要な注意事項

- 移行作業は**約15分**かかります
- 既存のDocker環境を**完全に停止**してから移行してください
- `.env`ファイルのバックアップを**必ず**作成してください
- 移行後は**ブラウザブックマーク**の更新を忘れないでください

---

## 主な変更点

### ポート番号変更一覧

| サービス | 変更前 | 変更後 | 理由 |
|---------|--------|--------|------|
| **Laravel API** | 13000 | **13000** | 変更なし（ベースポート） |
| **User App** | 13001 | **13100** | レンジ分離（13100-13199） |
| **Admin App** | 13002 | **13200** | レンジ分離（13200-13299） |
| **MinIO Console** | 13010 | **13300** | レンジ分離（13300-13399） |
| **PostgreSQL** | 13432 | **14000** | レンジ分離（14000-14099） |
| **Redis** | 13379 | **14100** | レンジ分離（14100-14199） |
| **Mailpit UI** | 13025 | **14200** | レンジ分離（14200-14299） |
| **Mailpit SMTP** | 11025 | **14300** | レンジ分離（14300-14399） |
| **MinIO API** | 13900 | **14400** | レンジ分離（14400-14499） |

### 新規追加された設定

- **WORKTREE_ID**: Worktree識別子（デフォルト: 0）
- **COMPOSE_PROJECT_NAME**: Docker Composeプロジェクト名（動的設定）
- **DB_DATABASE**: PostgreSQLデータベース名（動的設定）
- **CACHE_PREFIX**: Redisキャッシュプレフィックス（動的設定）

### アクセスURL変更

```bash
# 変更前
User App:   http://localhost:13001
Admin App:  http://localhost:13002
Mailpit UI: http://localhost:13025
MinIO UI:   http://localhost:13010

# 変更後
User App:   http://localhost:13100
Admin App:  http://localhost:13200
Mailpit UI: http://localhost:14200
MinIO UI:   http://localhost:13300
```

---

## 移行手順（8フェーズ）

### フェーズ1: 事前準備

#### ステップ1.1: 作業中の変更を退避

```bash
# 現在のブランチを確認
git branch

# 作業中の変更がある場合は退避
git stash push -m "Before worktree migration"
```

#### ステップ1.2: 既存Docker環境を完全停止

```bash
# プロジェクトルートディレクトリに移動
cd /path/to/laravel-next-b2c

# Docker環境を停止
make stop

# コンテナが完全に停止したことを確認
docker ps
# ↑ laravel-next-b2c関連のコンテナが表示されないことを確認
```

#### ステップ1.3: 現在のポート番号をメモ

後でブックマークを更新する際に参照するため、現在使用中のポート番号をメモしてください。

```bash
# 現在の.envファイルを確認
grep -E "PORT|URL" .env | grep -v "^#"
```

---

### フェーズ2: コード更新

#### ステップ2.1: 最新コードを取得

```bash
# mainブランチに移動
git checkout main

# 最新コードを取得
git pull origin main

# マージコンフリクトが発生した場合は解決してください
```

#### ステップ2.2: 変更内容を確認

```bash
# .env.exampleの変更を確認
git diff HEAD~1 .env.example

# docker-compose.ymlの変更を確認
git diff HEAD~1 docker-compose.yml

# Makefileの新規コマンドを確認
git diff HEAD~1 Makefile
```

---

### フェーズ3: 環境変数更新

#### ステップ3.1: .envファイルをバックアップ

```bash
# 現在の.envをバックアップ
cp .env .env.backup.$(date +%Y%m%d_%H%M%S)

# バックアップが作成されたことを確認
ls -la .env.backup.*
```

#### ステップ3.2: 新しい.env.exampleをコピー

```bash
# 新しい.env.exampleをコピー
cp .env.example .env
```

#### ステップ3.3: 秘密情報を復元

以下の環境変数を`.env.backup.*`から`.env`にコピーしてください：

```bash
# バックアップファイルから秘密情報を抽出
grep -E "^(APP_KEY|DB_PASSWORD|MAIL_FROM_ADDRESS)" .env.backup.* > secret.tmp

# 手動で.envに反映（または以下のコマンドを実行）
# APP_KEY
sed -i '' "s|^APP_KEY=.*|$(grep APP_KEY secret.tmp)|" .env

# DB_PASSWORD（カスタマイズしている場合）
# sed -i '' "s|^DB_PASSWORD=.*|$(grep DB_PASSWORD secret.tmp)|" .env

# 一時ファイル削除
rm secret.tmp
```

#### ステップ3.4: ポート番号を確認

新しい`.env`ファイルに以下のポート番号が設定されていることを確認してください：

```bash
# 確認コマンド
grep -E "^(APP_PORT|FORWARD_.*_PORT|E2E_.*_URL)" .env
```

期待される出力：
```bash
APP_PORT=13000
FORWARD_DB_PORT=14000
FORWARD_REDIS_PORT=14100
FORWARD_MAILPIT_PORT=14300
FORWARD_MAILPIT_DASHBOARD_PORT=14200
FORWARD_MINIO_PORT=14400
FORWARD_MINIO_CONSOLE_PORT=13300
E2E_USER_URL=http://localhost:13100
E2E_ADMIN_URL=http://localhost:13200
E2E_API_URL=http://localhost:13000
```

---

### フェーズ4: フロントエンド環境変数更新

#### ステップ4.1: User App環境変数を更新

```bash
# user-app/.env.localを作成
cat > frontend/user-app/.env.local <<EOF
NEXT_PUBLIC_API_URL=http://localhost:13000
NEXT_PUBLIC_API_BASE_URL=http://localhost:13000
NEXT_PUBLIC_API_V1_BASE_URL=http://localhost:13000/api/v1
EOF

# 内容を確認
cat frontend/user-app/.env.local
```

#### ステップ4.2: Admin App環境変数を更新

```bash
# admin-app/.env.localを作成
cat > frontend/admin-app/.env.local <<EOF
NEXT_PUBLIC_API_URL=http://localhost:13000
NEXT_PUBLIC_API_BASE_URL=http://localhost:13000
NEXT_PUBLIC_API_V1_BASE_URL=http://localhost:13000/api/v1
EOF

# 内容を確認
cat frontend/admin-app/.env.local
```

---

### フェーズ5: Docker環境再構築

#### ステップ5.1: Docker環境を起動

```bash
# Docker環境を起動
make dev

# 起動ログを確認（別ターミナル）
docker compose logs -f
```

#### ステップ5.2: コンテナの起動を確認

```bash
# コンテナ一覧を確認
docker ps

# 以下のコンテナが起動していることを確認
# - laravel-next-b2c-laravel-api
# - laravel-next-b2c-pgsql
# - laravel-next-b2c-redis
# - laravel-next-b2c-mailpit
# - laravel-next-b2c-minio
```

#### ステップ5.3: エラーログを確認

```bash
# Laravel APIのログを確認
docker logs laravel-next-b2c-laravel-api | tail -20

# PostgreSQLのログを確認
docker logs laravel-next-b2c-pgsql | tail -20

# エラーがある場合は後述のトラブルシューティングを参照
```

---

### フェーズ6: 動作確認

#### ステップ6.1: Laravel APIの動作確認

```bash
# ヘルスチェック
curl http://localhost:13000/api/health

# 期待される出力
# {"status":"ok"}
```

#### ステップ6.2: PostgreSQLの接続確認

```bash
# PostgreSQL接続テスト
docker exec -it laravel-next-b2c-pgsql psql -U sail -d laravel -c "SELECT 1;"

# 期待される出力
#  ?column?
# ----------
#         1
# (1 row)
```

#### ステップ6.3: Redisの接続確認

```bash
# Redis接続テスト
docker exec -it laravel-next-b2c-redis redis-cli PING

# 期待される出力
# PONG
```

#### ステップ6.4: フロントエンドアプリの起動

```bash
# Terminal 2: User App起動
cd frontend/user-app
npm run dev

# 起動後、ブラウザで http://localhost:13100 にアクセス
```

```bash
# Terminal 3: Admin App起動
cd frontend/admin-app
npm run dev

# 起動後、ブラウザで http://localhost:13200 にアクセス
```

#### ステップ6.5: ブラウザで動作確認

以下のURLにアクセスして、正常に表示されることを確認してください：

- ✅ User App: http://localhost:13100
- ✅ Admin App: http://localhost:13200
- ✅ Mailpit UI: http://localhost:14200
- ✅ MinIO Console: http://localhost:13300

---

### フェーズ7: ブックマーク更新

#### ステップ7.1: ブラウザブックマークを更新

以下のURLを新しいポート番号に更新してください：

| 項目 | 変更前 | 変更後 |
|------|--------|--------|
| User App | http://localhost:13001 | **http://localhost:13100** |
| Admin App | http://localhost:13002 | **http://localhost:13200** |
| Mailpit UI | http://localhost:13025 | **http://localhost:14200** |
| MinIO Console | http://localhost:13010 | **http://localhost:13300** |

#### ステップ7.2: カスタムスクリプトを更新

もしカスタムスクリプト（テストスクリプト、デプロイスクリプト等）でポート番号をハードコードしている場合は、新しいポート番号に更新してください。

```bash
# ポート番号をハードコードしているファイルを検索
grep -r "13001\|13002\|13025\|13010\|13432\|13379\|11025\|13900" . \
  --exclude-dir=node_modules \
  --exclude-dir=vendor \
  --exclude-dir=.git \
  --exclude="*.log"
```

---

### フェーズ8: Worktree機能試用（オプション）

新しいWorktree機能を試してみましょう。

#### ステップ8.1: テスト用Worktreeを作成

```bash
# テスト用ブランチ作成
git checkout -b test/worktree-migration

# Worktree作成
make worktree-create BRANCH=test/worktree-migration

# 出力例
# ✅ Worktree作成完了！
#    Worktree ID: 0
#    Worktree Path: /Users/okumura/worktrees/wt0
#    Laravel API: http://localhost:13000
#    User App: http://localhost:13100
#    Admin App: http://localhost:13200
```

#### ステップ8.2: ポート番号一覧を確認

```bash
# 全Worktreeのポート番号を表示
make worktree-ports

# 出力例
# ┌─────────────┬────────────────┬─────────────┬──────────┬──────────┬─────────────┐
# │ Worktree ID │ Laravel API    │ User App    │ Admin App│ PostgreSQL│ Redis       │
# ├─────────────┼────────────────┼─────────────┼──────────┼──────────┼─────────────┤
# │ 0           │ 13000          │ 13100       │ 13200    │ 14000    │ 14100       │
# └─────────────┴────────────────┴─────────────┴──────────┴──────────┴─────────────┘
```

#### ステップ8.3: 並列起動を確認

```bash
# Worktree内でDocker起動
cd ~/worktrees/wt0
make dev

# 別ターミナルでメインブランチもDocker起動
cd /path/to/laravel-next-b2c
make dev

# 両方のDocker環境が同時に起動していることを確認
docker ps
```

#### ステップ8.4: テストWorktreeを削除

```bash
# プロジェクトルートに戻る
cd /path/to/laravel-next-b2c

# Worktree + Docker完全削除（推奨）
make worktree-clean ID=0

# または、Worktree削除のみ
make worktree-remove PATH=~/worktrees/wt0

# 確認
make worktree-list
```

---

## 移行チェックリスト

移行作業が完了したら、以下のチェックリストで確認してください。

### 事前準備
- [ ] 作業中の変更を`git stash`で退避した
- [ ] 既存Docker環境を完全停止した（`make stop`）
- [ ] 現在のポート番号をメモした

### コード更新
- [ ] 最新コードを取得した（`git pull origin main`）
- [ ] `.env.example`の変更を確認した
- [ ] `docker-compose.yml`の変更を確認した
- [ ] `Makefile`の新規コマンドを確認した

### 環境変数更新
- [ ] `.env`ファイルをバックアップした（`.env.backup.*`）
- [ ] 新しい`.env.example`をコピーした
- [ ] `APP_KEY`等の秘密情報を復元した
- [ ] ポート番号が新レンジになっていることを確認した
  - [ ] `APP_PORT=13000` ✅
  - [ ] `FORWARD_DB_PORT=14000` ✅
  - [ ] `FORWARD_REDIS_PORT=14100` ✅
  - [ ] `FORWARD_MAILPIT_DASHBOARD_PORT=14200` ✅
  - [ ] `FORWARD_MAILPIT_PORT=14300` ✅
  - [ ] `FORWARD_MINIO_PORT=14400` ✅
  - [ ] `FORWARD_MINIO_CONSOLE_PORT=13300` ✅

### フロントエンド設定
- [ ] `frontend/user-app/.env.local`を作成した
- [ ] `frontend/admin-app/.env.local`を作成した
- [ ] フロントエンドの環境変数に新しいポート番号を設定した

### Docker環境再構築
- [ ] `make dev`でDocker環境を起動した
- [ ] 全コンテナが正常起動した（`docker ps`）
- [ ] エラーログがない（`docker logs <container-name>`）

### 動作確認
- [ ] Laravel APIヘルスチェック成功（`curl http://localhost:13000/api/health`）
- [ ] PostgreSQL接続成功（`docker exec -it laravel-next-b2c-pgsql psql ...`）
- [ ] Redis接続成功（`docker exec -it laravel-next-b2c-redis redis-cli PING`）
- [ ] User Appにブラウザでアクセス成功（http://localhost:13100）
- [ ] Admin Appにブラウザでアクセス成功（http://localhost:13200）
- [ ] Mailpit UIにブラウザでアクセス成功（http://localhost:14200）
- [ ] MinIO Consoleにブラウザでアクセス成功（http://localhost:13300）

### ブックマーク更新
- [ ] User Appのブックマークを更新した（13001 → 13100）
- [ ] Admin Appのブックマークを更新した（13002 → 13200）
- [ ] Mailpit UIのブックマークを更新した（13025 → 14200）
- [ ] MinIO Consoleのブックマークを更新した（13010 → 13300）

### カスタムスクリプト更新
- [ ] カスタムスクリプトのポート番号をハードコード検索した
- [ ] 該当スクリプトを新しいポート番号に更新した

### Worktree機能試用
- [ ] テスト用Worktreeを作成した（`make worktree-create`）
- [ ] ポート番号一覧を確認した（`make worktree-ports`）
- [ ] 並列起動を確認した（メインブランチとWorktree同時起動）
- [ ] テストWorktreeを削除した（`make worktree-clean ID=0` または `make worktree-remove`）

---

## トラブルシューティング

### 問題1: ポート番号競合エラー

#### 症状
```
Error: Port 13000 is already in use
```

#### 原因
既存のプロセスが該当ポート番号を使用している。

#### 解決策
```bash
# ポート番号を使用中のプロセスを確認
lsof -i :13000

# 出力例
# COMMAND   PID    USER   FD   TYPE DEVICE SIZE/OFF NODE NAME
# node      12345  user   20u  IPv4  0x...      0t0  TCP *:13000 (LISTEN)

# プロセスを終了
kill 12345

# Docker再起動
make dev
```

---

### 問題2: Docker起動失敗

#### 症状
```
Error: Cannot start service laravel-api
Error: failed to start containers: laravel-api
```

#### 原因
- `.env`ファイルが存在しない
- `.env`ファイルの設定値が不正
- Docker Composeの設定エラー

#### 解決策
```bash
# 1. .envファイルの存在確認
ls -la .env
# ファイルが存在しない場合
cp .env.example .env

# 2. Docker Compose設定を検証
docker compose config
# エラーがある場合は該当箇所を修正

# 3. Dockerログを確認
docker compose logs

# 4. Docker環境を完全にリセット
make clean
make dev
```

---

### 問題3: PostgreSQL接続エラー

#### 症状
```
SQLSTATE[08006] [7] could not connect to server
```

#### 原因
- PostgreSQLコンテナが起動していない
- `.env`のDB設定が不正
- ポート番号が間違っている

#### 解決策
```bash
# 1. PostgreSQLコンテナの状態確認
docker ps | grep pgsql

# コンテナが起動していない場合
docker compose up -d pgsql

# 2. PostgreSQLログを確認
docker logs laravel-next-b2c-pgsql

# 3. .envのDB設定を確認
grep -E "^DB_" .env

# 期待される出力
# DB_CONNECTION=pgsql
# DB_HOST=127.0.0.1
# DB_PORT=14000  # <- 新しいポート番号
# DB_DATABASE=laravel
# DB_USERNAME=sail
# DB_PASSWORD=secret

# 4. PostgreSQL手動接続テスト
docker exec -it laravel-next-b2c-pgsql psql -U sail -d laravel
```

---

### 問題4: Redis接続エラー

#### 症状
```
Connection refused [tcp://127.0.0.1:14100]
```

#### 原因
- Redisコンテナが起動していない
- `.env`のRedis設定が不正
- ポート番号が間違っている

#### 解決策
```bash
# 1. Redisコンテナの状態確認
docker ps | grep redis

# コンテナが起動していない場合
docker compose up -d redis

# 2. Redisログを確認
docker logs laravel-next-b2c-redis

# 3. .envのRedis設定を確認
grep -E "^REDIS_|^FORWARD_REDIS_PORT" .env

# 期待される出力
# REDIS_HOST=127.0.0.1
# REDIS_PASSWORD=null
# REDIS_PORT=14100  # <- 新しいポート番号
# FORWARD_REDIS_PORT=14100

# 4. Redis手動接続テスト
docker exec -it laravel-next-b2c-redis redis-cli PING
```

---

### 問題5: フロントエンドビルドエラー

#### 症状
```
Error: Invalid environment variable NEXT_PUBLIC_API_URL
Error: connect ECONNREFUSED 127.0.0.1:13001
```

#### 原因
- `.env.local`ファイルが存在しない
- 環境変数に古いポート番号が設定されている
- Next.jsキャッシュが古い

#### 解決策
```bash
# 1. .env.localファイルを確認
cat frontend/user-app/.env.local
cat frontend/admin-app/.env.local

# 期待される内容
# NEXT_PUBLIC_API_URL=http://localhost:13000
# NEXT_PUBLIC_API_BASE_URL=http://localhost:13000
# NEXT_PUBLIC_API_V1_BASE_URL=http://localhost:13000/api/v1

# 2. Next.jsキャッシュをクリア
rm -rf frontend/user-app/.next
rm -rf frontend/admin-app/.next

# 3. 依存関係を再インストール
cd frontend/user-app && npm install
cd frontend/admin-app && npm install

# 4. 再ビルド
cd frontend/user-app && npm run build
cd frontend/admin-app && npm run build

# 5. 開発サーバー再起動
cd frontend/user-app && npm run dev
cd frontend/admin-app && npm run dev
```

---

### 問題6: Worktree作成エラー

#### 症状
```
Error: Maximum number of worktrees (8) reached
Error: Port 13000 is already in use by another worktree
```

#### 原因
- 既に8個のWorktreeが作成されている（上限）
- ポート番号が重複している

#### 解決策
```bash
# 1. 既存Worktreeを確認
make worktree-list

# 出力例
# /path/to/laravel-next-b2c  abc1234 [main]
# /Users/okumura/worktrees/wt0  def5678 [feature/auth]
# /Users/okumura/worktrees/wt1  ghi9012 [feature/payment]

# 2. 不要なWorktreeを削除（Docker + Worktree完全削除推奨）
make worktree-clean ID=0
# または
make worktree-remove PATH=/Users/okumura/worktrees/wt0

# 3. ポート番号一覧を確認
make worktree-ports

# 4. 再度Worktree作成
make worktree-create BRANCH=feature/new-feature
```

---

## ロールバック手順

移行に失敗した場合、以下の手順で元の環境に戻すことができます。

### ステップ1: Docker環境を停止

```bash
# Docker環境を停止
make stop

# コンテナが完全に停止したことを確認
docker ps
```

### ステップ2: コードを移行前のコミットに戻す

```bash
# コミット履歴を確認
git log --oneline -10

# 移行前のコミットハッシュを特定（例: abc1234）
# コミットメッセージに「Feat: 🌳📝 Git Worktree並列開発環境仕様初期化」の直前

# 該当コミットに戻す
git checkout abc1234

# または、特定のブランチに戻す
git checkout main
git reset --hard origin/main
```

### ステップ3: .envファイルをバックアップから復元

```bash
# バックアップファイルを確認
ls -la .env.backup.*

# 最新のバックアップを復元
cp .env.backup.YYYYMMDD_HHMMSS .env

# 内容を確認
cat .env
```

### ステップ4: Docker環境を再起動

```bash
# Docker環境を起動
make dev

# コンテナの起動を確認
docker ps

# ログを確認
docker compose logs
```

### ステップ5: 動作確認

```bash
# Laravel APIヘルスチェック（旧ポート番号）
curl http://localhost:13000/api/health

# User App（旧ポート番号）
# ブラウザで http://localhost:13001 にアクセス

# Admin App（旧ポート番号）
# ブラウザで http://localhost:13002 にアクセス
```

### ステップ6: フロントエンド環境変数を復元

```bash
# 旧ポート番号に戻す
cat > frontend/user-app/.env.local <<EOF
NEXT_PUBLIC_API_URL=http://localhost:13000
EOF

cat > frontend/admin-app/.env.local <<EOF
NEXT_PUBLIC_API_URL=http://localhost:13000
EOF

# フロントエンドを再起動
cd frontend/user-app && npm run dev
cd frontend/admin-app && npm run dev
```

---

## サポート情報

### ドキュメント参照

- **README.md**: 並列開発環境の詳細説明とクイックスタートガイド
- **.kiro/specs/git-worktree-parallel-development/**: 仕様書（要件・設計・タスク）
- **Makefile**: 利用可能なコマンド一覧（`make help`）

### コマンドリファレンス

```bash
# ヘルプ表示
make help

# Worktree管理コマンド
make worktree-create BRANCH=feature/xxx [FROM=origin/main]  # Worktree作成
make worktree-list                                           # Worktree一覧
make worktree-ports                                          # ポート番号一覧
make worktree-remove PATH=<path>                             # Worktree削除のみ
make worktree-clean ID=<id or path>                          # Worktree + Docker完全削除（推奨）

# Docker管理コマンド
make dev          # Docker起動
make stop         # Docker停止
make clean        # Docker完全削除
make ps           # コンテナ一覧
make logs         # ログ表示

# テスト実行
make test         # 全テスト実行
```

### よくある質問

#### Q1: 移行後、古いポート番号でアクセスできますか？
A1: いいえ。ポート番号レンジ分離方式の導入により、全サービスが新しいポート番号に変更されています。ブックマークとスクリプトを必ず更新してください。

#### Q2: 既存のデータベースはどうなりますか？
A2: データベース名は変更されません（デフォルト: `laravel`）。ポート番号のみが変更されます（13432 → 14000）。

#### Q3: 複数のWorktreeを同時に起動できますか？
A3: はい。最大8個まで同時起動可能です。各Worktreeは独立したポート番号、データベース、Redisキャッシュを持ちます。

#### Q4: メモリはどれくらい必要ですか？
A4: 1Worktreeあたり約1GBです。5-8個のWorktreeを同時起動する場合は、16GB RAM以上を推奨します。

#### Q5: 移行作業にどれくらい時間がかかりますか？
A5: 通常15分程度です。Docker環境の再構築とフロントエンドのビルドに時間がかかります。

---

## まとめ

移行作業は以下の8フェーズで構成されています：

1. ✅ **事前準備**: 作業退避、Docker停止、ポート番号メモ
2. ✅ **コード更新**: 最新コード取得、変更内容確認
3. ✅ **環境変数更新**: .envバックアップ、新設定適用、秘密情報復元
4. ✅ **フロントエンド設定**: .env.local作成、API URL更新
5. ✅ **Docker環境再構築**: Docker起動、コンテナ確認、ログ確認
6. ✅ **動作確認**: API/DB/Redis/フロントエンド動作確認
7. ✅ **ブックマーク更新**: ブラウザブックマーク、カスタムスクリプト更新
8. ✅ **Worktree機能試用**: テストWorktree作成、並列起動確認

移行チェックリストを使用して、全ての項目が完了していることを確認してください。

問題が発生した場合は、トラブルシューティングセクションを参照するか、ロールバック手順で元の環境に戻すことができます。

**移行作業、お疲れさまでした！🎉**

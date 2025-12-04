# Requirements Document

## はじめに

本ドキュメントは、git worktreeを使った並列開発環境構築の要件を定義します。現在、単一ブランチでの開発のみ対応しており、テスト実行中（2-3分）やCI/CD待ち（5-10分）の間、別ブランチで作業できないという課題があります。

この機能により、Claude Codeを複数ブランチで同時実行できるようになり、5-8個のworktreeでDocker環境を衝突なく同時起動できます。ポート番号レンジ分離方式を採用し、データベース・ネットワーク・キャッシュを完全分離する設計により、並列開発の生産性が大幅に向上します。

**ビジネス価値:**
- 開発スピード向上（テスト実行中に別タスク着手可能）
- 複数spec並行開発の効率化
- CI/CD待ち時間の有効活用

---

## Requirements

### Requirement 1: ポート番号レンジ分離アーキテクチャ

**Objective:** 開発者として、複数のworktreeで同時にDocker環境を起動したい。これにより、ポート番号衝突を完全に回避し、99個までのworktreeを同時実行できるようにする。

#### Acceptance Criteria

1. WHEN 開発者が新しいworktreeを作成する THEN システムは各サービスに固有のポート番号レンジを割り当てなければならない
   - Laravel API: 13000-13099
   - User App: 13100-13199
   - Admin App: 13200-13299
   - MinIO Console: 13300-13399
   - PostgreSQL: 14000-14099
   - Redis: 14100-14199
   - Mailpit UI: 14200-14299
   - Mailpit SMTP: 14300-14399
   - MinIO API: 14400-14499

2. WHEN worktree IDが指定される THEN システムは計算式 `base_port + worktree_id` でポート番号を算出しなければならない

3. IF worktree IDが0の場合 THEN システムは各サービスのベースポート番号を使用しなければならない
   - 例: Laravel API = 13000, User App = 13100, Admin App = 13200

4. IF worktree IDが1の場合 THEN システムは各サービスのベースポート番号+1を使用しなければならない
   - 例: Laravel API = 13001, User App = 13101, Admin App = 13201

5. WHEN 開発者がポート番号を確認する THEN システムはworktree IDから即座にポート番号を逆算できなければならない

6. WHEN 複数のworktree（最大99個）が同時に起動している THEN システムはポート番号衝突が発生してはならない

---

### Requirement 2: 完全分離アーキテクチャ - データベース分離

**Objective:** 開発者として、各worktreeで独立したデータベース環境を持ちたい。これにより、マイグレーション競合やテストデータ競合を回避できるようにする。

#### Acceptance Criteria

1. WHEN 新しいworktreeが作成される THEN システムは動的なデータベース名を設定しなければならない
   - 形式: `laravel_wt{WORKTREE_ID}` (例: laravel_wt1, laravel_wt2)
   - メインブランチ: `laravel_main`

2. WHEN worktree環境変数ファイル（.env）が生成される THEN システムは `DB_DATABASE` を動的データベース名に設定しなければならない

3. WHEN worktreeセットアップスクリプトが実行される THEN システムはPostgreSQLに新規データベースを自動作成しなければならない
   - 実行コマンド: `CREATE DATABASE laravel_wt1`

4. IF 単一PostgreSQLインスタンスを共有する場合 THEN システムはDB名で完全分離を保証しなければならない

5. WHEN テストDB環境が必要な場合 THEN システムはテストDB名を `{DB_DATABASE}_test` 形式で生成しなければならない
   - 例: `laravel_wt1_test`

6. WHEN 異なるworktreeでマイグレーションを実行する THEN システムは他のworktreeのデータベースに影響を与えてはならない

---

### Requirement 3: 完全分離アーキテクチャ - Redisキャッシュ/セッション分離

**Objective:** 開発者として、各worktreeで独立したキャッシュ・セッション環境を持ちたい。これにより、キー名前空間衝突を完全に回避できるようにする。

#### Acceptance Criteria

1. WHEN 新しいworktreeが作成される THEN システムは動的なキャッシュプレフィックスを設定しなければならない
   - 形式: `wt{WORKTREE_ID}_` (例: wt1_, wt2_)

2. WHEN worktree環境変数ファイル（.env）が生成される THEN システムは `CACHE_PREFIX` を動的プレフィックスに設定しなければならない

3. WHEN キャッシュキーが生成される THEN システムはプレフィックス付きキー名を使用しなければならない
   - キャッシュ: `wt1_cache:key_name`
   - セッション: `wt1_session:session_id`
   - レート制限: `wt1_ratelimit:ip_address`

4. WHEN 異なるworktreeが同時にRedisを使用する THEN システムはキー衝突が発生してはならない

5. IF worktree IDが異なる場合 THEN システムは完全に独立したキャッシュ名前空間を提供しなければならない

---

### Requirement 4: 完全分離アーキテクチャ - Dockerネットワーク/コンテナ名分離

**Objective:** 開発者として、各worktreeで独立したDockerネットワークとコンテナを持ちたい。これにより、コンテナ名衝突やネットワーク競合を完全に回避できるようにする。

#### Acceptance Criteria

1. WHEN 新しいworktreeが作成される THEN システムは動的なCOMPOSE_PROJECT_NAMEを設定しなければならない
   - 形式: `laravel-next-b2c-wt{WORKTREE_ID}` (例: laravel-next-b2c-wt1)
   - メインブランチ: `laravel-next-b2c`

2. WHEN docker-compose.ymlが実行される THEN システムは環境変数からCOMPOSE_PROJECT_NAMEを読み込みコンテナ名を動的に生成しなければならない
   - 例: `${COMPOSE_PROJECT_NAME}-laravel-api`, `${COMPOSE_PROJECT_NAME}-pgsql`

3. WHEN Dockerネットワークが作成される THEN システムは動的なネットワーク名を使用しなければならない
   - 形式: `${COMPOSE_PROJECT_NAME}_network`

4. WHEN Dockerボリュームが作成される THEN システムは動的なボリューム名を使用しなければならない
   - PostgreSQL: `${COMPOSE_PROJECT_NAME}_pgsql`
   - Redis: `${COMPOSE_PROJECT_NAME}_redis`
   - MinIO: `${COMPOSE_PROJECT_NAME}_minio`

5. WHEN 複数のworktreeが同時にDocker環境を起動する THEN システムはコンテナ名衝突が発生してはならない

6. WHEN `docker ps` コマンドが実行される THEN システムは各worktreeのコンテナを識別可能な命名規則で表示しなければならない

---

### Requirement 5: 完全分離アーキテクチャ - ストレージ/ボリューム分離

**Objective:** 開発者として、各worktreeで独立したストレージ環境を持ちたい。これにより、ファイルアップロード競合やボリューム衝突を回避できるようにする。

#### Acceptance Criteria

1. WHEN PostgreSQLボリュームが使用される THEN システムはDB名による分離を利用し単一ボリュームを共有してもよい

2. WHEN Redisボリュームが使用される THEN システムは各worktree専用ボリュームを作成しなければならない
   - 理由: キー名前空間衝突回避

3. WHEN MinIOボリュームが使用される THEN システムは各worktree専用ボリュームを作成しなければならない
   - 理由: ファイルアップロード競合回避

4. IF Mailpitボリュームが使用される場合 THEN システムは共有ボリュームを使用してもよい
   - 理由: 開発環境における影響が少ない

5. WHEN Laravel storageディレクトリが使用される THEN システムは各worktree内のディレクトリ構造により自動的に分離されなければならない

6. WHEN 異なるworktreeがファイルストレージを操作する THEN システムはファイル競合が発生してはならない

---

### Requirement 6: フロントエンド動的設定

**Objective:** 開発者として、各worktreeのフロントエンドアプリが正しいバックエンドAPIに接続したい。これにより、動的なポート番号に対応した環境変数設定を実現する。

#### Acceptance Criteria

1. WHEN User Appのpackage.jsonが更新される THEN システムは動的ポート番号を環境変数から読み込むdev scriptを設定しなければならない
   - 例: `"dev": "next dev -p ${FORWARD_USER_APP_PORT:-13100}"`

2. WHEN Admin Appのpackage.jsonが更新される THEN システムは動的ポート番号を環境変数から読み込むdev scriptを設定しなければならない
   - 例: `"dev": "next dev -p ${FORWARD_ADMIN_APP_PORT:-13200}"`

3. WHEN フロントエンド環境変数ファイル（.env.local）が生成される THEN システムは動的API URLを設定しなければならない
   - `NEXT_PUBLIC_API_URL=http://localhost:{APP_PORT}`
   - `NEXT_PUBLIC_API_BASE_URL=http://localhost:{APP_PORT}`

4. WHEN E2Eテスト環境変数が生成される THEN システムは動的テストURLを設定しなければならない
   - `E2E_ADMIN_URL=http://localhost:{FORWARD_ADMIN_APP_PORT}`
   - `E2E_USER_URL=http://localhost:{FORWARD_USER_APP_PORT}`
   - `E2E_API_URL=http://localhost:{APP_PORT}`

5. WHEN フロントエンドアプリが起動する THEN システムは設定されたポート番号で正常に起動しなければならない

6. WHEN フロントエンドからAPIリクエストが送信される THEN システムは正しいworktreeのバックエンドAPIに接続しなければならない

---

### Requirement 7: 自動ポート割り当てスクリプト - ポート管理機能

**Objective:** 開発者として、worktree IDとポート番号の管理を自動化したい。これにより、手動設定のミスを防ぎ、削除済みIDを再利用できるようにする。

#### Acceptance Criteria

1. WHEN `scripts/worktree/port-manager.sh` スクリプトが実装される THEN システムは以下の関数を提供しなければならない
   - `get_active_worktrees()`: 使用中worktree一覧取得
   - `get_used_worktree_ids()`: 使用中worktree ID一覧取得（削除済みID検出）
   - `get_next_available_id()`: 次に利用可能なID取得（0-99範囲）
   - `generate_port_config()`: worktree ID → ポート番号計算
   - `list_all_worktrees()`: 全worktreeのポート番号一覧表示

2. WHEN 開発者が次に利用可能なworktree IDを確認する THEN システムは削除済みIDを優先的に再利用しなければならない

3. WHEN ポート番号計算が実行される THEN システムは以下の計算式を使用しなければならない
   ```
   APP_PORT=$((13000 + WORKTREE_ID))
   FORWARD_USER_APP_PORT=$((13100 + WORKTREE_ID))
   FORWARD_ADMIN_APP_PORT=$((13200 + WORKTREE_ID))
   FORWARD_MINIO_CONSOLE_PORT=$((13300 + WORKTREE_ID))
   FORWARD_DB_PORT=$((14000 + WORKTREE_ID))
   FORWARD_REDIS_PORT=$((14100 + WORKTREE_ID))
   FORWARD_MAILPIT_DASHBOARD_PORT=$((14200 + WORKTREE_ID))
   FORWARD_MAILPIT_PORT=$((14300 + WORKTREE_ID))
   FORWARD_MINIO_PORT=$((14400 + WORKTREE_ID))
   ```

4. WHEN 全worktreeのポート番号一覧が要求される THEN システムは各worktreeのポート番号を表形式で表示しなければならない

5. IF 利用可能なworktree IDが存在しない場合（0-99すべて使用中） THEN システムはエラーメッセージを表示しなければならない

6. WHEN ポート番号から逆算する THEN システムは即座にworktree IDを特定できなければならない

---

### Requirement 8: 自動ポート割り当てスクリプト - worktreeセットアップ自動化

**Objective:** 開発者として、単一コマンドで新しいworktree環境を完全にセットアップしたい。これにより、手動設定の手間を削減し、セットアップミスを防ぐ。

#### Acceptance Criteria

1. WHEN `scripts/worktree/setup.sh <branch-name>` スクリプトが実行される THEN システムは以下の処理を順番に実行しなければならない
   1. ブランチ存在確認
   2. 次に利用可能なworktree ID取得（削除済みIDを再利用）
   3. worktree作成（推奨パス: `~/worktrees/laravel-next-b2c-wt{ID}/`）
   4. .envコピー＆動的設定書き換え（ポート番号/DB名/キャッシュプレフィックス/COMPOSE_PROJECT_NAME）
   5. .claude/, .kiro/ ディレクトリコピー
   6. PostgreSQL DB作成（`CREATE DATABASE laravel_wt{ID}`）
   7. キャッシュクリア
   8. composer install（backend）
   9. npm install（user-app + admin-app）
   10. Laravel権限設定（storage/bootstrap/cache）
   11. 完了メッセージ表示（ポート番号一覧含む）

2. IF ブランチが存在しない場合 THEN システムはエラーメッセージを表示し処理を中断しなければならない

3. IF worktree ID枯渇（0-99すべて使用中）の場合 THEN システムはエラーメッセージを表示し処理を中断しなければならない

4. IF ポート番号衝突が検出された場合 THEN システムはエラーメッセージを表示し処理を中断しなければならない

5. IF PostgreSQL DB作成が失敗した場合 THEN システムはエラーメッセージを表示し処理を中断しなければならない

6. IF composer installまたはnpm installが失敗した場合 THEN システムはエラーメッセージを表示し処理を中断しなければならない

7. WHEN セットアップが正常に完了する THEN システムは以下の情報を表示しなければならない
   - worktree ID
   - worktreeパス
   - 全サービスのポート番号一覧
   - 次のステップ（Docker起動コマンド等）

8. WHEN セットアップスクリプトが再実行される THEN システムは既存worktreeを検出し適切にエラーを表示しなければならない

---

### Requirement 9: Makefile統合とCLI操作

**Objective:** 開発者として、シンプルなMakefileコマンドでworktree管理を行いたい。これにより、複雑なスクリプトパスを記憶する必要なく操作できるようにする。

#### Acceptance Criteria

1. WHEN `make worktree-create BRANCH=<branch-name>` コマンドが実行される THEN システムは自動セットアップスクリプトを実行しなければならない

2. WHEN `make worktree-list` コマンドが実行される THEN システムは全worktreeの一覧を表示しなければならない
   - 実行内容: `git worktree list`

3. WHEN `make worktree-ports` コマンドが実行される THEN システムは全worktreeのポート番号一覧を表示しなければならない
   - 実行内容: `./scripts/worktree/port-manager.sh list`

4. WHEN `make worktree-remove PATH=<worktree-path>` コマンドが実行される THEN システムはworktreeを削除しなければならない
   - 実行内容: `git worktree remove <path>`

5. WHEN worktreeが削除される THEN システムは削除されたworktree IDを自動的に再利用可能にしなければならない

6. WHEN Makefileコマンドが引数不足で実行される THEN システムは適切な使用方法ヘルプを表示しなければならない

---

### Requirement 10: .gitignore設定とgit管理

**Objective:** 開発者として、worktreeディレクトリをgit管理対象外にしたい。これにより、誤ってworktreeファイルをコミットすることを防ぐ。

#### Acceptance Criteria

1. WHEN .gitignoreファイルが更新される THEN システムは以下のパターンを除外対象に追加しなければならない
   ```
   # === Git Worktree ===
   # Worktreeディレクトリ（リポジトリ内作成時）
   /wt-*/
   /worktree-*/
   /worktrees/*/

   # Worktreeメタデータ
   /.git/worktrees/
   ```

2. WHEN worktreeがリポジトリ内に作成される THEN システムは自動的にgit管理対象外にしなければならない

3. WHEN 開発者が `git status` を実行する THEN システムはworktreeディレクトリを未追跡ファイルとして表示してはならない

4. IF worktreeが推奨パス（`~/worktrees/`）に作成される場合 THEN システムは追加の.gitignore設定を必要としない

---

### Requirement 11: Breaking Change対応と既存環境移行

**Objective:** 既存ユーザーとして、ポート番号レンジ分離への移行を安全に実行したい。これにより、既存環境を破壊せず新しいポート番号体系に移行できるようにする。

#### Acceptance Criteria

1. WHEN .env.exampleファイルが更新される THEN システムは以下のポート番号を新しいレンジに変更しなければならない
   - `FORWARD_USER_APP_PORT`: 13001 → 13100
   - `FORWARD_ADMIN_APP_PORT`: 13002 → 13200
   - `FORWARD_MINIO_CONSOLE_PORT`: 13010 → 13300
   - `FORWARD_DB_PORT`: 13432 → 14000
   - `FORWARD_REDIS_PORT`: 13379 → 14100
   - `FORWARD_MAILPIT_DASHBOARD_PORT`: 13025 → 14200
   - `FORWARD_MAILPIT_PORT`: 11025 → 14300
   - `FORWARD_MINIO_PORT`: 13900 → 14400

2. WHEN .env.exampleファイルが更新される THEN システムはWORKTREE_ID環境変数を追加しなければならない
   - デフォルト値: `WORKTREE_ID=0`

3. WHEN 既存ユーザーが移行手順を実行する THEN システムは以下のステップを提供しなければならない
   1. 既存Docker停止（`make stop`）
   2. .envファイルバックアップ作成
   3. .env.exampleから新しい設定をコピー
   4. 既存設定の手動マージ（DB_PASSWORD等）
   5. フロントエンド環境変数更新
   6. Docker再起動（`make dev`）
   7. ブラウザアクセス先変更

4. WHEN 移行手順ドキュメントが作成される THEN システムは影響を受けるユーザーを明示しなければならない
   - 既に開発環境を構築済みのユーザー
   - ブラウザブックマークでポート番号を保存しているユーザー

5. WHEN 移行手順ドキュメントが作成される THEN システムは主な変更点を一覧表示しなければならない
   - User App: `http://localhost:13001` → `http://localhost:13100`
   - Admin App: `http://localhost:13002` → `http://localhost:13200`
   - その他全サービスのポート番号変更

6. IF 既存ユーザーが移行を実行しない場合 THEN システムは従来のポート番号で動作してはならない

---

### Requirement 12: README.mdドキュメント更新

**Objective:** 開発者として、並列開発環境の使い方を理解したい。これにより、セットアップ手順・使用例・トラブルシューティングを学べるようにする。

#### Acceptance Criteria

1. WHEN README.mdが更新される THEN システムは新規セクション「🌳 並列開発（git worktree）」を追加しなければならない

2. WHEN 並列開発セクションが記述される THEN システムは以下の内容を含めなければならない
   - worktreeとは？（Claude Code並列実行のメリット）
   - セットアップ手順（`make worktree-create` コマンド）
   - ポート番号設計の説明（レンジ分離方式）
   - データベース分離戦略
   - 使用例（2つのworktreeで並列開発）
   - リソース使用量（推奨16GB RAM、推奨32GB RAM）
   - トラブルシューティング

3. WHEN トラブルシューティングセクションが記述される THEN システムは以下の問題解決方法を提供しなければならない
   - ポート衝突時の対処
   - DB接続エラー
   - Redisキー衝突
   - worktree削除方法

4. WHEN 使用例が記述される THEN システムは具体的なコマンド実行例を含めなければならない
   ```bash
   # Worktree作成
   make worktree-create BRANCH=feature/140/api-versioning

   # ポート番号確認
   make worktree-ports

   # Worktree一覧
   make worktree-list

   # Worktree削除
   make worktree-remove PATH=~/worktrees/laravel-next-b2c-wt1
   ```

5. WHEN リソース使用量が記述される THEN システムは1worktree起動時と5-8worktree同時起動時のメモリ使用量を明示しなければならない
   - 1worktree: 約1GB
   - 5-8worktrees: 約5GB

6. WHEN 推奨システム要件が記述される THEN システムは以下の情報を提供しなければならない
   - 最小: 16GB RAM（5 worktrees同時起動）
   - 推奨: 32GB RAM（8 worktrees同時起動 + IDE + Chrome）
   - ストレージ: 50GB空き（各worktreeは約1GB）

---

### Requirement 13: 動作検証とテスト戦略

**Objective:** 開発者として、実装が正しく動作することを検証したい。これにより、並列開発環境の信頼性を保証する。

#### Acceptance Criteria

1. WHEN 2つのworktreeで並列Docker起動テストが実行される THEN システムはポート番号衝突なく両方のDocker環境を起動しなければならない

2. WHEN ポート番号再利用テストが実行される THEN システムは削除されたworktree IDを正しく再利用しなければならない

3. WHEN DB分離テストが実行される THEN システムは以下を検証しなければならない
   - 各worktreeで独立したDBが作成されること
   - 異なるworktreeでマイグレーションを実行しても互いに影響しないこと

4. WHEN Redisキャッシュ分離テストが実行される THEN システムは以下を検証しなければならない
   - 各worktreeで独立したキャッシュプレフィックスが使用されること
   - 異なるworktreeでキャッシュ操作をしても互いに影響しないこと

5. WHEN E2Eテスト並列実行テストが実行される THEN システムは以下を検証しなければならない
   - 各worktreeで独立したE2E環境変数が設定されること
   - 異なるworktreeでE2Eテストを同時実行しても互いに影響しないこと

6. WHEN 全worktree（5-8個）同時起動テストが実行される THEN システムはリソース使用量が想定範囲内であることを検証しなければならない
   - メモリ使用量: 約5GB以内
   - CPU使用率: 安定動作

7. IF テスト実行中にエラーが発生した場合 THEN システムはエラーログを詳細に記録しなければならない

---

## 補足情報

### リソース使用量見積もり

**1worktree起動時（現在）:**
- Laravel API: 130MB
- PostgreSQL: 400MB
- Redis: 15MB
- MinIO: 50MB
- Mailpit: 10MB
- Admin App: 200MB
- User App: 200MB
- **合計: 約1GB**

**5-8worktree同時起動時（推奨構成）:**
- PostgreSQL: 400MB × 1（共有）
- Redis: 15MB × 8 = 120MB
- MinIO: 50MB × 8 = 400MB
- Laravel API: 130MB × 8 = 1GB
- Next.js: 400MB × 8 = 3.2GB
- **合計: 約5GB**

### 期待される効果

1. **並列開発の実現**: 5-8個のworktreeで衝突なし
2. **Claude Code高速化**: テスト実行中に次のタスクに着手可能
3. **ポート番号再利用**: worktree削除後、自動的にポート番号を再利用
4. **完全分離**: データベース・ネットワーク・キャッシュが完全分離
5. **運用自動化**: `make worktree-create`で即座に新規環境構築
6. **スケーラビリティ**: 将来的に99個まで拡張可能

### 参考資料

- 元になったスクリプト: `animalife/trimtrim_laravel/scripts/setup-worktree.sh`
- ポート番号衝突分析レポート: （詳細調査済み）
- 追加考慮事項レポート: 12項目（データベース・ネットワーク・キャッシュ等）

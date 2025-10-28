# Requirements Document

## はじめに

本要件定義は、ローカル開発環境のDocker設定改善を目的とし、開発者の生産性向上とシンプルな開発環境構築を実現します。

### 背景
現状の開発環境には以下の課題があります：
- `scripts/dev/`スクリプトが複雑で理解しづらく、`concurrently`のエラーで起動できない
- Laravel APIのホットリロードが効かず、コード変更のたびに再ビルドが必要
- Next.jsをDockerで動かそうとしており、ネイティブ起動と比較してパフォーマンスが低下
- 開発環境の起動方法が不明確で、新規参加者がすぐに開発を開始できない

### ビジネス価値
- **開発者エクスペリエンス向上**: シンプルで理解しやすい起動手順により、開発に集中できる環境を提供
- **開発サイクル高速化**: ホットリロード有効化により、コード変更から動作確認までの時間を1秒以内に短縮
- **オンボーディング効率化**: 明確なドキュメントにより、新規参加者が即座に開発環境を構築可能
- **保守性向上**: 複雑なスクリプトを削除し、標準的なDocker Composeコマンドで管理

## Requirements

### Requirement 1: Laravel API Docker設定のホットリロード対応
**Objective**: 開発者として、Laravel APIのコード変更が即座に反映される環境が欲しい。これにより、開発サイクルを高速化し、再ビルドの待ち時間を削減できる。

#### Acceptance Criteria

1. WHEN 開発者がdocker-compose.ymlを確認した THEN Laravel APIサービスはソースコードのvolumeマウント設定を含むこと（`./backend/laravel-api:/var/www/html:cached`）
2. WHEN Laravel APIサービスがDocker Composeで起動した THEN vendorディレクトリはコンテナ側に保持されること（volumeマウントから除外: `/var/www/html/vendor`）
3. WHEN Laravel APIサービスがDocker Composeで起動した THEN 環境変数`APP_ENV=local`が設定されていること
4. WHEN 開発者がLaravel APIのソースコード（例: routes/api.php）を変更した THEN 1秒以内に変更が反映され、再ビルドが不要であること
5. WHEN 開発者が`curl http://localhost:13000/api/health`でヘルスチェックを実行した THEN 正常なレスポンスが返却されること

### Requirement 2: Next.js アプリケーションのネイティブ起動対応
**Objective**: 開発者として、Next.jsアプリケーション（admin-app、user-app）をネイティブ起動したい。これにより、Turbopackの最高速パフォーマンスを享受し、ホットリロードを完璧に動作させることができる。

#### Acceptance Criteria

1. WHEN 開発者がdocker-compose.ymlを確認した THEN admin-appサービス定義が削除されていること
2. WHEN 開発者がdocker-compose.ymlを確認した THEN user-appサービス定義が削除されていること
3. WHEN 開発者が`cd frontend/admin-app && npm run dev`を実行した THEN ポート13002でadmin-appが起動すること
4. WHEN 開発者が`cd frontend/user-app && npm run dev`を実行した THEN ポート13001でuser-appが起動すること
5. WHEN 開発者がNext.jsアプリのソースコード（例: app/page.tsx）を変更した THEN 1秒以内にブラウザが自動リロードし、変更が反映されること
6. WHEN admin-appが起動した THEN http://localhost:13002 にアクセス可能であること
7. WHEN user-appが起動した THEN http://localhost:13001 にアクセス可能であること

### Requirement 3: 開発環境起動手順のドキュメント整備
**Objective**: 開発者として、開発環境の起動手順が明確に記載されたドキュメントが欲しい。これにより、チームメンバーや新規参加者が即座に開発を開始できる。

#### Acceptance Criteria

1. WHEN 開発者がREADME.mdを開いた THEN 「開発環境起動」セクションが存在すること
2. WHERE 「開発環境起動」セクション内 THE README.mdは必要なツール（Docker Desktop、Node.js 20+、PHP 8.4+）を明記すること
3. WHERE 「開発環境起動」セクション内 THE README.mdは3ターミナルでの起動手順（ターミナル1: Docker、ターミナル2: Admin App、ターミナル3: User App）を明記すること
4. WHERE 「開発環境起動」セクション内 THE README.mdは各サービスのアクセスURL（Laravel API: 13000、Admin App: 13002、User App: 13001）を明記すること
5. WHERE 「開発環境起動」セクション内 THE README.mdはホットリロード確認方法（Laravel API、Next.js）を明記すること
6. WHERE 「開発環境起動」セクション内 THE README.mdはトラブルシューティング（ポート競合、ホットリロード不具合）を含むこと
7. WHERE 「開発環境起動」セクション内 THE README.mdは停止方法（各ターミナルでCtrl+C、`docker compose down`）を明記すること

### Requirement 4: Makefile のシンプル化
**Objective**: 開発者として、シンプルで覚えやすいMakefileコマンドが欲しい。これにより、複雑なスクリプトを理解せずに、標準的なDocker Composeコマンドで開発環境を管理できる。

#### Acceptance Criteria

1. WHEN 開発者が`make dev`を実行した THEN Dockerサービス（PostgreSQL、Redis、Mailpit、MinIO、Laravel API）のみが起動すること（`docker compose up -d`相当）
2. WHEN `make dev`が完了した THEN 次のステップとして、手動でNext.jsアプリを起動する方法が表示されること（Terminal 2: admin-app、Terminal 3: user-app）
3. WHEN 開発者が`make stop`を実行した THEN Dockerサービスが停止すること（`docker compose stop`相当）
4. WHEN 開発者が`make clean`を実行した THEN Dockerコンテナとボリュームが完全削除されること（`docker compose down -v`相当）
5. WHEN 開発者が`make logs`を実行した THEN Dockerサービスのログが表示されること（`docker compose logs -f`相当）
6. WHEN 開発者が`make ps`を実行した THEN Dockerサービスの状態が表示されること（`docker compose ps`相当）
7. WHEN 開発者が`make help`を実行した THEN 利用可能なコマンド一覧が表示されること
8. WHERE Makefileの`make dev`ターゲット THE Makefileは`scripts/dev/main.sh`を呼び出さないこと（シンプルな`docker compose`コマンドのみ使用）

### Requirement 5: 不要なスクリプトの整理
**Objective**: 開発者として、複雑で理解しづらい`scripts/dev/`ディレクトリを削除または無視したい。これにより、保守すべきコードベースを削減し、シンプルな開発環境を維持できる。

#### Acceptance Criteria

1. WHEN プロジェクト構成を確認した THEN `scripts/dev/`ディレクトリが削除されているか、または使用しない旨がREADME.mdに明記されていること
2. IF `scripts/dev/`ディレクトリが削除されていない場合 THEN README.mdに「`scripts/dev/`は使用しません。上記の起動手順に従ってください」という注意書きが含まれていること

### Requirement 6: E2Eテスト環境設定の調整
**Objective**: 開発者として、E2Eテスト環境がNext.jsアプリのネイティブ起動に対応してほしい。これにより、E2Eテストを正常に実行できる。

#### Acceptance Criteria

1. WHEN 開発者がdocker-compose.ymlのe2e-testsサービスを確認した THEN depends_onからadmin-app、user-appサービスが削除されていること
2. WHEN 開発者がdocker-compose.ymlのe2e-testsサービスを確認した THEN depends_onにlaravel-apiサービス（condition: service_healthy）のみが含まれていること
3. WHERE e2e-testsサービスの環境変数 THE docker-compose.ymlはE2E_ADMIN_URLが`http://localhost:13002`に設定されていること
4. WHERE e2e-testsサービスの環境変数 THE docker-compose.ymlはE2E_USER_URLが`http://localhost:13001`に設定されていること
5. WHERE e2e-testsサービスの環境変数 THE docker-compose.ymlはE2E_API_URLが`http://localhost:13000`に設定されていること

### Requirement 7: 統合動作確認
**Objective**: 開発者として、すべての変更が統合的に動作することを確認したい。これにより、本要件が満たされていることを保証できる。

#### Acceptance Criteria

1. WHEN 開発者が`make dev`を実行した THEN PostgreSQL、Redis、Mailpit、MinIO、Laravel APIが起動すること
2. WHEN 開発者が`cd frontend/admin-app && npm run dev`を実行した THEN admin-appがポート13002で起動すること
3. WHEN 開発者が`cd frontend/user-app && npm run dev`を実行した THEN user-appがポート13001で起動すること
4. WHEN すべてのサービスが起動した THEN http://localhost:13000/api/health にアクセス可能であること
5. WHEN すべてのサービスが起動した THEN http://localhost:13001 にアクセス可能であること
6. WHEN すべてのサービスが起動した THEN http://localhost:13002 にアクセス可能であること
7. WHEN 開発者がLaravel APIのソースコードを変更した THEN 1秒以内に変更が反映されること
8. WHEN 開発者がNext.jsアプリのソースコードを変更した THEN 1秒以内にブラウザが自動リロードすること

## 対象外

以下は本要件の対象外とします：

- 本番環境のDocker設定（CI/CDで別途構築）
- 既存のE2Eテスト実装（Playwrightテスト内容の変更は不要）
- 複雑な開発環境スクリプト（`scripts/dev/`）の改修（削除または無視）

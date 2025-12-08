# Requirements Document

## Introduction

本仕様は、Next.js 15.5フロントエンドアプリケーション（Admin App / User App）のDocker化と、Laravel APIとの統合Docker Compose環境構築を目的としています。これにより、開発環境の統一、E2Eテストのフルコンテナ実行、CI/CD基盤強化、本番環境へのスムーズな移行が可能になります。

### ビジネス価値
- **開発環境統一**: チーム全体で一貫したDocker環境を利用し、環境差異によるトラブルを削減
- **E2Eテスト自動化**: Playwright E2EテストをDocker環境で実行可能にし、CI/CDパイプライン強化
- **スケーラビリティ**: 本番環境へのデプロイ準備（standalone output検証）
- **開発者体験向上**: `docker compose up`一発で全サービス起動、迅速な開発開始

## Requirements

### Requirement 1: Next.js Dockerfile作成（マルチステージビルド）
**Objective:** 開発チームとして、Admin AppとUser AppをそれぞれDockerコンテナとして起動できるDockerfileを作成し、開発環境の統一と本番デプロイの準備を整えたい

#### Acceptance Criteria

1. WHEN 開発者がAdmin App用Dockerfileを作成する THEN Dockerfile SHALL Node.js 20 Alpineをベースイメージとし、マルチステージビルド（deps/builder/runner）で構成する
2. WHEN 開発者がUser App用Dockerfileを作成する THEN Dockerfile SHALL Node.js 20 Alpineをベースイメージとし、マルチステージビルド（deps/builder/runner）で構成する
3. WHEN Dockerfileがdepsステージを実行する THEN システム SHALL モノレポ対応でルートpackage.jsonと各アプリのpackage.jsonをコピーし、npm ciで依存関係をインストールする
4. WHEN Dockerfileがbuilderステージを実行する THEN システム SHALL Next.js standalone出力を有効化し、npm run build --workspaceでビルドする
5. WHEN Dockerfileがrunnerステージを実行する THEN システム SHALL nodejs（GID 1001）ユーザーとnextjs（UID 1001）ユーザーを作成し、本番環境用の最小権限で実行する
6. WHEN Admin App Dockerfileが実行される THEN コンテナ SHALL ポート3001を公開し、ENV PORT=3001とHOSTNAME="0.0.0.0"を設定する
7. WHEN User App Dockerfileが実行される THEN コンテナ SHALL ポート3000を公開し、ENV PORT=3000とHOSTNAME="0.0.0.0"を設定する
8. WHEN Dockerfileがビルドを実行する THEN システム SHALL ENV NEXT_TELEMETRY_DISABLED=1を設定し、Next.jsテレメトリを無効化する

### Requirement 2: Next.js standalone output設定
**Objective:** 開発チームとして、Next.jsアプリをstandalone出力モードでビルドし、Dockerコンテナ内で最小限のファイルサイズで動作させたい

#### Acceptance Criteria

1. WHEN 開発者がnext.config.tsを更新する THEN Admin App SHALL output: 'standalone'設定を追加する
2. WHEN 開発者がnext.config.tsを更新する THEN User App SHALL output: 'standalone'設定を追加する
3. WHEN next.config.tsにstandalone設定がある AND npm run buildを実行する THEN Next.js SHALL .next/standaloneディレクトリに本番用最小ファイルセットを出力する
4. WHEN Dockerfileがstandalone出力をコピーする THEN システム SHALL .next/standalone、.next/static、publicディレクトリをランタイムステージにコピーする
5. WHEN next.config.tsを設定する THEN 設定ファイル SHALL 既存のoutputFileTracingRoot設定を保持し、モノレポ環境でのビルド警告を回避する

### Requirement 3: 統合Docker Compose設定（ルート配置）
**Objective:** 開発チームとして、Laravel API、Admin App、User App、E2Eテストを一つのdocker-compose.ymlで管理し、`docker compose up`一発で全サービスを起動したい

#### Acceptance Criteria

1. WHEN 開発者がdocker-compose.ymlをリポジトリルートに作成する THEN ファイル SHALL Laravel API、Admin App、User App、E2E Tests、PostgreSQL、Redis、Mailpit、MinIOの全サービスを定義する
2. WHEN docker-compose.ymlがlaravel-apiサービスを定義する THEN サービス SHALL 既存のbackend/laravel-api/compose.yamlからlaravel.testサービス設定を統合する
3. WHEN docker-compose.ymlがlaravel-apiサービスを定義する THEN サービス SHALL ポート13000を公開し、環境変数APP_PORT=13000を設定する
4. WHEN docker-compose.ymlがadmin-appサービスを定義する THEN サービス SHALL ポート3001を公開し、環境変数NEXT_PUBLIC_API_URL=http://laravel-api:13000を設定する
5. WHEN docker-compose.ymlがuser-appサービスを定義する THEN サービス SHALL ポート3000を公開し、環境変数NEXT_PUBLIC_API_URL=http://laravel-api:13000を設定する
6. WHEN docker-compose.ymlがadmin-app/user-appサービスを定義する THEN サービス SHALL volumes設定でホスト側ソースコードをマウントし、開発時のHot Reload機能を提供する
7. WHEN docker-compose.ymlがadmin-app/user-appサービスを定義する THEN サービス SHALL 匿名ボリューム（/app/frontend/{app-name}/node_modules）でnode_modulesをコンテナ内に保持する
8. WHEN docker-compose.ymlがe2e-testsサービスを定義する THEN サービス SHALL Playwright公式イメージ（mcr.microsoft.com/playwright:v1.47.2-jammy）を使用する
9. WHEN docker-compose.ymlがe2e-testsサービスを定義する THEN サービス SHALL admin-app、user-app、laravel-apiサービスにdepends_onで依存関係を設定する
10. WHEN docker-compose.ymlがe2e-testsサービスを定義する THEN サービス SHALL 環境変数E2E_ADMIN_URL、E2E_USER_URL、E2E_API_URLにDocker内部URLを設定する
11. WHEN docker-compose.ymlが全サービスを定義する THEN 設定ファイル SHALL app-networkという共通Bridgeネットワークを作成し、全サービスを接続する
12. WHEN docker-compose.ymlがPostgreSQL/Redisサービスを定義する THEN サービス SHALL 既存のcompose.yamlから設定を統合し、カスタムポート（PostgreSQL: 13432、Redis: 13379）を保持する
13. WHEN docker-compose.ymlがボリュームを定義する THEN 設定ファイル SHALL sail-pgsql、sail-redis、sail-minioの永続化ボリュームを定義する

### Requirement 4: .dockerignore設定
**Objective:** 開発チームとして、Dockerビルド時に不要なファイルを除外し、ビルド速度を最適化したい

#### Acceptance Criteria

1. WHEN 開発者が.dockerignoreファイルを各フロントエンドアプリディレクトリに作成する THEN ファイル SHALL node_modules、.next、coverage、.envなど開発環境固有ファイルを除外する
2. WHEN 開発者が.dockerignoreファイルを作成する THEN ファイル SHALL .git、.idea、.vscodeなどVCS/IDE設定ファイルを除外する
3. WHEN Dockerビルドが実行される AND .dockerignoreが設定されている THEN Docker SHALL 除外されたファイルをビルドコンテキストに含めず、ビルド時間を短縮する

### Requirement 5: 環境変数管理（.env連携）
**Objective:** 開発チームとして、Docker環境で必要な環境変数を.envファイルで一元管理し、設定の可視化と変更容易性を確保したい

#### Acceptance Criteria

1. WHEN 開発者が.env.exampleファイルを更新する THEN ファイル SHALL Frontend環境変数セクションにNEXT_PUBLIC_API_URL=http://localhost:13000を追加する
2. WHEN 開発者が.env.exampleファイルを更新する THEN ファイル SHALL E2E Tests環境変数セクションにE2E_ADMIN_URL、E2E_USER_URL、E2E_API_URL、認証情報を追加する
3. WHEN 開発者がdocker compose upを実行する THEN システム SHALL .envファイルから環境変数を読み込み、各サービスに適切な値を注入する
4. WHEN 環境変数が設定される THEN Admin App/User App SHALL NEXT_PUBLIC_API_URLを利用してLaravel APIに接続する
5. WHEN 環境変数が設定される THEN E2Eテスト SHALL E2E_ADMIN_URL、E2E_USER_URL、E2E_API_URLを利用して各サービスにアクセスする

### Requirement 6: Docker環境でのサービス起動
**Objective:** 開発チームとして、`docker compose up`コマンドで全サービスを一括起動し、即座に開発・テストを開始したい

#### Acceptance Criteria

1. WHEN 開発者がdocker compose up -d --buildを実行する THEN システム SHALL 全サービス（Laravel API、Admin App、User App、PostgreSQL、Redis、Mailpit、MinIO）をバックグラウンドで起動する
2. WHEN docker compose upが完了する THEN Admin App SHALL http://localhost:3001でアクセス可能になる
3. WHEN docker compose upが完了する THEN User App SHALL http://localhost:3000でアクセス可能になる
4. WHEN docker compose upが完了する THEN Laravel API SHALL http://localhost:13000でアクセス可能になる
5. WHEN 開発者がdocker compose psを実行する THEN システム SHALL 全サービスの起動状態を表示する
6. WHEN 開発者がdocker compose logs -f [service-name]を実行する THEN システム SHALL 指定サービスのリアルタイムログを表示する
7. WHEN 開発者がdocker compose downを実行する THEN システム SHALL 全サービスを停止し、コンテナを削除する

### Requirement 7: E2EテストDocker実行
**Objective:** 開発チームとして、PlaywrightによるE2EテストをDocker環境で実行し、CI/CDパイプラインでの自動テストを可能にしたい

#### Acceptance Criteria

1. WHEN 開発者がdocker-compose run --rm e2e-testsを実行する THEN システム SHALL e2e-testsコンテナを起動し、npm install、Playwrightインストール、テスト実行を順次実行する
2. WHEN e2e-testsコンテナが起動する THEN コンテナ SHALL admin-app、user-app、laravel-apiサービスが起動完了するまで待機する
3. WHEN e2e-testsコンテナがテストを実行する THEN Playwright SHALL Docker内部ネットワーク経由でadmin-app（http://admin-app:3001）、user-app（http://user-app:3000）にアクセスする
4. WHEN e2e-testsコンテナがテストを実行する THEN Playwright SHALL Laravel API（http://laravel-api:13000）に対してAPI統合テストを実行する
5. WHEN E2Eテストが完了する THEN システム SHALL テスト結果（成功/失敗）を標準出力に表示する
6. WHEN E2Eテストが完了する AND --rmフラグが指定されている THEN Docker SHALL テスト完了後にコンテナを自動削除する

### Requirement 8: ドキュメント整備
**Objective:** 開発チームとして、Docker環境でのセットアップ手順と運用方法を明確にドキュメント化し、新規参加者のオンボーディングを迅速化したい

#### Acceptance Criteria

1. WHEN 開発者がREADME.mdを更新する THEN ドキュメント SHALL 「Docker環境でのセットアップ」セクションを追加する
2. WHEN README.mdに手順を記載する THEN ドキュメント SHALL 環境変数設定手順（cp .env.example .env）を含む
3. WHEN README.mdに手順を記載する THEN ドキュメント SHALL 全サービス起動手順（docker compose up -d --build）を含む
4. WHEN README.mdに手順を記載する THEN ドキュメント SHALL 起動確認手順（docker compose ps）を含む
5. WHEN README.mdに手順を記載する THEN ドキュメント SHALL ログ確認手順（docker compose logs -f [service-name]）を含む
6. WHEN README.mdに手順を記載する THEN ドキュメント SHALL E2Eテスト実行手順（docker-compose run --rm e2e-tests）を含む
7. WHEN README.mdに手順を記載する THEN ドキュメント SHALL サービス停止手順（docker compose down）を含む
8. WHEN README.mdに手順を記載する THEN ドキュメント SHALL トラブルシューティングセクションを追加し、よくある問題と解決策を記載する

### Requirement 9: 開発時Hot Reload対応
**Objective:** 開発チームとして、Docker環境でもNext.jsのHot Reload機能を利用し、コード変更を即座に反映させたい

#### Acceptance Criteria

1. WHEN docker-compose.ymlがadmin-app/user-appサービスを定義する THEN サービス SHALL ホスト側ソースコードディレクトリをコンテナにマウントする
2. WHEN docker-compose.ymlがvolumes設定を定義する THEN 設定 SHALL 匿名ボリューム（/app/frontend/{app-name}/node_modules）でnode_modulesをコンテナ内に保持する
3. WHEN 開発者がホスト側でソースコードを変更する AND docker compose upで起動中 THEN Next.js SHALL ファイル変更を検知し、自動的に再ビルドとブラウザリロードを実行する
4. WHEN Hot Reloadが動作する THEN システム SHALL 変更反映まで数秒以内で完了する

### Requirement 10: ビルド最適化
**Objective:** 開発チームとして、Dockerビルド時間を最小化し、開発サイクルを高速化したい

#### Acceptance Criteria

1. WHEN Dockerfileがマルチステージビルドを使用する THEN ビルド SHALL 依存関係レイヤー（deps）を分離し、package.json変更時のみ再インストールする
2. WHEN .dockerignoreファイルが設定される THEN Docker SHALL 除外ファイルをビルドコンテキストに含めず、ビルド時間を短縮する
3. WHEN Dockerfileがnode_modulesをコピーする THEN ビルド SHALL --from=depsステージから依存関係をコピーし、重複インストールを回避する
4. WHEN Dockerfileがランタイムイメージを作成する THEN イメージ SHALL 本番実行に必要な最小ファイルのみを含み、イメージサイズを最小化する

## 完了条件（Definition of Done）

### 必須条件
- ✅ Admin App/User App用のDockerfile（frontend/admin-app/Dockerfile、frontend/user-app/Dockerfile）が作成され、docker buildが成功する
- ✅ next.config.tsにoutput: 'standalone'設定が追加され、ビルド時に.next/standaloneディレクトリが生成される
- ✅ リポジトリルートにdocker-compose.ymlが作成され、全サービス定義が完了している
- ✅ docker compose up -d --buildコマンドで全サービスが起動する
- ✅ Admin App（http://localhost:3001）、User App（http://localhost:3000）、Laravel API（http://localhost:13000）にブラウザでアクセス可能
- ✅ docker-compose run --rm e2e-testsコマンドでE2Eテストが実行され、成功する
- ✅ README.mdにDocker環境セットアップ手順が記載されている
- ✅ .dockerignoreファイルが各フロントエンドアプリディレクトリに作成されている
- ✅ .env.exampleにFrontend、E2E Tests環境変数が追加されている

### 推奨条件
- ✅ Docker環境でNext.js Hot Reloadが動作し、ソースコード変更が即座に反映される
- ✅ Dockerビルド時間が最適化され、初回ビルド後の再ビルドが高速化される
- ✅ docker compose logs -fで各サービスのログが適切に表示される
- ✅ トラブルシューティングドキュメントが作成され、よくある問題の解決策が記載されている

## 参考資料

### 公式ドキュメント
- [Next.js Docker Documentation](https://nextjs.org/docs/deployment#docker-image)
- [Next.js Standalone Output](https://nextjs.org/docs/advanced-features/output-file-tracing)
- [Docker Compose Documentation](https://docs.docker.com/compose/)
- [Playwright Docker Documentation](https://playwright.dev/docs/docker)

### 関連Issue/PR
- Issue #12: E2Eテスト環境基盤設定（Docker実行の前提条件）
- PR #58: E2Eテスト環境基盤構築
- Issue #14: Next.js アプリ用 Dockerfile 作成（本仕様）

### プロジェクト固有情報
- 既存Laravel API Docker構成: `backend/laravel-api/compose.yaml`
- 既存Next.js設定: `frontend/admin-app/next.config.ts`、`frontend/user-app/next.config.ts`
- E2Eテスト環境: `e2e/playwright.config.ts`
- プロジェクト構造: `.kiro/steering/structure.md`
- 技術スタック: `.kiro/steering/tech.md`

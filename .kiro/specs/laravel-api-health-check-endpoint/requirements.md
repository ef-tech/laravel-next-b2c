# Requirements Document

## Introduction

本ドキュメントは、Laravel APIに対するヘルスチェックエンドポイント（Liveness Probe）の実装要件を定義します。この機能は、本番環境でのサービス信頼性向上とDocker環境でのコンテナオーケストレーション最適化を目的としています。

**ビジネス価値**:
- **本番環境必須機能**: テンプレート化において不可欠なヘルスチェック機能の完成
- **信頼性向上**: コンテナの起動状態を正確に監視し、障害の早期検知を実現
- **依存関係制御**: Docker Composeの`depends_on: service_healthy`により、サービス間の起動順序を保証
- **運用効率化**: ヘルスチェック統合により、インフラレベルでの自動復旧を可能にする

**参考実装**: PR #71 - Docker Composeヘルスチェック実装（Next.jsアプリ実装済み）

---

## Requirements

### Requirement 1: Liveness エンドポイント実装

**Objective:** インフラストラクチャエンジニアおよびDevOpsチームとして、Laravel APIの起動状態を監視するためのLivenessエンドポイントが必要であり、これによりコンテナオーケストレータがサービスの健全性を判断できるようにする。

#### Acceptance Criteria

1. **WHEN** HTTP GETリクエストが `/api/health` に送信される **THEN** Laravel API **SHALL** HTTP 200 OKステータスコードを返却する

2. **WHEN** `/api/health` エンドポイントにアクセスする **THEN** Laravel API **SHALL** JSON形式で `{"status": "ok"}` のみを含むレスポンスボディを返却する

3. **WHEN** `/api/health` エンドポイントにアクセスする **THEN** Laravel API **SHALL** レスポンスヘッダーに `Cache-Control: no-store` を含める

4. **WHEN** `/api/health` エンドポイントにアクセスする **THEN** Laravel API **SHALL** 10ミリ秒以内にレスポンスを返却する

5. **WHEN** `/api/health` エンドポイントにアクセスする **THEN** Laravel API **SHALL** データベース接続やRedis接続などの外部依存関係をチェックせずに応答する

### Requirement 2: セキュリティとアクセス制御

**Objective:** セキュリティエンジニアとして、ヘルスチェックエンドポイントが適切なセキュリティ設定を持ち、機微情報を露出しないことを確認したい。これにより、本番環境での安全な運用を保証する。

#### Acceptance Criteria

1. **WHEN** `/api/health` エンドポイントにアクセスする **THEN** Laravel API **SHALL** 認証トークンなしでアクセスを許可する

2. **WHEN** `/api/health` エンドポイントにアクセスする **THEN** Laravel API **SHALL** レート制限（`throttle:api` middleware）を適用しない

3. **WHEN** `/api/health` エンドポイントから応答を返却する **THEN** Laravel API **SHALL** データベース接続情報、環境変数、サーバー構成などの機微情報を含めない

4. **WHEN** 認証されていないユーザーが `/api/health` にアクセスする **THEN** Laravel API **SHALL** HTTP 200 OKステータスコードを返却する

5. **IF** `/api/health` エンドポイントが秒間100回以上アクセスされる **THEN** Laravel API **SHALL** すべてのリクエストに対して正常に応答する

### Requirement 3: Docker ヘルスチェック統合

**Objective:** インフラストラクチャエンジニアとして、Dockerコンテナがヘルスチェック機能を持ち、Docker Composeがコンテナの健全性を監視できるようにしたい。これにより、サービス間の依存関係を正確に制御できる。

#### Acceptance Criteria

1. **WHEN** Dockerコンテナが起動する **THEN** Docker **SHALL** 10秒間隔でヘルスチェックを実行する

2. **WHEN** ヘルスチェックが実行される **THEN** Docker **SHALL** `wget --no-verbose --tries=1 --spider http://127.0.0.1:13000/api/health` コマンドを使用して `/api/health` エンドポイントにアクセスする

3. **WHEN** ヘルスチェックが失敗する **THEN** Docker **SHALL** 3回まで再試行する

4. **WHEN** 3回連続でヘルスチェックが失敗する **THEN** Docker **SHALL** コンテナを `unhealthy` 状態としてマークする

5. **WHEN** コンテナが起動してから30秒以内 **THEN** Docker **SHALL** ヘルスチェックの失敗をコンテナの健全性評価に含めない（start-period猶予時間）

6. **WHEN** ヘルスチェックリクエストが3秒以内に完了しない **THEN** Docker **SHALL** そのヘルスチェックを失敗とみなす

7. **WHEN** `docker compose ps` コマンドが実行される **THEN** Docker Compose **SHALL** laravel-apiコンテナのHEALTHカラムに `healthy` または `unhealthy` ステータスを表示する

8. **IF** 他のサービスが `depends_on: laravel-api: condition: service_healthy` を設定している **THEN** Docker Compose **SHALL** laravel-apiコンテナが `healthy` 状態になるまで依存サービスの起動を待機する

### Requirement 4: テストカバレッジと品質保証

**Objective:** QAエンジニアおよび開発者として、ヘルスチェックエンドポイントが正しく動作し、すべてのコード品質基準を満たしていることを保証したい。これにより、本番環境での信頼性を確保する。

#### Acceptance Criteria

1. **WHEN** Pest 4テストスイートが実行される **THEN** Laravel API **SHALL** `/api/health` エンドポイントの正常系テストに合格する

2. **WHEN** `/api/health` エンドポイントのFeatureテストが実行される **THEN** テストスイート **SHALL** HTTP 200 OKステータスコードの検証を含む

3. **WHEN** `/api/health` エンドポイントのFeatureテストが実行される **THEN** テストスイート **SHALL** JSONレスポンス構造 `{"status": "ok"}` の検証を含む

4. **WHEN** `/api/health` エンドポイントのFeatureテストが実行される **THEN** テストスイート **SHALL** `Cache-Control: no-store` ヘッダーの存在検証を含む

5. **WHEN** `/api/health` エンドポイントのFeatureテストが実行される **THEN** テストスイート **SHALL** 認証なしでアクセス可能であることの検証を含む

6. **WHEN** Laravel Pint（コードフォーマッター）が実行される **THEN** `/api/health` エンドポイントのコード **SHALL** Laravel Pint標準ルールに準拠する

7. **WHEN** Larastan（静的解析 Level 8）が実行される **THEN** `/api/health` エンドポイントのコード **SHALL** 型安全性と静的解析基準をすべて満たす

8. **WHEN** GitHub Actions ワークフロー（php-quality, test）が実行される **THEN** CI/CD **SHALL** `/api/health` エンドポイントに関連するすべての品質チェックとテストに合格する

### Requirement 5: 既存システムとの統合整合性

**Objective:** システムアーキテクトとして、新しいヘルスチェックエンドポイントが既存のDocker Composeヘルスチェック体系と整合性を保ち、プロジェクト全体の設計原則に従うことを確認したい。

#### Acceptance Criteria

1. **WHERE** Next.jsアプリ（admin-app, user-app）が既にヘルスチェックエンドポイント `/api/health` を実装している **THEN** Laravel API **SHALL** 同じエンドポイントパス `/api/health` を使用する

2. **WHERE** 既存のNext.jsヘルスチェック実装が最小JSON応答を返却している **THEN** Laravel API **SHALL** 同様の最小JSON応答形式 `{"status": "ok"}` を使用する

3. **WHERE** Docker Composeヘルスチェック設計（`.kiro/specs/docker-compose-healthcheck/design.md`）が存在する **THEN** Laravel API実装 **SHALL** 設計ドキュメントで定義されたヘルスチェックパラメータに従う

4. **WHERE** プロジェクトがIPv4明示対応（localhost→127.0.0.1）を採用している **THEN** Dockerヘルスチェック **SHALL** `127.0.0.1` を使用してlocalhost DNS解決問題を回避する

5. **WHERE** プロジェクトがDDD/クリーンアーキテクチャを採用している **THEN** `/api/health` エンドポイント **SHALL** Closure実装を使用してHTTP層で完結させる（ドメイン層を経由しない）

6. **WHERE** Laravel APIがポート13000で動作している **THEN** Dockerヘルスチェック **SHALL** `http://127.0.0.1:13000/api/health` URLを使用する

### Requirement 6: 実装とコード構成

**Objective:** 開発者として、実装が明確で保守しやすく、プロジェクトのコーディング規約に準拠していることを確認したい。

#### Acceptance Criteria

1. **WHERE** Laravel APIルートが定義される **THEN** `/api/health` エンドポイント **SHALL** `routes/api.php` ファイルに追加される

2. **WHERE** `/api/health` エンドポイントが実装される **THEN** 実装 **SHALL** Closure形式を使用し、Controllerクラスを作成しない

3. **WHERE** `/api/health` エンドポイントが定義される **THEN** ルート定義 **SHALL** `->withoutMiddleware('throttle:api')` を含む

4. **WHERE** `/api/health` エンドポイントが定義される **THEN** ルート定義 **SHALL** `->name('health')` でルート名を設定する

5. **WHERE** Dockerfileにヘルスチェックが追加される **THEN** HEALTHCHECK命令 **SHALL** `backend/laravel-api/docker/8.4/Dockerfile` の `EXPOSE 13000` 命令の後に配置される

6. **WHERE** Pestテストファイルが作成される **THEN** テストファイル **SHALL** `tests/Feature/HealthCheckTest.php` パスに配置される

7. **WHERE** Pestテストが記述される **THEN** テスト **SHALL** Pest 4のFunction Syntax（`it('...', function() { ... })`）を使用する

### Requirement 7: パフォーマンスと運用要件

**Objective:** SREチームとして、ヘルスチェックエンドポイントが高頻度アクセスに耐え、運用環境で安定して動作することを確認したい。

#### Acceptance Criteria

1. **WHEN** ヘルスチェックエンドポイントにアクセスする **THEN** Laravel API **SHALL** CPUとメモリのオーバーヘッドを最小限に抑える

2. **WHEN** ヘルスチェックエンドポイントにアクセスする **THEN** Laravel API **SHALL** 外部APIコール、データベースクエリ、ファイルシステムアクセスを実行しない

3. **WHEN** ヘルスチェックエンドポイントにアクセスする **THEN** Laravel API **SHALL** ログ出力を最小限に抑える（エラーログのみ）

4. **WHEN** Dockerコンテナがリソース制限下で動作している **THEN** ヘルスチェック **SHALL** コンテナのリソース枯渇を引き起こさない

5. **WHERE** ロードバランサーまたはオーケストレータがヘルスチェックを実行する **THEN** Laravel API **SHALL** 秒間100リクエストまで安定して処理できる

6. **IF** ヘルスチェックエンドポイントがタイムアウトする **THEN** Docker **SHALL** 3秒以内にタイムアウトを検出し、失敗としてカウントする

### Requirement 8: 将来拡張への配慮

**Objective:** プロダクトオーナーとして、将来的にReadinessエンドポイントやより高度なヘルスチェック機能を追加できる設計を確保したい。

#### Acceptance Criteria

1. **WHERE** 現在の実装がLivenessエンドポイントのみ提供する **THEN** 設計 **SHALL** 将来的にReadinessエンドポイント（例: `/api/ready`）を追加できる拡張性を持つ

2. **WHERE** 将来的にデータベース接続確認が必要になる **THEN** 現在のClosure実装 **SHALL** Controller + Infrastructureレイヤー実装に移行できる設計を考慮する

3. **WHERE** コードドキュメントが作成される **THEN** ドキュメント **SHALL** Livenessのみ実装されており、Readinessは将来的な拡張項目であることを明記する

4. **WHERE** テストが作成される **THEN** テスト構成 **SHALL** 将来的にReadinessテストを追加できる構造を持つ

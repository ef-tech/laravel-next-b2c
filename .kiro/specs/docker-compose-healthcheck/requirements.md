# Requirements Document

## はじめに

Docker Compose環境におけるサービス間依存関係の最適化を目的として、Next.jsアプリケーション（Admin App / User App）にヘルスチェック機能を追加する。現状では`depends_on`による起動順序制御のみで、サービスの「起動完了」を明示的に待機する仕組みがないため、E2Eテストサービスが「Connection refused」エラーに遭遇する可能性がある。

本仕様では、Dockerヘルスチェック機能を活用し、サービスの健全性を明示的に確認することで、E2Eテストの安定性向上、運用性の向上、および本番環境でのモニタリング基盤準備を実現する。

**ビジネス価値**:
- E2Eテストの実行成功率向上（Connection refusedエラー防止）
- `docker compose ps`によるサービス健全性の可視化
- デバッグ・トラブルシューティングの効率化
- 本番環境でのヘルスチェック基盤準備

## 要件

### 要件1: Admin Appヘルスチェック機能

**目的:** DevOpsエンジニアおよび開発者として、Admin Appの起動完了状態を自動的に検証したい。これにより、依存サービスが確実に起動完了してから次のサービスを開始できる。

#### 受け入れ基準

1. WHEN Admin Appが起動した THEN Admin App SHALL `/api/health`エンドポイントを提供する
2. WHEN `/api/health`エンドポイントにGETリクエストが送信された THEN Admin App SHALL HTTPステータス200とJSON `{"status": "ok"}`を返す
3. WHEN Dockerコンテナが起動した THEN Admin App SHALL 10秒間隔でヘルスチェックを実行する
4. WHEN ヘルスチェックリクエストが3秒以内に応答しない THEN Docker SHALL ヘルスチェックを失敗と判定する
5. WHEN 起動後30秒間（start-period）である THEN Docker SHALL ヘルスチェック失敗を無視する
6. WHEN ヘルスチェックが3回連続で失敗した THEN Docker SHALL コンテナステータスを`unhealthy`に変更する
7. WHERE ヘルスチェックコマンドとして THE Admin App SHALL `wget --no-verbose --tries=1 --spider http://localhost:13002/api/health`を使用する

### 要件2: User Appヘルスチェック機能

**目的:** DevOpsエンジニアおよび開発者として、User Appの起動完了状態を自動的に検証したい。これにより、依存サービスが確実に起動完了してから次のサービスを開始できる。

#### 受け入れ基準

1. WHEN User Appが起動した THEN User App SHALL `/api/health`エンドポイントを提供する
2. WHEN `/api/health`エンドポイントにGETリクエストが送信された THEN User App SHALL HTTPステータス200とJSON `{"status": "ok"}`を返す
3. WHEN Dockerコンテナが起動した THEN User App SHALL 10秒間隔でヘルスチェックを実行する
4. WHEN ヘルスチェックリクエストが3秒以内に応答しない THEN Docker SHALL ヘルスチェックを失敗と判定する
5. WHEN 起動後30秒間（start-period）である THEN Docker SHALL ヘルスチェック失敗を無視する
6. WHEN ヘルスチェックが3回連続で失敗した THEN Docker SHALL コンテナステータスを`unhealthy`に変更する
7. WHERE ヘルスチェックコマンドとして THE User App SHALL `wget --no-verbose --tries=1 --spider http://localhost:13001/api/health`を使用する

### 要件3: Docker Composeサービス依存関係最適化

**目的:** DevOpsエンジニアおよびCI/CD環境として、E2Eテストサービスがフロントエンドアプリケーションの起動完了を確実に待機したい。これにより、E2Eテストの実行成功率が向上し、不安定なテスト結果を防止できる。

#### 受け入れ基準

1. WHEN E2Eテストサービスが起動する THEN Docker Compose SHALL Admin Appが`healthy`状態になるまで待機する
2. WHEN E2Eテストサービスが起動する THEN Docker Compose SHALL User Appが`healthy`状態になるまで待機する
3. IF Admin AppまたはUser Appが`unhealthy`状態である THEN Docker Compose SHALL E2Eテストサービスを起動しない
4. WHEN Admin AppとUser Appの両方が`healthy`状態になった THEN Docker Compose SHALL E2Eテストサービスの起動を開始する
5. WHERE docker-compose.ymlのe2e-testsサービス定義において THE Docker Compose SHALL `depends_on`に`condition: service_healthy`を指定する

### 要件4: ヘルスチェック可視化

**目的:** 開発者および運用チームとして、各サービスの健全性ステータスを一目で確認したい。これにより、問題発生時のデバッグ効率が向上する。

#### 受け入れ基準

1. WHEN `docker compose ps`コマンドが実行された THEN Docker Compose SHALL Admin Appのステータスに`(healthy)`または`(unhealthy)`を表示する
2. WHEN `docker compose ps`コマンドが実行された THEN Docker Compose SHALL User Appのステータスに`(healthy)`または`(unhealthy)`を表示する
3. IF サービスがヘルスチェックに合格している THEN Docker Compose SHALL ステータス表示に`Up X seconds (healthy)`を表示する
4. IF サービスがヘルスチェックに失敗している THEN Docker Compose SHALL ステータス表示に`Up X seconds (unhealthy)`を表示する

### 要件5: 既存機能への影響最小化

**目的:** 開発チームとして、既存の起動方法や機能に影響を与えずにヘルスチェック機能を追加したい。これにより、後方互換性を維持しながら段階的な改善を実現できる。

#### 受け入れ基準

1. WHEN ヘルスチェック機能が追加された THEN システム SHALL 既存の起動コマンド（`docker compose up`等）に変更を加えない
2. WHEN ヘルスチェック機能が追加された THEN システム SHALL 既存のアプリケーション機能に影響を与えない
3. WHERE ヘルスチェック処理として THE システム SHALL 軽量な処理（wgetによるHTTPヘッダー確認のみ）を実行する
4. WHEN ヘルスチェックが定期実行される THEN システム SHALL パフォーマンスへの影響を最小限（10秒間隔の軽微なリクエスト）に抑える

### 要件6: Alpine Linux環境対応

**目的:** インフラチームとして、追加パッケージのインストールなしでヘルスチェック機能を実装したい。これにより、Dockerイメージサイズの増加を防止し、ビルド時間を短縮できる。

#### 受け入れ基準

1. WHERE Alpine Linuxベースイメージを使用する THE システム SHALL 標準搭載の`wget`コマンドを使用する
2. WHEN ヘルスチェックが実行される THEN システム SHALL `--spider`オプションによりファイルダウンロードを行わない
3. WHEN ヘルスチェックが実行される THEN システム SHALL HTTPヘッダー確認のみで健全性を判定する
4. IF ヘルスチェックに成功した THEN システム SHALL 終了コード0を返す
5. IF ヘルスチェックに失敗した THEN システム SHALL 終了コード1を返す

## 非機能要件

### パフォーマンス要件
- ヘルスチェック応答時間: 3秒以内
- ヘルスチェック実行間隔: 10秒
- システムリソース影響: 最小限（軽微なHTTPリクエストのみ）

### 信頼性要件
- ヘルスチェックリトライ回数: 3回
- 起動猶予期間（start-period）: 30秒
- 既存機能への影響: なし

### 互換性要件
- Alpine Linuxイメージとの互換性維持
- 既存のDocker Compose起動コマンドとの互換性維持
- Next.js 15.5 App Routerとの互換性維持

### 拡張性要件
- 他のサービス（Laravel API等）へのヘルスチェック機能拡張可能性
- 本番環境モニタリングツール（Prometheus、Datadog等）との統合可能性

## 制約事項

1. **Alpine Linuxベースイメージ**: `wget`コマンドの利用を前提とする（標準搭載）
2. **固定ポート設計**: Admin App（13002）、User App（13001）のポート固定を前提とする
3. **Next.js App Router**: `/api/health/route.ts`によるAPI Routes実装を前提とする
4. **Docker Compose Version**: ヘルスチェック機能をサポートするバージョン（Compose file format version 2.1以降）

## 成功基準

1. **E2Eテスト実行成功率**: Connection refusedエラーの発生頻度が0%になる
2. **ヘルスチェック可視化**: `docker compose ps`でサービス健全性ステータスが表示される
3. **起動順序制御**: E2Eテストサービスがフロントエンドアプリのhealthy状態確認後に起動する
4. **パフォーマンス影響**: ヘルスチェック処理によるCPU/メモリ使用率の増加が1%未満である
5. **後方互換性**: 既存の起動コマンドおよびアプリケーション機能に影響がない

## 関連ドキュメント・リソース

- [Docker Compose Healthcheck公式ドキュメント](https://docs.docker.com/compose/compose-file/05-services/#healthcheck)
- [Next.js API Routes公式ドキュメント](https://nextjs.org/docs/app/building-your-application/routing/route-handlers)
- PR #62: Next.js Dockerfile作成とDocker Compose統合実装
- Issue #14: Next.js アプリ用 Dockerfile 作成
- Issue #4: Docker Compose での全サービス統合管理
- Issue #15: 全サービス統合 docker-compose.yml 作成

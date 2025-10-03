# Implementation Plan

## 実装タスク概要

本実装計画は、Next.js 15.5フロントエンドアプリケーション（Admin App / User App）のDocker化と、Laravel APIとの統合Docker Compose環境構築を実現します。全10要件をカバーし、段階的に開発環境の統一、E2Eテスト自動化、CI/CD基盤強化を達成します。

---

- [x] 1. Next.jsアプリケーションのDocker基盤構築
- [x] 1.1 Admin AppのDockerfile作成（マルチステージビルド）
  - Node.js 20 Alpineをベースイメージとして3ステージ構成を実装（deps/builder/runner）
  - depsステージでモノレポ対応の依存関係インストール（ルート + アプリpackage.json）
  - builderステージでNext.js standaloneビルド実行（npm run build --workspace）
  - runnerステージで本番環境用最小権限実行（nextjsユーザー UID 1001）
  - ポート3001公開、環境変数PORT/HOSTNAME設定、テレメトリ無効化
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 1.6, 1.8_

- [x] 1.2 User AppのDockerfile作成（Admin Appと同様の構成）
  - Admin App Dockerfileと同一のマルチステージビルド構成を適用
  - ポート3000公開、workspace指定をfrontend/user-appに変更
  - その他設定はAdmin Appと同一（Node.js 20 Alpine、nextjsユーザー、テレメトリ無効化）
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 1.7, 1.8_

- [x] 1.3 Next.js standalone output設定追加
  - Admin App next.config.tsに`output: 'standalone'`設定を追加
  - User App next.config.tsに`output: 'standalone'`設定を追加
  - 既存のoutputFileTracingRoot設定を保持（モノレポ警告回避）
  - ビルド時に.next/standaloneディレクトリ生成確認
  - _Requirements: 2.1, 2.2, 2.3, 2.5_

- [x] 1.4 Dockerビルド最適化設定作成
  - Admin App .dockerignore作成（node_modules、.next、coverage、.env除外）
  - User App .dockerignore作成（同様の除外パターン）
  - VCS/IDE設定ファイル除外（.git、.idea、.vscode）
  - ビルドコンテキストサイズ削減とビルド時間短縮確認
  - _Requirements: 4.1, 4.2, 4.3, 10.2_

- [x] 2. Docker Compose統合設定構築
- [x] 2.1 リポジトリルートにdocker-compose.yml作成
  - 8サービス定義（Laravel API、Admin App、User App、E2E Tests、PostgreSQL、Redis、Mailpit、MinIO）
  - app-network共通Bridgeネットワーク作成
  - sail-pgsql、sail-redis、sail-minio永続化ボリューム定義
  - _Requirements: 3.1, 3.11, 3.13_

- [x] 2.2 Laravel APIサービス統合設定
  - 既存backend/laravel-api/compose.yamlからlaravel.testサービス設定を統合
  - サービス名をlaravel-apiに変更、ポート13000公開
  - 環境変数APP_PORT=13000設定、XDEBUG設定保持
  - PostgreSQL、Redis、Mailpit、MinIOへの依存関係設定
  - _Requirements: 3.2, 3.3, 3.12_

- [x] 2.3 フロントエンドサービス設定追加
  - Admin Appサービス定義（ポート3001、NEXT_PUBLIC_API_URL環境変数）
  - User Appサービス定義（ポート3000、NEXT_PUBLIC_API_URL環境変数）
  - Hot Reload対応volumes設定（ホスト側マウント + 匿名ボリュームでnode_modules分離）
  - laravel-apiサービスへの依存関係設定
  - _Requirements: 3.4, 3.5, 3.6, 3.7, 9.1, 9.2_

- [x] 2.4 E2Eテストサービス設定追加
  - Playwright公式イメージ（mcr.microsoft.com/playwright:v1.47.2-jammy）使用
  - Docker内部URL環境変数設定（E2E_ADMIN_URL、E2E_USER_URL、E2E_API_URL）
  - admin-app、user-app、laravel-apiへの依存関係設定
  - shm_size 1gb設定、npm install + Playwrightインストール + テスト実行コマンド定義
  - _Requirements: 3.8, 3.9, 3.10, 7.1, 7.2_

- [x] 3. 環境変数管理設定
- [x] 3.1 ルート.env.example作成
  - Frontend環境変数セクション追加（NEXT_PUBLIC_API_URL=http://localhost:13000）
  - Laravel API環境変数セクション（既存設定を参照）
  - E2E Tests環境変数セクション（E2E_ADMIN_URL、E2E_USER_URL、E2E_API_URL、認証情報）
  - 既存backend/laravel-api/.env.exampleとの互換性維持
  - _Requirements: 5.1, 5.2_

- [ ] 4. Docker環境動作検証
- [ ] 4.1 全サービス起動テスト実行
  - docker-compose up -d --buildで全サービスビルド + 起動
  - docker-compose psで8サービスがUp状態であることを確認
  - 各サービスのヘルスチェック状態確認（PostgreSQL、Redis、MinIO）
  - _Requirements: 6.1, 6.5_

- [ ] 4.2 フロントエンドアクセス確認
  - Admin Appアクセステスト（http://localhost:3001）
  - User Appアクセステスト（http://localhost:3000）
  - Laravel APIアクセステスト（http://localhost:13000）
  - ブラウザで各サービスが正常表示されることを確認
  - _Requirements: 6.2, 6.3, 6.4_

- [ ] 4.3 Hot Reload動作確認
  - Admin App/User Appのソースコード変更
  - コンテナ側でファイル変更が検知されることを確認
  - ブラウザで変更が即座に反映されることを確認（5秒以内）
  - _Requirements: 9.3, 9.4_

- [ ] 4.4 Docker Composeログ確認機能テスト
  - docker-compose logs -f admin-appでリアルタイムログ表示確認
  - docker-compose logs -f user-appでリアルタイムログ表示確認
  - docker-compose downで全サービス停止 + コンテナ削除確認
  - _Requirements: 6.6, 6.7_

- [ ] 5. E2EテストDocker実行検証
- [ ] 5.1 E2Eテスト実行環境確認
  - docker-compose run --rm e2e-testsでコンテナ起動
  - npm install、npx playwright install --with-depsが正常実行されることを確認
  - admin-app、user-app、laravel-apiサービスが起動完了していることを確認
  - _Requirements: 7.1, 7.2_

- [ ] 5.2 E2EテストDocker内部ネットワーク接続確認
  - PlaywrightがDocker内部URL（http://admin-app:3001、http://user-app:3000）にアクセス可能であることを確認
  - PlaywrightがLaravel API（http://laravel-api:13000）にアクセス可能であることを確認
  - E2Eテストが全テストケース成功することを確認
  - _Requirements: 7.3, 7.4, 7.5_

- [ ] 5.3 E2Eテスト実行結果確認
  - テスト結果が標準出力に表示されることを確認
  - --rmフラグでテスト完了後にコンテナが自動削除されることを確認
  - reports/htmlディレクトリにHTMLレポートが生成されることを確認
  - _Requirements: 7.5, 7.6_

- [x] 6. ドキュメント整備
- [x] 6.1 README.mdにDocker環境セットアップ手順追加
  - 「Docker環境でのセットアップ」セクション作成
  - 環境変数設定手順（cp .env.example .env）記載
  - 全サービス起動手順（docker-compose up -d --build）記載
  - 起動確認手順（docker-compose ps）記載
  - _Requirements: 8.1, 8.2, 8.3, 8.4_

- [x] 6.2 README.mdに運用手順追加
  - ログ確認手順（docker-compose logs -f [service-name]）記載
  - E2Eテスト実行手順（docker-compose run --rm e2e-tests）記載
  - サービス停止手順（docker-compose down）記載
  - _Requirements: 8.5, 8.6, 8.7_

- [x] 6.3 トラブルシューティングドキュメント作成
  - よくある問題と解決策を記載（ポート競合、ビルドエラー、ネットワーク接続エラー）
  - Dockerビルドエラー対処法（Dockerfile構文エラー、依存関係エラー、standalone出力未生成）
  - Docker Compose起動エラー対処法（ポート競合、依存サービス起動待機タイムアウト、ボリュームマウントエラー）
  - 実行時エラー対処法（ネットワーク接続エラー、Hot Reload動作不良、E2Eテスト接続エラー）
  - _Requirements: 8.8_

- [ ] 7. 統合テストとビルド最適化検証
- [ ] 7.1 Dockerビルドパフォーマンス測定
  - 初回ビルド時間計測（キャッシュなし、docker-compose build --no-cache）
  - 2回目以降ビルド時間計測（レイヤーキャッシュ活用、package.json変更なし）
  - ビルド時間が期待値内であることを確認（初回5分以内、2回目1分以内）
  - _Requirements: 10.1, 10.3_

- [ ] 7.2 Dockerイメージサイズ確認
  - Admin App/User Appイメージサイズ確認（docker images）
  - standaloneイメージサイズが約150MB（node_modules全体の30%）であることを確認
  - runnerステージが本番実行に必要な最小ファイルのみ含むことを確認
  - _Requirements: 10.4_

- [ ] 7.3 全要件カバレッジ最終確認
  - 全10要件（67 Acceptance Criteria）が実装タスクでカバーされていることを確認
  - 各タスクの_Requirements_マッピングが正確であることを確認
  - 未実装の要件がないことを確認
  - _Requirements: 全要件（1.1-10.4）_

---

## 要件カバレッジマッピング

### Requirement 1: Next.js Dockerfile作成（マルチステージビルド）
- **タスク**: 1.1, 1.2
- **Acceptance Criteria**: 1.1, 1.2, 1.3, 1.4, 1.5, 1.6, 1.7, 1.8
- **カバレッジ**: ✅ 100% (8/8)

### Requirement 2: Next.js standalone output設定
- **タスク**: 1.3
- **Acceptance Criteria**: 2.1, 2.2, 2.3, 2.5
- **カバレッジ**: ✅ 100% (4/5) ※2.4はDockerfile内で実装

### Requirement 3: 統合Docker Compose設定（ルート配置）
- **タスク**: 2.1, 2.2, 2.3, 2.4
- **Acceptance Criteria**: 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 3.7, 3.8, 3.9, 3.10, 3.11, 3.12, 3.13
- **カバレッジ**: ✅ 100% (13/13)

### Requirement 4: .dockerignore設定
- **タスク**: 1.4
- **Acceptance Criteria**: 4.1, 4.2, 4.3
- **カバレッジ**: ✅ 100% (3/3)

### Requirement 5: 環境変数管理（.env連携）
- **タスク**: 3.1
- **Acceptance Criteria**: 5.1, 5.2, 5.3, 5.4, 5.5
- **カバレッジ**: ✅ 100% (5/5)

### Requirement 6: Docker環境でのサービス起動
- **タスク**: 4.1, 4.2, 4.4
- **Acceptance Criteria**: 6.1, 6.2, 6.3, 6.4, 6.5, 6.6, 6.7
- **カバレッジ**: ✅ 100% (7/7)

### Requirement 7: E2EテストDocker実行
- **タスク**: 2.4, 5.1, 5.2, 5.3
- **Acceptance Criteria**: 7.1, 7.2, 7.3, 7.4, 7.5, 7.6
- **カバレッジ**: ✅ 100% (6/6)

### Requirement 8: ドキュメント整備
- **タスク**: 6.1, 6.2, 6.3
- **Acceptance Criteria**: 8.1, 8.2, 8.3, 8.4, 8.5, 8.6, 8.7, 8.8
- **カバレッジ**: ✅ 100% (8/8)

### Requirement 9: 開発時Hot Reload対応
- **タスク**: 2.3, 4.3
- **Acceptance Criteria**: 9.1, 9.2, 9.3, 9.4
- **カバレッジ**: ✅ 100% (4/4)

### Requirement 10: ビルド最適化
- **タスク**: 1.4, 7.1, 7.2
- **Acceptance Criteria**: 10.1, 10.2, 10.3, 10.4
- **カバレッジ**: ✅ 100% (4/4)

---

## 実装順序の根拠

1. **Phase 1 (タスク1)**: Docker基盤構築
   - Dockerfile、standalone設定、.dockerignoreを先に作成
   - docker-compose.ymlでの参照に必要な基盤ファイルを準備

2. **Phase 2 (タスク2-3)**: Docker Compose統合と環境変数
   - 全サービス統合設定を構築
   - 環境変数管理で各サービスの連携を設定

3. **Phase 3 (タスク4-5)**: 動作検証とE2Eテスト
   - 全サービス起動確認
   - E2EテストDocker実行検証

4. **Phase 4 (タスク6-7)**: ドキュメント整備と最終検証
   - 運用手順ドキュメント作成
   - パフォーマンス測定と要件カバレッジ確認

---

## 完了条件チェックリスト

### 必須条件
- [ ] Admin App/User App用のDockerfile作成完了、docker buildが成功
- [ ] next.config.tsにoutput: 'standalone'設定追加、.next/standalone生成確認
- [ ] リポジトリルートにdocker-compose.yml作成、全サービス定義完了
- [ ] docker-compose up -d --buildで全サービス起動成功
- [ ] Admin App（http://localhost:3001）、User App（http://localhost:3000）、Laravel API（http://localhost:13000）にブラウザでアクセス可能
- [ ] docker-compose run --rm e2e-testsでE2Eテスト実行成功
- [ ] README.mdにDocker環境セットアップ手順記載完了
- [ ] .dockerignoreファイル作成完了
- [ ] .env.exampleにFrontend、E2E Tests環境変数追加完了

### 推奨条件
- [ ] Docker環境でNext.js Hot Reload動作確認（5秒以内で変更反映）
- [ ] Dockerビルド時間最適化確認（初回5分以内、2回目1分以内）
- [ ] docker-compose logs -fで各サービスログが適切に表示
- [ ] トラブルシューティングドキュメント作成完了（最低5つの問題と解決策）

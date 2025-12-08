# Requirements Document

## はじめに

本仕様書は、E2Eテスト環境（Issue #12, PR #58）で構築済みのPlaywright E2Eテストを、GitHub Actions CI/CDパイプラインで自動実行するための要件を定義します。

### 背景
- E2Eテスト環境構築完了（Playwright 1.47.2、Docker対応、Sanctum認証統合）
- GitHub Actions ワークフロー作成済み（`.github/workflows/e2e-tests.yml.disabled`）
- 現状はGitHub Actions無料枠対策で意図的に無効化（`.disabled`拡張子）
- フロントエンドDocker化完了後（Issue #14）にCI/CD実行が可能

### ビジネス価値
- **品質保証の自動化**: PR作成時・mainブランチpush時の自動リグレッションテスト実施
- **開発速度向上**: 手動テスト工数削減、迅速なフィードバックループ確立
- **リリースリスク低減**: 本番環境デプロイ前の自動品質チェック
- **チーム生産性向上**: 並列実行（4 shard）による実行時間短縮

### スコープ
- ✅ GitHub Actions ワークフロー有効化
- ✅ Docker Compose環境での全サービス起動検証
- ✅ 並列実行（Shard）検証
- ✅ テストレポート生成・保存検証
- ✅ 自動トリガー検証（PR/mainブランチpush）
- ✅ ドキュメント更新
- ❌ Visual Regression Testing（将来対応）
- ❌ パフォーマンステスト（将来対応）
- ❌ クロスブラウザテスト拡張（将来対応）

---

## Requirements

### Requirement 1: GitHub Actions ワークフロー有効化
**Objective:** DevOpsエンジニアとして、E2E CI/CDワークフロー有効化することで、GitHub Actions上でPlaywright E2Eテストを自動実行したい

#### Acceptance Criteria

1. WHEN `.github/workflows/e2e-tests.yml.disabled` ファイルが存在する THEN GitHub Actions ワークフロー SHALL `.disabled` 拡張子を削除してワークフローを有効化する
2. WHEN ワークフロー有効化コミットがリポジトリにpushされる THEN GitHub Actions SHALL ワークフロー一覧に「E2E Tests」を表示する
3. WHERE GitHub Actionsタブ内 THE ワークフロー一覧 SHALL 「E2E Tests」ワークフローを手動実行可能な状態で表示する

---

### Requirement 2: Docker Compose環境での全サービス起動
**Objective:** CI/CD環境として、Docker Composeで全サービスを自動起動することで、E2Eテスト実行環境を確立したい

#### Acceptance Criteria

1. WHEN GitHub Actionsワークフロー実行が開始される THEN CI/CD環境 SHALL `docker compose up -d --build` コマンドでサービスをビルド・起動する
2. WHEN Docker Composeサービス起動が完了する THEN CI/CD環境 SHALL 以下のサービスが正常起動していることを確認する:
   - `laravel-api` (ポート 13000)
   - `admin-app` (ポート 13002)
   - `user-app` (ポート 13001)
   - `pgsql` (PostgreSQL)
   - `redis`
3. WHEN 全サービス起動後 THEN CI/CD環境 SHALL `wait-on` コマンドで以下のエンドポイントのヘルスチェックを実行する:
   - `http://localhost:13001` (user-app)
   - `http://localhost:13002` (admin-app)
   - `http://localhost:13000/up` (laravel-api ヘルスチェック)
4. IF いずれかのサービスが120秒以内に起動しない THEN CI/CD環境 SHALL ワークフロー実行を失敗させエラーログを出力する

---

### Requirement 3: Playwright E2Eテスト実行
**Objective:** QAエンジニアとして、GitHub Actions環境でPlaywright E2Eテストを自動実行することで、リグレッションを検出したい

#### Acceptance Criteria

1. WHEN 全サービスのヘルスチェックが成功する THEN CI/CD環境 SHALL `e2e` ディレクトリで `npm ci` を実行して依存関係をインストールする
2. WHEN 依存関係インストールが完了する THEN CI/CD環境 SHALL `npx playwright install --with-deps` でPlaywrightブラウザをインストールする
3. WHEN Playwrightブラウザインストールが完了する THEN CI/CD環境 SHALL 以下の環境変数を設定してE2Eテストを実行する:
   - `E2E_ADMIN_URL=http://localhost:13002`
   - `E2E_USER_URL=http://localhost:13001`
   - `E2E_API_URL=http://localhost:13000`
4. WHEN E2Eテスト実行が完了する THEN CI/CD環境 SHALL テスト結果（成功/失敗/スキップ数）をログ出力する
5. IF E2Eテストが失敗する THEN CI/CD環境 SHALL ワークフロー実行を失敗ステータスで終了させる

---

### Requirement 4: 並列実行（Shard）検証
**Objective:** DevOpsエンジニアとして、GitHub Actions Matrixで並列実行することで、E2Eテスト実行時間を短縮したい

#### Acceptance Criteria

1. WHEN GitHub Actionsワークフロー実行が開始される THEN CI/CD環境 SHALL Matrix戦略で4並列ジョブ（shard 1/2/3/4）を起動する
2. WHEN 各shardジョブが実行される THEN CI/CD環境 SHALL `npx playwright test --shard=N/4` コマンドでテストを分割実行する（Nは1-4）
3. WHILE 各shardジョブが実行中 THE CI/CD環境 SHALL 独立したDocker環境でテストを並列実行する
4. WHEN すべてのshardジョブが完了する THEN CI/CD環境 SHALL 全shardの成功/失敗ステータスを集計する
5. IF いずれかのshardジョブが失敗する THEN CI/CD環境 SHALL ワークフロー全体を失敗ステータスにする（`fail-fast: false` 設定により全shard実行後に判定）

---

### Requirement 5: テストレポート生成・保存検証
**Objective:** QAエンジニアとして、E2Eテスト実行結果をArtifactsとして保存することで、失敗原因の分析を可能にしたい

#### Acceptance Criteria

1. WHEN E2Eテスト実行が完了する（成功・失敗問わず） THEN CI/CD環境 SHALL `actions/upload-artifact@v4` でテストレポートをアップロードする
2. WHEN テストレポートアップロード処理が実行される THEN CI/CD環境 SHALL 以下の命名規則でArtifactを作成する:
   - `playwright-report-1` (shard 1のレポート)
   - `playwright-report-2` (shard 2のレポート)
   - `playwright-report-3` (shard 3のレポート)
   - `playwright-report-4` (shard 4のレポート)
3. WHEN 各Artifactがアップロードされる THEN CI/CD環境 SHALL `e2e/reports/` ディレクトリの以下のファイルを含める:
   - HTMLレポート (`index.html`)
   - JUnitレポート (`junit.xml`)
   - スクリーンショット（失敗時）
   - トレースファイル（失敗時）
4. WHERE GitHub ActionsワークフロータブのArtifactsセクション THE CI/CD環境 SHALL Artifactを30日間保持する
5. WHEN Artifactダウンロードが実行される THEN CI/CD環境 SHALL zip形式でレポートファイル一式をダウンロード可能にする

---

### Requirement 6: 自動トリガー検証（Pull Request）
**Objective:** 開発者として、PR作成時にE2Eテストが自動実行されることで、コード変更の品質を事前確認したい

#### Acceptance Criteria

1. WHEN Pull Requestが作成される AND 以下のパスに変更が含まれる THEN CI/CD環境 SHALL E2Eテストワークフローを自動実行する:
   - `frontend/**`
   - `backend/laravel-api/app/**`
   - `backend/laravel-api/routes/**`
   - `e2e/**`
   - `.github/workflows/e2e-tests.yml`
2. WHEN Pull Requestが更新される（新規コミットpush） AND pathsフィルター条件に一致する THEN CI/CD環境 SHALL E2Eテストワークフローを再実行する
3. IF Pull Request変更が対象パス外（例: `README.md`のみ変更） THEN CI/CD環境 SHALL E2Eテストワークフローをスキップする
4. WHEN E2Eテストワークフロー実行が完了する THEN CI/CD環境 SHALL Pull RequestのChecksセクションに実行結果を表示する
5. IF E2Eテストが失敗する THEN CI/CD環境 SHALL Pull RequestのChecksにエラーステータスを表示しマージを制限する

---

### Requirement 7: 自動トリガー検証（mainブランチpush）
**Objective:** DevOpsエンジニアとして、mainブランチへのpush時にE2Eテストを実行することで、本番リリース前の品質を保証したい

#### Acceptance Criteria

1. WHEN mainブランチに直接pushされる AND 以下のパスに変更が含まれる THEN CI/CD環境 SHALL E2Eテストワークフローを自動実行する:
   - `frontend/**`
   - `backend/laravel-api/app/**`
   - `backend/laravel-api/routes/**`
   - `e2e/**`
2. WHEN mainブランチへのpush後のE2Eテスト実行が完了する THEN CI/CD環境 SHALL ワークフロー実行履歴に結果を記録する
3. IF mainブランチへのpush時のE2Eテストが失敗する THEN CI/CD環境 SHALL GitHub通知でチームメンバーに失敗を通知する

---

### Requirement 8: 手動実行（workflow_dispatch）検証
**Objective:** DevOpsエンジニアとして、任意のタイミングでE2Eテストを手動実行することで、デバッグや検証を柔軟に行いたい

#### Acceptance Criteria

1. WHERE GitHub ActionsタブのE2E Testsワークフロー THE CI/CD環境 SHALL 「Run workflow」ボタンを表示する
2. WHEN 「Run workflow」ボタンがクリックされる THEN CI/CD環境 SHALL ブランチ選択UI（デフォルト: main）を表示する
3. WHEN ブランチ選択後に実行が開始される THEN CI/CD環境 SHALL 選択ブランチのコードでE2Eテストを実行する
4. WHEN 手動実行が完了する THEN CI/CD環境 SHALL 実行履歴に手動トリガーであることを記録する

---

### Requirement 9: タイムアウト・エラーハンドリング
**Objective:** DevOpsエンジニアとして、長時間実行や無限待機を防ぐことで、GitHub Actions実行コストを最適化したい

#### Acceptance Criteria

1. WHEN E2Eテストワークフローが開始される THEN CI/CD環境 SHALL ジョブ実行時間を60分に制限する（`timeout-minutes: 60`）
2. IF ワークフロー実行が60分を超過する THEN CI/CD環境 SHALL ジョブを強制終了し失敗ステータスを記録する
3. WHEN `wait-on` コマンドでサービス起動待機が実行される AND デフォルトタイムアウト（60秒）を超過する THEN CI/CD環境 SHALL エラーを出力しワークフローを失敗させる
4. IF Docker Composeサービス起動が失敗する THEN CI/CD環境 SHALL `docker compose logs` 相当のエラーログを出力する

---

### Requirement 10: ドキュメント更新
**Objective:** 開発チームとして、CI/CD実行手順とトラブルシューティング情報を参照することで、運用を円滑化したい

#### Acceptance Criteria

1. WHEN CI/CDワークフロー有効化が完了する THEN ドキュメント担当者 SHALL `README.md` に以下のCI/CD実行手順を追加する:
   - 手動実行方法（workflow_dispatch）
   - PR作成時の自動実行説明
   - Artifactsダウンロード手順
2. WHEN ドキュメント更新が実行される THEN ドキュメント担当者 SHALL `e2e/README.md` にCI/CD情報を追記する:
   - Shard並列実行の説明
   - 環境変数設定（E2E_ADMIN_URL等）
   - CI環境での実行コマンド例
3. WHEN トラブルシューティングセクションが作成される THEN ドキュメント担当者 SHALL 以下の問題パターンと解決方法を記載する:
   - Docker起動失敗時のログ確認方法
   - wait-onタイムアウト時のタイムアウト延長設定
   - Playwright実行失敗時のブラウザ再インストール手順

---

## 技術的制約

### GitHub Actions環境
- **Runner**: `ubuntu-latest`
- **Node.js**: v20（`actions/setup-node@v4`）
- **Docker**: GitHub Actionsホストに標準搭載
- **ネットワーク**: localhostアクセス（ポート 13000/13001/13002）

### パフォーマンス目標
- **実行時間**: 60分以内に全shard完了
- **並列実行**: 4 shard同時実行
- **リトライ**: Playwright設定で最大2回リトライ（フレーキーテスト対策）

### コスト管理
- **pathsフィルター**: 不要な実行を抑制（GitHub Actions無料枠対策）
- **Artifactsストレージ**: 30日間保持（自動削除）
- **手動実行優先**: 開発段階ではworkflow_dispatch推奨

---

## 前提条件

- Issue #14完了（Next.js Dockerfile作成とDocker Compose統合）
- Docker Compose環境で全サービスが正常起動すること
- E2Eテストがローカル環境で成功すること
- `.github/workflows/e2e-tests.yml.disabled` ファイルが存在すること

---

## 成功指標（DoD）

### 必須条件
- ✅ `.github/workflows/e2e-tests.yml` が有効化済み
- ✅ 手動実行（workflow_dispatch）でE2Eテストが成功
- ✅ 4並列実行（shard 1-4）がすべて成功
- ✅ Artifactsにレポート・スクリーンショット・トレースが保存
- ✅ PR作成時・mainブランチpush時の自動実行が動作

### 推奨条件
- ✅ 実行時間が60分以内に完了
- ✅ フレーキーテストが発生しない（または2回目のリトライで成功）
- ✅ README.mdにCI/CD実行手順が記載

---

## リスク管理

### リスク1: GitHub Actions無料枠超過
- **影響度**: 高
- **対策**: pathsフィルター、手動トリガー優先、並列数調整（4→2）

### リスク2: E2E実行時間の長期化
- **影響度**: 中
- **対策**: Shard並列実行、Docker Composeキャッシュ、Playwrightブラウザキャッシュ

### リスク3: フレーキーテスト（不安定なテスト）
- **影響度**: 中
- **対策**: retries設定、適切なwait設定、スクリーンショット・トレース保存

---

## 参考資料

### 公式ドキュメント
- [GitHub Actions Documentation](https://docs.github.com/actions)
- [Playwright Test Sharding](https://playwright.dev/docs/test-sharding)
- [GitHub Actions Artifacts](https://docs.github.com/actions/using-workflows/storing-workflow-data-as-artifacts)

### 関連Issue
- Issue #12: E2Eテスト環境基盤設定
- Issue #14: Next.js アプリ用 Dockerfile 作成（**前提条件**）
- PR #58: E2Eテスト環境基盤構築

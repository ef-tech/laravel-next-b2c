# Requirements Document

## GitHub Issue Information

**Issue**: [#59](https://github.com/ef-tech/laravel-next-b2c/issues/59) - E2E CI/CD実行確認（GitHub Actions ワークフロー有効化）
**Labels**: なし
**Milestone**: なし
**Assignees**: なし

### Original Issue Description

## 背景と目的

### 背景
- E2Eテスト環境構築完了（Issue #12, PR #58）
- GitHub Actions ワークフロー作成済み（`.github/workflows/e2e-tests.yml.disabled`）
- **現状**: GitHub Actions無料枠対策で意図的に無効化（`.disabled`拡張子）
- フロントエンドDocker化完了後（Issue #14）にCI/CD実行が可能に

### 目的
1. **CI/CDパイプライン有効化**: E2Eテストの自動実行開始
2. **並列実行検証**: GitHub Actions Matrixによる4並列実行の動作確認
3. **レポート生成検証**: HTML/JUnitレポート・スクリーンショット・トレース保存確認
4. **運用開始**: PR作成時・mainブランチpush時の自動E2E実行

## カテゴリ

**CI-CD** - E2Eテスト自動実行パイプライン有効化

### 詳細分類
- GitHub Actions ワークフロー有効化
- Docker環境でのPlaywright実行検証
- Shard（並列実行）動作確認
- テストレポートアーティファクト保存検証

## スコープ

### 対象範囲（前提: Issue #14完了後）
- ✅ `.github/workflows/e2e-tests.yml.disabled` → `.github/workflows/e2e-tests.yml` にリネーム
- ✅ ワークフロー実行検証（手動トリガー `workflow_dispatch`）
- ✅ Shard実行検証（4並列: shard 1/2/3/4）
- ✅ Docker Compose起動確認（laravel-api, admin-app, user-app）
- ✅ E2Eテスト実行成功確認
- ✅ レポート・スクリーンショット・トレース保存確認
- ✅ 自動トリガー検証（PR作成時、mainブランチpush時）
- ✅ ドキュメント更新（CI/CD実行手順・トラブルシューティング）

### 対象外（将来対応）
- ❌ Visual Regression Testing（Percy/Chromatic統合）
- ❌ パフォーマンステスト（Lighthouse CI統合）
- ❌ クロスブラウザテスト（Firefox, Webkit追加）

## 仕様と手順

### 1. ワークフロー有効化

**ファイル**: `.github/workflows/e2e-tests.yml.disabled` → `.github/workflows/e2e-tests.yml`

```bash
# リネーム
mv .github/workflows/e2e-tests.yml.disabled .github/workflows/e2e-tests.yml

# コミット
git add .github/workflows/e2e-tests.yml
git commit -m "Enable: 🚀 E2E CI/CDワークフロー有効化"
git push
```

### 2. ワークフロー構成（既存）

**ファイル**: `.github/workflows/e2e-tests.yml`
```yaml
name: E2E Tests

on:
  # 手動実行
  workflow_dispatch:

  # PR作成時
  pull_request:
    branches: [main, develop]
    paths:
      - 'frontend/**'
      - 'backend/laravel-api/app/**'
      - 'backend/laravel-api/routes/**'
      - 'e2e/**'
      - '.github/workflows/e2e-tests.yml'

  # mainブランチpush時
  push:
    branches: [main]
    paths:
      - 'frontend/**'
      - 'backend/laravel-api/app/**'
      - 'backend/laravel-api/routes/**'
      - 'e2e/**'

jobs:
  e2e-tests:
    runs-on: ubuntu-latest
    timeout-minutes: 60
    strategy:
      fail-fast: false
      matrix:
        shard: [1, 2, 3, 4]  # 4並列実行

    steps:
      - uses: actions/checkout@v4

      - name: Setup Node.js
        uses: actions/setup-node@v4
        with:
          node-version: '20'

      - name: Start services
        run: docker-compose up -d --build

      - name: Wait for services
        run: |
          npx wait-on http://localhost:3000
          npx wait-on http://localhost:3001
          npx wait-on http://localhost:13000/up

      - name: Install E2E dependencies
        working-directory: e2e
        run: npm ci

      - name: Install Playwright browsers
        working-directory: e2e
        run: npx playwright install --with-deps

      - name: Run E2E tests (Shard ${{ matrix.shard }}/4)
        working-directory: e2e
        run: npx playwright test --shard=${{ matrix.shard }}/4
        env:
          E2E_ADMIN_URL: http://localhost:3001
          E2E_USER_URL: http://localhost:3000
          E2E_API_URL: http://localhost:13000

      - name: Upload test results
        uses: actions/upload-artifact@v4
        if: always()
        with:
          name: playwright-report-${{ matrix.shard }}
          path: e2e/reports/
          retention-days: 30
```

### 3. 動作検証手順

#### Step 1: 手動実行検証（workflow_dispatch）

1. GitHub Actionsタブを開く
2. "E2E Tests"ワークフローを選択
3. "Run workflow"ボタンをクリック
4. 実行結果を確認

#### Step 2: Shard実行検証

- 4つのジョブ（shard 1/2/3/4）が並列実行されることを確認
- 各shardのログを確認
- 全shardが成功することを確認

#### Step 3: レポート確認

- Artifacts（成果物）に以下が保存されていることを確認
  - `playwright-report-1.zip`
  - `playwright-report-2.zip`
  - `playwright-report-3.zip`
  - `playwright-report-4.zip`
- 各zipにHTML/JUnitレポート、スクリーンショット、トレースが含まれることを確認

#### Step 4: 自動トリガー検証

- PRを作成し、E2Eテストが自動実行されることを確認
- mainブランチにpushし、E2Eテストが自動実行されることを確認

### 4. トラブルシューティング

**問題**: Docker起動失敗

```bash
# ログ確認
docker-compose logs laravel-api
docker-compose logs admin-app
docker-compose logs user-app

# サービス再起動
docker-compose restart
```

**問題**: wait-onタイムアウト

```yaml
# タイムアウト延長
- name: Wait for services
  run: |
    npx wait-on http://localhost:3000 --timeout 120000
    npx wait-on http://localhost:3001 --timeout 120000
    npx wait-on http://localhost:13000/up --timeout 120000
```

**問題**: Playwright実行失敗

```bash
# ブラウザ再インストール
npx playwright install --with-deps chromium
```

## 影響とリスク

### 影響範囲
| 対象 | 影響度 | 内容 |
|------|--------|------|
| **CI/CD** | 高 | GitHub Actions実行時間・コスト増加 |
| **開発者** | 中 | PR作成時の自動E2E実行待ち時間 |
| **品質保証** | 高 | 自動リグレッションテスト実施 |

### リスク管理

#### リスク1: GitHub Actions無料枠超過
- **対策**:
  - pathsフィルターで不要な実行を抑制
  - 手動トリガー（workflow_dispatch）を優先利用
  - 並列数を調整（4 → 2に削減可能）

#### リスク2: E2E実行時間の長期化
- **対策**:
  - Shard並列実行（4並列）
  - Docker Composeキャッシュ活用
  - Playwrightブラウザキャッシュ

#### リスク3: フレーキーテスト（不安定なテスト）
- **対策**:
  - retries設定（最大2回リトライ）
  - 適切なwait設定
  - スクリーンショット・トレース保存で原因調査

## チェックリスト

### Phase 1: ワークフロー有効化（前提: Issue #14完了）
- [ ] Issue #14完了確認（Docker環境構築完了）
- [ ] `.disabled` 削除（ワークフロー有効化）
- [ ] コミット・プッシュ

### Phase 2: 手動実行検証
- [ ] GitHub Actionsタブで手動実行
- [ ] 4 shardすべて成功確認
- [ ] Artifactsダウンロード・内容確認

### Phase 3: レポート検証
- [ ] HTMLレポート表示確認
- [ ] JUnitレポート形式確認
- [ ] スクリーンショット保存確認
- [ ] トレースファイル保存確認

### Phase 4: 自動トリガー検証
- [ ] PR作成時の自動実行確認
- [ ] mainブランチpush時の自動実行確認
- [ ] pathsフィルター動作確認

### Phase 5: ドキュメント更新
- [ ] README.mdにCI/CD実行手順追加
- [ ] e2e/README.mdにCI/CD情報追記
- [ ] トラブルシューティング追記

## 完了条件（DoD）

### 必須条件（前提: Issue #14完了）
- ✅ `.github/workflows/e2e-tests.yml` が有効化済み
- ✅ 手動実行（workflow_dispatch）でE2Eテストが成功
- ✅ 4並列実行（shard 1-4）がすべて成功
- ✅ Artifactsにレポート・スクリーンショット・トレースが保存
- ✅ PR作成時・mainブランチpush時の自動実行が動作

### 推奨条件
- ✅ 実行時間が60分以内に完了
- ✅ フレーキーテストが発生しない（または2回目のリトライで成功）
- ✅ README.mdにCI/CD実行手順が記載

## 参考資料

### 公式ドキュメント
- [GitHub Actions Documentation](https://docs.github.com/actions)
- [Playwright Test Sharding](https://playwright.dev/docs/test-sharding)
- [GitHub Actions Artifacts](https://docs.github.com/actions/using-workflows/storing-workflow-data-as-artifacts)

### 関連Issue
- Issue #12: E2Eテスト環境基盤設定
- Issue #14: Next.js アプリ用 Dockerfile 作成（**前提条件**）
- PR #58: E2Eテスト環境基盤構築

### 備考
**重要**: このIssueは **Issue #14完了後** に着手してください。Docker環境が構築されていない状態ではGitHub ActionsのE2E実行が失敗します。

## Extracted Information

### Technology Stack
**Backend**: Laravel API
**Frontend**: Next.js (admin-app, user-app)
**Infrastructure**: Docker Compose, GitHub Actions
**Tools**: Playwright, wait-on

### Project Structure
```
.github/workflows/e2e-tests.yml.disabled
frontend/
backend/laravel-api/app/
backend/laravel-api/routes/
e2e/
```

### Development Services Configuration
- admin-app: ポート 3001
- user-app: ポート 3000
- laravel-api: ポート 13000

### Requirements Hints
Based on issue analysis:
- CI/CDパイプライン有効化: E2Eテストの自動実行開始
- 並列実行検証: GitHub Actions Matrixによる4並列実行の動作確認
- レポート生成検証: HTML/JUnitレポート・スクリーンショット・トレース保存確認
- 運用開始: PR作成時・mainブランチpush時の自動E2E実行

### TODO Items from Issue
- [ ] Issue #14完了確認（Docker環境構築完了）
- [ ] .disabled 削除（ワークフロー有効化）
- [ ] コミット・プッシュ
- [ ] GitHub Actionsタブで手動実行
- [ ] 4 shardすべて成功確認
- [ ] Artifactsダウンロード・内容確認
- [ ] HTMLレポート表示確認
- [ ] JUnitレポート形式確認
- [ ] スクリーンショット保存確認
- [ ] トレースファイル保存確認
- [ ] PR作成時の自動実行確認
- [ ] mainブランチpush時の自動実行確認
- [ ] pathsフィルター動作確認
- [ ] README.mdにCI/CD実行手順追加
- [ ] e2e/README.mdにCI/CD情報追記
- [ ] トラブルシューティング追記

## Requirements
<!-- Will be generated in /kiro:spec-requirements phase -->

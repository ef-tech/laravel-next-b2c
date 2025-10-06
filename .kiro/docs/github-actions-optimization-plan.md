# GitHub Actions ワークフロー最適化計画

## 概要

本プロジェクトの4つのGitHub Actionsワークフローを最適化し、CI/CD実行時間の削減とリソース効率化を図る。

## 現在のワークフロー状況

### 既存ワークフロー

1. **frontend-test.yml** - Jest + Testing Library
   - 状態: paths設定済み、concurrency未設定
   - 実行時間: ~3-5分
   - キャッシュ: npm dependencies

2. **php-quality.yml** - Laravel Pint + Larastan
   - 状態: paths未設定、concurrency未設定
   - 実行時間: ~2-3分
   - キャッシュ: Composer + PHPStan

3. **test.yml** - Pest テスト
   - 状態: paths未設定、concurrency未設定、shard対応済み
   - 実行時間: ~8-12分（shard並列実行）
   - キャッシュ: Composer

4. **e2e-tests.yml** - Playwright E2E
   - 状態: paths設定済み、concurrency設定済み、shard対応済み
   - 実行時間: ~15-20分（shard並列実行）
   - キャッシュ: npm + Composer

## 最適化目標

### パフォーマンス目標
- **frontend-test.yml**: 3-5分 → 2-3分（40%削減）
- **php-quality.yml**: 2-3分 → 1-2分（33%削減）
- **test.yml**: 8-12分 → 6-8分（25%削減）
- **e2e-tests.yml**: 現状維持（既に最適化済み）

### 全体目標
- 不要なワークフロー実行の削減（paths設定による）
- 同時実行制限によるリソース競合回避
- キャッシング戦略統一化
- トラブルシューティング効率化

## 実装内容

### 1. Concurrency設定追加

**対象**: frontend-test.yml, php-quality.yml, test.yml

```yaml
concurrency:
  group: ${{ github.workflow }}-${{ github.ref }}
  cancel-in-progress: true
```

**効果**:
- 同一ブランチでの重複実行防止
- リソース競合回避
- 実行キューの最適化

### 2. Paths設定追加

**php-quality.yml**:
```yaml
paths:
  - 'backend/laravel-api/**'
  - '.github/workflows/php-quality.yml'
```

**test.yml**:
```yaml
paths:
  - 'backend/laravel-api/**'
  - '.github/workflows/test.yml'
```

**効果**:
- 関連ファイル変更時のみ実行
- 不要なワークフロー実行削減（推定60-70%削減）

### 3. Pull Request Types明示

**全ワークフロー**:
```yaml
pull_request:
  types: [opened, synchronize, reopened]
  branches: [main]
```

**効果**:
- 不要なPRイベントでの実行防止
- 明確な実行条件定義

### 4. 依存関係キャッシング統一化

**統一キャッシュ戦略**:
- **Composer**: `~/.composer/cache/files`
- **npm**: `node_modules` + npm cache directory
- **PHPStan**: `backend/laravel-api/storage/framework/cache/phpstan`

### 5. PHP Quality + Test統合検討

**統合パターン**:
```yaml
name: PHP Tests & Quality
jobs:
  quality:
    # Laravel Pint + Larastan
  test:
    needs: quality
    # Pest テスト（quality成功後のみ実行）
```

**メリット**:
- ワークフロー管理簡素化
- Quality Check失敗時のテスト実行スキップ
- リソース効率化

**デメリット**:
- 部分的な再実行が困難
- 失敗箇所の特定が複雑化

## 実装優先順序

### Phase 1: 低リスク最適化（1-2日）
1. **frontend-test.yml** - concurrency設定追加
2. **php-quality.yml** - concurrency + paths設定追加
3. **test.yml** - concurrency + paths設定追加

### Phase 2: Pull Request Types統一（1日）
4. 全ワークフローのpull_request.types明示

### Phase 3: キャッシング統一化（2-3日）
5. php-quality.ymlのキャッシュ戦略見直し
6. test.ymlのキャッシュ戦略見直し

### Phase 4: 統合検討（3-5日）
7. php-quality.yml + test.yml統合の検証
8. 統合ワークフローの実装・テスト

## ブランチプロテクション設定への影響

### 現在の想定設定
```yaml
required_status_checks:
  - Frontend Tests
  - PHP Quality Checks
  - Tests
  - E2E Tests (optional)
```

### paths設定変更による影響

**シナリオ**: フロントエンドのみ変更のPR
- ✅ Frontend Tests: 実行される
- ❌ PHP Quality Checks: スキップされる
- ❌ Tests: スキップされる
- ✅ E2E Tests: 実行される

**対策**:
1. **Always Run Option**: 重要チェックを常に実行する設定
2. **Path-based Branch Protection**: GitHub Apps使用
3. **Conditional Required Checks**: 変更内容に応じた必須チェック

### 推奨対策

```yaml
# .github/workflows/required-checks.yml
name: Required Checks
on:
  pull_request:
    branches: [main]
jobs:
  paths-filter:
    outputs:
      backend: ${{ steps.changes.outputs.backend }}
      frontend: ${{ steps.changes.outputs.frontend }}
    steps:
      - uses: dorny/paths-filter@v2
        id: changes
        with:
          filters: |
            backend:
              - 'backend/**'
            frontend:
              - 'frontend/**'
```

## 本番影響最小化戦略

### 段階的ロールアウト

#### Stage 1: Feature Branch検証
```bash
# feature/workflow-optimization ブランチで検証
git checkout -b feature/workflow-optimization
# 最適化実装
# 複数PRでの動作確認
```

#### Stage 2: Develop Branch検証
```bash
# developブランチでの統合検証
git checkout develop
git merge feature/workflow-optimization
# 1週間程度の動作監視
```

#### Stage 3: Main Branch展開
```bash
# 本番環境への適用
git checkout main
git merge develop
```

### リスク軽減措置

1. **Workflow Backup**
   ```bash
   # 現行ワークフローのバックアップ
   cp -r .github/workflows .github/workflows.backup
   ```

2. **Rollback Plan**
   ```bash
   # 問題発生時の即座ロールバック
   git revert <commit-hash>
   ```

3. **Monitoring Setup**
   ```bash
   # ワークフロー実行時間監視
   gh workflow list
   gh run list --workflow="Frontend Tests"
   ```
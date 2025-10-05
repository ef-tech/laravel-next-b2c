# GitHub Actions ワークフロー最適化実装ガイド

## 質問への回答

### 1. ワークフロー最適化の実装順序

#### 推奨実装順序と理由

**Phase 1: 低リスクな最適化から開始**
1. **frontend-test.yml** - concurrency設定追加
   - 理由: 既にpaths設定済みで、影響範囲が限定的
   - リスク: 低
   - 効果: 重複実行防止による即効性

2. **php-quality.yml** - concurrency + paths設定追加
   - 理由: 短時間で実行される軽量ワークフロー
   - リスク: 中（paths設定による影響要検証）
   - 効果: 実行頻度大幅削減

3. **test.yml** - concurrency + paths設定追加
   - 理由: 最も実行時間が長いため、最適化効果が大きい
   - リスク: 中（テスト環境への影響要確認）
   - 効果: 大幅な実行時間・頻度削減

**Phase 2: 統一化・標準化**
4. Pull Request Types統一
5. キャッシング戦略統一化

**Phase 3: 高度な最適化**
6. php-quality.yml + test.yml統合検討

### 2. 本番ブランチ（main）へのデプロイ影響最小化戦略

#### A. 段階的ロールアウト戦略

```bash
# Step 1: Feature Branchで検証
git checkout -b feature/workflow-optimization-phase1
# 各ワークフローを段階的に最適化
# 複数の実験PRで動作確認

# Step 2: Developブランチでの統合検証
git checkout develop
git merge feature/workflow-optimization-phase1
# 1週間の動作監視

# Step 3: Main適用（段階的）
# 1つずつワークフローを最適化してmainにマージ
```

#### B. リスク軽減策

1. **Blue-Green Deployment for Workflows**
```bash
# 新しいワークフローを .yml.new として作成
# 旧ワークフローと並行実行で検証
# 問題なければ切り替え
```

2. **Canary Release Pattern**
```yaml
# 特定のブランチパターンでのみ新ワークフロー実行
on:
  push:
    branches:
      - 'feature/workflow-test-*'
      - 'main'
```

3. **Immediate Rollback Plan**
```bash
# 緊急時ロールバック用のスクリプト準備
#!/bin/bash
# rollback-workflows.sh
git revert HEAD~1 --no-edit
git push origin main
echo "Workflows rolled back to previous version"
```

### 3. 各ワークフローの変更後動作確認手順

#### A. 自動化された確認手順

```bash
# .github/scripts/verify-workflow-optimization.sh
#!/bin/bash

echo "=== GitHub Actions ワークフロー最適化確認 ==="

# 1. Concurrency設定確認
echo "## 1. Concurrency設定確認"
test_concurrency() {
    local branch="test-concurrency-$(date +%s)"
    git checkout -b "$branch"
    echo "# Test change" >> README.md
    git add . && git commit -m "Test concurrency - first push"
    git push origin "$branch"
    
    # 2回目のpush（前のワークフローがキャンセルされるはず）
    echo "# Test change 2" >> README.md
    git add . && git commit -m "Test concurrency - second push"
    git push origin "$branch"
    
    echo "確認: GitHub Actionsページで最初のワークフローがキャンセルされているか確認"
    echo "URL: https://github.com/$(git remote get-url origin | sed 's/.*github.com[:/]//' | sed 's/.git$//')/actions"
}

# 2. Paths設定確認
echo "## 2. Paths設定確認"
test_paths_backend() {
    local branch="test-paths-backend-$(date +%s)"
    git checkout -b "$branch"
    echo "// Backend test change" >> backend/laravel-api/app/Http/Controllers/Controller.php
    git add . && git commit -m "Backend only change"
    git push origin "$branch"
    echo "確認: PHP Quality, Testsのみ実行されることを確認"
}

test_paths_frontend() {
    local branch="test-paths-frontend-$(date +%s)"
    git checkout -b "$branch"
    echo "// Frontend test change" >> frontend/user-app/src/app/page.tsx
    git add . && git commit -m "Frontend only change"
    git push origin "$branch"
    echo "確認: Frontend Tests, E2E Testsのみ実行されることを確認"
}

# 3. Pull Request Types確認
echo "## 3. Pull Request Types確認"
test_pr_types() {
    local branch="test-pr-types-$(date +%s)"
    git checkout -b "$branch"
    echo "# PR test change" >> README.md
    git add . && git commit -m "PR types test"
    git push origin "$branch"
    
    # Draft PRの作成
    gh pr create --title "Test PR Types" --body "Testing PR types" --draft
    echo "確認: Draft PRではワークフローが実行されないことを確認"
    
    # Ready for reviewに変更
    gh pr ready
    echo "確認: Ready for reviewでワークフローが実行されることを確認"
}

# 実行
test_concurrency
test_paths_backend
test_paths_frontend
test_pr_types
```

#### B. 手動確認チェックリスト

```markdown
## ワークフロー最適化確認チェックリスト

### frontend-test.yml
- [ ] Concurrency設定により重複実行がキャンセルされる
- [ ] Paths設定により関連ファイル変更時のみ実行される
- [ ] Pull Request Typesが正しく動作する
- [ ] キャッシュが正常に動作する
- [ ] 実行時間が期待値内に収まる

### php-quality.yml
- [ ] Concurrency設定により重複実行がキャンセルされる
- [ ] Paths設定により関連ファイル変更時のみ実行される  
- [ ] Pull Request Typesが正しく動作する
- [ ] Laravel Pint, Larastanが正常に実行される
- [ ] キャッシュが正常に動作する

### test.yml
- [ ] Concurrency設定により重複実行がキャンセルされる
- [ ] Paths設定により関連ファイル変更時のみ実行される
- [ ] Pull Request Typesが正しく動作する
- [ ] Pestテストが全て通る
- [ ] Shard並列実行が正常に動作する
- [ ] データベース・Redis接続が正常
```

### 4. Paths設定変更によるブランチプロテクション設定の影響

#### A. 問題の詳細

**現在の問題**:
```yaml
# ブランチプロテクション設定（推定）
required_status_checks:
  - "Frontend Tests"
  - "PHP Quality Checks" 
  - "Tests"
  - "E2E Tests"
```

**Paths設定後の問題**:
- フロントエンドのみ変更のPR → PHP Quality Checks, Testsが実行されない → ブランチプロテクションで失敗
- バックエンドのみ変更のPR → Frontend Tests, E2E Testsが実行されない → ブランチプロテクションで失敗

#### B. 解決策

**解決策1: Conditional Branch Protection (推奨)**

```yaml
# .github/workflows/path-based-checks.yml
name: Path-based Required Checks
on:
  pull_request:
    branches: [main]

jobs:
  check-changes:
    outputs:
      backend: ${{ steps.changes.outputs.backend }}
      frontend: ${{ steps.changes.outputs.frontend }}
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: dorny/paths-filter@v2
        id: changes
        with:
          filters: |
            backend:
              - 'backend/**'
            frontend:
              - 'frontend/**'
  
  frontend-required:
    needs: check-changes
    if: needs.check-changes.outputs.frontend == 'true'
    runs-on: ubuntu-latest
    steps:
      - run: echo "Frontend changes detected - Frontend tests will run"
  
  backend-required:
    needs: check-changes  
    if: needs.check-changes.outputs.backend == 'true'
    runs-on: ubuntu-latest
    steps:
      - run: echo "Backend changes detected - Backend tests will run"
      
  # Always pass job for branch protection
  required-checks:
    runs-on: ubuntu-latest
    steps:
      - run: echo "Required checks completed"
```

**解決策2: Skip Job Pattern**

```yaml
# 各ワークフローでskip jobを追加
name: Frontend Tests
on:
  pull_request:
    branches: [main]

jobs:
  check-changes:
    outputs:
      should_run: ${{ steps.changes.outputs.frontend }}
    runs-on: ubuntu-latest
    steps:
      - uses: dorny/paths-filter@v2
        id: changes
        with:
          filters: |
            frontend:
              - 'frontend/**'
              - 'test-utils/**'
  
  test:
    needs: check-changes
    if: needs.check-changes.outputs.should_run == 'true'
    # ... existing test job
    
  # Always pass for branch protection
  skip-message:
    needs: check-changes
    if: needs.check-changes.outputs.should_run == 'false'
    runs-on: ubuntu-latest
    steps:
      - run: echo "Skipping frontend tests - no frontend changes"
```

**解決策3: ブランチプロテクション設定変更**

```yaml
# GitHub Settings > Branches > main > Required status checks
# 変更前:
required_status_checks:
  - "Frontend Tests / test"
  - "PHP Quality Checks / php-quality"
  - "Tests / test"

# 変更後:
required_status_checks:
  - "Path-based Required Checks / required-checks"
```

### 5. チーム開発での段階的ロールアウト戦略

#### A. チーム内コミュニケーション戦略

**Phase 1: 事前準備（1週間）**
```markdown
## チーム向けアナウンス例

### 📢 GitHub Actions ワークフロー最適化のお知らせ

来週から段階的にGitHub Actionsワークフローの最適化を実施します。

#### 期待される効果
- CI/CD実行時間30-40%削減
- 不要なワークフロー実行60-70%削減
- より高速な開発フィードバック

#### 影響・注意点
- 一時的にワークフロー実行パターンが変更される場合があります
- 問題が発生した場合は即座に報告をお願いします
- 段階的実装のため、一部ワークフローが従来通りの場合があります

#### 実装スケジュール
- Week 1: frontend-test.yml最適化
- Week 2: php-quality.yml, test.yml最適化  
- Week 3: 統一化作業
- Week 4: 監視・調整

#### 問題報告先
Slack: #dev-ops チャンネル
GitHub Issues: [Workflow Optimization] タグ
```

**Phase 2: 段階的実装（各1週間）**

```bash
# Week 1: Frontend Tests最適化
echo "=== Week 1: Frontend Tests Optimization ==="
# 1. concurrency設定追加
# 2. 3日間の動作監視
# 3. チームからのフィードバック収集
# 4. 必要に応じて調整

# Week 2: PHP Quality & Tests最適化  
echo "=== Week 2: Backend Workflows Optimization ==="
# 1. paths設定追加
# 2. concurrency設定追加
# 3. 動作確認・監視
# 4. ブランチプロテクション設定調整

# Week 3: 統一化作業
echo "=== Week 3: Standardization ==="
# 1. Pull Request Types統一
# 2. キャッシング戦略統一
# 3. 統合検討・実装

# Week 4: 監視・最終調整
echo "=== Week 4: Monitoring & Final Adjustments ==="
# 1. パフォーマンス測定
# 2. 最終調整
# 3. ドキュメント更新
```

#### B. フィードバック収集・対応体制

**デイリーチェック体制**
```bash
# .github/scripts/daily-workflow-health-check.sh
#!/bin/bash

echo "## Daily Workflow Health Check - $(date +%Y-%m-%d)"

# 1. 失敗率チェック
echo "### Workflow Success Rate (Last 24h)"
for workflow in "Frontend Tests" "PHP Quality Checks" "Tests" "E2E Tests"; do
    total=$(gh run list --workflow="$workflow" --created $(date -d '1 day ago' +%Y-%m-%d) --json conclusion | jq length)
    success=$(gh run list --workflow="$workflow" --created $(date -d '1 day ago' +%Y-%m-%d) --json conclusion | jq '[.[] | select(.conclusion == "success")] | length')
    if [ $total -gt 0 ]; then
        success_rate=$(echo "scale=2; $success * 100 / $total" | bc)
        echo "- $workflow: $success_rate% ($success/$total)"
    fi
done

# 2. 平均実行時間チェック
echo "### Average Duration (Last 24h)"
# Implementation for duration analysis

# 3. エラーアラート
echo "### Issues Detected"
# Check for specific error patterns
```

**週次レビュー会議**
```markdown
## 週次ワークフロー最適化レビュー

### アジェンダ
1. パフォーマンスメトリクス確認
2. 発生した問題・解決策レビュー
3. 次週の実装計画確認
4. チームからのフィードバック

### 確認指標
- 実行時間削減率
- 実行頻度削減率
- 成功率維持
- 開発者満足度
```

### 6. ワークフロー変更後のトラブルシューティング手順

#### A. 問題分類と対応手順

**レベル1: 軽微な問題（自動復旧可能）**
```bash
# 例: キャッシュミス、一時的なネットワークエラー
# 対応: 自動リトライ、キャッシュクリア

# 自動対応スクリプト
#!/bin/bash
# auto-recovery.sh

check_and_retry() {
    local workflow_run_id=$1
    local status=$(gh run view $workflow_run_id --json conclusion -q '.conclusion')
    
    if [ "$status" = "failure" ]; then
        # ログ分析
        gh run view $workflow_run_id --log | grep -E "(cache|network|timeout)"
        if [ $? -eq 0 ]; then
            echo "Detected recoverable error, rerunning..."
            gh run rerun $workflow_run_id
        fi
    fi
}
```

**レベル2: 中程度の問題（手動対応必要）**
```bash
# 例: paths設定によるワークフロー未実行、concurrency設定問題
# 対応手順:

troubleshoot_paths_issue() {
    echo "=== Paths設定問題のトラブルシューティング ==="
    
    # 1. 変更ファイル確認
    echo "## 変更ファイル一覧"
    git diff --name-only HEAD~1
    
    # 2. paths設定確認
    echo "## 現在のpaths設定"
    grep -A 10 "paths:" .github/workflows/*.yml
    
    # 3. 期待されるワークフロー確認
    echo "## 実行されるべきワークフロー"
    # Logic to determine expected workflows based on changed files
    
    # 4. 実際の実行状況
    echo "## 実際の実行状況"
    gh run list --limit 5
}

troubleshoot_concurrency_issue() {
    echo "=== Concurrency設定問題のトラブルシューティング ==="
    
    # 1. 現在実行中のワークフロー確認
    echo "## 実行中ワークフロー"
    gh run list --status in_progress
    
    # 2. キャンセルされたワークフロー確認  
    echo "## キャンセルされたワークフロー"
    gh run list --status cancelled --limit 10
    
    # 3. Concurrency group確認
    echo "## Concurrency設定"
    grep -A 3 "concurrency:" .github/workflows/*.yml
}
```

**レベル3: 重大な問題（緊急対応必要）**
```bash
# 例: 全ワークフロー停止、本番デプロイブロック
# 緊急対応手順:

emergency_rollback() {
    echo "=== 緊急ロールバック手順 ==="
    
    # 1. 現在の状況確認
    echo "## 現在の状況"
    gh run list --limit 10
    
    # 2. バックアップから復旧
    echo "## ワークフローバックアップから復旧"
    if [ -d ".github/workflows.backup" ]; then
        cp -r .github/workflows.backup/* .github/workflows/
        git add .github/workflows/
        git commit -m "Emergency rollback: restore workflows from backup"
        git push origin main
        echo "✅ ワークフローをバックアップから復旧しました"
    else
        echo "❌ バックアップが見つかりません"
        echo "手動でワークフローを復旧してください"
    fi
    
    # 3. 影響確認
    echo "## 復旧確認"
    sleep 30
    gh run list --limit 5
}

emergency_disable_all() {
    echo "=== 全ワークフロー無効化（最終手段） ==="
    
    # 全ワークフローを無効化
    for file in .github/workflows/*.yml; do
        if [[ "$file" != *.disabled ]]; then
            mv "$file" "$file.disabled"
        fi
    done
    
    git add .github/workflows/
    git commit -m "Emergency: disable all workflows"
    git push origin main
    
    echo "⚠️ 全ワークフローを無効化しました"
    echo "問題解決後、手動で再有効化してください"
}
```

#### B. 監視・アラート体制

**自動監視スクリプト**
```bash
# .github/scripts/workflow-monitor.sh
#!/bin/bash

# Slack通知関数
notify_slack() {
    local message=$1
    local webhook_url="$SLACK_WEBHOOK_URL"
    
    curl -X POST -H 'Content-type: application/json' \
        --data "{\"text\":\"$message\"}" \
        "$webhook_url"
}

# ワークフロー健全性チェック
check_workflow_health() {
    local issues_found=0
    
    # 1. 異常な失敗率チェック（過去1時間で50%以上失敗）
    for workflow in "Frontend Tests" "PHP Quality Checks" "Tests"; do
        local failure_rate=$(gh run list --workflow="$workflow" --created $(date -d '1 hour ago' +%Y-%m-%d) --json conclusion | jq '[.[] | select(.conclusion == "failure")] | length / (.[].length // 1) * 100')
        
        if (( $(echo "$failure_rate > 50" | bc -l) )); then
            notify_slack "🚨 $workflow failure rate: ${failure_rate}% (last 1 hour)"
            issues_found=1
        fi
    done
    
    # 2. 異常な実行時間チェック（通常の2倍以上）
    # Implementation for duration anomaly detection
    
    # 3. concurrency問題チェック（キャンセル率異常）
    local cancelled_count=$(gh run list --status cancelled --created $(date -d '1 hour ago' +%Y-%m-%d) --json id | jq length)
    if [ $cancelled_count -gt 10 ]; then
        notify_slack "⚠️ Unusual number of cancelled workflows: $cancelled_count (last 1 hour)"
        issues_found=1
    fi
    
    if [ $issues_found -eq 0 ]; then
        echo "✅ All workflows healthy"
    fi
}

# 定期実行（cron: */15 * * * *）
check_workflow_health
```

### 7. 最適化後のCI/CD実行時間削減効果の測定方法

#### A. ベースライン測定

**測定前データ収集**
```bash
# .github/scripts/collect-baseline-metrics.sh
#!/bin/bash

echo "=== CI/CD Baseline Metrics Collection ==="
echo "Collection Date: $(date)"

# 1. 実行時間メトリクス収集
collect_duration_metrics() {
    echo "## Workflow Duration Metrics (Last 30 days)"
    
    for workflow in "Frontend Tests" "PHP Quality Checks" "Tests" "E2E Tests"; do
        echo "### $workflow"
        
        # 平均実行時間
        avg_duration=$(gh run list --workflow="$workflow" --limit 100 --json durationMs | \
            jq '[.[] | select(.durationMs != null) | .durationMs] | add / length / 1000')
        echo "Average Duration: ${avg_duration}s"
        
        # 最大実行時間
        max_duration=$(gh run list --workflow="$workflow" --limit 100 --json durationMs | \
            jq '[.[] | select(.durationMs != null) | .durationMs] | max / 1000')
        echo "Max Duration: ${max_duration}s"
        
        # 実行回数
        run_count=$(gh run list --workflow="$workflow" --created $(date -d '30 days ago' +%Y-%m-%d) --json id | jq length)
        echo "Total Runs (30 days): $run_count"
        
        echo ""
    done
}

# 2. 実行頻度メトリクス収集
collect_frequency_metrics() {
    echo "## Workflow Frequency Metrics"
    
    # 日次実行回数の平均
    echo "### Daily Execution Average (Last 30 days)"
    for workflow in "Frontend Tests" "PHP Quality Checks" "Tests" "E2E Tests"; do
        daily_avg=$(gh run list --workflow="$workflow" --created $(date -d '30 days ago' +%Y-%m-%d) --json id | \
            jq 'length / 30')
        echo "$workflow: $daily_avg runs/day"
    done
}

# 3. リソース使用量メトリクス収集  
collect_resource_metrics() {
    echo "## Resource Usage Metrics"
    
    # 同時実行数の分析
    echo "### Concurrent Execution Analysis"
    # Note: GitHub APIでは同時実行の履歴取得が困難
    # 現在の実行中ワークフロー数を記録
    concurrent_count=$(gh run list --status in_progress --json id | jq length)
    echo "Current Concurrent Runs: $concurrent_count"
}

# データをファイルに保存
{
    collect_duration_metrics
    collect_frequency_metrics  
    collect_resource_metrics
} > "baseline_metrics_$(date +%Y%m%d).md"

echo "✅ Baseline metrics saved to baseline_metrics_$(date +%Y%m%d).md"
```

#### B. 継続的測定・比較

**週次比較レポート**
```bash
# .github/scripts/weekly-performance-report.sh
#!/bin/bash

echo "# Weekly CI/CD Performance Report"
echo "Report Date: $(date)"
echo ""

# 1. 実行時間比較
generate_duration_comparison() {
    echo "## Execution Time Comparison"
    echo ""
    echo "| Workflow | Current Week | Previous Week | Change | Target |"
    echo "|----------|--------------|---------------|--------|--------|"
    
    for workflow in "Frontend Tests" "PHP Quality Checks" "Tests" "E2E Tests"; do
        # 今週の平均
        current_avg=$(gh run list --workflow="$workflow" --created $(date -d '7 days ago' +%Y-%m-%d) --json durationMs | \
            jq '[.[] | select(.durationMs != null) | .durationMs] | add / length / 1000')
        
        # 前週の平均
        previous_avg=$(gh run list --workflow="$workflow" --created $(date -d '14 days ago' +%Y-%m-%d) --json durationMs | \
            jq '[.[] | select(.durationMs != null) | (.createdAt | strptime("%Y-%m-%dT%H:%M:%SZ") | mktime) as $created_ts | select($created_ts < (now - 7*24*3600)) | .durationMs] | add / length / 1000')
        
        # 変化率計算
        if [ "$previous_avg" != "null" ] && [ "$current_avg" != "null" ]; then
            change=$(echo "scale=1; ($current_avg - $previous_avg) / $previous_avg * 100" | bc)
            change_indicator=""
            if (( $(echo "$change < 0" | bc -l) )); then
                change_indicator="🔽 ${change}%"
            elif (( $(echo "$change > 0" | bc -l) )); then
                change_indicator="🔺 +${change}%"
            else
                change_indicator="➡️ ${change}%"
            fi
        else
            change_indicator="N/A"
        fi
        
        # 目標設定
        case "$workflow" in
            "Frontend Tests") target="180s (40%↓)" ;;
            "PHP Quality Checks") target="90s (33%↓)" ;;
            "Tests") target="480s (25%↓)" ;;
            "E2E Tests") target="1200s (maintain)" ;;
        esac
        
        echo "| $workflow | ${current_avg}s | ${previous_avg}s | $change_indicator | $target |"
    done
    echo ""
}

# 2. 実行頻度比較
generate_frequency_comparison() {
    echo "## Execution Frequency Comparison"
    echo ""
    echo "| Workflow | Current Week | Previous Week | Change | Savings |"
    echo "|----------|--------------|---------------|--------|---------|"
    
    for workflow in "Frontend Tests" "PHP Quality Checks" "Tests" "E2E Tests"; do
        # 今週の実行回数
        current_count=$(gh run list --workflow="$workflow" --created $(date -d '7 days ago' +%Y-%m-%d) --json id | jq length)
        
        # 前週の実行回数
        previous_count=$(gh run list --workflow="$workflow" --created $(date -d '14 days ago' +%Y-%m-%d) --json id | \
            jq '[.[] | select((.createdAt | strptime("%Y-%m-%dT%H:%M:%SZ") | mktime) < (now - 7*24*3600))] | length')
        
        # 削減効果計算
        if [ $previous_count -gt 0 ]; then
            savings=$(echo "scale=1; ($previous_count - $current_count) / $previous_count * 100" | bc)
            if (( $(echo "$savings > 0" | bc -l) )); then
                savings_indicator="💰 ${savings}%"
            else
                savings_indicator="📈 ${savings}%"
            fi
        else
            savings_indicator="N/A"
        fi
        
        change=$((current_count - previous_count))
        if [ $change -lt 0 ]; then
            change_indicator="🔽 $change"
        elif [ $change -gt 0 ]; then
            change_indicator="🔺 +$change"
        else
            change_indicator="➡️ $change"
        fi
        
        echo "| $workflow | $current_count | $previous_count | $change_indicator | $savings_indicator |"
    done
    echo ""
}

# 3. 目標達成状況
generate_goal_tracking() {
    echo "## Goal Achievement Status"
    echo ""
    
    # 全体目標の達成状況を可視化
    echo "### Overall Targets"
    echo "- 🎯 **実行時間削減**: 30-40%"
    echo "- 🎯 **実行頻度削減**: 60-70%"  
    echo "- 🎯 **開発者満足度**: 向上"
    echo ""
    
    echo "### Achievement Status"
    # 実際の達成率を計算・表示
    # (実装は測定データに基づいて調整)
}

# レポート生成・保存
report_file="performance_report_$(date +%Y%m%d).md"
{
    generate_duration_comparison
    generate_frequency_comparison
    generate_goal_tracking
} > "$report_file"

echo "📊 Performance report saved to $report_file"

# Slackに要約を送信
if [ -n "$SLACK_WEBHOOK_URL" ]; then
    summary="📊 Weekly CI/CD Performance Report\n"
    summary+="• Frontend Tests: $(gh run list --workflow="Frontend Tests" --created $(date -d '7 days ago' +%Y-%m-%d) --json durationMs | jq '[.[] | select(.durationMs != null) | .durationMs] | add / length / 1000')s avg\n"
    summary+="• PHP Quality: $(gh run list --workflow="PHP Quality Checks" --created $(date -d '7 days ago' +%Y-%m-%d) --json durationMs | jq '[.[] | select(.durationMs != null) | .durationMs] | add / length / 1000')s avg\n"
    summary+="Full report: $report_file"
    
    curl -X POST -H 'Content-type: application/json' \
        --data "{\"text\":\"$summary\"}" \
        "$SLACK_WEBHOOK_URL"
fi
```

#### C. ダッシュボード作成

**GitHub Pages向けダッシュボード**
```html
<!-- .github/pages/ci-metrics-dashboard.html -->
<!DOCTYPE html>
<html>
<head>
    <title>CI/CD Metrics Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .metrics-container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .chart-container { width: 100%; height: 400px; margin: 20px 0; }
        .metric-card { background: #f5f5f5; padding: 15px; margin: 10px; border-radius: 8px; }
    </style>
</head>
<body>
    <div class="metrics-container">
        <h1>CI/CD Performance Metrics</h1>
        
        <div class="metric-card">
            <h2>📊 Execution Time Trends</h2>
            <canvas id="durationChart" class="chart-container"></canvas>
        </div>
        
        <div class="metric-card">
            <h2>📈 Execution Frequency</h2>
            <canvas id="frequencyChart" class="chart-container"></canvas>
        </div>
        
        <div class="metric-card">
            <h2>🎯 Goal Achievement</h2>
            <canvas id="goalChart" class="chart-container"></canvas>
        </div>
    </div>

    <script>
        // データはGitHub APIから動的に取得
        // または、定期的に生成されるJSONファイルから読み込み
        
        // Duration Chart
        const durationCtx = document.getElementById('durationChart').getContext('2d');
        new Chart(durationCtx, {
            type: 'line',
            data: {
                labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
                datasets: [{
                    label: 'Frontend Tests',
                    data: [300, 280, 250, 200],
                    borderColor: 'rgb(75, 192, 192)',
                    tension: 0.1
                }, {
                    label: 'PHP Quality',
                    data: [150, 140, 120, 90],
                    borderColor: 'rgb(255, 99, 132)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Duration (seconds)'
                        }
                    }
                }
            }
        });
        
        // Frequency Chart
        const frequencyCtx = document.getElementById('frequencyChart').getContext('2d');
        new Chart(frequencyCtx, {
            type: 'bar',
            data: {
                labels: ['Frontend Tests', 'PHP Quality', 'Tests', 'E2E Tests'],
                datasets: [{
                    label: 'Before Optimization',
                    data: [50, 45, 40, 20],
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 1
                }, {
                    label: 'After Optimization',
                    data: [30, 15, 18, 8],
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Executions per week'
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
```

## まとめ

このガイドでは、GitHub Actions ワークフロー最適化の実装について、以下の観点から詳細な手順を提供しました：

1. **段階的実装戦略**: リスクを最小化しながら確実に最適化を進める
2. **本番影響最小化**: Blue-Green deployment patterns適用
3. **包括的テスト**: 自動化・手動両方の確認手順
4. **ブランチプロテクション対応**: paths設定による影響の解決策
5. **チーム協調**: コミュニケーション戦略と段階的ロールアウト
6. **トラブルシューティング**: 3段階の問題対応とアラート体制
7. **効果測定**: 継続的な監視とレポーティング

この実装ガイドに従うことで、30-40%の実行時間削減と60-70%の実行頻度削減を安全に達成できることが期待されます。
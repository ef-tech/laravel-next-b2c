# GitHub Actions ワークフロー最適化 トラブルシューティングガイド

## 目次

1. [問題分類・レベル定義](#問題分類レベル定義)
2. [よくある問題と解決策](#よくある問題と解決策)
3. [緊急対応手順](#緊急対応手順)
4. [診断ツール・スクリプト](#診断ツールスクリプト)
5. [予防策・監視体制](#予防策監視体制)

## 問題分類・レベル定義

### レベル1: 軽微な問題（自動復旧可能）
- **定義**: 一時的な問題で、リトライ・キャッシュクリア等で解決可能
- **影響**: 個別PR・ブランチレベル
- **対応時間**: 即座～5分
- **例**: キャッシュミス、ネットワーク一時エラー、タイムアウト

### レベル2: 中程度の問題（手動対応必要）
- **定義**: 設定変更・調整が必要だが、開発は継続可能
- **影響**: 特定ワークフロー・機能レベル
- **対応時間**: 15分～1時間
- **例**: paths設定問題、concurrency設定異常、部分的な設定ミス

### レベル3: 重大な問題（緊急対応必要）
- **定義**: 開発・デプロイプロセスに重大な影響
- **影響**: 全体・本番環境レベル
- **対応時間**: 即座（5分以内にロールバック開始）
- **例**: 全ワークフロー停止、本番デプロイブロック、セキュリティ問題

## よくある問題と解決策

### 1. Concurrency関連の問題

#### 問題1-1: 重要なワークフローが意図せずキャンセルされる

**症状**:
```
Workflow 'Tests' was cancelled due to concurrency group limitation
```

**原因**:
- concurrency設定が過度に制限的
- 緊急修正とFeature branchの競合

**解決策**:
```yaml
# 修正前
concurrency:
  group: ${{ github.workflow }}-${{ github.ref }}
  cancel-in-progress: true

# 修正後（mainブランチは保護）
concurrency:
  group: ${{ github.workflow }}-${{ github.ref }}
  cancel-in-progress: ${{ github.ref != 'refs/heads/main' }}
```

**予防策**:
```yaml
# 重要ブランチ専用のconcurrency設定
concurrency:
  group: ${{ github.workflow }}-${{ github.ref }}
  cancel-in-progress: >-
    ${{
      github.ref != 'refs/heads/main' &&
      github.ref != 'refs/heads/develop' &&
      !startsWith(github.ref, 'refs/heads/hotfix/')
    }}
```

#### 問題1-2: Concurrency groupの競合

**症状**:
```
Multiple workflows using the same concurrency group
```

**解決策**:
```yaml
# ワークフロー固有のgroup名を使用
concurrency:
  group: ${{ github.workflow }}-${{ github.ref }}-${{ github.run_number }}
  cancel-in-progress: true
```

### 2. Paths設定関連の問題

#### 問題2-1: 必要なワークフローが実行されない

**症状**:
- フロントエンド変更のPRでバックエンドテストが実行されない
- ブランチプロテクションで必須チェックが失敗

**診断方法**:
```bash
# 変更ファイルとpaths設定の確認
echo "=== 変更ファイル ==="
git diff --name-only HEAD~1

echo "=== Paths設定 ==="
grep -A 10 "paths:" .github/workflows/*.yml

echo "=== 実行されたワークフロー ==="
gh run list --limit 5
```

**解決策A: Skip jobパターン**
```yaml
name: Backend Tests
on:
  pull_request:
    branches: [main]

jobs:
  check-changes:
    outputs:
      should_run: ${{ steps.changes.outputs.backend }}
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: dorny/paths-filter@v2
        id: changes
        with:
          filters: |
            backend:
              - 'backend/**'
              - '.github/workflows/test.yml'
  
  test:
    needs: check-changes
    if: needs.check-changes.outputs.should_run == 'true'
    runs-on: ubuntu-latest
    steps:
      - run: echo "Running backend tests"
      # ... actual test steps
    
  # ブランチプロテクション用のalways-pass job
  always-pass:
    needs: check-changes
    if: needs.check-changes.outputs.should_run == 'false'
    runs-on: ubuntu-latest
    steps:
      - run: echo "✅ Backend tests skipped - no backend changes"
```

**解決策B: Required checks ワークフロー**
```yaml
# .github/workflows/required-checks.yml
name: Required Checks
on:
  pull_request:
    branches: [main]

jobs:
  determine-requirements:
    outputs:
      backend_required: ${{ steps.changes.outputs.backend }}
      frontend_required: ${{ steps.changes.outputs.frontend }}
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
  
  wait-for-backend:
    needs: determine-requirements
    if: needs.determine-requirements.outputs.backend_required == 'true'
    runs-on: ubuntu-latest
    steps:
      - name: Wait for backend checks
        uses: lewagon/wait-on-check-action@v1.3.1
        with:
          ref: ${{ github.ref }}
          check-name: 'PHP Quality Checks / php-quality'
          repo-token: ${{ secrets.GITHUB_TOKEN }}
          wait-interval: 10
      - name: Wait for backend tests
        uses: lewagon/wait-on-check-action@v1.3.1
        with:
          ref: ${{ github.ref }}
          check-name: 'Tests / test'
          repo-token: ${{ secrets.GITHUB_TOKEN }}
          wait-interval: 10
  
  summary:
    needs: [determine-requirements, wait-for-backend]
    if: always()
    runs-on: ubuntu-latest
    steps:
      - run: echo "All required checks completed or skipped appropriately"
```

#### 問題2-2: Paths設定が過度に制限的

**症状**:
```
Shared dependency changes don't trigger relevant workflows
```

**解決策**:
```yaml
# 共通依存関係も含める
paths:
  - 'backend/**'
  - 'package*.json'  # 共通依存関係
  - 'composer.json'
  - 'composer.lock'
  - '.env.example'
  - 'docker-compose.yml'
  - '.github/workflows/test.yml'
```

### 3. キャッシュ関連の問題

#### 問題3-1: キャッシュキーの競合

**症状**:
```
Cache restore failed: conflicting cache keys
```

**解決策**:
```yaml
# より具体的なキャッシュキー
- name: Cache Composer dependencies
  uses: actions/cache@v4
  with:
    path: ~/.composer/cache/files
    key: ${{ runner.os }}-composer-${{ github.workflow }}-${{ hashFiles('**/composer.lock') }}
    restore-keys: |
      ${{ runner.os }}-composer-${{ github.workflow }}-
      ${{ runner.os }}-composer-
```

#### 問題3-2: キャッシュサイズ制限

**症状**:
```
Cache size exceeds 10GB limit
```

**解決策**:
```yaml
# キャッシュパスを分割
- name: Cache Composer cache
  uses: actions/cache@v4
  with:
    path: ~/.composer/cache
    key: composer-cache-${{ hashFiles('**/composer.lock') }}

- name: Cache vendor directory
  uses: actions/cache@v4
  with:
    path: backend/laravel-api/vendor
    key: composer-vendor-${{ hashFiles('**/composer.lock') }}
```

### 4. Pull Request Types関連の問題

#### 問題4-1: Draft PRでワークフローが実行される

**症状**:
```
Workflows run on draft PR when they shouldn't
```

**解決策**:
```yaml
on:
  pull_request:
    types: [opened, synchronize, reopened, ready_for_review]
    branches: [main]

jobs:
  test:
    if: github.event.pull_request.draft == false
    runs-on: ubuntu-latest
```

#### 問題4-2: 特定のPRイベントでワークフローが実行されない

**診断方法**:
```bash
# PRイベントの確認
gh pr view <PR-NUMBER> --json state,isDraft,mergeable

# ワークフロー実行履歴確認
gh run list --event pull_request --limit 10
```

**解決策**:
```yaml
# 明示的にイベントタイプを指定
on:
  pull_request:
    types: [opened, synchronize, reopened, converted_to_draft, ready_for_review]
    branches: [main]

jobs:
  test:
    # draft状態をチェック
    if: >-
      github.event.action != 'converted_to_draft' &&
      github.event.pull_request.draft == false
```

## 緊急対応手順

### レベル3緊急対応: 全ワークフロー無効化

```bash
#!/bin/bash
# emergency-disable-workflows.sh

echo "🚨 EMERGENCY: Disabling all workflows"

# バックアップ作成
if [ ! -d ".github/workflows.backup" ]; then
    cp -r .github/workflows .github/workflows.backup
    echo "✅ Backup created at .github/workflows.backup"
fi

# 全ワークフローを無効化
for file in .github/workflows/*.yml; do
    if [[ "$file" != *.disabled ]] && [[ "$file" != *.backup ]]; then
        mv "$file" "$file.disabled"
        echo "❌ Disabled: $(basename "$file")"
    fi
done

# 緊急用最小限ワークフローを作成
cat > .github/workflows/emergency-status.yml << 'EOF'
name: Emergency Status
on:
  push:
    branches: [main]
  pull_request:
    branches: [main]

jobs:
  emergency-notice:
    runs-on: ubuntu-latest
    steps:
      - run: |
          echo "⚠️ Emergency mode: All workflows temporarily disabled"
          echo "Contact: #dev-ops channel for resolution"
          exit 0
EOF

# コミット・プッシュ
git add .github/workflows/
git commit -m "EMERGENCY: Disable all workflows - investigating issues"
git push origin main

echo ""
echo "🚨 EMERGENCY ACTIONS COMPLETED"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "✅ All workflows disabled"
echo "✅ Backup saved to .github/workflows.backup"
echo "✅ Emergency status workflow active"
echo ""
echo "🔧 NEXT STEPS:"
echo "1. Investigate and fix the root cause"
echo "2. Test fixes in feature branch"
echo "3. Run ./restore-workflows.sh when ready"
echo ""
echo "📞 Emergency contact: #dev-ops Slack channel"
```

### 段階的復旧手順

```bash
#!/bin/bash
# restore-workflows.sh

echo "🔄 Workflow restoration process"

# 復旧前チェック
echo "## Pre-restoration checks"
echo "1. Root cause identified and fixed? [y/N]"
read -r confirmed
if [[ "$confirmed" != "y" ]]; then
    echo "❌ Restoration cancelled. Fix issues first."
    exit 1
fi

echo "2. Tested in feature branch? [y/N]"
read -r tested
if [[ "$tested" != "y" ]]; then
    echo "⚠️ Consider testing in feature branch first"
    echo "Continue anyway? [y/N]"
    read -r force_continue
    if [[ "$force_continue" != "y" ]]; then
        exit 1
    fi
fi

# 段階的復旧
echo "## Restoration strategy:"
echo "1. Frontend Tests (lowest risk)"
echo "2. PHP Quality Checks"  
echo "3. Backend Tests"
echo "4. E2E Tests (highest impact)"
echo ""

restore_workflow() {
    local workflow=$1
    local file_pattern=$2
    
    echo "Restoring: $workflow"
    
    # .disabled ファイルを探して復旧
    for disabled_file in .github/workflows/*${file_pattern}*.disabled; do
        if [ -f "$disabled_file" ]; then
            original_file="${disabled_file%.disabled}"
            mv "$disabled_file" "$original_file"
            echo "✅ Restored: $(basename "$original_file")"
            
            # コミット・プッシュ
            git add "$original_file"
            git commit -m "Restore workflow: $workflow"
            git push origin main
            
            # 動作確認待機
            echo "⏳ Waiting 30s for workflow validation..."
            sleep 30
            
            # 最新実行状況確認
            echo "Latest runs:"
            gh run list --limit 3
            
            echo "Workflow restored successfully? [y/N]"
            read -r success
            if [[ "$success" != "y" ]]; then
                echo "❌ Restoration failed. Re-disabling..."
                mv "$original_file" "$disabled_file"
                git add "$disabled_file"
                git commit -m "Re-disable workflow: $workflow (restoration failed)"
                git push origin main
                return 1
            fi
            
            return 0
        fi
    done
    
    echo "❌ No disabled file found for: $workflow"
    return 1
}

# 段階的復旧実行
restore_workflow "Frontend Tests" "frontend-test"
if [ $? -eq 0 ]; then
    restore_workflow "PHP Quality Checks" "php-quality"
    if [ $? -eq 0 ]; then
        restore_workflow "Backend Tests" "test"
        if [ $? -eq 0 ]; then
            restore_workflow "E2E Tests" "e2e-tests"
        fi
    fi
fi

# 緊急用ワークフローを削除
if [ -f ".github/workflows/emergency-status.yml" ]; then
    rm .github/workflows/emergency-status.yml
    git add .github/workflows/emergency-status.yml
    git commit -m "Remove emergency status workflow"
    git push origin main
fi

echo ""
echo "✅ RESTORATION COMPLETED"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📊 Final status check:"
gh run list --limit 5
```

### 部分的ロールバック

```bash
#!/bin/bash
# partial-rollback.sh

echo "🔄 Partial workflow rollback"

rollback_workflow() {
    local workflow_name=$1
    local backup_available=false
    
    echo "Rolling back: $workflow_name"
    
    # バックアップから復旧
    if [ -d ".github/workflows.backup" ]; then
        for backup_file in .github/workflows.backup/*.yml; do
            if grep -q "name: $workflow_name" "$backup_file" 2>/dev/null; then
                target_file=".github/workflows/$(basename "$backup_file")"
                cp "$backup_file" "$target_file"
                echo "✅ Restored from backup: $(basename "$backup_file")"
                backup_available=true
                break
            fi
        done
    fi
    
    if [ "$backup_available" = false ]; then
        echo "❌ No backup found for: $workflow_name"
        echo "Manual restoration required"
        return 1
    fi
    
    # 変更をコミット
    git add .github/workflows/
    git commit -m "Rollback workflow: $workflow_name"
    git push origin main
    
    echo "✅ Rollback completed for: $workflow_name"
    return 0
}

# 対話的ロールバック
echo "Available workflows for rollback:"
echo "1. Frontend Tests"
echo "2. PHP Quality Checks"
echo "3. Backend Tests" 
echo "4. E2E Tests"
echo "5. All workflows"
echo ""
echo "Select workflow to rollback [1-5]:"
read -r choice

case $choice in
    1) rollback_workflow "Frontend Tests" ;;
    2) rollback_workflow "PHP Quality Checks" ;;
    3) rollback_workflow "Tests" ;;
    4) rollback_workflow "E2E Tests" ;;
    5) 
        echo "Rolling back all workflows..."
        if [ -d ".github/workflows.backup" ]; then
            cp -r .github/workflows.backup/* .github/workflows/
            git add .github/workflows/
            git commit -m "Rollback all workflows to backup"
            git push origin main
            echo "✅ All workflows rolled back"
        else
            echo "❌ No backup directory found"
        fi
        ;;
    *) echo "Invalid choice" ;;
esac
```

## 診断ツール・スクリプト

### 包括的診断スクリプト

```bash
#!/bin/bash
# workflow-diagnostics.sh

echo "🔍 GitHub Actions Workflow Diagnostics"
echo "======================================"
echo ""

# 基本情報収集
collect_basic_info() {
    echo "## Basic Information"
    echo "Timestamp: $(date)"
    echo "Repository: $(git remote get-url origin)"
    echo "Current branch: $(git branch --show-current)"
    echo "Latest commit: $(git log -1 --oneline)"
    echo ""
}

# ワークフロー設定確認
check_workflow_configs() {
    echo "## Workflow Configuration Analysis"
    echo ""
    
    for workflow_file in .github/workflows/*.yml; do
        if [ -f "$workflow_file" ]; then
            workflow_name=$(grep "^name:" "$workflow_file" | head -1 | cut -d: -f2 | xargs)
            echo "### $workflow_name ($(basename "$workflow_file"))"
            
            # Concurrency設定確認
            if grep -q "concurrency:" "$workflow_file"; then
                echo "✅ Concurrency: Configured"
                grep -A 2 "concurrency:" "$workflow_file" | sed 's/^/    /'
            else
                echo "❌ Concurrency: Not configured"
            fi
            
            # Paths設定確認
            if grep -q "paths:" "$workflow_file"; then
                echo "✅ Paths: Configured"
                grep -A 10 "paths:" "$workflow_file" | sed 's/^/    /'
            else
                echo "⚠️ Paths: Not configured"
            fi
            
            # Pull request types確認
            pr_types=$(grep -A 5 "pull_request:" "$workflow_file" | grep "types:" || echo "Not specified")
            echo "PR Types: $pr_types"
            
            echo ""
        fi
    done
}

# 実行履歴分析
analyze_execution_history() {
    echo "## Execution History Analysis (Last 24h)"
    echo ""
    
    for workflow in "Frontend Tests" "PHP Quality Checks" "Tests" "E2E Tests"; do
        echo "### $workflow"
        
        # 成功率
        total_runs=$(gh run list --workflow="$workflow" --created $(date -d '1 day ago' +%Y-%m-%d) --json conclusion 2>/dev/null | jq length || echo 0)
        successful_runs=$(gh run list --workflow="$workflow" --created $(date -d '1 day ago' +%Y-%m-%d) --json conclusion 2>/dev/null | jq '[.[] | select(.conclusion == "success")] | length' || echo 0)
        
        if [ "$total_runs" -gt 0 ]; then
            success_rate=$(echo "scale=1; $successful_runs * 100 / $total_runs" | bc -l)
            echo "Success Rate: $success_rate% ($successful_runs/$total_runs)"
        else
            echo "Success Rate: No runs in last 24h"
        fi
        
        # 平均実行時間
        avg_duration=$(gh run list --workflow="$workflow" --limit 10 --json durationMs 2>/dev/null | \
            jq '[.[] | select(.durationMs != null) | .durationMs] | add / length / 1000' || echo "N/A")
        echo "Average Duration: ${avg_duration}s"
        
        # キャンセル率
        cancelled_runs=$(gh run list --workflow="$workflow" --created $(date -d '1 day ago' +%Y-%m-%d) --json conclusion 2>/dev/null | jq '[.[] | select(.conclusion == "cancelled")] | length' || echo 0)
        if [ "$total_runs" -gt 0 ]; then
            cancel_rate=$(echo "scale=1; $cancelled_runs * 100 / $total_runs" | bc -l)
            echo "Cancel Rate: $cancel_rate% ($cancelled_runs/$total_runs)"
        fi
        
        echo ""
    done
}

# 現在の問題検出
detect_current_issues() {
    echo "## Current Issues Detection"
    echo ""
    
    # 実行中のワークフロー確認
    running_workflows=$(gh run list --status in_progress --json workflowName,createdAt 2>/dev/null || echo "[]")
    running_count=$(echo "$running_workflows" | jq length)
    
    if [ "$running_count" -gt 0 ]; then
        echo "### Currently Running Workflows ($running_count)"
        echo "$running_workflows" | jq -r '.[] | "- \(.workflowName) (started: \(.createdAt))"'
        echo ""
    fi
    
    # 失敗したワークフロー
    failed_workflows=$(gh run list --status failure --limit 5 --json workflowName,createdAt,conclusion 2>/dev/null || echo "[]")
    failed_count=$(echo "$failed_workflows" | jq length)
    
    if [ "$failed_count" -gt 0 ]; then
        echo "### Recent Failures ($failed_count)"
        echo "$failed_workflows" | jq -r '.[] | "- \(.workflowName) (\(.createdAt))"'
        echo ""
    fi
    
    # 異常なキャンセル率
    echo "### Abnormal Cancellation Detection"
    cancel_threshold=30  # 30%以上のキャンセル率を異常とする
    
    for workflow in "Frontend Tests" "PHP Quality Checks" "Tests" "E2E Tests"; do
        total=$(gh run list --workflow="$workflow" --created $(date -d '6 hours ago' +%Y-%m-%d) --json conclusion 2>/dev/null | jq length || echo 0)
        cancelled=$(gh run list --workflow="$workflow" --created $(date -d '6 hours ago' +%Y-%m-%d) --json conclusion 2>/dev/null | jq '[.[] | select(.conclusion == "cancelled")] | length' || echo 0)
        
        if [ "$total" -gt 3 ]; then  # 最低3回の実行がある場合のみチェック
            cancel_rate=$(echo "scale=0; $cancelled * 100 / $total" | bc -l)
            if [ "$cancel_rate" -gt "$cancel_threshold" ]; then
                echo "🚨 $workflow: High cancellation rate ${cancel_rate}% (${cancelled}/${total})"
            fi
        fi
    done
}

# 推奨アクション
suggest_actions() {
    echo "## Recommended Actions"
    echo ""
    
    # 設定改善提案
    echo "### Configuration Improvements"
    
    for workflow_file in .github/workflows/*.yml; do
        if [ -f "$workflow_file" ]; then
            workflow_name=$(grep "^name:" "$workflow_file" | head -1 | cut -d: -f2 | xargs)
            
            improvements=()
            
            # Concurrency未設定チェック
            if ! grep -q "concurrency:" "$workflow_file"; then
                improvements+=("Add concurrency configuration")
            fi
            
            # Paths未設定チェック（e2e-tests.yml以外）
            if [[ "$(basename "$workflow_file")" != "e2e-tests.yml" ]] && ! grep -q "paths:" "$workflow_file"; then
                improvements+=("Add paths configuration")
            fi
            
            # Pull request types未指定チェック
            if grep -q "pull_request:" "$workflow_file" && ! grep -q "types:" "$workflow_file"; then
                improvements+=("Specify pull_request types")
            fi
            
            if [ ${#improvements[@]} -gt 0 ]; then
                echo "#### $workflow_name"
                for improvement in "${improvements[@]}"; do
                    echo "- $improvement"
                done
                echo ""
            fi
        fi
    done
    
    # パフォーマンス改善提案
    echo "### Performance Improvements"
    
    # 高頻度実行ワークフローの特定
    for workflow in "Frontend Tests" "PHP Quality Checks" "Tests" "E2E Tests"; do
        daily_runs=$(gh run list --workflow="$workflow" --created $(date -d '1 day ago' +%Y-%m-%d) --json id 2>/dev/null | jq length || echo 0)
        if [ "$daily_runs" -gt 20 ]; then
            echo "- Consider paths optimization for '$workflow' (${daily_runs} runs/day)"
        fi
    done
    
    # 長時間実行ワークフローの特定
    for workflow in "Frontend Tests" "PHP Quality Checks" "Tests" "E2E Tests"; do
        avg_duration=$(gh run list --workflow="$workflow" --limit 10 --json durationMs 2>/dev/null | \
            jq '[.[] | select(.durationMs != null) | .durationMs] | add / length / 1000' 2>/dev/null || echo 0)
        
        # 10分以上のワークフローに対する提案
        if [ "$avg_duration" != "null" ] && [ "$avg_duration" != "0" ]; then
            if (( $(echo "$avg_duration > 600" | bc -l) )); then
                echo "- Consider optimization for '$workflow' (avg: ${avg_duration}s)"
            fi
        fi
    done
}

# メイン実行
main() {
    collect_basic_info
    check_workflow_configs
    analyze_execution_history
    detect_current_issues
    suggest_actions
    
    echo ""
    echo "🔍 Diagnostics completed at $(date)"
    echo "For detailed logs, use: gh run view <run-id> --log"
}

# ログファイル出力オプション
if [ "$1" = "--output" ]; then
    output_file="workflow_diagnostics_$(date +%Y%m%d_%H%M%S).md"
    main > "$output_file"
    echo "📄 Diagnostics saved to: $output_file"
else
    main
fi
```

### リアルタイム監視スクリプト

```bash
#!/bin/bash
# workflow-monitor.sh

echo "📊 Real-time Workflow Monitor"
echo "Press Ctrl+C to stop"
echo ""

monitor_workflows() {
    while true; do
        clear
        echo "🔄 GitHub Actions Live Monitor - $(date)"
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
        echo ""
        
        # 実行中ワークフロー
        echo "## 🏃 Currently Running"
        running=$(gh run list --status in_progress --json workflowName,createdAt,runNumber --limit 10 2>/dev/null || echo "[]")
        running_count=$(echo "$running" | jq length)
        
        if [ "$running_count" -gt 0 ]; then
            echo "$running" | jq -r '.[] | "🔄 \(.workflowName) #\(.runNumber) (started: \(.createdAt | strptime("%Y-%m-%dT%H:%M:%SZ") | strftime("%H:%M:%S")))"'
        else
            echo "No workflows currently running ✅"
        fi
        echo ""
        
        # 最近完了したワークフロー
        echo "## ✅ Recently Completed (Last 30 min)"
        completed=$(gh run list --created $(date -d '30 minutes ago' +%Y-%m-%d) --json workflowName,conclusion,createdAt --limit 10 2>/dev/null || echo "[]")
        completed_count=$(echo "$completed" | jq length)
        
        if [ "$completed_count" -gt 0 ]; then
            echo "$completed" | jq -r '.[] | 
                if .conclusion == "success" then "✅ \(.workflowName) - SUCCESS"
                elif .conclusion == "failure" then "❌ \(.workflowName) - FAILED"  
                elif .conclusion == "cancelled" then "🚫 \(.workflowName) - CANCELLED"
                else "⚪ \(.workflowName) - \(.conclusion // "UNKNOWN")"
                end'
        else
            echo "No recent completions"
        fi
        echo ""
        
        # キュー状況
        echo "## 📋 Queue Status"
        queued=$(gh run list --status queued --json workflowName --limit 10 2>/dev/null || echo "[]")
        queued_count=$(echo "$queued" | jq length)
        
        if [ "$queued_count" -gt 0 ]; then
            echo "⏳ $queued_count workflows queued"
            echo "$queued" | jq -r '.[] | "  - \(.workflowName)"'
        else
            echo "Queue is empty ✅"
        fi
        echo ""
        
        # アラート検出
        echo "## 🚨 Alerts"
        alerts_found=false
        
        # 長時間実行チェック
        long_running=$(gh run list --status in_progress --json workflowName,createdAt 2>/dev/null | \
            jq --arg threshold "$(date -d '30 minutes ago' -u +%Y-%m-%dT%H:%M:%SZ)" \
            '[.[] | select(.createdAt < $threshold)]')
        long_running_count=$(echo "$long_running" | jq length)
        
        if [ "$long_running_count" -gt 0 ]; then
            echo "⚠️ $long_running_count workflows running longer than 30 minutes"
            echo "$long_running" | jq -r '.[] | "  - \(.workflowName)"'
            alerts_found=true
        fi
        
        # 高失敗率チェック
        recent_failures=$(gh run list --created $(date -d '1 hour ago' +%Y-%m-%d) --json conclusion 2>/dev/null | \
            jq '[.[] | select(.conclusion == "failure")] | length')
        recent_total=$(gh run list --created $(date -d '1 hour ago' +%Y-%m-%d) --json conclusion 2>/dev/null | jq length)
        
        if [ "$recent_total" -gt 5 ] && [ "$recent_failures" -gt 0 ]; then
            failure_rate=$(echo "scale=0; $recent_failures * 100 / $recent_total" | bc -l)
            if [ "$failure_rate" -gt 50 ]; then
                echo "🚨 High failure rate: ${failure_rate}% (${recent_failures}/${recent_total}) in last hour"
                alerts_found=true
            fi
        fi
        
        if [ "$alerts_found" = false ]; then
            echo "No alerts detected ✅"
        fi
        
        echo ""
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
        echo "Refreshing in 10 seconds... (Ctrl+C to stop)"
        
        sleep 10
    done
}

# トラップでクリーンアップ
trap 'echo -e "\n👋 Monitor stopped"; exit 0' INT

monitor_workflows
```

## 予防策・監視体制

### 1. 事前チェックリスト

```bash
#!/bin/bash
# pre-deployment-checklist.sh

echo "📋 Pre-deployment Workflow Checklist"
echo "===================================="
echo ""

checklist_item() {
    local item=$1
    local check_command=$2
    
    echo -n "Checking: $item ... "
    
    if eval "$check_command" >/dev/null 2>&1; then
        echo "✅ PASS"
        return 0
    else
        echo "❌ FAIL"
        return 1
    fi
}

# YAML構文チェック
echo "## 1. YAML Syntax Validation"
for workflow_file in .github/workflows/*.yml; do
    if [ -f "$workflow_file" ]; then
        checklist_item "$(basename "$workflow_file") syntax" "yamllint '$workflow_file'"
    fi
done
echo ""

# 必須設定チェック
echo "## 2. Required Configuration Check"
for workflow_file in .github/workflows/*.yml; do
    if [ -f "$workflow_file" ]; then
        workflow_name=$(basename "$workflow_file" .yml)
        
        # Concurrency設定チェック
        checklist_item "$workflow_name concurrency" "grep -q 'concurrency:' '$workflow_file'"
        
        # 適切なトリガー設定チェック
        checklist_item "$workflow_name triggers" "grep -E '(push|pull_request):' '$workflow_file'"
    fi
done
echo ""

# セキュリティチェック
echo "## 3. Security Check"
checklist_item "No hardcoded secrets" "! grep -r 'password\|token\|secret' .github/workflows/ --include='*.yml' | grep -v '\${{'"
checklist_item "No external script execution" "! grep -r 'curl.*|.*sh' .github/workflows/ --include='*.yml'"
echo ""

# パフォーマンスチェック
echo "## 4. Performance Check"
checklist_item "Caching configured" "grep -r 'uses: actions/cache' .github/workflows/ --include='*.yml'"
checklist_item "No unnecessary checkouts" "[ \$(grep -c 'uses: actions/checkout' .github/workflows/*.yml) -le 20 ]"
echo ""

echo "📋 Checklist completed"
```

### 2. 継続的監視設定

```yaml
# .github/workflows/workflow-health-monitor.yml
name: Workflow Health Monitor

on:
  schedule:
    # 毎時実行
    - cron: '0 * * * *'
  workflow_dispatch:

jobs:
  monitor:
    runs-on: ubuntu-latest
    steps:
      - name: Checkout
        uses: actions/checkout@v4
        
      - name: Check workflow health
        run: |
          # 異常検出ロジック
          
          # 1. 高失敗率検出
          for workflow in "Frontend Tests" "PHP Quality Checks" "Tests" "E2E Tests"; do
            total=$(gh run list --workflow="$workflow" --created $(date -d '1 hour ago' +%Y-%m-%d) --json conclusion | jq length)
            failures=$(gh run list --workflow="$workflow" --created $(date -d '1 hour ago' +%Y-%m-%d) --json conclusion | jq '[.[] | select(.conclusion == "failure")] | length')
            
            if [ "$total" -gt 5 ] && [ "$failures" -gt 0 ]; then
              failure_rate=$(echo "scale=0; $failures * 100 / $total" | bc)
              if [ "$failure_rate" -gt 50 ]; then
                echo "::warning::High failure rate for $workflow: ${failure_rate}% (${failures}/${total})"
              fi
            fi
          done
          
          # 2. 長時間実行検出
          long_running=$(gh run list --status in_progress --json workflowName,createdAt | \
            jq --arg threshold "$(date -d '2 hours ago' -u +%Y-%m-%dT%H:%M:%SZ)" \
            '[.[] | select(.createdAt < $threshold)]')
          
          if [ "$(echo "$long_running" | jq length)" -gt 0 ]; then
            echo "$long_running" | jq -r '.[] | "::warning::Long running workflow: \(.workflowName)"'
          fi
        env:
          GITHUB_TOKEN: ${{ secrets.GITHUB_TOKEN }}
          
      - name: Notify Slack on issues
        if: failure()
        uses: 8398a7/action-slack@v3
        with:
          status: failure
          text: "🚨 Workflow health issues detected"
        env:
          SLACK_WEBHOOK_URL: ${{ secrets.SLACK_WEBHOOK_URL }}
```

### 3. 自動復旧機能

```yaml
# .github/workflows/auto-recovery.yml  
name: Auto Recovery

on:
  workflow_run:
    workflows: ["Frontend Tests", "PHP Quality Checks", "Tests"]
    types: [completed]

jobs:
  auto-recovery:
    if: github.event.workflow_run.conclusion == 'failure'
    runs-on: ubuntu-latest
    steps:
      - name: Analyze failure
        id: analysis
        run: |
          workflow_name="${{ github.event.workflow_run.name }}"
          run_id="${{ github.event.workflow_run.id }}"
          
          # ログ分析
          gh run view "$run_id" --log > failure_log.txt
          
          # 回復可能なエラーパターンチェック
          if grep -E "(cache|network|timeout|rate limit)" failure_log.txt; then
            echo "recoverable=true" >> $GITHUB_OUTPUT
            echo "recovery_reason=transient_error" >> $GITHUB_OUTPUT
          elif grep -E "(disk space|memory)" failure_log.txt; then
            echo "recoverable=true" >> $GITHUB_OUTPUT  
            echo "recovery_reason=resource_issue" >> $GITHUB_OUTPUT
          else
            echo "recoverable=false" >> $GITHUB_OUTPUT
          fi
        env:
          GITHUB_TOKEN: ${{ secrets.GITHUB_TOKEN }}
          
      - name: Auto retry
        if: steps.analysis.outputs.recoverable == 'true'
        run: |
          echo "Attempting auto-recovery for recoverable failure"
          
          # キャッシュクリア（該当する場合）
          if [ "${{ steps.analysis.outputs.recovery_reason }}" = "transient_error" ]; then
            gh cache delete --all || true
          fi
          
          # ワークフローの再実行
          gh run rerun ${{ github.event.workflow_run.id }}
          
          # 通知
          curl -X POST -H 'Content-type: application/json' \
            --data '{"text":"🔄 Auto-recovery triggered for ${{ github.event.workflow_run.name }}"}' \
            "${{ secrets.SLACK_WEBHOOK_URL }}"
        env:
          GITHUB_TOKEN: ${{ secrets.GITHUB_TOKEN }}
          
      - name: Alert on non-recoverable failure
        if: steps.analysis.outputs.recoverable == 'false'
        run: |
          curl -X POST -H 'Content-type: application/json' \
            --data '{"text":"🚨 Non-recoverable failure in ${{ github.event.workflow_run.name }} - manual intervention required"}' \
            "${{ secrets.SLACK_WEBHOOK_URL }}"
```

このトラブルシューティングガイドにより、GitHub Actions ワークフロー最適化における問題の早期発見・迅速な対応・継続的な改善が実現できます。
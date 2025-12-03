#!/usr/bin/env bash
################################################################################
# Git Worktree並列開発環境 - クリーンアップスクリプト
################################################################################
# Worktree環境とDockerリソースを完全削除
#
# 使い方:
#   ./scripts/worktree/cleanup.sh <worktree-path>
#   ./scripts/worktree/cleanup.sh <worktree-id>
#
# 例:
#   ./scripts/worktree/cleanup.sh ~/worktrees/wt0
#   ./scripts/worktree/cleanup.sh 0
################################################################################

set -euo pipefail

# ============================================
# スクリプトディレクトリ取得
# ============================================
readonly SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
readonly PROJECT_ROOT="$(cd "${SCRIPT_DIR}/../.." && pwd)"
readonly PROJECT_NAME="$(basename "${PROJECT_ROOT}")"

# ============================================
# port-manager.sh読み込み
# ============================================
# shellcheck source=./port-manager.sh
source "${SCRIPT_DIR}/port-manager.sh"

# ============================================
# エラーハンドリング
# ============================================
error() {
    echo "❌ エラー: $1" >&2
    exit 1
}

# ============================================
# Worktree ID取得
# ============================================
get_worktree_id_from_input() {
    local input="$1"

    # 数字のみの場合はWorktree IDとして扱う
    if [[ "${input}" =~ ^[0-9]+$ ]]; then
        echo "${input}"
        return 0
    fi

    # パスの場合はWorktree IDを抽出
    if [[ "${input}" =~ wt([0-9]+) ]]; then
        echo "${BASH_REMATCH[1]}"
        return 0
    fi

    error "Worktree IDまたはパスの形式が不正です: ${input}"
}

# ============================================
# Dockerリソース削除
# ============================================
cleanup_docker_resources() {
    local worktree_id="$1"
    local compose_project_name="wt${worktree_id}"

    echo "🧹 Dockerリソースをクリーンアップしています..." >&2
    echo "   Worktree ID: ${worktree_id}" >&2
    echo "   COMPOSE_PROJECT_NAME: ${compose_project_name}" >&2

    # コンテナ停止・削除
    local containers
    containers=$(docker ps -aq --filter "name=${compose_project_name}-" 2>/dev/null || true)
    if [[ -n "${containers}" ]]; then
        echo "   - コンテナ停止・削除中..." >&2
        # shellcheck disable=SC2086
        docker stop ${containers} >/dev/null 2>&1 || true
        # shellcheck disable=SC2086
        docker rm ${containers} >/dev/null 2>&1 || true
    else
        echo "   - コンテナなし（スキップ）" >&2
    fi

    # ネットワーク削除
    local network="${compose_project_name}-network"
    if docker network ls --format '{{.Name}}' | grep -q "^${network}\$"; then
        echo "   - ネットワーク削除中: ${network}" >&2
        docker network rm "${network}" >/dev/null 2>&1 || true
    else
        echo "   - ネットワークなし（スキップ）" >&2
    fi

    # ボリューム削除
    local volumes
    volumes=$(docker volume ls --format '{{.Name}}' | grep "^${compose_project_name}-" || true)
    if [[ -n "${volumes}" ]]; then
        echo "   - ボリューム削除中..." >&2
        while IFS= read -r volume; do
            echo "     - ${volume}" >&2
            docker volume rm "${volume}" >/dev/null 2>&1 || true
        done <<< "${volumes}"
    else
        echo "   - ボリュームなし（スキップ）" >&2
    fi

    echo "✅ Dockerリソースクリーンアップ完了" >&2
}

# ============================================
# Worktree削除
# ============================================
cleanup_worktree() {
    local worktree_path="$1"

    echo "" >&2
    echo "🗑️  Worktreeを削除しています..." >&2
    echo "   パス: ${worktree_path}" >&2

    if [[ ! -d "${worktree_path}" ]]; then
        echo "   - Worktreeディレクトリが存在しません（スキップ）" >&2
        return 0
    fi

    # git worktree remove実行
    if git worktree remove --force "${worktree_path}" >&2 2>&1; then
        echo "✅ Worktree削除完了" >&2
    else
        echo "⚠️  警告: Worktree削除に失敗しました" >&2
        echo "   手動で削除してください: rm -rf ${worktree_path}" >&2
    fi
}

# ============================================
# メイン処理
# ============================================
main() {
    if [[ $# -lt 1 ]]; then
        error "引数が不足しています。使い方: $0 <worktree-path|worktree-id>"
    fi

    local input="$1"
    local worktree_id
    worktree_id=$(get_worktree_id_from_input "${input}")

    local worktree_path="${PROJECT_ROOT}/../${PROJECT_NAME}-wt${worktree_id}"

    echo "" >&2
    echo "=========================================" >&2
    echo "🧹 Git Worktreeクリーンアップ" >&2
    echo "=========================================" >&2
    echo "" >&2
    echo "Worktree ID: ${worktree_id}" >&2
    echo "Worktree パス: ${worktree_path}" >&2
    echo "" >&2

    # Dockerリソース削除
    cleanup_docker_resources "${worktree_id}"

    # Worktree削除
    cleanup_worktree "${worktree_path}"

    echo "" >&2
    echo "=========================================" >&2
    echo "🎉 クリーンアップ完了!" >&2
    echo "=========================================" >&2
}

# スクリプト実行
main "$@"

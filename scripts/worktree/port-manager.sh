#!/usr/bin/env bash
# ============================================
# Git Worktree ポート番号管理スクリプト
# ============================================
# 機能:
#   - Worktree IDの自動割り当て (0-99の範囲)
#   - ポート番号の自動計算 (外部公開7サービス)
#   - Worktree ID一覧とポート番号の表示
#   - 削除済みIDの優先再利用
# ============================================

set -euo pipefail

# ============================================
# 定数定義: ポート番号レンジ (ベースポート)
# ============================================
# 注意: PostgreSQL (5432) と Redis (6379) は内部ネットワーク専用のため、
#       ホストに公開されません。ポート番号レンジから除外されています。
readonly PORT_LARAVEL_API_BASE=13000
readonly PORT_USER_APP_BASE=13100
readonly PORT_ADMIN_APP_BASE=13200
readonly PORT_MINIO_CONSOLE_BASE=13300
readonly PORT_MAILPIT_UI_BASE=14200
readonly PORT_MAILPIT_SMTP_BASE=14300
readonly PORT_MINIO_API_BASE=14400

readonly MAX_WORKTREE_ID=99
readonly MIN_WORKTREE_ID=0

# ============================================
# ヘルプメッセージ
# ============================================
show_help() {
    cat <<EOF
使用方法: $0 <コマンド> [オプション]

コマンド:
  next-id       次に利用可能なWorktree IDを取得 (0-99の範囲、削除済みID優先再利用)
  calculate-ports <worktree_id>
                Worktree IDから外部公開7サービスのポート番号を計算してJSON形式で出力
  list          全Worktreeとそのポート番号を表形式で表示
  reverse-lookup <port>
                ポート番号からWorktree IDを逆算

例:
  $0 next-id                  # 次に利用可能なIDを取得
  $0 calculate-ports 0        # Worktree ID 0のポート番号を計算
  $0 list                     # 全Worktreeのポート番号一覧を表示
  $0 reverse-lookup 13100     # User AppポートからWorktree IDを逆算

終了コード:
  0 - 成功
  1 - エラー (引数不正、ID枯渇等)
EOF
}

# ============================================
# エラーハンドリング
# ============================================
error() {
    echo "❌ エラー: $*" >&2
    exit 1
}

# ============================================
# Worktree ID管理: 使用中IDを取得
# ============================================
get_used_worktree_ids() {
    # git worktree listからworktree IDを抽出
    # パス形式の想定: ../<プロジェクト名>-wt0, ../<プロジェクト名>-wt1, etc.
    git worktree list --porcelain 2>/dev/null | \
        grep '^worktree' | \
        awk '{print $2}' | \
        grep -oE 'wt[0-9]+$' | \
        sed 's/wt//' | \
        sort -n || true
}

# ============================================
# Worktree ID管理: 次に利用可能なIDを検索
# ============================================
get_next_available_id() {
    local used_ids
    used_ids=$(get_used_worktree_ids)

    # 削除済みIDを優先的に再利用
    for id in $(seq ${MIN_WORKTREE_ID} ${MAX_WORKTREE_ID}); do
        if ! echo "${used_ids}" | grep -qx "${id}"; then
            echo "${id}"
            return 0
        fi
    done

    # ID枯渇エラー
    error "Worktree IDが枯渇しました (最大100個まで)"
}

# ============================================
# ポート番号計算: Worktree IDから外部公開7サービスのポート番号を計算
# ============================================
calculate_ports() {
    local worktree_id="$1"

    # Worktree ID検証
    if ! [[ "${worktree_id}" =~ ^[0-9]+$ ]]; then
        error "Worktree IDは数値である必要があります: ${worktree_id}"
    fi

    if (( worktree_id < MIN_WORKTREE_ID || worktree_id > MAX_WORKTREE_ID )); then
        error "Worktree IDは${MIN_WORKTREE_ID}-${MAX_WORKTREE_ID}の範囲である必要があります: ${worktree_id}"
    fi

    # ポート番号計算 (base_port + worktree_id)
    local port_laravel_api=$((PORT_LARAVEL_API_BASE + worktree_id))
    local port_user_app=$((PORT_USER_APP_BASE + worktree_id))
    local port_admin_app=$((PORT_ADMIN_APP_BASE + worktree_id))
    local port_minio_console=$((PORT_MINIO_CONSOLE_BASE + worktree_id))
    local port_mailpit_ui=$((PORT_MAILPIT_UI_BASE + worktree_id))
    local port_mailpit_smtp=$((PORT_MAILPIT_SMTP_BASE + worktree_id))
    local port_minio_api=$((PORT_MINIO_API_BASE + worktree_id))

    # JSON形式で出力
    cat <<EOF
{
  "worktree_id": ${worktree_id},
  "ports": {
    "laravel_api": ${port_laravel_api},
    "user_app": ${port_user_app},
    "admin_app": ${port_admin_app},
    "minio_console": ${port_minio_console},
    "mailpit_ui": ${port_mailpit_ui},
    "mailpit_smtp": ${port_mailpit_smtp},
    "minio_api": ${port_minio_api}
  }
}
EOF
}

# ============================================
# Worktreeポート番号一覧表示
# ============================================
list_worktrees() {
    echo "========================================="
    echo "Git Worktree ポート番号一覧"
    echo "========================================="
    printf "%-4s %-30s %-10s %-10s %-10s %-10s %-10s %-10s %-10s\n" \
        "ID" "ブランチ" "Laravel" "User" "Admin" "MinIO C" "Mailpit UI" "Mailpit SMTP" "MinIO API"
    echo "-----------------------------------------------------------------------------------------------------------------------------------"

    # git worktree listから情報を取得
    git worktree list --porcelain 2>/dev/null | \
        awk '
            /^worktree/ { worktree=$2 }
            /^branch/ { branch=$2; gsub(/.*\//, "", branch) }
            /^$/ {
                if (worktree ~ /wt[0-9]+$/) {
                    # BSD awk互換: matchの代わりにsubを使用
                    id = worktree
                    sub(/.*wt/, "", id)
                    print id, branch, worktree
                }
                worktree=""; branch=""
            }
        ' | \
        while read -r id branch path; do
            # ポート番号計算
            local port_laravel=$((PORT_LARAVEL_API_BASE + id))
            local port_user=$((PORT_USER_APP_BASE + id))
            local port_admin=$((PORT_ADMIN_APP_BASE + id))
            local port_minio_c=$((PORT_MINIO_CONSOLE_BASE + id))
            local port_mailpit_ui=$((PORT_MAILPIT_UI_BASE + id))
            local port_mailpit_smtp=$((PORT_MAILPIT_SMTP_BASE + id))
            local port_minio_api=$((PORT_MINIO_API_BASE + id))

            printf "%-4s %-30s %-10s %-10s %-10s %-10s %-10s %-10s %-10s\n" \
                "${id}" "${branch}" "${port_laravel}" "${port_user}" "${port_admin}" "${port_minio_c}" \
                "${port_mailpit_ui}" "${port_mailpit_smtp}" "${port_minio_api}"
        done

    echo "========================================="
}

# ============================================
# ポート番号からWorktree IDを逆算
# ============================================
reverse_lookup() {
    local port="$1"

    # ポート番号検証
    if ! [[ "${port}" =~ ^[0-9]+$ ]]; then
        error "ポート番号は数値である必要があります: ${port}"
    fi

    # 各サービスのベースポートと比較
    local base_ports=(
        "${PORT_LARAVEL_API_BASE}:Laravel API"
        "${PORT_USER_APP_BASE}:User App"
        "${PORT_ADMIN_APP_BASE}:Admin App"
        "${PORT_MINIO_CONSOLE_BASE}:MinIO Console"
        "${PORT_MAILPIT_UI_BASE}:Mailpit UI"
        "${PORT_MAILPIT_SMTP_BASE}:Mailpit SMTP"
        "${PORT_MINIO_API_BASE}:MinIO API"
    )

    for entry in "${base_ports[@]}"; do
        local base_port="${entry%%:*}"
        local service_name="${entry#*:}"

        if (( port >= base_port && port < base_port + 100 )); then
            local worktree_id=$((port - base_port))
            echo "ポート ${port} は ${service_name} の Worktree ID ${worktree_id} です"
            return 0
        fi
    done

    error "ポート番号 ${port} はWorktreeのポート範囲外です"
}

# ============================================
# メイン処理
# ============================================
main() {
    if [[ $# -eq 0 ]]; then
        show_help
        exit 1
    fi

    local command="$1"
    shift

    case "${command}" in
        next-id)
            get_next_available_id
            ;;
        calculate-ports)
            if [[ $# -ne 1 ]]; then
                error "calculate-portsコマンドにはWorktree IDが必要です"
            fi
            calculate_ports "$1"
            ;;
        list)
            list_worktrees
            ;;
        reverse-lookup)
            if [[ $# -ne 1 ]]; then
                error "reverse-lookupコマンドにはポート番号が必要です"
            fi
            reverse_lookup "$1"
            ;;
        help|--help|-h)
            show_help
            ;;
        *)
            error "不明なコマンド: ${command}\n\n$(show_help)"
            ;;
    esac
}

# スクリプトが直接実行された場合のみmain関数を呼び出す
# (sourceされた場合は呼び出さない)
if [[ "${BASH_SOURCE[0]}" == "${0}" ]]; then
    main "$@"
fi

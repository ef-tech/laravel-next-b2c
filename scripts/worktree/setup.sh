#!/usr/bin/env bash
# ============================================
# Git Worktree セットアップ自動化スクリプト
# ============================================
# 機能:
#   - Worktree作成とID自動割り当て
#   - 環境変数ファイル (.env) 自動生成
#   - ポート番号自動設定
#   - 依存関係インストール (Composer, npm)
#   - セットアップ完了メッセージ表示
# ============================================

set -euo pipefail

# ============================================
# プロジェクトルート取得
# ============================================
readonly SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
readonly PROJECT_ROOT="$(cd "${SCRIPT_DIR}/../.." && pwd)"
readonly PROJECT_NAME="$(basename "${PROJECT_ROOT}")"
readonly PORT_MANAGER="${SCRIPT_DIR}/port-manager.sh"

# ============================================
# ヘルプメッセージ
# ============================================
show_help() {
    cat <<EOF
使用方法: $0 <ブランチ名> [作成元]

Git Worktreeを作成し、開発環境を自動セットアップします。

引数:
  <ブランチ名>  作成するブランチ名 (例: feature/new-feature)
  [作成元]      ブランチ作成元の参照 (例: origin/main, main, HEAD)
                省略時は既存ブランチが必要

例:
  # 既存ブランチからWorktree作成
  $0 feature/existing-branch

  # origin/mainから新しいブランチを作成
  $0 feature/new-feature origin/main

  # mainから新しいブランチを作成
  $0 feature/new-feature main

処理内容:
  1. 次に利用可能なWorktree IDを自動取得
  2. Git Worktreeを作成 (パス: ../<プロジェクト名>-wt<ID>)
  3. .envファイルを自動生成 (ポート番号、DB名、キャッシュプレフィックス設定)
  4. Composer install実行 (Laravel依存関係)
  5. npm install実行 (User App, Admin App)
  6. セットアップ完了メッセージ表示

終了コード:
  0 - 成功
  1 - エラー (引数不正、ID枯渇、ブランチ不存在等)
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
# 入力検証
# ============================================
validate_input() {
    if [[ $# -lt 1 ]] || [[ $# -gt 2 ]]; then
        show_help
        exit 1
    fi

    local branch_name="$1"
    local from_ref="${2:-}"

    # FROM引数が指定されている場合
    if [[ -n "${from_ref}" ]]; then
        # FROM参照の存在確認
        if ! git rev-parse --verify "${from_ref}" >/dev/null 2>&1; then
            error "FROM参照 '${from_ref}' が見つかりません"
        fi

        # ブランチが既に存在する場合はエラー
        if git rev-parse --verify "${branch_name}" >/dev/null 2>&1; then
            error "ブランチ '${branch_name}' は既に存在します。FROM引数は新しいブランチ作成時のみ使用できます。"
        fi
    else
        # FROM引数がない場合は既存ブランチが必要
        if ! git rev-parse --verify "${branch_name}" >/dev/null 2>&1; then
            error "ブランチ '${branch_name}' が存在しません。先にブランチを作成するか、FROM引数を指定してください。"
        fi
    fi
}

# ============================================
# Worktree作成
# ============================================
create_worktree() {
    local branch_name="$1"
    local worktree_id="$2"
    local from_ref="${3:-}"
    local worktree_path="${PROJECT_ROOT}/../${PROJECT_NAME}-wt${worktree_id}"

    echo "📁 Worktreeを作成しています..." >&2
    echo "   ID: ${worktree_id}" >&2
    echo "   ブランチ: ${branch_name}" >&2
    if [[ -n "${from_ref}" ]]; then
        echo "   作成元: ${from_ref}" >&2
    fi
    echo "   パス: ${worktree_path}" >&2

    # Worktreeディレクトリの親ディレクトリが存在することを確認
    mkdir -p "$(dirname "${worktree_path}")"

    # git worktree add実行
    if [[ -n "${from_ref}" ]]; then
        # FROM引数がある場合：新しいブランチを作成
        if ! git worktree add -b "${branch_name}" "${worktree_path}" "${from_ref}" >&2; then
            error "Worktreeの作成に失敗しました"
        fi
    else
        # FROM引数がない場合：既存ブランチをチェックアウト
        if ! git worktree add "${worktree_path}" "${branch_name}" >&2; then
            error "Worktreeの作成に失敗しました"
        fi
    fi

    echo "✅ Worktree作成完了" >&2
    echo "${worktree_path}"
}

# ============================================
# 環境変数ヘルパー関数
# ============================================
# 環境変数を設定または追加（macOS互換）
update_env_var() {
    local env_file="$1"
    local key="$2"
    local value="$3"

    if grep -q "^${key}=" "${env_file}"; then
        # 既存行を上書き（macOS互換のためsed -i ''を使用）
        sed -i '' "s|^${key}=.*|${key}=${value}|" "${env_file}"
    else
        # 新規行を追加
        echo "${key}=${value}" >> "${env_file}"
    fi
}

# ポート番号をJSONから抽出
extract_port() {
    local ports_json="$1"
    local service_key="$2"
    echo "${ports_json}" | grep -o "\"${service_key}\": [0-9]*" | awk '{print $2}'
}

# ============================================
# バックエンド環境変数ファイル更新
# ============================================
update_backend_env() {
    local env_file="$1"
    local worktree_id="$2"
    local port_laravel="$3"
    local port_user="$4"
    local port_admin="$5"
    local port_pgsql="$6"
    local port_redis="$7"
    local port_mailpit_smtp="$8"
    local port_mailpit_ui="$9"
    local port_minio_api="${10}"
    local port_minio_console="${11}"

    # ポート番号設定
    update_env_var "${env_file}" "WORKTREE_ID" "${worktree_id}"
    update_env_var "${env_file}" "APP_PORT" "${port_laravel}"
    update_env_var "${env_file}" "E2E_USER_URL" "http://localhost:${port_user}"
    update_env_var "${env_file}" "E2E_ADMIN_URL" "http://localhost:${port_admin}"
    update_env_var "${env_file}" "E2E_API_URL" "http://localhost:${port_laravel}"
    update_env_var "${env_file}" "FORWARD_DB_PORT" "${port_pgsql}"
    update_env_var "${env_file}" "FORWARD_REDIS_PORT" "${port_redis}"
    update_env_var "${env_file}" "FORWARD_MAILPIT_PORT" "${port_mailpit_smtp}"
    update_env_var "${env_file}" "FORWARD_MAILPIT_DASHBOARD_PORT" "${port_mailpit_ui}"
    update_env_var "${env_file}" "FORWARD_MINIO_PORT" "${port_minio_api}"
    update_env_var "${env_file}" "FORWARD_MINIO_CONSOLE_PORT" "${port_minio_console}"

    # Worktree並列開発設定
    if ! grep -q "# Git Worktree並列開発設定" "${env_file}"; then
        echo "" >> "${env_file}"
        echo "# Git Worktree並列開発設定" >> "${env_file}"
    fi
    update_env_var "${env_file}" "COMPOSE_PROJECT_NAME" "wt${worktree_id}"
    update_env_var "${env_file}" "DB_DATABASE" "laravel_wt${worktree_id}"
    update_env_var "${env_file}" "CACHE_PREFIX" "wt${worktree_id}_"
    update_env_var "${env_file}" "CORS_ALLOWED_ORIGINS" "http://localhost:${port_user},http://localhost:${port_admin}"
}

# ============================================
# 環境変数ファイル生成
# ============================================
generate_env_file() {
    local worktree_path="$1"
    local worktree_id="$2"
    local ports_json="$3"

    echo "" >&2
    echo "⚙️  環境変数ファイルを生成しています..." >&2

    # ポート番号を抽出
    local port_laravel=$(extract_port "${ports_json}" "laravel_api")
    local port_user=$(extract_port "${ports_json}" "user_app")
    local port_admin=$(extract_port "${ports_json}" "admin_app")
    local port_minio_console=$(extract_port "${ports_json}" "minio_console")
    local port_pgsql=$(extract_port "${ports_json}" "pgsql")
    local port_redis=$(extract_port "${ports_json}" "redis")
    local port_mailpit_ui=$(extract_port "${ports_json}" "mailpit_ui")
    local port_mailpit_smtp=$(extract_port "${ports_json}" "mailpit_smtp")
    local port_minio_api=$(extract_port "${ports_json}" "minio_api")

    # ルートの.env設定
    cp "${PROJECT_ROOT}/.env.example" "${worktree_path}/.env"
    update_backend_env "${worktree_path}/.env" "${worktree_id}" \
        "${port_laravel}" "${port_user}" "${port_admin}" \
        "${port_pgsql}" "${port_redis}" "${port_mailpit_smtp}" \
        "${port_mailpit_ui}" "${port_minio_api}" "${port_minio_console}"

    # backend/laravel-api/.env設定
    if [[ -f "${PROJECT_ROOT}/.env.example" ]]; then
        cp "${PROJECT_ROOT}/.env.example" "${worktree_path}/backend/laravel-api/.env"
        update_backend_env "${worktree_path}/backend/laravel-api/.env" "${worktree_id}" \
            "${port_laravel}" "${port_user}" "${port_admin}" \
            "${port_pgsql}" "${port_redis}" "${port_mailpit_smtp}" \
            "${port_mailpit_ui}" "${port_minio_api}" "${port_minio_console}"
    fi

    # フロントエンド環境変数設定 (User App, Admin App)
    # User App
    if [[ ! -d "${worktree_path}/frontend/user-app" ]]; then
        error "User Appディレクトリが存在しません: ${worktree_path}/frontend/user-app"
    fi

    cat > "${worktree_path}/frontend/user-app/.env.local" <<EOF
# Git Worktree ${worktree_id} - User App環境変数
NEXT_PUBLIC_API_URL=http://localhost:${port_laravel}
NEXT_PUBLIC_API_BASE_URL=http://localhost:${port_laravel}
E2E_USER_URL=http://localhost:${port_user}
E2E_ADMIN_URL=http://localhost:${port_admin}
E2E_API_URL=http://localhost:${port_laravel}
EOF

    # Admin App
    if [[ ! -d "${worktree_path}/frontend/admin-app" ]]; then
        error "Admin Appディレクトリが存在しません: ${worktree_path}/frontend/admin-app"
    fi

    cat > "${worktree_path}/frontend/admin-app/.env.local" <<EOF
# Git Worktree ${worktree_id} - Admin App環境変数
NEXT_PUBLIC_API_URL=http://localhost:${port_laravel}
NEXT_PUBLIC_API_BASE_URL=http://localhost:${port_laravel}
E2E_ADMIN_URL=http://localhost:${port_admin}
E2E_USER_URL=http://localhost:${port_user}
E2E_API_URL=http://localhost:${port_laravel}
EOF

    echo "✅ 環境変数ファイル生成完了" >&2
}

# ============================================
# 依存関係インストール
# ============================================
install_dependencies() {
    local worktree_path="$1"

    echo "" >&2
    echo "📦 依存関係をインストールしています..." >&2

    # Composer install (Laravel) - ENV_VALIDATION_SKIP=trueで環境変数検証をスキップ
    echo "   - Composer install (Laravel API)..." >&2
    if ! (cd "${worktree_path}/backend/laravel-api" && ENV_VALIDATION_SKIP=true composer install --no-interaction --prefer-dist >&2); then
        error "Composer installに失敗しました"
    fi

    # npm install (User App) - CI=trueでhuskyのprepareスクリプトをスキップ
    echo "   - npm install (User App)..." >&2
    if ! (cd "${worktree_path}/frontend/user-app" && CI=true npm install >&2); then
        error "npm install (User App) に失敗しました"
    fi

    # npm install (Admin App) - CI=trueでhuskyのprepareスクリプトをスキップ
    echo "   - npm install (Admin App)..." >&2
    if ! (cd "${worktree_path}/frontend/admin-app" && CI=true npm install >&2); then
        error "npm install (Admin App) に失敗しました"
    fi

    # Laravelキャッシュクリア
    echo "   - Laravelキャッシュクリア..." >&2
    (cd "${worktree_path}/backend/laravel-api" && php artisan cache:clear >/dev/null 2>&1 || true)
    (cd "${worktree_path}/backend/laravel-api" && php artisan config:clear >/dev/null 2>&1 || true)
    (cd "${worktree_path}/backend/laravel-api" && php artisan route:clear >/dev/null 2>&1 || true)

    # Laravelストレージディレクトリ権限設定
    echo "   - ストレージディレクトリ権限設定..." >&2
    chmod -R 775 "${worktree_path}/backend/laravel-api/storage" 2>/dev/null || true
    chmod -R 775 "${worktree_path}/backend/laravel-api/bootstrap/cache" 2>/dev/null || true

    # APP_KEY生成（ENV_VALIDATION_SKIP=trueで環境変数検証をスキップ）
    echo "   - APP_KEY生成中..." >&2
    if ! (cd "${worktree_path}/backend/laravel-api" && ENV_VALIDATION_SKIP=true php artisan key:generate --no-interaction >&2); then
        error "APP_KEY生成に失敗しました"
    fi

    echo "✅ 依存関係インストール完了" >&2
}

# ============================================
# セットアップ完了メッセージ
# ============================================
show_completion_message() {
    local worktree_path="$1"
    local worktree_id="$2"
    local ports_json="$3"

    echo ""
    echo "========================================="
    echo "🎉 Git Worktreeセットアップ完了!"
    echo "========================================="
    echo ""
    echo "Worktree ID: ${worktree_id}"
    echo "Worktree パス: ${worktree_path}"
    echo ""
    echo "ポート番号一覧:"
    echo "  Laravel API:        $(echo "${ports_json}" | grep -o '"laravel_api": [0-9]*' | awk '{print $2}')"
    echo "  User App:           $(echo "${ports_json}" | grep -o '"user_app": [0-9]*' | awk '{print $2}')"
    echo "  Admin App:          $(echo "${ports_json}" | grep -o '"admin_app": [0-9]*' | awk '{print $2}')"
    echo "  MinIO Console:      $(echo "${ports_json}" | grep -o '"minio_console": [0-9]*' | awk '{print $2}')"
    echo "  PostgreSQL:         $(echo "${ports_json}" | grep -o '"pgsql": [0-9]*' | awk '{print $2}')"
    echo "  Redis:              $(echo "${ports_json}" | grep -o '"redis": [0-9]*' | awk '{print $2}')"
    echo "  Mailpit UI:         $(echo "${ports_json}" | grep -o '"mailpit_ui": [0-9]*' | awk '{print $2}')"
    echo "  Mailpit SMTP:       $(echo "${ports_json}" | grep -o '"mailpit_smtp": [0-9]*' | awk '{print $2}')"
    echo "  MinIO API:          $(echo "${ports_json}" | grep -o '"minio_api": [0-9]*' | awk '{print $2}')"
    echo ""
    echo "次のステップ:"
    echo "  1. Worktreeに移動:"
    echo "     cd ${worktree_path}"
    echo ""
    echo "  2. Docker環境を起動:"
    echo "     make dev"
    echo ""
    echo "  3. フロントエンドアプリを起動:"
    echo "     # Terminal 2"
    echo "     cd ${worktree_path}/frontend/user-app && npm run dev"
    echo ""
    echo "     # Terminal 3"
    echo "     cd ${worktree_path}/frontend/admin-app && npm run dev"
    echo ""
    echo "========================================="
}

# ============================================
# メイン処理
# ============================================
main() {
    # ヘルプ表示
    if [[ $# -eq 0 ]] || [[ "$1" == "help" ]] || [[ "$1" == "--help" ]] || [[ "$1" == "-h" ]]; then
        show_help
        exit 0
    fi

    # 入力検証
    validate_input "$@"
    local branch_name="$1"
    local from_ref="${2:-}"

    # port-manager.sh存在確認
    if [[ ! -x "${PORT_MANAGER}" ]]; then
        error "port-manager.shが見つかりません: ${PORT_MANAGER}"
    fi

    # 1. 次に利用可能なWorktree IDを取得
    echo "🔍 次に利用可能なWorktree IDを取得しています..." >&2
    local worktree_id
    if ! worktree_id=$("${PORT_MANAGER}" next-id); then
        error "Worktree IDの取得に失敗しました"
    fi
    echo "✅ Worktree ID: ${worktree_id}" >&2

    # 2. ポート番号を計算
    echo "" >&2
    echo "🔢 ポート番号を計算しています..." >&2
    local ports_json
    if ! ports_json=$("${PORT_MANAGER}" calculate-ports "${worktree_id}"); then
        error "ポート番号の計算に失敗しました"
    fi
    echo "✅ ポート番号計算完了" >&2

    # 3. Worktree作成
    echo "" >&2
    local worktree_path
    if ! worktree_path=$(create_worktree "${branch_name}" "${worktree_id}" "${from_ref}"); then
        error "Worktree作成に失敗しました"
    fi

    # 4. 環境変数ファイル生成
    if ! generate_env_file "${worktree_path}" "${worktree_id}" "${ports_json}"; then
        error "環境変数ファイル生成に失敗しました"
    fi

    # 5. 依存関係インストール
    if ! install_dependencies "${worktree_path}"; then
        # エラーでも続行 (警告のみ)
        echo "⚠️  警告: 依存関係のインストール中に問題が発生しましたが、セットアップを続行します"
    fi

    # 6. セットアップ完了メッセージ
    show_completion_message "${worktree_path}" "${worktree_id}" "${ports_json}"
}

main "$@"

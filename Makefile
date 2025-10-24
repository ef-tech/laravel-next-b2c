# Laravel + Pest テストワークフロー Makefile
# 使用方法: make [target]

LARAVEL_DIR := backend/laravel-api
SCRIPTS_DIR := scripts

.PHONY: help test test-sqlite test-pgsql test-parallel test-coverage
.PHONY: test-setup test-cleanup test-switch-sqlite test-switch-pgsql
.PHONY: test-all test-all-pgsql test-backend-only test-frontend-only test-e2e-only
.PHONY: test-with-coverage test-pr test-smoke test-diagnose
.PHONY: docker-up docker-down docker-logs
.PHONY: setup setup-ci setup-from
.PHONY: dev dev-docker dev-native dev-api dev-frontend dev-infra dev-minimal dev-stop dev-env

# デフォルトターゲット
help: ## ヘルプを表示
	@echo "Laravel + Next.js モノレポ Makefile"
	@echo ""
	@echo "利用可能なコマンド:"
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "  \033[36m%-20s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)

# =============================================================================
# 環境セットアップコマンド
# =============================================================================

setup: ## 開発環境一括セットアップ（15分以内）
	@./$(SCRIPTS_DIR)/setup/main.sh

setup-ci: ## CI/CD用セットアップ（対話的プロンプトなし）
	@./$(SCRIPTS_DIR)/setup/main.sh --ci

setup-from: ## 部分的再実行（例: make setup-from STEP=install_dependencies）
	@./$(SCRIPTS_DIR)/setup/main.sh --from $(STEP)

# =============================================================================
# テスト実行コマンド
# =============================================================================

test: ## デフォルトテスト実行（SQLite）
	cd $(LARAVEL_DIR) && ./vendor/bin/pest

quick-test: ## SQLite高速テスト（開発用）
	cd $(LARAVEL_DIR) && ./vendor/bin/pest

test-pgsql: ## PostgreSQL本番同等テスト
	@echo "🐳 Docker環境を確認中..."
	@docker compose ps pgsql | grep -q "Up" || (echo "❌ PostgreSQLが起動していません。'make docker-up' を実行してください。" && exit 1)
	./$(SCRIPTS_DIR)/switch-test-env.sh pgsql
	cd $(LARAVEL_DIR) && ./vendor/bin/pest

test-parallel: ## 並列テスト実行（セットアップ→実行→クリーンアップ）
	./$(SCRIPTS_DIR)/parallel-test-setup.sh 4
	cd $(LARAVEL_DIR) && ./vendor/bin/pest --parallel
	./$(SCRIPTS_DIR)/parallel-test-cleanup.sh 4

test-coverage: ## カバレッジ付きテスト実行
	cd $(LARAVEL_DIR) && XDEBUG_MODE=coverage ./vendor/bin/pest --coverage --min=85

test-watch: ## テストファイル監視実行（開発用）
	cd $(LARAVEL_DIR) && ./vendor/bin/pest --watch

# =============================================================================
# テスト環境セットアップ
# =============================================================================

test-setup: ## PostgreSQL並列テスト環境セットアップ
	./$(SCRIPTS_DIR)/parallel-test-setup.sh

test-cleanup: ## PostgreSQL並列テスト環境クリーンアップ
	./$(SCRIPTS_DIR)/parallel-test-cleanup.sh

test-switch-sqlite: ## テスト環境をSQLiteに切り替え
	./$(SCRIPTS_DIR)/switch-test-env.sh sqlite

test-switch-pgsql: ## テスト環境をPostgreSQLに切り替え
	./$(SCRIPTS_DIR)/switch-test-env.sh pgsql

test-db-check: ## テスト用データベース存在確認
	./$(SCRIPTS_DIR)/check-test-db.sh

# =============================================================================
# Docker管理コマンド
# =============================================================================

docker-up: ## Docker環境起動（PostgreSQL + Redis）
	docker compose up -d pgsql redis

docker-down: ## Docker環境停止
	docker compose down

docker-logs: ## PostgreSQLログ確認
	docker compose logs -f pgsql

docker-reset: ## Docker環境リセット
	docker compose down -v
	docker compose up -d pgsql redis

# =============================================================================
# 品質管理コマンド
# =============================================================================

lint: ## コード品質チェック（Pint + Larastan）
	cd $(LARAVEL_DIR) && ./vendor/bin/pint --test
	cd $(LARAVEL_DIR) && ./vendor/bin/phpstan analyse

lint-fix: ## コードスタイル自動修正（Pint）
	cd $(LARAVEL_DIR) && ./vendor/bin/pint

# =============================================================================
# テスト実行コマンド（新規統合スクリプト）
# =============================================================================

test-all: ## 全テストスイート実行（SQLite高速モード）
	@bash $(SCRIPTS_DIR)/test/main.sh --fast

test-all-pgsql: ## 全テストスイート実行（PostgreSQL並列モード）
	@bash $(SCRIPTS_DIR)/test/main.sh --env postgres --parallel 4

test-backend-only: ## バックエンドテストのみ実行
	@bash $(SCRIPTS_DIR)/test/main.sh --suite backend

test-frontend-only: ## フロントエンドテストのみ実行
	@bash $(SCRIPTS_DIR)/test/main.sh --suite frontend

test-e2e-only: ## E2Eテストのみ実行
	@bash $(SCRIPTS_DIR)/test/main.sh --suite e2e

test-with-coverage: ## カバレッジ付き全テスト実行（PostgreSQL）
	@bash $(SCRIPTS_DIR)/test/main.sh --env postgres --coverage --report

test-pr: ## PR前推奨テスト（Lint + PostgreSQL + カバレッジ）
	@echo "🔥 PR前チェックを実行します..."
	$(MAKE) lint-fix
	@bash $(SCRIPTS_DIR)/test/main.sh --env postgres --coverage --report
	@echo "✅ PR前チェック完了！"

test-smoke: ## スモークテスト（高速ヘルスチェック）
	@echo "🚬 スモークテスト実行中..."
	@bash $(SCRIPTS_DIR)/test/main.sh --fast --suite backend
	@echo "✅ スモークテスト完了！"

test-diagnose: ## テスト環境診断（未実装: Phase 5で実装予定）
	@echo "🏥 テスト環境診断..."
	@echo "⚠️  診断スクリプトは Phase 5 で実装予定です"

# =============================================================================
# 統合ワークフロー
# =============================================================================

ci-test: ## CI/CD相当の完全テスト（PostgreSQL並列実行+カバレッジ）
	@echo "🚀 CI/CD相当のテスト実行を開始します..."
	@echo "1️⃣ PostgreSQL環境に切り替え..."
	$(MAKE) test-switch-pgsql
	@echo "2️⃣ 並列テスト実行..."
	$(MAKE) test-parallel
	@echo "3️⃣ カバレッジチェック..."
	$(MAKE) test-coverage
	@echo "✅ すべてのテストが完了しました！"

full-test: ## フルテスト（PR前推奨）
	@echo "🔥 フルテストを実行します..."
	$(MAKE) lint-fix
	$(MAKE) test-pgsql
	$(MAKE) test-coverage

# =============================================================================
# ヘルスチェック
# =============================================================================

health: ## 環境ヘルスチェック
	@echo "🏥 環境ヘルスチェック実行中..."
	@echo ""
	@echo "📋 Docker環境:"
	@docker compose ps pgsql redis || echo "  ❌ Docker環境が起動していません"
	@echo ""
	@echo "📋 Laravel設定:"
	@cd $(LARAVEL_DIR) && php artisan --version
	@echo ""
	@echo "📋 テスト環境:"
	@cd $(LARAVEL_DIR) && ./vendor/bin/pest --version
	@echo ""
	@echo "📋 データベース接続:"
	@cd $(LARAVEL_DIR) && php artisan migrate:status 2>/dev/null | head -5 || echo "  ⚠️ データベース接続エラー"
	@echo ""
	@echo "✅ ヘルスチェック完了"

# =============================================================================
# 開発サーバー起動コマンド
# =============================================================================

dev: ## 開発サーバー起動（ハイブリッドモード: インフラDocker、アプリネイティブ）
	@./scripts/dev/main.sh --mode hybrid --profile full

dev-docker: ## 開発サーバー起動（Dockerモード: 全サービスDocker）
	@./scripts/dev/main.sh --mode docker --profile full

dev-native: ## 開発サーバー起動（ネイティブモード: 全サービスネイティブ）
	@./scripts/dev/main.sh --mode native --profile full

dev-api: ## 開発サーバー起動（APIのみ）
	@./scripts/dev/main.sh --mode hybrid --profile api-only

dev-frontend: ## 開発サーバー起動（フロントエンドのみ）
	@./scripts/dev/main.sh --mode hybrid --profile frontend-only

dev-infra: ## 開発サーバー起動（インフラのみ）
	@./scripts/dev/main.sh --mode docker --profile infra-only

dev-minimal: ## 開発サーバー起動（最小構成: API + フロントエンド1つ）
	@./scripts/dev/main.sh --mode hybrid --profile minimal

dev-stop: ## 開発サーバー停止
	@./scripts/dev/process-manager.sh stop || true
	@docker compose down

# =============================================================================
# 開発者用クイックコマンド
# =============================================================================

dev-env: ## 開発環境スタート（Docker起動 + SQLite設定）
	$(MAKE) docker-up
	$(MAKE) test-switch-sqlite
	@echo "✅ 開発環境の準備が完了しました！"
	@echo "   テスト実行: make test"

prod-test: ## 本番同等テスト環境（PostgreSQL設定）
	$(MAKE) docker-up
	$(MAKE) test-switch-pgsql
	@echo "✅ 本番同等テスト環境の準備が完了しました！"
	@echo "   テスト実行: make test-pgsql"
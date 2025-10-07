# Laravel + Pest テストワークフロー Makefile
# 使用方法: make [target]

LARAVEL_DIR := backend/laravel-api
SCRIPTS_DIR := scripts

.PHONY: help test test-sqlite test-pgsql test-parallel test-coverage
.PHONY: test-setup test-cleanup test-switch-sqlite test-switch-pgsql
.PHONY: docker-up docker-down docker-logs

# デフォルトターゲット
help: ## ヘルプを表示
	@echo "Laravel テストワークフロー Makefile"
	@echo ""
	@echo "利用可能なコマンド:"
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "  \033[36m%-20s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)

# =============================================================================
# テスト実行コマンド
# =============================================================================

test: ## デフォルトテスト実行（SQLite）
	cd $(LARAVEL_DIR) && ./vendor/bin/pest

test-sqlite: ## SQLiteでテスト実行（高速）
	cd $(LARAVEL_DIR) && DB_CONNECTION=sqlite DB_DATABASE=:memory: ./vendor/bin/pest

test-pgsql: ## PostgreSQLでテスト実行（本番同等）
	@echo "🐳 Docker環境を確認中..."
	@docker compose ps pgsql | grep -q "Up" || (echo "❌ PostgreSQLが起動していません。'make docker-up' を実行してください。" && exit 1)
	cd $(LARAVEL_DIR) && \
		DB_CONNECTION=pgsql \
		DB_HOST=127.0.0.1 \
		DB_PORT=13432 \
		DB_DATABASE=testing \
		DB_USERNAME=sail \
		DB_PASSWORD=password \
		./vendor/bin/pest

test-parallel: ## 並列テスト実行
	cd $(LARAVEL_DIR) && ./vendor/bin/pest --parallel

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
# 統合ワークフロー
# =============================================================================

ci-test: ## CI/CD相当のテスト実行
	@echo "🚀 CI/CD相当のテスト実行を開始します..."
	@echo "1️⃣ コード品質チェック..."
	$(MAKE) lint
	@echo "2️⃣ SQLiteテスト実行..."
	$(MAKE) test-sqlite
	@echo "3️⃣ PostgreSQLテスト実行..."
	$(MAKE) test-pgsql
	@echo "4️⃣ 並列テスト実行..."
	$(MAKE) test-parallel
	@echo "5️⃣ カバレッジチェック..."
	$(MAKE) test-coverage
	@echo "✅ すべてのテストが完了しました！"

quick-test: ## 高速テスト（開発用）
	@echo "⚡ 高速テストを実行します..."
	$(MAKE) test-sqlite

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
# 開発者用クイックコマンド
# =============================================================================

dev: ## 開発環境スタート（Docker起動 + SQLite設定）
	$(MAKE) docker-up
	$(MAKE) test-switch-sqlite
	@echo "✅ 開発環境の準備が完了しました！"
	@echo "   テスト実行: make test"

prod-test: ## 本番同等テスト環境（PostgreSQL設定）
	$(MAKE) docker-up
	$(MAKE) test-switch-pgsql
	@echo "✅ 本番同等テスト環境の準備が完了しました！"
	@echo "   テスト実行: make test-pgsql"
# Laravel + Next.js モノレポ Makefile
# 使用方法: make [target]

# =============================================================================
# 変数定義
# =============================================================================
LARAVEL_DIR := backend/laravel-api
SCRIPTS_DIR := scripts

# =============================================================================
# .PHONY宣言
# =============================================================================
.PHONY: help
.PHONY: setup setup-ci setup-from
.PHONY: dev stop clean logs ps dev-env
.PHONY: test test-pgsql test-parallel test-coverage test-watch
.PHONY: test-setup test-cleanup test-switch-sqlite test-switch-pgsql test-db-check
.PHONY: test-all test-all-pgsql test-backend-only test-frontend-only test-e2e-only
.PHONY: test-with-coverage test-pr test-smoke test-diagnose ci-test full-test
.PHONY: docker-up docker-down docker-logs docker-reset
.PHONY: lint lint-fix health
.PHONY: validate-i18n test-i18n
.PHONY: worktree-create worktree-list worktree-ports worktree-remove

# =============================================================================
# デフォルトターゲット
# =============================================================================
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

test: ## デフォルトテスト実行（SQLite高速モード）
	@cd $(LARAVEL_DIR) && ./vendor/bin/pest

test-pgsql: ## PostgreSQL本番同等テスト
	@echo "🐳 Docker環境を確認中..."
	@docker compose ps pgsql | grep -q "Up" || (echo "❌ PostgreSQLが起動していません。'make docker-up' を実行してください。" && exit 1)
	@./$(SCRIPTS_DIR)/switch-test-env.sh pgsql
	@cd $(LARAVEL_DIR) && ./vendor/bin/pest

test-parallel: ## 並列テスト実行（PostgreSQL + 4並列）
	@./$(SCRIPTS_DIR)/parallel-test-setup.sh 4
	@cd $(LARAVEL_DIR) && ./vendor/bin/pest --parallel
	@./$(SCRIPTS_DIR)/parallel-test-cleanup.sh 4

test-coverage: ## カバレッジ付きテスト実行（85%以上必須）
	@cd $(LARAVEL_DIR) && XDEBUG_MODE=coverage ./vendor/bin/pest --coverage --min=85

test-watch: ## テストファイル監視実行（開発用）
	@cd $(LARAVEL_DIR) && ./vendor/bin/pest --watch

# =============================================================================
# テスト環境セットアップ
# =============================================================================

test-setup: ## PostgreSQL並列テスト環境セットアップ
	@./$(SCRIPTS_DIR)/parallel-test-setup.sh

test-cleanup: ## PostgreSQL並列テスト環境クリーンアップ
	@./$(SCRIPTS_DIR)/parallel-test-cleanup.sh

test-switch-sqlite: ## テスト環境をSQLiteに切り替え
	@./$(SCRIPTS_DIR)/switch-test-env.sh sqlite

test-switch-pgsql: ## テスト環境をPostgreSQLに切り替え
	@./$(SCRIPTS_DIR)/switch-test-env.sh pgsql

test-db-check: ## テスト用データベース存在確認
	@./$(SCRIPTS_DIR)/check-test-db.sh

# =============================================================================
# Docker管理コマンド
# =============================================================================

docker-up: ## Docker環境起動（PostgreSQL + Redis）
	@docker compose up -d pgsql redis

docker-down: ## Docker環境停止
	@docker compose down

docker-logs: ## PostgreSQLログ確認
	@docker compose logs -f pgsql

docker-reset: ## Docker環境リセット（ボリューム削除）
	@docker compose down -v
	@docker compose up -d pgsql redis

# =============================================================================
# 品質管理コマンド
# =============================================================================

lint: ## コード品質チェック（Pint + Larastan）
	@cd $(LARAVEL_DIR) && ./vendor/bin/pint --test
	@cd $(LARAVEL_DIR) && ./vendor/bin/phpstan analyse

lint-fix: ## コードスタイル自動修正（Pint）
	@cd $(LARAVEL_DIR) && ./vendor/bin/pint

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

test-diagnose: ## テスト環境診断（ポート・環境変数・Docker・DB・ディスク・メモリ確認）
	@echo "🏥 テスト環境診断..."
	@./scripts/test/diagnose.sh

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
# 開発サーバー起動コマンド（シンプル版）
# =============================================================================

dev: ## Dockerサービス起動（Laravel API + Infra）
	@echo "🚀 Dockerサービスを起動中..."
	@docker compose --profile api --profile infra up -d
	@echo "✅ Dockerサービス起動完了！"
	@echo ""
	@echo "📝 次のステップ:"
	@echo "  Terminal 2: cd frontend/admin-app && npm run dev"
	@echo "  Terminal 3: cd frontend/user-app && npm run dev"
	@echo ""
	@echo "🌐 アクセスURL:"
	@echo "  Laravel API: http://localhost:13000"
	@echo "  Admin App:   http://localhost:13002"
	@echo "  User App:    http://localhost:13001"

stop: ## Dockerサービス停止
	@echo "🛑 Dockerサービスを停止中..."
	@docker compose stop
	@echo "✅ Dockerサービス停止完了！"

clean: ## Dockerコンテナ・ボリューム完全削除
	@echo "🧹 Dockerコンテナ・ボリュームを削除中..."
	@docker compose down -v
	@echo "✅ クリーンアップ完了！"

logs: ## Dockerサービスログ表示
	@docker compose logs -f

ps: ## Dockerサービス状態表示
	@docker compose ps

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

# =============================================================================
# i18n検証・テストコマンド
# =============================================================================

validate-i18n: ## 翻訳ファイル検証（構造・キー整合性チェック）
	@echo "🌍 翻訳ファイル検証を開始します..."
	@echo ""
	@echo "1️⃣ 翻訳ファイル構造検証（validate-i18n-messages.js）..."
	@npm run validate:i18n-messages
	@echo ""
	@echo "2️⃣ 翻訳ファイルキー整合性検証（validate-i18n-keys.js）..."
	@npm run validate:i18n-keys
	@echo ""
	@echo "✅ 翻訳ファイル検証完了！"

test-i18n: ## i18n関連テスト実行（Unit + Component + E2E）
	@echo "🧪 i18n関連テスト実行を開始します..."
	@echo ""
	@echo "1️⃣ NetworkError Unit Tests..."
	@npm test -- NetworkError.test --watchAll=false
	@echo ""
	@echo "2️⃣ Error Boundary Component Tests..."
	@npm test -- error.test --watchAll=false
	@echo ""
	@echo "3️⃣ Global Error Boundary Component Tests..."
	@npm test -- global-error.test --watchAll=false
	@echo ""
	@echo "4️⃣ i18n E2E Tests..."
	@cd e2e && npx playwright test i18n-locale-detection.spec.ts error-message-i18n.spec.ts
	@echo ""
	@echo "5️⃣ カバレッジレポート生成..."
	@npm run test:coverage
	@echo ""
	@echo "✅ i18n関連テスト実行完了！"

# =============================================================================
# Git Worktree並列開発コマンド
# =============================================================================

worktree-create: ## Git Worktree作成 (例: make worktree-create BRANCH=feature/new-feature [FROM=origin/main])
	@if [ -z "$(BRANCH)" ]; then \
		echo "❌ エラー: BRANCH引数が必要です"; \
		echo "使用例:"; \
		echo "  make worktree-create BRANCH=feature/new-feature"; \
		echo "  make worktree-create BRANCH=feature/new-feature FROM=origin/main"; \
		exit 1; \
	fi
	@if [ -n "$(FROM)" ]; then \
		./$(SCRIPTS_DIR)/worktree/setup.sh $(BRANCH) $(FROM); \
	else \
		./$(SCRIPTS_DIR)/worktree/setup.sh $(BRANCH); \
	fi

worktree-list: ## Git Worktree一覧表示
	@echo "📋 Git Worktree一覧:"
	@echo ""
	@git worktree list

worktree-ports: ## Git Worktreeポート番号一覧表示
	@./$(SCRIPTS_DIR)/worktree/port-manager.sh list

worktree-remove: ## Git Worktree削除 (例: make worktree-remove PATH=../laravel-next-b2c-wt0)
	@if [ -z "$(PATH)" ]; then \
		echo "❌ エラー: PATH引数が必要です"; \
		echo "使用例: make worktree-remove PATH=../laravel-next-b2c-wt0"; \
		exit 1; \
	fi
	@echo "🗑️  Worktreeを削除しています: $(PATH)"
	@git worktree remove $(PATH)
	@echo "✅ Worktree削除完了"

worktree-clean: ## Git Worktree完全削除 (Docker + Worktree) (例: make worktree-clean ID=0 または ID=../laravel-next-b2c-wt0)
	@if [ -z "$(ID)" ]; then \
		echo "❌ エラー: ID引数が必要です"; \
		echo "使用例:"; \
		echo "  make worktree-clean ID=0"; \
		echo "  make worktree-clean ID=../laravel-next-b2c-wt0"; \
		exit 1; \
	fi
	@./$(SCRIPTS_DIR)/worktree/cleanup.sh $(ID)
# Project Structure

## ルートディレクトリ構成
```
laravel-next-b2c/
├── backend/             # バックエンドAPI層
│   └── laravel-api/     # Laravel APIアプリケーション
├── frontend/            # フロントエンド層
│   ├── admin-app/       # 管理者向けアプリケーション
│   └── user-app/        # エンドユーザー向けアプリケーション
├── e2e/                 # E2Eテスト環境 (Playwright)
├── .github/             # GitHub設定
│   └── workflows/       # GitHub Actionsワークフロー (CI/CD) - 発火タイミング最適化済み
│       ├── e2e-tests.yml          # E2Eテスト（4 Shard並列、Concurrency + Paths最適化）
│       ├── frontend-test.yml      # フロントエンドテスト（Jest/Testing Library + TypeScript型チェック + Next.js本番ビルド検証、API契約監視含む）
│       ├── php-quality.yml        # PHP品質チェック（Pint + Larastan）
│       └── test.yml               # PHPテスト（Pest 4、Composerキャッシュ最適化）
├── .claude/             # Claude Code設定・コマンド
├── .kiro/               # Kiro仕様駆動開発設定
├── .husky/              # Gitフック管理 (husky設定)
├── .idea/               # IntelliJ IDEA設定 (IDE固有、gitignore済み)
├── .git/                # Gitリポジトリ
├── docker-compose.yml   # Docker Compose統合設定（全サービス一括起動、ヘルスチェック統合、プロジェクト固有イメージ命名）
│                        # Laravel APIヘルスチェック: curl http://127.0.0.1:${APP_PORT}/api/health (動的ポート対応)
├── .dockerignore        # Dockerビルド除外設定（モノレポ対応）
├── .gitignore           # 統合ファイル除外設定 (モノレポ対応)
├── Makefile             # プロジェクト管理タスク
│                        # ⚡ セットアップコマンド:
│                        #   - make setup: 一括セットアップ実行（15分以内）
│                        #   - make setup-from STEP=...: 指定ステップから再実行
│                        #     ステップ: check_prerequisites, setup_env, install_dependencies, start_services, verify_setup
│                        # 🚀 開発サーバー起動コマンド（日常開発、3ターミナル方式）:
│                        #   - make dev: Dockerサービス起動（Laravel API + PostgreSQL + Redis等）
│                        #   - make stop: Dockerサービス停止
│                        #   - make clean: Dockerコンテナ・ボリューム完全削除
│                        #   - make logs: Dockerサービスログ表示
│                        #   - make ps: Dockerサービス状態表示
│                        #   - make help: 利用可能コマンド一覧表示
│                        # ⚠️ Next.jsアプリはネイティブ起動推奨:
│                        #   - Terminal 2: cd frontend/admin-app && npm run dev
│                        #   - Terminal 3: cd frontend/user-app && npm run dev
│                        # 🧪 テスト実行統合コマンド（全テストスイート）:
│                        #   - make test-all: 全テスト実行（SQLite、約30秒）
│                        #   - make test-all-pgsql: 全テスト実行（PostgreSQL並列、約5-10分）
│                        #   - make test-backend-only: バックエンドテストのみ（約2秒）
│                        #   - make test-frontend-only: フロントエンドテストのみ（約15秒）
│                        #   - make test-e2e-only: E2Eテストのみ（約2-5分）
│                        #   - make test-pr: Lint + PostgreSQL + カバレッジ（約3-5分、PR前推奨）
│                        #   - make test-smoke: スモークテスト（高速ヘルスチェック、約5秒）
│                        #   - make test-diagnose: テスト環境診断（ポート・環境変数・Docker・DB等確認）
│                        # 🧪 テストインフラ管理タスク（バックエンドテストDB）:
│                        #   - make quick-test, test-pgsql, test-parallel, test-setup, etc.
├── package.json         # モノレポルート設定 (ワークスペース管理、共通スクリプト)
├── node_modules/        # 共通依存関係
├── docs/                # 📝 プロジェクトドキュメント（フロントエンドテストコードESLint、CORS設定、セキュリティヘッダー、テスト運用）
│   ├── JEST_ESLINT_INTEGRATION_GUIDE.md  # Jest/Testing Library ESLint統合ガイド（設定概要、プラグイン詳細、適用ルール）
│   ├── JEST_ESLINT_QUICKSTART.md         # Jest/ESLintクイックスタートガイド（5分セットアップ、基本ワークフロー）
│   ├── JEST_ESLINT_TROUBLESHOOTING.md    # Jest/ESLintトラブルシューティング（設定問題、実行エラー、ルール調整）
│   ├── JEST_ESLINT_CONFIG_EXAMPLES.md    # Jest/ESLint設定サンプル集（プロジェクト別設定例、カスタマイズパターン）
│   ├── CORS_CONFIGURATION_GUIDE.md       # 🌐 CORS環境変数設定完全ガイド（環境別設定、セキュリティベストプラクティス、トラブルシューティング）
│   ├── SECURITY_HEADERS_OPERATION.md     # 🔐 セキュリティヘッダー運用マニュアル（日常運用、Report-Onlyモード運用、Enforceモード切り替え）
│   ├── SECURITY_HEADERS_TROUBLESHOOTING.md  # 🔐 セキュリティヘッダートラブルシューティング（CSP違反デバッグ、CORSエラー対処）
│   ├── CSP_DEPLOYMENT_CHECKLIST.md       # 🔐 CSP本番デプロイチェックリスト（段階的導入フローガイド）
│   ├── TESTING_DATABASE_WORKFLOW.md      # テストDB運用ワークフローガイド（SQLite/PostgreSQL切り替え、並列テスト実行）
│   ├── TESTING_EXECUTION_GUIDE.md        # 🧪 テスト実行ガイド（全テストスイート実行方法、クイックスタート、ローカル/CI/CD環境テスト実行、診断スクリプト）
│   └── TESTING_TROUBLESHOOTING_EXTENDED.md  # 🧪 テストトラブルシューティング拡張版（よくある問題と解決策、ログ分析方法、エスカレーション手順）
├── scripts/             # プロジェクトスクリプト
│   ├── test/                             # 🧪 テスト実行スクリプト（Phase 1-7完了、60サブタスク完了）
│   │   ├── run-all-tests.sh              # 統合オーケストレーションスクリプト（全テストスイート統括）
│   │   ├── run-backend-tests.sh          # バックエンドテスト実行（Pest 4、SQLite/PostgreSQL切替）
│   │   ├── run-frontend-tests.sh         # フロントエンドテスト実行（Jest 29、2アプリ並列、JUnit XML出力）
│   │   ├── run-e2e-tests.sh              # E2Eテスト実行（Playwright 4 Shard並列、JUnit XML出力）
│   │   ├── generate-test-report.sh       # テストレポート生成（JUnit XML統合、カバレッジ集約、サマリー出力）
│   │   └── diagnose-test-env.sh          # テスト環境診断（ポート・環境変数・Docker・DB・ディスク・メモリ確認）
│   ├── analyze-csp-violations.sh         # 🔐 CSP違反ログ分析スクリプト
│   ├── validate-security-headers.sh      # 🔐 セキュリティヘッダー検証スクリプト（Laravel/Next.js対応）
│   ├── validate-cors-config.sh           # 🌐 CORS設定整合性確認スクリプト
│   ├── generate-error-types.js           # 🎯 エラー型定義自動生成スクリプト（Laravel Enum → TypeScript型定義）
│   │   # - Laravel app/Enums/ErrorCode.phpを解析
│   │   # - frontend/types/errors.ts を自動生成
│   │   # - ErrorCode Enum/Union型定義
│   │   # - RFC 7807準拠エラーレスポンス型
│   │   # - tryFrom()メソッド対応
│   ├── verify-error-types.sh             # 🎯 エラー型定義検証スクリプト（Laravel/TypeScript型整合性チェック）
│   ├── validate-error-codes.js           # 🎯 エラーコード検証スクリプト（重複チェック、命名規約検証）
│   └── setup/                            # ⚡ セットアップスクリプト（make setup実装）
│       └── setup-project.sh              # プロジェクト一括セットアップスクリプト（15分以内環境構築）
│   # ⚠️ 注記: scripts/dev/ ディレクトリは削除されました
│   # - 理由: TypeScript/Bash混在の複雑な構成で保守が困難
│   # - 代替: シンプルな3ターミナル起動方式（README.md「🚀 開発環境起動（日常開発）」参照）
│   # - Terminal 1: make dev（Docker起動）
│   # - Terminal 2/3: npm run dev（Next.jsネイティブ起動）
├── CLAUDE.md            # プロジェクト開発ガイドライン
├── README.md            # プロジェクト概要
├── SECURITY_HEADERS_IMPLEMENTATION_GUIDE.md  # 🔐 セキュリティヘッダー実装ガイド（Laravel/Next.js実装手順、環境変数設定、CSPカスタマイズ）
└── DOCKER_TROUBLESHOOTING.md  # Dockerトラブルシューティング完全ガイド（APP_PORTポート設定問題、イメージ再ビルド、完全クリーンアップ手順）
```

## バックエンド構造 (`backend/laravel-api/`)
### 🏗️ DDD/クリーンアーキテクチャ + Laravel標準構成
```
laravel-api/
├── ddd/                 # 🏗️ DDD/クリーンアーキテクチャ層 (新規)
│   ├── Domain/          # Domain層（ビジネスロジック中核）
│   │   └── User/        # ユーザー集約
│   │       ├── Entities/           # エンティティ（User.php）
│   │       ├── ValueObjects/       # 値オブジェクト（Email.php, UserId.php）
│   │       ├── Repositories/       # Repositoryインターフェース（UserRepositoryInterface.php）
│   │       ├── Events/             # ドメインイベント（UserRegistered.php）
│   │       ├── Services/           # ドメインサービス
│   │       └── Exceptions/         # ドメイン例外
│   ├── Application/     # Application層（ユースケース）
│   │   ├── User/        # ユーザーユースケース
│   │   │   ├── UseCases/           # ユースケース（RegisterUserUseCase.php）
│   │   │   ├── DTOs/               # データ転送オブジェクト（RegisterUserInput.php, RegisterUserOutput.php）
│   │   │   ├── Services/           # アプリケーションサービスインターフェース（TransactionManager.php, EventBus.php）
│   │   │   ├── Queries/            # クエリインターフェース（UserQueryInterface.php）
│   │   │   └── Exceptions/         # アプリケーション例外
│   │   └── Middleware/  # 🛡️ ミドルウェア設定（DDD統合、完全実装済み）
│   │       └── Config/  # ミドルウェアグループ設定
│   │           ├── MiddlewareGroupsConfig.php  # グループ定義（api/auth/public）
│   │           └── RateLimitConfig.php         # APIレート制限設定（エンドポイント分類細分化、フェイルオーバー対応）
│   │               # - エンドポイント分類: 認証API（5回/分）、書き込みAPI（10回/分）、読み取りAPI（60回/分）、管理者API（100回/分）
│   │               # - Redis障害時フェイルオーバー: RATELIMIT_CACHE_STORE環境変数でRedis/Array切替
│   │               # - キャッシュ競合対策: Cache::increment() + Cache::add()アトミック操作
│   │               # - retry_after最適化: 負の値問題修正、resetAt計算改善
│   └── Infrastructure/  # Infrastructure層（外部システム実装）
│       └── Persistence/ # 永続化実装
│           ├── Eloquent/           # Eloquent Repository実装（EloquentUserRepository.php）
│           ├── Query/              # Query実装（EloquentUserQuery.php）
│           └── Services/           # サービス実装（LaravelTransactionManager.php, LaravelEventBus.php）
├── app/                 # Laravel標準アプリケーション層（既存MVC共存）
│   ├── Console/         # Artisanコマンド
│   │   └── Commands/    # カスタムコマンド
│   │       └── PruneExpiredTokens.php  # 🔐 期限切れトークン削除コマンド（tokens:prune）
│   ├── Enums/           # Enumクラス（PHP 8.1+）
│   │   └── ErrorCode.php  # 🎯 エラーコード定義（型安全、TypeScript自動生成対象）
│   │       # - Enum定義: VALIDATION_ERROR, AUTHENTICATION_FAILED等
│   │       # - getType()メソッド: RFC 7807 type URI生成（単一ソース化、2025-11-19実装完了）
│   │       # - tryFrom()メソッド: 型安全な変換処理
│   │       # - Laravel/フロントエンド同期保証
│   ├── Http/            # 🏗️ HTTP層（DDD統合）
│   │   ├── Controllers/ # Controllerからユースケース呼び出し
│   │   │   ├── Api/     # 📊 API基本機能コントローラー（レガシー、非推奨）
│   │   │   │   ├── HealthController.php  # ヘルスチェック（GET /api/health、非推奨 → V1使用推奨）
│   │   │   │   └── CspReportController.php  # 🔐 CSP違反レポート収集（POST /api/csp-report、非推奨 → V1使用推奨）
│   │   │   ├── Api/V1/  # 🔢 V1 APIコントローラー（推奨）
│   │   │   │   ├── Auth/    # 🔐 V1認証コントローラー
│   │   │   │   │   ├── LoginController.php     # ログイン処理（POST /api/v1/login, POST /api/v1/logout）
│   │   │   │   │   ├── MeController.php        # 認証ユーザー情報（GET /api/v1/me）
│   │   │   │   │   └── TokenController.php     # トークン管理（GET /api/v1/tokens, POST /api/v1/tokens/{id}/revoke）
│   │   │   │   ├── HealthController.php  # V1ヘルスチェック（GET /api/v1/health）
│   │   │   │   └── CspReportController.php  # 🔐 V1 CSP違反レポート収集（POST /api/v1/csp-report）
│   │   │   ├── Auth/    # 🔐 認証コントローラー（レガシー、段階的に非推奨）
│   │   │   │   ├── LoginController.php     # ログイン処理（POST /api/login, POST /api/logout）
│   │   │   │   ├── MeController.php        # 認証ユーザー情報（GET /api/me）
│   │   │   │   └── TokenController.php     # トークン管理（GET /api/tokens, POST /api/tokens/{id}/revoke）
│   │   ├── Middleware/  # 🛡️ ミドルウェア（基本ミドルウェアスタック完全実装、145テスト成功、85%カバレッジ）
│   │   │   ├── Authenticate.php  # 🔐 Sanctum認証ミドルウェア（auth:sanctum）
│   │   │   ├── SecurityHeaders.php  # 🔐 セキュリティヘッダーミドルウェア（X-Frame-Options、X-Content-Type-Options等）
│   │   │   ├── ContentSecurityPolicy.php  # 🔐 CSPヘッダー設定ミドルウェア（動的CSP構築、Report-Only/Enforceモード切替）
│   │   │   ├── SetLocaleFromAcceptLanguage.php  # 🌍 多言語対応ミドルウェア（Accept-Language header自動検出、i18n対応）
│   │   │   ├── SetRequestId.php          # 🛡️ リクエストID付与（Laravel標準Str::uuid()、構造化ログ対応）
│   │   │   ├── LogPerformance.php        # 🛡️ パフォーマンス監視（レスポンスタイム記録）
│   │   │   ├── LogSecurity.php           # 🛡️ セキュリティログ分離（個人情報ハッシュ化対応、LOG_HASH_SENSITIVE_DATA環境変数制御）
│   │   │   ├── DynamicRateLimit.php      # 🛡️ APIレート制限強化版
│   │   │   │   # - エンドポイント分類細分化: 認証（5回/分）、書き込み（10回/分）、読み取り（60回/分）、管理者（100回/分）
│   │   │   │   # - Redis障害時フェイルオーバー: RATELIMIT_CACHE_STORE環境変数でRedis/Array切替
│   │   │   │   # - キャッシュ競合対策: Cache::increment() + Cache::add()アトミック操作
│   │   │   │   # - retry_after最適化: 負の値問題修正、resetAt計算改善
│   │   │   │   # - DDD統合: Application層RateLimitConfig参照
│   │   │   ├── IdempotencyKey.php        # 🛡️ 冪等性保証（環境変数駆動、Webhook対応、IPアドレス識別、24時間キャッシュ）
│   │   │   ├── Authorize.php             # 🛡️ ポリシーベース認可
│   │   │   ├── AuditLog.php              # 🛡️ ユーザー行動追跡
│   │   │   ├── SecurityAudit.php         # 🛡️ セキュリティイベント監査
│   │   │   ├── SetETag.php               # 🛡️ ETag自動生成（HTTP Cache-Control設定）
│   │   │   └── CheckETag.php             # 🛡️ 条件付きリクエスト対応（304 Not Modified）
│   │   ├── Requests/    # リクエストバリデーション
│   │   │   └── Auth/    # 🔐 認証リクエスト
│   │   │       └── LoginRequest.php  # ログインバリデーション（email, password必須）
│   │   ├── Resources/   # APIリソース（Presenter統一）
│   │   │   └── UserResource.php  # ユーザーAPIレスポンス
│   │   └── Presenters/  # 🎨 Presenter層（API/V1統合）
│   │       └── Api/V1/  # V1専用Presenter
│   │           ├── Auth/
│   │           │   ├── LoginPresenter.php    # ログインレスポンス統一
│   │           │   └── TokenPresenter.php    # トークン管理レスポンス統一
│   │           └── HealthPresenter.php       # ヘルスチェックレスポンス統一
│   ├── Exceptions/      # 例外ハンドラー
│   │   └── Handler.php  # 🔒 Exception Handler強化版
│   │       # - AuthenticationException: API専用JSONレスポンス、loginルートリダイレクト無効化
│   │       # - ValidationException: 422 Unprocessable Entity + JSON + errors配列
│   │       # - 統一エラーレスポンス: { "message": "...", "errors": {...} }
│   ├── Models/          # Eloquentモデル（Infrastructure層で使用）
│   │   └── User.php     # 🔐 Userモデル（HasApiTokens trait使用、password必須化対応）
│   └── Providers/       # サービスプロバイダー（DI設定含む）
├── bootstrap/           # アプリケーション初期化
├── config/              # 設定ファイル
│   ├── sanctum.php      # 🔐 Sanctum認証設定（stateful_domains, expiration等）
│   ├── auth.php         # 認証設定（guards: sanctum）
│   ├── security.php     # 🔐 セキュリティヘッダー設定（CSP、HSTS、X-Frame-Options等環境変数駆動設定）
│   ├── cors.php         # 🌐 CORS設定（fruitcake/laravel-cors統合、credentials対応）
│   └── middleware.php   # 🛡️ ミドルウェア設定（DDD Application層統合完了、エンドポイント別グループ定義）
│       # - グループ定義: api/auth/public
│       # - DDD Application層参照: MiddlewareGroupsConfig、RateLimitConfig
│       # - レート制限環境変数: RATELIMIT_CACHE_STORE、RATELIMIT_*_MAX_ATTEMPTS
│       # - Idempotency環境変数: IDEMPOTENCY_CACHE_STORE、IDEMPOTENCY_TTL
│       # - ログ個人情報配慮: LOG_HASH_SENSITIVE_DATA、LOG_SENSITIVE_FIELDS
├── lang/                # 🌍 多言語ファイル（i18n対応）
│   ├── ja/              # 日本語リソース
│   │   ├── validation.php  # バリデーションメッセージ（日本語）
│   │   ├── errors.php      # 🎯 エラーメッセージ（日本語、RFC 7807準拠）
│   │   └── auth.php        # 認証メッセージ（日本語）
│   └── en/              # 英語リソース
│       ├── validation.php  # バリデーションメッセージ（英語）
│       ├── errors.php      # 🎯 エラーメッセージ（英語、RFC 7807準拠）
│       └── auth.php        # 認証メッセージ（英語）
├── database/            # データベース関連
│   ├── factories/       # モデルファクトリー
│   ├── migrations/      # マイグレーション
│   │   ├── 0001_01_01_000000_create_users_table.php  # usersテーブル（bigint主キー、Laravel標準準拠）
│   │   └── 2019_12_14_000001_create_personal_access_tokens_table.php  # 🔐 Sanctumトークンテーブル（bigint tokenable_id）
│   └── seeders/         # シーダー
├── docker/              # Docker設定 (PHP 8.0-8.4対応、APP_PORTデフォルト13000最適化済み)
├── docs/                # 🏗️ プロジェクトドキュメント（DDD + 最適化ガイド + インフラ検証 + テストDB運用 + 認証 + セキュリティヘッダー + Docker）
│   ├── ddd-architecture.md        # DDD 4層構造アーキテクチャ概要
│   ├── ddd-development-guide.md   # DDD開発ガイドライン
│   ├── ddd-testing-strategy.md    # DDD層別テスト戦略
│   ├── ddd-troubleshooting.md     # DDDトラブルシューティング
│   ├── database-connection.md     # PostgreSQL接続設定ガイド（環境別設定・タイムアウト最適化・トラブルシューティング）
│   ├── VERIFICATION.md            # Dockerヘルスチェック検証手順ドキュメント
│   ├── TESTING_DATABASE_WORKFLOW.md  # テストDB運用ワークフローガイド（SQLite/PostgreSQL切り替え、並列テスト実行）
│   ├── sanctum-authentication-guide.md  # 🔐 Sanctum認証ガイド（エンドポイント、トークン管理、セキュリティ設定、トラブルシューティング）
│   ├── security-headers-implementation.md  # 🔐 セキュリティヘッダー実装詳細（Laravel/Next.js実装、環境変数設定、CSPカスタマイズ）
│   ├── DOCKER_TROUBLESHOOTING.md  # Dockerトラブルシューティング（APP_PORTポート設定、イメージ再ビルド、完全クリーンアップ）
│   └── [その他最適化ドキュメント]
├── public/              # 公開ディレクトリ (エントリーポイント)
├── resources/           # リソースファイル
│   ├── css/             # スタイルシート
│   ├── js/              # JavaScript/TypeScript
│   └── views/           # Bladeテンプレート
├── routes/              # ルート定義
│   ├── api.php          # API専用ルート
│   │                    # 🔢 V1 APIエンドポイント（推奨）:
│   │                    #   📊 ヘルスチェック:
│   │                    #     - GET /api/v1/health (V1\HealthController@show, ルート名: v1.health)
│   │                    #   🔐 認証エンドポイント:
│   │                    #     - POST /api/v1/login (V1\Auth\LoginController@login)
│   │                    #     - POST /api/v1/logout (V1\Auth\LoginController@logout, auth:sanctum)
│   │                    #     - GET /api/v1/me (V1\Auth\MeController@show, auth:sanctum)
│   │                    #   🔐 トークン管理エンドポイント:
│   │                    #     - GET /api/v1/tokens (V1\Auth\TokenController@index, auth:sanctum)
│   │                    #     - POST /api/v1/tokens/{id}/revoke (V1\Auth\TokenController@revoke, auth:sanctum)
│   │                    #     - POST /api/v1/tokens/refresh (V1\Auth\TokenController@refresh, auth:sanctum)
│   │                    #   🔐 セキュリティエンドポイント:
│   │                    #     - POST /api/v1/csp-report (V1\CspReportController@store, CSP違反レポート収集)
│   │                    #
│   │                    # 📊 レガシーエンドポイント（非推奨、段階的に廃止予定）:
│   │                    #   - GET /api/health (HealthController@show, ルート名: health)
│   │                    #   - POST /api/login, /api/logout, GET /api/me
│   │                    #   - GET /api/tokens, POST /api/tokens/{id}/revoke
│   │                    #   - POST /api/csp-report
│   ├── web.php          # Web画面ルート
│   └── console.php      # コンソールルート
│                        # 🔐 Scheduled Tasks:
│                        #   - tokens:prune (PruneExpiredTokens, 毎日実行)
├── storage/             # ストレージ (ログ、キャッシュ、アップロード)
├── tests/               # 🏗️ テストスイート (Pest 4 + Architecture Tests: 96.1%カバレッジ)
│   ├── Feature/         # 機能テスト（HTTP層統合テスト）
│   │   ├── Api/         # 📊 API基本機能テスト（レガシー）
│   │   │   └── HealthCheckTest.php  # ヘルスチェックエンドポイントテスト（非推奨）
│   │   ├── Api/V1/      # 🔢 V1 API機能テスト（推奨）
│   │   │   ├── Auth/    # 🔐 V1認証機能テスト
│   │   │   │   ├── LoginTest.php          # V1ログイン・ログアウトテスト（password必須化対応）
│   │   │   │   ├── TokenManagementTest.php # V1トークン管理テスト
│   │   │   │   └── MeTest.php              # V1認証ユーザー情報テスト
│   │   │   ├── HealthCheckTest.php  # V1ヘルスチェックエンドポイントテスト（JSON形式、Content-Type、ルート名検証）
│   │   │   └── CspReportTest.php    # V1 CSP違反レポートテスト（application/json互換性検証）
│   │   ├── Auth/        # 🔐 認証機能テスト（レガシー、段階的にV1移行）
│   │   │   ├── LoginTest.php          # ログイン・ログアウトテスト（12テスト、password必須化対応）
│   │   │   └── TokenManagementTest.php # トークン管理テスト（一覧取得、無効化、更新）
│   │   ├── Security/    # 🔐 セキュリティ機能テスト
│   │   │   ├── SecurityHeadersTest.php  # セキュリティヘッダーテスト（X-Frame-Options、X-Content-Type-Options等）
│   │   │   └── CspReportTest.php        # CSP違反レポートテスト（application/json互換性検証）
│   │   └── Middleware/  # 🛡️ ミドルウェア機能テスト（145テスト成功、85%カバレッジ）
│   │       ├── SetRequestIdTest.php     # リクエストID付与テスト（UUID生成、ヘッダー検証）
│   │       ├── DynamicRateLimitTest.php # APIレート制限強化テスト
│   │       │   # - エンドポイント分類テスト（認証/書き込み/読み取り/管理者API）
│   │       │   # - 環境変数駆動テスト（RATELIMIT_CACHE_STORE切替）
│   │       │   # - キャッシュストア切替テスト（Redis/Array）
│   │       │   # - retry_after計算テスト（負の値問題修正検証）
│   │       │   # - キャッシュ競合対策テスト（アトミック操作検証）
│   │       ├── IdempotencyKeyTest.php   # 冪等性保証テスト（Webhook対応、IPアドレス識別、24時間TTL）
│   │       ├── LogPerformanceTest.php   # パフォーマンス監視テスト（レスポンスタイム記録検証）
│   │       ├── LogSecurityTest.php      # セキュリティログテスト（個人情報ハッシュ化検証）
│   │       └── SetETagTest.php          # ETag生成・検証テスト（304 Not Modified検証）
│   ├── Unit/            # ユニットテスト（ドメインロジックテスト）
│   ├── Arch/            # 🏗️ Architecture Tests（依存方向検証、レイヤー分離チェック）
│   │   ├── DomainLayerTest.php         # Domain層依存チェック
│   │   ├── ApplicationLayerTest.php    # Application層依存チェック
│   │   ├── InfrastructureLayerTest.php # Infrastructure層実装チェック
│   │   ├── NamingConventionTest.php    # 命名規約検証
│   │   ├── MiddlewareGroupTest.php     # 🛡️ ミドルウェアグループ設定検証
│   │   └── ErrorHandlingTest.php       # 🎯 RFC 7807エラーハンドリング検証（2025-11-19追加、絶対パス→相対パス修正）
│   ├── Pest.php         # Pest設定・ヘルパー
│   └── TestCase.php     # 基底テストクラス
├── vendor/              # Composer依存関係
├── compose.yaml         # Docker Compose設定
├── composer.json        # PHP依存関係管理
├── package.json         # Node.js依存関係 (Vite用)
├── vite.config.js       # Vite設定
├── pint.json            # Laravel Pint設定 (コードフォーマッター)
├── phpstan.neon         # PHPStan/Larastan設定 (静的解析 Level 8)
├── phpunit.xml          # Pest設定ファイル（Pest用phpunit.xml）
└── .env                 # 環境設定
```

## フロントエンド構造
### Next.js App Router構成 (両アプリ共通)
```
{admin-app|user-app}/
├── src/                 # ソースコード
│   ├── app/             # App Router (Next.js 13+)
│   │   ├── [locale]/    # 🌍 多言語対応ルート（next-intl統合、全ページ完全統一、2025-01-13 PR #134完了）
│   │   │   ├── layout.tsx   # ロケール対応レイアウト
│   │   │   ├── page.tsx     # ホームページ
│   │   │   ├── error.tsx    # 🎯 Error Boundaries i18n実装（React 19、多言語Fallback UI、locale as string型明示化）
│   │   │   ├── global-error.tsx  # 🎯 グローバルError Boundaries i18n（NEXT_LOCALE Cookie検出、Accept-Language fallback）
│   │   │   └── [...dynamic]/  # 動的ページ（[locale]配下に統一、i18n完全対応、validLocale型統一）
│   │   ├── globals.css  # グローバルスタイル
│   │   └── actions.ts   # Server Actions
│   ├── messages/        # 🌍 多言語メッセージファイル（next-intl）
│   │   ├── ja.json      # 日本語メッセージ（エラー、UI等）
│   │   └── en.json      # 英語メッセージ（エラー、UI等）
│   ├── components/      # 再利用可能コンポーネント
│   │   └── **/*.test.tsx # コンポーネントテスト
│   ├── lib/             # ユーティリティ・ヘルパー
│   │   ├── api-client.ts  # 🎯 API通信ユーティリティ（Sanctum認証統合、401自動リダイレクト、NetworkError日本語化）
│   │   ├── error-handler.ts  # 🎯 エラーハンドリングロジック（RFC 7807準拠エラー解析、Request ID抽出）
│   │   └── **/*.test.ts  # ライブラリテスト
│   ├── hooks/           # カスタムReactフック
│   │   └── **/*.test.ts  # フックテスト
│   ├── types/           # TypeScript型定義
│   │   ├── api/         # API関連型定義
│   │   │   └── v1.ts    # 🔢 V1 API型定義（Presenter型、リクエスト型、レスポンス型）
│   │   └── errors.ts    # 🎯 エラー型定義（自動生成、ErrorCode Enum、RFC 7807準拠型）
│   │       # - Laravel Enumから自動生成（generate-error-types.js）
│   │       # - ErrorCode Enum/Union型定義
│   │       # - RFC 7807準拠エラーレスポンス型
│   │       # - 型安全なエラーコード体系
│   └── utils/           # 汎用ユーティリティ
├── public/              # 静的ファイル
├── coverage/            # テストカバレッジレポート
├── node_modules/        # Node.js依存関係
├── i18n/                # 🌍 i18n設定（next-intl）
│   └── request.ts       # next-intlリクエスト設定（ロケール検出ロジック）
├── middleware.ts        # 🔐 Next.jsミドルウェア（セキュリティヘッダー設定、i18nルーティング、環境変数駆動）
├── Dockerfile           # Next.js Dockerイメージ定義（本番ビルド最適化）
├── package.json         # フロントエンド依存関係管理（--port固定設定、next-intl含む）
├── tsconfig.json        # ✅ TypeScript設定（tsconfig.base.json継承、2025-11-13更新）
│                        # - extends: "../tsconfig.base.json"（共通設定継承）
│                        # - baseUrl: "."（明示的設定、相対パス解決の基点）
│                        # - paths: @/* と @shared/* パスエイリアス定義
│                        # - exclude: テストファイル除外（型チェック対象から除外）
├── jest.config.js       # Jest設定（プロジェクト固有）
├── tailwind.config.js   # Tailwind CSS設定
├── next.config.ts       # Next.js設定（outputFileTracingRoot設定、モノレポ対応、next-intl統合）
├── eslint.config.mjs    # ESLint 9設定 (flat config形式)
└── .env.local           # 環境変数（セキュリティヘッダー設定含む）
```

### フロントエンド共通設定 (`frontend/`)
```
frontend/
├── .eslint.base.mjs     # 共通ESLint Base設定（モノレポ統一設定）
│                        # - Next.js推奨設定（core-web-vitals, typescript）
│                        # - テストファイル専用オーバーライド設定
│                        #   - Jest推奨ルールセット（flat/recommended）
│                        #   - Testing Library推奨ルールセット（flat/react）
│                        #   - Jest-DOM推奨ルールセット（flat/recommended）
│                        # - Prettier競合ルール無効化
├── tsconfig.base.json   # ✅ 共通TypeScript設定（2025-11-13導入完了）
│                        # - 15個の共通compilerOptions集約（target、strict、jsx等）
│                        # - Next.jsプラグイン統合
│                        # - forceConsistentCasingInFileNames: true（大文字小文字一貫性チェック）
│                        # - User App/Admin Appで継承（extends: "../tsconfig.base.json"）
├── lib/                 # 🔧 フロントエンド共通ライブラリ（frontend-lib-monorepo-consolidation完了）
│   └── global-error-messages.ts  # ✅ Global Error静的辞書（共通モジュール化完了）
│                        # - User AppとAdmin Appの重複メッセージ辞書を統一
│                        # - DRY原則適用による保守性向上（~170行コード削減）
│                        # - satisfies演算子適用による型安全性強化
│                        # - 詳細JSDocコメント完備
│                        # - 4カテゴリ構造: network, boundary, validation, global
│                        # - 日本語/英語対応（ja/en）
│                        # - TypeScript型推論最適化（as const + satisfies）
│                        # - @shared/lib/global-error-messages 経由でImport可能
├── types/               # 🔧 フロントエンド共通型定義（frontend-lib-monorepo-consolidation完了）
│   ├── errors.ts        # エラー型定義（共通、自動生成対象、ErrorCode Enum）
│   │                    # - @shared/types/errors 経由でImport可能
│   ├── messages.d.ts    # メッセージ型定義（GlobalErrorMessages型、全54テストpass）
│   │                    # - @shared/types/messages 経由でImport可能
│   └── api/             # API型定義
│       └── v1.ts        # 🔢 V1 API型定義（Presenter型、リクエスト型、レスポンス型）
│                        # - @shared/types/api/v1 経由でImport可能
├── admin-app/           # 管理者向けアプリケーション
│   ├── tsconfig.json    # 🔧 TypeScript設定（@shared/*パスエイリアス設定含む）
│   │                    # - paths: { "@shared/*": ["../../../frontend/*"] }
│   │                    # - 共通ライブラリへの統一Import設定
│   └── ...
├── user-app/            # エンドユーザー向けアプリケーション
│   ├── tsconfig.json    # 🔧 TypeScript設定（@shared/*パスエイリアス設定含む）
│   │                    # - paths: { "@shared/*": ["../../../frontend/*"] }
│   │                    # - 共通ライブラリへの統一Import設定
│   └── ...
├── TESTING_GUIDE.md     # フロントエンドテストガイド
└── TESTING_TROUBLESHOOTING.md  # テストトラブルシューティング
```

**🔧 frontend-lib-monorepo-consolidation成果**:
- **@shared/*パスエイリアス実装**: TypeScript paths設定による共通モジュール参照
- **重複ファイル削除**: ~560行コード削減（User App/Admin Appの重複排除）
- **Import文統一化**: 両アプリから `@shared/lib/*`, `@shared/types/*` 経由で統一Import
- **単一ソース原則**: 変更影響範囲の最小化、型安全性維持

**Docker最適化ポイント**:
- **outputFileTracingRoot**: モノレポルート指定で依存関係トレース最適化
- **standalone出力**: 最小限ファイルセットによる軽量Dockerイメージ
- **マルチステージビルド**: builder → runner ステージ分離
- **libc6-compat**: Alpine Linux上でのNext.js互換性保証

### モノレポルート構成 (コード品質管理・テスト・Docker)
```
laravel-next-b2c/
├── docker-compose.yml   # Docker Compose統合設定
│                        # - 全サービス定義 (laravel-api, admin-app, user-app, pgsql, redis, etc.)
│                        # - プロジェクト固有イメージ命名 (laravel-next-b2c/app、他プロジェクトとの競合回避)
│                        # - APP_PORTデフォルト値最適化 (Dockerfile: 13000、ランタイム変更可能)
│                        # - ヘルスチェック機能統合 (全サービスの起動状態監視)
│                        # - 依存関係の自動管理 (depends_on: service_healthy)
│                        # - IPv4明示対応 (localhost→127.0.0.1)
│                        # - ネットワーク設定
│                        # - ボリューム管理
│                        # - 環境変数設定
├── .dockerignore        # Dockerビルド除外設定
│                        # - node_modules, .next, .git等の除外
│                        # - モノレポ対応（各サブディレクトリで有効）
├── package.json         # ワークスペース定義、共通スクリプト
│                        # workspaces: ["frontend/admin-app", "frontend/user-app"]
│                        # lint-staged設定を含む
├── jest.base.js         # モノレポ共通Jest設定
├── jest.config.js       # プロジェクト統括Jest設定
├── jest.setup.ts        # グローバルテストセットアップ
├── test-utils/          # 共通テストユーティリティ
│   ├── render.tsx       # カスタムrender関数
│   ├── router.ts        # Next.js Router モック設定
│   └── env.ts           # 環境変数モック
├── coverage/            # 統合カバレッジレポート
├── test-results/        # テスト実行結果（JUnit XML統合レポート）
│   ├── backend-results.xml      # バックエンドテスト結果（Pest JUnit出力）
│   ├── frontend-admin-results.xml  # Admin Appテスト結果（Jest JUnit出力）
│   ├── frontend-user-results.xml   # User Appテスト結果（Jest JUnit出力）
│   └── e2e-results.xml             # E2Eテスト結果（Playwright JUnit出力）
├── .husky/              # Gitフック自動化 (husky v9推奨方法: 直接フック配置)
│   ├── pre-commit       # コミット前にlint-staged実行
│   ├── pre-push         # プッシュ前にcomposer quality実行
│   └── _/               # レガシーフック（非推奨、互換性のため残存）
└── node_modules/        # 共通devDependencies
    ├── eslint           # ESLint 9
    ├── prettier         # Prettier 3
    ├── husky            # Gitフック管理
    ├── lint-staged      # ステージファイルlint
    ├── jest             # Jest 29
    └── @testing-library # React Testing Library 16
```

## E2Eテスト構造 (`e2e/`)
### Playwright E2Eテスト構成
```
e2e/
├── fixtures/            # テストフィクスチャ
│   └── global-setup.ts  # グローバルセットアップ（Sanctum認証）
├── helpers/             # テストヘルパー関数
│   └── sanctum.ts       # Laravel Sanctum認証ヘルパー
├── projects/            # プロジェクト別テスト
│   ├── admin/           # Admin Appテスト
│   │   ├── pages/       # Page Object Model (POM)
│   │   │   ├── LoginPage.ts     # ログインページオブジェクト
│   │   │   └── ProductsPage.ts  # 商品ページオブジェクト
│   │   └── tests/       # テストケース
│   │       ├── home.spec.ts          # ホームページテスト
│   │       ├── login.spec.ts         # ログインテスト（未実装スキップ中）
│   │       └── products-crud.spec.ts # 商品CRUD操作テスト（未実装スキップ中）
│   ├── user/            # User Appテスト
│   │   ├── pages/       # Page Object Model
│   │   └── tests/       # テストケース
│   │       ├── home.spec.ts              # ホームページテスト
│   │       └── api-integration.spec.ts   # API統合テスト（未実装スキップ中）
│   └── shared/          # 共通テスト
│       └── tests/       # テストケース
│           └── security-headers.spec.ts  # 🔐 セキュリティヘッダーE2Eテスト（Laravel/User/Admin全17テスト、CSP違反検出テスト含む）
├── storage/             # 認証状態ファイル（自動生成）
│   ├── admin.json       # Admin認証状態
│   └── user.json        # User認証状態
├── reports/             # テストレポート（自動生成）
├── test-results/        # テスト実行結果（自動生成）
├── playwright.config.ts # Playwright設定
├── package.json         # E2E依存関係
├── tsconfig.json        # TypeScript設定
├── .env                 # E2E環境変数（gitignore済み）
├── .env.example         # E2E環境変数テンプレート
└── README.md            # E2Eテストガイド（セットアップ、実行方法、CI/CD統合）
```

### CI/CD E2Eテスト実行フロー
```
GitHub Actions (.github/workflows/e2e-tests.yml):
1. トリガー: Pull Request / mainブランチpush / 手動実行
2. 並列実行: 4 Shard Matrix戦略（約2分完了）
3. セットアップ:
   - PHP 8.4インストール
   - Composerキャッシング（高速化）
   - Node.js 20セットアップ
   - npm依存関係インストール
4. サービス起動:
   - Laravel API: 開発モード（php artisan serve）
   - User App: npm run dev（ポート: 13001）
   - Admin App: npm run dev（ポート: 13002）
5. wait-on: 全サービス起動待機（タイムアウト: 5分）
6. Playwrightテスト実行: 各Shardごとに並列実行
7. レポート保存: Artifacts（HTML/JUnit、スクリーンショット、トレース）
```

## コード構成パターン
### 命名規約
- **ディレクトリ**: kebab-case (`admin-app`, `user-app`)
- **ファイル**: kebab-case (`.tsx`, `.ts`, `.php`)
- **コンポーネント**: PascalCase (`UserProfile.tsx`)
- **関数・変数**: camelCase (`getUserData`)
- **定数**: SCREAMING_SNAKE_CASE (`API_BASE_URL`)
- **型定義**: PascalCase (`UserInterface`, `ApiResponse`)

**🏗️ DDD固有命名規約**:
- **Entity**: PascalCase + `Entity`なし (`User.php`, not `UserEntity.php`)
- **ValueObject**: PascalCase (`Email.php`, `UserId.php`)
- **Repository Interface**: PascalCase + `RepositoryInterface` (`UserRepositoryInterface.php`)
- **Repository実装**: `Eloquent` + 名前 + `Repository` (`EloquentUserRepository.php`)
- **UseCase**: PascalCase + `UseCase` (`RegisterUserUseCase.php`)
- **DTO**: 用途 + 名前 + `Input/Output` (`RegisterUserInput.php`, `RegisterUserOutput.php`)
- **Domain Event**: 過去形 + `Event`なし (`UserRegistered.php`)
- **Query Interface**: PascalCase + `QueryInterface` (`UserQueryInterface.php`)

### ファイル構成原則
#### 🏗️ Laravel DDD/クリーンアーキテクチャ (バックエンド)
**4層構造の責務分離**:
- **Domain層** (`ddd/Domain/`):
  - 1集約1ディレクトリ（例: `ddd/Domain/User/`）
  - Entities、ValueObjects、Repository Interfaces、Events、Services、Exceptionsをサブディレクトリで整理
  - Laravelフレームワークに依存しない（Carbon除く）
- **Application層** (`ddd/Application/`):
  - 1集約1ディレクトリ（例: `ddd/Application/User/`）
  - UseCases、DTOs、Service Interfaces、Queries、Exceptionsをサブディレクトリで整理
  - Infrastructure層に依存しない（依存性逆転）
- **Infrastructure層** (`ddd/Infrastructure/`):
  - Repository実装、Query実装、Service実装をPersistence配下に配置
  - Eloquent依存コードはここに集約
- **HTTP層** (`app/Http/`):
  - Controllers、Requests、Resources、Middlewareを配置
  - Controllerはユースケース呼び出しのみ（薄いレイヤー）

**依存方向ルール**:
- HTTP → Application → Domain ← Infrastructure
- Domain層は他の層に依存しない（中心層）
- Infrastructure層はDomain/Application層のインターフェースを実装

**既存Laravel標準構成**:
- **1クラス1ファイル**: PSR-4標準準拠
- **名前空間**: `App\` をルートとする階層構造
- **Controller**: `App\Http\Controllers\` 配下
- **Model**: `App\Models\` 配下（Infrastructure層で使用）
- **Service**: `App\Services\` 配下 (従来のビジネスロジック、段階的にDDD移行)
- **Request**: `App\Http\Requests\` 配下 (バリデーション)

#### Next.js (フロントエンド)
- **Page Component**: `app/` ディレクトリ内のServer Components
- **Client Component**: `'use client'` ディレクティブ明示
- **共通Component**: `components/` ディレクトリで再利用
- **カスタムHook**: `hooks/` ディレクトリ、`use` プレフィックス
- **型定義**: `types/` ディレクトリ、`.d.ts` 拡張子

## Import構成指針
### バックエンド (Laravel DDD + API専用)
```php
// 🏗️ DDD層のインポート順序
// 1. Domain層（最上位）
use Ddd\Domain\User\Entities\User;
use Ddd\Domain\User\ValueObjects\Email;
use Ddd\Domain\User\ValueObjects\UserId;
use Ddd\Domain\User\Repositories\UserRepositoryInterface;
use Ddd\Domain\User\Events\UserRegistered;

// 2. Application層（ユースケース）
use Ddd\Application\User\UseCases\RegisterUserUseCase;
use Ddd\Application\User\DTOs\RegisterUserInput;
use Ddd\Application\User\DTOs\RegisterUserOutput;
use Ddd\Application\User\Services\TransactionManager;
use Ddd\Application\User\Queries\UserQueryInterface;

// 3. Infrastructure層（実装）
use Ddd\Infrastructure\Persistence\Eloquent\EloquentUserRepository;
use Ddd\Infrastructure\Persistence\Query\EloquentUserQuery;

// 4. Laravel APIコア機能（最小依存関係）
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;  // APIレスポンス専用
use App\Models\User as EloquentUser;  // EloquentモデルはInfrastructureで使用
use App\Http\Requests\Api\RegisterUserRequest;
use App\Http\Resources\UserResource;

// 5. Sanctum認証（コアパッケージ）
use Laravel\Sanctum\HasApiTokens;           // Personal Access Tokens trait
use Laravel\Sanctum\PersonalAccessToken;    // トークンモデル
use Illuminate\Support\Facades\Auth;        // 認証ファサード

// 6. 最小必要パッケージのみ
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
```

**Import原則**:
- Domain層は他の層をimportしない（Laravelフレームワーク除く）
- Application層はDomain層のみimport
- Infrastructure層はDomain/Application層のインターフェースを実装
- HTTP層はApplication層のユースケースを呼び出し

### フロントエンド (Next.js 15.5 + React 19)
```typescript
// React 19最新機能
import React from 'react'
import { useState, useEffect, use } from 'react'  // React 19 'use' hook

// Next.js 15.5 App Router
import Link from 'next/link'
import Image from 'next/image'
import { notFound } from 'next/navigation'

// 🔧 共通モジュール (@shared/*パスエイリアス、frontend-lib-monorepo-consolidation)
import { GLOBAL_ERROR_MESSAGES } from '@shared/lib/global-error-messages'  // Global Error静的辞書
import type { ErrorCode, ApiErrorResponse } from '@shared/types/errors'    // エラー型定義
import type { GlobalErrorMessages } from '@shared/types/messages'          // メッセージ型定義
import type { V1ApiResponse, V1LoginRequest } from '@shared/types/api/v1' // V1 API型定義

// 内部モジュール (相対パス避ける)
import { Button } from '@/components/ui/button'
import { useAuth } from '@/hooks/useAuth'        // 🔐 Sanctumトークン認証カスタムフック
import type { User, ApiResponse } from '@/types/api'  // APIレスポンス型

// API通信 (Laravel API専用最適化対応 + 🔐 Sanctum認証統合)
import axios from 'axios'
import { apiClient } from '@/lib/api-client'     // Sanctum認証統合
// 🔐 Sanctum認証APIエンドポイント:
// - POST /api/login: { email, password } → { token, user }
// - POST /api/logout: Authorization Bearer token
// - GET /api/me: Authorization Bearer token → user
// - GET /api/tokens: Authorization Bearer token → tokens[]
// - POST /api/tokens/{id}/revoke: Authorization Bearer token
import { clsx } from 'clsx'
```

**🔧 Import原則（frontend-lib-monorepo-consolidation適用後）**:
- **@shared/*を優先**: 共通ライブラリ・型定義は`@shared/*`経由でImport
- **@/は各アプリ固有モジュール**: コンポーネント、フック、ユーティリティは`@/`経由
- **重複排除**: `@shared/lib/*`、`@shared/types/*`で重複コード削減
- **単一ソース原則**: 共通モジュールは`frontend/lib/`、`frontend/types/`のみに配置

## 主要アーキテクチャ原則
### 🏗️ DDD/クリーンアーキテクチャ原則
- **依存性逆転原則 (DIP)**: Domain層を中心とした依存方向の制御、インターフェースによる抽象化
- **単一責任原則 (SRP)**: 各レイヤーと各クラスは単一の責務のみを持つ
- **オープン・クローズド原則 (OCP)**: 拡張に対して開いており、変更に対して閉じている
- **リスコフの置換原則 (LSP)**: 派生型はその基本型と置換可能
- **インターフェース分離原則 (ISP)**: クライアントに特化したインターフェース設計
- **4層依存ルール**: HTTP → Application → Domain ← Infrastructure
- **Architecture Testing**: Pestによる依存方向とレイヤー分離の自動検証

### 分離の原則
- **関心の分離**: UI層、ビジネスロジック層、データ層の明確な分離（DDD 4層構造）
- **API境界**: フロントエンドとバックエンドの完全な分離
- **アプリケーション分離**: 管理者用とユーザー用の独立開発
- **環境分離**: Docker Compose統合による開発環境の一貫性保証
- **インフラ信頼性**: Dockerヘルスチェック機能による起動保証と障害検知
- **既存MVCとDDD共存**: 段階的移行戦略による既存機能の保守性維持

### ディレクトリ責任
- **`backend/laravel-api/`**: API機能、データベース操作、ビジネスロジック
  - **`ddd/`**: 🏗️ DDD/クリーンアーキテクチャ実装
    - **`Domain/`**: ビジネスロジック中核（フレームワーク非依存）
    - **`Application/`**: ユースケース実装（Infrastructure非依存）
    - **`Infrastructure/`**: 外部システム実装（Repository、Query、Services）
  - **`app/`**: Laravel標準構成（HTTP層、既存MVC共存）
  - **`tests/`**: テストスイート（Feature、Unit、🏗️ Arch）
- **`frontend/admin-app/`**: 管理者機能UI、管理画面専用コンポーネント
- **`frontend/user-app/`**: ユーザー機能UI、顧客向けインターフェース
- **`.claude/`**: Claude Code設定、コマンド定義
- **`.kiro/`**: 仕様駆動開発、ステアリング文書

### 設定ファイル配置
- **環境設定**: 各アプリケーションルートの `.env`
- **ビルド設定**: 各技術スタック専用 (`package.json`, `composer.json`)
- **Docker設定**:
  - ルート: `docker-compose.yml` - 全サービス統合設定（ヘルスチェック統合、依存関係管理、プロジェクト固有イメージ命名）
  - バックエンド: `backend/laravel-api/compose.yaml` - Laravel Sail設定
  - バックエンド: `backend/laravel-api/docker/8.4/Dockerfile` - Laravel APIイメージ定義（APP_PORT=13000デフォルト最適化済み）
  - フロントエンド: `frontend/{admin-app,user-app}/Dockerfile` - Next.js イメージ定義
  - ルート: `.dockerignore` - ビルド除外設定
  - ドキュメント: `backend/laravel-api/docs/VERIFICATION.md` - Dockerヘルスチェック検証手順
  - ドキュメント: `DOCKER_TROUBLESHOOTING.md` - Dockerトラブルシューティング完全ガイド（APP_PORTポート設定、イメージ再ビルド、完全クリーンアップ）
- **テストインフラ設定**:
  - ルート: `Makefile` - テストDB管理タスク（quick-test, test-pgsql, test-parallel, test-setup等）
  - ルート: `scripts/` - テスト環境切り替え・並列テストスクリプト
  - ドキュメント: `docs/TESTING_DATABASE_WORKFLOW.md` - テストDB運用ワークフローガイド
- **開発ツール設定**: 各ディレクトリに適切な設定ファイル
- **PHP品質管理設定**:
  - `backend/laravel-api/pint.json` - Laravel Pint設定
  - `backend/laravel-api/phpstan.neon` - Larastan/PHPStan設定
- **CI/CD設定**: `.github/workflows/` - GitHub Actionsワークフロー
- **Next.js最適化設定**:
  - `frontend/{admin-app,user-app}/next.config.ts` - outputFileTracingRoot設定（モノレポ対応）

## 開発フロー指針

### ⚡ 日常開発フロー（3ターミナル起動方式）
1. **Terminal 1**: `make dev` でDockerサービス起動（Laravel API + インフラ）
2. **Terminal 2**: `cd frontend/admin-app && npm run dev` でAdmin App起動
3. **Terminal 3**: `cd frontend/user-app && npm run dev` でUser App起動
4. **ホットリロード確認**: 各ファイル変更後1秒以内に反映を確認
   - Laravel API: `routes/api.php` 等の変更を確認
   - Next.js: `.tsx` ファイル変更をブラウザで確認

**注記**: 以前の `scripts/dev/` による統合起動方式は削除されました。シンプルな3ターミナル方式を推奨します。

### 🏗️ アーキテクチャ開発フロー
1. **🏗️ DDD/クリーンアーキテクチャ開発フロー**:
   - **Domain First**: ビジネスロジックをDomain層で先行実装（Entity、ValueObject、Repository Interface）
   - **UseCase実装**: Application層でユースケース実装（DTO、UseCase）
   - **Infrastructure実装**: Repository/Query実装（EloquentベースのConcrete実装）
   - **HTTP統合**: Controller からユースケース呼び出し（薄いHTTP層）
   - **Architecture Testing**: Pestによる依存方向の自動検証
2. **API First**: バックエンドAPIを先行開発
3. **コンポーネント駆動**: フロントエンドの再利用可能設計
4. **型安全性**: TypeScript活用による開発時エラー防止
5. **テスト駆動（96.1%カバレッジ達成）**:
   - バックエンド: Pest 4による包括的テスト
     - Unit Tests: Domain層ロジックテスト（Domain層100%カバレッジ）
     - Feature Tests: Application層統合テスト（Application層98%カバレッジ）
     - 🏗️ Architecture Tests: 依存方向検証、レイヤー分離チェック、命名規約検証
     - テストDB環境: SQLite（高速開発）/PostgreSQL（本番同等）の柔軟な切り替え、並列テスト実行（4 Shard）
   - フロントエンド: Jest 29 + Testing Library 16（カバレッジ94.73%）
   - E2E: Playwright 1.47.2によるエンドツーエンドテスト
   - テストサンプル: Client Component、Server Actions、Custom Hooks、API Fetch
   - Page Object Model: E2Eテストの保守性向上パターン
   - Makefileタスク: テストインフラ管理の標準化（quick-test, test-pgsql, ci-test）
6. **環境分離**: 開発、ステージング、本番環境の明確な分離
7. **品質管理の自動化**:
   - Git Hooks (pre-commit: lint-staged, pre-push: composer quality)
   - CI/CD (GitHub Actions: Pull Request時の自動品質チェック + Architecture Tests)
   - 開発時の継続的品質保証
8. **E2E認証統合**:
   - Laravel Sanctum認証のE2Eテスト対応
   - Global Setup による認証状態の事前生成
   - 環境変数による柔軟なテスト環境設定
9. **既存MVCとDDD共存戦略**:
   - 段階的移行アプローチ（新機能はDDD、既存機能は徐々に移行）
   - 共存期間の明確な責務分離
   - リファクタリング優先順位の設定
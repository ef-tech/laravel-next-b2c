# Claude Code Spec-Driven Development

Kiro-style Spec Driven Development implementation using claude code slash commands, hooks and agents.

## Project Context

### Paths
- Steering: `.kiro/steering/`
- Specs: `.kiro/specs/`
- Commands: `.claude/commands/`

### Steering vs Specification

**Steering** (`.kiro/steering/`) - Guide AI with project-wide rules and context
**Specs** (`.kiro/specs/`) - Formalize development process for individual features

### Active Specifications
- `readme-setup-development-guide` - README.md整備（セットアップ手順、開発フロー記載）
- `laravel-minimal-package-config` - Laravel必要最小限パッケージ構成（API専用最適化）
- `frontend-common-eslint-prettier-config` - フロントエンド共通ESLint/Prettier設定（モノレポ統一設定）
- `laravel-pint-larastan-config` - Laravel用Laravel Pint・Larastan設定（PHP品質管理システム強化）
- `laravel-pest-migration` - Laravel PHPUnit を Pest 4に移行（テストフレームワーク移行とサンプル作成）
- `phpunit-to-pest-complete-migration` - Laravel の PHPUnit テストを Pest テストに完全移行する
- `nextjs-jest-testing-library-setup` - Next.js Jest + Testing Library設定とテストサンプル作成（フロントエンドテスト環境整備）
- `e2e-test-environment-setup` - E2Eテスト環境基盤設定（Playwright + モノレポ対応 + CI/CD統合）
- `nextjs-app-port-fixed` - Next.jsアプリポート固定化（user-app: 13001, admin-app: 13002）
- `nextjs-app-dockerfile` - Next.js Dockerfile作成とDocker Compose統合（開発環境Docker化・E2E Docker実行対応）
- `e2e-cicd-execution-verification` - E2E CI/CD実行確認（GitHub Actions ワークフロー有効化）
- `github-actions-trigger-optimization` - GitHub Actions発火タイミング最適化（concurrency/paths設定・API契約監視・実行時間削減）
- `husky-v10-pre-commit-deprecation` - Husky v9推奨方法対応（`.husky/`直下にフック直接配置、非推奨警告解消）
- `laravel-ddd-clean-architecture-solid` - Laravel DDD/クリーンアーキテクチャ/SOLID導入（4層構造・Repository Pattern・テスト戦略・段階的移行）
- `docker-compose-healthcheck` - Docker Composeヘルスチェック追加（Next.jsアプリ・サービス間依存関係最適化）
- `testing-database-setup` - テスト用DB設定（SQLite/PostgreSQL環境切替・並列テスト実行・CI/CD統合）
- `laravel-sanctum-basic-setup` - Laravel Sanctum基本設定（API認証基盤整備・トークンベース認証・セキュアAPI通信）
- `laravel-api-health-check-endpoint` - Laravel APIヘルスチェックエンドポイント実装（/api/health追加・Dockerヘルスチェック統合）
- `frontend-test-eslint-setup` - フロントエンドテストコードのESLint設定追加（Jest/Testing Libraryルール統合・モノレポ対応）
- `cors-environment-config` - CORS環境変数設定（フロントエンドアプリ用、開発/本番環境対応、セキュリティ強化）
- `environment-variable-management` - 環境変数適切管理方法整備（.env.exampleコメント整備、バリデーション実装、セキュリティガイド作成）
- `security-headers-setup` - セキュリティヘッダー設定（OWASP準拠、Laravel/Next.js実装、CSPレポート収集、段階的導入）
- `basic-middleware-setup` - 基本ミドルウェア設定（統一ミドルウェアスタック、ログ・レート制限・認証認可・監査・キャッシュ、DDD統合）
- `api-rate-limit-setup` - APIレート制限設定強化（エンドポイント分類細分化、Redis障害時フェイルオーバー、DDD準拠、テストカバレッジ85%以上）
- `setup-script` - 一括セットアップスクリプト作成（make setup自動化、15分以内環境構築、冪等性保証、エラーハンドリング）
- `dev-server-startup-script` - 開発サーバー起動スクリプト作成（単一コマンド起動、Docker/ネイティブ/ハイブリッドモード、設定駆動アーキテクチャ）
- `test-execution-script` - テスト実行スクリプト作成（全テストスイート統合実行、JUnit/カバレッジレポート統合、CI/CD連携、エラーハンドリング）
- `docker-config-improvement` - Docker設定改善（Laravel APIホットリロード対応、Next.jsネイティブ起動、開発環境シンプル化）
- `uuid-to-bigint` - UUID主キーからbigint主キーへの変更（Laravel標準構成準拠、パフォーマンス最適化）
- `api-versioning` - APIバージョニング（V1実装、URLベース/api/v1、DDD準拠、完全テスト戦略、V2ロードマップ）
- `error-handling-pattern` - エラーハンドリングパターン作成（RFC 7807準拠、統一APIエラーレスポンス、Request ID伝播、多言語対応、Error Boundaries実装）
- `frontend-error-message-i18n` - フロントエンドエラーメッセージ多言語化対応（next-intl統合、Error Boundaries i18n、NetworkError多言語化、Accept-Language連携）
- `frontend-lib-monorepo-consolidation` - frontend/lib/コード重複解消（TypeScriptパスエイリアス@shared、重複ファイル削除、約560行削減、メンテナンス性向上）
- `frontend-cicd-build-validation` - フロントエンドCI/CD本番ビルド検証追加（TypeScript型チェック・npm run build・PR時エラー検知強化）
- `rfc7807-type-uri-unification` - RFC 7807 type URI完全統一（ErrorCode::getType()単一ソース化、HasProblemDetails/DomainException修正、フォールバック処理実装、Architecture Tests追加）
- `domain-exception-has-problem-details` - DomainExceptionへのHasProblemDetails trait適用（DRY原則徹底、toProblemDetails()重複削除、保守性向上、Architecture Test強化）

### Completed Specifications
- `frontend-common-tsconfig` - ✅ フロントエンド共通tsconfig.base.json導入完了（TypeScript設定の重複削減、15個の共通compilerOptions集約、保守性向上、スケーラビリティ確保）
  - 実装完了日: 2025-11-13
  - Issue: #126
  - 成果: tsconfig.base.json作成、User App/Admin Appで共通設定継承、型チェック・Jest・Next.jsビルド全検証成功
- `i18n-type-unification` - ✅ i18n型定義統一完了（User App/Admin AppのvalidLocale型を統一、string型明示化、next-intl公式型定義準拠、型安全性維持）
  - 実装完了日: 2025-11-13
  - PR: #134
  - 成果: locale型をstringに明示的変換、Error Boundaries i18n完全実装、全ページを[locale]ルート配下に統一、型エラー完全解消
- `global-error-static-dictionary-dry` - ✅ Global Error静的辞書の共通化完了（DRY原則適用、~170行コード削減、型安全性維持、全54テストpass）
  - 実装完了日: 2025-11-09
  - 成果: User App/Admin Appの重複メッセージ辞書を共通モジュール化、保守性向上
- `jest-remove-pass-with-no-tests` - ✅ Jestの--passWithNoTestsオプション削除完了（テスト実行の確実性向上、テストファイル削除検知、CI品質保証）
  - 実装完了日: 2025-11-18
  - PR: #140
  - Issue: #138
  - 成果: Phase 1-7完了（全7フェーズ完了、全タスク完了）、testMatch設定検証、異常系テスト検証、CI/CD統合検証、BtoCテンプレート品質保証最終検証、ドキュメント更新（TESTING_TROUBLESHOOTING.md補助コマンド追記、Jestバージョン表記修正Jest 30 → Jest 29.7.0）

- Use `/kiro:spec-status [feature-name]` to check progress

## Development Guidelines
- Think in English, but generate responses in Japanese (思考は英語、回答の生成は日本語で行うように)

## Workflow

### Phase 0: Steering (Optional)
`/kiro:steering` - Create/update steering documents
`/kiro:steering-custom` - Create custom steering for specialized contexts

Note: Optional for new features or small additions. You can proceed directly to spec-init.

### Phase 1: Specification Creation
1. `/kiro:spec-init [detailed description]` - Initialize spec with detailed project description
2. `/kiro:spec-requirements [feature]` - Generate requirements document
3. `/kiro:spec-design [feature]` - Interactive: "Have you reviewed requirements.md? [y/N]"
4. `/kiro:spec-tasks [feature]` - Interactive: Confirms both requirements and design review

### Phase 2: Progress Tracking
`/kiro:spec-status [feature]` - Check current progress and phases

## Development Rules
1. **Consider steering**: Run `/kiro:steering` before major development (optional for new features)
2. **Follow 3-phase approval workflow**: Requirements → Design → Tasks → Implementation
3. **Approval required**: Each phase requires human review (interactive prompt or manual)
4. **No skipping phases**: Design requires approved requirements; Tasks require approved design
5. **Update task status**: Mark tasks as completed when working on them
6. **Keep steering current**: Run `/kiro:steering` after significant changes
7. **Check spec compliance**: Use `/kiro:spec-status` to verify alignment

## Steering Configuration

### Current Steering Files
Managed by `/kiro:steering` command. Updates here reflect command changes.

### Active Steering Files
- `product.md`: Always included - Product context and business objectives
- `tech.md`: Always included - Technology stack and architectural decisions
- `structure.md`: Always included - File organization and code patterns

### Custom Steering Files
<!-- Added by /kiro:steering-custom command -->
<!-- Format:
- `filename.md`: Mode - Pattern(s) - Description
  Mode: Always|Conditional|Manual
  Pattern: File patterns for Conditional mode
-->

### Inclusion Modes
- **Always**: Loaded in every interaction (default)
- **Conditional**: Loaded for specific file patterns (e.g., "*.test.js")
- **Manual**: Reference with `@filename.md` syntax


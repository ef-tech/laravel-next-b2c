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
- `husky-v10-pre-commit-deprecation` - Husky v10対応（pre-commitフック非推奨警告修正）
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


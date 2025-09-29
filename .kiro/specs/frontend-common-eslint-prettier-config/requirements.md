# Requirements Document

## GitHub Issue Information

**Issue**: [#7](https://github.com/ef-tech/laravel-next-b2c/issues/7) - フロントエンド共通 ESLint/Prettier 設定
**Labels**: なし
**Milestone**: なし
**Assignees**: なし

### Original Issue Description

## 背景と目的

### 背景
- 現在、admin-appとuser-appの2つのNext.jsアプリケーションが独立したESLint設定を持っている
- Prettier設定が未導入で、コードフォーマットが統一されていない
- チーム開発におけるコードスタイルの一貫性が確保されていない状況

### 目的
1. **保守性向上**: 共通のコードスタイル設定により、コードレビューの効率化と品質向上を実現
2. **開発効率化**: 自動フォーマット・自動修正により、スタイルに関する議論時間を削減
3. **品質保証**: コミット前チェックにより、低品質コードの混入を防止

## カテゴリ

**Code** - フロントエンド開発環境整備（ESLint/Prettier統一設定）

### 詳細分類
- 設定ファイル作成・編集
- 開発依存関係の追加・更新
- Git Hooks設定（husky + lint-staged）
- VSCode設定ファイルの追加

## スコープ

### 対象範囲
- ✅ `frontend/admin-app/` のESLint/Prettier設定
- ✅ `frontend/user-app/` のESLint/Prettier設定
- ✅ モノレポルートでの共通設定ファイル
- ✅ コミット前の自動チェック設定（husky + lint-staged）
- ✅ VSCodeエディタ設定の統一

### 対象外
- ❌ Laravel（backend）のコードスタイル設定（PHPは別Issue）
- ❌ 既存コードへの一括自動修正適用（段階的適用は別途計画）
- ❌ CI/CDパイプラインの構築（別Issue #8で対応）
- ❌ テストコードのリント設定（将来対応）

## Extracted Information

### Technology Stack

**Backend**: Laravel
**Frontend**: Next.js, TypeScript, React, Tailwind CSS
**Infrastructure**: npm workspaces (monorepo)
**Tools**: ESLint 9, Prettier 3, Husky 9, lint-staged 15, VSCode

### Project Structure

Issueから抽出された主要なファイル・ディレクトリ構成:

```
/.prettierrc
/.prettierignore
/frontend/.eslint.base.mjs
/frontend/admin-app/eslint.config.mjs
/frontend/user-app/eslint.config.mjs
/package.json
/frontend/admin-app/package.json
/frontend/user-app/package.json
/.husky/pre-commit
/.vscode/settings.json
/.vscode/extensions.json
```

### Requirements Hints

Issueの分析から抽出された主要要件:

- admin-appとuser-appの2つのNext.jsアプリケーション向け共通ESLint設定の作成
- Prettier設定の導入とコードフォーマット統一
- Git Hooks (husky + lint-staged) によるコミット前チェックの自動化
- VSCodeエディタ設定の統一と保存時自動フォーマット設定
- モノレポ構成での共通設定共有（FlatCompat活用）
- 段階的適用戦略（既存コードへの一括適用は対象外）

### TODO Items from Issue

Phase 1: 設定ファイル作成
- [ ] `/.prettierrc` 作成
- [ ] `/.prettierignore` 作成
- [ ] `/frontend/.eslint.base.mjs` 作成
- [ ] `/frontend/admin-app/eslint.config.mjs` 更新
- [ ] `/frontend/user-app/eslint.config.mjs` 更新

Phase 2: パッケージ管理
- [ ] ルート `/package.json` 作成
- [ ] `/frontend/admin-app/package.json` スクリプト更新
- [ ] `/frontend/user-app/package.json` スクリプト更新
- [ ] `npm install` 実行（ルート）
- [ ] 各アプリの `npm install` 実行

Phase 3: Git Hooks設定
- [ ] `npx husky init` 実行
- [ ] `/.husky/pre-commit` 作成
- [ ] 実行権限付与（`chmod +x .husky/pre-commit`）
- [ ] コミット動作テスト

Phase 4: エディタ設定
- [ ] `/.vscode/settings.json` 作成
- [ ] `/.vscode/extensions.json` 作成
- [ ] VSCode再起動で設定反映確認

Phase 5: 動作確認
- [ ] `npm run format:check` 実行成功
- [ ] `npm run lint` 実行成功（警告は許容）
- [ ] `npm run type-check` 実行成功
- [ ] コミット時のlint-staged動作確認
- [ ] 各アプリの `npm run dev` 正常起動確認

## Requirements
<!-- Will be generated in /kiro:spec-requirements phase -->
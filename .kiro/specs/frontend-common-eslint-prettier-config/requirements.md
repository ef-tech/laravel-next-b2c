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

### はじめに

本要件定義は、admin-appとuser-appの2つのNext.jsアプリケーションにおいて、コードスタイルとフォーマットの統一を実現するための共通ESLint/Prettier設定の導入を定義します。

**ビジネス価値**:
- コードレビューの効率化と品質向上による保守性の向上
- 自動フォーマットによるスタイル議論時間の削減と開発効率の向上
- コミット前チェックによる低品質コードの混入防止

**技術的制約**:
- モノレポ構成（npm workspaces）での共通設定共有
- Next.js 15.5 + React 19 + TypeScript環境
- ESLint 9 Flat Config形式の採用
- 既存アプリケーションへの影響最小化（段階的適用）

---

### Requirement 1: Prettierコードフォーマット設定

**目的**: 開発者として、統一されたコードフォーマット設定を利用することで、手動でのスタイル調整作業を排除し、一貫したコードベースを維持したい

#### Acceptance Criteria

1. WHEN 開発者がルートディレクトリに`.prettierrc`ファイルを配置する THEN コードフォーマットシステムは、printWidth 100、singleQuote false、trailingComma all、semi true、tabWidth 2、endOfLine lf、Tailwind CSSプラグインを含む設定を適用すること
2. WHEN 開発者がルートディレクトリに`.prettierignore`ファイルを配置する THEN コードフォーマットシステムは、node_modules、.next、dist、build、out、coverage、.turbo、.vercel、*.min.*、backend、.kiro、.claude、.git、.husky、.ideaディレクトリをフォーマット対象から除外すること
3. WHEN 開発者が`npm run format:check`コマンドを実行する THEN コードフォーマットシステムは、frontend配下のすべてのTypeScript、JavaScript、JSON、CSS、Markdownファイルのフォーマット状態をチェックし、差分がある場合はエラーを返すこと
4. WHEN 開発者が`npm run format`コマンドを実行する THEN コードフォーマットシステムは、frontend配下のすべての対象ファイルを自動的にフォーマットし、設定に準拠した状態に変更すること

---

### Requirement 2: ESLint共通設定とモノレポ統合

**目的**: 開発者として、admin-appとuser-appで共通のESLint設定を共有することで、重複設定を排除し、一貫したコード品質基準を適用したい

#### Acceptance Criteria

1. WHEN 開発者が`/frontend/.eslint.base.mjs`ファイルを作成する THEN ESLintシステムは、Next.js推奨設定、TypeScript推奨設定、カスタムルール（no-console: warn、no-debugger: warn、unused-vars: warn）、Prettier競合ルール無効化、共通ignoreパターンを含む基本設定を提供すること
2. WHEN 開発者が各アプリの`eslint.config.mjs`で基本設定をimportする THEN ESLintシステムは、各アプリケーションに共通設定を適用し、個別のカスタマイズも可能にすること
3. WHEN 開発者が`npm run lint`コマンドをルートディレクトリで実行する THEN ESLintシステムは、すべてのワークスペース（admin-app、user-app）のリントチェックを並列実行し、結果を統合して表示すること
4. WHEN 開発者が`npm run lint:fix`コマンドを実行する THEN ESLintシステムは、自動修正可能なすべての問題を修正し、修正不可能な問題のみをエラーとして報告すること
5. WHEN ESLint設定がNext.js core-web-vitalsとTypeScript推奨設定を継承する THEN ESLintシステムは、FlatCompatを使用してレガシー設定形式を新しいFlat Config形式に変換し、正しく動作すること

---

### Requirement 3: Git Hooksによるコミット前自動チェック

**目的**: 開発者として、コミット前に自動的にリントとフォーマットのチェックが実行されることで、品質基準を満たさないコードのコミットを防止したい

#### Acceptance Criteria

1. WHEN 開発者がルート`package.json`にhusky 9とlint-staged 15を依存関係として追加し、`npx husky init`を実行する THEN Git Hooksシステムは、`.husky/`ディレクトリを作成し、Huskyの初期設定を完了すること
2. WHEN 開発者が`.husky/pre-commit`ファイルを作成し、`npx lint-staged`を実行するように設定する THEN Git Hooksシステムは、コミット前にlint-stagedを自動実行すること
3. WHEN 開発者がTypeScript/JavaScriptファイルをステージングしてコミットする THEN lint-stagedシステムは、ステージングされたファイルに対してESLint自動修正（--fix --max-warnings=0）とPrettier自動フォーマットを順次実行すること
4. WHEN 開発者がCSS、Markdown、JSONファイルをステージングしてコミットする THEN lint-stagedシステムは、ステージングされたファイルに対してPrettier自動フォーマットのみを実行すること
5. WHEN lint-stagedの実行中にESLintエラーまたはフォーマットエラーが発生する THEN Git Hooksシステムは、コミットを中断し、エラー内容を明確に表示すること
6. WHEN 開発者が緊急時に`git commit --no-verify`を使用する THEN Git Hooksシステムは、pre-commitフックをスキップし、チェックなしでコミットを許可すること

---

### Requirement 4: VSCodeエディタ統合設定

**目的**: 開発者として、VSCodeエディタで統一された開発環境設定を使用することで、保存時の自動フォーマットとリアルタイムのリントエラー表示を実現したい

#### Acceptance Criteria

1. WHEN 開発者が`.vscode/settings.json`ファイルを作成し、editor.formatOnSave、editor.codeActionsOnSave、eslint.workingDirectories、prettier.configPathを設定する THEN VSCodeは、ファイル保存時に自動的にPrettierフォーマットとESLint自動修正を実行すること
2. WHEN VSCode設定でTypeScript、TypeScriptReact、JavaScript、JSON各ファイルタイプのデフォルトフォーマッターをPrettierに設定する THEN VSCodeは、各ファイルタイプで常にPrettierを使用してフォーマットを実行すること
3. WHEN 開発者が`.vscode/extensions.json`ファイルで推奨拡張機能（ESLint、Prettier、Tailwind CSS）を指定する THEN VSCodeは、プロジェクトを開いた際に推奨拡張機能のインストールを促すこと
4. WHEN eslint.workingDirectoriesに`frontend/admin-app`と`frontend/user-app`を指定する THEN VSCodeのESLint拡張機能は、各アプリケーションの独立したESLint設定を正しく認識し、適用すること

---

### Requirement 5: npm workspaces統合とパッケージ管理

**目的**: 開発者として、モノレポ構成でルートから統一されたコマンドで全アプリケーションのリント・フォーマット操作を実行したい

#### Acceptance Criteria

1. WHEN 開発者がルート`package.json`でworkspacesに`frontend/admin-app`と`frontend/user-app`を指定する THEN npmは、各ワークスペースを認識し、ルートから統合コマンドを実行可能にすること
2. WHEN 開発者がルートディレクトリで`npm install`を実行する THEN npmは、ルートおよびすべてのワークスペースの依存関係を解決し、共通の依存関係をルートにhoistすること
3. WHEN ルート`package.json`に`prepare`スクリプトで`husky`を設定する THEN npmは、`npm install`実行時に自動的にHuskyのセットアップを実行すること
4. WHEN 各アプリの`package.json`に`lint`、`lint:fix`、`type-check`スクリプトを定義する THEN 各アプリケーションディレクトリおよびルートから該当コマンドを実行可能にすること
5. WHEN ルート`package.json`で`--workspaces`フラグを使用したlint、lint:fix、type-checkスクリプトを定義する THEN npmは、すべてのワークスペースで該当コマンドを並列実行すること

---

### Requirement 6: 依存関係の最小化と互換性確保

**目的**: 開発者として、必要最小限の依存関係で最新のESLint 9、Prettier 3、Husky 9、lint-staged 15を導入し、既存のNext.js 15.5環境との互換性を確保したい

#### Acceptance Criteria

1. WHEN 開発者がルート`package.json`のdevDependenciesにeslint、eslint-config-prettier、eslint-config-next、@eslint/eslintrc、prettier、prettier-plugin-tailwindcss、husky、lint-stagedを追加する THEN npmは、これらのパッケージを正しくインストールし、相互に互換性のあるバージョンを解決すること
2. WHEN ESLint 9 Flat Config形式を採用する THEN ESLintシステムは、従来の`.eslintrc`形式ではなく`eslint.config.mjs`形式の設定ファイルを正しく認識し、動作すること
3. WHEN @eslint/eslintrcパッケージのFlatCompatを使用する THEN ESLintシステムは、Next.jsの推奨設定（レガシー形式）を新しいFlat Config形式に正しく変換すること
4. WHEN prettier-plugin-tailwindcssをPrettier設定のpluginsに追加する THEN Prettierは、Tailwind CSSのクラス名を自動的にソートし、推奨順序に整列すること
5. WHEN 各アプリケーションで既存のnext、react、typescript依存関係を維持する THEN 新しく追加されたリント・フォーマット関連パッケージは、既存の依存関係と競合せず、正常に動作すること

---

### Requirement 7: 既存コードへの影響最小化と段階的適用

**目的**: 開発者として、既存コードベースへの破壊的変更を避け、段階的にリント・フォーマット基準を適用できるようにしたい

#### Acceptance Criteria

1. WHEN 初回のESLintチェック実行時に大量の警告が発生する THEN ESLintシステムは、警告を表示するがビルドやコミットを阻害せず、開発者に段階的な修正機会を提供すること
2. WHEN lint-stagedで`--max-warnings=0`オプションを使用する THEN ESLintシステムは、警告をエラーとして扱い、新規コミット時の品質基準を厳格に適用すること
3. WHEN 既存のESLint設定ファイル（`.eslintrc.*`）が存在する THEN 新しいESLint設定システムは、古い設定ファイルを無視し、新しい`eslint.config.mjs`のみを使用すること
4. WHEN 開発者が初回フォーマット適用前に`npm run format:check`を実行する THEN Prettierは、フォーマット差分を検出して報告するが、ファイルを変更せず、レビュー機会を提供すること
5. WHEN 開発者が段階的に既存コードを修正する THEN リント・フォーマットシステムは、修正されたファイルのみに新しい基準を適用し、未修正ファイルに影響を与えないこと

---

### Requirement 8: ビルド・開発サーバーとの統合

**目的**: 開発者として、リント・フォーマット設定が各アプリケーションの開発サーバー起動やビルドプロセスを阻害しないことを確認したい

#### Acceptance Criteria

1. WHEN 開発者が`npm run dev`コマンドを各アプリディレクトリで実行する THEN Next.jsは、リント・フォーマット設定に関係なく、正常に開発サーバーを起動すること
2. WHEN 開発者が`npm run build`コマンドを各アプリディレクトリで実行する THEN Next.jsは、ビルド前にESLintチェックを実行し、エラーがある場合はビルドを失敗させ、警告のみの場合はビルドを成功させること
3. WHEN Next.jsのビルドプロセスでESLintエラーが検出される THEN Next.jsは、具体的なエラー箇所とファイルパスを明確に表示し、開発者が迅速に修正できるようにすること
4. WHEN 開発者がTurbopackモード（`--turbopack`フラグ）で開発サーバーを起動する THEN リント・フォーマット設定は、Turbopackの高速ビルド機能を阻害せず、正常に動作すること
5. WHEN 型チェック（`npm run type-check`）を実行する THEN TypeScriptコンパイラは、リント・フォーマット設定とは独立して型エラーのみを検出し、報告すること
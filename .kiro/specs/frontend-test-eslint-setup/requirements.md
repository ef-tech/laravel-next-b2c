# Requirements Document

## GitHub Issue Information

**Issue**: [#79](https://github.com/ef-tech/laravel-next-b2c/issues/79) - Priority: 高 - フロントエンドテストコードのリント設定追加
**Labels**: なし
**Milestone**: なし
**Assignees**: なし

### Original Issue Description

# フロントエンドテストコードのESLint設定追加

## 背景・目的

### 現状の課題
- Next.js 15.5 + React 19 + Jest 29 + React Testing Library 16環境でテストコードが存在
- テストファイル（`*.test.ts`, `*.test.tsx`）に対するESLint設定が未適用
- `jest.config.js`のみ除外されており、テストコード自体の品質チェックが行われていない
- `describe`/`it`/`expect`などのJestグローバル関数が未定義エラーとして誤検知される可能性

### 目的
- Jest/Testing Libraryのベストプラクティスを強制し、テストコードの品質と一貫性を向上
- テストファイル特有のESLint設定を導入し、開発体験を改善
- モノレポ（admin-app/user-app）全体で統一されたテストコードリント環境を構築

---

## イントロダクション

本仕様は、Next.js 15.5 + React 19 + Jest 29 + React Testing Library 16環境におけるテストコードの品質向上を目的として、ESLintによるテストファイル専用のリント設定を追加するものです。

### ビジネス価値
- **テストコード品質向上**: Jest/Testing Libraryのベストプラクティスを強制し、バグの早期検出と保守性向上を実現
- **開発体験改善**: IDEでのリアルタイムエラー表示と自動修正機能により、開発効率を向上
- **モノレポ全体の一貫性**: admin-app/user-app両方で統一されたテストコード規約を適用し、チーム開発の生産性を向上
- **CI/CD統合**: 自動リントチェックにより、品質ゲートを強化し、本番環境への不具合流入を防止

### スコープ
**対象範囲**:
- `frontend/.eslint.base.mjs`へのテストファイル専用オーバーライド追加
- eslint-plugin-jest, eslint-plugin-testing-library, globalsパッケージの導入
- GitHub Actions・lint-stagedへのテストリント統合

**対象外**:
- 既存テストコード自体の修正（初期は警告のみ）
- E2Eテスト（Playwright）へのリント設定
- バックエンド（Laravel）のテスト設定
- ESLint v10への移行

---

## Requirements

### Requirement 1: ESLint依存関係管理
**Objective:** フロントエンド開発者として、テストコードのリントに必要なESLintプラグインがプロジェクトに導入されていることを期待する。これにより、テストファイルのベストプラクティスチェックが可能になる。

#### Acceptance Criteria

1. WHEN 開発者がルートディレクトリで`npm install`を実行した THEN ESLintテストプラグインシステム SHALL `eslint-plugin-jest@^28`, `eslint-plugin-testing-library@^6`, `eslint-plugin-jest-dom@^5`, `globals@^15`を`package.json`の`devDependencies`にインストールする

2. WHEN 開発者が`npm ls eslint-plugin-jest eslint-plugin-testing-library`コマンドを実行した THEN ESLintテストプラグインシステム SHALL バージョン競合なしで依存関係ツリーを表示する

3. WHERE ルート`package.json`の`devDependencies` THE ESLintテストプラグインシステム SHALL 以下の4つのパッケージエントリを含む
   - `eslint-plugin-jest: ^28`
   - `eslint-plugin-testing-library: ^6`
   - `eslint-plugin-jest-dom: ^5`
   - `globals: ^15`

4. IF `node_modules/.bin/eslint --version`コマンドを実行した THEN ESLintシステム SHALL バージョン9.x.xを表示する

---

### Requirement 2: ESLint Flat Config統合
**Objective:** フロントエンド開発者として、既存のESLint Flat Config設定を維持しつつ、テストファイル専用のルールが適用されることを期待する。これにより、通常のコードとテストコードで適切に分離されたリント環境が実現される。

#### Acceptance Criteria

1. WHEN `frontend/.eslint.base.mjs`ファイルを開いた THEN ESLint設定ファイル SHALL `eslint-plugin-jest`, `eslint-plugin-testing-library`, `eslint-plugin-jest-dom`, `globals`の4つのimport文を含む

2. WHERE `frontend/.eslint.base.mjs`のexport default配列 THE ESLint設定ファイル SHALL `eslintConfigPrettier`の**直前**にテストファイル専用オーバーライド設定を含む

3. WHEN テストファイル専用オーバーライド設定が評価された THEN ESLint設定 SHALL 以下のfilesパターンに一致するファイルにのみ適用される
   - `**/*.{test,spec}.{ts,tsx,js,jsx}`
   - `**/__tests__/**/*.{ts,tsx,js,jsx}`

4. WHERE テストファイル専用オーバーライド設定のplugins THE ESLint設定 SHALL 3つのプラグインを登録する
   - `jest: jestPlugin`
   - `testing-library: testingLibrary`
   - `jest-dom: jestDom`

5. WHEN テストファイルがリントされた THEN ESLint設定 SHALL `languageOptions.globals`に`globals.jest`を含め、`describe`, `it`, `expect`等のJestグローバル関数を認識する

6. WHERE テストファイル専用オーバーライド設定のrules THE ESLint設定 SHALL 以下の推奨ルールセットを適用する
   - `jestPlugin.configs["flat/recommended"].rules`
   - `testingLibrary.configs["flat/react"].rules`
   - `jestDom.configs["flat/recommended"].rules`

7. WHEN テストファイルがリントされた THEN ESLint設定 SHALL 以下のテスト特有の調整を適用する
   - `no-console: off`（デバッグ容易性優先）
   - `@typescript-eslint/no-unused-vars: warn`（`argsIgnorePattern: "^_"`, `varsIgnorePattern: "^_"`, `caughtErrors: "none"`）
   - `no-empty-function: off`（`jest.fn()`許容）

8. WHEN 初期導入フェーズでテストファイルがリントされた THEN ESLint設定 SHALL 以下のルールを`warn`レベルで適用する
   - `testing-library/no-node-access: warn`
   - `testing-library/no-container: warn`
   - `testing-library/no-debugging-utils: warn`

9. IF 既存の`next/core-web-vitals`設定が存在する THEN ESLint設定 SHALL FlatCompat方式を維持し、既存設定と共存する

---

### Requirement 3: ローカル開発環境でのリント実行
**Objective:** フロントエンド開発者として、ローカル環境でテストファイルを含む全ファイルのリントチェックが正常に動作することを期待する。これにより、コミット前に問題を検出できる。

#### Acceptance Criteria

1. WHEN 開発者がルートディレクトリで`npm run lint`を実行した THEN ESLintシステム SHALL ワークスペース全体（admin-app, user-app）のテストファイルを含む全ファイルをリントする

2. WHEN 開発者が`frontend/admin-app`ディレクトリで`npm run lint`を実行した THEN ESLintシステム SHALL admin-appのテストファイルを含む全ファイルをリントする

3. WHEN 開発者が`frontend/user-app`ディレクトリで`npm run lint`を実行した THEN ESLintシステム SHALL user-appのテストファイルを含む全ファイルをリントする

4. WHEN テストファイルで`describe`, `it`, `expect`を使用した THEN ESLintシステム SHALL `no-undef`エラーを表示しない（Jestグローバル関数として認識）

5. WHEN テストファイルで`fit`または`fdescribe`を使用した THEN ESLintシステム SHALL `jest/no-focused-tests`エラーを表示する

6. WHEN テストファイルで`container.querySelector()`を使用した THEN ESLintシステム SHALL `testing-library/no-node-access`警告を表示する

7. WHEN 開発者が`npm run lint:fix`を実行した THEN ESLintシステム SHALL 自動修正可能なテストファイルのリントエラーを修正する

8. WHEN 開発者が`npx eslint "frontend/**/src/**/*.{test,spec}.{ts,tsx}"`を実行した THEN ESLintシステム SHALL テストファイルのみを対象にリントする

9. IF 既存の通常コードファイル（`*.tsx`, `*.ts`）が存在する THEN ESLintシステム SHALL テストルール追加後もエラー数を増加させない

---

### Requirement 4: lint-staged統合
**Objective:** フロントエンド開発者として、Git commitの際にステージされたテストファイルが自動的にリントされることを期待する。これにより、品質の低いテストコードのコミットを防止できる。

#### Acceptance Criteria

1. WHERE ルート`package.json`の`lint-staged`設定 THE lint-stagedシステム SHALL `frontend/admin-app/**/*.{js,jsx,ts,tsx}`パターンでテストファイルを含む全ファイルをリント対象とする

2. WHERE ルート`package.json`の`lint-staged`設定 THE lint-stagedシステム SHALL `frontend/user-app/**/*.{js,jsx,ts,tsx}`パターンでテストファイルを含む全ファイルをリント対象とする

3. WHEN 開発者がテストファイル（`*.test.tsx`）をステージングした AND `git commit`を実行した THEN lint-stagedシステム SHALL ESLintを実行し、エラーがあればコミットを中断する

4. WHEN 開発者が`jest.config.js`ファイルをステージングした AND `git commit`を実行した THEN lint-stagedシステム SHALL `jest.config.js`を除外し、リント対象としない

5. IF テストファイルにESLintエラーが存在する AND 開発者が`git commit`を実行した THEN lint-stagedシステム SHALL コミットを失敗させ、エラー内容を表示する

6. WHEN lint-stagedがテストファイルをリントした THEN lint-stagedシステム SHALL テストファイル専用オーバーライド設定（Jest/Testing Libraryルール）を適用する

7. WHERE `.husky/pre-commit`フック THE Huskyシステム SHALL lint-stagedを実行する

8. IF 開発者がパフォーマンス最適化を希望する THEN lint-stagedシステム SHALL `--cache`, `--cache-location`オプションを使用してキャッシュを有効化できる

---

### Requirement 5: CI/CD統合（GitHub Actions）
**Objective:** 開発チームとして、GitHub ActionsのCI環境でテストファイルを含む全ファイルのリントチェックが自動実行されることを期待する。これにより、Pull Request時に品質ゲートを強制できる。

#### Acceptance Criteria

1. WHERE `.github/workflows/frontend-test.yml`ワークフロー THE GitHub Actionsシステム SHALL lintジョブを含む

2. WHEN Pull Requestが作成された OR 更新された THEN GitHub Actionsシステム SHALL frontend-test.ymlワークフローを自動実行する

3. WHEN lintジョブが実行された THEN GitHub Actionsシステム SHALL 以下のステップを順次実行する
   - `actions/checkout@v4`でリポジトリをチェックアウト
   - `actions/setup-node@v4`でNode.js 20をセットアップ
   - `npm ci`で依存関係をインストール
   - `npm run lint`でESLintを実行

4. WHERE lintジョブのNode.jsセットアップステップ THE GitHub Actionsシステム SHALL `cache: 'npm'`を指定してnpmキャッシュを有効化する

5. WHEN テストファイルにESLintエラーが存在する AND lintジョブが実行された THEN GitHub Actionsシステム SHALL ジョブを失敗させる

6. WHEN テストファイルにESLint警告が存在する AND `--max-warnings=0`が設定されている THEN GitHub Actionsシステム SHALL ジョブを失敗させる

7. IF 並列実行最適化が実装される THEN GitHub Actionsシステム SHALL matrixストラテジーで`workspace: [admin-app, user-app]`を並列実行できる

8. WHEN lintジョブが成功した THEN GitHub Actionsシステム SHALL Pull Requestに緑色のチェックマークを表示する

9. WHEN lintジョブが失敗した THEN GitHub Actionsシステム SHALL Pull Requestに赤色の×マークを表示し、マージを防止する

---

### Requirement 6: 段階的ルール昇格（運用フェーズ）
**Objective:** 開発チームとして、初期導入時は警告レベルでルールを適用し、段階的に厳格化することを期待する。これにより、既存テストへの影響を最小限に抑えながら品質向上を実現できる。

#### Acceptance Criteria

1. WHEN フェーズ1（初期導入）が開始された THEN ESLint設定システム SHALL 全Testing Libraryルールを`warn`レベルで適用する

2. WHEN CI環境で`npm run lint`が実行された THEN ESLint設定システム SHALL 警告数をレポートし、ベースライン情報を提供する

3. WHERE チームレビュー会議 THE 開発チーム SHALL 警告内容を分析し、ルール昇格の優先順位を決定する

4. WHEN フェーズ2（ルール引き上げ）が開始された THEN ESLint設定システム SHALL 以下の低ノイズルールを`error`レベルに昇格する
   - `jest/no-disabled-tests: error`
   - `jest/no-focused-tests: error`
   - `jest/valid-expect: error`
   - `testing-library/no-await-sync-queries: error`
   - `testing-library/no-manual-cleanup: error`

5. WHEN フェーズ3（完全適用）が開始された THEN ESLint設定システム SHALL 全推奨ルールを`error`レベルに昇格する

6. WHERE CI環境の`npm run lint`コマンド THE GitHub Actionsシステム SHALL `--max-warnings=0`フラグを追加し、警告もCI失敗対象とする

7. WHEN 新規テストコードが作成された THEN ESLint設定システム SHALL フェーズ3のルール（全error化）を適用し、ルール準拠を強制する

8. IF チームフィードバックでルール調整が必要 THEN ESLint設定システム SHALL `frontend/.eslint.base.mjs`のrules設定を更新し、カスタムルールレベルを適用できる

---

### Requirement 7: パフォーマンスと互換性
**Objective:** フロントエンド開発者として、ESLintテストルール追加後もリント実行時間が許容範囲内であり、既存のESLint 9環境と互換性が保たれることを期待する。これにより、開発体験を損なうことなく品質向上を実現できる。

#### Acceptance Criteria

1. WHEN ESLintテストルール追加前のリント実行時間を測定した AND 追加後の実行時間を測定した THEN ESLint設定システム SHALL 実行時間の増加を±10%以内に抑える

2. WHEN 開発者が`npm run lint -- --cache`を実行した THEN ESLintシステム SHALL `.eslintcache`ファイルを生成し、2回目以降の実行を高速化する

3. WHERE `frontend/.eslint.base.mjs`のテストファイル専用オーバーライド THE ESLint設定 SHALL `files`パターンでテストファイルのみにプラグインを適用し、通常ファイルへの影響を最小化する

4. IF 既存のESLint v9設定が存在する THEN ESLint設定システム SHALL ESLint v9のFlat Config形式を維持し、互換性を保つ

5. WHEN 開発者がESLintバージョンを確認した THEN ESLintシステム SHALL ESLint v9.x.xを表示し、v10への移行を行わない

6. WHERE ワークスペース別キャッシュ THE lint-stagedシステム SHALL `--cache-location frontend/admin-app/.eslintcache`と`--cache-location frontend/user-app/.eslintcache`を個別に指定できる

7. WHEN 大量のテストファイルが存在する THEN ESLintシステム SHALL 並列実行モードをサポートし、`--max-warnings=0`との併用が可能である

8. IF パフォーマンス問題が発生した THEN ESLint設定システム SHALL ルールセットを調整し、重いルールを無効化できる

---

### Requirement 8: ロールバックと復旧
**Objective:** 開発チームとして、ESLintテストルール導入後に問題が発生した場合、迅速にロールバックできる手順が明確であることを期待する。これにより、リスクを最小化できる。

#### Acceptance Criteria

1. WHEN 開発者が緊急ロールバックを実行する THEN ロールバック手順 SHALL 1分以内に完了する

2. WHERE ロールバック手順のステップ1 THE 開発者 SHALL `npm uninstall eslint-plugin-jest eslint-plugin-testing-library eslint-plugin-jest-dom globals`を実行する

3. WHERE ロールバック手順のステップ2 THE 開発者 SHALL `git checkout frontend/.eslint.base.mjs`を実行し、設定ファイルを復元する

4. WHEN ロールバック完了後に`npm run lint`を実行した THEN ESLintシステム SHALL 元の状態（テストルール追加前）に復元され、正常に動作する

5. IF ロールバック後も問題が継続する THEN ロールバック手順 SHALL `npm install`でnode_modulesを再構築するステップを含む

6. WHERE ロールバックドキュメント THE プロジェクトドキュメント SHALL ロールバック手順の詳細を`docs/JEST_ESLINT_TROUBLESHOOTING.md`に記載する

7. WHEN ロールバックが完了した THEN 開発チーム SHALL 問題の根本原因を分析し、再導入計画を策定する

8. IF 部分的なロールバックが必要 THEN ESLint設定 SHALL 特定のルールのみを無効化し、プラグイン自体は維持できる

---

### Requirement 9: ドキュメントとチーム周知
**Objective:** 開発チームとして、ESLintテストルール導入に関する包括的なドキュメントが整備され、チーム全体に周知されることを期待する。これにより、スムーズな導入と継続的な活用を実現できる。

#### Acceptance Criteria

1. WHERE プロジェクトドキュメント THE ドキュメントシステム SHALL 以下の3つのドキュメントを含む
   - `docs/JEST_ESLINT_INTEGRATION_GUIDE.md`（メインガイド）
   - `docs/JEST_ESLINT_CONFIG_EXAMPLES.md`（設定例集）
   - `docs/JEST_ESLINT_QUICKSTART.md`（5分クイックスタート）

2. WHEN Pull Requestが作成された THEN Pull Request説明 SHALL 以下の情報を含む
   - 変更内容の概要
   - Before/After設定比較
   - 動作確認結果
   - ドキュメントリンク

3. WHERE Pull Requestの本文 THE Pull Request SHALL ドキュメントリンク（`docs/JEST_ESLINT_*.md`）を共有する

4. WHEN 新ルールが追加された THEN チーム周知 SHALL 主要ポイント（focused tests検出、Testing Libraryクエリ推奨等）を説明する

5. IF チームメンバーから質問が発生した THEN ドキュメント SHALL FAQセクションで回答を提供する

6. WHERE `docs/JEST_ESLINT_INTEGRATION_GUIDE.md` THE メインガイド SHALL 以下のセクションを含む
   - 導入背景・目的
   - 依存パッケージ一覧
   - ESLint設定変更手順
   - ローカル動作確認方法
   - CI/CD統合手順
   - トラブルシューティング

7. WHERE `docs/JEST_ESLINT_CONFIG_EXAMPLES.md` THE 設定例集 SHALL 以下の実例を含む
   - テストファイル専用オーバーライド設定の完全な例
   - カスタムルール調整例
   - パフォーマンス最適化設定例

8. WHERE `docs/JEST_ESLINT_QUICKSTART.md` THE クイックスタート SHALL 5分以内で導入を完了できる簡潔な手順を提供する

9. WHEN ドキュメントが更新された THEN ドキュメント SHALL 最終更新日とバージョン情報を記載する

---

## 完了定義（Definition of Done）

本仕様の実装が完了したと見なされるためには、以下の条件を全て満たす必要があります：

### 必須条件
1. **依存パッケージ追加**: `eslint-plugin-jest`, `eslint-plugin-testing-library`, `eslint-plugin-jest-dom`, `globals`が`package.json`に存在し、バージョン競合なし
2. **ESLint設定変更**: `frontend/.eslint.base.mjs`にテストオーバーライド追加、Jestグローバル関数認識、推奨ルールセット適用
3. **ローカル動作確認**: `npm run lint`でテストファイルリント動作、`describe`/`it`/`expect`未定義エラーなし、既存コードでエラー増加なし
4. **lint-staged統合**: pre-commitでテストファイルリント自動実行、`jest.config.js`除外維持
5. **CI/CD統合**: GitHub Actionsで`npm run lint`成功、テストファイルエラーでCI失敗確認

### 品質基準
1. **コード品質**: focused tests検出動作、Testing Library推奨クエリ強制動作、assertionなしテスト検出動作
2. **パフォーマンス**: リント実行時間が導入前の±10%以内、`--cache`オプションで2回目以降高速化
3. **ドキュメント**: `docs/JEST_ESLINT_*.md`最新状態、Pull Request説明詳細（Before/After比較含む）、チーム向けドキュメントリンク共有完了

### 承認条件
1. **テックリードレビュー承認**: ESLint設定の妥当性確認、段階的導入戦略の承認
2. **CI/CDパイプライン成功**: GitHub Actionsで全チェック成功、テストカバレッジ維持（94%以上）
3. **チーム周知完了**: Pull Request説明共有、新ルールの主要ポイント説明、質問対応完了

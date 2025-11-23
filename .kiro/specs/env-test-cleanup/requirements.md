# Requirements Document

## Introduction

本仕様書は、Laravel Next.js B2Cテンプレートプロジェクトにおける環境変数関連テストコードの整理・削除に関する要件を定義します。

**ビジネス価値**:
- テストコード削減（約300行）による保守コスト削減
- テスト実行時間短縮（約5-10秒）による開発効率向上
- コードベースの簡素化による可読性・保守性の向上
- 既存の環境変数バリデーション機構（起動時検証、CI/CD検証）への効率的な移行

**背景**:
本プロジェクトには環境変数関連のテストファイルが複数存在していますが、Zodスキーマによる起動時バリデーション（`check-env.ts`）とCI/CDワークフロー（`env-validation.yml`）により、テストコードなしでも十分な品質保証が実現されています。このため、重複するテストコードを削除し、効率的なテスト戦略への移行が求められています。

**関連Issue**: [#105](https://github.com/ef-tech/laravel-next-b2c/issues/105)

---

## Requirements

### Requirement 1: 未使用ユーティリティの削除

**Objective:** 開発者として、未使用の`test-utils/env.ts`ファイルを削除したい。これにより、デッドコードを排除し、コードベースの保守性を向上させる。

#### Acceptance Criteria

1. **WHEN** 削除作業を開始する **THEN** テスト実行スクリプトは `test-utils/env.ts` ファイルが他のファイルから参照されていないことを確認する **SHALL** grep検索により参照がゼロであることを検証する

2. **WHEN** 参照がないことが確認された **THEN** テスト実行スクリプトは `test-utils/env.ts` ファイルを削除する **SHALL** ファイルシステムから完全に削除する

3. **IF** `test-utils/` ディレクトリに他のファイルが存在する **THEN** テスト実行スクリプトは `test-utils/` ディレクトリを保持する **SHALL** 他のユーティリティファイルを残す

4. **IF** `test-utils/` ディレクトリが空になる **THEN** テスト実行スクリプトは `test-utils/` ディレクトリも削除する **SHALL** 空のディレクトリを残さない

---

### Requirement 2: フロントエンド環境変数テストの削除

**Objective:** 開発者として、Admin AppとUser Appの環境変数テストファイル（`env.test.ts`）を削除したい。これにより、起動時バリデーションで代替される重複テストを排除する。

#### Acceptance Criteria

1. **WHEN** 環境変数テスト削除を実行する **THEN** テスト実行スクリプトは以下のファイルを削除する **SHALL**:
   - `frontend/admin-app/src/lib/__tests__/env.test.ts`
   - `frontend/user-app/src/lib/__tests__/env.test.ts`

2. **IF** `__tests__/` ディレクトリに他のテストファイルが存在する **THEN** テスト実行スクリプトは `__tests__/` ディレクトリを保持する **SHALL** 他のテストファイルを残す

3. **IF** `__tests__/` ディレクトリが空になる **THEN** テスト実行スクリプトは `__tests__/` ディレクトリも削除する **SHALL** 空のディレクトリを残さない

4. **WHILE** 削除後もアプリケーションが起動される間 **THE** 環境変数バリデーションシステムは **SHALL** `check-env.ts` による起動時バリデーションを正常に実行する

5. **WHEN** `npm run dev` または `npm run build` が実行される **THEN** Next.jsアプリケーションは **SHALL** Zodスキーマによる環境変数バリデーションを実行し、不正な値の場合はエラーを出力する

---

### Requirement 3: 環境変数同期スクリプトテストの簡素化

**Objective:** 開発者として、`scripts/__tests__/env-sync.test.ts` のテストケースを簡素化したい。これにより、必要最小限のテストを維持しながら保守コストを削減する。

#### Acceptance Criteria

1. **WHEN** env-sync.test.tsを簡素化する **THEN** テスト実行スクリプトは以下の3つの必須テストケースを残す **SHALL**:
   - `.env.example`から`.env`作成テスト
   - 不足キー検出テスト
   - 未知キー検出テスト

2. **WHEN** 簡素化後のテストが実行される **THEN** 全てのテストが成功する **SHALL** 3つのテストケースが全てパスする

3. **IF** 元のテストファイルに5つ以上のテストケースがある **THEN** テスト実行スクリプトは重複・冗長なテストケースを削除する **SHALL** コア機能のテストのみ保持する

---

### Requirement 4: CI/CDワークフロー調整

**Objective:** DevOps担当者として、CI/CDワークフローのpaths設定を調整したい。これにより、削除されたファイルへの参照を除去し、ワークフローの効率を維持する。

#### Acceptance Criteria

1. **WHEN** `test-utils/env.ts` が削除された **THEN** GitHub Actionsワークフローは **SHALL** `.github/workflows/frontend-test.yml` のpaths設定から `test-utils/**` パターンを削除する（他のtest-utilsファイルが残る場合は保持）

2. **IF** `test-utils/` ディレクトリが完全に削除された **THEN** GitHub Actionsワークフローは **SHALL** 全てのワークフローファイルから `test-utils/**` パターンを除去する

3. **WHILE** CI/CDパイプラインが実行される間 **THE** 環境変数検証ワークフロー（`env-validation.yml`）は **SHALL** 変更なく正常に動作する

4. **WHEN** Pull Requestが作成される **THEN** GitHub Actionsワークフローは **SHALL** 以下の検証を正常に実行する:
   - フロントエンドテスト（削除後のテストスイート）
   - 環境変数バリデーション
   - 本番ビルド検証

---

### Requirement 5: ドキュメント更新

**Objective:** 開発者として、テスト削除に伴うドキュメントを更新したい。これにより、環境変数テスト戦略の変更を正確に反映する。

#### Acceptance Criteria

1. **WHEN** テストファイルが削除された **THEN** ドキュメンテーションシステムは **SHALL** `frontend/TESTING_GUIDE.md` から `test-utils/env.ts` の使用例セクションを削除する

2. **WHEN** ドキュメントを更新する **THEN** ドキュメンテーションシステムは **SHALL** `frontend/TESTING_GUIDE.md` に環境変数バリデーションは起動時に自動実行されることを明記する

3. **IF** `.kiro/specs/environment-variable-management/design.md` が存在する **THEN** ドキュメンテーションシステムは **SHALL** テスト戦略セクションを「起動時バリデーション + CI/CD検証」が主な保証手段であることを反映して更新する

4. **WHEN** 全ての変更が完了した **THEN** ドキュメンテーションシステムは **SHALL** 環境変数テスト戦略の変更履歴を記録する

---

### Requirement 6: 削除後の品質保証

**Objective:** QA担当者として、テスト削除後も環境変数バリデーション機能が正常に動作することを確認したい。これにより、既存機能の品質低下を防ぐ。

#### Acceptance Criteria

1. **WHEN** 全てのテスト削除が完了した **THEN** テスト実行スクリプトは **SHALL** `npm test` で全テストスイートを実行し、全てパスすることを確認する

2. **WHEN** 開発サーバーを起動する **THEN** Next.jsアプリケーションは **SHALL** 環境変数バリデーションを正常に実行する:
   - `npm run dev` (admin-app): ポート13002で起動
   - `npm run dev` (user-app): ポート13001で起動

3. **WHEN** 手動検証コマンドを実行する **THEN** 環境変数同期スクリプトは **SHALL** `npm run env:check` で正常に動作する

4. **WHILE** CI/CDパイプラインが動作する間 **THE** 品質保証システムは **SHALL** 以下のLayer構成で環境変数を保証する:
   - Layer 1: 起動時バリデーション（`check-env.ts` + `env.ts` Zodスキーマ）
   - Layer 2: CI/CD自動検証（`env-validation.yml`）
   - Layer 3: 手動検証コマンド（`npm run env:check`）

5. **IF** テストカバレッジが低下する **THEN** 品質保証システムは **SHALL** `env.ts` のカバレッジ0%表示を許容する（起動時バリデーションで実質カバー）

---

### Requirement 7: ロールバック対応

**Objective:** 開発者として、削除後に問題が発生した場合にロールバックできるようにしたい。これにより、リスクを最小化する。

#### Acceptance Criteria

1. **IF** 削除後に問題が発生した **THEN** バージョン管理システムは **SHALL** Gitログから削除されたファイルを復元可能にする

2. **WHEN** 削除を実施する **THEN** コミットメッセージは **SHALL** 削除されたファイル一覧と削除理由を明記する

3. **IF** 将来的に環境変数テストが必要になった **THEN** 開発チームは **SHALL** 10行程度の再実装で対応可能である

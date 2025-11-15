# Requirements Document

## GitHub Issue Information

**Issue**: [#138](https://github.com/ef-tech/laravel-next-b2c/issues/138) - Refactor: Jestの--passWithNoTestsオプション削除（テスト実行の確実性向上）
**Labels**: refactoring, testing, frontend
**Milestone**: なし
**Assignees**: なし

---

## Introduction

本要件は、User App と Admin App の `package.json` に設定されている Jest の `--passWithNoTests` オプションを削除することで、テスト実行の確実性を向上させることを目的としています。

### 現状の問題

現在、両アプリケーションのテストスクリプトで `--passWithNoTests` オプションが有効化されており、以下の問題が発生しています：

1. **テストファイル削除の検知不可**: テストファイルが誤って削除されても CI が成功してしまう
2. **testMatch パターンの間違いを検知できない**: Jest設定の間違いに気付けない
3. **テストカバレッジの低下を検知できない**: テストファイル数の減少を検知できない

これらの問題により、BtoCテンプレートとしての信頼性が低下しています。

### ビジネス価値

- **品質保証の強化**: テストの存在を確実に検証することで、コード品質を維持
- **早期問題検知**: テスト設定やテストファイルの問題を即座に検出
- **テンプレート信頼性向上**: テンプレート利用者が安心してテストを運用できる環境を提供

---

## Requirements

### Requirement 1: package.json からの --passWithNoTests オプション削除

**Objective:** 開発者として、テストファイルが存在しない場合にCIが確実に失敗するように、`--passWithNoTests` オプションを削除したい。これにより、テスト実行の確実性が向上する。

#### Acceptance Criteria

1. WHEN User App の package.json を確認する THEN `test` スクリプトに `--passWithNoTests` オプションが含まれていない SHALL
2. WHEN Admin App の package.json を確認する THEN `test` スクリプトに `--passWithNoTests` オプションが含まれていない SHALL
3. WHEN User App の package.json を確認する THEN `test:coverage` スクリプトに `--passWithNoTests` オプションが含まれていない SHALL
4. WHEN Admin App の package.json を確認する THEN `test:coverage` スクリプトに `--passWithNoTests` オプションが含まれていない SHALL

### Requirement 2: 既存テストの実行確認

**Objective:** 開発者として、オプション削除後も既存のすべてのテストが正常に実行されることを確認したい。これにより、既存機能への影響がないことを保証する。

#### Acceptance Criteria

1. WHEN User App で `npm test` を実行する THEN 173個のテストが正常に実行される SHALL
2. WHEN Admin App で `npm test` を実行する THEN 212個のテストが正常に実行される SHALL
3. WHEN ルートディレクトリで `npm test` を実行する THEN 合計385個のテストが正常に実行される SHALL
4. WHEN 各アプリで `npm run test:coverage` を実行する THEN カバレッジレポートが正常に生成される SHALL

### Requirement 3: テストファイル不在時の失敗検証

**Objective:** 開発者として、テストファイルが見つからない場合にCIが確実に失敗することを確認したい。これにより、テストファイル削除や設定ミスを早期に検知できる。

#### Acceptance Criteria

1. IF User App のテストファイルがすべて削除される THEN `npm test` コマンドが非ゼロ終了コードで失敗する SHALL
2. IF Admin App のテストファイルがすべて削除される THEN `npm test` コマンドが非ゼロ終了コードで失敗する SHALL
3. IF jest.config.js の testMatch パターンが間違っている THEN `npm test` コマンドが非ゼロ終了コードで失敗する SHALL
4. WHEN テストファイルが見つからない状態で Jest を実行する THEN エラーメッセージ "No tests found" が表示される SHALL

### Requirement 4: CI/CD パイプラインの正常動作確認

**Objective:** DevOps担当者として、GitHub Actions ワークフローが正常に動作し、テスト失敗時に適切にCIが失敗することを確認したい。これにより、CI/CDの信頼性を維持する。

#### Acceptance Criteria

1. WHEN Pull Request を作成する AND すべてのテストが存在する THEN frontend-test.yml ワークフローが成功する SHALL
2. WHEN Pull Request を作成する AND テストファイルが削除されている THEN frontend-test.yml ワークフローが失敗する SHALL
3. WHEN main ブランチにマージする THEN すべての CI チェックが成功している SHALL
4. WHILE CI/CD パイプラインが実行される THE User App と Admin App のテストが並列実行される SHALL

### Requirement 5: ローカル開発環境での動作確認

**Objective:** 開発者として、ローカル環境でテストを実行する際にも同様の挙動が保証されることを確認したい。これにより、ローカルとCI環境の一貫性を保つ。

#### Acceptance Criteria

1. WHEN ローカル環境で `npm test` を実行する THEN CI環境と同じテスト結果が得られる SHALL
2. WHEN ローカル環境で `npm run test:watch` を実行する THEN ファイル変更時にテストが自動実行される SHALL
3. IF ローカル環境でテストファイルが見つからない THEN エラーメッセージが表示され、プロセスが非ゼロ終了コードで終了する SHALL
4. WHEN ルートディレクトリで `npm test` を実行する THEN 両アプリのテストが順次実行される SHALL

### Requirement 6: BtoCテンプレートとしての品質保証

**Objective:** テンプレート利用者として、テストの確実性が保証されたテンプレートを利用したい。これにより、テンプレート導入後の開発が安心して進められる。

#### Acceptance Criteria

1. WHEN テンプレートを新規プロジェクトに導入する THEN テスト実行の確実性が保証されている SHALL
2. IF 誤ってテストファイルを削除する THEN CI が即座に失敗し、開発者に通知される SHALL
3. WHEN テスト設定を変更する AND 設定が間違っている THEN ローカルテスト実行時にエラーが検出される SHALL
4. WHILE プロジェクト開発が進行する THE テストカバレッジの低下が確実に検知される SHALL

---

## Extracted Information

### Technology Stack
- **Frontend**: Jest 29, npm, package.json
- **Infrastructure**: GitHub Actions CI/CD
- **Tools**: Jest, npm scripts

### Project Structure
```
frontend/user-app/package.json
frontend/admin-app/package.json
jest.config.js
jest.base.js
.github/workflows/frontend-test.yml
```

### Current Test Count
- User App: 173 tests
- Admin App: 212 tests
- Total: 385 tests

### Reference Information
- 発見元: PR #136 のレビュー対応中に発見
- 関連Issue: #137（Close済み - 問題が存在しなかった）
- Jest公式ドキュメント: `--passWithNoTests` は開発初期やプロトタイプ向けのオプション

### Note
`--passWithNoTests` オプションは、以下のような限定的な用途で使用されるべきです：
- プロジェクト立ち上げ初期（テストファイルがまだ存在しない段階）
- テストを段階的に追加している途中のCI実行
- モノレポで一部のパッケージにテストがない場合

本プロジェクトは既に 385 tests のテストが存在するため、このオプションは不要です。

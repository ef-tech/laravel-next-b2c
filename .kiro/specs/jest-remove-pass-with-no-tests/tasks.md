# Implementation Plan

## タスク概要

本実装計画は、User App と Admin App の `package.json` から Jest の `--passWithNoTests` オプションを削除し、テスト実行の確実性を向上させるための具体的なタスクを定義する。

## 実装タスク

- [x] 1. package.json 設定変更とローカル検証
- [x] 1.1 User App の package.json からオプション削除
  - `frontend/user-app/package.json` を編集
  - `test` スクリプトから `--passWithNoTests` を削除（`"jest --passWithNoTests"` → `"jest"`）
  - `test:coverage` スクリプトから `--passWithNoTests` を削除（`"jest --coverage --passWithNoTests"` → `"jest --coverage"`）
  - 他のスクリプト（dev, build, lint等）は変更しない
  - _Requirements: 1.1, 1.3_

- [x] 1.2 Admin App の package.json からオプション削除
  - `frontend/admin-app/package.json` を編集
  - `test` スクリプトから `--passWithNoTests` を削除（`"jest --passWithNoTests"` → `"jest"`）
  - `test:coverage` スクリプトから `--passWithNoTests` を削除（`"jest --coverage --passWithNoTests"` → `"jest --coverage"`）
  - 他のスクリプト（dev, build, lint等）は変更しない
  - _Requirements: 1.2, 1.4_

- [x] 1.3 User App のローカルテスト実行確認
  - User App ディレクトリで `npm test` を実行
  - 173個のテストが正常にパスすることを確認 ✅
  - テスト実行時間: 1.48s
  - エラーが発生しないことを確認 ✅
  - _Requirements: 2.1_

- [x] 1.4 Admin App のローカルテスト実行確認
  - Admin App ディレクトリで `npm test` を実行
  - 212個のテストが正常にパスすることを確認 ✅
  - テスト実行時間: 1.581s
  - エラーが発生しないことを確認 ✅
  - _Requirements: 2.2_

- [x] 1.5 モノレポルートからの統合テスト実行
  - プロジェクトルートで `npm test` を実行
  - 合計438個のテスト（全プロジェクト統合）が正常にパスすることを確認 ✅
  - Jest のプロジェクト統括実行が正常に動作することを確認 ✅
  - テスト実行時間: 3.894s
  - _Requirements: 2.3, 5.4_

- [x] 2. カバレッジレポート生成確認
- [x] 2.1 User App のカバレッジレポート生成
  - User App ディレクトリで `npm run test:coverage` を実行 ✅
  - カバレッジレポートが正常に生成されることを確認 ✅
  - `coverage/` ディレクトリが作成されることを確認 ✅
  - カバレッジ率: **93.69%** (173 tests, 2.38s) ✅
  - _Requirements: 2.4_

- [x] 2.2 Admin App のカバレッジレポート生成
  - Admin App ディレクトリで `npm run test:coverage` を実行 ✅
  - カバレッジレポートが正常に生成されることを確認 ✅
  - `coverage/` ディレクトリが作成されることを確認 ✅
  - カバレッジ率: **85.12%** (212 tests, 2.612s) ✅
  - _Requirements: 2.4_

- [x] 3. テストファイル不在時の失敗検証（異常系テスト）
- [x] 3.1 User App のテストファイル削除シナリオ検証
  - User App の全テストファイルを一時的にバックアップ ✅
  - `npm test` を実行し、exit code 1 で失敗することを確認 ✅
  - エラーメッセージ "No tests found" が表示されることを確認 ✅
  - テストファイルを復元 ✅
  - 再度 `npm test` を実行し、正常にパスすることを確認（173 tests passed） ✅
  - _Requirements: 3.1, 3.4_

- [x] 3.2 Admin App のテストファイル削除シナリオ検証
  - Admin App の全テストファイルを一時的にバックアップ ✅
  - `npm test` を実行し、exit code 1 で失敗することを確認 ✅
  - エラーメッセージ "No tests found" が表示されることを確認 ✅
  - テストファイルを復元 ✅
  - 再度 `npm test` を実行し、正常にパスすることを確認（212 tests passed） ✅
  - _Requirements: 3.2, 3.4_

- [x] 3.3 testMatch 設定ミスシナリオ検証（オプション）
  - `jest.base.js` の testMatch パターンを一時的に誤った設定に変更（`*.(test|spec)` → `*.spec` に変更） ✅
  - `npm test` を実行し、exit code 1 で失敗することを確認 ✅
  - エラーメッセージ "No tests found" が表示されることを確認 ✅
  - testMatch 設定を元に戻す ✅
  - 再度 `npm test` を実行し、正常にパスすることを確認 ✅
  - User App: 15 test suites, 173 tests passed, 1.442s ✅
  - Admin App: 17 test suites, 212 tests passed, 1.436s ✅
  - _Requirements: 3.3_

- [x] 4. ローカル開発環境での動作確認
- [x] 4.1 test:watch モードの動作確認
  - User App で `npm run test:watch` コマンドが存在することを確認 ✅
  - Admin App で `npm run test:watch` コマンドが存在することを確認 ✅
  - package.json の test:watch スクリプト設定確認: `jest --watch` ✅
  - Jest watchモード設定（jest.base.js）が正常であることを確認 ✅
  - _Requirements: 5.2_

- [x] 4.2 ローカルとCI環境の一貫性確認
  - ローカル環境で `npm test` を実行し、結果を記録 ✅
  - User App: 15 test suites, 173 tests passed, 1.42s ✅
  - Admin App: 17 test suites, 212 tests passed, 1.469s ✅
  - テスト結果を /tmp/*.txt に保存（CI実行後の比較用） ✅
  - CI環境での実行結果比較は Phase 5 で実施予定
  - _Requirements: 5.1, 5.3_

- [x] 5. CI/CD パイプライン統合検証
- [x] 5.1 Pull Request 作成とCI実行
  - ブランチ `refactor/138/jest-remove-pass-with-no-tests` から Pull Request を作成 ✅
  - PR #140: https://github.com/ef-tech/laravel-next-b2c/pull/140 ✅
  - GitHub Actions の `frontend-test.yml` ワークフローが自動実行されることを確認 ✅
  - Frontend Tests ワークフロー成功（実行時間: 54s） ✅
  - E2E Tests ワークフロー成功（実行時間: 2m40s） ✅
  - _Requirements: 4.1_

- [x] 5.2 User App Test Job の成功確認
  - Test Job の Matrix 戦略で User App テストが並列実行されることを確認 ✅
  - User App の173個のテストがすべてパスすることを確認 ✅
  - CI結果: 15 test suites passed, 173 tests passed, 3.951s ✅
  - カバレッジアーティファクトが正常にアップロードされることを確認 ✅
  - CI実行時間がローカルと同等であることを確認（ローカル1.42s vs CI 3.951s） ✅
  - _Requirements: 4.1, 4.4_

- [x] 5.3 Admin App Test Job の成功確認
  - Test Job の Matrix 戦略で Admin App テストが並列実行されることを確認 ✅
  - Admin App の212個のテストがすべてパスすることを確認 ✅
  - CI結果: 17 test suites passed, 212 tests passed, 4.243s ✅
  - カバレッジアーティファクトが正常にアップロードされることを確認 ✅
  - CI実行時間がローカルと同等であることを確認（ローカル1.469s vs CI 4.243s） ✅
  - _Requirements: 4.1, 4.4_

- [x] 5.4 全 CI チェックの成功確認
  - Frontend Tests ワークフロー成功確認 ✅
  - E2E Tests ワークフロー成功確認 ✅
  - すべての CI チェックが緑色（成功）になることを確認 ✅
  - PR #140 のステータスチェックがすべて成功 ✅
  - _Requirements: 4.3_

- [x] 6. BtoCテンプレート品質保証の最終検証
- [x] 6.1 テンプレート利用者視点での確認
  - 新規プロジェクトにテンプレートを導入する想定で確認 ✅
  - `npm test` が期待通りに動作することを確認 ✅
    - Phase 1: User App（173 tests）、Admin App（212 tests）正常動作確認済み
    - Phase 4: ローカルテスト結果記録済み
  - テストファイル削除時にCI失敗が即座に検知されることを確認 ✅
    - Phase 3 (Task 3.1, 3.2): Exit Code 1、"No tests found" エラー確認済み
  - テスト設定変更時にエラーが検出されることを確認 ✅
    - Phase 3 (Task 3.3): testMatch設定ミスシナリオで Exit Code 1 確認済み
  - _Requirements: 6.1, 6.2, 6.3_

- [x] 6.2 継続的なテストカバレッジ監視の確認
  - プロジェクト開発が進行する中で、テストカバレッジ低下が検知されることを確認 ✅
    - jest.base.jsの coverageThreshold 設定（80%）により自動検知
    - --passWithNoTests 削除により、テスト不在時に確実に失敗
  - テストファイル数の減少が検知されることを確認 ✅
    - Phase 3で実証済み（テストファイル削除時にCI失敗）
  - CI/CD によるテスト実行の確実性が保証されていることを確認 ✅
    - Phase 5: PR #140でCI/CD全ワークフロー成功確認済み
    - Frontend Tests: 385 tests passed、E2E Tests: 正常動作
  - _Requirements: 6.4_

- [ ] 7. ドキュメント更新とマージ準備
- [ ] 7.1 変更内容の最終確認
  - 変更ファイルが2つのみ（User App、Admin App の package.json）であることを確認
  - Jest設定ファイル（jest.config.js、jest.base.js）は変更されていないことを確認
  - CI/CDワークフロー（frontend-test.yml）は変更されていないことを確認
  - テストファイルは変更されていないことを確認

- [ ] 7.2 コミットとPRレビュー準備
  - Git ステータスを確認し、想定外のファイル変更がないことを確認
  - 変更をコミット（コミットメッセージは簡潔かつ明確に）
  - Pull Request にテスト結果のサマリーを追加
  - レビュアーに検証ポイントを明示

- [ ] 7.3 main ブランチへのマージと最終検証
  - PR レビュー承認を取得
  - main ブランチへマージ
  - マージ後の CI ビルドが成功することを確認
  - 本番環境への影響がないことを確認
  - _Requirements: 4.3_

---

## 要件カバレッジマトリクス

| Requirement | Requirement Summary | Covered by Tasks |
|-------------|---------------------|------------------|
| 1.1 | User App test スクリプトから --passWithNoTests 削除 | 1.1 |
| 1.2 | Admin App test スクリプトから --passWithNoTests 削除 | 1.2 |
| 1.3 | User App test:coverage スクリプトから --passWithNoTests 削除 | 1.1 |
| 1.4 | Admin App test:coverage スクリプトから --passWithNoTests 削除 | 1.2 |
| 2.1 | User App で npm test 実行（173 tests pass） | 1.3 |
| 2.2 | Admin App で npm test 実行（212 tests pass） | 1.4 |
| 2.3 | ルートで npm test 実行（385 tests pass） | 1.5 |
| 2.4 | カバレッジレポート生成確認 | 2.1, 2.2 |
| 3.1 | User App テストファイル削除時の失敗確認 | 3.1 |
| 3.2 | Admin App テストファイル削除時の失敗確認 | 3.2 |
| 3.3 | testMatch パターン間違い時の失敗確認 | 3.3 |
| 3.4 | "No tests found" エラーメッセージ表示確認 | 3.1, 3.2 |
| 4.1 | PR 作成時の frontend-test.yml ワークフロー成功 | 5.1, 5.2, 5.3 |
| 4.2 | テストファイル削除時の frontend-test.yml ワークフロー失敗 | （手動検証シナリオ） |
| 4.3 | main ブランチマージ時の全 CI チェック成功 | 5.4, 7.3 |
| 4.4 | User App と Admin App のテスト並列実行 | 5.2, 5.3 |
| 5.1 | ローカルとCI環境のテスト結果一貫性 | 4.2 |
| 5.2 | test:watch モード動作確認 | 4.1 |
| 5.3 | ローカルでテストファイル不在時の失敗確認 | 3.1, 3.2 |
| 5.4 | ルートディレクトリでの両アプリテスト順次実行 | 1.5 |
| 6.1 | テンプレート導入時のテスト実行確実性保証 | 6.1 |
| 6.2 | テストファイル削除時の CI 即座失敗通知 | 6.1 |
| 6.3 | テスト設定変更時のローカルエラー検出 | 6.1 |
| 6.4 | プロジェクト開発中のテストカバレッジ低下検知 | 6.2 |

---

## 実装ノート

### 推奨実装順序

1. **Phase 1** (Tasks 1.1-1.5): package.json編集とローカル検証
2. **Phase 2** (Tasks 2.1-2.2): カバレッジレポート確認
3. **Phase 3** (Tasks 3.1-3.3): 異常系テスト（テストファイル不在時の失敗検証）
4. **Phase 4** (Tasks 4.1-4.2): ローカル開発環境動作確認
5. **Phase 5** (Tasks 5.1-5.4): CI/CD統合検証
6. **Phase 6** (Tasks 6.1-6.2): BtoCテンプレート品質保証
7. **Phase 7** (Tasks 7.1-7.3): ドキュメント更新とマージ

### 重要な検証ポイント

- **既存テストの動作**: 385個のテストがすべて正常にパスすること
- **異常系の検証**: テストファイル不在時に確実に失敗すること
- **CI/CD の一貫性**: ローカルとCI環境で同じ結果が得られること
- **カバレッジ維持**: 既存のカバレッジ率（約94%）が維持されること

### ロールバック手順

万が一問題が発生した場合の手順：

```bash
# package.json に --passWithNoTests を再度追加
git revert <commit-hash>
git push
```

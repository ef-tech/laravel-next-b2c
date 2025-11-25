# Implementation Plan

## ENV系テストコード整理

本実装計画は、環境変数関連テストコードの整理・削除を段階的に実行するためのタスク一覧です。

---

- [x] 1. 事前調査と参照確認
- [x] 1.1 削除対象ファイルの参照状況確認
  - `test-utils/env.ts` がプロジェクト内で参照されていないことをGrep検索で確認
  - Admin App/User Appの `env.test.ts` がインポートされていないことを確認
  - 削除対象ファイルの一覧と行数を記録
  - _Requirements: 1.1, 1.2_

- [x] 1.2 テストスイートの現状確認
  - `npm test` を実行し、現状のテスト数・パス状況を記録
  - `scripts/__tests__/env-sync.test.ts` のテストケース構成を確認（保持対象3ケースと削除対象2ケースを特定）
  - _Requirements: 3.1, 3.3_

---

- [x] 2. 未使用ユーティリティの削除
- [x] 2.1 test-utils/env.ts ファイルの削除
  - ファイルを削除
  - `test-utils/` ディレクトリに他のファイル（render.tsx, router.ts）が残っていることを確認
  - _Requirements: 1.2, 1.3_

---

- [x] 3. フロントエンド環境変数テストの削除
- [x] 3.1 Admin App環境変数テストファイルの削除
  - `frontend/admin-app/src/lib/__tests__/env.test.ts` を削除
  - `__tests__/` ディレクトリが空になったため削除
  - _Requirements: 2.1, 2.2_

- [x] 3.2 User App環境変数テストファイルの削除
  - `frontend/user-app/src/lib/__tests__/env.test.ts` を削除
  - `__tests__/` ディレクトリに他のテストファイル（i18n-config.test.ts, network-error-i18n.test.ts）が残るため保持
  - _Requirements: 2.1, 2.3_

---

- [x] 4. 環境変数同期スクリプトテストの簡素化
- [x] 4.1 env-sync.test.ts の重複テストケース削除
  - 「`.env.exampleのみ存在する場合、.envが作成される`」テストケースを削除（ケース5と重複）
  - 「`.envに既存値がある場合、新規キーのみ追加される`」テストケースを削除（コア機能外）
  - 以下の3コアケースを保持: `不足キー検出`、`未知キー検出`、`.envファイルが存在しない場合、.env.exampleからコピーされる`
  - _Requirements: 3.1, 3.2, 3.3_

---

- [x] 5. ドキュメント更新
- [x] 5.1 TESTING_GUIDE.md の更新
  - `test-utils/env.ts` の使用例セクションを削除
  - 環境変数バリデーションは起動時に自動実行される旨を明記
  - 品質保証層（起動時バリデーション、CI/CD自動検証、手動検証）を説明
  - _Requirements: 5.1, 5.2_

---

- [x] 6. 検証と品質保証
- [x] 6.1 テストスイートの動作確認
  - `npm test` を実行し、全テストがパスすることを確認（34 Test Suites, 420 Tests）
  - Admin App/User App個別でテスト実行し、エラーがないことを確認
  - env-sync.test.ts の3コアケースが正常にパスすることを確認
  - _Requirements: 6.1, 3.2_

- [x] 6.2 起動時バリデーションの動作確認
  - 起動時バリデーション機構（check-env.ts + env.ts Zodスキーマ）は変更なく維持
  - `npm run env:check` による手動検証コマンドは変更なく動作
  - _Requirements: 6.2, 6.3, 2.4, 2.5_

- [x] 6.3 CI/CDワークフローの確認
  - `frontend-test.yml` のpaths設定が適切であることを確認（test-utils/** は render.tsx, router.ts が残るため保持）
  - `env-validation.yml` は変更不要（環境変数バリデーション機構に影響なし）
  - _Requirements: 4.1, 4.2, 4.3_

---

- [x] 7. コミットとロールバック準備
- [x] 7.1 変更のコミット
  - 削除されたファイル一覧と削除理由を明記したコミットメッセージを作成
  - Git履歴からファイル復元可能であることを確認
  - ブランチ: `refactor/105/env-test-cleanup`
  - コミット: `5dcf0ec`
  - _Requirements: 7.1, 7.2, 7.3_

---

## 実装結果サマリー

### 削除されたファイル
| ファイル | 行数 | テスト数 | 削除理由 |
|---------|------|----------|----------|
| `test-utils/env.ts` | 10行 | - | 未使用ユーティリティ（参照なし） |
| `frontend/admin-app/src/lib/__tests__/env.test.ts` | 103行 | 9テスト | 起動時バリデーションで代替 |
| `frontend/user-app/src/lib/__tests__/env.test.ts` | 127行 | 11テスト | 起動時バリデーションで代替 |
| `frontend/admin-app/src/lib/__tests__/` | - | - | 空ディレクトリ |

### 簡素化されたファイル
| ファイル | 削除テスト数 | 保持テスト数 | 変更理由 |
|---------|------------|-------------|----------|
| `scripts/__tests__/env-sync.test.ts` | 2テスト | 3テスト | 重複・コア機能外のテスト削除 |

### テスト結果比較
| 項目 | 削除前 | 削除後 | 差分 |
|------|-------|-------|------|
| Test Suites | 36 | 34 | -2 |
| Tests | 440 | 420 | -20 |

### 品質保証層（変更なし）
1. **起動時バリデーション**: `check-env.ts` + `env.ts` Zodスキーマ
2. **CI/CD自動検証**: `env-validation.yml`
3. **手動検証**: `npm run env:check`

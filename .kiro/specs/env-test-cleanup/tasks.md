# Implementation Plan

## ENV系テストコード整理

本実装計画は、環境変数関連テストコードの整理・削除を段階的に実行するためのタスク一覧です。

---

- [ ] 1. 事前調査と参照確認
- [ ] 1.1 削除対象ファイルの参照状況確認
  - `test-utils/env.ts` がプロジェクト内で参照されていないことをGrep検索で確認
  - Admin App/User Appの `env.test.ts` がインポートされていないことを確認
  - 削除対象ファイルの一覧と行数を記録
  - _Requirements: 1.1, 1.2_

- [ ] 1.2 テストスイートの現状確認
  - `npm test` を実行し、現状のテスト数・パス状況を記録
  - `scripts/__tests__/env-sync.test.ts` のテストケース構成を確認（保持対象3ケースと削除対象2ケースを特定）
  - _Requirements: 3.1, 3.3_

---

- [ ] 2. 未使用ユーティリティの削除
- [ ] 2.1 test-utils/env.ts ファイルの削除
  - ファイルを削除
  - `test-utils/` ディレクトリに他のファイル（render.tsx, router.ts）が残っていることを確認
  - _Requirements: 1.2, 1.3_

---

- [ ] 3. フロントエンド環境変数テストの削除
- [ ] 3.1 Admin App環境変数テストファイルの削除
  - `frontend/admin-app/src/lib/__tests__/env.test.ts` を削除
  - `__tests__/` ディレクトリに他のテストファイルがある場合はディレクトリを保持
  - _Requirements: 2.1, 2.2_

- [ ] 3.2 User App環境変数テストファイルの削除
  - `frontend/user-app/src/lib/__tests__/env.test.ts` を削除
  - `__tests__/` ディレクトリに他のテストファイルがある場合はディレクトリを保持
  - _Requirements: 2.1, 2.3_

---

- [ ] 4. 環境変数同期スクリプトテストの簡素化
- [ ] 4.1 env-sync.test.ts の重複テストケース削除
  - 「`.env.exampleのみ存在する場合、.envが作成される`」テストケースを削除（ケース5と重複）
  - 「`.envに既存値がある場合、新規キーのみ追加される`」テストケースを削除（コア機能外）
  - 以下の3コアケースを保持: `.env.exampleから.env作成`、`不足キー検出`、`未知キー検出`
  - _Requirements: 3.1, 3.2, 3.3_

---

- [ ] 5. ドキュメント更新
- [ ] 5.1 TESTING_GUIDE.md の更新
  - `test-utils/env.ts` の使用例セクションを削除
  - 環境変数バリデーションは起動時に自動実行される旨を明記
  - 削除されたテストファイルへの参照を削除
  - _Requirements: 5.1, 5.2_

---

- [ ] 6. 検証と品質保証
- [ ] 6.1 テストスイートの動作確認
  - `npm test` を実行し、全テストがパスすることを確認
  - Admin App/User App個別でテスト実行し、エラーがないことを確認
  - env-sync.test.ts の3コアケースが正常にパスすることを確認
  - _Requirements: 6.1, 3.2_

- [ ] 6.2 起動時バリデーションの動作確認
  - Admin App (`npm run dev` ポート13002) の起動確認
  - User App (`npm run dev` ポート13001) の起動確認
  - `npm run env:check` による手動検証コマンドの動作確認
  - _Requirements: 6.2, 6.3, 2.4, 2.5_

- [ ] 6.3 CI/CDワークフローの確認
  - `frontend-test.yml` のpaths設定が適切であることを確認（test-utils/** は他ファイルが残るため保持）
  - `env-validation.yml` が変更なく正常動作することを確認
  - _Requirements: 4.1, 4.2, 4.3_

---

- [ ] 7. コミットとロールバック準備
- [ ] 7.1 変更のコミット
  - 削除されたファイル一覧と削除理由を明記したコミットメッセージを作成
  - Git履歴からファイル復元可能であることを確認
  - _Requirements: 7.1, 7.2, 7.3_

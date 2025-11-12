# 実装計画

## 概要

User AppとAdmin Appの`validLocale`型定義を統一し、next-intl公式型定義（`RequestConfig.locale: string`）に準拠した実装にする。Admin Appの`string`型実装パターンに統一し、コードベース全体の一貫性を向上させる。

**変更範囲**: `frontend/user-app/src/i18n.ts`の型定義のみ（1ファイル、1行の変更）

---

## 実装タスク

- [x] 1. User App i18n設定の型定義変更
- [x] 1.1 User App i18n.tsファイルの型定義を変更
  - `validLocale`変数の型定義を`Locale`型から`string`型に変更
  - 型キャストを`as Locale`から`as string`に変更
  - コードの実行時動作は変更せず、型定義のみ調整
  - _Requirements: 1.1, 1.4, 2.1_

- [x] 1.2 共通i18n設定の不変性を確認
  - `frontend/lib/i18n-config.ts`が変更されていないことを確認
  - `locales`配列、`defaultLocale`、`Locale`型定義が既存のまま維持されていることを検証
  - _Requirements: 8.1, 8.2_

- [x] 1.3 Admin App実装との一貫性を確認
  - User AppとAdmin Appの`i18n.ts`実装を比較
  - 両アプリで`validLocale: string`型定義が一致していることを確認
  - ロケール検証ロジックが同一パターンで実装されていることを検証
  - _Requirements: 9.1, 9.2, 9.3_

- [x] 2. TypeScript型チェック検証
- [x] 2.1 ワークスペース全体の型チェック実行
  - `npm run type-check`を実行
  - TypeScript型推論エラーがないことを確認
  - 型定義の整合性を検証
  - _Requirements: 3.1_

- [x] 2.2 Admin App個別の型チェック実行
  - `cd frontend/admin-app && npm run type-check`を実行
  - Admin App固有の型推論エラーがないことを確認
  - _Requirements: 3.2_

- [x] 2.3 User App個別の型チェック実行
  - `cd frontend/user-app && npm run type-check`を実行
  - User App固有の型推論エラーがないことを確認
  - 変更後の型定義が正しく推論されることを検証
  - _Requirements: 3.3_

- [x] 3. 本番ビルド検証
- [x] 3.1 Admin App本番ビルド実行
  - `cd frontend/admin-app && npm run build`を実行
  - ビルドエラーがないことを確認
  - ビルド時間が1-3分で完了することを確認
  - _Requirements: 4.1, 4.4_

- [x] 3.2 User App本番ビルド実行
  - `cd frontend/user-app && npm run build`を実行
  - ビルドエラーがないことを確認
  - ビルド時間が1-3分で完了することを確認
  - 型定義変更後のビルド成功を検証
  - _Requirements: 4.2, 4.4_

- [x] 4. 単体テスト検証
- [x] 4.1 フロントエンド全体の単体テスト実行
  - `npm test`を実行
  - 全テストがpassすることを確認
  - テストカバレッジが維持されることを確認
  - _Requirements: 5.1_

- [x] 4.2 Admin App単体テスト実行
  - `npm run test:admin`を実行
  - Admin App単体テストが全passすることを確認
  - i18n設定ロジックが正常動作することを検証
  - _Requirements: 5.2, 5.4_

- [x] 4.3 User App単体テスト実行
  - `npm run test:user`を実行
  - User App単体テストが全passすることを確認
  - ロケール検証ロジックが正常動作することを検証
  - エラーメッセージ多言語化が正常動作することを確認
  - _Requirements: 5.3, 5.4_

- [ ] 5. E2Eテスト検証
- [ ] 5.1 Docker環境起動と健全性確認
  - `make dev`でDocker環境を起動
  - `docker compose ps`で全サービスがhealthyステータスになることを確認
  - user-app (13001)、admin-app (13002)、laravel-api (13000)が正常起動することを検証
  - _Requirements: 6.1_

- [ ] 5.2 デフォルトロケール動作確認
  - `/ja/...`または`/`でアクセス
  - 日本語コンテンツが表示されることを確認
  - デフォルトロケールのフォールバック動作を検証
  - _Requirements: 6.2_

- [ ] 5.3 英語ロケール動作確認
  - `/en/...`でアクセス
  - 英語コンテンツが表示されることを確認
  - ロケール切替機能が正常動作することを検証
  - _Requirements: 6.3_

- [ ] 5.4 不正ロケールのフォールバック確認
  - `/invalid/...`でアクセス
  - デフォルトロケール（ja）にフォールバックすることを確認
  - 日本語コンテンツが表示されることを検証
  - ロケール検証ロジックが正常動作することを確認
  - _Requirements: 6.4_

- [ ] 6. CI/CD自動検証
- [ ] 6.1 PRブランチ作成
  - `git checkout -b feature/131/i18n-type-unification`でブランチ作成（既存）
  - 変更をコミット
  - GitHubリモートにpush
  - _Requirements: 7.1_

- [ ] 6.2 GitHub Actions lint検証確認
  - `.github/workflows/frontend-test.yml`ワークフローが自動実行されることを確認
  - `lint / user-app (20.x)`ジョブがGreenステータスになることを確認
  - ESLint静的解析が成功することを検証
  - _Requirements: 7.2_

- [ ] 6.3 GitHub Actions test検証確認
  - `test / user-app (20.x)`ジョブがGreenステータスになることを確認
  - Jest単体テストが成功することを検証
  - カバレッジレポートが生成されることを確認
  - _Requirements: 7.3_

- [ ] 6.4 GitHub Actions build検証確認
  - `build / user-app (20.x)`ジョブがGreenステータスになることを確認
  - TypeScript型チェックが成功することを検証
  - Next.js本番ビルドが成功することを確認
  - _Requirements: 7.4_

- [ ] 7. PR作成とフィードバック
- [ ] 7.1 PR作成
  - GitHub上でPull Requestを作成
  - タイトル: `✅ i18n型定義統一（User App/Admin App validLocale型をstring型に統一）`
  - 説明: 変更内容、検証結果サマリー、関連Issue #131へのリンクを記載
  - レビュアーを指定
  - _Requirements: 10.1, 10.2_

- [ ] 7.2 PR #129へのフィードバック報告
  - PR #129にコメントを投稿
  - 対応完了の報告（User App `validLocale`型定義を`string`型に統一）
  - 全検証結果のサマリー（型チェック、ビルド、テスト、CI/CD）を記載
  - 作成したPRへのリンクを含める
  - _Requirements: 10.1, 10.2_

- [ ] 7.3 コードレビュー対応
  - レビュアーからの指摘事項を確認
  - 必要に応じて修正を実施
  - 再検証（型チェック、ビルド、テスト）を実行
  - レビュアー承認を取得
  - _Requirements: 10.3_

- [ ] 8. マージと完了
- [ ] 8.1 最終動作確認
  - CI/CD全ジョブがGreenステータスであることを確認
  - レビュアー承認（1名以上）を確認
  - マージ前の最終チェックリスト実施
  - _Requirements: All_

- [ ] 8.2 mainブランチへのマージ
  - Pull Requestをmainブランチにマージ
  - マージ後のCI/CD自動実行を確認
  - マージコミットが正常に取り込まれることを検証
  - _Requirements: All_

---

## 実装順序の根拠

1. **Phase 1 (Task 1)**: コア変更の実施
   - User App i18n.tsの型定義変更（最も重要な変更）
   - 共通i18n設定とAdmin App実装の確認（変更なしの保証）

2. **Phase 2 (Task 2)**: TypeScript型安全性検証
   - ワークスペース全体の型チェック（全体的な型整合性確認）
   - Admin/User App個別の型チェック（詳細な型推論検証）

3. **Phase 3 (Task 3)**: 本番ビルド検証
   - Admin/User App本番ビルド（実際のビルド成功確認）
   - ビルド時間測定（パフォーマンス維持確認）

4. **Phase 4 (Task 4)**: 機能正確性検証
   - フロントエンド単体テスト（ロジックレベルの動作確認）
   - Admin/User App個別テスト（詳細な機能検証）

5. **Phase 5 (Task 5)**: エンドツーエンド検証
   - Docker環境起動（実際の実行環境での動作確認）
   - ロケール切替動作確認（実際のユーザーシナリオ検証）

6. **Phase 6 (Task 6)**: CI/CD自動検証
   - GitHub Actions実行（PRワークフロー自動実行）
   - lint/test/build全ジョブ確認（品質保証の最終確認）

7. **Phase 7 (Task 7)**: PR作成とレビュー
   - PR作成とフィードバック報告（対外的なコミュニケーション）
   - コードレビュー対応（品質向上とナレッジ共有）

8. **Phase 8 (Task 8)**: 完了処理
   - 最終動作確認とマージ（本番環境への反映準備）

---

## 成功基準チェックリスト

### 機能成功基準
- [ ] User Appの`validLocale`型定義が`string`型に変更完了
- [ ] Admin Appの`validLocale`型定義が`string`型のまま維持
- [ ] TypeScript型チェックが全プロジェクトでpass
- [ ] 本番ビルドがAdmin/User App両方で成功
- [ ] 単体テストが全pass
- [ ] E2Eテストでロケール切替動作が正常

### 品質成功基準
- [ ] GitHub Actions全job（lint、test、build）がGreen
- [ ] コードレビュー完了（レビュアー承認1名以上）
- [ ] リグレッションテスト実行（機能検証）
- [ ] パフォーマンス検証（ビルド時間・実行時パフォーマンス）

### 完了基準
- [ ] PR #129へのフィードバック報告完了
- [ ] mainブランチへのマージ完了

---

## リスク管理

### 想定されるリスクと対応策

| リスク | 発生確率 | 影響度 | 緩和策 | 対応タスク |
|--------|----------|--------|--------|-----------|
| TypeScript型推論エラー | 低 | 中 | Admin App実装パターン踏襲、型チェック実行 | Task 2 |
| 本番ビルド失敗 | 極低 | 高 | Admin Appで既に検証済み、ローカルビルド確認 | Task 3 |
| 単体テスト失敗 | 低 | 中 | i18n設定ロジック変更なし、既存テスト維持 | Task 4 |
| E2Eテストでロケール切替異常 | 極低 | 高 | 実行時動作不変、ロケール検証ロジック維持 | Task 5 |
| CI/CDジョブ失敗 | 低 | 中 | GitHub Actionsログ分析、失敗原因修正 | Task 6 |

---

## 注意事項

### 実装時の重要ポイント
1. **型定義のみ変更**: ロケール検証ロジック、メッセージロードロジックは変更しない
2. **Admin Appは変更なし**: 既存の`string`型実装を維持（参照実装）
3. **共通i18n設定は変更なし**: `frontend/lib/i18n-config.ts`は既存のまま維持
4. **実行時動作は同一**: `validLocale`の実行時値は常に`'ja' | 'en'`で不変

### ロールバックトリガー
以下のいずれかが発生した場合、変更をrevert:
1. TypeScript型チェック失敗
2. 本番ビルド失敗
3. 単体テスト失敗
4. E2Eテスト失敗（ロケール切替動作異常）
5. GitHub Actions CI/CDジョブ失敗

### 推定作業時間
- **Phase 1-2**: 15分（コード変更 + 型チェック）
- **Phase 3-4**: 15分（ビルド + 単体テスト）
- **Phase 5**: 10分（E2Eテスト）
- **Phase 6**: 5-10分（CI/CD検証）
- **Phase 7**: 10-15分（PR作成 + フィードバック）
- **Phase 8**: 5分（マージ）
- **Total**: 約60-70分（レビュー時間除く）

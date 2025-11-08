# 実装タスク

## 実装計画

- [x] 1. 共通モジュールの作成と型定義の整備
- [x] 1.1 `frontend/lib/global-error-messages.ts`の作成と静的メッセージ辞書の実装
  - User App `global-error.tsx`から既存の静的メッセージ辞書（messages）をコピー
  - 型定義（`Locale`、`GlobalErrorMessages`）を明示的にexport
  - `as const`型アサーションを維持し、TypeScript型推論を保証
  - JSDocコメントでモジュールの目的と使用方法を記述
  - 4カテゴリ（network, boundary, validation, global）× 2言語（ja, en）の完全な実装
  - _要件: 1.1, 1.2, 1.4, 1.5, 2.1, 2.4, 7.1_

- [x] 1.2 TypeScriptコンパイル確認と型安全性の検証
  - モノレポルートで`npm run type-check`を実行
  - 共通モジュールの型定義が正しく認識されることを確認
  - `Locale`型と`GlobalErrorMessages`型の型推論を検証
  - _要件: 2.2, 2.3, 2.5_

- [x] 2. User App Global Error Boundaryの共通モジュール統合
- [x] 2.1 User App `global-error.tsx`へのimport文追加とローカル定義削除
  - `import { globalErrorMessages, type Locale } from '@/../../lib/global-error-messages'`を追加
  - ローカル`messages`定数定義（約85行）を完全削除
  - ローカル`type Locale`定義を削除
  - `const t = messages[locale]`を`const t = globalErrorMessages[locale]`に変更
  - `detectLocale()`関数とその他のロジックは変更せず維持
  - _要件: 1.3, 3.1, 3.3, 3.5, 3.6_

- [x] 2.2 User Appの全27テスト実行と検証
  - `cd frontend/user-app && npm test src/app/__tests__/global-error.test.tsx`を実行
  - 全27テストがpassすることを確認
  - テスト失敗時は共通モジュールまたはimportロジックを修正
  - _要件: 5.1, 5.3, 5.4, 5.5, 5.6_

- [x] 3. Admin App Global Error Boundaryの共通モジュール統合
- [x] 3.1 Admin App `global-error.tsx`へのimport文追加とローカル定義削除
  - `import { globalErrorMessages, type Locale } from '@/../../lib/global-error-messages'`を追加
  - ローカル`messages`定数定義（約85行）を完全削除
  - ローカル`type Locale`定義を削除
  - `const t = messages[locale]`を`const t = globalErrorMessages[locale]`に変更
  - `detectLocale()`関数とその他のロジックは変更せず維持
  - _要件: 1.3, 3.2, 3.4, 3.5, 3.6_

- [x] 3.2 Admin Appの全27テスト実行と検証
  - `cd frontend/admin-app && npm test src/app/__tests__/global-error.test.tsx`を実行
  - 全27テストがpassすることを確認
  - テスト失敗時は共通モジュールまたはimportロジックを修正
  - _要件: 5.2, 5.3, 5.4, 5.5, 5.6_

- [x] 4. 統合動作確認とエンドツーエンド検証
- [ ] 4.1 User Appの手動動作確認とエラーハンドリング検証（手動確認タスク、スキップ可）
  - User App開発サーバー起動（`cd frontend/user-app && npm run dev`）
  - ブラウザでエラー画面を手動確認（ApiError、NetworkError、汎用Error）
  - 日本語メッセージ（ja）と英語メッセージ（en）の表示を確認
  - ロケール検出ロジック（NEXT_LOCALE Cookie、document.documentElement.lang）の動作確認
  - `reset()`ボタンによるエラーリカバリー機能の確認
  - _要件: 4.1, 4.3, 4.4, 4.5, 4.6, 4.7_

- [ ] 4.2 Admin Appの手動動作確認とエラーハンドリング検証（手動確認タスク、スキップ可）
  - Admin App開発サーバー起動（`cd frontend/admin-app && npm run dev`）
  - ブラウザでエラー画面を手動確認（ApiError、NetworkError、汎用Error）
  - 日本語メッセージ（ja）と英語メッセージ（en）の表示を確認
  - ロケール検出ロジックの動作確認
  - `reset()`ボタンによるエラーリカバリー機能の確認
  - _要件: 4.2, 4.3, 4.4, 4.5, 4.6, 4.7_

- [x] 4.3 全54テストの最終確認とカバレッジ維持検証
  - モノレポルートで`npm test`を実行
  - User App全27テスト + Admin App全27テスト = 全54テストのpassを確認
  - カバレッジが維持されていることを確認（カバレッジ低下がないこと）
  - TypeScriptコンパイルエラーがないことを最終確認
  - _要件: 5.7, 6.5_

- [x] 5. ドキュメント更新とプルリクエスト準備
- [x] 5.1 CLAUDE.mdの仕様ステータス更新
  - `CLAUDE.md`の`global-error-static-dictionary-dry`仕様ステータスを`completed`に更新
  - リファクタリング完了の記録
  - _要件: 7.5_

- [ ] 5.2 コミット作成とプルリクエスト準備
  - リファクタリング完了後の変更をコミット
  - コミットメッセージ: "Refactor: Global Error静的辞書の共通化完了（DRY原則適用）"
  - PR #119作成準備
  - _要件: 7.4_

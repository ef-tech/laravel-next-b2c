# Implementation Tasks

## Phase 1: 共通TypeScript設定基盤の作成

### Task 1: frontend/tsconfig.base.json の作成
- [x] 1.1 frontend/tsconfig.base.json ファイルを新規作成する
  - モノレポ共通のTypeScript設定を定義
  - User App/Admin App両方から継承可能な設定にする
  - _Requirements: 1.1_

- [x] 1.2 共通compilerOptionsを定義する
  - `target`: "ES2017"
  - `lib`: ["dom", "dom.iterable", "esnext"]
  - `allowJs`: true
  - `skipLibCheck`: true
  - `strict`: true
  - `noEmit`: true
  - `esModuleInterop`: true
  - `module`: "esnext"
  - `moduleResolution`: "bundler"
  - `resolveJsonModule`: true
  - `isolatedModules`: true
  - `jsx`: "preserve"
  - `incremental`: true
  - `plugins`: [{"name": "next"}]
  - _Requirements: 1.1_

- [x] 1.3 共通excludeを定義する
  - `exclude`: ["node_modules"]
  - Jestテストファイルの除外設定は各アプリで管理
  - _Requirements: 1.2_

- [x] 1.4 パスエイリアス設定を含まないことを確認する
  - `paths`設定は各アプリ固有設定として残す
  - tsconfig.base.jsonには記述しない
  - _Requirements: 1.3_

- [x] 1.5 JSON構文の有効性を検証する
  - `npx tsc --showConfig` でパースエラーがないことを確認
  - VSCodeでJSON schemaバリデーションが通ることを確認
  - _Requirements: 1.4_

**Validation Checkpoint:**
- tsconfig.base.jsonが有効なJSON形式である
- 15個の共通compilerOptionsが全て含まれている
- pathsプロパティが存在しない

---

## Phase 2: User Appの設定リファクタリング

### Task 2: user-app/tsconfig.json のリファクタリング
- [x] 2.1 既存のuser-app/tsconfig.jsonをバックアップする
  - `frontend/user-app/tsconfig.json.backup` として保存
  - ロールバック時に使用
  - _Requirements: 10.1_

- [x] 2.2 extends設定を追加する
  - `"extends": "../tsconfig.base.json"` を先頭に追加
  - TypeScript継承システムが正しく動作することを確認
  - _Requirements: 2.1_

- [x] 2.3 共通compilerOptionsを削除する
  - target, lib, allowJs, skipLibCheck, strict, noEmit, esModuleInterop, module, moduleResolution, resolveJsonModule, isolatedModules, jsx, incremental, plugins を削除
  - tsconfig.base.jsonから継承されることを確認
  - _Requirements: 2.4_

- [x] 2.4 アプリ固有のpaths設定のみを保持する
  - `"@/*": ["./src/*"]` (User App内部)
  - `"@shared/*": ["../lib/*"]` (モノレポ共通)
  - compilerOptions.pathsにのみ記述
  - _Requirements: 2.2_

- [x] 2.5 アプリ固有のinclude設定のみを保持する
  - `["next-env.d.ts", "**/*.ts", "**/*.tsx", ".next/types/**/*.ts"]`
  - トップレベルのincludeプロパティとして記述
  - _Requirements: 2.3_

- [x] 2.6 JSON構文の有効性を検証する
  - `npx tsc --showConfig` でパースエラーがないことを確認
  - VSCodeでJSON schemaバリデーションが通ることを確認
  - _Requirements: 2.5_

**Validation Checkpoint:**
- user-app/tsconfig.jsonが `"extends": "../tsconfig.base.json"` を含む
- 共通compilerOptionsが削除されている
- paths設定（@/*, @shared/*）が保持されている
- include設定が保持されている

---

## Phase 3: Admin Appの設定リファクタリング

### Task 3: admin-app/tsconfig.json のリファクタリング
- [x] 3.1 既存のadmin-app/tsconfig.jsonをバックアップする
  - `frontend/admin-app/tsconfig.json.backup` として保存
  - ロールバック時に使用
  - _Requirements: 10.1_

- [x] 3.2 extends設定を追加する
  - `"extends": "../tsconfig.base.json"` を先頭に追加
  - TypeScript継承システムが正しく動作することを確認
  - _Requirements: 3.1_

- [x] 3.3 共通compilerOptionsを削除する
  - target, lib, allowJs, skipLibCheck, strict, noEmit, esModuleInterop, module, moduleResolution, resolveJsonModule, isolatedModules, jsx, incremental, plugins を削除
  - tsconfig.base.jsonから継承されることを確認
  - _Requirements: 3.4_

- [x] 3.4 アプリ固有のpaths設定のみを保持する
  - `"@/*": ["./src/*"]` (Admin App内部)
  - `"@shared/*": ["../lib/*"]` (モノレポ共通)
  - compilerOptions.pathsにのみ記述
  - _Requirements: 3.2_

- [x] 3.5 アプリ固有のinclude設定のみを保持する
  - `["next-env.d.ts", "**/*.ts", "**/*.tsx", ".next/types/**/*.ts"]`
  - トップレベルのincludeプロパティとして記述
  - _Requirements: 3.3_

- [x] 3.6 JSON構文の有効性を検証する
  - `npx tsc --showConfig` でパースエラーがないことを確認
  - VSCodeでJSON schemaバリデーションが通ることを確認
  - _Requirements: 3.5_

- [x] 3.7 User App設定との構造一致を確認する
  - extends, paths, includeの構造が同一であることを確認
  - 値のみがアプリ固有（相対パスの起点が異なる）
  - _Requirements: 3.6_

**Validation Checkpoint:**
- admin-app/tsconfig.jsonが `"extends": "../tsconfig.base.json"` を含む
- 共通compilerOptionsが削除されている
- paths設定（@/*, @shared/*）が保持されている
- include設定が保持されている
- User App設定と構造が一致している

---

## Phase 4: TypeScript型チェックの検証

### Task 4: TypeScriptコンパイラの動作確認
- [x] 4.1 User Appで型チェックを実行する
  - `cd frontend/user-app && npm run type-check`
  - エラーなく完了することを確認
  - _Requirements: 4.1_

- [x] 4.2 Admin Appで型チェックを実行する
  - `cd frontend/admin-app && npm run type-check`
  - エラーなく完了することを確認
  - _Requirements: 4.2_

- [x] 4.3 @/* パスエイリアスの解決を確認する
  - User App: `import { ... } from '@/components/...'` が解決される
  - Admin App: `import { ... } from '@/components/...'` が解決される
  - TypeScriptコンパイラがエラーを出さない
  - _Requirements: 4.3_

- [x] 4.4 @shared/* パスエイリアスの解決を確認する
  - User App: `import { ... } from '@shared/...'` が解決される
  - Admin App: `import { ... } from '@shared/...'` が解決される
  - TypeScriptコンパイラがエラーを出さない
  - _Requirements: 4.4_

- [x] 4.5 設定継承の確認
  - `npx tsc --showConfig` の出力でtsconfig.base.jsonの設定が反映されていることを確認
  - target, lib, strict等の設定が正しく継承されている
  - _Requirements: 4.5_

**Validation Checkpoint:**
- User App/Admin App両方で `npm run type-check` が成功
- パスエイリアス（@/*, @shared/*）が正しく解決される
- tsconfig.base.jsonの設定が正しく継承されている

---

## Phase 5: IDE型補完の検証

### Task 5: VSCode型補完の動作確認
- [ ] 5.1 User AppでVSCodeを開く
  - `code frontend/user-app`
  - TypeScript Language Serviceが起動することを確認
  - _Requirements: 5.1, 5.2_

- [ ] 5.2 User Appで@/*の型補完を確認する
  - ファイル内で `import {} from '@/'` と入力
  - VSCodeがsrc/配下のファイルを補完候補に表示
  - _Requirements: 5.1_

- [ ] 5.3 User Appで@shared/*の型補完を確認する
  - ファイル内で `import {} from '@shared/'` と入力
  - VSCodeがfrontend/lib/配下のファイルを補完候補に表示
  - _Requirements: 5.2_

- [ ] 5.4 Admin AppでVSCodeを開く
  - `code frontend/admin-app`
  - TypeScript Language Serviceが起動することを確認
  - _Requirements: 5.3, 5.4_

- [ ] 5.5 Admin Appで@/*の型補完を確認する
  - ファイル内で `import {} from '@/'` と入力
  - VSCodeがsrc/配下のファイルを補完候補に表示
  - _Requirements: 5.3_

- [ ] 5.6 Admin Appで@shared/*の型補完を確認する
  - ファイル内で `import {} from '@shared/'` と入力
  - VSCodeがfrontend/lib/配下のファイルを補完候補に表示
  - _Requirements: 5.4_

- [ ] 5.7 継承設定の認識を確認する
  - VSCodeのTypeScript: Go to Source Definition機能でtsconfig.base.jsonが認識される
  - tsconfig.jsonファイルを開いた時にextends設定が解決される
  - _Requirements: 5.5_

- [ ] 5.8 型エラー検出精度を確認する
  - リファクタリング前に検出されていた型エラーが同様に検出される
  - 新しい型エラーが発生していない
  - _Requirements: 5.6_

**Validation Checkpoint:**
- VSCodeで@/*, @shared/*の型補完が正常に動作する
- tsconfig.base.jsonの継承設定がVSCodeに認識される
- 型エラー検出精度がリファクタリング前と同等

---

## Phase 6: Jestテストの検証

### Task 6: Jestテストスイートの動作確認
- [x] 6.1 User AppでJestテストを実行する
  - `cd frontend/user-app && npm test`
  - 全テストがパスすることを確認
  - _Requirements: 6.1_

- [x] 6.2 Admin AppでJestテストを実行する
  - `cd frontend/admin-app && npm test`
  - 全テストがパスすることを確認
  - _Requirements: 6.2_

- [x] 6.3 Jestでの@/*パスエイリアス解決を確認する
  - User App: テストコード内の `import { ... } from '@/...'` が解決される
  - Admin App: テストコード内の `import { ... } from '@/...'` が解決される
  - _Requirements: 6.3_

- [x] 6.4 Jestでの@shared/*パスエイリアス解決を確認する
  - User App: テストコード内の `import { ... } from '@shared/...'` が解決される
  - Admin App: テストコード内の `import { ... } from '@shared/...'` が解決される
  - _Requirements: 6.4_

- [x] 6.5 テスト実行時間のベンチマーク
  - リファクタリング前のテスト実行時間を記録
  - リファクタリング後のテスト実行時間を記録
  - ±10%以内の差異であることを確認
  - _Requirements: 6.5_

**Validation Checkpoint:**
- User App/Admin App両方で `npm test` が全テストパス
- Jestでパスエイリアスが正しく解決される
- テスト実行時間がリファクタリング前と同等（±10%以内）

---

## Phase 7: Next.jsビルドの検証

### Task 7: Next.jsビルドシステムの動作確認
- [x] 7.1 User AppでNext.jsビルドを実行する
  - `cd frontend/user-app && npm run build`
  - エラーなく完了することを確認
  - _Requirements: 7.1_

- [x] 7.2 Admin AppでNext.jsビルドを実行する
  - `cd frontend/admin-app && npm run build`
  - エラーなく完了することを確認
  - _Requirements: 7.2_

- [x] 7.3 .next/types/**/*.ts の型定義認識を確認する
  - ビルド時に `.next/types/` 配下の型定義ファイルが認識される
  - includeプロパティの `.next/types/**/*.ts` が有効である
  - _Requirements: 7.3_

- [x] 7.4 tsconfig.base.json設定の適用を確認する
  - ビルドログでTypeScript設定が正しく適用されている
  - target, lib等の設定がビルド時に反映されている
  - _Requirements: 7.4_

- [x] 7.5 ビルド時間のベンチマーク
  - リファクタリング前のビルド時間を記録
  - リファクタリング後のビルド時間を記録
  - ±10%以内の差異であることを確認
  - _Requirements: 7.5_

- [x] 7.6 ビルド成果物の確認
  - `.next/standalone/` ディレクトリが生成される
  - Docker本番ビルド対応が維持されている
  - _Requirements: 7.6_

**Validation Checkpoint:**
- User App/Admin App両方で `npm run build` が成功
- .next/types/**/*.ts が正しく認識される
- tsconfig.base.jsonの設定が正しく適用される
- ビルド時間がリファクタリング前と同等（±10%以内）
- .next/standalone/ディレクトリが正しく生成される

---

## Phase 8: スケーラビリティ・保守性の検証

### Task 8: 拡張性・保守性の確認
- [x] 8.1 新アプリ追加シミュレーション（任意）
  - frontend/new-app/tsconfig.jsonを仮作成
  - `"extends": "../tsconfig.base.json"` で継承
  - アプリ固有のpaths設定のみで動作することを確認
  - _Requirements: 8.1, 8.2_

- [x] 8.2 共通設定変更シミュレーション
  - tsconfig.base.jsonの設定を一時的に変更（例: `target: "ES2020"`）
  - User App/Admin Appで `npx tsc --showConfig` を実行
  - 両アプリに変更が反映されることを確認
  - 元の設定に戻す
  - _Requirements: 8.3_

- [x] 8.3 設定オーバーライド可能性の確認
  - User App/Admin Appのtsconfig.jsonでcompilerOptionsを追加
  - tsconfig.base.jsonの設定を上書きできることを確認
  - 追加した設定を削除して元に戻す
  - _Requirements: 8.4_

- [x] 8.4 保守性の確認
  - tsconfig.base.jsonのみ編集すれば共通設定が変更できることを確認
  - 各アプリのtsconfig.jsonがアプリ固有設定のみを含むことを確認
  - _Requirements: 9.1, 9.2_

- [x] 8.5 ドキュメント性の確認
  - 新しい開発者がtsconfig.base.jsonを読んでプロジェクト共通設定を理解できることを確認
  - 各アプリのtsconfig.jsonがシンプルで理解しやすいことを確認
  - _Requirements: 9.3_

**Validation Checkpoint:**
- 新アプリ追加時にtsconfig.base.jsonを継承できる
- tsconfig.base.json 1箇所の変更で全アプリに反映される
- 各アプリで設定オーバーライドが可能
- 設定ファイルがシンプルで保守しやすい

---

## Phase 9: 後方互換性・CI/CDの検証

### Task 9: 後方互換性とCI/CDの確認
- [x] 9.1 TypeScriptバージョンの確認
  - リファクタリング前後でTypeScriptバージョンが同一であることを確認
  - package.jsonのtypescriptパッケージバージョンを確認
  - _Requirements: 10.1_

- [x] 9.2 Next.jsバージョンの確認
  - リファクタリング前後でNext.jsバージョンが同一であることを確認
  - package.jsonのnextパッケージバージョンを確認
  - _Requirements: 10.2_

- [x] 9.3 npmスクリプトの動作確認
  - `npm run dev`: 開発サーバーが起動する
  - `npm run build`: ビルドが成功する
  - `npm test`: テストが全パスする
  - `npm run type-check`: 型チェックが成功する
  - _Requirements: 10.3_

- [x] 9.4 CI/CDパイプラインの確認
  - GitHub Actionsの「Frontend - Type Check」ワークフローが成功する
  - GitHub Actionsの「Frontend - Build Validation」ワークフローが成功する
  - GitHub Actionsの「Frontend - Test」ワークフローが成功する
  - リファクタリング前と同じ成功率であることを確認
  - _Requirements: 10.4_

- [x] 9.5 型エラー検出の一貫性確認
  - 既存のコードベースに変更を加えない状態で型チェック実行
  - リファクタリング前と同じ型エラー（0件）が検出されることを確認
  - _Requirements: 10.5_

**Validation Checkpoint:**
- TypeScript/Next.jsバージョンがリファクタリング前と同一
- 全npmスクリプトがリファクタリング前と同じ動作をする
- CI/CDパイプラインが成功する
- 型エラー検出精度がリファクタリング前と同等

---

## Phase 10: ドキュメント更新とクリーンアップ

### Task 10: 最終ドキュメント更新
- [x] 10.1 バックアップファイルの削除
  - `frontend/user-app/tsconfig.json.backup` を削除
  - `frontend/admin-app/tsconfig.json.backup` を削除
  - リファクタリングが完全に成功したことを確認
  - _Requirements: 10.1_

- [x] 10.2 CLAUDE.mdの更新（既に完了）
  - Active Specificationsに `frontend-common-tsconfig` が追加済み
  - 説明文が正確であることを確認
  - _Requirements: 9.3_

- [x] 10.3 コミットメッセージの作成
  - Prefix: Refactor
  - Emoji: 🔧
  - 概要: フロントエンド共通tsconfig.base.json導入（TypeScript設定の重複削減）
  - 詳細: リファクタリング内容、検証結果、影響範囲を記載
  - _Requirements: 9.5_

- [x] 10.4 プルリクエストの作成
  - タイトル: 🔧 フロントエンド共通tsconfig.base.json導入
  - 本文: リファクタリング内容、検証結果、Before/After比較を記載
  - Close: #126 を記載
  - _Requirements: 9.3_

- [x] 10.5 Kiro仕様の完了マーク
  - spec.jsonの `phase` を `"completed"` に更新
  - `updated_at` タイムスタンプを更新
  - `ready_for_implementation` を `false` のまま保持（実装完了のため）
  - _Requirements: 9.3_

**Validation Checkpoint:**
- バックアップファイルが削除されている
- ドキュメントが最新状態に更新されている
- コミットメッセージが適切に作成されている
- プルリクエストが作成されている
- Kiro仕様が完了状態になっている

---

## Rollback Plan

リファクタリング中に問題が発生した場合の巻き戻し手順:

### Phase 2-3 Rollback (設定ファイルリファクタリング失敗時)
1. バックアップファイルを元に戻す
   ```bash
   cd frontend/user-app
   mv tsconfig.json.backup tsconfig.json
   cd ../admin-app
   mv tsconfig.json.backup tsconfig.json
   ```
2. frontend/tsconfig.base.jsonを削除
   ```bash
   cd frontend
   rm tsconfig.base.json
   ```

### Phase 4-7 Rollback (検証失敗時)
1. Phase 2-3のRollbackを実行
2. 問題の原因を調査
3. tsconfig.base.jsonの設定を見直し
4. 再度Phase 1から実施

### Phase 8-9 Rollback (拡張性・CI/CD検証失敗時)
1. Phase 2-3のRollbackを実行
2. 設計の見直しを検討
3. 必要に応じてアプローチを変更

---

## Success Criteria Summary

全フェーズ完了時に以下が満たされていること:

✅ **設定ファイル構造**
- frontend/tsconfig.base.json が存在し、15個の共通compilerOptionsを含む
- frontend/user-app/tsconfig.json が tsconfig.base.json を継承し、アプリ固有設定のみを含む
- frontend/admin-app/tsconfig.json が tsconfig.base.json を継承し、アプリ固有設定のみを含む

✅ **機能動作**
- User App/Admin App両方で `npm run type-check` が成功
- User App/Admin App両方で `npm test` が全テストパス
- User App/Admin App両方で `npm run build` が成功
- VSCodeで@/*, @shared/*の型補完が正常に動作

✅ **品質保証**
- テスト実行時間がリファクタリング前と同等（±10%以内）
- ビルド時間がリファクタリング前と同等（±10%以内）
- CI/CDパイプラインが成功する

✅ **保守性・拡張性**
- tsconfig.base.json 1箇所の変更で全アプリに反映される
- 新アプリ追加時にtsconfig.base.jsonを継承できる
- 設定ファイルがシンプルで理解しやすい

✅ **後方互換性**
- TypeScript/Next.jsバージョンが変更されていない
- 全npmスクリプトがリファクタリング前と同じ動作をする
- 型エラー検出精度がリファクタリング前と同等

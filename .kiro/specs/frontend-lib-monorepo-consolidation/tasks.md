# Implementation Plan

## Overview

本実装プランは、フロントエンドモノレポ構成における共通ライブラリのコード重複解消を段階的に実現します。TypeScriptパスエイリアス（`@shared/*`）を3層（TypeScript、webpack、Jest）で設定し、既存の重複コード（約560行）を削除します。各タスクは1-3時間で完了可能なサイズに分割され、段階的な検証を行いながら安全に移行を完了させます。

---

## Tasks

- [x] 1. User App向けTypeScript・ビルド・テスト設定を確立する
- [x] 1.1 User AppのTypeScriptパスエイリアス設定を追加する
  - tsconfig.jsonを開き、既存のpaths設定を確認する
  - compilerOptions.pathsに`@shared/*`エイリアスを追加し、`../lib/*`に解決させる
  - baseUrl設定が`.`であることを確認し、相対パス解決を有効化する
  - TypeScript型チェックを実行して設定エラーがないことを確認する
  - _Requirements: 1.1, 1.2, 1.3, 1.4_

- [x] 1.2 User AppのNext.js webpack alias設定を追加する
  - next.config.tsを開き、既存のwebpack設定を確認する
  - pathモジュールをインポートし、`__dirname`を使用して相対パス解決を準備する
  - webpack関数内でresolve.alias設定に`@shared`エイリアスを追加する
  - 開発サーバーを起動して設定が正しく読み込まれることを確認する
  - _Requirements: 2.1, 2.2, 2.3, 2.4_

- [x] 1.3 User AppのJest moduleNameMapper設定を追加する
  - jest.config.jsを開き、既存のmoduleNameMapper設定を確認する
  - moduleNameMapperに`^@shared/(.*)$`パターンを追加し、`<rootDir>/../lib/$1`にマッピングする
  - 既存の`@/*`マッパーや`security-config`マッパーが保持されていることを確認する
  - Jestテストを実行して設定が正しく動作することを確認する
  - _Requirements: 3.1, 3.2, 3.3, 3.4_

- [x] 2. Admin App向けTypeScript・ビルド・テスト設定を確立する
- [x] 2.1 Admin AppのTypeScriptパスエイリアス設定を追加する
  - tsconfig.jsonを開き、User Appと同様のpaths設定を追加する
  - compilerOptions.pathsに`@shared/*`エイリアスを追加し、`../lib/*`に解決させる
  - baseUrl設定が`.`であることを確認する
  - TypeScript型チェックを実行して設定エラーがないことを確認する
  - _Requirements: 1.1, 1.2, 1.3, 1.4_

- [x] 2.2 Admin AppのNext.js webpack alias設定を追加する
  - next.config.tsを開き、User Appと同様のwebpack alias設定を追加する
  - pathモジュールをインポートし、resolve.aliasに`@shared`を追加する
  - 開発サーバーを起動して設定が正しく読み込まれることを確認する
  - _Requirements: 2.1, 2.2, 2.3, 2.4_

- [x] 2.3 Admin AppのJest moduleNameMapper設定を追加する
  - jest.config.jsを開き、User Appと同様のmoduleNameMapper設定を追加する
  - `^@shared/(.*)$`パターンを追加し、既存マッパーを保持する
  - Jestテストを実行して設定が正しく動作することを確認する
  - _Requirements: 3.1, 3.2, 3.3, 3.4_

- [x] 3. パスエイリアス設定の統合検証を実施する
- [x] 3.1 User AppとAdmin Appの型チェックを実行する
  - 両アプリケーションでTypeScript型チェック（`npm run type-check`）を実行する
  - 型エラーがゼロであることを確認する
  - IDEで`@shared/*`インポートの型補完が動作することを確認する
  - _Requirements: 1.1, 1.2, 1.3_

- [x] 3.2 User AppとAdmin Appの開発サーバー起動を確認する
  - User Appで開発サーバー（`npm run dev`）を起動し、エラーがないことを確認する
  - Admin Appで開発サーバーを起動し、エラーがないことを確認する
  - ブラウザでhttp://localhost:13001とhttp://localhost:13002にアクセスし、ページが表示されることを確認する
  - _Requirements: 2.2, 2.3, 7.1, 7.2_

- [x] 3.3 User AppとAdmin AppのJestテストを実行する
  - User Appで全Jestテスト（`npm run test`）を実行し、全テストがパスすることを確認する
  - Admin Appで全Jestテストを実行し、全テストがパスすることを確認する
  - テストカバレッジが既存レベル（94.73%以上）を維持していることを確認する
  - _Requirements: 3.2, 3.3, 6.1, 6.2_

- [x] 4. User Appのimport文を一括更新する
- [x] 4.1 User Appの影響範囲を調査する
  - grepコマンドで`from '@/lib/api-client'`を検索し、該当箇所を特定する
  - grepコマンドで`from '@/lib/api-error'`を検索し、該当箇所を特定する
  - grepコマンドで`from '@/lib/network-error'`を検索し、該当箇所を特定する
  - 検索結果を記録し、置換対象ファイル数を把握する
  - _Requirements: 4.1_

- [x] 4.2 User Appのimport文を自動置換する
  - sedコマンドで`from '@/lib/api-client'`を`from '@shared/api-client'`に一括置換する
  - sedコマンドで`from '@/lib/api-error'`を`from '@shared/api-error'`に一括置換する
  - sedコマンドで`from '@/lib/network-error'`を`from '@shared/network-error'`に一括置換する
  - バックアップファイル（`.bak`）を削除する
  - _Requirements: 4.2_

- [x] 4.3 User Appの置換結果を検証する
  - TypeScript型チェック（`npm run type-check`）を実行し、型エラーがないことを確認する
  - grepコマンドで`from '@/lib/`を再検索し、アプリ固有ファイル（`api.ts`, `env.ts`）のみが残っていることを確認する
  - すべての`.ts`および`.tsx`ファイルで`@shared/*`インポートに統一されていることを確認する
  - _Requirements: 4.3, 4.4, 4.5_

- [x] 5. Admin Appのimport文を一括更新する
- [x] 5.1 Admin Appの影響範囲を調査する
  - grepコマンドで`from '@/lib/api-client'`を検索し、該当箇所を特定する
  - grepコマンドで`from '@/lib/api-error'`を検索し、該当箇所を特定する
  - grepコマンドで`from '@/lib/network-error'`を検索し、該当箇所を特定する
  - 検索結果を記録し、置換対象ファイル数を把握する
  - _Requirements: 4.1_

- [x] 5.2 Admin Appのimport文を自動置換する
  - sedコマンドで3つのインポートパターンを`@shared/*`に一括置換する
  - バックアップファイル（`.bak`）を削除する
  - TypeScript型チェックを実行し、型エラーがないことを確認する
  - _Requirements: 4.2, 4.3_

- [x] 5.3 Admin Appの置換結果を検証する
  - grepコマンドで置換漏れがないことを確認する
  - アプリ固有ファイル（`api.ts`, `env.ts`）が変更されていないことを確認する
  - すべてのインポートが`@shared/*`に統一されていることを確認する
  - _Requirements: 4.4, 4.5_

- [x] 6. 重複ファイルを削除してコード重複を解消する
- [x] 6.1 User Appの重複ファイルを削除する
  - User App `src/lib/api-client.ts`を削除する
  - User App `src/lib/api-error.ts`を削除する
  - User App `src/lib/network-error.ts`を削除する
  - アプリ固有ファイル（`api.ts`, `env.ts`）が保持されていることを確認する
  - _Requirements: 5.1, 5.2_

- [x] 6.2 Admin Appの重複ファイルを削除する
  - Admin App `src/lib/api-client.ts`を削除する
  - Admin App `src/lib/api-error.ts`を削除する
  - Admin App `src/lib/network-error.ts`を削除する
  - アプリ固有ファイル（`api.ts`, `env.ts`）が保持されていることを確認する
  - _Requirements: 5.1, 5.2_

- [x] 6.3 重複ファイル削除後の検証を実施する
  - TypeScript型チェックを実行し、型エラーがないことを確認する
  - 両アプリケーションで`src/lib/`ディレクトリにアプリ固有ファイルのみが存在することを確認する
  - 約560行のコード削減が達成されたことを確認する
  - _Requirements: 5.3, 5.4_

- [ ] 7. 全テストスイートを実行して品質を保証する
- [ ] 7.1 User AppとAdmin AppのJestユニットテストを実行する
  - User Appで全Jestテスト（`npm run test`）を実行し、すべてのテストがパスすることを確認する
  - Admin Appで全Jestテストを実行し、すべてのテストがパスすることを確認する
  - カバレッジレポート（`npm run test:coverage`）を生成し、94.73%以上を維持していることを確認する
  - _Requirements: 6.1, 6.2_

- [ ] 7.2 TypeScript型チェックとESLint/Prettierを実行する
  - 両アプリケーションでTypeScript型チェック（`npm run type-check`）を実行し、型エラーゼロを確認する
  - ESLintとPrettierチェック（`npm run lint && npm run format:check`）を実行し、エラーゼロを確認する
  - コード品質が維持されていることを確認する
  - _Requirements: 6.3, 6.4_

- [ ] 7.3 User AppとAdmin Appの本番ビルドを実行する
  - User Appで本番ビルド（`npm run build`）を実行し、ビルドが成功することを確認する
  - Admin Appで本番ビルドを実行し、ビルドが成功することを確認する
  - ビルドエラーやwebpackエラーが発生しないことを確認する
  - _Requirements: 6.5_

- [ ] 7.4 E2Eテストを実行して統合動作を確認する
  - E2Eテストディレクトリに移動する
  - User App E2Eテスト（`npm run test:user`）を実行し、全テストがパスすることを確認する
  - Admin App E2Eテスト（`npm run test:admin`）を実行し、全テストがパスすることを確認する
  - _Requirements: 6.6_

- [ ] 8. 開発サーバー動作とAPIリクエストを検証する
- [ ] 8.1 User App開発サーバーの動作を確認する
  - User Appで開発サーバー（`npm run dev`）を起動する
  - ブラウザでhttp://localhost:13001にアクセスし、ホームページが正常に表示されることを確認する
  - APIリクエストが成功し、正常なレスポンスが返されることを確認する
  - _Requirements: 7.1, 7.3_

- [ ] 8.2 Admin App開発サーバーの動作を確認する
  - Admin Appで開発サーバーを起動する
  - ブラウザでhttp://localhost:13002にアクセスし、ホームページが正常に表示されることを確認する
  - APIリクエストが成功することを確認する
  - _Requirements: 7.2, 7.3_

- [ ] 8.3 ブラウザ開発者ツールでヘッダーとエラーハンドリングを検証する
  - ブラウザ開発者ツールのNetworkタブを開き、`X-Request-ID`ヘッダーが付与されていることを確認する
  - `Accept-Language`ヘッダーが正しく設定されていることを確認する
  - エラーレスポンスが発生した場合、RFC 7807準拠のエラーハンドリングが機能することを確認する
  - _Requirements: 7.4, 7.5_

- [ ] 8.4 ホットリロード機能の動作を確認する
  - 開発サーバー起動中に共通ライブラリファイル（`frontend/lib/api-client.ts`等）を編集する
  - ブラウザが自動リロードされ、変更が即座に反映されることを確認する
  - User AppとAdmin App両方でホットリロードが正常に動作することを確認する
  - _Requirements: 7.6_

- [ ] 9. ドキュメントを更新して変更履歴を記録する
- [ ] 9.1 CHANGELOG.mdを更新する
  - CHANGELOG.mdを開き、新しいバージョンセクションを追加する
  - パスエイリアス導入（`@shared/*`）による変更内容を記載する
  - 重複ファイル削除（約560行削減）を記載する
  - テストカバレッジ維持（94.73%以上）を記載する
  - Breaking Changes無しであることを明記する
  - _Requirements: 8.1, 8.2, 8.3_

---

## Implementation Notes

### 実装順序の重要性
タスクは上記の順序で実装する必要があります。特に以下の依存関係に注意してください：
- パスエイリアス設定（タスク1-2）→ import文更新（タスク4-5）→ 重複ファイル削除（タスク6）
- 各フェーズ後に検証タスク（タスク3, 7, 8）を実行し、段階的に品質を保証

### ロールバック戦略
各タスクグループ完了後に問題が発生した場合、以下の手順でロールバック可能：
- タスク1-3完了後: 設定ファイルをGitから復元
- タスク4-5完了後: `.bak`ファイルから復元、またはGitから復元
- タスク6完了後: 削除されたファイルをGitから復元

### テストカバレッジ維持
jest.config.jsの`collectCoverageFrom`設定で、共通ライブラリファイル（`!src/lib/api-client.ts`等）を除外済みであることを確認してください。これにより、カバレッジ率の維持が保証されます。

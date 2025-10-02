# Implementation Plan

## フロントエンドアプリケーション設定更新

- [x] 1. User Appの起動ポート設定を更新する
- [x] 1.1 User Appのnpm scriptsにポート指定を追加する
  - dev scriptに`--port 13001`フラグを追加
  - start scriptに`--port 13001`フラグを追加
  - 既存の`--turbopack`フラグを維持
  - _Requirements: 1.1, 1.2, 1.6_

- [x] 1.2 User Appの起動確認とHMR動作検証を実施する
  - ポート13001での起動を確認
  - ブラウザでアクセス可能なことを確認
  - ソースコード変更時のHMR自動反映を確認
  - _Requirements: 1.1, 4.2, 4.4_

- [x] 2. Admin Appの起動ポート設定を更新する
- [x] 2.1 Admin Appのnpm scriptsにポート指定を追加する
  - dev scriptに`--port 13002`フラグを追加
  - start scriptに`--port 13002`フラグを追加
  - 既存の`--turbopack`フラグを維持
  - _Requirements: 1.3, 1.4, 1.6_

- [ ] 2.2 Admin Appの起動確認とHMR動作検証を実施する
  - ポート13002での起動を確認
  - ブラウザでアクセス可能なことを確認
  - ソースコード変更時のHMR自動反映を確認
  - _Requirements: 1.3, 4.1, 4.4_

- [ ] 2.3 両アプリの同時起動とポート競合確認を実施する
  - User AppとAdmin Appを同時に起動
  - ポート競合がないことを確認
  - 両アプリが正常にアクセス可能なことを確認
  - _Requirements: 1.5_

---

## E2Eテスト環境設定更新

- [x] 3. E2E環境変数ファイルを更新する
- [x] 3.1 E2E環境変数テンプレートを更新する
  - .env.exampleのE2E_ADMIN_URLをポート13002に変更
  - .env.exampleのE2E_USER_URLをポート13001に変更
  - E2E_API_URLは13000のまま維持
  - _Requirements: 2.1, 2.2, 2.3_

- [x] 3.2 E2E環境変数ファイルを更新する（存在する場合）
  - .envのE2E_ADMIN_URLをポート13002に変更
  - .envのE2E_USER_URLをポート13001に変更
  - E2E_API_URLは13000のまま維持
  - _Requirements: 2.1, 2.2_

- [x] 4. Playwright設定ファイルを更新する
- [x] 4.1 Playwrightのadmin-chromiumプロジェクト設定を更新する
  - baseURLのデフォルト値を`'http://localhost:13002'`に変更
  - 環境変数フォールバックパターンを維持
  - デバイス設定とtestDir設定を維持
  - _Requirements: 2.4_

- [x] 4.2 Playwrightのuser-chromiumプロジェクト設定を更新する
  - baseURLのデフォルト値を`'http://localhost:13001'`に変更
  - 環境変数フォールバックパターンを維持
  - デバイス設定とtestDir設定を維持
  - _Requirements: 2.5_

- [ ] 4.3 E2Eテスト実行確認を実施する
  - 全E2Eテストを実行（`npx playwright test`）
  - admin-chromiumプロジェクトのテスト実行を確認
  - user-chromiumプロジェクトのテスト実行を確認
  - 固定baseURLでのテスト実行を検証
  - _Requirements: 2.6, 5.1, 5.2, 5.3_

---

## バックエンドCORS設定更新

- [x] 5. Laravel CORS設定を更新する
- [x] 5.1 CORS allowed_originsに新ポートを追加する
  - `http://localhost:13001`をallowed_originsに追加
  - `http://localhost:13002`をallowed_originsに追加
  - `http://127.0.0.1:13001`をallowed_originsに追加
  - `http://127.0.0.1:13002`をallowed_originsに追加
  - 既存ポート（3000, 3001）を後方互換性のため保持
  - _Requirements: 3.1, 3.2_

- [ ] 5.2 CORS通信テストを実施する
  - admin-app（ポート13002）からのOPTIONSリクエストテスト
  - user-app（ポート13001）からのOPTIONSリクエストテスト
  - curlコマンドでCORSヘッダーの検証
  - ブラウザDevToolsでの実際の通信確認
  - _Requirements: 3.3, 3.4_

---

## 開発環境動作確認

- [ ] 6. 開発ワークフローの動作確認を実施する
- [ ] 6.1 TypeScript型チェックを実行する
  - user-appで型チェック実行（`npm run type-check`）
  - admin-appで型チェック実行（`npm run type-check`）
  - モノレポルートから全体の型チェック実行
  - 型エラーがないことを確認
  - _Requirements: 4.5_

- [ ] 6.2 ESLintチェックを実行する
  - user-appでlint実行（`npm run lint`）
  - admin-appでlint実行（`npm run lint`）
  - モノレポルートから全体のlint実行
  - リントエラーがないことを確認
  - _Requirements: 4.6_

- [ ] 6.3 プロダクションビルドを実行する
  - user-appでビルド実行（`npm run build`）
  - admin-appでビルド実行（`npm run build`）
  - ビルド成功を確認
  - ビルド後の起動確認（`npm run start`）
  - _Requirements: 4.7_

- [ ] 6.4 Laravel APIヘルスチェックを確認する
  - Laravel APIの起動を確認
  - http://localhost:13000/up へのアクセス確認
  - ヘルスチェックレスポンス正常性確認
  - _Requirements: 4.3_

---

## ドキュメント更新

- [x] 7. README.mdを更新する
- [x] 7.1 開発サーバー起動手順のポート情報を更新する
  - User Appのポートを13001に変更
  - Admin Appのポートを13002に変更
  - Laravel APIのポート13000を明記
  - 起動コマンド例を更新
  - _Requirements: 6.1, 6.2_

- [x] 7.2 E2Eテスト手順の環境変数設定例を更新する
  - E2E_ADMIN_URLの設定例を13002に更新
  - E2E_USER_URLの設定例を13001に更新
  - .env.exampleのコピー手順を明記
  - _Requirements: 6.3, 6.4_

- [x] 8. トラブルシューティングガイドを追加する
- [x] 8.1 ポート競合エラーの症状と解決方法を記載する
  - EADDRINUSEエラーの症状を記載
  - `lsof -i :13001 :13002`コマンドによるポート確認手順
  - `pkill -f "next dev"`によるプロセス停止手順
  - 再起動手順を記載
  - _Requirements: 6.5, 6.6, 8.1, 8.2, 8.3_

- [ ] 8.2 バックアップとロールバック手順を記載する
  - gitタグによるバックアップ手順
  - git checkoutによるロールバック手順
  - 緊急時の全ファイル復元手順
  - _Requirements: 8.4, 8.5_

---

## CI/CD設定確認

- [x] 9. CI/CD設定ファイルを確認・更新する
- [x] 9.1 E2Eワークフロー無効化ファイルにコメントを追加する
  - .github/workflows/e2e-tests.yml.disabledを確認
  - 将来的な有効化時のポート指定例を記載
  - admin-appのポート13002起動設定例を追記
  - user-appのポート13001起動設定例を追記
  - wait-onコマンドの正しいURL例を追記
  - _Requirements: 7.1, 7.2, 7.3, 7.4_

- [ ] 9.2 GitHub Secrets管理の確認を実施する
  - 必要な環境変数の一覧を確認
  - GitHub Secrets設定の必要性を文書化
  - 将来的なワークフロー有効化時の手順を記載
  - _Requirements: 7.5_

---

## 統合テストと最終検証

- [ ] 10. 全体統合テストを実施する
- [ ] 10.1 全アプリケーション同時起動テストを実施する
  - Laravel API、User App、Admin Appを同時起動
  - 全アプリが正常に起動することを確認
  - ポート競合がないことを確認
  - _Requirements: 1.5, 4.1, 4.2, 4.3_

- [ ] 10.2 クロスブラウザ動作確認を実施する
  - ChromeでUser AppとAdmin Appにアクセス
  - FirefoxでUser AppとAdmin Appにアクセス
  - Safariで User AppとAdmin Appにアクセス
  - 全ブラウザで正常表示を確認
  - _Requirements: 4.8_

- [ ] 10.3 E2E全テスト実行と検証を実施する
  - 全E2Eテストを実行（`npx playwright test`）
  - テスト実行結果の確認
  - HTMLレポートの生成と確認
  - アプリケーション未起動時のエラーメッセージ確認
  - global-setupの環境変数読み込み確認
  - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5_

- [ ] 10.4 最終品質チェックを実施する
  - TypeScript型チェック全通過確認
  - ESLint全通過確認
  - プロダクションビルド成功確認
  - 全テスト（Jest + Playwright）通過確認
  - _Requirements: 4.5, 4.6, 4.7_

---

## 完了条件

全タスク完了後、以下の状態であることを確認：

1. **設定ファイル更新完了**
   - user-app/package.json: dev/startスクリプトに`--port 13001`
   - admin-app/package.json: dev/startスクリプトに`--port 13002`
   - e2e/.env.example: URL設定が13001/13002
   - e2e/playwright.config.ts: baseURLが13001/13002
   - backend/laravel-api/config/cors.php: allowed_originsに新ポート追加

2. **動作確認完了**
   - 両アプリが指定ポートで正常起動
   - HMR正常動作
   - TypeScript型チェック・Lintチェック全通過
   - プロダクションビルド成功
   - CORS通信テスト全通過
   - E2Eテスト全通過

3. **ドキュメント整備完了**
   - README.md更新完了
   - トラブルシューティングガイド追加完了
   - CI/CD設定確認完了

4. **最終検証完了**
   - クロスブラウザ動作確認完了
   - 全アプリ同時起動確認完了
   - 全品質チェック通過

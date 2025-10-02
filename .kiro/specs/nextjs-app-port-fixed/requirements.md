# Requirements Document

## はじめに

現在、user-appとadmin-appはNext.jsのデフォルト動作に依存し、起動ごとに異なるポートが自動割り当てされています。これにより以下の問題が発生しています：

- E2EテストでbaseURL指定が不安定
- CI/CD環境での接続先が不確定
- 開発者間で異なるポート設定によるCORS問題
- ドキュメントとの実際の動作の乖離

本要件では、Next.jsアプリケーション（user-app、admin-app）の起動ポートを固定化し、すべての開発環境（ローカル/Docker/CI/E2E）で一貫性を担保することで、開発者エクスペリエンスの向上とテスト環境の安定化を実現します。

### ビジネス価値
- 開発者のオンボーディング時間の短縮（環境セットアップの簡素化）
- E2Eテストの信頼性向上（baseURL固定化による安定化）
- CORS設定の明確化（開発者間での設定相違の解消）
- ドキュメントと実際の動作の一致（保守性向上）

---

## Requirements

### Requirement 1: Next.jsアプリケーションのポート固定化

**Objective:** 開発者として、Next.jsアプリケーションが常に同じポートで起動することで、環境設定を統一し、開発効率を向上させたい。

#### Acceptance Criteria

1. WHEN user-appが`npm run dev`コマンドで起動される THEN user-appシステム SHALL ポート13001で起動する
2. WHEN user-appが`npm run start`コマンドで起動される THEN user-appシステム SHALL ポート13001で起動する
3. WHEN admin-appが`npm run dev`コマンドで起動される THEN admin-appシステム SHALL ポート13002で起動する
4. WHEN admin-appが`npm run start`コマンドで起動される THEN admin-appシステム SHALL ポート13002で起動する
5. WHEN 開発者が両アプリを同時起動する THEN 両システム SHALL ポート競合なく正常に起動する
6. WHERE package.jsonのscriptsセクション THE 各アプリシステム SHALL `--port`フラグを用いてポート指定を含む

---

### Requirement 2: E2Eテスト環境設定の更新

**Objective:** E2Eテストエンジニアとして、PlaywrightテストがNext.jsアプリケーションの固定ポートを使用することで、テスト実行の安定性を確保したい。

#### Acceptance Criteria

1. WHERE e2e/.envファイル THE E2E環境システム SHALL `E2E_ADMIN_URL=http://localhost:13002`を設定する
2. WHERE e2e/.envファイル THE E2E環境システム SHALL `E2E_USER_URL=http://localhost:13001`を設定する
3. WHERE e2e/.env.exampleファイル THE E2E環境システム SHALL .envと同一のURL設定例を提供する
4. WHERE e2e/playwright.config.tsのadmin-chromiumプロジェクト THE Playwrightシステム SHALL `baseURL: 'http://localhost:13002'`を設定する
5. WHERE e2e/playwright.config.tsのuser-chromiumプロジェクト THE Playwrightシステム SHALL `baseURL: 'http://localhost:13001'`を設定する
6. WHEN E2Eテストが実行される THEN Playwrightシステム SHALL 固定されたbaseURLを使用してテストを実行する

---

### Requirement 3: Laravel CORS設定の確認と更新

**Objective:** バックエンド開発者として、Laravel APIが新しいフロントエンドポートからのリクエストを正しく処理することで、CORS問題を防ぎたい。

#### Acceptance Criteria

1. WHERE backend/laravel-api/config/cors.phpファイル THE Laravel APIシステム SHALL `http://localhost:13002`（admin-app）をallowed_originsに含む
2. WHERE backend/laravel-api/config/cors.phpファイル THE Laravel APIシステム SHALL `http://localhost:13001`（user-app）をallowed_originsに含む
3. WHEN admin-appからOPTIONSリクエストが送信される THEN Laravel APIシステム SHALL 正しいCORSヘッダーを返す
4. WHEN user-appからOPTIONSリクエストが送信される THEN Laravel APIシステム SHALL 正しいCORSヘッダーを返す
5. IF 開発環境でワイルドカード許可が使用される THEN CORS設定システム SHALL `allowed_origins: ['*']`を許可する

---

### Requirement 4: 開発環境の動作確認

**Objective:** 開発者として、ポート変更後もすべての開発機能が正常に動作することで、既存の開発ワークフローを維持したい。

#### Acceptance Criteria

1. WHEN admin-appが起動される THEN システム SHALL http://localhost:13002 でアクセス可能である
2. WHEN user-appが起動される THEN システム SHALL http://localhost:13001 でアクセス可能である
3. WHEN Laravel APIが起動される THEN システム SHALL http://localhost:13000/up でヘルスチェックに応答する
4. WHEN ソースコードが変更される THEN HMR（Hot Module Replacement）システム SHALL 自動的に変更を反映する
5. WHEN `npm run type-check`が実行される THEN TypeScriptシステム SHALL 型エラーなしで完了する
6. WHEN `npm run lint`が実行される THEN ESLintシステム SHALL リントエラーなしで完了する
7. WHEN `npm run build`が実行される THEN ビルドシステム SHALL プロダクションビルドに成功する
8. WHEN 異なるブラウザ（Chrome/Firefox/Safari）でアクセスされる THEN 各アプリシステム SHALL 正常に表示される

---

### Requirement 5: E2Eテストの実行確認

**Objective:** QAエンジニアとして、E2Eテストがポート変更後も正常に動作することで、品質保証プロセスを継続したい。

#### Acceptance Criteria

1. WHEN `npx playwright test`が実行される THEN E2Eテストシステム SHALL 全テストを正常に実行する
2. WHEN `npx playwright test --project=admin-chromium`が実行される THEN E2Eテストシステム SHALL admin-app専用テストを正常に実行する
3. WHEN `npx playwright test --project=user-chromium`が実行される THEN E2Eテストシステム SHALL user-app専用テストを正常に実行する
4. WHEN E2Eテストが実行される AND アプリケーションが起動していない THEN E2Eテストシステム SHALL 適切なエラーメッセージを表示する
5. WHEN global-setupが実行される THEN 認証システム SHALL 環境変数で指定されたURLを使用して認証状態を生成する

---

### Requirement 6: ドキュメント更新

**Objective:** 新規参加開発者として、最新のドキュメントを参照することで、正しいポート情報で開発環境をセットアップしたい。

#### Acceptance Criteria

1. WHERE README.mdの開発サーバー起動手順 THE ドキュメントシステム SHALL Admin Appのポートを13002と記載する
2. WHERE README.mdの開発サーバー起動手順 THE ドキュメントシステム SHALL User Appのポートを13001と記載する
3. WHERE README.mdのE2Eテスト手順 THE ドキュメントシステム SHALL 正しい環境変数設定例を提供する
4. WHERE E2Eテストガイド THE ドキュメントシステム SHALL e2e/.env設定例を最新のポート情報で記載する
5. WHERE トラブルシューティングガイド THE ドキュメントシステム SHALL ポート競合エラーの症状と解決方法を記載する
6. WHERE トラブルシューティングガイド THE ドキュメントシステム SHALL `lsof -i :13001 :13002`コマンドによるポート確認方法を記載する

---

### Requirement 7: CI/CD設定の確認

**Objective:** DevOpsエンジニアとして、CI/CD環境でもポート設定が適切に反映されることで、自動化プロセスの信頼性を確保したい。

#### Acceptance Criteria

1. WHERE .github/workflows/e2e-tests.yml.disabled THE CI/CDシステム SHALL 将来的な有効化時のポート指定例を含む
2. IF E2Eワークフローが有効化される THEN CI/CDシステム SHALL admin-appをポート13002で起動する
3. IF E2Eワークフローが有効化される THEN CI/CDシステム SHALL user-appをポート13001で起動する
4. IF E2Eワークフローが有効化される THEN CI/CDシステム SHALL `wait-on`で正しいURLのヘルスチェックを実行する
5. WHERE CI/CD環境変数 THE CI/CDシステム SHALL GitHub Secretsで必要な認証情報を管理する

---

### Requirement 8: エラーハンドリングとロールバック

**Objective:** 運用担当者として、ポート変更による問題が発生した場合に迅速に対処できることで、サービスの継続性を確保したい。

#### Acceptance Criteria

1. WHEN ポート13001または13002が既に使用されている THEN システム SHALL `EADDRINUSE`エラーを明確に表示する
2. WHERE エラーメッセージ THE システム SHALL 競合しているポート番号を含む
3. WHERE トラブルシューティング手順 THE ドキュメントシステム SHALL `pkill -f "next dev"`によるプロセス停止方法を記載する
4. IF ポート変更に問題が発生する THEN 開発チーム SHALL 元の設定に戻すロールバック手順を実行可能である
5. WHERE バックアップ手順 THE ドキュメントシステム SHALL `git tag port-change-backup-$(date +%Y%m%d-%H%M%S)`によるバックアップ方法を記載する

---

## スコープ外の項目

以下の項目は本要件の対象外とします：

1. **Docker Compose設定**: 本プロジェクトにはDocker Compose未導入のため対象外（将来的な導入時に別途対応）
2. **プロダクション環境のポート設定**: 本番デプロイ環境はリバースプロキシ・ロードバランサー側で管理
3. **既存の開発者環境への強制適用**: 各開発者の移行は任意のタイミングで実施
4. **モバイルアプリとの連携**: 本要件はWeb開発環境のみが対象

---

## 技術的制約

1. **Next.js CLIの仕様**: `--port`オプションがNext.js公式サポート方式であり、これを使用する
2. **環境変数方式の非採用**: `PORT=13002 next dev`方式はWindows環境でのcross-env依存が必要なため採用しない
3. **next.config.jsの制限**: Next.js設定ファイルはポート設定に対応していないため使用不可
4. **既存E2Eテスト**: 現在一部のE2Eテストがスキップされているが、ポート変更後も同様の状態を維持する

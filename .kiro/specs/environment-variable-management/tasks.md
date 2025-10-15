# 実装計画: 環境変数適切管理方法整備

## 実装タスク一覧

- [x] 1. 環境変数テンプレートファイルの詳細化 ✅
- [x] 1.1 ルートディレクトリの .env.example に詳細コメントを追加 ✅
  - モノレポ全体で共通の環境変数（NEXT_PUBLIC_API_URL、APP_PORT、Docker関連ポート、E2E URL等）に対して、説明・必須性・環境別値例・セキュリティレベル・注意事項を含むコメントを追加
  - セクション分割により可読性を向上（Frontend、Docker Port Configuration、E2E Tests等）
  - 各変数のコメントフォーマット統一（説明、必須、環境、セキュリティ、デフォルト、注意事項）
  - _Requirements: 1.1, 1.2_
  - _Commit: 80b7111_

- [x] 1.2 Laravel .env.example に詳細コメントを追加 ✅
  - Laravel API固有の環境変数（APP_NAME、APP_ENV、APP_KEY、APP_DEBUG、DB関連、Sanctum、CORS等）に対して詳細コメントを追加
  - セクション分割（Application Configuration、Database Configuration、Sanctum Configuration、CORS Configuration等）
  - 条件付き必須項目の明示（DB_HOST、DB_PORT等はDB_CONNECTION次第）
  - セキュリティレベルの明示（公開可/機密/極秘）
  - _Requirements: 1.1, 1.3_
  - _Commit: 80b7111_

- [x] 1.3 E2E .env.example に詳細コメントを追加 ✅
  - E2Eテスト実行に必要な環境変数（E2E_ADMIN_URL、E2E_USER_URL、E2E_API_URL、認証情報等）に詳細コメントを追加
  - テスト環境固有の注意事項を記載（本番環境では使用しないこと等）
  - セキュリティレベルの明示（機密/極秘、ただしテスト用のため低リスク）
  - _Requirements: 1.1, 1.4_
  - _Commit: 80b7111_

- [x] 2. Laravel環境変数バリデーション機能の実装 ✅
- [x] 2.1 環境変数スキーマ定義を作成 ✅
  - スキーマファイルで環境変数の必須性、型、デフォルト値、許可値、条件付き必須を宣言的に定義
  - 主要な環境変数（APP_NAME、APP_ENV、APP_DEBUG、DB関連、Sanctum、CORS等）のスキーマを実装
  - 型定義（string、integer、boolean、url、email）と型検証ルールを定義
  - 条件付き必須項目の実装（DB_CONNECTION=pgsql/mysqlの場合、DB_HOSTが必須等）
  - セキュリティレベル定義（security_level: high等）
  - _Requirements: 2.1, 2.5_
  - _Commit: 5a3393d_

- [x] 2.2 環境変数バリデータクラスを実装 ✅
  - スキーマに基づく環境変数バリデーションロジックを実装
  - 必須チェック機能（条件付き必須も含む）
  - 型検証機能（string、integer、boolean、url、email）
  - 許可値チェック機能（allowed_values配列による検証）
  - エラーメッセージフォーマット機能（不足変数名、期待される型・値、設定例を含む）
  - 警告モード機能（エラーがあってもログ記録のみで起動継続）
  - _Requirements: 2.2, 2.3_
  - _Commit: 5a3393d_

- [x] 2.3 Bootstrapper（起動時バリデーション）を実装 ✅
  - アプリケーションブートストラップフェーズで環境変数バリデーションを実行
  - スキップフラグ機能（ENV_VALIDATION_SKIP=trueで緊急時にバリデーション無効化）
  - 警告モード・エラーモード切り替え機能（ENV_VALIDATION_MODE環境変数）
  - バリデーション成功時のログ記録
  - バリデーション失敗時のRuntimeException発生とアプリケーション起動停止
  - _Requirements: 2.1, 2.2_
  - _Commit: 4ccdc40_

- [x] 2.4 Artisan手動検証コマンドを実装 ✅
  - 手動で環境変数を検証するArtisanコマンドを実装
  - コマンドシグネチャ（env:validate --mode=error|warning）
  - 警告モード・エラーモードのオプション対応
  - 検証成功時の成功メッセージ表示
  - 検証失敗時のエラー詳細表示とコマンド終了ステータス設定
  - _Requirements: 2.4_
  - _Commit: 7c5952a_

- [x] 2.5 Bootstrapperをアプリケーションに登録 ✅
  - bootstrap/app.phpでBootstrapperを登録し、起動時バリデーションを有効化
  - withBootstrappers配列にValidateEnvironmentクラスを追加（→AppServiceProvider::boot()に変更）
  - 登録後の動作確認（起動時にバリデーションが実行されることを確認）
  - _Requirements: 2.1, 2.6_
  - _Commit: e731687_

- [x] 3. Next.js環境変数バリデーション機能の実装 ✅
- [x] 3.1 Admin App用Zodスキーマを実装 ✅
  - Zodスキーマによる環境変数定義を実装（NEXT_PUBLIC_API_URL、NODE_ENV等）
  - 型安全な環境変数オブジェクトのエクスポート
  - TypeScript型推論によるコンパイル時型チェックの有効化
  - 実行時バリデーション（Zodスキーマによる検証）
  - バリデーションエラー時の明確なエラーメッセージ表示
  - _Requirements: 3.1, 3.4, 3.5_
  - _Commit: 9cbd438_

- [x] 3.2 User App用Zodスキーマを実装 ✅
  - Zodスキーマによる環境変数定義を実装（Admin Appとほぼ同様だが、NEXT_PUBLIC_APP_NAME等の差分を反映）
  - 型安全な環境変数オブジェクトのエクスポート
  - TypeScript型推論によるコンパイル時型チェックの有効化
  - 実行時バリデーション（Zodスキーマによる検証）
  - バリデーションエラー時の明確なエラーメッセージ表示
  - _Requirements: 3.1, 3.4, 3.5_
  - _Commit: 9cbd438_

- [x] 3.3 ビルド前検証スクリプトとpackage.json統合を実装 ✅
  - ビルド前検証スクリプト（check-env.ts）を作成し、env.tsをインポートしてバリデーション実行
  - package.jsonのpredev/prebuildフックに検証スクリプトを統合
  - Admin App、User App両方に統合
  - バリデーション成功時の成功メッセージ表示
  - バリデーション失敗時のエラー表示と実行停止
  - _Requirements: 3.1, 3.2, 3.6_
  - _Commit: 9cbd438_

- [ ] 4. 環境変数同期スクリプトの実装
- [ ] 4.1 環境変数同期スクリプトを実装
  - .env.exampleと.envの差分検出機能を実装
  - 不足キー・未知キーの検出とリスト表示
  - --checkオプション（差分チェックのみ、書き込みなし）
  - --writeオプション（差分検出して.envに新規キーを追加）
  - .envファイルが存在しない場合の.env.exampleコピー機能
  - 既存値の保持（新規キーのみ追加）
  - 対象ファイル（ルート.env、backend/laravel-api/.env、e2e/.env）の全対応
  - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 4.7_

- [ ] 4.2 package.jsonスクリプト追加と動作確認
  - ルートpackage.jsonにenv:check、env:syncスクリプトを追加
  - env:checkスクリプトの動作確認（差分検出・表示）
  - env:syncスクリプトの動作確認（差分検出・同期）
  - Commander依存関係の追加（コマンドラインオプション解析用）
  - dotenv依存関係の追加（.envファイルパース用）
  - _Requirements: 4.1, 4.4_

- [ ] 5. GitHub Actions Secrets設定ガイドの作成
- [ ] 5.1 GitHub Actions Secrets設定ガイドドキュメントを作成
  - GitHub Actions Secretsの役割と重要性を説明
  - Secrets命名規約（{サービス}_{環境}_{変数名}パターン）の定義
  - Repository Secrets vs Environment Secretsの使い分け基準と設定手順
  - 必須Secrets一覧（Backend: DB_PASSWORD、APP_KEY等 / Frontend: NEXT_PUBLIC_API_URL_PROD等）
  - CI/CDワークフローでのSecrets参照方法（${{ secrets.SECRET_NAME }}）
  - セキュリティベストプラクティス（定期ローテーション、アクセス制御、監査ログ確認）
  - トラブルシューティング（Secret不足エラーの解決方法）
  - _Requirements: 5.1, 5.2_

- [ ] 6. 環境変数セキュリティガイドの作成
- [ ] 6.1 環境変数セキュリティガイドドキュメントを作成
  - セキュリティ原則（機密情報の定義、.env管理、バージョン管理からの除外）
  - Laravel/Next.jsセキュリティ設定（CORS、CSRFプロテクション、Sanctum認証設定）
  - CI/CDセキュリティ（GitHub Secrets暗号化、アクセス制御、監査ログ）
  - セキュリティチェックリスト（セットアップ時、運用時、インシデント対応）
  - インシデント対応手順（機密情報漏洩時の緊急対応、影響範囲調査、再発防止策）
  - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5_

- [ ] 7. README.md環境変数管理セクションの更新
- [ ] 7.1 README.mdに環境変数管理セクションを追加
  - セットアップ手順（.env.exampleコピー、環境変数設定、バリデーション実行）
  - 環境変数テンプレート構成（ルート、Laravel、E2E）
  - バリデーションコマンド（php artisan env:validate、npm run env:check/env:sync）
  - トラブルシューティング（よくあるエラーと解決方法）
  - 関連ドキュメントへのリンク（GitHub Actions Secrets、環境変数セキュリティガイド）
  - _Requirements: 10.1, 10.2, 10.3, 10.4_

- [ ] 8. CI/CDワークフロー統合
- [ ] 8.1 Laravel テストワークフローに環境変数バリデーションステップを追加
  - .github/workflows/test.ymlのRun database migrationsステップの前に環境変数バリデーションステップを追加
  - php artisan env:validateコマンドの実行
  - 必要な環境変数の設定（DB_CONNECTION、DB_TEST_HOST等）
  - バリデーション失敗時のワークフロー失敗
  - _Requirements: 7.1, 7.6_

- [ ] 8.2 フロントエンドテストワークフローに環境変数バリデーションステップを追加
  - .github/workflows/frontend-test.ymlのRun tests with coverageステップの前に環境変数バリデーションステップを追加
  - 各アプリのpackage.jsonにenv:checkスクリプトを追加
  - npm run env:checkコマンドの実行
  - 必要な環境変数の設定（NEXT_PUBLIC_API_URL、NODE_ENV=test）
  - バリデーション失敗時のワークフロー失敗
  - _Requirements: 7.2, 7.6_

- [ ] 8.3 環境変数バリデーション専用ワークフローを作成
  - .github/workflows/environment-validation.ymlを新規作成
  - concurrency設定（PR内の連続コミットで古い実行を自動キャンセル）
  - paths設定（環境変数関連ファイル変更時のみ実行）
  - validate-laravelジョブ（Laravel環境変数バリデーション）
  - validate-nextjsジョブ（Next.js環境変数バリデーション、Admin/User Appマトリクス実行）
  - validate-env-syncジョブ（環境変数同期チェック）
  - _Requirements: 7.3, 7.4, 7.5, 7.7_

- [ ] 8.4 CI/CD環境での動作確認
  - Pull Request作成時のワークフロー自動実行確認
  - 環境変数関連ファイル変更時のワークフロートリガー確認
  - バリデーション成功時のチェックステータス確認
  - バリデーション失敗時のチェックステータスとエラーメッセージ確認
  - ビルド時間増加の確認（約10-20秒増加、許容範囲内）
  - _Requirements: 7.6, 7.7, 7.8_

- [x] 9. ユニットテスト・統合テストの実装（一部完了）
- [x] 9.1 Laravel EnvValidatorのユニットテストを実装 ✅
  - 必須変数不足時のRuntimeException検証テスト
  - 正常な環境変数でのバリデーション成功テスト
  - 型違いでのバリデーションエラーテスト
  - 許可値リストにない値でのエラーテスト
  - 警告モードでのバリデーション成功テスト
  - 条件付き必須チェックのテスト
  - _Requirements: 8.1, 8.2_
  - _テストカバレッジ: 35テスト（EnvSchema: 10, EnvValidator: 10, ValidateEnvironment: 7, ValidateEnvCommand: 8）_
  - _Commit: 5a3393d, 4ccdc40, 7c5952a_

- [x] 9.2 Next.js Zodスキーマのユニットテストを実装 ✅
  - 正常な環境変数でのバリデーション成功テスト（Admin App: 8テスト）
  - 不正なURL形式でのバリデーションエラーテスト（Admin App）
  - 許可されていないNODE_ENVでのエラーテスト（Admin App）
  - 必須環境変数不足時のエラーテスト（Admin App）
  - User App用の同様のテストを実装（User App: 10テスト）
  - _Requirements: 8.3, 8.4_
  - _テストカバレッジ: 18テスト（Admin App: 8, User App: 10）_
  - _Commit: 9cbd438_

- [ ] 9.3 環境変数同期スクリプトの統合テストを実装
  - .env.exampleのみ存在する場合の.env作成テスト
  - .envに既存値がある場合の新規キーのみ追加テスト
  - env:checkでの不足キー検出テスト
  - env:checkでの未知キー検出テスト
  - テストフィクスチャのセットアップ・クリーンアップ
  - _Requirements: 8.5, 8.6_

- [ ] 10. CI/CD環境でのE2Eテストとエラーメッセージ確認
- [ ] 10.1 CI/CD環境でのE2Eテストシナリオを実行
  - 環境変数不足時のLaravel API起動失敗テスト
  - 環境変数不足時のNext.jsビルド失敗テスト
  - GitHub Actions環境変数バリデーションワークフロー実行テスト
  - バリデーションエラーメッセージの明瞭性確認
  - エラーメッセージの改善（新規メンバーでも理解できる内容）
  - _Requirements: 8.7, 8.8_

- [ ] 11. チーム展開とロールアウト
- [ ] 11.1 警告モードでのロールアウト準備
  - 警告モード（ENV_VALIDATION_MODE=warning）の動作確認
  - 警告モード導入時のドキュメント整備（マイグレーション期間、対応手順）
  - チームレビュー実施（最低2名の承認）
  - ロールアウト計画の承認
  - トラブルシューティング体制の整備（問い合わせ窓口、緊急対応手順）
  - _Requirements: 9.1, 9.2, 9.5_

- [ ] 11.2 エラーモードへの移行とフィードバック対応
  - マイグレーション期間（2週間）の経過確認
  - エラーモード（ENV_VALIDATION_MODE=error）への切り替え
  - 本番環境へのデプロイ
  - 運用開始とフィードバック収集
  - フィードバック反映（エラーメッセージ改善、ドキュメント更新等）
  - _Requirements: 9.3, 9.4, 9.6, 10.5, 10.6, 10.7_

## Requirements カバレッジ確認

全10個の主要要件が以下のタスクでカバーされています:

- **Requirement 1 (環境変数テンプレートファイル詳細化)**: タスク 1.1, 1.2, 1.3
- **Requirement 2 (Laravel環境変数バリデーション機能)**: タスク 2.1, 2.2, 2.3, 2.4, 2.5
- **Requirement 3 (Next.js環境変数型安全アクセス機能)**: タスク 3.1, 3.2, 3.3
- **Requirement 4 (環境変数同期スクリプト機能)**: タスク 4.1, 4.2
- **Requirement 5 (GitHub Actions Secrets統合ガイド機能)**: タスク 5.1
- **Requirement 6 (環境変数セキュリティガイド機能)**: タスク 6.1
- **Requirement 7 (CI/CDワークフロー環境変数バリデーション統合機能)**: タスク 8.1, 8.2, 8.3, 8.4
- **Requirement 8 (テスト戦略と品質保証機能)**: タスク 9.1, 9.2, 9.3, 10.1
- **Requirement 9 (段階的ロールアウトと移行戦略機能)**: タスク 11.1, 11.2
- **Requirement 10 (チーム標準化とドキュメント整備機能)**: タスク 7.1, 11.1, 11.2

## 実装優先順位

- **高**: タスク 1, 2, 3（環境変数テンプレート詳細化、バリデーション実装）
- **中**: タスク 4, 8（同期スクリプト、CI/CD統合）
- **低**: タスク 5, 6, 7, 9, 10, 11（ドキュメント作成、テスト、チーム展開）

## 期待される効果

- 環境変数設定ミスによる実行時エラーの防止
- 新規メンバーのオンボーディング時間の短縮（15分以内）
- セキュリティインシデントリスクの低減
- CI/CDビルド失敗の早期検出

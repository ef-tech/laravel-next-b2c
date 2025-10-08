# Implementation Plan

## タスク概要

PostgreSQL 17接続設定の最適化を、既存のLaravel 12データベース設定アーキテクチャを拡張する形で実装します。後方互換性を維持しながら、タイムアウト設定、PDO最適化、環境別設定分離を実現します。

---

- [ ] 1. PostgreSQL接続設定の最適化実装
- [x] 1.1 データベース接続設定の拡張
  - 既存のPostgreSQL接続設定にタイムアウトパラメータを追加
  - アプリケーション名の設定を追加（接続追跡用）
  - PostgreSQL GUC設定（statement_timeout、idle_in_transaction_session_timeout、lock_timeout）を環境変数経由で適用
  - PDO属性の最適化設定を追加（ATTR_EMULATE_PREPARES、ATTR_ERRMODE）
  - 環境変数のデフォルト値フォールバックを実装
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 1.6_

- [x] 1.2 接続設定の検証とテスト
  - 設定値読み込みの単体テストを作成
  - GUC設定文字列生成の単体テストを作成
  - PDO属性設定の単体テストを作成
  - 環境変数フォールバックの単体テストを作成
  - _Requirements: 1.1, 1.2, 1.3, 1.6_

- [ ] 2. 環境別設定テンプレートの作成
- [x] 2.1 バックエンド環境変数テンプレートの作成
  - Docker環境用PostgreSQL設定をコメント付きで追加
  - ネイティブ環境用PostgreSQL設定をコメント付きで追加
  - 本番環境用PostgreSQL設定（SSL設定含む）をコメント付きで追加
  - タイムアウト設定の詳細説明をインラインコメントで記載
  - PDO設定の説明をインラインコメントで記載
  - 既存SQLiteデフォルト設定を維持
  - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 2.6, 3.1, 3.2_

- [x] 2.2 ルート環境変数テンプレートの整合性確認
  - ルート.env.exampleとバックエンド.env.exampleの整合性を確認
  - 必要に応じてルート.env.exampleにもPostgreSQL設定のコメントを追加
  - _Requirements: 2.1_

- [ ] 3. Docker環境での接続検証
- [x] 3.1 Docker環境での基本接続確認
  - PostgreSQLコンテナの起動とヘルスチェック状態を確認
  - pg_isreadyコマンドで接続受付可能状態を確認
  - Laravel API内からPDO接続オブジェクトの取得を確認
  - PostgreSQL 17のバージョン情報を取得して確認
  - _Requirements: 4.1, 4.2, 4.3, 4.4_

- [x] 3.2 Docker環境でのタイムアウト設定確認
  - statement_timeout設定値の確認（SHOW statement_timeout）
  - 環境変数で指定したタイムアウト値が正しく適用されていることを確認
  - _Requirements: 4.5_

- [x] 3.3 Docker環境でのマイグレーション確認
  - migrate:fresh --seedの実行確認
  - migrate:statusの実行確認
  - マイグレーション実行が正常に完了することを確認
  - _Requirements: 4.6_

- [ ] 4. ネイティブ環境での接続検証
- [ ] 4.1 ネイティブ環境からの接続確認
  - ホストマシンからDockerコンテナPostgreSQLへの接続確認（DB_HOST=127.0.0.1、DB_PORT=13432）
  - php artisan config:show databaseで設定表示確認
  - php artisan migrate:statusでマイグレーション状態確認
  - _Requirements: 5.1, 5.2, 5.3_

- [ ] 4.2 ネイティブ環境でのテストスイート実行
  - php artisan testの全テスト成功確認
  - PostgreSQL接続を使用したテストの正常動作確認
  - _Requirements: 5.4_

- [ ] 5. タイムアウト動作の検証とテスト
- [x] 5.1 タイムアウトテストの作成
  - statement_timeout超過テスト（長時間クエリ）を作成 ✅
  - idle_in_transaction_session_timeout超過テスト（放置トランザクション）を作成 ✅
  - connect_timeout動作確認テスト（接続確立時間）を作成 ✅
  - lock_timeout設定確認テスト（設定値検証）を作成 ✅
  - タイムアウト設定値の確認テスト（PostgreSQLセッション適用）を作成 ✅
  - .env.testing.pgsqlにタイムアウト設定追加 ✅
  - _Requirements: 6.1, 6.2, 6.3, 6.4_

- [ ] 5.2 タイムアウトテスト結果の記録
  - テスト結果をLaravelログに記録
  - テスト結果をドキュメントに記載
  - _Requirements: 6.5_

- [ ] 6. 後方互換性の検証
- [x] 6.1 既存SQLite環境での動作確認
  - PostgreSQL設定追加後もSQLiteデフォルト設定が維持されていることを確認
  - 既存のマイグレーション実行が正常に動作することを確認
  - 既存のテストスイートが正常に実行されることを確認（DatabaseConfigTest: 5 tests passed）
  - _Requirements: 3.1, 3.3_

- [ ] 6.2 PostgreSQL設定有効化の手順確認
  - .envファイルでのコメント解除と値変更による切り替え確認
  - 切り替え後の動作確認
  - _Requirements: 3.4_

- [ ] 7. ドキュメント整備
- [x] 7.1 PostgreSQL接続設定ドキュメントの作成
  - Docker環境での接続方法を記載 ✅
  - ネイティブ環境での接続方法を記載 ✅
  - 本番環境での推奨設定を記載（SSL設定含む） ✅
  - 各環境変数の説明と設定値の根拠を記載 ✅
  - トラブルシューティング手順を記載（接続失敗、タイムアウトエラー、SSL証明書エラー） ✅
  - パフォーマンステスト手順を記載（接続時間計測、クエリ実行時間計測） ✅
  - 推奨監視項目を記載（接続数、statement_timeout超過回数、アイドルトランザクション数） ✅
  - _Requirements: 7.1, 7.2, 7.3, 7.4, 10.5_

- [x] 7.2 README.mdの更新
  - PostgreSQL接続設定セクションを追加 ✅
  - database-connection.mdへのリンクを記載 ✅
  - クイックスタートガイドを追加（.env設定の手順） ✅
  - _Requirements: 7.5_

- [ ] 8. CI/CD環境での動作確認
- [ ] 8.1 GitHub Actionsテストワークフローの実行確認
  - PostgreSQL接続設定を使用したテストが全て成功することを確認
  - マイグレーション実行が正常に完了することを確認
  - 接続エラー発生時の明確なエラーメッセージ出力を確認
  - _Requirements: 8.1, 8.3, 8.4_

- [ ] 8.2 E2Eテスト環境での接続確認
  - E2Eテスト環境でPostgreSQL接続が正常に確立されることを確認
  - 全てのE2Eテストが成功することを確認
  - _Requirements: 8.2_

- [ ] 9. 統合テストと品質確認
- [x] 9.1 統合テストの実行
  - Docker環境での統合テスト実行
  - ネイティブ環境での統合テスト実行（SQLite環境で全52テスト成功）
  - 全テストケースの成功確認
  - _Requirements: All_

- [x] 9.2 コード品質チェック
  - Laravel Pint実行（コードフォーマット確認）✅
  - Larastan実行（静的解析確認）✅
  - テストカバレッジ確認（DatabaseConfigTest: 26 assertions）
  - _Requirements: All_

- [ ] 9.3 最終動作確認
  - Docker環境での全機能動作確認
  - ネイティブ環境での全機能動作確認
  - 後方互換性の最終確認
  - ドキュメントの完全性確認
  - _Requirements: All_

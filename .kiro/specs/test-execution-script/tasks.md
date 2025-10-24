# 実装計画

## フェーズ1: スクリプト基盤とユーティリティライブラリ構築

- [x] 1. 共通関数ライブラリ実装
- [x] 1.1 色定義と出力フォーマット関数作成
  - ANSI色コード定数を定義（RED、GREEN、YELLOW、BLUE、NC）
  - 色なし出力対応（CI環境での互換性確保）
  - _Requirements: 12.1, 12.4_

- [x] 1.2 ログ出力統一関数実装
  - 5種類のログレベル関数作成（info、success、warn、error、debug）
  - タイムスタンプ付きログフォーマット実装
  - デバッグモード対応（DEBUG=1環境変数）
  - stderr出力でのメッセージ表示
  - _Requirements: 12.1, 12.2, 12.3_

- [x] 1.3 共通ライブラリファイル配置と権限設定
  - scripts/lib/ディレクトリ作成
  - colors.shとlogging.sh配置
  - 実行権限付与（chmod +x）
  - 依存関係チェック機能実装
  - _Requirements: 12.5, 16.1_

- [x] 2. テストレポートディレクトリ構造構築
- [x] 2.1 ディレクトリ自動生成機能実装
  - test-results/配下の4階層ディレクトリ作成（junit、coverage、reports、logs）
  - 既存ディレクトリの保持とクリーンアップロジック
  - CI環境での一時ファイル削除機能
  - _Requirements: 13.1, 13.2, 13.3, 13.4, 13.5, 13.6_

## フェーズ2: テストスイート実行スクリプト実装

- [x] 3. バックエンドテスト実行抽象化レイヤー構築
- [x] 3.1 バックエンドテスト実行関数実装
  - DB環境選択ロジック（SQLite/PostgreSQL切り替え）
  - 既存Makefileタスク呼び出し（quick-test、test-parallel）
  - 並列実行数制御
  - JUnit XMLレポート出力設定確認
  - _Requirements: 2.1, 3.1, 3.2, 3.3, 4.5, 14.1, 14.2_

- [x] 3.2 カバレッジレポート生成機能統合
  - --coverageオプション対応
  - カバレッジレポートディレクトリ出力（test-results/coverage/backend/）
  - 既存のtest-coverageタスク再利用
  - _Requirements: 6.1, 6.2_

- [x] 3.3 バックエンドテストエラーハンドリング
  - exit code記録とログファイル保存（backend.log）
  - テスト失敗時の継続実行制御（set +e）
  - _Requirements: 7.1, 7.6, 7.7_

- [ ] 4. フロントエンドテスト実行抽象化レイヤー構築
- [ ] 4.1 フロントエンドテスト並列実行機能実装
  - Admin AppとUser App並列実行ロジック
  - npm testコマンド呼び出し（バックグラウンド実行 + wait）
  - 各アプリのJUnit XMLレポート出力設定確認
  - _Requirements: 2.2, 14.3, 14.4_

- [ ] 4.2 フロントエンドカバレッジレポート生成
  - --coverageオプション対応
  - 各アプリのカバレッジレポート出力（test-results/coverage/frontend-admin/、frontend-user/）
  - _Requirements: 6.3, 6.4_

- [ ] 4.3 フロントエンドテストエラーハンドリング
  - 各アプリのexit code記録とログファイル保存
  - テスト失敗時の継続実行制御
  - _Requirements: 7.2, 7.8_

- [ ] 5. E2Eテスト実行抽象化レイヤー構築
- [ ] 5.1 サービスヘルスチェック機能実装
  - 3サービスのヘルスエンドポイント確認（Laravel API、User App、Admin App）
  - 最大120秒リトライロジック
  - タイムアウト時のエラーメッセージ表示
  - _Requirements: 10.1, 10.2, 10.3, 10.4, 10.5, 10.6_

- [ ] 5.2 E2Eテスト実行機能実装
  - Playwright並列実行制御（Shard対応）
  - npx playwright testコマンド呼び出し
  - JUnit XMLレポート出力設定確認
  - _Requirements: 2.3, 14.5, 14.6_

- [ ] 5.3 E2Eテストエラーハンドリング
  - exit code記録とログファイル保存（e2e.log）
  - サービスヘルスチェック失敗時の即座終了
  - _Requirements: 7.3, 7.6_

## フェーズ3: オーケストレーション層と統合制御

- [ ] 6. メインオーケストレーションスクリプト実装
- [ ] 6.1 環境変数バリデーション機能
  - 必須環境変数チェックリスト作成
  - 未設定時のエラーメッセージ表示
  - _Requirements: 1.3_

- [ ] 6.2 ポート競合チェック機能
  - 5ポートの使用状況確認（13000、13001、13002、13432、13379）
  - lsofコマンド実行とエラーメッセージ表示
  - _Requirements: 1.4_

- [ ] 6.3 CLI引数解析とオプション処理
  - --suite、--env、--parallel、--coverage、--report、--ci、--fastオプション解析
  - デフォルト値設定（suite=all、env=sqlite、parallel=4）
  - ヘルプメッセージ表示機能（--help）
  - _Requirements: 1.1, 1.2, 2.1, 2.2, 2.3, 3.1, 3.2, 4.1, 4.2, 5.1, 9.1_

- [ ] 6.4 並列実行制御とプロセス管理
  - バックエンドとフロントエンドの並列実行ロジック（バックグラウンド実行 + wait）
  - E2Eテストの順次実行（サービス起動後）
  - _Requirements: 1.5, 1.6_

- [ ] 6.5 統合エラーハンドリングとexit code管理
  - 各テストスイートのexit code記録
  - 失敗したテストスイートのリスト管理
  - 最終的なexit code決定ロジック
  - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.5_

## フェーズ4: レポート生成と統合サマリー

- [ ] 7. 統合レポート生成機能実装
- [ ] 7.1 JUnit XMLレポート統合処理
  - 各テストスイートのJUnit XMLレポート収集
  - test-results/junit/配下のファイル確認
  - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5_

- [ ] 7.2 統合サマリーJSON生成
  - タイムスタンプ、実行時間、総テスト数、成功数、失敗数の集計
  - 各スイート別結果の構造化
  - 失敗テストリストの生成
  - test-results/reports/test-summary.json出力
  - _Requirements: 5.6, 5.7_

- [ ] 7.3 GitHub Actions Summary Markdown生成
  - CI環境判定（$GITHUB_STEP_SUMMARY存在確認）
  - Markdownフォーマット統合サマリー作成
  - テーブル形式の結果表示
  - $GITHUB_STEP_SUMMARYファイルへの追記
  - _Requirements: 5.8, 9.3_

## フェーズ5: 診断スクリプトと運用ツール

- [ ] 8. テスト環境診断スクリプト実装
- [ ] 8.1 ポート使用状況診断機能
  - 5ポートの使用状況確認とプロセス表示
  - _Requirements: 11.2_

- [ ] 8.2 環境変数診断機能
  - 必須環境変数の設定状態確認
  - _Requirements: 11.3_

- [ ] 8.3 Dockerコンテナ診断機能
  - docker psコマンド実行と状態表示
  - _Requirements: 11.4_

- [ ] 8.4 データベース接続診断機能
  - PostgreSQL接続確認
  - _Requirements: 11.5_

- [ ] 8.5 システムリソース診断機能
  - ディスク空き容量確認
  - メモリ使用状況確認
  - _Requirements: 11.6, 11.7_

- [ ] 8.6 診断結果統合出力
  - 全診断結果のコンソール表示
  - エラー状況のサマリー表示
  - _Requirements: 11.1, 11.8_

## フェーズ6: Makefile統合とCI/CD連携

- [ ] 9. Makefile新規タスク追加
- [ ] 9.1 基本テストタスク実装
  - test-allタスク追加（SQLite環境全テスト）
  - test-all-pgsqlタスク追加（PostgreSQL環境並列全テスト）
  - test-backend-onlyタスク追加
  - test-frontend-onlyタスク追加
  - test-e2e-onlyタスク追加
  - _Requirements: 8.1, 8.2, 8.3, 8.4, 8.5_

- [ ] 9.2 高度なテストタスク実装
  - test-with-coverageタスク追加（PostgreSQL + カバレッジ）
  - test-prタスク追加（lint + PostgreSQL + カバレッジ）
  - test-smokeタスク追加（スモークテスト高速実行）
  - test-diagnoseタスク追加（診断スクリプト呼び出し）
  - _Requirements: 8.6, 8.7, 8.8, 8.9_

- [ ] 9.3 既存タスク互換性確認
  - quick-test、test-pgsql、test-parallel、ci-testタスク動作確認
  - _Requirements: 8.10_

- [ ] 10. GitHub Actionsワークフロー作成
- [ ] 10.1 ワークフロートリガー設定
  - Pull Request、mainブランチpush、手動実行トリガー
  - paths-filterアクション統合
  - _Requirements: 9.4, 9.5, 9.6_

- [ ] 10.2 CI環境テスト実行ジョブ実装
  - --ciオプション使用したテスト実行
  - Composerキャッシング設定
  - Node.jsキャッシング設定
  - _Requirements: 9.1, 9.2_

- [ ] 10.3 テスト結果アーティファクトアップロード
  - test-results/ディレクトリのアップロード
  - GitHub Actions Summary表示確認
  - _Requirements: 9.7_

## フェーズ7: ドキュメント整備と最終検証

- [ ] 11. テスト実行ガイドドキュメント作成
- [ ] 11.1 クイックスタートガイド作成
  - 基本コマンドの使用例
  - よく使うコマンドパターン
  - _Requirements: 15.1, 15.2_

- [ ] 11.2 ローカルテスト実行ガイド作成
  - テストスイート別実行方法
  - DB環境選択方法
  - カバレッジレポート確認方法
  - _Requirements: 15.2_

- [ ] 11.3 CI/CD実行ガイド作成
  - GitHub Actionsワークフロー説明
  - Artifactsダウンロード方法
  - _Requirements: 15.2_

- [ ] 12. トラブルシューティングガイド作成
- [ ] 12.1 よくある問題と解決策記載
  - ポート競合エラー対処法
  - DB接続エラー対処法
  - メモリ不足エラー対処法
  - 並列実行失敗対処法
  - _Requirements: 15.3, 15.4_

- [ ] 12.2 診断スクリプト使用方法記載
  - make test-diagnoseコマンド説明
  - 診断結果の読み方
  - _Requirements: 15.5_

- [ ] 12.3 ログ分析方法記載
  - ログファイル構造説明
  - エラーメッセージの解読方法
  - _Requirements: 15.5_

- [ ] 13. プロジェクトドキュメント更新
- [ ] 13.1 README.md更新
  - 新規コマンド追加
  - テスト実行セクション拡充
  - _Requirements: 15.6_

- [ ] 13.2 CLAUDE.md更新
  - Active Specificationsリスト追加
  - test-execution-script仕様追加
  - _Requirements: 15.7_

- [ ] 14. 最終検証と統合テスト
- [ ] 14.1 ローカル環境統合テスト
  - 全Makefileタスク実行確認
  - レポート生成確認
  - エラーハンドリング動作確認
  - _Requirements: 全要件_

- [ ] 14.2 CI/CD環境統合テスト
  - Pull Request作成とワークフロー実行確認
  - Artifactsアップロード確認
  - GitHub Actions Summary表示確認
  - _Requirements: 全要件_

# 実装タスク

## 1. 基盤整備（Breaking Change適用）

- [ ] 1. 環境変数テンプレートのポート番号レンジ変更を適用する
- [ ] 1.1 .env.exampleの全9サービスのポート番号を新レンジに更新する
  - User Appを13001から13100に変更
  - Admin Appを13002から13200に変更
  - MinIO Consoleを13010から13300に変更
  - PostgreSQLを13432から14000に変更
  - Redisを13379から14100に変更
  - Mailpit UIを13025から14200に変更
  - Mailpit SMTPを11025から14300に変更
  - MinIO APIを13900から14400に変更
  - WORKTREE_ID環境変数をデフォルト値0で追加
  - _Requirements: 11.1, 11.2_

- [ ] 1.2 Docker Compose設定をworktree対応の動的設定に変更する
  - コンテナ名を環境変数COMPOSE_PROJECT_NAMEベースの動的命名に変更
  - ネットワーク名を環境変数ベースの動的命名に変更
  - ボリューム名を環境変数ベースの動的命名に変更
  - DB_DATABASE環境変数の動的設定を追加
  - CACHE_PREFIX環境変数の動的設定を追加
  - _Requirements: 4.1, 4.2, 4.3, 4.4_

- [ ] 1.3 フロントエンドアプリのポート番号設定を新レンジに変更する
  - User Appのpackage.jsonでdevスクリプトのポート番号を13001から13100に変更
  - User Appのstartスクリプトのポート番号を13001から13100に変更
  - Admin Appのpackage.jsonでdevスクリプトのポート番号を13002から13200に変更
  - Admin Appのstartスクリプトのポート番号を13002から13200に変更
  - _Requirements: 6.1, 6.2_

## 2. ポート管理スクリプトの実装

- [ ] 2. ポート番号割り当て管理機能を実装する
- [ ] 2.1 port-manager.shスクリプトの基本構造を作成する
  - scripts/worktree/ディレクトリを作成
  - port-manager.shファイルを作成
  - エラー処理設定（set -euo pipefail）を追加
  - 基本的なヘルプメッセージ機能を実装
  - _Requirements: 7.1_

- [ ] 2.2 worktree ID管理機能を実装する
  - git worktree listコマンドで使用中worktreeを取得する機能
  - 使用中worktree IDを抽出する機能
  - 0-99の範囲で次に利用可能なIDを検索する機能（削除済みIDを優先再利用）
  - 上限チェック機能（最大100個まで）を実装
  - _Requirements: 7.1, 7.2, 7.5_

- [ ] 2.3 ポート番号計算機能を実装する
  - worktree IDから全9サービスのポート番号を計算する機能
  - 計算式（base_port + worktree_id）を適用
  - JSON形式でポート番号設定を出力する機能
  - ポート番号からworktree IDを逆算する機能
  - _Requirements: 7.3, 7.6_

- [ ] 2.4 worktreeポート番号一覧表示機能を実装する
  - 全worktreeとそのポート番号を表形式で表示
  - worktree ID、ブランチ名、全9サービスのポート番号を含む
  - 見やすいテーブルフォーマットで出力
  - _Requirements: 7.4_

## 3. Worktreeセットアップ自動化スクリプトの実装

- [ ] 3. Worktree作成とセットアップの完全自動化機能を実装する
- [ ] 3.1 setup.shスクリプトの基本構造と入力検証を作成する
  - setup.shファイルを作成
  - エラー処理設定（set -euo pipefail）を追加
  - ブランチ名引数の検証機能
  - ブランチ存在確認機能
  - エラー時の適切なメッセージ表示機能
  - _Requirements: 8.1, 8.2_

- [ ] 3.2 worktree作成とID割り当て機能を実装する
  - port-manager.shを呼び出して次に利用可能なIDを取得
  - git worktree addコマンドでworktreeを作成
  - worktree作成パスの推奨構造を実装（~/worktrees/配下）
  - ID枯渇時のエラーハンドリング
  - _Requirements: 8.1, 8.3_

- [ ] 3.3 環境変数ファイル生成機能を実装する
  - .env.exampleをworktreeディレクトリにコピー
  - WORKTREE_ID環境変数を設定
  - COMPOSE_PROJECT_NAME環境変数を設定（wt{ID}形式）
  - DB_DATABASE環境変数を設定（laravel_wt{ID}形式）
  - CACHE_PREFIX環境変数を設定（wt{ID}_形式）
  - 全9サービスのポート番号環境変数を設定
  - _Requirements: 8.1, 2.1, 3.1, 3.2_

- [ ] 3.4 フロントエンド環境変数設定機能を実装する
  - NEXT_PUBLIC_API_URL環境変数を動的ポート番号で設定
  - NEXT_PUBLIC_API_BASE_URL環境変数を設定
  - E2E_ADMIN_URL環境変数を動的ポート番号で設定
  - E2E_USER_URL環境変数を動的ポート番号で設定
  - E2E_API_URL環境変数を動的ポート番号で設定
  - _Requirements: 6.3, 6.4_

- [ ] 3.5 PostgreSQLデータベース自動作成機能を実装する
  - backend/laravel-api/docker/pgsql/init.d/ディレクトリを作成
  - create-database.shスクリプトを作成
  - DB_DATABASE環境変数を読み込む機能
  - データベースが存在しない場合のみ作成する機能
  - エラーハンドリングとログ出力機能
  - _Requirements: 2.2, 2.3, 8.5_

- [ ] 3.6 依存関係インストールとキャッシュクリア機能を実装する
  - Laravelプロジェクトにcomposer installを実行
  - User Appにnpm installを実行
  - Admin Appにnpm installを実行
  - Laravelキャッシュをクリア
  - Laravelストレージディレクトリの権限設定
  - 各ステップのエラーハンドリング
  - _Requirements: 8.1, 8.6_

- [ ] 3.7 セットアップ完了メッセージ表示機能を実装する
  - worktree IDの表示
  - worktreeパスの表示
  - 全9サービスのポート番号一覧の表示
  - 次のステップ（Docker起動コマンド等）の表示
  - エラー発生時の詳細なエラーメッセージ表示
  - _Requirements: 8.7, 8.8_

## 4. Makefile統合とCLI操作

- [ ] 4. Worktree管理のMakefileコマンドを実装する
- [ ] 4.1 make worktree-createコマンドを実装する
  - BRANCH引数を受け取る機能
  - BRANCH引数未指定時のヘルプメッセージ表示
  - setup.shスクリプトを呼び出す機能
  - エラー時の適切なメッセージ表示
  - _Requirements: 9.1, 9.6_

- [ ] 4.2 make worktree-listコマンドを実装する
  - git worktree listコマンドを実行する機能
  - 全worktreeの一覧を表示
  - _Requirements: 9.2_

- [ ] 4.3 make worktree-portsコマンドを実装する
  - port-manager.sh listコマンドを呼び出す機能
  - 全worktreeのポート番号一覧を表示
  - _Requirements: 9.3_

- [ ] 4.4 make worktree-removeコマンドを実装する
  - PATH引数を受け取る機能
  - PATH引数未指定時のヘルプメッセージ表示
  - git worktree removeコマンドを実行する機能
  - worktree削除後のID再利用可能化
  - エラー時の適切なメッセージ表示
  - _Requirements: 9.4, 9.5, 9.6_

## 5. .gitignore設定とgit管理

- [ ] 5. Worktreeディレクトリのgit管理除外設定を追加する
- [ ] 5.1 .gitignoreにworktreeパターンを追加する
  - worktreeディレクトリ除外パターンを追加（/wt-*, /worktree-*, /worktrees/*）
  - worktreeメタデータ除外パターンを追加（/.git/worktrees/）
  - コメントで明確なセクション分けを追加
  - _Requirements: 10.1, 10.2_

- [ ] 5.2 .gitignore動作の検証機能を追加する
  - git statusでworktreeディレクトリが表示されないことを確認
  - リポジトリ内worktree作成時の除外動作を確認
  - _Requirements: 10.3, 10.4_

## 6. ドキュメント整備

- [ ] 6. 並列開発環境のドキュメントを作成する
- [ ] 6.1 README.mdに並列開発セクションを追加する
  - 「🌳 並列開発（git worktree）」セクションを追加
  - worktreeの概要説明を記述（Claude Code並列実行のメリット）
  - ポート番号レンジ分離方式の説明を記述
  - データベース分離戦略の説明を記述
  - リソース使用量の説明を記述（1worktree: 1GB、5-8worktrees: 5GB）
  - 推奨システム要件を記述（最小16GB RAM、推奨32GB RAM）
  - _Requirements: 12.1, 12.2, 12.5, 12.6_

- [ ] 6.2 README.mdに使用例とコマンドリファレンスを追加する
  - セットアップ手順（make worktree-createコマンド）を記述
  - ポート番号確認方法（make worktree-portsコマンド）を記述
  - worktree一覧表示方法（make worktree-listコマンド）を記述
  - worktree削除方法（make worktree-removeコマンド）を記述
  - 2つのworktreeで並列開発する具体例を記述
  - _Requirements: 12.3, 12.4_

- [ ] 6.3 README.mdにトラブルシューティングセクションを追加する
  - ポート衝突時の対処方法を記述
  - DB接続エラーの対処方法を記述
  - Redisキー衝突の対処方法を記述
  - worktree削除方法の詳細を記述
  - エラーメッセージと解決策の一覧を記述
  - _Requirements: 12.3_

- [ ] 6.4 移行手順ドキュメントを作成する
  - MIGRATION.mdファイルを作成
  - 影響を受けるユーザーの説明を記述
  - 主な変更点の一覧を記述（全ポート番号変更）
  - 8フェーズの移行手順を詳細に記述
  - 移行チェックリストを記述
  - ロールバック手順を記述
  - _Requirements: 11.3, 11.4, 11.5_

## 7. 動作検証とテスト

- [ ] 7. 並列開発環境の動作検証を実施する
- [ ] 7.1 並列Docker起動テストを実施する
  - 2つのworktreeを作成
  - 両方のworktreeで同時にDocker環境を起動
  - ポート番号が正しく分離されていることを確認（curl healthチェック）
  - コンテナ名が正しく分離されていることを確認（docker ps確認）
  - ネットワークが正しく分離されていることを確認（docker network ls確認）
  - _Requirements: 13.1_

- [ ] 7.2 ポート番号再利用テストを実施する
  - worktreeを作成してポート番号を確認
  - worktreeを削除
  - 新しいworktreeを作成して同じポート番号が再利用されることを確認
  - .worktree-ports.jsonが正しく更新されることを確認
  - _Requirements: 13.2_

- [ ] 7.3 データベース分離テストを実施する
  - 2つのworktreeでマイグレーションを実行
  - 各worktreeで独立したDBが作成されることを確認
  - 各worktreeでテストデータを作成
  - データが互いに分離されていることを確認（psqlクエリで確認）
  - マイグレーション履歴が独立していることを確認
  - _Requirements: 13.3_

- [ ] 7.4 Redisキャッシュ分離テストを実施する
  - 2つのworktreeでキャッシュデータを設定
  - 各worktreeで独立したキャッシュプレフィックスが使用されることを確認
  - キャッシュデータが互いに分離されていることを確認（redis-cli確認）
  - キャッシュの衝突が発生しないことを確認
  - _Requirements: 13.4_

- [ ] 7.5 E2Eテスト並列実行テストを実施する
  - 2つのworktreeでE2E環境変数が正しく設定されることを確認
  - 2つのworktreeで同時にE2Eテストを実行
  - テスト結果が互いに干渉しないことを確認
  - テストデータが分離されていることを確認
  - _Requirements: 13.5_

- [ ] 7.6 リソース使用量検証テストを実施する
  - 5-8個のworktreeを同時起動
  - メモリ使用量が想定範囲内（約5GB以内）であることを確認
  - CPU使用率が安定していることを確認
  - エラーログが記録されていることを確認（エラー発生時）
  - _Requirements: 13.6, 13.7_

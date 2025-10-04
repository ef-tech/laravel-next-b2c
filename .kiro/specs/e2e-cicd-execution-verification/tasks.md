# Implementation Plan

## Phase 1: ワークフロー有効化と基本検証

- [ ] 1. GitHub Actionsワークフローを有効化する
- [x] 1.1 ワークフローファイルのリネーム実行
  - `.github/workflows/e2e-tests.yml.disabled`の`.disabled`拡張子を削除
  - ワークフロー定義の内容確認（name: E2E Tests、トリガー設定確認）
  - ポート設定が13000番台統一であることを検証
  - _Requirements: 1.1, 1.2_

- [x] 1.2 ワークフロー有効化コミット作成
  - 変更をgit add
  - コミットメッセージ作成（"Enable: 🚀 E2E CI/CDワークフロー有効化"）
  - リポジトリにpush
  - _Requirements: 1.1, 1.2_

- [x] 1.3 GitHub UIでワークフロー表示確認
  - GitHub Actionsタブにアクセス
  - 「E2E Tests」ワークフローが一覧に表示されることを確認
  - 「Run workflow」ボタンが表示されることを確認
  - _Requirements: 1.2, 1.3_

## Phase 2: Docker Compose環境検証

- [ ] 2. Docker Compose起動とヘルスチェックを検証する
- [ ] 2.1 ローカル環境でDocker Compose起動テスト
  - `docker-compose up -d --build`コマンド実行
  - 全サービス起動完了を確認（laravel-api, admin-app, user-app, pgsql, redis）
  - 各サービスのログ確認（`docker-compose logs`）
  - _Requirements: 2.1, 2.2_

- [ ] 2.2 サービスヘルスチェック動作確認
  - `wait-on`コマンドで3エンドポイントにアクセス
  - http://localhost:13001（user-app）の応答確認
  - http://localhost:13002（admin-app）の応答確認
  - http://localhost:13000/up（laravel-api）の応答確認
  - _Requirements: 2.3_

- [ ] 2.3 タイムアウト設定の動作確認
  - サービス起動前に`wait-on`実行してタイムアウト動作確認
  - 120秒タイムアウト設定が正しく動作することを検証
  - エラーメッセージが適切に出力されることを確認
  - _Requirements: 2.4, 9.3_

## Phase 3: 手動実行（workflow_dispatch）検証

- [x] 3. 手動トリガーでE2Eテストを実行し基本動作を検証する
- [x] 3.1 workflow_dispatch手動実行
  - GitHub Actionsタブで「Run workflow」ボタンをクリック
  - ブランチ選択UI（デフォルト: main）を確認
  - shard_count選択（デフォルト: 4）を確認
  - ワークフロー実行を開始
  - _Requirements: 8.1, 8.2_

- [x] 3.2 Matrix並列実行の動作確認
  - 4つのShardジョブ（shard 1/2/3/4）が並列起動することを確認
  - 各ShardでDocker Compose環境が独立して起動することを確認
  - 各Shardのログで`--shard=N/4`コマンド実行を確認
  - _Requirements: 4.1, 4.2, 4.3_

- [x] 3.3 E2Eテスト実行結果の確認
  - 全Shard実行完了を待機
  - 各Shardのテスト結果（成功/失敗/スキップ数）をログで確認
  - 全Shardが成功することを検証
  - _Requirements: 3.4, 4.4_

- [x] 3.4 Artifacts保存の確認
  - GitHub ActionsのArtifactsセクションにアクセス
  - playwright-report-1/2/3/4の4つのArtifactsが保存されることを確認
  - 各Artifactをダウンロードしてzip形式であることを確認
  - _Requirements: 5.1, 5.2, 5.5_

- [x] 3.5 レポートファイル内容の検証
  - HTMLレポート（index.html）が含まれることを確認
  - JUnitレポート（junit.xml）が含まれることを確認
  - テスト成功時のスクリーンショット保存を確認
  - _Requirements: 5.3_

## Phase 4: 自動トリガー検証（Pull Request）

- [ ] 4. Pull Request作成時の自動実行を検証する
- [ ] 4.1 テスト用ブランチ作成とPR作成
  - テスト用ブランチを作成（feature/test-e2e-cicd）
  - 対象パスに軽微な変更を加える（例: frontend/admin-app/README.md更新）
  - Pull Requestを作成
  - _Requirements: 6.1_

- [x] 4.2 PR自動実行の確認
  - Pull RequestのChecksセクションに「E2E Tests」が表示されることを確認
  - ワークフローが自動実行開始されることを確認
  - 4つのShardジョブが並列実行されることを確認
  - _Requirements: 6.1, 6.4_

- [x] 4.3 PR更新時の再実行確認
  - テスト用ブランチに新規コミットをpush
  - Pull Requestが自動更新されることを確認
  - E2Eテストワークフローが再実行されることを確認
  - _Requirements: 6.2_

- [ ] 4.4 pathsフィルター動作確認
  - 対象外パス（README.mdのみ）を変更した新規コミットを作成
  - E2Eテストワークフローがスキップされることを確認
  - Checksセクションに表示されないことを確認
  - _Requirements: 6.3_

- [ ] 4.5 テスト失敗時の動作確認（任意）
  - E2Eテストを意図的に失敗させる変更を加える
  - PR ChecksにFailedステータスが表示されることを確認
  - マージボタンが制限されることを確認（リポジトリ設定による）
  - _Requirements: 6.5_

## Phase 5: 自動トリガー検証（mainブランチpush）

- [ ] 5. mainブランチへのpush時の自動実行を検証する
- [ ] 5.1 mainブランチpush時の自動実行確認
  - テスト用Pull Requestをmainブランチにマージ
  - mainブランチへのpush時にE2Eテストワークフローが自動実行されることを確認
  - ワークフロー実行履歴に記録されることを確認
  - _Requirements: 7.1, 7.2_

- [ ] 5.2 失敗時の通知確認（任意）
  - E2Eテスト失敗時にGitHub通知が送信されることを確認
  - チームメンバーに通知が届くことを確認
  - _Requirements: 7.3_

## Phase 6: エラーハンドリング検証

- [ ] 6. エラーハンドリングとタイムアウト動作を検証する
- [ ] 6.1 Job Timeout動作確認
  - ワークフローの`timeout-minutes: 60`設定を確認
  - 60分以内に実行完了することを検証
  - _Requirements: 9.1, 9.2_

- [ ] 6.2 Docker起動失敗時のエラーログ確認
  - Docker Compose起動失敗を意図的に発生させる（任意）
  - `docker-compose logs`相当のエラーログが出力されることを確認
  - ワークフローが失敗ステータスで終了することを確認
  - _Requirements: 9.4_

- [ ] 6.3 wait-onタイムアウトエラー確認
  - サービス起動遅延を意図的に発生させる（任意）
  - wait-onタイムアウトエラーメッセージが出力されることを確認
  - タイムアウト延長方法のガイドが表示されることを確認
  - _Requirements: 9.3_

## Phase 7: ドキュメント更新

- [ ] 7. CI/CD実行手順とトラブルシューティングをドキュメント化する
- [ ] 7.1 README.mdにCI/CD実行手順を追加
  - 手動実行方法（workflow_dispatch）のセクション追加
  - PR作成時の自動実行説明を追加
  - Artifactsダウンロード手順を追加
  - _Requirements: 10.1_

- [ ] 7.2 e2e/README.mdにCI/CD情報を追記
  - Shard並列実行の説明を追加
  - 環境変数設定（E2E_ADMIN_URL等）の説明を追加
  - CI環境での実行コマンド例を追加
  - _Requirements: 10.2_

- [ ] 7.3 トラブルシューティングセクション作成
  - Docker起動失敗時のログ確認方法を記載
  - wait-onタイムアウト時のタイムアウト延長設定を記載
  - Playwright実行失敗時のブラウザ再インストール手順を記載
  - _Requirements: 10.3_

## Phase 8: 最終検証と本番運用開始

- [ ] 8. 全体動作を最終検証し本番運用を開始する
- [ ] 8.1 全要件の動作確認
  - Requirement 1-10のすべてのAcceptance Criteriaが満たされていることを確認
  - 全Shardが安定して成功することを確認
  - Artifactsが正しく保存されることを確認
  - _Requirements: すべて_

- [ ] 8.2 パフォーマンス目標達成確認
  - 実行時間が60分以内であることを確認
  - Docker起動時間が5分以内であることを確認
  - wait-on待機時間が120秒以内であることを確認
  - _Requirements: パフォーマンス目標_

- [ ] 8.3 本番運用開始
  - チームメンバーにE2E CI/CD有効化を通知
  - ドキュメントリンクを共有
  - 運用フィードバックチャネル確立
  - _Requirements: 運用開始_

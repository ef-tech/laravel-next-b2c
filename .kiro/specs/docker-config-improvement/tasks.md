# Docker設定改善 - 実装タスク

## 実装計画

- [x] 1. Laravel API Docker設定のホットリロード対応を実装
- [x] 1.1 docker-compose.ymlのLaravel APIサービスにvolume mount設定を追加
  - `./backend/laravel-api:/var/www/html:cached`のvolume mount設定を追加
  - 環境変数`APP_ENV=local`を追加してホットリロードを有効化
  - docker compose config実行で構文エラーがないことを確認
  - _Requirements: 1.1, 1.2, 1.3_
  - ⚠️ 注記: `/var/www/html/vendor`の除外volume設定は不要（vendorディレクトリが見つからずエラーとなるため削除）

- [x] 1.2 Laravel APIのホットリロード動作確認を実施
  - docker compose down実行で既存コンテナを削除
  - docker compose up -d実行でLaravel APIを起動
  - docker compose psでlaravel-apiサービスがhealthy状態であることを確認
  - routes/api.phpファイルを編集してコード変更を実施
  - curl http://localhost:13000/api/health実行で1秒以内に変更が反映されることを確認
  - _Requirements: 1.4, 1.5_

- [ ] 2. Next.jsアプリケーションのネイティブ起動対応を実装
- [ ] 2.1 docker-compose.ymlからNext.jsサービス定義を削除
  - admin-appサービス定義をdocker-compose.ymlから完全削除
  - user-appサービス定義をdocker-compose.ymlから完全削除
  - profiles: frontendグループから2サービスを削除
  - docker compose config実行で構文エラーがないことを確認
  - _Requirements: 2.1, 2.2_

- [ ] 2.2 Next.jsアプリのネイティブ起動動作確認を実施
  - frontend/admin-appディレクトリへ移動してnpm run dev実行
  - ポート13002でadmin-appが起動することを確認
  - http://localhost:13002へのアクセスが可能であることを確認
  - frontend/user-appディレクトリへ移動してnpm run dev実行
  - ポート13001でuser-appが起動することを確認
  - http://localhost:13001へのアクセスが可能であることを確認
  - _Requirements: 2.3, 2.4, 2.6, 2.7_

- [ ] 2.3 Next.jsアプリのホットリロード動作確認を実施
  - admin-appのapp/page.tsxファイルを編集
  - 1秒以内にブラウザが自動リロードして変更が反映されることを確認
  - user-appのapp/page.tsxファイルを編集
  - 1秒以内にブラウザが自動リロードして変更が反映されることを確認
  - _Requirements: 2.5_

- [ ] 3. 開発環境起動手順のドキュメント整備を実施
- [ ] 3.1 README.mdに「開発環境起動」セクションを追加
  - README.mdに「開発環境起動」セクションを新規作成
  - 前提条件セクションを追加（Docker Desktop、Node.js 20+、PHP 8.4+を明記）
  - _Requirements: 3.1, 3.2_

- [ ] 3.2 README.mdに3ターミナル起動手順を追加
  - Terminal 1: Dockerサービス起動（make dev実行）の手順を記載
  - Terminal 2: Admin App起動（cd frontend/admin-app && npm run dev）の手順を記載
  - Terminal 3: User App起動（cd frontend/user-app && npm run dev）の手順を記載
  - 各サービスのアクセスURL（Laravel API: 13000、Admin App: 13002、User App: 13001）を明記
  - _Requirements: 3.3, 3.4_

- [ ] 3.3 README.mdにホットリロード確認方法とトラブルシューティングを追加
  - Laravel APIのホットリロード確認方法を記載（routes/api.php編集 → 1秒以内反映）
  - Next.jsのホットリロード確認方法を記載（app/page.tsx編集 → 1秒以内自動リロード）
  - トラブルシューティングセクションを追加（ポート競合、ホットリロード不具合対処法）
  - 停止方法セクションを追加（各ターミナルでCtrl+C、docker compose down）
  - _Requirements: 3.5, 3.6, 3.7_

- [ ] 4. Makefileのシンプル化を実施
- [ ] 4.1 Makefileに基本的なDockerコマンドラッパーを実装
  - make devターゲットを実装（docker compose up -d実行、次ステップガイダンス表示）
  - make stopターゲットを実装（docker compose stop実行）
  - make cleanターゲットを実装（docker compose down -v実行）
  - make logsターゲットを実装（docker compose logs -f実行）
  - make psターゲットを実装（docker compose ps実行）
  - make helpターゲットを実装（利用可能コマンド一覧表示）
  - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 4.7_

- [ ] 4.2 Makefileから複雑なスクリプト呼び出しを削除
  - Makefileのmake devターゲットからscripts/dev/main.sh呼び出しを削除
  - 既存のmake dev-docker、make dev-nativeなど複雑なターゲットを削除
  - シンプルなDocker Composeコマンドのみを使用するように変更
  - make -n devでドライラン実行して構文エラーがないことを確認
  - _Requirements: 4.8_

- [ ] 5. 不要なスクリプトの整理を実施
- [ ] 5.1 scripts/dev/ディレクトリの削除または無視を実施
  - scripts/dev/ディレクトリを完全削除（git rm -r scripts/dev/）、またはREADME.mdに使用しない旨を明記
  - 削除した場合はREADME.mdに削除理由を追加
  - 無視する場合は.gitignoreにscripts/dev/を追加し、README.mdに「scripts/dev/は使用しません。上記の起動手順に従ってください」と明記
  - _Requirements: 5.1, 5.2_

- [ ] 6. E2Eテスト環境設定の調整を実施
- [ ] 6.1 docker-compose.ymlのe2e-testsサービスを調整
  - e2e-testsサービスのdepends_onからadmin-app、user-appサービスを削除
  - e2e-testsサービスのdepends_onにlaravel-apiサービス（condition: service_healthy）のみを含める
  - e2e-testsサービスの環境変数E2E_ADMIN_URLを`http://localhost:13002`に設定
  - e2e-testsサービスの環境変数E2E_USER_URLを`http://localhost:13001`に設定
  - e2e-testsサービスの環境変数E2E_API_URLを`http://localhost:13000`に設定
  - docker compose config実行で構文エラーがないことを確認
  - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5_

- [ ] 7. 統合動作確認を実施
- [ ] 7.1 全サービスの起動確認を実施
  - make dev実行でDockerサービス（PostgreSQL、Redis、Mailpit、MinIO、Laravel API）が起動することを確認
  - docker compose psで全サービスがhealthy状態であることを確認
  - frontend/admin-appディレクトリでnpm run dev実行してadmin-appがポート13002で起動することを確認
  - frontend/user-appディレクトリでnpm run dev実行してuser-appがポート13001で起動することを確認
  - _Requirements: 7.1, 7.2, 7.3_

- [ ] 7.2 全サービスへのアクセス確認を実施
  - http://localhost:13000/api/healthへのアクセスが可能であることを確認
  - http://localhost:13001へのアクセスが可能であることを確認
  - http://localhost:13002へのアクセスが可能であることを確認
  - _Requirements: 7.4, 7.5, 7.6_

- [ ] 7.3 ホットリロード動作確認を実施
  - Laravel APIのroutes/api.phpファイルを編集して1秒以内に変更が反映されることを確認
  - admin-appのapp/page.tsxファイルを編集して1秒以内にブラウザが自動リロードすることを確認
  - user-appのapp/page.tsxファイルを編集して1秒以内にブラウザが自動リロードすることを確認
  - _Requirements: 7.7, 7.8_

## 要件カバレッジ確認

全要件が実装タスクでカバーされていることを確認しました：

- **Requirement 1（Laravel API Docker設定のホットリロード対応）**: タスク1.1、1.2でカバー
- **Requirement 2（Next.jsアプリケーションのネイティブ起動対応）**: タスク2.1、2.2、2.3でカバー
- **Requirement 3（開発環境起動手順のドキュメント整備）**: タスク3.1、3.2、3.3でカバー
- **Requirement 4（Makefileのシンプル化）**: タスク4.1、4.2でカバー
- **Requirement 5（不要なスクリプトの整理）**: タスク5.1でカバー
- **Requirement 6（E2Eテスト環境設定の調整）**: タスク6.1でカバー
- **Requirement 7（統合動作確認）**: タスク7.1、7.2、7.3でカバー

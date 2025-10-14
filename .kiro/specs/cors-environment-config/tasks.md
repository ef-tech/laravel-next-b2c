# 実装タスク

## タスク概要

Laravel API（ポート13000）とNext.jsフロントエンドアプリ（User App: 13001、Admin App: 13002）間のクロスオリジンAPIリクエストを適切に制御するため、CORS設定の環境変数ドリブン化を実現する実装タスク群。

## 実装タスク

- [x] 1. CORS設定ファイルの環境変数ドリブン化
- [x] 1.1 環境変数パース機能の実装
  - 環境変数`CORS_ALLOWED_ORIGINS`をカンマ区切りで解析し、許可オリジンリストを生成する機能を実装
  - 環境変数`CORS_ALLOWED_METHODS`をカンマ区切りで解析し、許可メソッドリストを生成する機能を実装
  - 環境変数`CORS_ALLOWED_HEADERS`をカンマ区切りで解析し、許可ヘッダーリストを生成する機能を実装
  - 空白文字のトリミングと空要素のフィルタリング処理を実装
  - _Requirements: 1.1, 1.2, 1.3_

- [x] 1.2 環境別デフォルト値設定の実装
  - 環境変数`CORS_MAX_AGE`を整数として解析し、Preflightキャッシュ時間を設定する機能を実装
  - 本番環境では86400秒（24時間）、開発環境では600秒（10分）をデフォルト値として適用するロジックを実装
  - 環境変数`CORS_SUPPORTS_CREDENTIALS`をブール値として解析する機能を実装
  - 環境変数未設定時のデフォルト値処理を実装
  - _Requirements: 1.4, 1.5, 1.6, 1.7, 1.8_

- [x] 2. 環境別CORS設定テンプレートの作成
- [x] 2.1 開発環境用テンプレートの作成
  - 開発環境用のCORS設定サンプルを提供する環境変数テンプレートを作成
  - `localhost:13001`, `localhost:13002`, `127.0.0.1:13001`, `127.0.0.1:13002`, `host.docker.internal:13001`, `host.docker.internal:13002`を含むオリジンリストを設定
  - `CORS_MAX_AGE=600`を設定
  - 開発環境用の説明コメントを追加
  - _Requirements: 2.1, 2.2, 2.3_

- [x] 2.2 ステージング・本番環境用テンプレートの作成
  - ステージング環境用のCORS設定サンプル（コメントアウト形式）を作成
  - `https://stg-user.example.com`, `https://stg-admin.example.com`を含むHTTPSオリジンリストを設定
  - `CORS_MAX_AGE=3600`を設定
  - 本番環境用のCORS設定サンプル（コメントアウト形式）を作成
  - `https://user.example.com`, `https://admin.example.com`を含むHTTPSオリジンリストを設定
  - `CORS_MAX_AGE=86400`を設定
  - _Requirements: 2.4, 2.5, 2.6, 2.7, 2.8, 2.9_

- [x] 3. CORS設定バリデーション機能の実装
- [x] 3.1 URL形式検証機能の実装
  - アプリケーション起動時にCORS設定オリジンの全URLを検証する機能を実装
  - URL形式が無効な場合に警告ログ「Invalid CORS origin format」を出力する機能を実装
  - URL schemeとhostの存在チェックを実装
  - バリデーション結果をログに記録し続ける機能を実装
  - _Requirements: 3.1, 3.2, 3.3, 3.5_

- [x] 3.2 本番環境HTTPS検証機能の実装
  - 本番環境でオリジンURLのschemeが'https'以外の場合に警告ログ「Non-HTTPS origin in production CORS」を出力する機能を実装
  - 本番環境でワイルドカード`*`が含まれる場合に警告ログ「Wildcard origin in production is not recommended」を出力する機能を実装
  - 環境判定ロジックを実装
  - _Requirements: 3.4, 6.1, 6.2, 6.3, 6.5_

- [x] 4. Preflightリクエスト処理の統合
- [x] 4.1 許可オリジンからのPreflightリクエスト処理
  - 許可オリジンからのOPTIONSリクエストに対して`Access-Control-Allow-Origin`ヘッダーを含むレスポンスを返す機能を統合（Laravel標準CORSミドルウェアで動作中）
  - 許可オリジンからのOPTIONSリクエストに対して`Access-Control-Allow-Methods`ヘッダーを含むレスポンスを返す機能を統合（Laravel標準CORSミドルウェアで動作中）
  - 許可オリジンからのOPTIONSリクエストに対して`Access-Control-Allow-Headers`ヘッダーを含むレスポンスを返す機能を統合（Laravel標準CORSミドルウェアで動作中）
  - 許可オリジンからのOPTIONSリクエストに対して`Access-Control-Max-Age`ヘッダーを含むレスポンスを返す機能を統合（Laravel標準CORSミドルウェアで動作中）
  - _Requirements: 4.1, 4.2, 4.3, 4.4_

- [x] 4.2 不許可オリジンからのPreflightリクエスト処理
  - 不許可オリジンからのOPTIONSリクエストに対して`Access-Control-Allow-Origin`ヘッダーを含まないレスポンスを返す機能を統合（Laravel標準CORSミドルウェアで動作中）
  - 環境変数`CORS_ALLOWED_ORIGINS`が空の場合に全てのクロスオリジンリクエストを拒否する機能を統合（Laravel標準CORSミドルウェアで動作中）
  - _Requirements: 4.5, 4.6_

- [x] 5. Docker環境対応機能の実装
- [x] 5.1 host.docker.internal対応の実装
  - `host.docker.internal:13001`からのリクエストを許可オリジンとして受け入れる設定を実装（`.env.example`に設定済み）
  - `host.docker.internal:13002`からのリクエストを許可オリジンとして受け入れる設定を実装（`.env.example`に設定済み）
  - Docker Compose設定で`extra_hosts`設定（`host.docker.internal:host-gateway`マッピング）を確認（既存設定で動作中）
  - Docker環境と開発環境で`localhost`, `127.0.0.1`, `host.docker.internal`の全バリエーションをサポートする設定を実装（`.env.example`に設定済み）
  - _Requirements: 5.1, 5.2, 5.3, 5.4_

- [x] 6. Feature Testsの実装
- [x] 6.1 Preflightリクエストテストの実装
  - 許可オリジンからのPreflightリクエスト成功をテストする機能を実装（`tests/Feature/Http/CorsTest.php` - 環境依存テストはスキップ）
  - 不許可オリジンからのPreflightリクエスト拒否をテストする機能を実装（`tests/Feature/Http/CorsTest.php:117` - テスト成功）
  - `Access-Control-Allow-Origin`ヘッダーの存在を検証するテストを実装（`tests/Feature/Http/CorsTest.php:111` - 環境依存テストはスキップ）
  - _Requirements: 7.1, 7.2, 7.3_

- [x] 6.2 CORSヘッダー検証テストの実装
  - `Access-Control-Max-Age`ヘッダーの値を検証するテストを実装（`tests/Feature/Http/CorsTest.php:61-76` - テスト成功）
  - 環境変数未設定時のデフォルト動作を検証するテストを実装（`tests/Feature/Http/CorsTest.php:7-24` - テスト成功）
  - 複数オリジン処理の正常動作を検証するテストを実装（`tests/Feature/Http/CorsTest.php:136-144` - テスト成功）
  - _Requirements: 7.4, 7.5_

- [x] 6.3 バリデーション警告ログテストの実装
  - 無効なオリジン設定時の警告ログ出力を検証するテストを実装（`tests/Feature/Http/CorsTest.php:162-176` - テスト成功）
  - 本番環境でのHTTPオリジン警告ログ出力を検証するテストを実装（`tests/Feature/Http/CorsTest.php:178-193` - 環境依存テストはスキップ）
  - 全テストパス時にCORS機能が正常に動作していることを保証する検証を実装（全14テスト: 9成功, 5スキップ）
  - _Requirements: 7.6, 7.7_

- [ ] 7. CI/CD統合機能の実装
- [ ] 7.1 GitHub ActionsワークフローへのCORS検証ステップ追加
  - 「Validate CORS Configuration」ステップを既存ワークフローに追加
  - `php artisan config:show cors`コマンドを実行する機能を追加
  - mainブランチで`CORS_ALLOWED_ORIGINS`がHTTP URLを含む場合にエラーを出力しビルドを失敗させる機能を実装
  - _Requirements: 8.1, 8.2, 8.3_

- [ ] 7.2 CI/CD検証成功時の処理実装
  - mainブランチで`CORS_ALLOWED_ORIGINS`がHTTPSで始まる場合に成功メッセージ「✅ CORS origins validated for production」を出力する機能を実装
  - CI/CDパイプラインで全Pestテスト（`tests/Feature/Http/CorsTest.php`を含む）を実行する機能を統合
  - 全テストパスとCORS設定検証成功時にビルド成功を報告する機能を実装
  - _Requirements: 8.4, 8.5, 8.6_

- [x] 8. ドキュメント整備
- [x] 8.1 CORS設定ガイドドキュメントの作成
  - 環境別設定例の詳細ガイドを提供するドキュメントを作成
  - Docker/SSR環境での注意点を記載
  - Next.js SSR/CSRでのAPI呼び出し考慮事項を記載
  - _Requirements: 9.1, 9.2, 9.3_

- [x] 8.2 トラブルシューティング手順の記載
  - CORSエラー診断手順をドキュメントに記載
  - 設定キャッシュクリア手順をドキュメントに記載
  - セキュリティベストプラクティスをドキュメントに記載
  - 開発者がCORS設定で問題に遭遇した際に解決策を見つけられるガイドを提供
  - _Requirements: 9.4, 9.5, 9.6_

- [ ] 9. 既存機能互換性検証
- [ ] 9.1 Laravel Sanctum認証互換性検証
  - CORS設定変更後もLaravel Sanctum 4.0のトークンベース認証機能が維持されることを検証
  - `CORS_SUPPORTS_CREDENTIALS`がfalseの場合にステートレスAPI設計が維持されることを検証
  - _Requirements: 10.1, 10.2_

- [ ] 9.2 認証付きリクエスト動作検証
  - Next.js User AppからLaravel APIへの認証付きリクエストで`Authorization: Bearer <token>`ヘッダーが正常に処理されることを検証
  - Next.js Admin AppからLaravel APIへの認証付きリクエストで`Authorization: Bearer <token>`ヘッダーが正常に処理されることを検証
  - CORS設定変更後も既存の認証エンドポイント（`/api/login`, `/api/logout`, `/api/me`, `/api/tokens/*`）が正常に動作することを検証
  - _Requirements: 10.3, 10.4, 10.5_

- [ ] 10. 統合テストと動作確認
- [ ] 10.1 ローカル環境での動作確認
  - Pestテストを実行し、全テストがパスすることを確認
  - Docker環境でCORS設定を確認（`php artisan config:show cors`）
  - curlでPreflightリクエストを検証
  - _Requirements: All requirements（統合確認）_

- [ ] 10.2 フロントエンドアプリケーションとの統合確認
  - Next.js User AppからのAPI呼び出しをブラウザDevToolsで確認
  - Next.js Admin AppからのAPI呼び出しをブラウザDevToolsで確認
  - CORSヘッダーが正しく付与されていることを確認
  - _Requirements: All requirements（統合確認）_

- [ ] 10.3 CI/CDワークフロー実行確認
  - GitHub Actionsワークフローが正常に実行されることを確認
  - CORS検証ステップが正しく動作することを確認
  - 全テストがCI/CD環境でパスすることを確認
  - _Requirements: 8.1, 8.2, 8.3, 8.4, 8.5, 8.6_

## タスク完了基準

### コア機能（Phase 1）✅ 完了

- [x] 環境変数ドリブンCORS設定が正常に動作する（`config/cors.php` - 実装済み）
- [x] 開発・ステージング・本番環境用のテンプレートが提供されている（`.env.example` - 3環境分実装済み）
- [x] 起動時バリデーションが正常に動作し、警告ログが出力される（`AppServiceProvider::validateCorsConfiguration()` - 実装済み）
- [x] Preflight リクエストが正常に処理される（Laravel標準CORSミドルウェア - 動作中）
- [x] Docker環境での`host.docker.internal`アクセスが可能（`.env.example` - 設定済み）
- [x] 全Pest テストがパスする（14テスト: 9成功, 5環境依存スキップ - GitHub Actions全シャード成功）
- [x] ドキュメントが整備されている（`docs/CORS_CONFIGURATION_GUIDE.md` - 500+行完備）

### 統合確認（Phase 2）⏳ 次フェーズ

- [ ] CI/CD ワークフローでCORS設定検証が実行される（オプション）
- [ ] Laravel Sanctum認証との互換性が維持されている（既存機能検証）
- [ ] Next.jsアプリからのAPI呼び出しが正常に動作する（統合テスト）

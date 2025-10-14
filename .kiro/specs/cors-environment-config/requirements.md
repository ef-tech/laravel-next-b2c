# Requirements Document

## Introduction

本要件は、Laravel API（ポート13000）とNext.jsフロントエンドアプリ（User App: 13001、Admin App: 13002）間のクロスオリジンAPIリクエストを適切に制御するため、CORS（Cross-Origin Resource Sharing）設定の環境変数ドリブン化を実現するものです。

現在の静的配列ベースの設定から環境変数による柔軟な設定管理へ移行することで、開発・ステージング・本番環境ごとに適切なオリジン制限を実現し、セキュリティ強化と保守性向上を両立します。

**ビジネス価値:**
- 環境別の適切なセキュリティ境界の実現
- 設定変更の容易性向上（コード変更不要）
- 本番環境でのHTTPS強制によるセキュリティリスク低減
- 自動検証による設定ミスの早期発見
- Docker/ローカル開発環境の両対応

## Requirements

### Requirement 1: 環境変数ドリブンCORS設定管理

**Objective:** 開発者として、環境変数を通じてCORS設定を管理したい。これにより、環境ごとに異なるオリジン設定をコード変更なしで適用できる。

#### Acceptance Criteria

1. WHEN `config/cors.php`が読み込まれる THEN Laravel APIシステムは環境変数`CORS_ALLOWED_ORIGINS`をカンマ区切りで解析し、許可オリジンリストを生成する
2. WHEN `config/cors.php`が読み込まれる THEN Laravel APIシステムは環境変数`CORS_ALLOWED_HEADERS`をカンマ区切りで解析し、許可ヘッダーリストを生成する
3. WHEN `config/cors.php`が読み込まれる THEN Laravel APIシステムは環境変数`CORS_ALLOWED_METHODS`をカンマ区切りで解析し、許可メソッドリストを生成する
4. WHEN `config/cors.php`が読み込まれる THEN Laravel APIシステムは環境変数`CORS_MAX_AGE`を整数として解析し、Preflightキャッシュ時間を設定する
5. WHEN `config/cors.php`が読み込まれる THEN Laravel APIシステムは環境変数`CORS_SUPPORTS_CREDENTIALS`をブール値として解析し、資格情報サポートを設定する
6. IF `CORS_ALLOWED_ORIGINS`が未設定 THEN Laravel APIシステムは空配列をデフォルト値として使用する
7. IF `CORS_MAX_AGE`が未設定 AND 本番環境 THEN Laravel APIシステムは86400秒（24時間）をデフォルト値として使用する
8. IF `CORS_MAX_AGE`が未設定 AND 開発環境 THEN Laravel APIシステムは600秒（10分）をデフォルト値として使用する

### Requirement 2: 環境別CORS設定テンプレート

**Objective:** 開発者として、環境別のCORS設定例を参照したい。これにより、適切な設定を迅速に適用できる。

#### Acceptance Criteria

1. WHEN `.env.example`ファイルが参照される THEN Laravel APIシステムは開発環境用のCORS設定サンプルを提供する
2. WHERE 開発環境設定セクション THE `.env.example`は`localhost:13001`, `localhost:13002`, `127.0.0.1:13001`, `127.0.0.1:13002`, `host.docker.internal:13001`, `host.docker.internal:13002`を含むオリジンリストを提供する
3. WHERE 開発環境設定セクション THE `.env.example`は`CORS_MAX_AGE=600`を提供する
4. WHEN `.env.example`ファイルが参照される THEN Laravel APIシステムはステージング環境用のCORS設定サンプル（コメントアウト形式）を提供する
5. WHERE ステージング環境設定セクション THE `.env.example`は`https://stg-user.example.com`, `https://stg-admin.example.com`を含むHTTPSオリジンリストを提供する
6. WHERE ステージング環境設定セクション THE `.env.example`は`CORS_MAX_AGE=3600`を提供する
7. WHEN `.env.example`ファイルが参照される THEN Laravel APIシステムは本番環境用のCORS設定サンプル（コメントアウト形式）を提供する
8. WHERE 本番環境設定セクション THE `.env.example`は`https://user.example.com`, `https://admin.example.com`を含むHTTPSオリジンリストを提供する
9. WHERE 本番環境設定セクション THE `.env.example`は`CORS_MAX_AGE=86400`を提供する

### Requirement 3: CORS設定バリデーション

**Objective:** 運用担当者として、CORS設定の妥当性を自動検証したい。これにより、設定ミスによるセキュリティリスクや機能障害を防ぎたい。

#### Acceptance Criteria

1. WHEN `AppServiceProvider`の`boot()`メソッドが実行される THEN Laravel APIシステムは`config('cors.allowed_origins')`の全オリジンURLを検証する
2. IF オリジンURLが無効な形式 THEN Laravel APIシステムは警告ログ「Invalid CORS origin format」を出力する
3. IF オリジンURLのschemeが空 OR hostが空 THEN Laravel APIシステムは警告ログ「Invalid CORS origin format」を出力する
4. IF 本番環境 AND オリジンURLのschemeが'https'以外 THEN Laravel APIシステムは警告ログ「Non-HTTPS origin in production CORS」を出力する
5. WHILE アプリケーションが起動中 THE Laravel APIシステムは全CORS設定バリデーション結果をログに記録し続ける

### Requirement 4: Preflightリクエスト処理

**Objective:** フロントエンド開発者として、ブラウザからのPreflightリクエストが適切に処理されることを期待する。これにより、APIとの通信が正常に動作する。

#### Acceptance Criteria

1. WHEN 許可オリジンからOPTIONSリクエストが送信される THEN Laravel APIシステムは`Access-Control-Allow-Origin`ヘッダーに送信元オリジンを含むレスポンスを返す
2. WHEN 許可オリジンからOPTIONSリクエストが送信される THEN Laravel APIシステムは`Access-Control-Allow-Methods`ヘッダーに設定された許可メソッドリストを含むレスポンスを返す
3. WHEN 許可オリジンからOPTIONSリクエストが送信される THEN Laravel APIシステムは`Access-Control-Allow-Headers`ヘッダーに設定された許可ヘッダーリストを含むレスポンスを返す
4. WHEN 許可オリジンからOPTIONSリクエストが送信される THEN Laravel APIシステムは`Access-Control-Max-Age`ヘッダーに設定されたキャッシュ時間を含むレスポンスを返す
5. IF 不許可オリジンからOPTIONSリクエストが送信される THEN Laravel APIシステムは`Access-Control-Allow-Origin`ヘッダーを含まないレスポンスを返す
6. WHEN 環境変数`CORS_ALLOWED_ORIGINS`が空 THEN Laravel APIシステムは全てのクロスオリジンリクエストを拒否する

### Requirement 5: Docker環境対応

**Objective:** Docker環境で開発する開発者として、`host.docker.internal`経由のAPIアクセスが可能であることを期待する。これにより、Docker環境での開発効率が向上する。

#### Acceptance Criteria

1. WHERE Docker Compose環境 THE Laravel APIシステムは`host.docker.internal:13001`からのリクエストを許可オリジンとして受け入れる
2. WHERE Docker Compose環境 THE Laravel APIシステムは`host.docker.internal:13002`からのリクエストを許可オリジンとして受け入れる
3. WHEN `docker-compose.yml`の`extra_hosts`設定が確認される THEN Laravel APIサービスは`host.docker.internal:host-gateway`マッピングを持つ
4. IF Docker環境 AND 開発環境 THEN Laravel APIシステムは`localhost`, `127.0.0.1`, `host.docker.internal`の全バリエーションをサポートする

### Requirement 6: セキュリティ強化

**Objective:** セキュリティ担当者として、本番環境でHTTPSオリジンのみが許可されることを保証したい。これにより、中間者攻撃のリスクを低減する。

#### Acceptance Criteria

1. IF 本番環境 AND 設定されたオリジンにHTTP URLが含まれる THEN Laravel APIシステムは警告ログを出力する
2. IF 本番環境 AND ワイルドカード`*`が`CORS_ALLOWED_ORIGINS`に含まれる THEN Laravel APIシステムは警告ログ「Wildcard origin in production is not recommended」を出力する
3. WHEN 本番環境のCORS設定が読み込まれる THEN Laravel APIシステムは全オリジンが`https://`で始まることを検証する
4. IF ステージング環境 OR 本番環境 THEN Laravel APIシステムは`CORS_MAX_AGE`を3600秒以上に設定する
5. WHILE 本番環境で動作中 THE Laravel APIシステムはHTTPSオリジンのみを許可し続ける

### Requirement 7: Feature Tests実装

**Objective:** QA担当者として、CORS設定が正しく動作することを自動テストで検証したい。これにより、リグレッションを防ぐ。

#### Acceptance Criteria

1. WHEN `tests/Feature/Http/CorsTest.php`が実行される THEN Laravel APIシステムは許可オリジンからのPreflightリクエスト成功をテストする
2. WHEN `tests/Feature/Http/CorsTest.php`が実行される THEN Laravel APIシステムは不許可オリジンからのPreflightリクエスト拒否をテストする
3. WHEN `tests/Feature/Http/CorsTest.php`が実行される THEN Laravel APIシステムは`Access-Control-Allow-Origin`ヘッダーの存在を検証する
4. WHEN `tests/Feature/Http/CorsTest.php`が実行される THEN Laravel APIシステムは`Access-Control-Max-Age`ヘッダーの値を検証する
5. WHEN `tests/Feature/Http/CorsTest.php`が実行される THEN Laravel APIシステムは環境変数未設定時のデフォルト動作を検証する
6. WHEN `tests/Feature/Http/CorsTest.php`が実行される THEN Laravel APIシステムは無効なオリジン設定時の警告ログ出力を検証する
7. IF 全テストがパス THEN Laravel APIシステムはCORS機能が正常に動作していることを保証する

### Requirement 8: CI/CD統合

**Objective:** CI/CD管理者として、GitHub ActionsでCORS設定を自動検証したい。これにより、本番デプロイ前に設定ミスを検出する。

#### Acceptance Criteria

1. WHEN `.github/workflows/test.yml`が実行される THEN GitHub Actionsワークフローは「Validate CORS Configuration」ステップを含む
2. WHEN 「Validate CORS Configuration」ステップが実行される THEN GitHub Actionsは`php artisan config:show cors`コマンドを実行する
3. IF GitHubブランチが`main` AND `CORS_ALLOWED_ORIGINS`がHTTP URLを含む THEN GitHub Actionsワークフローはエラーを出力しビルドを失敗させる
4. IF GitHubブランチが`main` AND `CORS_ALLOWED_ORIGINS`がHTTPSで始まる THEN GitHub Actionsワークフローは成功メッセージ「✅ CORS origins validated for production」を出力する
5. WHEN CI/CDパイプラインが実行される THEN GitHub Actionsは全Pestテスト（`tests/Feature/Http/CorsTest.php`を含む）を実行する
6. IF 全テストがパス AND CORS設定検証成功 THEN GitHub Actionsワークフローはビルド成功を報告する

### Requirement 9: ドキュメント整備

**Objective:** 開発者として、CORS設定のガイドドキュメントを参照したい。これにより、適切な設定とトラブルシューティングを迅速に実行できる。

#### Acceptance Criteria

1. WHEN `docs/CORS_CONFIGURATION_GUIDE.md`が作成される THEN Laravel APIシステムは環境別設定例の詳細ガイドを提供する
2. WHERE ガイドドキュメント THE `docs/CORS_CONFIGURATION_GUIDE.md`はDocker/SSR環境での注意点を記載する
3. WHERE ガイドドキュメント THE `docs/CORS_CONFIGURATION_GUIDE.md`はNext.js SSR/CSRでのAPI呼び出し考慮事項を記載する
4. WHERE ガイドドキュメント THE `docs/CORS_CONFIGURATION_GUIDE.md`はトラブルシューティング手順（CORSエラー診断、設定キャッシュクリア）を記載する
5. WHERE ガイドドキュメント THE `docs/CORS_CONFIGURATION_GUIDE.md`はセキュリティベストプラクティスを記載する
6. WHEN 開発者がCORS設定で問題に遭遇する THEN 開発者は`docs/CORS_CONFIGURATION_GUIDE.md`を参照して解決策を見つけることができる

### Requirement 10: 既存機能との互換性

**Objective:** Laravel Sanctum認証を使用する開発者として、CORS設定変更後もトークンベース認証が正常に動作することを期待する。

#### Acceptance Criteria

1. WHEN CORS設定が環境変数ドリブンに変更される THEN Laravel APIシステムはLaravel Sanctum 4.0のトークンベース認証機能を維持する
2. IF `CORS_SUPPORTS_CREDENTIALS`がfalse THEN Laravel APIシステムはステートレスAPI設計を維持する
3. WHEN Next.js User AppからLaravel APIへ認証付きリクエストが送信される THEN Laravel APIシステムは`Authorization: Bearer <token>`ヘッダーを正常に処理する
4. WHEN Next.js Admin AppからLaravel APIへ認証付きリクエストが送信される THEN Laravel APIシステムは`Authorization: Bearer <token>`ヘッダーを正常に処理する
5. IF CORS設定変更後 THEN Laravel APIシステムは既存の認証エンドポイント（`/api/login`, `/api/logout`, `/api/me`, `/api/tokens/*`）を正常に動作させる

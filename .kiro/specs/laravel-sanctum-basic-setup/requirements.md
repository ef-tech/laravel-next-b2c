# Requirements Document

## GitHub Issue Information

**Issue**: [#28](https://github.com/ef-tech/laravel-next-b2c/issues/28) - Laravel Sanctum 基本設定
**Labels**: なし
**Milestone**: なし
**Assignees**: なし

### Original Issue Description
API認証の基盤を整備し、セキュアなAPI通信を実現する

## Extracted Information

### Technology Stack
**Backend**: Laravel Sanctum 4.0（トークンベース認証）
**Frontend**: 既存Next.js環境（Admin App / User App）
**Infrastructure**: API認証基盤、ステートレス設計
**Tools**: Laravel Sanctum、Pest（テストフレームワーク）

### Project Structure
既存プロジェクトにLaravel Sanctumを統合:
```
backend/laravel-api/
├── config/sanctum.php           # Sanctum設定ファイル
├── app/Models/User.php          # UserモデルにHasApiTokensトレイト追加
├── routes/api.php               # API認証ルート定義
├── database/migrations/         # Personal access tokensテーブルマイグレーション
└── tests/Feature/Auth/          # 認証関連テスト
```

### Development Services Configuration
- **Laravel API**: ポート13000（既存）
- **PostgreSQL**: ポート13432（既存）
- **Redis**: ポート13379（キャッシュ用、既存）

## Introduction

本要件定義は、Laravel Next.js B2Cアプリケーションテンプレートに**Laravel Sanctum 4.0**を用いたAPI認証基盤を整備し、セキュアなAPI通信を実現するための要件を定義します。

既存のステートレスAPI設計（Laravel 12 API専用最適化済み）との統合を前提とし、フロントエンド（Next.js Admin App / User App）からのトークンベース認証を実現します。

**ビジネス価値**:
- セキュアなAPI通信によるデータ保護
- トークンベース認証による水平スケーリング対応（ステートレス設計維持）
- 既存の高パフォーマンスAPI設計との統合（33.3%起動速度向上を維持）
- Next.jsフロントエンドとのシームレスな認証統合

## Requirements

### Requirement 1: Sanctum基本設定とインストール
**Objective:** As a バックエンド開発者, I want Laravel Sanctum 4.0をプロジェクトに統合する, so that トークンベースAPI認証の基盤が整備される

#### Acceptance Criteria

1. WHEN Sanctumパッケージがインストールされていない場合 THEN Laravel API SHALL `composer require laravel/sanctum`でSanctum 4.0をインストールする
2. WHEN Sanctum設定ファイルが存在しない場合 THEN Laravel API SHALL `php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"`で設定ファイルを生成する
3. WHEN Sanctum設定が完了した場合 THEN `config/sanctum.php` SHALL 以下の設定を含む:
   - `stateful` ドメイン設定（localhost:13001, localhost:13002）
   - `guard` 設定（API専用）
   - `expiration` 設定（トークン有効期限）
   - `middleware` 設定（既存ミドルウェアとの統合）
4. WHEN Personal access tokensマイグレーションを実行する場合 THEN Laravel API SHALL `php artisan migrate`で`personal_access_tokens`テーブルを作成する
5. WHERE 既存のステートレスAPI設計（SESSION_DRIVER=array）を維持する THE Sanctum設定 SHALL セッション機能を使用しない構成とする

### Requirement 2: Userモデル設定
**Objective:** As a バックエンド開発者, I want UserモデルにSanctum機能を統合する, so that ユーザーがAPIトークンを発行・管理できる

#### Acceptance Criteria

1. WHEN UserモデルにHasApiTokensトレイトが追加されていない場合 THEN `app/Models/User.php` SHALL `use Laravel\Sanctum\HasApiTokens`トレイトをインポートする
2. WHEN Userモデルが定義されている場合 THEN User SHALL HasApiTokensトレイトを使用する（`use HasApiTokens;`）
3. WHEN Userインスタンスが存在する場合 THEN User SHALL `createToken()`メソッドでAPIトークンを発行できる
4. WHEN Userインスタンスが存在する場合 THEN User SHALL `tokens()`リレーションで発行済みトークン一覧を取得できる
5. WHERE 既存のEloquentモデル設計を維持する THE UserモデルSHALL 既存のモデル機能（fillable, hidden, casts等）を保持する

### Requirement 3: 認証エンドポイント実装
**Objective:** As a フロントエンド開発者, I want API認証エンドポイントを利用する, so that ユーザーがログイン/ログアウト/トークン管理を実行できる

#### Acceptance Criteria

1. WHEN ユーザーがログインリクエスト（POST `/api/login`）を送信する THEN Laravel API SHALL メールアドレスとパスワードを検証し、成功時にAPIトークンを返却する
2. WHEN ログインが成功した場合 THEN Laravel API SHALL レスポンスに以下を含む:
   - `token`: 発行されたAPIトークン（平文）
   - `user`: ユーザー情報（id, name, email）
   - `token_type`: "Bearer"
3. WHEN ログインが失敗した場合（認証情報不正） THEN Laravel API SHALL 401 Unauthorizedステータスコードとエラーメッセージを返却する
4. WHEN 認証済みユーザーがログアウトリクエスト（POST `/api/logout`）を送信する THEN Laravel API SHALL 現在のトークンを失効させる
5. WHEN 認証済みユーザーが自身の情報取得リクエスト（GET `/api/user`）を送信する THEN Laravel API SHALL ユーザー情報を返却する
6. WHERE 認証が必要なエンドポイント THE Laravel API SHALL `auth:sanctum`ミドルウェアで保護する

### Requirement 4: 認証ミドルウェア設定
**Objective:** As a バックエンド開発者, I want API認証ミドルウェアを設定する, so that 保護されたエンドポイントへのアクセスを制御できる

#### Acceptance Criteria

1. WHEN `bootstrap/app.php`でミドルウェアエイリアスが定義されていない場合 THEN Laravel API SHALL `auth:sanctum`エイリアスを`EnsureFrontendRequestsAreStateful`ミドルウェアと統合する
2. WHEN 保護されたAPIルートが定義される場合 THEN `routes/api.php` SHALL `Route::middleware('auth:sanctum')`グループを使用する
3. WHEN 認証されていないユーザーが保護されたエンドポイントにアクセスする場合 THEN Laravel API SHALL 401 Unauthorizedステータスコードを返却する
4. WHEN 無効なトークンでリクエストを送信する場合 THEN Laravel API SHALL 401 Unauthorizedステータスコードを返却する
5. WHERE API専用設計を維持する THE 認証ミドルウェア SHALL Web機能（セッション、Cookie認証）を使用しない

### Requirement 5: CORS設定最適化
**Objective:** As a フロントエンド開発者, I want Next.jsアプリケーションからAPIにアクセスする, so that クロスオリジンリクエストが正常に処理される

#### Acceptance Criteria

1. WHEN `config/cors.php`が存在する場合 THEN CORS設定 SHALL 以下のオリジンを許可する:
   - `http://localhost:13001`（User App）
   - `http://localhost:13002`（Admin App）
2. WHEN CORS設定が定義されている場合 THEN CORS SHALL 以下のHTTPメソッドを許可する: GET, POST, PUT, DELETE, OPTIONS
3. WHEN CORS設定が定義されている場合 THEN CORS SHALL 以下のヘッダーを許可する: Content-Type, Authorization, X-Requested-With
4. WHEN プリフライトリクエスト（OPTIONS）を受信する場合 THEN Laravel API SHALL 200 OKステータスコードと適切なCORSヘッダーを返却する
5. WHERE 既存のCORS設定が存在する THE CORS設定 SHALL 既存設定との整合性を保持する

### Requirement 6: トークン管理機能
**Objective:** As a 認証済みユーザー, I want 自身のAPIトークンを管理する, so that セキュリティを維持しながらAPIアクセスを制御できる

#### Acceptance Criteria

1. WHEN 認証済みユーザーが新しいトークン発行リクエスト（POST `/api/tokens`）を送信する THEN Laravel API SHALL 新しいAPIトークンを発行し返却する
2. WHEN トークン発行時にトークン名が指定された場合 THEN Laravel API SHALL トークンに指定された名前を設定する
3. WHEN 認証済みユーザーが発行済みトークン一覧取得リクエスト（GET `/api/tokens`）を送信する THEN Laravel API SHALL ユーザーの全トークン情報（id, name, created_at, last_used_at）を返却する
4. WHEN 認証済みユーザーが特定トークン削除リクエスト（DELETE `/api/tokens/{id}`）を送信する THEN Laravel API SHALL 指定されたトークンを失効させる
5. WHEN 認証済みユーザーが全トークン削除リクエスト（DELETE `/api/tokens`）を送信する THEN Laravel API SHALL ユーザーの全トークンを失効させる
6. WHERE トークンが使用された場合 THE personal_access_tokensテーブル SHALL last_used_atカラムを更新する

### Requirement 7: テストカバレッジ（Pest 4）
**Objective:** As a 品質保証担当者, I want 認証機能の包括的テストを実施する, so that Sanctum認証が正常に動作することを保証できる

#### Acceptance Criteria

1. WHEN 認証テストスイートが実行される場合 THEN `tests/Feature/Auth/LoginTest.php` SHALL ログイン成功/失敗のテストケースを含む
2. WHEN ログインテストが実行される場合 THEN ログインテスト SHALL 以下のシナリオをカバーする:
   - 正常なログイン（トークン発行成功）
   - 不正なメールアドレス（401エラー）
   - 不正なパスワード（401エラー）
   - バリデーションエラー（422エラー）
3. WHEN 認証済みエンドポイントテストが実行される場合 THEN `tests/Feature/Auth/AuthenticatedEndpointTest.php` SHALL 認証済みアクセス/未認証アクセスのテストケースを含む
4. WHEN トークン管理テストが実行される場合 THEN `tests/Feature/Auth/TokenManagementTest.php` SHALL トークン発行/一覧取得/削除のテストケースを含む
5. WHEN ログアウトテストが実行される場合 THEN `tests/Feature/Auth/LogoutTest.php` SHALL ログアウト成功/トークン失効検証のテストケースを含む
6. WHERE 既存のPest 4テスト環境を活用する THE 認証テスト SHALL Pestのアサーションメソッドとテストヘルパーを使用する
7. WHEN 全認証テストが実行される場合 THEN テストカバレッジ SHALL 認証関連コードの90%以上をカバーする

### Requirement 8: Next.jsフロントエンド統合
**Objective:** As a フロントエンド開発者, I want Next.jsアプリケーションからSanctum認証を利用する, so that ユーザーがシームレスにログイン/ログアウトできる

#### Acceptance Criteria

1. WHEN フロントエンドがログインリクエストを送信する場合 THEN Next.jsアプリ SHALL `fetch`または`axios`で`POST /api/login`にメールアドレスとパスワードを送信する
2. WHEN ログインが成功した場合 THEN Next.jsアプリ SHALL 受信したトークンをlocalStorageまたはCookieに保存する
3. WHEN 保護されたAPIにアクセスする場合 THEN Next.jsアプリ SHALL Authorizationヘッダーに`Bearer {token}`を含める
4. WHEN ログアウトリクエストを送信する場合 THEN Next.jsアプリ SHALL `POST /api/logout`にトークンを含めて送信し、成功時にローカルトークンを削除する
5. WHERE Admin AppとUser Appが異なる認証フローを持つ THE フロントエンド統合 SHALL 各アプリの認証要件に対応する
6. WHEN APIエラー（401 Unauthorized）を受信した場合 THEN Next.jsアプリ SHALL ユーザーをログイン画面にリダイレクトする

### Requirement 9: セキュリティ設定
**Objective:** As a セキュリティ担当者, I want API認証のセキュリティを強化する, so that 不正アクセスやトークン漏洩リスクを最小化できる

#### Acceptance Criteria

1. WHEN トークンが発行される場合 THEN Laravel API SHALL SHA-256ハッシュ化されたトークンをデータベースに保存する
2. WHEN Sanctum設定で有効期限が定義されている場合 THEN 発行されたトークン SHALL 指定された期間後に自動的に失効する
3. WHERE 本番環境デプロイ時 THE Sanctum設定 SHALL HTTPS通信を強制する（`secure` Cookie設定）
4. WHEN レート制限が設定されている場合 THEN ログインエンドポイント SHALL 過剰なリクエストを制限する（例: 5回/分）
5. WHERE パスワード検証を実行する THE Laravel API SHALL Laravelのハッシュ機能（bcrypt/argon2）を使用する
6. WHEN トークンが使用されない場合（一定期間） THEN Laravel API SHALL 未使用トークンを自動削除するスケジュールコマンドを提供する

### Requirement 10: ドキュメント整備
**Objective:** As a 開発者, I want Sanctum認証の実装ドキュメントを参照する, so that 認証機能を正しく理解し、拡張できる

#### Acceptance Criteria

1. WHEN ドキュメントが作成される場合 THEN `backend/laravel-api/docs/sanctum-setup.md` SHALL 以下のセクションを含む:
   - Sanctumインストール手順
   - 設定ファイル説明（config/sanctum.php）
   - 認証エンドポイント一覧（API仕様）
   - フロントエンド統合ガイド（Next.js例）
   - トラブルシューティング
2. WHEN README.mdが更新される場合 THEN プロジェクトREADME SHALL Laravel Sanctum認証機能の概要を記載する
3. WHERE API仕様ドキュメントが存在する THE ドキュメント SHALL 認証エンドポイントのリクエスト/レスポンス例を含む
4. WHEN トラブルシューティングガイドが作成される場合 THEN ドキュメント SHALL よくある問題と解決策を記載する:
   - CORS設定エラー
   - トークン検証失敗
   - 401 Unauthorizedエラー
   - マイグレーションエラー

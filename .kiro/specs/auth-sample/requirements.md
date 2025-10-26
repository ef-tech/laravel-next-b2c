# Requirements Document

## イントロダクション

本仕様は、Laravel 12 + Sanctum 4.0 + Next.js 15.5を使用した、User/Admin完全分離認証システムの実装要件を定義します。

### ビジネス価値
B2Cテンプレートとして、以下の価値を提供します：
- **完全分離設計**: User/Adminの異なるセキュリティ要件に対応（別テーブル、別ガード、別トークン管理）
- **DDD実装パターン**: ビジネスロジックの明確な分離と保守性向上
- **API将来性保証**: 初期からのバージョニング戦略により、破壊的変更に対応可能
- **統一エラーハンドリング**: 一貫したエラー体験による開発効率化
- **包括的テスト戦略**: 品質保証の自動化による長期的な信頼性確保

### スコープ概要

**含まれるもの:**
- User/Admin認証機能（ログイン・ログアウト・認証状態確認）
- DDD 4層構造実装（Domain/Application/Infrastructure/Presentation）
- Sanctumマルチガード設定とカスタムミドルウェア
- APIバージョニング（v1プレフィックス）
- 統一エラーハンドリング（バックエンド・フロントエンド）
- フロントエンド認証Context/hooks（user-app/admin-app）
- 包括的テスト（Unit/Feature/E2E）
- 技術ドキュメント（API仕様書、認証フロー図、セットアップガイド等）

**含まれないもの:**
- パスワードリセット機能（別Issue化）
- メール認証機能（別Issue化）
- 2FA（二要素認証）機能（将来実装）
- OAuth連携（Google/GitHub等）
- RBAC詳細実装（基本roleカラムのみ）
- API v2以降の実装（設計のみ）

---

## 要件

### 要件1: Adminデータベース設計

**目的:** データベース管理者として、User/Admin完全分離認証システムのための管理者専用テーブルを用意したい。これにより、ユーザーと管理者で異なるセキュリティ要件とライフサイクル管理が可能になる。

#### 受入基準

1. **WHEN** マイグレーション実行時 **THEN** Laravel API **SHALL** `admins`テーブルを作成する（id, name, email, email_verified_at, password, role, is_active, remember_token, created_at, updated_at, deleted_atカラム含む）
2. **WHEN** マイグレーション実行時 **THEN** Laravel API **SHALL** `admins.email`カラムにUNIQUE制約を設定する
3. **WHEN** マイグレーション実行時 **THEN** Laravel API **SHALL** `admins.is_active`カラムにINDEXを設定する
4. **WHEN** AdminSeeder実行時 **THEN** Laravel API **SHALL** 開発用管理者アカウント（admin@example.com / password / super_admin）を作成する
5. **WHEN** AdminSeeder実行時 **THEN** Laravel API **SHALL** 開発用スタッフアカウント（staff@example.com / password / admin）を作成する
6. **IF** `admins.role`が設定される **THEN** Laravel API **SHALL** 'admin' または 'super_admin' のみを許可する
7. **IF** 管理者が論理削除される **THEN** Laravel API **SHALL** `deleted_at`タイムスタンプを記録し、物理削除は行わない

---

### 要件2: Domain層実装（DDD 4層構造）

**目的:** ドメインエキスパートとして、ビジネスロジックをフレームワーク非依存で実装したい。これにより、ビジネスルールの明確化と長期的な保守性を確保する。

#### 受入基準

1. **WHEN** Admin Entityが生成される **THEN** Laravel API **SHALL** id, name, email, role, isActiveプロパティを持つドメインオブジェクトを作成する
2. **IF** Admin Entityに`canAccessAdminPanel()`が呼び出される **THEN** Laravel API **SHALL** `isActive`フラグがtrueの場合のみtrueを返す
3. **WHEN** AdminRole ValueObjectが生成される **THEN** Laravel API **SHALL** 'admin' または 'super_admin' 以外の値を拒否する（InvalidArgumentException）
4. **WHEN** AdminRepository Interfaceが定義される **THEN** Laravel API **SHALL** `findByEmail(Email $email): ?Admin`メソッドを宣言する
5. **WHEN** AdminRepository Interfaceが定義される **THEN** Laravel API **SHALL** `verifyCredentials(Email $email, string $password): ?Admin`メソッドを宣言する
6. **IF** Domain層の任意のクラスがインポートされる **THEN** Laravel API **SHALL** Laravelフレームワーククラス（Eloquent、Facade等）への依存を含まない（Carbon除く）

---

### 要件3: Application層実装（ユースケース）

**目的:** アプリケーション開発者として、ビジネスユースケースを明確なInput/Output契約で実装したい。これにより、再利用性とテスタビリティを向上させる。

#### 受入基準

1. **WHEN** LoginAdminUseCaseが実行される **THEN** Laravel API **SHALL** LoginAdminInput（email, password）を受け取り、LoginAdminOutput（token, adminDTO）を返す
2. **IF** LoginAdminUseCaseで無効な認証情報が提供される **THEN** Laravel API **SHALL** InvalidCredentialsExceptionをスローする
3. **IF** LoginAdminUseCaseで無効化された管理者が認証を試みる **THEN** Laravel API **SHALL** AccountDisabledExceptionをスローする
4. **WHEN** LogoutAdminUseCaseが実行される **THEN** Laravel API **SHALL** 指定されたtokenIdのトークンを失効させる
5. **WHEN** GetAuthenticatedAdminUseCaseが実行される **THEN** Laravel API **SHALL** 認証済み管理者のAdminDTOを返す
6. **IF** Application層のUseCaseがRepository依存を持つ **THEN** Laravel API **SHALL** Interface型でのみ依存する（Infrastructure層の具象クラスに直接依存しない）

---

### 要件4: Infrastructure層実装（永続化）

**目的:** インフラストラクチャ開発者として、Domain/Application層のインターフェースをEloquent ORMで実装したい。これにより、具体的なデータアクセス実装を提供する。

#### 受入基準

1. **WHEN** Admin Eloquentモデルが定義される **THEN** Laravel API **SHALL** HasApiTokens, SoftDeletesトレイトを使用する
2. **WHEN** Admin Eloquentモデルが定義される **THEN** Laravel API **SHALL** $fillable（name, email, password, role, is_active）を設定する
3. **WHEN** Admin Eloquentモデルが定義される **THEN** Laravel API **SHALL** $hidden（password, remember_token）を設定する
4. **WHEN** EloquentAdminRepositoryが実装される **THEN** Laravel API **SHALL** AdminRepository Interfaceを完全に実装する
5. **WHEN** EloquentAdminRepository::verifyCredentials()が呼び出される **THEN** Laravel API **SHALL** パスワードハッシュ検証とis_activeチェックを両方実行する
6. **WHEN** EloquentAdminRepositoryがEloquentモデルをDomain Entityにマッピングする **THEN** Laravel API **SHALL** 永続化層の詳細をDomain層に漏らさない

---

### 要件5: Presentation層実装（API Controller）

**目的:** API開発者として、薄いHTTP層からApplication層のUseCaseを呼び出したい。これにより、ビジネスロジックをHTTP層から分離する。

#### 受入基準

1. **WHEN** POST `/api/v1/admin/login`にリクエストされる **THEN** Laravel API **SHALL** LoginAdminControllerを呼び出す
2. **WHEN** AdminLoginControllerが実行される **THEN** Laravel API **SHALL** LoginRequestでバリデーションを実行する（email必須・email形式、password必須・最低8文字）
3. **WHEN** AdminLoginControllerが成功する **THEN** Laravel API **SHALL** 200 OK（token, adminリソース）を返す
4. **WHEN** POST `/api/v1/admin/logout`にリクエストされる **THEN** Laravel API **SHALL** AdminLogoutControllerを呼び出す（auth:admin middleware保護）
5. **WHEN** GET `/api/v1/admin/dashboard`にリクエストされる **THEN** Laravel API **SHALL** AdminDashboardControllerを呼び出す（auth:admin + AdminGuard middleware保護）
6. **IF** Controllerが複雑なビジネスロジックを含む **THEN** Laravel API **SHALL** そのロジックをApplication層のUseCaseに移動する

---

### 要件6: Sanctumマルチガード認証設定

**目的:** セキュリティエンジニアとして、User/Admin別の認証ガードを設定したい。これにより、異なる認証ポリシーとトークンスコープを適用できる。

#### 受入基準

1. **WHEN** `config/auth.php`が更新される **THEN** Laravel API **SHALL** 'api'ガード（provider: users）を定義する
2. **WHEN** `config/auth.php`が更新される **THEN** Laravel API **SHALL** 'admin'ガード（provider: admins）を定義する
3. **WHEN** AdminGuard middlewareが実行される **THEN** Laravel API **SHALL** `$request->user('admin')`で認証確認を行う
4. **IF** AdminGuard middlewareで認証されたユーザーがAdmin型でない **THEN** Laravel API **SHALL** 401 Unauthorizedを返す
5. **IF** AdminGuard middlewareで管理者の`is_active`がfalse **THEN** Laravel API **SHALL** 403 Forbiddenを返す
6. **WHEN** UserGuard middlewareが実行される **THEN** Laravel API **SHALL** `$request->user('api')`で認証確認を行い、User型であることを検証する

---

### 要件7: APIバージョニング戦略実装

**目的:** API設計者として、初期からAPIバージョニング（v1）を導入したい。これにより、将来の破壊的変更に備え、後方互換性を維持できる。

#### 受入基準

1. **WHEN** 認証APIエンドポイントが定義される **THEN** Laravel API **SHALL** 全エンドポイントに`/api/v1`プレフィックスを付与する
2. **WHEN** ルート名が定義される **THEN** Laravel API **SHALL** 全ルート名に`v1.`プレフィックスを付与する（例: `v1.admin.login`）
3. **WHEN** バージョン無しエンドポイント（例: `/api/admin/login`）にリクエストされる **THEN** Laravel API **SHALL** 308 Permanent Redirectで`/api/v1/admin/login`にリダイレクトする
4. **WHEN** フロントエンド環境変数が設定される **THEN** User App **SHALL** `NEXT_PUBLIC_API_VERSION=v1`を含む
5. **WHEN** フロントエンド環境変数が設定される **THEN** Admin App **SHALL** `NEXT_PUBLIC_API_VERSION=v1`を含む
6. **WHEN** APIクライアントが初期化される **THEN** User App/Admin App **SHALL** 環境変数`NEXT_PUBLIC_API_VERSION`を使用してエンドポイントURLを構築する

---

### 要件8: 統一エラーハンドリング実装

**目的:** フロントエンド開発者として、一貫したエラーレスポンス形式で処理したい。これにより、エラーハンドリングロジックを統一し、ユーザー体験を向上させる。

#### 受入基準

1. **WHEN** 認証エラーが発生する **THEN** Laravel API **SHALL** JSON形式（code, message, errors, trace_id）でエラーを返す
2. **IF** InvalidCredentialsExceptionがスローされる **THEN** Laravel API **SHALL** 401 OK（code: "AUTH.INVALID_CREDENTIALS"）を返す
3. **IF** AccountDisabledExceptionがスローされる **THEN** Laravel API **SHALL** 403 Forbidden（code: "AUTH.ACCOUNT_DISABLED"）を返す
4. **WHEN** バリデーションエラーが発生する **THEN** Laravel API **SHALL** 422 Unprocessable Entity（code: "VALIDATION_ERROR", errors: フィールド別エラー配列）を返す
5. **WHEN** APIクライアントがエラーレスポンスを受信する **THEN** User App/Admin App **SHALL** トーストUIでエラーメッセージを表示する
6. **WHEN** APIクライアントがエラーレスポンスを受信する **THEN** User App/Admin App **SHALL** trace_idをログに記録し、デバッグを容易にする

---

### 要件9: User App認証機能実装

**目的:** エンドユーザーとして、User App（ポート13001）でログイン・ログアウトしたい。これにより、保護されたユーザー専用ページにアクセスできる。

#### 受入基準

1. **WHEN** User Appのログイン画面が表示される **THEN** User App **SHALL** email/passwordフォームを表示する
2. **WHEN** User Appでログインフォームが送信される **THEN** User App **SHALL** POST `/api/v1/user/login`にリクエストを送信する
3. **IF** User Appでログインが成功する **THEN** User App **SHALL** トークンをlocalStorage（key: `user_token`）に保存する
4. **WHEN** User Appでログアウトボタンがクリックされる **THEN** User App **SHALL** POST `/api/v1/user/logout`にリクエストを送信する
5. **WHEN** User Appでログアウトが成功する **THEN** User App **SHALL** localStorageからトークンを削除し、ログイン画面にリダイレクトする
6. **WHEN** User Appの保護されたページにアクセスされる **THEN** User App **SHALL** 認証済みでない場合、ログイン画面にリダイレクトする
7. **WHEN** User App起動時 **THEN** User App **SHALL** localStorageからトークンを復元し、GET `/api/v1/user/profile`でユーザー情報を取得する

---

### 要件10: Admin App認証機能実装

**目的:** 管理者として、Admin App（ポート13002）でログイン・ログアウトしたい。これにより、管理者専用ダッシュボードにアクセスできる。

#### 受入基準

1. **WHEN** Admin Appのログイン画面が表示される **THEN** Admin App **SHALL** email/passwordフォームを表示する
2. **WHEN** Admin Appでログインフォームが送信される **THEN** Admin App **SHALL** POST `/api/v1/admin/login`にリクエストを送信する
3. **IF** Admin Appでログインが成功する **THEN** Admin App **SHALL** トークンをlocalStorage（key: `admin_token`）に保存する
4. **WHEN** Admin Appでログアウトボタンがクリックされる **THEN** Admin App **SHALL** POST `/api/v1/admin/logout`にリクエストを送信する
5. **WHEN** Admin Appでログアウトが成功する **THEN** Admin App **SHALL** localStorageからトークンを削除し、ログイン画面にリダイレクトする
6. **WHEN** Admin Appの管理者ダッシュボードにアクセスされる **THEN** Admin App **SHALL** 認証済みでない場合、ログイン画面にリダイレクトする
7. **WHEN** Admin App起動時 **THEN** Admin App **SHALL** localStorageからトークンを復元し、GET `/api/v1/admin/dashboard`で管理者情報を取得する

---

### 要件11: 権限分離検証

**目的:** セキュリティエンジニアとして、User/Admin間でトークンが混在しないことを保証したい。これにより、権限昇格攻撃を防ぐ。

#### 受入基準

1. **WHEN** UserトークンでAdmin APIエンドポイントにアクセスされる **THEN** Laravel API **SHALL** 401 Unauthorizedを返す
2. **WHEN** AdminトークンでUser専用APIエンドポイントにアクセスされる **THEN** Laravel API **SHALL** 401 Unauthorizedを返す
3. **WHEN** `personal_access_tokens`テーブルにトークンが保存される **THEN** Laravel API **SHALL** `tokenable_type`カラムで`App\Models\User`または`App\Models\Admin`を区別する
4. **WHEN** AdminGuard middlewareがトークン検証を行う **THEN** Laravel API **SHALL** `tokenable_type`が`App\Models\Admin`であることを確認する
5. **WHEN** UserGuard middlewareがトークン検証を行う **THEN** Laravel API **SHALL** `tokenable_type`が`App\Models\User`であることを確認する

---

### 要件12: バックエンドテスト実装（Pest 4）

**目的:** テストエンジニアとして、包括的な自動テストで品質を保証したい。これにより、リグレッションを防ぎ、リファクタリングを安全に実施できる。

#### 受入基準

1. **WHEN** Domain層のAdminTestが実行される **THEN** Laravel API **SHALL** `canAccessAdminPanel()`ロジックをテストする（isActive true/false）
2. **WHEN** Application層のLoginAdminUseCaseTestが実行される **THEN** Laravel API **SHALL** 正常系・異常系（無効認証情報、無効化アカウント）をテストする
3. **WHEN** Feature層のAdmin LoginTestが実行される **THEN** Laravel API **SHALL** HTTP統合テスト（POST `/api/v1/admin/login`）を実行する
4. **WHEN** Feature層のAPI Versioning Testが実行される **THEN** Laravel API **SHALL** v1エンドポイント正常動作とリダイレクト動作をテストする
5. **WHEN** Feature層のAdmin DashboardTestが実行される **THEN** Laravel API **SHALL** 認証済み/未認証/User権限での管理者ダッシュボードアクセスをテストする
6. **WHEN** 全バックエンドテストが実行される **THEN** Laravel API **SHALL** テストカバレッジ85%以上を達成する

---

### 要件13: フロントエンドテスト実装（Jest + Testing Library）

**目的:** フロントエンドエンジニアとして、UIコンポーネントと認証ロジックをテストしたい。これにより、ユーザー体験の品質を保証する。

#### 受入基準

1. **WHEN** User App LoginPage.test.tsxが実行される **THEN** User App **SHALL** ログインフォーム表示・送信・エラー表示をテストする
2. **WHEN** User App AuthContext.test.tsxが実行される **THEN** User App **SHALL** login/logout/ユーザー状態更新をテストする
3. **WHEN** Admin App LoginPage.test.tsxが実行される **THEN** Admin App **SHALL** ログインフォーム表示・送信・エラー表示をテストする
4. **WHEN** Admin App AdminAuthContext.test.tsxが実行される **THEN** Admin App **SHALL** login/logout/管理者状態更新をテストする
5. **WHEN** API Endpoint Versioning Testが実行される **THEN** User App/Admin App **SHALL** 正しいAPIバージョン（v1）のエンドポイントを使用していることをテストする
6. **WHEN** 全フロントエンドテストが実行される **THEN** User App/Admin App **SHALL** テストカバレッジ80%以上を達成する

---

### 要件14: E2Eテスト実装（Playwright）

**目的:** QAエンジニアとして、フルスタック統合テストで実際のユーザーフローを検証したい。これにより、本番環境に近い状態での動作保証を得る。

#### 受入基準

1. **WHEN** User認証フローE2Eテストが実行される **THEN** Playwright **SHALL** ログイン→プロフィール表示→ログアウトの一連フローをテストする
2. **WHEN** Admin認証フローE2Eテストが実行される **THEN** Playwright **SHALL** ログイン→ダッシュボード表示→ログアウトの一連フローをテストする
3. **WHEN** ガード分離検証E2Eテストが実行される **THEN** Playwright **SHALL** UserトークンでAdmin画面アクセス不可を検証する
4. **WHEN** ガード分離検証E2Eテストが実行される **THEN** Playwright **SHALL** AdminトークンでUser専用画面アクセス不可を検証する
5. **WHEN** API v1エンドポイントアクセステストが実行される **THEN** Playwright **SHALL** `/api/v1/*`エンドポイントへの正常アクセスを検証する
6. **WHEN** E2Eテストが実行される **THEN** Playwright **SHALL** Docker環境で全サービス起動後にテストを実行する

---

### 要件15: 技術ドキュメント作成

**目的:** 開発チームメンバーとして、技術仕様と運用ガイドを参照したい。これにより、開発・保守・トラブルシューティングを効率化する。

#### 受入基準

1. **WHEN** 認証フロー図が作成される **THEN** Laravel Next.js B2C **SHALL** MermaidシーケンスダイアグラムでUser/Admin別認証フローを図示する
2. **WHEN** API仕様書が作成される **THEN** Laravel Next.js B2C **SHALL** OpenAPI 3.0.0形式で全v1エンドポイント（パラメーター、レスポンス形式含む）を文書化する
3. **WHEN** APIバージョニング戦略ドキュメントが作成される **THEN** Laravel Next.js B2C **SHALL** 破壊的変更定義、バージョンサポート期間、移行手順を記載する
4. **WHEN** セットアップガイドが作成される **THEN** Laravel Next.js B2C **SHALL** マイグレーション実行、Seeder実行、環境変数設定、開発サーバー起動の手順を記載する
5. **WHEN** トラブルシューティングガイドが作成される **THEN** Laravel Next.js B2C **SHALL** よくある問題（トークン認証失敗、CORS、Admin無効化、APIバージョンエラー）と解決策を記載する
6. **WHEN** セキュリティベストプラクティスドキュメントが作成される **THEN** Laravel Next.js B2C **SHALL** トークンストレージ（localStorage vs HttpOnly Cookie）、CSRF対策、XSS対策の推奨事項を記載する

---

## 非機能要件

### パフォーマンス要件

1. **WHEN** ログインAPIが呼び出される **THEN** Laravel API **SHALL** 200ms以内にレスポンスを返す（平均値、95パーセンタイル）
2. **WHEN** 認証済みAPIエンドポイントが呼び出される **THEN** Laravel API **SHALL** トークン検証オーバーヘッドを10ms以内に抑える
3. **WHEN** フロントエンドでページ遷移が発生する **THEN** User App/Admin App **SHALL** 認証状態確認を100ms以内に完了する

### セキュリティ要件

1. **WHEN** パスワードがデータベースに保存される **THEN** Laravel API **SHALL** bcryptアルゴリズム（コスト係数10以上）でハッシュ化する
2. **WHEN** Sanctumトークンが発行される **THEN** Laravel API **SHALL** UUIDベースのランダムトークン（推測不可能）を生成する
3. **WHEN** 認証エラーが発生する **THEN** Laravel API **SHALL** 詳細な失敗理由を漏らさない（例: "メールアドレスまたはパスワードが正しくありません"）
4. **WHEN** localStorageにトークンが保存される **THEN** User App/Admin App **SHALL** XSS脆弱性対策（CSP設定、エスケープ処理）を実施する

### 保守性要件

1. **WHEN** 新機能が追加される **THEN** Laravel API **SHALL** DDD 4層構造の依存方向ルールに従う（HTTP→Application→Domain←Infrastructure）
2. **WHEN** ビジネスロジックが変更される **THEN** Laravel API **SHALL** Domain層の変更のみで完結する（Infrastructure層の変更不要）
3. **WHEN** APIエンドポイントが追加される **THEN** Laravel API **SHALL** バージョニング戦略に従い、破壊的変更時は新バージョン（v2等）を作成する

### テスタビリティ要件

1. **WHEN** 任意の層のテストが実行される **THEN** Laravel API **SHALL** 依存注入（DI）により、モック/スタブの差し替えを可能にする
2. **WHEN** Integration Testが実行される **THEN** Laravel API **SHALL** In-Memory RepositoryまたはSQLiteテストDBを使用し、外部依存を最小化する
3. **WHEN** E2Eテストが実行される **THEN** Playwright **SHALL** Docker環境で全サービス起動し、本番環境に近い状態でテストする

---

## 制約条件

### 技術制約

1. **Laravel**: バージョン12以降を使用する
2. **Sanctum**: バージョン4.0以降を使用する
3. **Next.js**: バージョン15.5以降を使用する
4. **React**: バージョン19以降を使用する
5. **Pest**: バージョン4以降を使用する（PHPUnitからの完全移行済み）
6. **Playwright**: バージョン1.47.2以降を使用する
7. **PostgreSQL**: バージョン17以降を使用する（開発・本番環境）
8. **SQLite**: テスト環境で高速テスト実行のため使用可能

### アーキテクチャ制約

1. **DDD 4層構造**: Domain/Application/Infrastructure/Presentation層の明確な分離を維持する
2. **依存方向ルール**: HTTP→Application→Domain←Infrastructureを厳守する
3. **Repository Pattern**: データアクセスはRepository Interfaceを経由する（直接Eloquent操作禁止）
4. **SOLID原則**: 単一責任、オープン・クローズド、リスコフの置換、インターフェース分離、依存性逆転を遵守する

### 環境制約

1. **ポート固定**: User App（13001）、Admin App（13002）、Laravel API（13000）のポートは固定する
2. **Docker統合**: 全サービスはDocker Composeで一括起動可能にする
3. **環境変数管理**: 開発環境（`.env`）、本番環境（環境変数）で適切に分離する

### 除外制約

1. **パスワードリセット**: 本仕様の対象外（別Issue #XX で実装予定）
2. **メール認証**: 本仕様の対象外（別Issue #YY で実装予定）
3. **2FA（二要素認証）**: 本仕様の対象外（将来実装検討）
4. **OAuth連携**: 本仕様の対象外（将来実装検討）
5. **RBAC詳細実装**: 基本roleカラムのみ実装、詳細な権限管理は将来実装
6. **API v2実装**: 設計のみ記載、実装は将来の破壊的変更時

---

## 用語集

| 用語 | 定義 |
|------|------|
| **User** | エンドユーザー。User App（ポート13001）を使用し、`users`テーブルで管理される。 |
| **Admin** | 管理者ユーザー。Admin App（ポート13002）を使用し、`admins`テーブルで管理される。 |
| **Sanctum** | Laravel公式のAPI認証パッケージ。Personal Access Tokensによるステートレス認証を提供。 |
| **Personal Access Token** | SanctumがユーザーごとにUUIDベースで発行するAPIトークン。 |
| **Guard** | Laravelの認証ガード機構。本仕様では'api'（User用）と'admin'（Admin用）の2つを定義。 |
| **DDD（Domain-Driven Design）** | ドメイン駆動設計。ビジネスロジックを中心に据えた設計手法。 |
| **4層構造** | Domain層、Application層、Infrastructure層、Presentation層（HTTP）の4層アーキテクチャ。 |
| **UseCase** | Application層のビジネスユースケース実装。Input/Output契約を持つ。 |
| **Repository** | データアクセスを抽象化するパターン。InterfaceをDomain層、実装をInfrastructure層に配置。 |
| **Entity** | Domain層のビジネスオブジェクト。一意識別子を持つ。 |
| **Value Object** | Domain層の値オブジェクト。一意識別子を持たず、値で等価性を判定。 |
| **APIバージョニング** | APIエンドポイントにバージョン番号（v1）を付与し、将来の破壊的変更に対応する戦略。 |
| **EARS** | Easy Approach to Requirements Syntax。要件記述フォーマット（WHEN/IF/WHILE/WHERE構文）。 |
| **Pest** | PHPのモダンテストフレームワーク。PHPUnitの後継として採用。 |
| **E2E（End-to-End）** | エンドツーエンドテスト。フルスタック統合テスト。Playwrightで実装。 |

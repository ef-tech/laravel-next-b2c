# 実装計画

- [x] 1. データベース基盤とSeeder準備
- [x] 1.1 管理者専用テーブルを作成
  - adminsテーブルマイグレーション作成（id、name、email、email_verified_at、password、role、is_active、remember_token、created_at、updated_at、deleted_atカラム含む）
  - admins.emailにUNIQUE制約を設定
  - admins.is_activeにINDEXを設定
  - admins.deleted_atにINDEX設定（SoftDeletes用）
  - roleカラムはデフォルト'admin'、is_activeカラムはデフォルトtrueに設定
  - _Requirements: 1.1, 1.2, 1.3_

- [x] 1.2 開発用管理者データSeederを作成
  - AdminSeeder作成（admin@example.com / password / super_admin）
  - 開発用スタッフアカウント作成（staff@example.com / password / admin）
  - パスワードはbcryptでハッシュ化
  - is_active=trueに設定
  - _Requirements: 1.4, 1.5, 1.6_

- [ ] 2. Domain層実装（DDD 4層構造の中核）
- [x] 2.1 Admin集約のEntityとValueObjectを実装
  - Admin Entity作成（id、name、email、role、isActiveプロパティ）
  - canAccessAdminPanel()ビジネスメソッド実装（isActive=trueの場合のみtrueを返す）
  - equals()メソッド実装（id比較による等価性判定）
  - AdminRole ValueObject作成（'admin' または 'super_admin' のバリデーション）
  - 無効なロール値でInvalidArgumentExceptionをスロー
  - isSuperAdmin()メソッド実装
  - AdminId ValueObject作成（一意識別子）
  - Email ValueObject（User集約と共有、既存実装を活用）
  - _Requirements: 2.1, 2.2, 2.3, 2.6_

- [x] 2.2 AdminRepository Interfaceを定義
  - findByEmail(Email $email): ?Admin メソッド宣言
  - verifyCredentials(Email $email, string $password): ?Admin メソッド宣言
  - save(Admin $admin): void メソッド宣言
  - フレームワーク非依存（Eloquent、Facade等への依存なし、Carbon除く）
  - _Requirements: 2.4, 2.5, 2.6_

- [ ] 3. Application層実装（ユースケースとDTO）
- [x] 3.1 LoginAdminUseCaseを実装
  - LoginAdminInput DTOを作成（email、password）
  - LoginAdminOutput DTOを作成（token、adminDTO）
  - AdminDTO作成（id、name、email、role、isActive）
  - execute()メソッド実装（AdminRepository::verifyCredentials呼び出し）
  - 無効な認証情報でInvalidCredentialsExceptionをスロー
  - is_active=falseでAccountDisabledExceptionをスロー
  - トークン発行ロジック（Sanctum PersonalAccessToken）
  - AdminDTOマッピング
  - _Requirements: 3.1, 3.2, 3.3, 3.6_

- [x] 3.2 LogoutAdminUseCaseを実装
  - LogoutAdminInput DTOを作成（tokenId）
  - execute()メソッド実装（指定されたtokenIdのトークンを失効）
  - personal_access_tokens.deleted_at設定によるトークン失効
  - トランザクション内で実行
  - _Requirements: 3.4_

- [x] 3.3 GetAuthenticatedAdminUseCaseを実装
  - GetAuthenticatedAdminInput DTOを作成（adminId）
  - GetAuthenticatedAdminOutput DTOを作成（admin: AdminDTO）
  - execute()メソッド実装（AdminRepository::findById呼び出し）
  - 存在しない場合はAdminNotFoundExceptionをスロー
  - isActive=falseの管理者も取得可能（認証済みのため）
  - _Requirements: 3.5_

- [ ] 4. Infrastructure層実装（永続化とEloquent統合）
- [x] 4.1 Admin Eloquentモデルを作成
  - HasApiTokens、HasFactory、SoftDeletesトレイト使用
  - $fillable設定（name、email、password、role、is_active）
  - $hidden設定（password、remember_token）
  - casts()メソッド設定（email_verified_at: datetime、password: hashed、is_active: boolean）
  - adminsテーブルとのORMマッピング
  - _Requirements: 4.1, 4.2, 4.3_

- [x] 4.2 EloquentAdminRepositoryを実装
  - AdminRepository Interfaceを完全実装
  - findByEmail()実装（Eloquent where('email')->first()）
  - verifyCredentials()実装（パスワードハッシュ検証、is_activeチェック）
  - save()実装（Eloquent findOrNew()->save()）
  - mapToDomainEntity()プライベートメソッド実装（Eloquent → Domain Entity マッピング）
  - 永続化層の詳細（created_at、updated_at等）をDomain層に漏らさない
  - _Requirements: 4.4, 4.5, 4.6_

- [x] 4.3 DI Container（DddServiceProvider）にAdminRepository登録
  - app/Providers/DddServiceProvider.php更新
  - AdminRepositoryInterface → EloquentAdminRepository バインディング追加
  - 既存UserRepositoryバインディングのパターンを踏襲
  - _Requirements: 4.4_

- [x] 5. Presentation層実装（API Controller とミドルウェア）
- [x] 5.1 AdminLoginControllerを実装
  - POST /api/v1/admin/login エンドポイント処理
  - LoginRequest作成（email必須・email形式、password必須・最低8文字バリデーション）
  - LoginAdminUseCaseを呼び出し
  - 成功時: 200 OK（token、admin リソース）
  - 失敗時: 統一エラーレスポンス形式（code、message、errors、trace_id）
  - _Requirements: 5.1, 5.2, 5.3, 5.6_

- [x] 5.2 AdminLogoutControllerを実装
  - POST /api/v1/admin/logout エンドポイント処理
  - auth:admin middleware保護
  - LogoutAdminUseCaseを呼び出し
  - 成功時: 200 OK（空レスポンス）
  - _Requirements: 5.4, 5.6_

- [x] 5.3 AdminDashboardControllerを実装
  - GET /api/v1/admin/dashboard エンドポイント処理
  - auth:admin + AdminGuard middleware保護
  - GetAuthenticatedAdminUseCaseを呼び出し
  - 成功時: 200 OK（admin リソース）
  - _Requirements: 5.5, 5.6_

- [x] 6. Sanctumマルチガード認証設定とミドルウェア
- [x] 6.1 config/auth.phpにadminガード追加
  - 'api'ガード定義（provider: users、既存実装）
  - 'admin'ガード定義（provider: admins、新規追加）
  - adminプロバイダー定義（driver: eloquent、model: App\Models\Admin）
  - _Requirements: 6.1, 6.2_

- [x] 6.2 AdminGuard Middlewareを実装
  - $request->user('admin')で認証確認
  - Admin型チェック（instanceof Admin）
  - 非Admin型の場合: 401 Unauthorizedをスロー
  - is_active=falseの場合: 403 Forbiddenをスロー
  - tokenable_typeがApp\Models\Adminであることを保証
  - _Requirements: 6.3, 6.4, 6.5, 11.1, 11.4_

- [x] 6.3 UserGuard Middlewareを実装
  - $request->user('api')で認証確認
  - User型チェック（instanceof User）
  - 非User型の場合: 401 Unauthorizedをスロー
  - tokenable_typeがApp\Models\Userであることを保証
  - _Requirements: 6.6, 11.2, 11.5_

- [x] 7. APIバージョニング戦略実装（v1プレフィックス）
- [x] 7.1 routes/api.phpにv1ルート追加
  - Route::prefix('v1')->name('v1.')でv1グループ作成
  - 全認証エンドポイントをv1グループ内に配置
  - ルート名に'v1.'プレフィックス付与（例: v1.admin.login）
  - バージョン無しエンドポイント（/api/admin/login）から/api/v1/admin/loginへの308 Permanent Redirect設定
  - User v1 Controllers作成（LoginController, LogoutController, ProfileController）
  - Admin v1 エンドポイント実装済み
  - _Requirements: 7.1, 7.2, 7.3_

- [x] 7.2 フロントエンド環境変数NEXT_PUBLIC_API_VERSIONを設定
  - User App .env.local に NEXT_PUBLIC_API_VERSION=v1 追加
  - Admin App .env.local に NEXT_PUBLIC_API_VERSION=v1 追加
  - User App .env.example に NEXT_PUBLIC_API_VERSION=v1 追加
  - Admin App .env.example に NEXT_PUBLIC_API_VERSION=v1 追加
  - env.ts（User App/Admin App）にNEXT_PUBLIC_API_VERSION追加（Zodバリデーション）
  - buildApiUrl()ヘルパー関数作成（api.ts）でエンドポイントURL構築
  - buildApiUrl()のユニットテスト作成（api.test.ts）
  - _Requirements: 7.4, 7.5, 7.6_

- [ ] 8. 統一エラーハンドリング実装
- [x] 8.1 カスタム例外クラスを作成
  - InvalidCredentialsException作成（DomainException継承、code: AUTH.INVALID_CREDENTIALS）
  - AccountDisabledException作成（DomainException継承、code: AUTH.ACCOUNT_DISABLED）
  - AdminNotFoundException作成（DomainException継承、code: ADMIN_NOT_FOUND）
  - ApplicationExceptionベースクラス作成
  - getErrorCode()メソッド実装（文字列エラーコード取得）
  - ユニットテスト完了（7テスト成功）
  - _Requirements: 8.2, 8.3_

- [x] 8.2 グローバルエラーハンドラーを更新
  - bootstrap/app.phpのwithExceptions()クロージャ更新（Laravel 12はHandlerクラスなし）
  - 統一エラーレスポンス形式実装（code、message、errors、trace_id）
  - trace_idとしてリクエストUUIDを含める（SetRequestId middleware連携）
  - InvalidCredentialsException: 401 Unauthorized（code: AUTH.INVALID_CREDENTIALS）
  - AccountDisabledException: 403 Forbidden（code: AUTH.ACCOUNT_DISABLED）
  - AdminNotFoundException: 404 Not Found（code: ADMIN_NOT_FOUND）
  - ValidationException: 422 Unprocessable Entity（code: VALIDATION_ERROR、errors: フィールド別エラー配列）
  - getStatusCode()メソッド実装（各例外クラスに適切なHTTPステータスコード）
  - Featureテスト完了（5テスト成功）
  - _Requirements: 8.1, 8.4, 8.6_

- [x] 8.3 フロントエンドAPIクライアントエラーハンドリングを実装
  - ApiError class作成（code, message, statusCode, traceId, errors）
  - handleApiError()関数実装（統一エラーレスポンス処理）
  - trace_idをconsole.errorでログに記録（デバッグ用）
  - ErrorHandlersヘルパー実装（エラーコード別処理分岐）
    - isAuthError(): AUTH.INVALID_CREDENTIALS | AUTH.TOKEN_EXPIRED | AUTH.ACCOUNT_DISABLED
    - isValidationError(): VALIDATION_ERROR
    - isInvalidCredentials(), isAccountDisabled(), isTokenExpired()
  - admin-app: 18テスト成功（api-error-handler.test.ts: 7, api.test.ts: 11）
  - user-app: 18テスト成功（api-error-handler.test.ts: 7, api.test.ts: 11）
  - 全テストスイート成功（admin-app: 73, user-app: 69）
  - _Requirements: 8.5, 8.6_
  - 注: トーストUI表示は Task 9/10 の認証Context実装時に統合

- [ ] 9. User App認証機能実装
- [x] 9.1 User App AuthContextを作成
  - React Context API使用
  - login(email, password)メソッド実装（POST /api/v1/user/login）
  - logout()メソッド実装（POST /api/v1/user/logout）
  - fetchUserProfile()メソッド実装（GET /api/v1/user/profile）
  - user、token、isLoading、isAuthenticated状態管理
  - トークンをlocalStorage保存（key: user_token）
  - トークン復元処理実装（初回ロード時）
  - 全11テスト成功（AuthContext.test.tsx）
  - _Requirements: 9.1, 9.2, 9.3, 9.4, 9.5, 9.7_

- [x] 9.2 User App useAuth hooksを作成
  - useAuth()フック実装（AuthContext.tsx内で実装完了）
  - ログイン・ログアウト・認証状態確認のAPI提供
  - _Requirements: 9.1, 9.2, 9.4, 9.5_

- [x] 9.3 User App LoginPageを作成
  - LoginPageコンポーネント作成（email/passwordフォーム）
  - クライアント側バリデーション実装（email形式、password最低8文字）
  - ログインボタンクリック時にAuthContext.login()呼び出し
  - エラーハンドリング統合（Task 8.3のErrorHandlers使用）
  - ログイン成功時にホーム画面（/）へリダイレクト
  - ログイン処理中のUI無効化
  - 全11テスト成功（LoginPage.test.tsx）
  - _Requirements: 9.1, 9.2, 9.3_

- [x] 9.4 User App LogoutButton実装
  - LogoutButtonコンポーネント作成
  - logout()関数呼び出し（useAuth hooks使用）
  - トークン削除（AuthContext内で処理）
  - ログイン画面へリダイレクト（成功時・エラー時両方）
  - カスタムclassName・children props対応
  - 全7テスト成功（LogoutButton.test.tsx）
  - _Requirements: 9.1, 9.2_

- [x] 9.5 User App 認証ルート保護
  - Next.js middleware実装（middleware.ts）
  - 認証が必要なページリスト定義（/profile）
  - Cookieからuser_tokenトークン検証
  - 未認証時にログイン画面（/login）へリダイレクト
  - 静的ファイル・APIルートは処理スキップ
  - 全8テスト成功（middleware.test.ts）
  - _Requirements: 9.6_

- [x] 9.6 User App ProfilePageを作成
  - ProfilePageコンポーネント作成
  - 初回表示時にfetchUserProfile()呼び出し
  - 認証済みユーザー情報表示（id、name、email）
  - 未認証時にログイン画面へリダイレクト
  - エラーハンドリング実装
  - LogoutButton統合
  - 全5テスト成功（profile/page.test.tsx）
  - _Requirements: 9.4_

- [x] 9.7 User App 初回ロード時のトークン復元
  - localStorageからトークン読み込み
  - トークン検証（GET /api/v1/user/profile）
  - 無効なトークンの場合はクリア
  - AuthContext.tsx内で実装完了
  - _Requirements: 9.7_

- [ ] 10. Admin App認証機能実装
- [ ] 10.1 Admin App AdminAuthContextを作成
  - React Context API使用
  - login(email, password)メソッド実装（POST /api/v1/admin/login）
  - logout()メソッド実装（POST /api/v1/admin/logout）
  - fetchAdminInfo()メソッド実装（GET /api/v1/admin/dashboard）
  - admin、token、isLoading、isAuthenticated状態管理
  - トークンをlocalStorage保存（key: admin_token）
  - _Requirements: 10.1, 10.2, 10.3, 10.4, 10.5, 10.7_

- [ ] 10.2 Admin App useAdminAuth hooksを作成
  - useAdminAuthContext()フック実装
  - ログイン・ログアウト・認証状態確認のAPI提供
  - _Requirements: 10.1, 10.2, 10.4, 10.5_

- [ ] 10.3 Admin App APIクライアントを実装
  - apiEndpoints.admin.login定義（POST /api/v1/admin/login）
  - apiEndpoints.admin.logout定義（POST /api/v1/admin/logout）
  - apiEndpoints.admin.dashboard定義（GET /api/v1/admin/dashboard）
  - NEXT_PUBLIC_API_VERSION環境変数を使用したURL構築
  - Authorizationヘッダー自動付与（Bearer token）
  - _Requirements: 10.2, 10.3, 10.4_

- [ ] 10.4 Admin App ログイン画面を作成
  - LoginPageコンポーネント作成（email/passwordフォーム）
  - フォームバリデーション（email形式、password最低8文字）
  - ログインボタンクリック時にAdminAuthContext.login()呼び出し
  - エラー時にトーストUI表示
  - 成功時にダッシュボードにリダイレクト
  - _Requirements: 10.1, 10.2, 10.3_

- [ ] 10.5 Admin App 管理者ダッシュボードのリダイレクト実装
  - 認証済みでない場合、ログイン画面にリダイレクト
  - useAdminAuthContext()フックで認証状態確認
  - _Requirements: 10.6_

- [ ] 11. 権限分離検証実装
- [ ] 11.1 personal_access_tokensテーブルのtokenable_type検証
  - UserトークンでAdmin APIエンドポイントアクセス時に401 Unauthorizedを返す
  - AdminトークンでUser専用APIエンドポイントアクセス時に401 Unauthorizedを返す
  - tokenable_typeカラムでApp\Models\UserまたはApp\Models\Adminを区別
  - AdminGuard middlewareでtokenable_type=App\Models\Admin検証
  - UserGuard middlewareでtokenable_type=App\Models\User検証
  - _Requirements: 11.1, 11.2, 11.3, 11.4, 11.5_

- [ ] 12. バックエンドテスト実装（Pest 4）
- [ ] 12.1 Domain層Unitテストを実装
  - AdminTest作成（canAccessAdminPanel()ロジックテスト、isActive true/false）
  - AdminRoleTest作成（バリデーションテスト、isSuperAdmin()テスト、equals()テスト）
  - Admin Entityのimmutability検証
  - _Requirements: 12.1_

- [ ] 12.2 Application層Unitテストを実装
  - LoginAdminUseCaseTest作成（正常系・異常系テスト）
  - 有効な認証情報でLoginAdminOutputを返す
  - 無効な認証情報でInvalidCredentialsExceptionをスロー
  - is_active=falseでAccountDisabledExceptionをスロー
  - トークン発行検証
  - AdminDTOマッピング検証
  - _Requirements: 12.2_

- [ ] 12.3 Feature層統合テストを実装
  - Admin LoginTest作成（POST /api/v1/admin/loginのHTTP統合テスト）
  - 正しい認証情報で200 OK
  - 無効な認証情報で401 Unauthorized
  - バリデーションエラーで422 Unprocessable Entity
  - ログアウトテスト（POST /api/v1/admin/logout）
  - 管理者ダッシュボードアクセステスト（GET /api/v1/admin/dashboard）
  - 認証済み/未認証/User権限での管理者ダッシュボードアクセステスト
  - _Requirements: 12.3, 12.5_

- [ ] 12.4 API Versioning Testを実装
  - v1エンドポイント正常動作テスト（POST /api/v1/admin/login）
  - バージョン無しエンドポイントのリダイレクトテスト（POST /api/admin/login → /api/v1/admin/login）
  - ルート名検証（v1.admin.login）
  - _Requirements: 12.4_

- [ ] 12.5 テストカバレッジ85%以上達成
  - Pest --coverage実行
  - カバレッジレポート確認
  - 不足箇所の追加テスト実装
  - _Requirements: 12.6_

- [ ] 13. フロントエンドテスト実装（Jest + Testing Library）
- [ ] 13.1 User App テストを実装
  - LoginPage.test.tsx作成（ログインフォーム表示・送信・エラー表示テスト）
  - AuthContext.test.tsx作成（login/logout/ユーザー状態更新テスト）
  - API Endpoint Versioning Test（正しいAPIバージョン（v1）使用確認）
  - _Requirements: 13.1, 13.2, 13.5_

- [ ] 13.2 Admin App テストを実装
  - LoginPage.test.tsx作成（ログインフォーム表示・送信・エラー表示テスト）
  - AdminAuthContext.test.tsx作成（login/logout/管理者状態更新テスト）
  - API Endpoint Versioning Test（正しいAPIバージョン（v1）使用確認）
  - _Requirements: 13.3, 13.4, 13.5_

- [ ] 13.3 テストカバレッジ80%以上達成
  - Jest --coverage実行
  - カバレッジレポート確認
  - 不足箇所の追加テスト実装
  - _Requirements: 13.6_

- [ ] 14. E2Eテスト実装（Playwright）
- [ ] 14.1 User認証フローE2Eテストを実装
  - e2e/projects/user/tests/auth.spec.ts作成
  - ログイン→プロフィール表示→ログアウトの一連フロー検証
  - プロフィール画面でユーザー名正しく表示
  - ログアウト後にログイン画面にリダイレクト
  - _Requirements: 14.1_

- [ ] 14.2 Admin認証フローE2Eテストを実装
  - e2e/projects/admin/tests/auth.spec.ts作成
  - ログイン→ダッシュボード表示→ログアウトの一連フロー検証
  - ダッシュボードで管理者名正しく表示
  - ログアウト後にログイン画面にリダイレクト
  - _Requirements: 14.2_

- [ ] 14.3 ガード分離検証E2Eテストを実装
  - UserトークンでAdmin画面アクセス不可検証（401 Unauthorizedエラーページ表示）
  - AdminトークンでUser専用画面アクセス不可検証（401 Unauthorizedエラーページ表示）
  - tokenable_type別のアクセス制御検証
  - _Requirements: 14.3, 14.4_

- [ ] 14.4 API v1エンドポイントアクセステストを実装
  - /api/v1/*エンドポイントへの正常アクセス検証
  - バージョニング正常動作確認
  - _Requirements: 14.5_

- [ ] 14.5 Docker環境でE2Eテスト実行
  - docker-compose.yml統合
  - 全サービス起動後のテスト実行
  - E2Eテスト実行スクリプト作成（scripts/test/run-e2e-tests.sh統合）
  - _Requirements: 14.6_

- [ ] 15. 技術ドキュメント作成
- [ ] 15.1 認証フロー図を作成
  - Mermaidシーケンスダイアグラム作成
  - User/Admin別認証フローを図示
  - トークン発行・検証フロー記載
  - _Requirements: 15.1_

- [ ] 15.2 API仕様書を作成
  - OpenAPI 3.0.0形式で文書化
  - 全v1エンドポイント記載（POST /api/v1/admin/login、POST /api/v1/user/login等）
  - パラメーター、レスポンス形式、エラーコード記載
  - 統一エラーレスポンス形式のスキーマ定義
  - _Requirements: 15.2_

- [ ] 15.3 APIバージョニング戦略ドキュメントを作成
  - 破壊的変更定義
  - バージョンサポート期間（v1: 最低6ヶ月）
  - v2移行手順
  - 後方互換性ルール記載
  - _Requirements: 15.3_

- [ ] 15.4 セットアップガイドを作成
  - マイグレーション実行手順（php artisan migrate）
  - Seeder実行手順（php artisan db:seed --class=AdminSeeder）
  - 環境変数設定（.env、.env.local）
  - 開発サーバー起動手順（make dev）
  - 管理者アカウント初期情報記載
  - _Requirements: 15.4_

- [ ] 15.5 トラブルシューティングガイドを作成
  - トークン認証失敗
  - CORSエラー
  - Admin無効化エラー
  - APIバージョンエラー
  - よくある問題と解決策記載
  - _Requirements: 15.5_

- [ ] 15.6 セキュリティベストプラクティスドキュメントを作成
  - トークンストレージ（localStorage vs HttpOnly Cookie比較）
  - CSRF対策（Sanctumステートレストークン説明）
  - XSS対策（CSP設定推奨）
  - パスワードハッシュ化（bcryptアルゴリズム、コスト係数10以上）
  - レート制限（DynamicRateLimit middleware活用）
  - _Requirements: 15.6_

# 認証フロー図

このドキュメントでは、Laravel + Next.js B2C アプリケーションにおける User/Admin 認証フローを図示します。

## 目次

- [User 認証フロー](#user-認証フロー)
- [Admin 認証フロー](#admin-認証フロー)
- [トークン検証フロー](#トークン検証フロー)
- [ログアウトフロー](#ログアウトフロー)

---

## User 認証フロー

User App のログインから認証状態確認までの完全なフローを示します。

```mermaid
sequenceDiagram
    actor User
    participant UserApp as User App<br/>(Next.js:13001)
    participant AuthContext as AuthContext
    participant API as Laravel API<br/>(:13000)
    participant UserGuard as UserGuard<br/>Middleware
    participant UseCase as LoginUserUseCase<br/>(Application Layer)
    participant Repository as EloquentUserRepository<br/>(Infrastructure Layer)
    participant DB as Database<br/>(users table)

    User->>UserApp: ログイン画面にアクセス
    UserApp->>User: ログインフォーム表示

    User->>UserApp: メールアドレス・パスワード入力
    User->>UserApp: ログインボタンクリック

    UserApp->>AuthContext: login(email, password)
    AuthContext->>API: POST /api/v1/user/login<br/>{email, password}

    API->>UseCase: execute(email, password)
    UseCase->>Repository: findByEmail(email)
    Repository->>DB: SELECT * FROM users WHERE email=?
    DB-->>Repository: User record
    Repository-->>UseCase: User Entity

    UseCase->>UseCase: パスワード検証<br/>Hash::check(password, user.password)

    alt パスワード正しい
        UseCase->>DB: トークン生成・保存<br/>INSERT INTO personal_access_tokens
        DB-->>UseCase: Token created
        UseCase-->>API: LoginResponse<br/>{token, user: {id, name, email}}
        API-->>AuthContext: 200 OK<br/>{token, user}
        AuthContext->>AuthContext: setUser(user)<br/>setToken(token)
        AuthContext-->>UserApp: 認証成功
        UserApp->>UserApp: /profile へリダイレクト
        UserApp->>User: プロフィール画面表示
    else パスワード誤り
        UseCase-->>API: InvalidCredentialsException
        API-->>AuthContext: 401 Unauthorized<br/>{code: "AUTH.INVALID_CREDENTIALS"}
        AuthContext-->>UserApp: エラー
        UserApp->>User: エラーメッセージ表示
    end

    Note over User,DB: 認証後のAPIリクエスト

    User->>UserApp: プロフィール画面で操作
    UserApp->>API: GET /api/v1/user/profile<br/>Authorization: Bearer {token}

    API->>UserGuard: トークン検証
    UserGuard->>DB: SELECT * FROM personal_access_tokens WHERE token=?
    DB-->>UserGuard: Token record (tokenable_type: User)
    UserGuard->>UserGuard: tokenable_type == User?

    alt User型トークン
        UserGuard->>API: 認証OK
        API-->>UserApp: 200 OK<br/>{user: {id, name, email}}
        UserApp->>User: プロフィール情報表示
    else 非User型トークン
        UserGuard-->>UserApp: 401 Unauthorized<br/>{code: "AUTH.UNAUTHORIZED"}
        UserApp->>UserApp: /login へリダイレクト
    end
```

### フロー説明

1. **ログイン画面表示**: User が `/login` にアクセス
2. **認証情報送信**: User App が `/api/v1/user/login` に POST リクエスト
3. **認証処理**:
   - UseCase がパスワード検証
   - トークン生成（`personal_access_tokens` テーブル）
   - `tokenable_type = 'App\Models\User'` で保存
4. **認証成功**: トークンとユーザー情報を返却、`/profile` へリダイレクト
5. **認証後リクエスト**: Bearer トークンで保護された API にアクセス
6. **トークン検証**: `UserGuard` が `tokenable_type` を検証し、User 型のみ通過

---

## Admin 認証フロー

Admin App のログインから認証状態確認までの完全なフローを示します。

```mermaid
sequenceDiagram
    actor Admin
    participant AdminApp as Admin App<br/>(Next.js:13002)
    participant AdminAuthContext as AdminAuthContext
    participant API as Laravel API<br/>(:13000)
    participant AdminGuard as AdminGuard<br/>Middleware
    participant UseCase as LoginAdminUseCase<br/>(Application Layer)
    participant Repository as EloquentAdminRepository<br/>(Infrastructure Layer)
    participant DB as Database<br/>(admins table)

    Admin->>AdminApp: ログイン画面にアクセス
    AdminApp->>Admin: ログインフォーム表示

    Admin->>AdminApp: メールアドレス・パスワード入力
    Admin->>AdminApp: ログインボタンクリック

    AdminApp->>AdminAuthContext: login(email, password)
    AdminAuthContext->>API: POST /api/v1/admin/login<br/>{email, password}

    API->>UseCase: execute(email, password)
    UseCase->>Repository: findByEmail(email)
    Repository->>DB: SELECT * FROM admins WHERE email=?
    DB-->>Repository: Admin record
    Repository-->>UseCase: Admin Entity

    UseCase->>UseCase: 有効性検証<br/>admin.isActive()

    alt Admin有効かつパスワード正しい
        UseCase->>DB: トークン生成・保存<br/>INSERT INTO personal_access_tokens
        DB-->>UseCase: Token created
        UseCase-->>API: LoginResponse<br/>{token, admin: {id, name, email, role}}
        API-->>AdminAuthContext: 200 OK<br/>{token, admin}
        AdminAuthContext->>AdminAuthContext: setAdmin(admin)<br/>setToken(token)
        AdminAuthContext-->>AdminApp: 認証成功
        AdminApp->>AdminApp: /dashboard へリダイレクト
        AdminApp->>Admin: ダッシュボード画面表示
    else Admin無効
        UseCase-->>API: AdminDisabledException
        API-->>AdminAuthContext: 403 Forbidden<br/>{code: "AUTH.ADMIN_DISABLED"}
        AdminAuthContext-->>AdminApp: エラー
        AdminApp->>Admin: エラーメッセージ表示
    else パスワード誤り
        UseCase-->>API: InvalidCredentialsException
        API-->>AdminAuthContext: 401 Unauthorized<br/>{code: "AUTH.INVALID_CREDENTIALS"}
        AdminAuthContext-->>AdminApp: エラー
        AdminApp->>Admin: エラーメッセージ表示
    end

    Note over Admin,DB: 認証後のAPIリクエスト

    Admin->>AdminApp: ダッシュボードで操作
    AdminApp->>API: GET /api/v1/admin/dashboard<br/>Authorization: Bearer {token}

    API->>AdminGuard: トークン検証
    AdminGuard->>DB: SELECT * FROM personal_access_tokens WHERE token=?
    DB-->>AdminGuard: Token record (tokenable_type: Admin)
    AdminGuard->>AdminGuard: tokenable_type == Admin?

    alt Admin型トークン
        AdminGuard->>DB: SELECT * FROM admins WHERE id=?
        DB-->>AdminGuard: Admin record
        AdminGuard->>AdminGuard: admin.is_active == true?

        alt Admin有効
            AdminGuard->>API: 認証OK
            API-->>AdminApp: 200 OK<br/>{admin: {id, name, email, role}}
            AdminApp->>Admin: ダッシュボード情報表示
        else Admin無効
            AdminGuard-->>AdminApp: 403 Forbidden<br/>{code: "AUTH.ADMIN_DISABLED"}
            AdminApp->>AdminApp: /login へリダイレクト
        end
    else 非Admin型トークン
        AdminGuard-->>AdminApp: 401 Unauthorized<br/>{code: "AUTH.UNAUTHORIZED"}
        AdminApp->>AdminApp: /login へリダイレクト
    end
```

### フロー説明

1. **ログイン画面表示**: Admin が `/login` にアクセス
2. **認証情報送信**: Admin App が `/api/v1/admin/login` に POST リクエスト
3. **認証処理**:
   - UseCase がパスワード検証
   - **Admin 有効性検証** (`is_active = true`)
   - トークン生成（`personal_access_tokens` テーブル）
   - `tokenable_type = 'App\Models\Admin'` で保存
4. **認証成功**: トークンと管理者情報を返却、`/dashboard` へリダイレクト
5. **認証後リクエスト**: Bearer トークンで保護された API にアクセス
6. **トークン検証**: `AdminGuard` が `tokenable_type` と `is_active` を検証し、有効な Admin のみ通過

---

## トークン検証フロー

Laravel Sanctum によるトークン検証の詳細フローを示します。

```mermaid
sequenceDiagram
    participant Client as Client<br/>(User/Admin App)
    participant API as Laravel API
    participant SanctumMiddleware as Sanctum<br/>Middleware
    participant Guard as UserGuard /<br/>AdminGuard
    participant DB as Database

    Client->>API: GET /api/v1/user/profile<br/>Authorization: Bearer {token}

    API->>SanctumMiddleware: トークン検証開始
    SanctumMiddleware->>DB: SELECT * FROM personal_access_tokens<br/>WHERE token = hash({token})

    alt トークン存在
        DB-->>SanctumMiddleware: Token record<br/>{id, tokenable_type, tokenable_id, abilities, expires_at}

        SanctumMiddleware->>SanctumMiddleware: トークン有効期限確認<br/>expires_at > now()

        alt トークン有効
            SanctumMiddleware->>DB: SELECT * FROM {tokenable_type}<br/>WHERE id = {tokenable_id}
            DB-->>SanctumMiddleware: User/Admin record
            SanctumMiddleware->>SanctumMiddleware: $request->user() に設定
            SanctumMiddleware->>Guard: next(request)

            Guard->>Guard: ガード固有の検証<br/>(tokenable_type, is_active等)

            alt ガード検証成功
                Guard->>API: リクエスト通過
                API-->>Client: 200 OK<br/>{data}
            else ガード検証失敗
                Guard-->>Client: 401/403<br/>{code, message}
            end
        else トークン期限切れ
            SanctumMiddleware-->>Client: 401 Unauthorized<br/>{code: "AUTH.TOKEN_EXPIRED"}
        end
    else トークン不存在
        SanctumMiddleware-->>Client: 401 Unauthorized<br/>{code: "AUTH.UNAUTHORIZED"}
    end
```

### トークン検証ステップ

1. **トークンハッシュ化**: リクエストの Bearer トークンを SHA-256 でハッシュ化
2. **DB検索**: `personal_access_tokens` テーブルからトークンレコード取得
3. **有効期限確認**: `expires_at` が現在時刻より後であることを確認
4. **ユーザー取得**: `tokenable_type` と `tokenable_id` から User/Admin レコード取得
5. **ガード検証**: `UserGuard` または `AdminGuard` で追加検証
   - `UserGuard`: `tokenable_type == 'App\Models\User'`
   - `AdminGuard`: `tokenable_type == 'App\Models\Admin'` かつ `is_active == true`

---

## ログアウトフロー

User/Admin のログアウト処理フローを示します。

```mermaid
sequenceDiagram
    actor User/Admin
    participant App as User App /<br/>Admin App
    participant AuthContext as AuthContext /<br/>AdminAuthContext
    participant API as Laravel API
    participant UseCase as LogoutUserUseCase /<br/>LogoutAdminUseCase
    participant DB as Database

    User/Admin->>App: ログアウトボタンクリック
    App->>AuthContext: logout()
    AuthContext->>API: POST /api/v1/user/logout<br/>Authorization: Bearer {token}

    API->>UseCase: execute(user)
    UseCase->>DB: DELETE FROM personal_access_tokens<br/>WHERE tokenable_id = {user.id}<br/>AND tokenable_type = {User/Admin}<br/>AND id = {current_token.id}

    alt トークン削除成功
        DB-->>UseCase: Token deleted
        UseCase-->>API: 成功レスポンス
        API-->>AuthContext: 204 No Content
        AuthContext->>AuthContext: clearUser()<br/>clearToken()
        AuthContext-->>App: ログアウト成功
        App->>App: /login へリダイレクト
        App->>User/Admin: ログイン画面表示
    else トークン削除失敗
        DB-->>UseCase: Error
        UseCase-->>API: Exception
        API-->>AuthContext: 500 Internal Server Error
        AuthContext-->>App: エラー
        App->>User/Admin: エラーメッセージ表示
    end
```

### ログアウト処理の特徴

1. **現在のトークンのみ削除**: ログアウト時は現在使用中のトークンのみを削除
2. **他デバイスのトークンは保持**: 他のデバイスでログインしている場合、そのトークンは無効化されない
3. **全デバイスログアウト**: 全トークン削除が必要な場合は、別エンドポイント `/api/v1/user/logout-all` を使用
4. **クライアント側のクリーンアップ**: `AuthContext` がトークンとユーザー情報をクリア

---

## ガード分離の仕組み

User と Admin のガードが完全に分離されている仕組みを図示します。

```mermaid
graph TB
    subgraph "User App (Port: 13001)"
        UserLogin[Login Page]
        UserProfile[Profile Page]
    end

    subgraph "Admin App (Port: 13002)"
        AdminLogin[Login Page]
        AdminDashboard[Dashboard Page]
    end

    subgraph "Laravel API (Port: 13000)"
        subgraph "User Routes (/api/v1/user/*)"
            UserLoginAPI[POST /login]
            UserProfileAPI[GET /profile]
            UserLogoutAPI[POST /logout]

            UserLoginAPI --> UserGuard
            UserProfileAPI --> UserGuard
            UserLogoutAPI --> UserGuard
        end

        subgraph "Admin Routes (/api/v1/admin/*)"
            AdminLoginAPI[POST /login]
            AdminDashboardAPI[GET /dashboard]
            AdminLogoutAPI[POST /logout]

            AdminLoginAPI --> AdminGuard
            AdminDashboardAPI --> AdminGuard
            AdminLogoutAPI --> AdminGuard
        end

        UserGuard{UserGuard<br/>Middleware}
        AdminGuard{AdminGuard<br/>Middleware}
    end

    subgraph "Database"
        UsersTable[(users table)]
        AdminsTable[(admins table)]
        TokensTable[(personal_access_tokens)]
    end

    UserLogin -->|POST /api/v1/user/login| UserLoginAPI
    UserProfile -->|GET /api/v1/user/profile| UserProfileAPI

    AdminLogin -->|POST /api/v1/admin/login| AdminLoginAPI
    AdminDashboard -->|GET /api/v1/admin/dashboard| AdminDashboardAPI

    UserGuard -->|tokenable_type:<br/>App\Models\User| UsersTable
    AdminGuard -->|tokenable_type:<br/>App\Models\Admin| AdminsTable

    UserLoginAPI --> TokensTable
    AdminLoginAPI --> TokensTable

    UserGuard -.->|検証| TokensTable
    AdminGuard -.->|検証| TokensTable

    style UserGuard fill:#e1f5ff
    style AdminGuard fill:#fff4e1
    style UsersTable fill:#e1f5ff
    style AdminsTable fill:#fff4e1
```

### ガード分離のポイント

1. **完全に独立したルート**: User と Admin は別の URL プレフィックスを使用
2. **専用ミドルウェア**: `UserGuard` と `AdminGuard` で `tokenable_type` を検証
3. **別テーブル**: `users` と `admins` テーブルで完全に分離
4. **トークン識別**: `personal_access_tokens.tokenable_type` でトークンの種類を識別
5. **クロスアクセス防止**: User トークンで Admin エンドポイントにアクセスすると 401 エラー

---

## セキュリティ考慮事項

### トークンストレージ

- **推奨**: HttpOnly Cookie（XSS 攻撃からトークンを保護）
- **代替**: LocalStorage（SPA の場合、CSP で XSS 対策必須）

### トークン有効期限

- **デフォルト**: 24時間（`config/sanctum.php` の `expiration`）
- **推奨**: リフレッシュトークン実装で短い有効期限を設定

### CSRF 対策

- **Sanctum ステートレストークン**: CSRF トークン不要
- **Cookie ベース認証**: `sanctum:csrf-cookie` エンドポイントで CSRF トークン取得

### レート制限

- **ログインエンドポイント**: 1分間に5回まで（`DynamicRateLimit` middleware）
- **一般エンドポイント**: 1分間に60回まで

---

## 関連ドキュメント

- [API仕様書](./API_SPECIFICATION.md)
- [APIバージョニング戦略](./API_VERSIONING_STRATEGY.md)
- [セキュリティベストプラクティス](./SECURITY_BEST_PRACTICES.md)
- [トラブルシューティングガイド](./TROUBLESHOOTING.md)

# API仕様書 - 認証機能 (v1)

Laravel + Next.js B2C アプリケーションの認証API仕様書（OpenAPI 3.0.0形式）

## 目次

- [概要](#概要)
- [ベースURL](#ベースurl)
- [認証方式](#認証方式)
- [エンドポイント一覧](#エンドポイント一覧)
- [統一エラーレスポンス](#統一エラーレスポンス)
- [OpenAPI仕様](#openapi仕様)

---

## 概要

**バージョン**: v1
**プロトコル**: HTTPS (本番環境), HTTP (開発環境)
**コンテンツタイプ**: `application/json`
**認証方式**: Laravel Sanctum Bearer Token

---

## ベースURL

| 環境 | URL |
|------|-----|
| 開発環境 | `http://localhost:13000` |
| 本番環境 | `https://api.example.com` |

すべてのエンドポイントは `/api/v1` プレフィックスを使用します。

---

## 認証方式

### Bearer Token認証

保護されたエンドポイントには、`Authorization` ヘッダーに Bearer トークンを含める必要があります。

```http
Authorization: Bearer {token}
```

**トークン取得方法**:
- User: `POST /api/v1/user/login`
- Admin: `POST /api/v1/admin/login`

**トークン有効期限**: 24時間（デフォルト）

---

## エンドポイント一覧

### User エンドポイント

| メソッド | エンドポイント | 説明 | 認証 |
|---------|---------------|------|------|
| POST | `/api/v1/user/login` | ユーザーログイン | 不要 |
| POST | `/api/v1/user/logout` | ユーザーログアウト | 必要 |
| GET | `/api/v1/user/profile` | ユーザープロフィール取得 | 必要 |

### Admin エンドポイント

| メソッド | エンドポイント | 説明 | 認証 |
|---------|---------------|------|------|
| POST | `/api/v1/admin/login` | 管理者ログイン | 不要 |
| POST | `/api/v1/admin/logout` | 管理者ログアウト | 必要 |
| GET | `/api/v1/admin/dashboard` | 管理者ダッシュボード取得 | 必要 |

### 共通エンドポイント

| メソッド | エンドポイント | 説明 | 認証 |
|---------|---------------|------|------|
| GET | `/api/health` | ヘルスチェック | 不要 |

---

## 統一エラーレスポンス

すべてのエラーレスポンスは以下の形式に従います。

### エラーレスポンス形式

```json
{
  "code": "ERROR_CODE",
  "message": "人間が読める形式のエラーメッセージ",
  "details": {}
}
```

### エラーコード一覧

| HTTPステータス | エラーコード | 説明 |
|---------------|-------------|------|
| 400 | `VALIDATION.FAILED` | バリデーションエラー |
| 401 | `AUTH.UNAUTHORIZED` | 認証されていない |
| 401 | `AUTH.INVALID_CREDENTIALS` | 認証情報が無効 |
| 401 | `AUTH.TOKEN_EXPIRED` | トークンの有効期限切れ |
| 403 | `AUTH.ADMIN_DISABLED` | 管理者アカウントが無効 |
| 404 | `RESOURCE.NOT_FOUND` | リソースが見つからない |
| 429 | `RATE_LIMIT.EXCEEDED` | レート制限を超過 |
| 500 | `SERVER.INTERNAL_ERROR` | サーバー内部エラー |

---

## OpenAPI仕様

以下は、OpenAPI 3.0.0形式の完全なAPI仕様です。

```yaml
openapi: 3.0.0
info:
  title: Laravel Next.js B2C Authentication API
  description: |
    User/Admin完全分離認証システムのAPI仕様書

    ## 特徴
    - Laravel 12 + Sanctum 4.0 ベース
    - User/Admin完全分離（別テーブル、別ガード、別ミドルウェア）
    - APIバージョニング対応（/api/v1プレフィックス）
    - 統一エラーレスポンス形式
    - レート制限実装

    ## 認証方式
    - Bearer Token認証（Laravel Sanctum）
    - トークン有効期限: 24時間

  version: 1.0.0
  contact:
    name: API Support
    email: support@example.com
  license:
    name: MIT
    url: https://opensource.org/licenses/MIT

servers:
  - url: http://localhost:13000/api/v1
    description: 開発環境
  - url: https://api.example.com/api/v1
    description: 本番環境

tags:
  - name: User Authentication
    description: ユーザー認証関連のエンドポイント
  - name: Admin Authentication
    description: 管理者認証関連のエンドポイント
  - name: Health Check
    description: ヘルスチェックエンドポイント

paths:
  /user/login:
    post:
      summary: ユーザーログイン
      description: メールアドレスとパスワードでユーザー認証を行い、アクセストークンを発行します
      operationId: userLogin
      tags:
        - User Authentication
      requestBody:
        required: true
        content:
          application/json:
            schema:
              type: object
              required:
                - email
                - password
              properties:
                email:
                  type: string
                  format: email
                  description: ユーザーのメールアドレス
                  example: user@example.com
                password:
                  type: string
                  format: password
                  minLength: 8
                  description: ユーザーのパスワード
                  example: password123
            examples:
              validUser:
                summary: 有効なユーザー
                value:
                  email: user@example.com
                  password: password123
      responses:
        '200':
          description: ログイン成功
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/UserLoginResponse'
              examples:
                success:
                  summary: ログイン成功
                  value:
                    token: 1|abcdefghijklmnopqrstuvwxyz1234567890
                    user:
                      id: 1
                      name: John Doe
                      email: user@example.com
                      created_at: '2025-01-27T00:00:00Z'
                      updated_at: '2025-01-27T00:00:00Z'
        '401':
          $ref: '#/components/responses/UnauthorizedError'
        '422':
          $ref: '#/components/responses/ValidationError'
        '429':
          $ref: '#/components/responses/RateLimitError'

  /user/logout:
    post:
      summary: ユーザーログアウト
      description: 現在のアクセストークンを無効化します
      operationId: userLogout
      tags:
        - User Authentication
      security:
        - bearerAuth: []
      responses:
        '204':
          description: ログアウト成功（レスポンスボディなし）
        '401':
          $ref: '#/components/responses/UnauthorizedError'

  /user/profile:
    get:
      summary: ユーザープロフィール取得
      description: 認証されたユーザーのプロフィール情報を取得します
      operationId: getUserProfile
      tags:
        - User Authentication
      security:
        - bearerAuth: []
      responses:
        '200':
          description: プロフィール取得成功
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/UserProfile'
              examples:
                success:
                  summary: プロフィール取得成功
                  value:
                    id: 1
                    name: John Doe
                    email: user@example.com
                    created_at: '2025-01-27T00:00:00Z'
                    updated_at: '2025-01-27T00:00:00Z'
        '401':
          $ref: '#/components/responses/UnauthorizedError'

  /admin/login:
    post:
      summary: 管理者ログイン
      description: メールアドレスとパスワードで管理者認証を行い、アクセストークンを発行します
      operationId: adminLogin
      tags:
        - Admin Authentication
      requestBody:
        required: true
        content:
          application/json:
            schema:
              type: object
              required:
                - email
                - password
              properties:
                email:
                  type: string
                  format: email
                  description: 管理者のメールアドレス
                  example: admin@example.com
                password:
                  type: string
                  format: password
                  minLength: 8
                  description: 管理者のパスワード
                  example: password123
            examples:
              validAdmin:
                summary: 有効な管理者
                value:
                  email: admin@example.com
                  password: password123
      responses:
        '200':
          description: ログイン成功
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/AdminLoginResponse'
              examples:
                success:
                  summary: ログイン成功
                  value:
                    token: 2|zyxwvutsrqponmlkjihgfedcba0987654321
                    admin:
                      id: 1
                      name: Admin User
                      email: admin@example.com
                      role: super_admin
                      is_active: true
                      created_at: '2025-01-27T00:00:00Z'
                      updated_at: '2025-01-27T00:00:00Z'
        '401':
          $ref: '#/components/responses/UnauthorizedError'
        '403':
          $ref: '#/components/responses/AdminDisabledError'
        '422':
          $ref: '#/components/responses/ValidationError'
        '429':
          $ref: '#/components/responses/RateLimitError'

  /admin/logout:
    post:
      summary: 管理者ログアウト
      description: 現在のアクセストークンを無効化します
      operationId: adminLogout
      tags:
        - Admin Authentication
      security:
        - bearerAuth: []
      responses:
        '204':
          description: ログアウト成功（レスポンスボディなし）
        '401':
          $ref: '#/components/responses/UnauthorizedError'

  /admin/dashboard:
    get:
      summary: 管理者ダッシュボード取得
      description: 認証された管理者のダッシュボード情報を取得します
      operationId: getAdminDashboard
      tags:
        - Admin Authentication
      security:
        - bearerAuth: []
      responses:
        '200':
          description: ダッシュボード取得成功
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/AdminDashboard'
              examples:
                success:
                  summary: ダッシュボード取得成功
                  value:
                    admin:
                      id: 1
                      name: Admin User
                      email: admin@example.com
                      role: super_admin
                      is_active: true
                      created_at: '2025-01-27T00:00:00Z'
                      updated_at: '2025-01-27T00:00:00Z'
                    statistics:
                      total_users: 1000
                      active_users: 850
                      total_admins: 10
        '401':
          $ref: '#/components/responses/UnauthorizedError'
        '403':
          $ref: '#/components/responses/AdminDisabledError'

components:
  securitySchemes:
    bearerAuth:
      type: http
      scheme: bearer
      bearerFormat: JWT
      description: |
        Laravel Sanctum Bearer Token

        トークンは以下のエンドポイントから取得できます:
        - User: POST /api/v1/user/login
        - Admin: POST /api/v1/admin/login

  schemas:
    UserLoginResponse:
      type: object
      required:
        - token
        - user
      properties:
        token:
          type: string
          description: アクセストークン（24時間有効）
          example: 1|abcdefghijklmnopqrstuvwxyz1234567890
        user:
          $ref: '#/components/schemas/UserProfile'

    UserProfile:
      type: object
      required:
        - id
        - name
        - email
        - created_at
        - updated_at
      properties:
        id:
          type: integer
          format: int64
          description: ユーザーID
          example: 1
        name:
          type: string
          description: ユーザー名
          example: John Doe
        email:
          type: string
          format: email
          description: メールアドレス
          example: user@example.com
        created_at:
          type: string
          format: date-time
          description: 作成日時（ISO 8601形式）
          example: '2025-01-27T00:00:00Z'
        updated_at:
          type: string
          format: date-time
          description: 更新日時（ISO 8601形式）
          example: '2025-01-27T00:00:00Z'

    AdminLoginResponse:
      type: object
      required:
        - token
        - admin
      properties:
        token:
          type: string
          description: アクセストークン（24時間有効）
          example: 2|zyxwvutsrqponmlkjihgfedcba0987654321
        admin:
          $ref: '#/components/schemas/AdminProfile'

    AdminProfile:
      type: object
      required:
        - id
        - name
        - email
        - role
        - is_active
        - created_at
        - updated_at
      properties:
        id:
          type: integer
          format: int64
          description: 管理者ID
          example: 1
        name:
          type: string
          description: 管理者名
          example: Admin User
        email:
          type: string
          format: email
          description: メールアドレス
          example: admin@example.com
        role:
          type: string
          enum:
            - super_admin
            - admin
            - moderator
          description: 管理者ロール
          example: super_admin
        is_active:
          type: boolean
          description: アカウント有効状態
          example: true
        created_at:
          type: string
          format: date-time
          description: 作成日時（ISO 8601形式）
          example: '2025-01-27T00:00:00Z'
        updated_at:
          type: string
          format: date-time
          description: 更新日時（ISO 8601形式）
          example: '2025-01-27T00:00:00Z'

    AdminDashboard:
      type: object
      required:
        - admin
      properties:
        admin:
          $ref: '#/components/schemas/AdminProfile'
        statistics:
          type: object
          description: ダッシュボード統計情報
          properties:
            total_users:
              type: integer
              description: 総ユーザー数
              example: 1000
            active_users:
              type: integer
              description: アクティブユーザー数
              example: 850
            total_admins:
              type: integer
              description: 総管理者数
              example: 10

    ErrorResponse:
      type: object
      required:
        - code
        - message
      properties:
        code:
          type: string
          description: エラーコード（大文字スネークケース）
          example: AUTH.INVALID_CREDENTIALS
        message:
          type: string
          description: 人間が読める形式のエラーメッセージ
          example: メールアドレスまたはパスワードが正しくありません
        details:
          type: object
          description: エラーの詳細情報（オプション）
          additionalProperties: true
          example:
            field: email
            value: invalid@example.com

    ValidationErrorResponse:
      type: object
      required:
        - code
        - message
        - details
      properties:
        code:
          type: string
          enum:
            - VALIDATION.FAILED
          description: バリデーションエラーコード
          example: VALIDATION.FAILED
        message:
          type: string
          description: バリデーションエラーメッセージ
          example: 入力内容に誤りがあります
        details:
          type: object
          description: フィールドごとのエラーメッセージ
          additionalProperties:
            type: array
            items:
              type: string
          example:
            email:
              - メールアドレスの形式が正しくありません
            password:
              - パスワードは8文字以上である必要があります

  responses:
    UnauthorizedError:
      description: 認証エラー
      content:
        application/json:
          schema:
            $ref: '#/components/schemas/ErrorResponse'
          examples:
            invalidCredentials:
              summary: 認証情報が無効
              value:
                code: AUTH.INVALID_CREDENTIALS
                message: メールアドレスまたはパスワードが正しくありません
                details: {}
            tokenExpired:
              summary: トークンの有効期限切れ
              value:
                code: AUTH.TOKEN_EXPIRED
                message: トークンの有効期限が切れています
                details: {}
            unauthorized:
              summary: 認証されていない
              value:
                code: AUTH.UNAUTHORIZED
                message: 認証が必要です
                details: {}

    AdminDisabledError:
      description: 管理者アカウントが無効
      content:
        application/json:
          schema:
            $ref: '#/components/schemas/ErrorResponse'
          example:
            code: AUTH.ADMIN_DISABLED
            message: 管理者アカウントが無効化されています
            details: {}

    ValidationError:
      description: バリデーションエラー
      content:
        application/json:
          schema:
            $ref: '#/components/schemas/ValidationErrorResponse'
          example:
            code: VALIDATION.FAILED
            message: 入力内容に誤りがあります
            details:
              email:
                - メールアドレスの形式が正しくありません
              password:
                - パスワードは8文字以上である必要があります

    RateLimitError:
      description: レート制限エラー
      content:
        application/json:
          schema:
            $ref: '#/components/schemas/ErrorResponse'
          example:
            code: RATE_LIMIT.EXCEEDED
            message: リクエスト回数が上限を超えました。しばらくしてから再度お試しください
            details:
              retry_after: 60

security:
  - bearerAuth: []
```

---

## レート制限

### ログインエンドポイント

| エンドポイント | 制限 | 期間 |
|---------------|------|------|
| `POST /api/v1/user/login` | 5回 | 1分 |
| `POST /api/v1/admin/login` | 5回 | 1分 |

### 一般エンドポイント

| エンドポイント | 制限 | 期間 |
|---------------|------|------|
| その他すべてのエンドポイント | 60回 | 1分 |

レート制限を超過した場合、`429 Too Many Requests` ステータスコードが返されます。

---

## CURLサンプル

### User ログイン

```bash
curl -X POST http://localhost:13000/api/v1/user/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "password123"
  }'
```

### User プロフィール取得

```bash
curl -X GET http://localhost:13000/api/v1/user/profile \
  -H "Authorization: Bearer 1|abcdefghijklmnopqrstuvwxyz1234567890"
```

### User ログアウト

```bash
curl -X POST http://localhost:13000/api/v1/user/logout \
  -H "Authorization: Bearer 1|abcdefghijklmnopqrstuvwxyz1234567890"
```

### Admin ログイン

```bash
curl -X POST http://localhost:13000/api/v1/admin/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@example.com",
    "password": "password123"
  }'
```

### Admin ダッシュボード取得

```bash
curl -X GET http://localhost:13000/api/v1/admin/dashboard \
  -H "Authorization: Bearer 2|zyxwvutsrqponmlkjihgfedcba0987654321"
```

### Admin ログアウト

```bash
curl -X POST http://localhost:13000/api/v1/admin/logout \
  -H "Authorization: Bearer 2|zyxwvutsrqponmlkjihgfedcba0987654321"
```

---

## 関連ドキュメント

- [認証フロー図](./AUTHENTICATION_FLOW.md)
- [APIバージョニング戦略](./API_VERSIONING_STRATEGY.md)
- [セキュリティベストプラクティス](./SECURITY_BEST_PRACTICES.md)
- [トラブルシューティングガイド](./TROUBLESHOOTING.md)

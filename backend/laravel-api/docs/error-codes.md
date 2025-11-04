# エラーコード一覧

## 概要

本ドキュメントは、Laravel Next.js B2CアプリケーションテンプレートのAPIエラーコード一覧を定義します。全てのエラーレスポンスはRFC 7807 (Problem Details for HTTP APIs) 準拠形式で返却されます。

### エラーレスポンス形式

```json
{
  "type": "http://localhost/errors/{error-code}",
  "title": "エラータイトル",
  "status": 400,
  "detail": "詳細なエラーメッセージ",
  "error_code": "DOMAIN-SUBDOMAIN-CODE",
  "trace_id": "550e8400-e29b-41d4-a716-446655440000",
  "instance": "/api/v1/users",
  "timestamp": "2025-11-04T12:34:56Z"
}
```

### エラーコード命名規則

- **形式**: `{LAYER}-{SUBDOMAIN}-{CODE}`
- **LAYER**: `DOMAIN` (ビジネスロジック), `APP` (アプリケーション層), `INFRA` (インフラ層)
- **SUBDOMAIN**: エラーの発生領域（AUTH, VAL, BIZ, DB等）
- **CODE**: 4桁の数字（レイヤーごとに範囲を分離）
  - Domain層: 4000-4999
  - Application層: 4000-4999
  - Infrastructure層: 5000-5999

---

## 1. 認証エラー (AUTH-*)

### AUTH-4001: 認証情報が無効

**HTTPステータス**: 401 Unauthorized

**エラーコード**: `DOMAIN-AUTH-4001`

**メッセージ**:
- 日本語: メールアドレスまたはパスワードが正しくありません
- English: Invalid email or password

**発生条件**:
- ログイン時にメールアドレスまたはパスワードが誤っている
- 登録されていないユーザーでログインを試みた

**レスポンス例**:
```json
{
  "type": "http://localhost/errors/domain-auth-4001",
  "title": "Invalid Credentials",
  "status": 401,
  "detail": "メールアドレスまたはパスワードが正しくありません",
  "error_code": "DOMAIN-AUTH-4001",
  "trace_id": "550e8400-e29b-41d4-a716-446655440000",
  "instance": "/api/v1/login",
  "timestamp": "2025-11-04T12:34:56Z"
}
```

**対処方法**:
1. メールアドレスとパスワードを再確認
2. パスワードを忘れた場合はパスワードリセットを実行
3. 新規ユーザーの場合は登録手続きを実施

---

### AUTH-4002: 認証トークンの有効期限切れ

**HTTPステータス**: 401 Unauthorized

**エラーコード**: `DOMAIN-AUTH-4002`

**メッセージ**:
- 日本語: 認証トークンの有効期限が切れています
- English: Authentication token has expired

**発生条件**:
- トークンの有効期限（デフォルト60日）が切れている
- トークンが無効化されている

**レスポンス例**:
```json
{
  "type": "http://localhost/errors/domain-auth-4002",
  "title": "Token Expired",
  "status": 401,
  "detail": "認証トークンの有効期限が切れています",
  "error_code": "DOMAIN-AUTH-4002",
  "trace_id": "550e8400-e29b-41d4-a716-446655440001",
  "instance": "/api/v1/me",
  "timestamp": "2025-11-04T12:34:56Z"
}
```

**対処方法**:
1. 再ログインして新しいトークンを取得
2. トークン更新エンドポイント（`POST /api/v1/tokens/refresh`）を利用

---

### AUTH-4003: 認証トークンが無効

**HTTPステータス**: 401 Unauthorized

**エラーコード**: `DOMAIN-AUTH-4003`

**メッセージ**:
- 日本語: 認証トークンが無効です
- English: Invalid authentication token

**発生条件**:
- トークン形式が不正
- 改ざんされたトークン
- 存在しないトークン

**レスポンス例**:
```json
{
  "type": "http://localhost/errors/domain-auth-4003",
  "title": "Invalid Token",
  "status": 401,
  "detail": "認証トークンが無効です",
  "error_code": "DOMAIN-AUTH-4003",
  "trace_id": "550e8400-e29b-41d4-a716-446655440002",
  "instance": "/api/v1/me",
  "timestamp": "2025-11-04T12:34:56Z"
}
```

**対処方法**:
1. Authorizationヘッダーの形式を確認（`Bearer {token}`）
2. トークンの文字列が完全であることを確認
3. 再ログインして正しいトークンを取得

---

### AUTH-4004: 権限不足

**HTTPステータス**: 403 Forbidden

**エラーコード**: `DOMAIN-AUTH-4004`

**メッセージ**:
- 日本語: この操作を実行する権限がありません
- English: Insufficient permissions

**発生条件**:
- ユーザーの権限レベルが不足している
- 管理者専用エンドポイントに一般ユーザーがアクセス

**レスポンス例**:
```json
{
  "type": "http://localhost/errors/domain-auth-4004",
  "title": "Insufficient Permissions",
  "status": 403,
  "detail": "この操作を実行する権限がありません",
  "error_code": "DOMAIN-AUTH-4004",
  "trace_id": "550e8400-e29b-41d4-a716-446655440003",
  "instance": "/api/v1/admin/users",
  "timestamp": "2025-11-04T12:34:56Z"
}
```

**対処方法**:
1. ユーザーの権限レベルを確認
2. 管理者権限が必要な場合は管理者に連絡
3. APIドキュメントで必要な権限を確認

---

## 2. バリデーションエラー (VAL-*)

### VAL-4001: 入力内容エラー

**HTTPステータス**: 422 Unprocessable Entity

**エラーコード**: `DOMAIN-VAL-4001`

**メッセージ**:
- 日本語: 入力内容にエラーがあります
- English: Validation failed

**発生条件**:
- リクエストパラメータが不正
- 必須フィールドが未入力
- フィールドの形式が不正

**レスポンス例**:
```json
{
  "type": "http://localhost/errors/domain-val-4001",
  "title": "Validation Error",
  "status": 422,
  "detail": "入力内容にエラーがあります",
  "error_code": "DOMAIN-VAL-4001",
  "errors": {
    "email": [
      "メールアドレスの形式が正しくありません"
    ],
    "password": [
      "パスワードは8文字以上である必要があります"
    ],
    "age": [
      "年齢フィールドは必須です"
    ]
  },
  "trace_id": "550e8400-e29b-41d4-a716-446655440004",
  "instance": "/api/v1/users",
  "timestamp": "2025-11-04T12:34:56Z"
}
```

**対処方法**:
1. `errors`フィールドでフィールド別のエラーメッセージを確認
2. 各フィールドのバリデーションルールを確認
3. 正しい形式で再送信

---

### VAL-4002: メールアドレス形式エラー

**HTTPステータス**: 422 Unprocessable Entity

**エラーコード**: `DOMAIN-VAL-4002`

**メッセージ**:
- 日本語: メールアドレスの形式が正しくありません
- English: Invalid email format

**発生条件**:
- メールアドレス形式が不正（@が含まれない等）

**レスポンス例**:
```json
{
  "type": "http://localhost/errors/domain-val-4002",
  "title": "Invalid Email Format",
  "status": 422,
  "detail": "メールアドレスの形式が正しくありません",
  "error_code": "DOMAIN-VAL-4002",
  "errors": {
    "email": [
      "メールアドレスの形式が正しくありません"
    ]
  },
  "trace_id": "550e8400-e29b-41d4-a716-446655440005",
  "instance": "/api/v1/users",
  "timestamp": "2025-11-04T12:34:56Z"
}
```

**対処方法**:
1. メールアドレスに`@`と`.`が含まれていることを確認
2. 有効なドメイン名であることを確認
3. スペースや不正な文字が含まれていないことを確認

---

## 3. ビジネスロジックエラー (BIZ-*)

### BIZ-4001: リソース未検出

**HTTPステータス**: 404 Not Found

**エラーコード**: `APP-BIZ-4001`

**メッセージ**:
- 日本語: 指定されたリソースが見つかりません
- English: Resource not found

**発生条件**:
- 存在しないリソースIDを指定
- 削除済みリソースへのアクセス

**レスポンス例**:
```json
{
  "type": "http://localhost/errors/app-biz-4001",
  "title": "Resource Not Found",
  "status": 404,
  "detail": "指定されたリソースが見つかりません",
  "error_code": "APP-BIZ-4001",
  "trace_id": "550e8400-e29b-41d4-a716-446655440006",
  "instance": "/api/v1/users/999",
  "timestamp": "2025-11-04T12:34:56Z"
}
```

**対処方法**:
1. リソースIDを確認
2. リソースが削除されていないか確認
3. リソース一覧エンドポイントで存在を確認

---

### BIZ-4002: リソース競合

**HTTPステータス**: 409 Conflict

**エラーコード**: `DOMAIN-BIZ-4002`

**メッセージ**:
- 日本語: すでに同じリソースが存在します
- English: Resource already exists

**発生条件**:
- 重複するメールアドレスで登録
- ユニーク制約違反

**レスポンス例**:
```json
{
  "type": "http://localhost/errors/domain-biz-4002",
  "title": "Resource Conflict",
  "status": 409,
  "detail": "すでに同じリソースが存在します",
  "error_code": "DOMAIN-BIZ-4002",
  "trace_id": "550e8400-e29b-41d4-a716-446655440007",
  "instance": "/api/v1/users",
  "timestamp": "2025-11-04T12:34:56Z"
}
```

**対処方法**:
1. 既存リソースを確認
2. 別の値（メールアドレス等）を使用
3. 既存リソースの更新を検討

---

## 4. インフラストラクチャエラー (INFRA-*)

### INFRA-5001: データベース接続エラー

**HTTPステータス**: 503 Service Unavailable

**エラーコード**: `INFRA-DB-5001`

**メッセージ**:
- 日本語: データベースに接続できません
- English: Database connection failed

**発生条件**:
- データベースサーバーがダウン
- ネットワーク接続エラー
- データベース接続プールが枯渇

**レスポンス例**:
```json
{
  "type": "http://localhost/errors/infra-db-5001",
  "title": "Database Connection Error",
  "status": 503,
  "detail": "データベースに接続できません",
  "error_code": "INFRA-DB-5001",
  "trace_id": "550e8400-e29b-41d4-a716-446655440008",
  "instance": "/api/v1/users",
  "timestamp": "2025-11-04T12:34:56Z"
}
```

**対処方法**:
1. データベースサーバーの状態を確認
2. ネットワーク接続を確認
3. しばらく時間をおいて再試行
4. システム管理者に連絡

---

### INFRA-5002: 外部API通信エラー

**HTTPステータス**: 502 Bad Gateway

**エラーコード**: `INFRA-API-5002`

**メッセージ**:
- 日本語: 外部サービスとの通信に失敗しました
- English: External API request failed

**発生条件**:
- 外部APIサーバーがダウン
- 外部APIのタイムアウト
- 外部APIからのエラーレスポンス

**レスポンス例**:
```json
{
  "type": "http://localhost/errors/infra-api-5002",
  "title": "External API Error",
  "status": 502,
  "detail": "外部サービスとの通信に失敗しました",
  "error_code": "INFRA-API-5002",
  "trace_id": "550e8400-e29b-41d4-a716-446655440009",
  "instance": "/api/v1/payments",
  "timestamp": "2025-11-04T12:34:56Z"
}
```

**対処方法**:
1. しばらく時間をおいて再試行
2. 外部サービスのステータスページを確認
3. システム管理者に連絡

---

### INFRA-5003: リクエストタイムアウト

**HTTPステータス**: 504 Gateway Timeout

**エラーコード**: `INFRA-TIMEOUT-5003`

**メッセージ**:
- 日本語: 処理に時間がかかりすぎました
- English: Request timeout

**発生条件**:
- 処理時間が制限時間を超過
- 重いクエリの実行
- 外部APIのタイムアウト

**レスポンス例**:
```json
{
  "type": "http://localhost/errors/infra-timeout-5003",
  "title": "Request Timeout",
  "status": 504,
  "detail": "処理に時間がかかりすぎました",
  "error_code": "INFRA-TIMEOUT-5003",
  "trace_id": "550e8400-e29b-41d4-a716-446655440010",
  "instance": "/api/v1/reports/generate",
  "timestamp": "2025-11-04T12:34:56Z"
}
```

**対処方法**:
1. しばらく時間をおいて再試行
2. リクエストパラメータを調整して処理量を削減
3. システム管理者に連絡

---

## 5. ネットワークエラー (NETWORK-*)

### NETWORK-0001: ネットワーク接続エラー

**HTTPステータス**: N/A (フロントエンド側エラー)

**エラーコード**: `NETWORK-CONNECTION-0001`

**メッセージ**:
- 日本語: ネットワーク接続エラーが発生しました
- English: Network connection error

**発生条件**:
- インターネット接続が切断
- APIサーバーへの接続失敗
- DNS解決エラー

**対処方法**:
1. インターネット接続を確認
2. ネットワーク設定を確認
3. しばらく時間をおいて再試行

---

### NETWORK-0002: リクエストタイムアウト

**HTTPステータス**: N/A (フロントエンド側エラー)

**エラーコード**: `NETWORK-TIMEOUT-0002`

**メッセージ**:
- 日本語: リクエストがタイムアウトしました
- English: Request timed out

**発生条件**:
- クライアント側タイムアウト設定を超過
- ネットワーク遅延

**対処方法**:
1. ネットワーク接続速度を確認
2. しばらく時間をおいて再試行
3. タイムアウト設定を確認

---

## 付録

### エラーコード一覧表

| エラーコード | カテゴリー | HTTPステータス | 翻訳キー |
|-------------|----------|---------------|---------|
| DOMAIN-AUTH-4001 | 認証 | 401 | errors.auth.invalid_credentials |
| DOMAIN-AUTH-4002 | 認証 | 401 | errors.auth.token_expired |
| DOMAIN-AUTH-4003 | 認証 | 401 | errors.auth.token_invalid |
| DOMAIN-AUTH-4004 | 認証 | 403 | errors.auth.insufficient_permissions |
| DOMAIN-VAL-4001 | バリデーション | 422 | errors.validation.invalid_input |
| DOMAIN-VAL-4002 | バリデーション | 422 | errors.validation.invalid_email |
| APP-BIZ-4001 | ビジネスロジック | 404 | errors.business.resource_not_found |
| DOMAIN-BIZ-4002 | ビジネスロジック | 409 | errors.business.resource_conflict |
| INFRA-DB-5001 | インフラ | 503 | errors.infrastructure.database_unavailable |
| INFRA-API-5002 | インフラ | 502 | errors.infrastructure.external_api_error |
| INFRA-TIMEOUT-5003 | インフラ | 504 | errors.infrastructure.request_timeout |
| NETWORK-CONNECTION-0001 | ネットワーク | N/A | - |
| NETWORK-TIMEOUT-0002 | ネットワーク | N/A | - |

### Request ID追跡方法

全てのエラーレスポンスには`trace_id`フィールドが含まれます。この値を使用してログからエラーの詳細を追跡できます。

**手順**:
1. エラーレスポンスの`trace_id`をコピー
2. サポートに問い合わせる際に`trace_id`を提供
3. ログ検索: `grep {trace_id} /path/to/logs/laravel.log`

### 環境別エラーメッセージ

**開発環境** (`APP_ENV=local`):
- スタックトレースを含む詳細エラーメッセージ
- デバッグ情報の表示

**本番環境** (`APP_ENV=production`):
- 内部エラーの詳細をマスク
- 汎用エラーメッセージを返却
- センシティブ情報の非表示

### 多言語対応

エラーメッセージは`Accept-Language`ヘッダーに基づいて自動的に切り替わります。

**サポート言語**:
- 日本語 (`ja`)
- 英語 (`en`)

**リクエスト例**:
```http
GET /api/v1/users/999 HTTP/1.1
Host: localhost:13000
Accept-Language: ja
Authorization: Bearer {token}
```

**レスポンス**: 日本語エラーメッセージ

---

## 参考資料

- [RFC 7807 - Problem Details for HTTP APIs](https://datatracker.ietf.org/doc/html/rfc7807)
- [Laravel Exception Handling](https://laravel.com/docs/12.x/errors)
- [Next.js Error Handling](https://nextjs.org/docs/app/building-your-application/routing/error-handling)
- [プロジェクトトラブルシューティングガイド](./error-handling-troubleshooting.md)

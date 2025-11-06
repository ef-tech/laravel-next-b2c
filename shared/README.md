# Shared Resources

バックエンド（Laravel）とフロントエンド（Next.js）で共有するリソースを格納するディレクトリ

## エラーコード定義

### 概要

RFC 7807 (Problem Details for HTTP APIs) 準拠のエラーコード体系定義

- **定義ファイル**: `error-codes.json`
- **スキーマ**: `error-codes.schema.json`
- **バリデーション**: `npm run validate:error-codes`

### エラーコード形式

```
DOMAIN-SUBDOMAIN-CODE
```

- **DOMAIN**: カテゴリー（AUTH, VAL, BIZ, INFRA）
- **SUBDOMAIN**: サブカテゴリー（LOGIN, TOKEN, INPUT等）
- **CODE**: 3桁の数字（001-999）

### カテゴリー

| カテゴリー | 説明 | HTTPステータス範囲 |
|-----------|------|------------------|
| AUTH | 認証・認可エラー | 401, 403 |
| VAL | バリデーションエラー | 422 |
| BIZ | ビジネスロジックエラー | 404, 409 |
| INFRA | インフラ・外部システムエラー | 502, 503, 504 |

### エラーコード定義構造

```json
{
  "AUTH-LOGIN-001": {
    "code": "AUTH-LOGIN-001",
    "http_status": 401,
    "type": "https://example.com/errors/auth/invalid-credentials",
    "default_message": "Invalid email or password",
    "translation_key": "errors.auth.invalid_credentials",
    "category": "AUTH",
    "description": "ログイン認証失敗（メールアドレスまたはパスワードが正しくない）",
    "resolution": "メールアドレスとパスワードを確認してください"
  }
}
```

### 必須フィールド

- `code`: エラーコード（DOMAIN-SUBDOMAIN-CODE形式）
- `http_status`: HTTPステータスコード（400-599）
- `type`: RFC 7807 type URI
- `default_message`: デフォルトエラーメッセージ（英語）
- `translation_key`: 翻訳キー（errors.category.subcategory形式）
- `category`: エラーカテゴリー（AUTH/VAL/BIZ/INFRA）

### オプションフィールド

- `description`: エラーの詳細説明
- `resolution`: 解決方法のヒント

### バリデーション

エラーコード定義を追加・変更した場合は、必ずバリデーションを実行してください：

```bash
npm run validate:error-codes
```

### 使用例

#### バックエンド（Laravel）

```php
use Ddd\Shared\Exceptions\DomainException;

throw new DomainException(
    message: 'Invalid email or password',
    errorCode: 'AUTH-LOGIN-001',
    statusCode: 401
);
```

#### フロントエンド（Next.js）

```typescript
import { ApiError } from '@/lib/errors/api-error';

if (error instanceof ApiError) {
  console.log(error.errorCode); // "AUTH-LOGIN-001"
  console.log(error.status);    // 401
  console.log(error.title);     // "Invalid email or password"
}
```

## 型定義生成

TypeScript型定義は自動生成スクリプトで生成されます（実装予定）：

```bash
npm run generate:error-types
```

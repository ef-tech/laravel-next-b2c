# CORS設定ガイド

## 概要

Laravel Next.js B2Cアプリケーションテンプレートでは、Laravel API（ポート13000）とNext.jsフロントエンドアプリ（User App: 13001、Admin App: 13002）間のクロスオリジンAPIリクエストを適切に制御するため、環境変数ドリブンなCORS設定を実装しています。

## 目次

- [環境別設定例](#環境別設定例)
- [Docker/SSR環境での注意点](#dockerssr環境での注意点)
- [Next.js SSR/CSRでのAPI呼び出し考慮事項](#nextjs-ssrcsrでのapi呼び出し考慮事項)
- [トラブルシューティング](#トラブルシューティング)
- [セキュリティベストプラクティス](#セキュリティベストプラクティス)

## 環境別設定例

### 開発環境（Local/Docker）

`.env`ファイルに以下を設定：

```env
CORS_ALLOWED_ORIGINS=http://localhost:13001,http://localhost:13002,http://127.0.0.1:13001,http://127.0.0.1:13002,http://host.docker.internal:13001,http://host.docker.internal:13002
CORS_ALLOWED_METHODS=GET,POST,PUT,DELETE,PATCH,OPTIONS
CORS_ALLOWED_HEADERS=Content-Type,Authorization,X-Requested-With
CORS_MAX_AGE=600
CORS_SUPPORTS_CREDENTIALS=false
```

**説明**:
- **localhost, 127.0.0.1, host.docker.internal**: 全バリエーションをサポートし、ローカル開発とDocker環境の両方で動作
- **CORS_MAX_AGE=600**: 10分間のPreflightキャッシュ（開発環境では短めに設定）

### ステージング環境

`.env`ファイルに以下を設定：

```env
CORS_ALLOWED_ORIGINS=https://stg-user.example.com,https://stg-admin.example.com
CORS_ALLOWED_METHODS=GET,POST,PUT,DELETE,PATCH,OPTIONS
CORS_ALLOWED_HEADERS=Content-Type,Authorization,X-Requested-With
CORS_MAX_AGE=3600
CORS_SUPPORTS_CREDENTIALS=false
```

**説明**:
- **HTTPSオリジンのみ**: セキュリティ強化のため、ステージング環境でもHTTPSを使用
- **CORS_MAX_AGE=3600**: 1時間のPreflightキャッシュ

### 本番環境

`.env`ファイルに以下を設定：

```env
CORS_ALLOWED_ORIGINS=https://user.example.com,https://admin.example.com
CORS_ALLOWED_METHODS=GET,POST,PUT,DELETE,PATCH,OPTIONS
CORS_ALLOWED_HEADERS=Content-Type,Authorization,X-Requested-With
CORS_MAX_AGE=86400
CORS_SUPPORTS_CREDENTIALS=false
```

**説明**:
- **HTTPSオリジンのみ**: 本番環境では必須（HTTPオリジンは自動検出され警告ログが出力される）
- **CORS_MAX_AGE=86400**: 24時間のPreflightキャッシュ（パフォーマンス最適化）
- **ワイルドカード`*`禁止**: セキュリティリスクのため本番環境では非推奨（警告ログが出力される）

## Docker/SSR環境での注意点

### Docker Compose環境

Docker Compose環境では、`host.docker.internal`を使用してホストマシンにアクセスできます。

**docker-compose.yml設定例**:

```yaml
services:
  laravel-api:
    image: laravel-next-b2c/app
    ports:
      - "13000:13000"
    extra_hosts:
      - "host.docker.internal:host-gateway"
    environment:
      CORS_ALLOWED_ORIGINS: "http://localhost:13001,http://localhost:13002,http://host.docker.internal:13001,http://host.docker.internal:13002"
```

**重要**: `extra_hosts`設定により、コンテナ内から`host.docker.internal`でホストマシンにアクセス可能になります。

### Next.js SSR環境

Next.js SSRでは、サーバーサイドレンダリング時にAPIリクエストが発生します。この場合、`localhost`や`127.0.0.1`ではなく、Docker内部ネットワーク経由でAPIにアクセスする必要があります。

**Next.js設定例**:

```typescript
// next.config.ts
const config: NextConfig = {
  env: {
    API_URL: process.env.NODE_ENV === 'production'
      ? 'https://api.example.com'
      : 'http://host.docker.internal:13000', // Docker環境
  },
};
```

## Next.js SSR/CSRでのAPI呼び出し考慮事項

### CSR (Client-Side Rendering)

クライアントサイドレンダリングでは、ブラウザから直接APIにアクセスします。

**例**:

```typescript
// app/actions.ts (Client Component)
'use client';

export async function fetchData() {
  const response = await fetch('http://localhost:13000/api/data');
  return response.json();
}
```

**CORS適用**: ブラウザがPreflightリクエスト（OPTIONS）を送信し、CORSヘッダーを検証します。

### SSR (Server-Side Rendering)

サーバーサイドレンダリングでは、Next.jsサーバーからAPIにアクセスします。

**例**:

```typescript
// app/page.tsx (Server Component)
export default async function Page() {
  const response = await fetch('http://laravel-api:13000/api/data');
  const data = await response.json();
  return <div>{data.message}</div>;
}
```

**CORS適用**: Next.jsサーバーからのリクエストには、CORSは適用されません（同一サーバー間通信）。ただし、ブラウザからの初回リクエストにはCORSが適用されます。

## トラブルシューティング

### CORSエラー診断

#### エラー例1: "No 'Access-Control-Allow-Origin' header is present"

**原因**: 許可されていないオリジンからのリクエスト

**解決策**:
1. `.env`ファイルの`CORS_ALLOWED_ORIGINS`にオリジンを追加
2. 設定キャッシュをクリア: `php artisan config:clear`
3. アプリケーションを再起動

**確認コマンド**:

```bash
# 現在のCORS設定を確認
php artisan config:show cors

# curlでPreflightリクエストをテスト
curl -X OPTIONS http://localhost:13000/api/health \
  -H "Origin: http://localhost:13001" \
  -H "Access-Control-Request-Method: GET" \
  -v
```

#### エラー例2: "CORS policy: The 'Access-Control-Allow-Credentials' header in the response is 'true'"

**原因**: `supports_credentials`が`true`に設定されているが、オリジンがワイルドカード`*`

**解決策**:
1. `CORS_SUPPORTS_CREDENTIALS=false`に設定（ステートレスAPI設計）
2. または、明示的なオリジンリストを使用

#### エラー例3: "Failed to load resource: net::ERR_CONNECTION_REFUSED"

**原因**: APIサーバーが起動していない、またはポートが間違っている

**解決策**:
1. Laravel APIサーバーが起動していることを確認: `docker compose ps`
2. ポート設定を確認: `APP_PORT=13000`
3. ファイアウォール設定を確認

### 設定キャッシュクリア

Laravel設定キャッシュをクリアする手順：

```bash
# 設定キャッシュをクリア
php artisan config:clear

# ルートキャッシュをクリア
php artisan route:clear

# 全キャッシュをクリア
php artisan optimize:clear
```

### 警告ログの確認

CORS設定バリデーションの警告ログを確認：

```bash
# ログをリアルタイムで確認
php artisan pail

# 特定の警告を検索
grep "Invalid CORS origin format" storage/logs/laravel.log
grep "Non-HTTPS origin in production" storage/logs/laravel.log
```

## セキュリティベストプラクティス

### 1. 本番環境ではHTTPS必須

本番環境では必ずHTTPSオリジンのみを許可してください。HTTPオリジンは中間者攻撃のリスクがあります。

**良い例**:
```env
CORS_ALLOWED_ORIGINS=https://user.example.com,https://admin.example.com
```

**悪い例** (本番環境):
```env
CORS_ALLOWED_ORIGINS=http://user.example.com,http://admin.example.com
```

### 2. ワイルドカード`*`の使用を避ける

本番環境でワイルドカード`*`を使用すると、任意のオリジンからのアクセスが可能になり、セキュリティリスクが高まります。

**良い例**:
```env
CORS_ALLOWED_ORIGINS=https://user.example.com,https://admin.example.com
```

**悪い例** (本番環境):
```env
CORS_ALLOWED_ORIGINS=*
```

### 3. 最小権限の原則

`CORS_ALLOWED_METHODS`と`CORS_ALLOWED_HEADERS`は、必要最小限に設定してください。

**推奨設定**:
```env
CORS_ALLOWED_METHODS=GET,POST,PUT,DELETE,PATCH,OPTIONS
CORS_ALLOWED_HEADERS=Content-Type,Authorization,X-Requested-With
```

### 4. Preflightキャッシュの最適化

`CORS_MAX_AGE`を環境に応じて設定することで、パフォーマンスを最適化できます。

| 環境 | CORS_MAX_AGE | 理由 |
|------|--------------|------|
| 開発環境 | 600秒（10分） | 設定変更を即座に反映 |
| ステージング | 3600秒（1時間） | テスト効率とパフォーマンスのバランス |
| 本番環境 | 86400秒（24時間） | パフォーマンス最適化 |

### 5. ステートレスAPI設計

`CORS_SUPPORTS_CREDENTIALS=false`を設定し、ステートレスAPI設計を維持してください。トークンベース認証（Laravel Sanctum）を使用する場合、資格情報は不要です。

```env
CORS_SUPPORTS_CREDENTIALS=false
```

### 6. 設定バリデーションの活用

Laravel起動時に自動的にCORS設定が検証され、警告ログが出力されます。定期的にログを確認し、設定ミスを早期に発見してください。

**確認コマンド**:
```bash
php artisan pail --filter="CORS"
```

## 参考リソース

- [MDN Web Docs: CORS](https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS)
- [Laravel CORS公式ドキュメント](https://laravel.com/docs/12.x/cors)
- [OWASP CORS Security Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Cross-Origin_Resource_Sharing_Cheat_Sheet.html)
- [Laravel Sanctum認証ガイド](./backend/laravel-api/docs/sanctum-authentication-guide.md)

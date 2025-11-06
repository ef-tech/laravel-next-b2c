# エラーハンドリングトラブルシューティングガイド

## 概要

本ドキュメントは、Laravel Next.js B2Cアプリケーションテンプレートのエラーハンドリングに関するよくある問題と解決策を提供します。

---

## 1. Request ID追跡方法

### 問題: エラーの詳細をログから追跡したい

**症状**:
- エラーが発生したが、ログからどのリクエストか特定できない
- ユーザーから報告されたエラーの詳細を確認したい

**解決策**:

#### ステップ1: エラーレスポンスからRequest IDを取得

全てのエラーレスポンスには`trace_id`フィールドが含まれます:

```json
{
  "trace_id": "550e8400-e29b-41d4-a716-446655440000",
  "...": "..."
}
```

#### ステップ2: ログからRequest IDを検索

```bash
# Laravel APIログを検索
cd /Users/okumura/Work/src/ef-tech/template/laravel-next-b2c/backend/laravel-api
grep "550e8400-e29b-41d4-a716-446655440000" storage/logs/laravel.log

# Docker環境の場合
docker compose logs laravel-api | grep "550e8400-e29b-41d4-a716-446655440000"
```

#### ステップ3: ログエントリを分析

ログには以下の情報が含まれます:
- リクエストURL
- HTTPメソッド
- ユーザー情報（認証済みの場合）
- スタックトレース（開発環境）
- 実行時間

**例**:
```
[2025-11-04 12:34:56] local.ERROR: Database connection failed
{"trace_id":"550e8400-e29b-41d4-a716-446655440000","url":"/api/v1/users","method":"GET","user_id":123}
```

#### ステップ4: サポートに問い合わせる際の情報提供

ユーザーからエラー報告を受けた場合:
1. エラー画面に表示される`Request ID`をコピー
2. サポートチケットに`Request ID`を記載
3. サポート担当者がログから詳細を追跡

---

## 2. 多言語メッセージ設定ミス

### 問題: 翻訳キーがそのまま表示される

**症状**:
```json
{
  "detail": "errors.auth.invalid_credentials"
}
```

**原因**:
- 翻訳ファイル（`lang/ja/errors.php`, `lang/en/errors.php`）に翻訳キーが定義されていない
- Laravel Translatorが翻訳キーを解決できない

**解決策**:

#### ステップ1: 翻訳ファイルを確認

```bash
cd backend/laravel-api
cat lang/ja/errors.php
cat lang/en/errors.php
```

#### ステップ2: 翻訳キーを追加

`lang/ja/errors.php`:
```php
return [
    'auth' => [
        'invalid_credentials' => 'メールアドレスまたはパスワードが正しくありません',
        // 新規追加
        'your_new_key' => '新しいエラーメッセージ',
    ],
];
```

`lang/en/errors.php`:
```php
return [
    'auth' => [
        'invalid_credentials' => 'Invalid email or password',
        // 新規追加
        'your_new_key' => 'Your new error message',
    ],
];
```

#### ステップ3: キャッシュをクリア

```bash
php artisan config:clear
php artisan cache:clear
```

#### ステップ4: 翻訳キーの使用方法を確認

Exceptionクラスでの使用例:
```php
throw new class(trans('errors.auth.invalid_credentials')) extends DomainException {
    public function getStatusCode(): int { return 401; }
    public function getErrorCode(): string { return 'DOMAIN-AUTH-4001'; }
    protected function getTitle(): string { return 'Invalid Credentials'; }
};
```

---

### 問題: 言語が切り替わらない

**症状**:
- `Accept-Language: ja`を送信しても英語メッセージが返される
- 常に同じ言語のメッセージが表示される

**原因**:
- `SetLocaleFromAcceptLanguage` Middlewareが動作していない
- Middlewareスタックに登録されていない

**解決策**:

#### ステップ1: Middleware登録を確認

`config/middleware.php`:
```php
'api' => [
    \App\Http\Middleware\SetRequestId::class,
    \App\Http\Middleware\SetLocaleFromAcceptLanguage::class, // この行があることを確認
    // ...
],
```

#### ステップ2: Middlewareクラスの存在を確認

```bash
ls -la app/Http/Middleware/SetLocaleFromAcceptLanguage.php
```

存在しない場合は実装が必要です。

#### ステップ3: リクエストヘッダーを確認

```bash
curl -H "Accept-Language: ja" http://localhost:13000/api/v1/users/999
```

#### ステップ4: ログでLocaleを確認

`app/Http/Middleware/SetLocaleFromAcceptLanguage.php`にログ出力を追加:
```php
public function handle($request, Closure $next)
{
    $locale = $request->header('Accept-Language', 'en');
    app()->setLocale($locale);
    \Log::info('Locale set to: ' . $locale);
    return $next($request);
}
```

---

## 3. Error Boundary動作不良

### 問題: Next.js Error Boundaryが表示されない

**症状**:
- エラーが発生してもError Boundaryがキャッチしない
- 白い画面のままになる
- ブラウザコンソールにエラーが表示される

**原因**:
- Error Boundaryは**Rendering中のエラー**のみキャッチする
- イベントハンドラー内のエラーはキャッチしない
- Server Componentsのエラーは`error.tsx`でキャッチ
- Client Componentsのエラーは手動でthrowする必要がある

**解決策**:

#### ケース1: Client Componentのイベントハンドラー内エラー

❌ **キャッチされない例**:
```tsx
"use client";

export default function MyComponent() {
  const handleClick = async () => {
    // この中のエラーはError Boundaryでキャッチされない
    const response = await fetch('/api/users');
    if (!response.ok) {
      throw new Error('Failed'); // Error Boundaryでキャッチされない
    }
  };

  return <button onClick={handleClick}>Click</button>;
}
```

✅ **修正例**:
```tsx
"use client";

import { useState } from 'react';

export default function MyComponent() {
  const [error, setError] = useState<Error | null>(null);

  // エラーをstateに保存してrenderingでthrow
  if (error) {
    throw error; // Error Boundaryでキャッチされる
  }

  const handleClick = async () => {
    try {
      const response = await fetch('/api/users');
      if (!response.ok) {
        throw new Error('Failed');
      }
    } catch (err) {
      setError(err as Error); // stateに保存
    }
  };

  return <button onClick={handleClick}>Click</button>;
}
```

#### ケース2: Server Componentのエラー

Server Componentsのエラーは自動的に`error.tsx`でキャッチされます:

```tsx
// app/users/page.tsx (Server Component)
export default async function UsersPage() {
  const response = await fetch('http://localhost:13000/api/v1/users');
  if (!response.ok) {
    throw new Error('Failed to fetch users'); // 自動的にerror.tsxでキャッチ
  }
  const users = await response.json();
  return <div>{/* ... */}</div>;
}
```

#### ケース3: error.tsxの実装確認

`app/error.tsx`:
```tsx
'use client';

export default function Error({
  error,
  reset,
}: {
  error: Error & { digest?: string };
  reset: () => void;
}) {
  return (
    <div>
      <h2>エラーが発生しました</h2>
      <p>{error.message}</p>
      <button onClick={() => reset()}>再試行</button>
    </div>
  );
}
```

#### ケース4: global-error.tsxの実装確認

`app/global-error.tsx`（Root Layoutのエラー用）:
```tsx
'use client';

export default function GlobalError({
  error,
  reset,
}: {
  error: Error & { digest?: string };
  reset: () => void;
}) {
  return (
    <html>
      <body>
        <h2>アプリケーションエラー</h2>
        <p>{error.message}</p>
        <button onClick={() => reset()}>再試行</button>
      </body>
    </html>
  );
}
```

---

### 問題: Error Boundaryがリセットされない

**症状**:
- `reset()`ボタンをクリックしてもエラー状態が解消されない
- 同じエラー画面が表示され続ける

**原因**:
- `reset()`はコンポーネントを再マウントするが、同じエラーが再発生する
- エラーの根本原因が解決されていない

**解決策**:

#### ステップ1: エラーの根本原因を解決

```tsx
'use client';

import { useEffect } from 'react';

export default function Error({
  error,
  reset,
}: {
  error: Error & { digest?: string };
  reset: () => void;
}) {
  useEffect(() => {
    // エラーをログに記録
    console.error('Error logged:', error);
  }, [error]);

  return (
    <div>
      <h2>エラーが発生しました</h2>
      <p>{error.message}</p>
      {/* Request IDを表示 */}
      {error.digest && (
        <p className="text-sm text-gray-500">
          Request ID: {error.digest}
        </p>
      )}
      <button onClick={() => reset()}>再試行</button>
      {/* ホームに戻るボタンも追加 */}
      <button onClick={() => window.location.href = '/'}>
        ホームに戻る
      </button>
    </div>
  );
}
```

#### ステップ2: エラーハンドリングロジックを改善

```tsx
"use client";

import { useState } from 'react';
import { ApiClient } from '@/lib/api-client';

export default function MyComponent() {
  const [error, setError] = useState<Error | null>(null);
  const [retryCount, setRetryCount] = useState(0);

  if (error) {
    throw error;
  }

  const handleClick = async () => {
    try {
      setError(null); // エラーをクリア
      const client = new ApiClient();
      const data = await client.request('/api/v1/users');
      // 成功処理
    } catch (err) {
      if (retryCount < 3) {
        // 3回まで自動リトライ
        setRetryCount(retryCount + 1);
        setTimeout(() => handleClick(), 1000 * retryCount);
      } else {
        setError(err as Error);
      }
    }
  };

  return <button onClick={handleClick}>Click</button>;
}
```

---

## 4. RFC 7807レスポンス形式の問題

### 問題: Content-Typeが`application/json`になる

**症状**:
```http
HTTP/1.1 400 Bad Request
Content-Type: application/json

{
  "type": "...",
  "title": "...",
  ...
}
```

**原因**:
- Exception HandlerでContent-Typeヘッダーを設定していない
- LaravelデフォルトのJSONレスポンスを使用している

**解決策**:

`app/Exceptions/Handler.php`:
```php
public function render($request, Throwable $e)
{
    if ($e instanceof DomainException ||
        $e instanceof ApplicationException ||
        $e instanceof InfrastructureException) {

        return response()->json(
            $e->toProblemDetails(),
            $e->getStatusCode(),
            ['Content-Type' => 'application/problem+json'] // ヘッダー設定
        );
    }

    return parent::render($request, $e);
}
```

---

### 問題: `trace_id`フィールドが空

**症状**:
```json
{
  "trace_id": null,
  ...
}
```

**原因**:
- `SetRequestId` Middlewareが動作していない
- リクエストヘッダーに`X-Request-ID`が設定されていない

**解決策**:

#### ステップ1: Middleware登録を確認

`config/middleware.php`:
```php
'api' => [
    \App\Http\Middleware\SetRequestId::class, // 先頭に配置
    // ...
],
```

#### ステップ2: Middlewareの実装を確認

`app/Http/Middleware/SetRequestId.php`:
```php
public function handle($request, Closure $next)
{
    $requestId = $request->header('X-Request-ID') ?? (string) Str::uuid();
    $request->headers->set('X-Request-ID', $requestId);

    // ログコンテキストに追加
    \Log::withContext(['trace_id' => $requestId]);

    return $next($request);
}
```

#### ステップ3: Exception内での取得方法を確認

`ddd/Shared/Exceptions/DomainException.php`:
```php
public function toProblemDetails(): array
{
    return [
        // ...
        'trace_id' => request()->header('X-Request-ID'), // ヘッダーから取得
        // ...
    ];
}
```

---

## 5. フロントエンドAPIクライアントの問題

### 問題: NetworkErrorとApiErrorの区別ができない

**症状**:
- ネットワークエラーもAPIエラーも同じエラーメッセージ
- エラーの種類によって異なる処理ができない

**解決策**:

#### ステップ1: エラークラスを定義

`lib/errors/api-error.ts`:
```typescript
export class ApiError extends Error {
  constructor(
    message: string,
    public statusCode: number,
    public errorCode?: string,
    public trace_id?: string
  ) {
    super(message);
    this.name = 'ApiError';
  }
}
```

`lib/errors/network-error.ts`:
```typescript
export class NetworkError extends Error {
  constructor(message: string, public originalError: Error) {
    super(message);
    this.name = 'NetworkError';
  }
}
```

#### ステップ2: APIクライアントでエラーを分類

`lib/api-client.ts`:
```typescript
export class ApiClient {
  async request<T>(url: string, options?: RequestInit): Promise<T> {
    try {
      const response = await fetch(url, {
        ...options,
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/problem+json',
          ...options?.headers,
        },
      });

      if (!response.ok) {
        const problemDetails = await response.json();
        throw new ApiError(
          problemDetails.detail || 'API Error',
          response.status,
          problemDetails.error_code,
          problemDetails.trace_id
        );
      }

      return await response.json();
    } catch (error) {
      if (error instanceof ApiError) {
        throw error; // APIエラーはそのままスロー
      }
      // ネットワークエラー
      throw new NetworkError('Network connection failed', error as Error);
    }
  }
}
```

#### ステップ3: エラーハンドリングで区別

```typescript
try {
  const data = await apiClient.request('/api/v1/users');
} catch (error) {
  if (error instanceof ApiError) {
    console.error('API Error:', error.statusCode, error.errorCode);
    // ユーザーにAPIエラーメッセージを表示
    showErrorMessage(error.message);
  } else if (error instanceof NetworkError) {
    console.error('Network Error:', error);
    // ユーザーにネットワークエラーメッセージを表示
    showErrorMessage('ネットワーク接続を確認してください');
  }
}
```

---

## 6. 環境別エラーメッセージ制御

### 問題: 本番環境で内部エラーの詳細が表示される

**症状**:
- 本番環境でスタックトレースが表示される
- センシティブ情報（DB接続文字列等）が漏洩する

**原因**:
- `APP_ENV`が`production`に設定されていない
- Exception Handlerで環境別処理が実装されていない

**解決策**:

#### ステップ1: 環境変数を確認

`.env`:
```env
APP_ENV=production
APP_DEBUG=false
```

#### ステップ2: Exception Handlerで環境別処理

`app/Exceptions/Handler.php`:
```php
public function render($request, Throwable $e)
{
    if ($e instanceof DomainException ||
        $e instanceof ApplicationException ||
        $e instanceof InfrastructureException) {

        $problemDetails = $e->toProblemDetails();

        // 本番環境では内部エラーの詳細をマスク
        if (app()->environment('production') && $e->getStatusCode() >= 500) {
            $problemDetails['detail'] = 'An internal server error occurred. Please contact support.';
            unset($problemDetails['trace']); // スタックトレース削除
        }

        return response()->json(
            $problemDetails,
            $e->getStatusCode(),
            ['Content-Type' => 'application/problem+json']
        );
    }

    return parent::render($request, $e);
}
```

---

## 7. デバッグ方法

### Laravel APIのデバッグ

#### ステップ1: ログレベルを設定

`.env`:
```env
LOG_CHANNEL=stack
LOG_LEVEL=debug
```

#### ステップ2: デバッグログを出力

```php
use Illuminate\Support\Facades\Log;

Log::debug('Exception occurred', [
    'exception' => get_class($e),
    'message' => $e->getMessage(),
    'trace_id' => request()->header('X-Request-ID'),
]);
```

#### ステップ3: ログを確認

```bash
tail -f storage/logs/laravel.log
```

### Next.jsのデバッグ

#### ステップ1: ブラウザコンソールを確認

```javascript
console.error('Error occurred:', error);
console.log('Error details:', {
  name: error.name,
  message: error.message,
  stack: error.stack,
});
```

#### ステップ2: Next.jsサーバーログを確認

```bash
# ターミナルでNext.jsを起動している場合、ログが表示される
npm run dev
```

---

## 8. よくある質問

### Q1: エラーコードの命名規則は？

**A**: `{LAYER}-{SUBDOMAIN}-{CODE}`形式です。
- LAYER: DOMAIN, APP, INFRA
- SUBDOMAIN: AUTH, VAL, BIZ, DB等
- CODE: 4桁数字（レイヤーごとに範囲を分離）

例: `DOMAIN-AUTH-4001`, `INFRA-DB-5001`

### Q2: 新しいエラーコードを追加するには？

**A**: 以下の手順:
1. `lang/ja/errors.php`, `lang/en/errors.php`に翻訳キーを追加
2. Exceptionクラスで`getErrorCode()`メソッドを実装
3. `docs/error-codes.md`ドキュメントに追加
4. テストを作成

### Q3: Error Boundaryでキャッチできないエラーは？

**A**: 以下のエラーはError Boundaryでキャッチできません:
- イベントハンドラー内のエラー（`onClick`, `onChange`等）
- `setTimeout`, `setInterval`内のエラー
- Server-side rendering（SSR）中のエラー
- Error Boundary自身で発生したエラー

これらのエラーは`try-catch`で処理し、stateに保存してrenderingでthrowする必要があります。

### Q4: Request IDはどこで確認できる？

**A**: 以下の場所で確認できます:
- エラーレスポンスの`trace_id`フィールド
- レスポンスヘッダーの`X-Request-ID`
- Error Boundary UIの`Request ID`表示

### Q5: 多言語対応はどのように動作する？

**A**: `Accept-Language`ヘッダーに基づいて自動的に切り替わります:
- `Accept-Language: ja` → 日本語メッセージ
- `Accept-Language: en` → 英語メッセージ
- ヘッダー未指定 → 英語（デフォルト）

---

## 参考資料

- [エラーコード一覧](./error-codes.md)
- [RFC 7807 - Problem Details for HTTP APIs](https://datatracker.ietf.org/doc/html/rfc7807)
- [Laravel Exception Handling](https://laravel.com/docs/12.x/errors)
- [Next.js Error Handling](https://nextjs.org/docs/app/building-your-application/routing/error-handling)

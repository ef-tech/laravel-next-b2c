# ミドルウェア実装ガイド

このドキュメントでは、Laravel API専用バックエンドで実装されている12種類のカスタムミドルウェアの詳細と実装パターンを説明します。

## 目次

1. [ミドルウェア一覧](#ミドルウェア一覧)
2. [ミドルウェアグループ](#ミドルウェアグループ)
3. [グローバルミドルウェア](#グローバルミドルウェア)
4. [各ミドルウェアの詳細](#各ミドルウェアの詳細)
5. [DDD/クリーンアーキテクチャ統合パターン](#dddクリーンアーキテクチャ統合パターン)
6. [実装パターン](#実装パターン)

## ミドルウェア一覧

### グローバルミドルウェア（全リクエストに適用）

| ミドルウェア | 責務 | 実行順序 |
|------------|------|---------|
| SetRequestId | リクエストID生成・伝播 | 1 |
| CorrelationId | Correlation ID管理（W3C Trace Context対応） | 2 |
| ForceJsonResponse | JSON入出力強制（Accept/Content-Type検証） | 3 |
| SecurityHeaders | セキュリティヘッダー設定（OWASP準拠） | 4 |

### グループミドルウェア（特定エンドポイントに適用）

| ミドルウェア | 責務 | 適用グループ |
|------------|------|-------------|
| RequestLogging | 構造化ログ出力（機密データマスキング） | api |
| PerformanceMonitoring | パフォーマンスメトリクス収集 | api |
| DynamicRateLimit | 動的レート制限（Redis） | api, guest, internal, webhook |
| SanctumTokenVerification | Sanctumトークン詳細検証 | auth, internal |
| AuthorizationCheck | 権限ベースアクセス制御 | internal |
| AuditTrail | 監査証跡記録 | auth, internal |
| IdempotencyKey | Idempotency保証（重複リクエスト防止） | webhook |
| CacheHeaders | HTTPキャッシュヘッダー設定 | readonly |
| ETag | ETag生成・条件付きGET処理 | readonly |

## ミドルウェアグループ

### 1. apiグループ（基底グループ）

**目的**: 全APIエンドポイント共通の基本処理

**構成**:
```php
'api' => [
    \Illuminate\Routing\Middleware\SubstituteBindings::class,
    \App\Http\Middleware\RequestLogging::class,
    \App\Http\Middleware\PerformanceMonitoring::class,
    \App\Http\Middleware\DynamicRateLimit::class.':api',
]
```

**適用条件**: 全てのAPIエンドポイント

### 2. authグループ（認証必須）

**目的**: 認証が必要なエンドポイント

**構成**:
```php
'auth' => [
    'api', // apiグループを継承
    'auth:sanctum',
    \App\Http\Middleware\SanctumTokenVerification::class,
    \App\Http\Middleware\AuditTrail::class,
]
```

**適用条件**: ユーザー認証が必要なエンドポイント

### 3. guestグループ（公開API）

**目的**: 認証不要の公開エンドポイント

**構成**:
```php
'guest' => [
    'api', // apiグループを継承
    \App\Http\Middleware\DynamicRateLimit::class.':public',
]
```

**適用条件**: ログイン、会員登録など認証不要エンドポイント

### 4. internalグループ（内部/管理用）

**目的**: 管理者専用エンドポイント

**構成**:
```php
'internal' => [
    'api', // apiグループを継承
    'auth:sanctum',
    \App\Http\Middleware\SanctumTokenVerification::class,
    \App\Http\Middleware\AuthorizationCheck::class.':admin',
    \App\Http\Middleware\DynamicRateLimit::class.':strict',
    \App\Http\Middleware\AuditTrail::class,
]
```

**適用条件**: 管理者ロールが必要なエンドポイント

### 5. webhookグループ（外部コールバック）

**目的**: Webhook受信エンドポイント

**構成**:
```php
'webhook' => [
    'api', // apiグループを継承
    \App\Http\Middleware\IdempotencyKey::class,
    \App\Http\Middleware\DynamicRateLimit::class.':webhook',
]
```

**適用条件**: 外部サービスからのWebhook通知

### 6. readonlyグループ（読み取り専用）

**目的**: キャッシュ可能な読み取り専用エンドポイント

**構成**:
```php
'readonly' => [
    'api', // apiグループを継承
    \App\Http\Middleware\CacheHeaders::class,
    \App\Http\Middleware\ETag::class,
]
```

**適用条件**: GETリクエストでキャッシュ可能なエンドポイント

## グローバルミドルウェア

グローバルミドルウェアは全てのリクエストに適用されます。実行順序が重要です。

### 実行順序

```
1. TrustProxies（Laravel組み込み）
2. ValidatePostSize（Laravel組み込み）
3. PreventRequestsDuringMaintenance（Laravel組み込み）
4. HandleCors（Laravel組み込み）
5. SetRequestId（カスタム）
6. CorrelationId（カスタム）
7. ForceJsonResponse（カスタム）
8. SecurityHeaders（カスタム）
9. ルート固有ミドルウェアグループ
```

## 各ミドルウェアの詳細

### 1. SetRequestId

**責務**: リクエストID生成・伝播

**実装パターン**:
```php
final class SetRequestId
{
    public function handle(Request $request, Closure $next): Response
    {
        // カスタムリクエストIDまたは新規生成
        $requestId = $request->header('X-Request-ID') ?? (string) Uuid::uuid4();
        $request->headers->set('X-Request-ID', $requestId);

        // ログコンテキストに追加
        Log::withContext(['request_id' => $requestId]);

        $response = $next($request);
        $response->headers->set('X-Request-ID', $requestId);

        return $response;
    }
}
```

**使用例**:
- トレーシング
- ログ集約
- デバッグ

### 2. CorrelationId

**責務**: Correlation ID管理（W3C Trace Context対応）

**実装パターン**:
```php
final class CorrelationId
{
    public function handle(Request $request, Closure $next): Response
    {
        // W3C Trace Contextの解析
        $traceparent = $request->header('traceparent');
        if ($traceparent) {
            $traceContext = $this->parseTraceparent($traceparent);
            $correlationId = $traceContext['trace_id'];
        } else {
            $correlationId = $request->header('X-Correlation-Id') ?? (string) Uuid::uuid4();
        }

        $request->headers->set('X-Correlation-Id', $correlationId);
        Log::withContext(['correlation_id' => $correlationId]);

        $response = $next($request);
        $response->headers->set('X-Correlation-ID', $correlationId);

        return $response;
    }
}
```

**使用例**:
- 分散トレーシング
- マイクロサービス間の追跡
- APM（Application Performance Monitoring）統合

### 3. ForceJsonResponse

**責務**: JSON入出力強制

**実装パターン**:
```php
final class ForceJsonResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        // Acceptヘッダー検証
        if (!$this->acceptsJson($request->header('Accept', ''))) {
            return $this->jsonError('Not Acceptable', 'This endpoint only supports application/json', 406);
        }

        // POST/PUT/PATCHのContent-Type検証
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH'])) {
            if (!$this->isJsonContentType($request->header('Content-Type', ''))) {
                return $this->jsonError('Unsupported Media Type', 'Request body must be application/json', 415);
            }
        }

        return $next($request);
    }
}
```

**使用例**:
- API仕様の一貫性保証
- 不正なAcceptヘッダーの拒否

### 4. SecurityHeaders

**責務**: セキュリティヘッダー設定（OWASP準拠）

**実装パターン**:
```php
final class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // 基本セキュリティヘッダー
        $response = $this->addBasicHeaders($response);

        // CSPヘッダー（設定により有効化）
        $response = $this->addCspHeaders($response, $request);

        // HSTSヘッダー（HTTPS環境のみ）
        $response = $this->addHstsHeader($response, $request);

        return $response;
    }
}
```

**使用例**:
- XSS攻撃防止
- クリックジャッキング防止
- MIME sniffing防止

### 5. RequestLogging

**責務**: 構造化ログ出力（機密データマスキング）

**実装パターン**:
```php
final class RequestLogging
{
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        // 非同期ログ記録（terminateメソッド）
        $logData = [
            'method' => $request->method(),
            'path' => $request->path(),
            'status' => $response->getStatusCode(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'request_body' => $this->maskSensitiveData($request->all()),
        ];

        Log::info('HTTP Request', $logData);
    }
}
```

**使用例**:
- アクセスログ記録
- 監査証跡
- デバッグ

### 6. PerformanceMonitoring

**責務**: パフォーマンスメトリクス収集

**実装パターン**:
```php
final class PerformanceMonitoring
{
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage();

        $response = $next($request);

        // メトリクス計算
        $duration = (microtime(true) - $startTime) * 1000; // ミリ秒
        $memoryUsage = memory_get_usage() - $startMemory;

        // ヘッダーに追加
        $response->headers->set('X-Response-Time', (string) round($duration, 2));

        return $response;
    }
}
```

**使用例**:
- レスポンス時間監視
- メモリ使用量監視
- パフォーマンスボトルネック特定

### 7. DynamicRateLimit

**責務**: 動的レート制限（Redis）

**実装パターン**:
```php
final class DynamicRateLimit
{
    public function handle(Request $request, Closure $next, string $endpointType = 'api'): Response
    {
        try {
            $config = config("ratelimit.endpoints.{$endpointType}");
            $maxAttempts = $config['requests'];
            $decayMinutes = $config['per_minute'];

            // レート制限チェック
            $identifier = $this->resolveIdentifier($request, $config['by']);
            $key = "rate_limit:{$endpointType}:{$identifier}";

            $cache = Cache::store('redis');
            $attempts = (int) $cache->get($key, 0);

            if ($attempts >= $maxAttempts) {
                return $this->buildRateLimitResponse($maxAttempts, 0, now()->addMinutes($decayMinutes)->getTimestamp());
            }

            $cache->put($key, $attempts + 1, $decayMinutes * 60);

            $response = $next($request);
            return $this->addRateLimitHeaders($response, $maxAttempts, $maxAttempts - $attempts - 1, now()->addMinutes($decayMinutes)->getTimestamp());
        } catch (\Exception $e) {
            // グレースフルデグラデーション
            Log::warning('Rate limit check failed, skipping', ['error' => $e->getMessage()]);
            return $next($request);
        }
    }
}
```

**使用例**:
- API乱用防止
- DDoS攻撃緩和
- 公平なリソース配分

### 8. SanctumTokenVerification

**責務**: Sanctumトークン詳細検証

**実装パターン**:
```php
final class SanctumTokenVerification
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user) {
            return new Response('Unauthorized', 401);
        }

        $token = $request->user()->currentAccessToken();
        if (!$token) {
            return new Response('Invalid Token', 401);
        }

        // トークン有効期限チェック
        if ($token->expires_at && now()->isAfter($token->expires_at)) {
            return new Response('Token Expired', 401);
        }

        // ログ記録
        Log::withContext(['user_id' => $user->id, 'token_id' => $token->id]);

        return $next($request);
    }
}
```

**使用例**:
- トークン有効期限検証
- トークン詳細ログ記録

### 9. AuthorizationCheck

**責務**: 権限ベースアクセス制御

**実装パターン**（DDD統合）:
```php
final class AuthorizationCheck
{
    public function __construct(
        private readonly AuthorizationServiceInterface $authorizationService
    ) {}

    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();
        if (!$user) {
            return new Response('Unauthorized', 401);
        }

        // Application層のAuthorizationServiceポートを経由
        $authorized = $this->authorizationService->authorize($user, $permission);

        if (!$authorized) {
            Log::warning('Authorization failed', ['user_id' => $user->id, 'permission' => $permission]);
            return new Response('Forbidden', 403);
        }

        return $next($request);
    }
}
```

**使用例**:
- ロールベースアクセス制御（RBAC）
- 権限チェック

### 10. AuditTrail

**責務**: 監査証跡記録

**実装パターン**（DDD統合）:
```php
final class AuditTrail
{
    public function __construct(
        private readonly AuditRepositoryInterface $auditRepository
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        $user = $request->user();
        if (!$user) {
            return;
        }

        // Application層のAuditRepositoryポートを経由
        $this->auditRepository->log([
            'user_id' => $user->id,
            'action' => $request->method().' '.$request->path(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'request_body' => $this->maskSensitiveData($request->all()),
            'response_status' => $response->getStatusCode(),
        ]);
    }
}
```

**使用例**:
- セキュリティ監査
- コンプライアンス対応
- ユーザー行動追跡

### 11. IdempotencyKey

**責務**: Idempotency保証（重複リクエスト防止）

**実装パターン**:
```php
final class IdempotencyKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user || $request->method() === 'GET') {
            return $next($request);
        }

        $idempotencyKey = $request->header('Idempotency-Key');
        if (!$idempotencyKey) {
            return $next($request);
        }

        // Redisキャッシュチェック
        $redisKey = "idempotency:{$idempotencyKey}:{$user->id}";
        $cached = Redis::connection()->get($redisKey);

        if ($cached) {
            // キャッシュ済みレスポンスを返す
            return response($cached['body'], $cached['status']);
        }

        // 新規リクエスト処理
        $response = $next($request);

        // レスポンスをキャッシュ（24時間）
        Redis::connection()->setex($redisKey, 86400, json_encode([
            'status' => $response->getStatusCode(),
            'body' => $response->getContent(),
        ]));

        return $response;
    }
}
```

**使用例**:
- 重複決済防止
- Webhook重複処理防止
- ネットワーク不安定時の安全性確保

### 12. CacheHeaders & ETag

**責務**: HTTPキャッシュ最適化

**実装パターン**:
```php
// CacheHeaders
final class CacheHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->method() !== 'GET' || !config('cache_headers.enabled')) {
            return $response;
        }

        // 環境別キャッシュ設定
        if (config('app.env') === 'production') {
            $maxAge = config('cache_headers.max_age', 3600);
            $response->headers->set('Cache-Control', "public, max-age={$maxAge}");
        } else {
            $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
        }

        return $response;
    }
}

// ETag
final class ETag
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->method() !== 'GET') {
            return $response;
        }

        $content = $response->getContent();
        if (strlen($content) > 1048576) { // 1MB以上はスキップ
            return $response;
        }

        $etag = '"'.hash('sha256', $content).'"';

        // If-None-Matchチェック
        if ($request->header('If-None-Match') === $etag) {
            $response->setStatusCode(304);
            $response->setContent('');
        }

        $response->headers->set('ETag', $etag);
        return $response;
    }
}
```

**使用例**:
- 帯域幅削減
- レスポンス速度向上
- サーバー負荷軽減

## DDD/クリーンアーキテクチャ統合パターン

### Application層ポート経由のアクセス

ミドルウェアからドメインロジックにアクセスする場合、Application層のポート（インターフェース）を経由します。

**パターン1: AuthorizationCheck**

```php
// Application層ポート
namespace App\Application\Ports;

interface AuthorizationServiceInterface
{
    public function authorize(User $user, string $permission): bool;
}

// Infrastructure層実装
namespace App\Infrastructure\Services;

class AuthorizationService implements AuthorizationServiceInterface
{
    public function authorize(User $user, string $permission): bool
    {
        // ドメインロジック呼び出し
        return $user->hasPermission($permission);
    }
}

// ミドルウェア（HTTP層）
namespace App\Http\Middleware;

class AuthorizationCheck
{
    public function __construct(
        private readonly AuthorizationServiceInterface $authorizationService
    ) {}

    public function handle(Request $request, Closure $next, string $permission): Response
    {
        // Application層ポートを経由してドメインロジックにアクセス
        $authorized = $this->authorizationService->authorize($request->user(), $permission);
        // ...
    }
}
```

**パターン2: AuditTrail**

```php
// Application層ポート
namespace App\Application\Ports;

interface AuditRepositoryInterface
{
    public function log(array $data): void;
}

// Infrastructure層実装
namespace App\Infrastructure\Repositories;

class AuditRepository implements AuditRepositoryInterface
{
    public function log(array $data): void
    {
        // データベース永続化
        DB::table('audit_logs')->insert($data);
    }
}

// ミドルウェア（HTTP層）
namespace App\Http\Middleware;

class AuditTrail
{
    public function __construct(
        private readonly AuditRepositoryInterface $auditRepository
    ) {}

    public function terminate(Request $request, Response $response): void
    {
        // Application層ポートを経由して監査ログを記録
        $this->auditRepository->log([/* ... */]);
    }
}
```

### 依存性注入（DI）の設定

```php
// app/Providers/AppServiceProvider.php
public function register(): void
{
    $this->app->bind(
        \App\Application\Ports\AuthorizationServiceInterface::class,
        \App\Infrastructure\Services\AuthorizationService::class
    );

    $this->app->bind(
        \App\Application\Ports\AuditRepositoryInterface::class,
        \App\Infrastructure\Repositories\AuditRepository::class
    );
}
```

## 実装パターン

### パターン1: 基本的なミドルウェア

```php
final class ExampleMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // リクエスト前処理

        $response = $next($request);

        // レスポンス後処理

        return $response;
    }
}
```

### パターン2: terminateメソッド利用（非同期処理）

```php
final class ExampleMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        // レスポンス送信後の非同期処理
        // ログ記録、メトリクス送信など
    }
}
```

### パターン3: 外部サービス依存（グレースフルデグラデーション）

```php
final class ExampleMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            // 外部サービス（Redis等）を使用する処理
            $result = $this->externalService->process();
        } catch (\Exception $e) {
            // エラー時はスキップ（グレースフルデグラデーション）
            Log::warning('External service failed, skipping', ['error' => $e->getMessage()]);
            return $next($request);
        }

        return $next($request);
    }
}
```

### パターン4: DDD統合パターン

```php
final class ExampleMiddleware
{
    public function __construct(
        private readonly SomeServiceInterface $service
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        // Application層ポートを経由してドメインロジックにアクセス
        $result = $this->service->execute($request->user());

        if (!$result) {
            return new Response('Forbidden', 403);
        }

        return $next($request);
    }
}
```

## まとめ

このミドルウェア実装により、以下を達成しています：

1. **API専用最適化**: セッション除去、JSON強制
2. **セキュリティ強化**: OWASP準拠、権限制御、監査証跡
3. **運用性向上**: ログ記録、メトリクス収集、トレーシング
4. **信頼性向上**: レート制限、Idempotency、グレースフルデグラデーション
5. **パフォーマンス最適化**: HTTPキャッシュ、ETag
6. **DDD準拠**: Application層ポート経由のアクセス

各ミドルウェアは単一責任原則に従い、テスト可能で保守性の高い設計になっています。

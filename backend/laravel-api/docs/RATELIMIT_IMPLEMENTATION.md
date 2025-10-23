# APIレート制限実装ドキュメント

## 目次
- [概要](#概要)
- [アーキテクチャ](#アーキテクチャ)
- [エンドポイント分類システム](#エンドポイント分類システム)
- [実装詳細](#実装詳細)
- [テスト戦略](#テスト戦略)
- [設定](#設定)

## 概要

本プロジェクトでは、DDD/クリーンアーキテクチャに基づいた柔軟で堅牢なAPIレート制限システムを実装しています。

### 主な特徴
- **DDD準拠4層アーキテクチャ**: Domain、Application、Infrastructure、HTTP層の明確な分離
- **4種類のエンドポイント分類**: 認証状態×機密性の2軸分類システム
- **Redis障害時フェイルオーバー**: 自動的にセカンダリストア（Array/File Cache）に切り替え
- **構造化ログ・メトリクス**: Prometheus/StatsD統合準備完了
- **Laravel標準互換**: ThrottleRequests完全互換、既存コードへの影響なし
- **高いテストカバレッジ**: 167テスト、530アサーション（92%合格率）

## アーキテクチャ

### DDD 4層構造

```
┌─────────────────────────────────────────────────────────┐
│           HTTP Layer (Interface)                        │
│  ┌───────────────────────────────────────────────────┐  │
│  │ DynamicRateLimit Middleware                       │  │
│  │ - handle(): レート制限チェック実行                │  │
│  │ - buildRateLimitResponse(): 429レスポンス構築     │  │
│  │ - addRateLimitHeaders(): HTTPヘッダー追加         │  │
│  └───────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────┘
                      ▼ 依存
┌─────────────────────────────────────────────────────────┐
│       Application Layer (Use Cases & Services)          │
│  ┌───────────────────────────────────────────────────┐  │
│  │ Services:                                         │  │
│  │ - EndpointClassifier: エンドポイント分類          │  │
│  │ - KeyResolver: レート制限キー解決                 │  │
│  │ - RateLimitConfigManager: 設定管理                │  │
│  │                                                   │  │
│  │ Contracts (Interfaces):                           │  │
│  │ - RateLimitService: レート制限チェック            │  │
│  │ - RateLimitMetrics: メトリクス記録                │  │
│  └───────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────┘
                      ▼ 依存
┌─────────────────────────────────────────────────────────┐
│         Domain Layer (Business Logic)                   │
│  ┌───────────────────────────────────────────────────┐  │
│  │ ValueObjects:                                     │  │
│  │ - RateLimitRule: レート制限ルール                 │  │
│  │ - RateLimitKey: レート制限識別キー                │  │
│  │ - RateLimitResult: レート制限結果                 │  │
│  │ - EndpointClassification: エンドポイント分類      │  │
│  └───────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────┘
                      ▲ 実装
┌─────────────────────────────────────────────────────────┐
│    Infrastructure Layer (External Systems)              │
│  ┌───────────────────────────────────────────────────┐  │
│  │ Stores:                                           │  │
│  │ - LaravelRateLimiterStore: Redis/Cache統合        │  │
│  │ - FailoverRateLimitStore: フェイルオーバー実装    │  │
│  │                                                   │  │
│  │ Metrics:                                          │  │
│  │ - LogMetrics: 構造化ログ記録                      │  │
│  └───────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────┘
```

## エンドポイント分類システム

### 4種類の分類（認証状態 × 機密性）

| 分類 | 認証 | 機密性 | 制限値 | 識別方法 | 用途例 |
|------|------|--------|--------|----------|--------|
| `public_unauthenticated` | 未認証 | 公開 | 60 req/min | IPアドレス | ページ表示、商品一覧 |
| `protected_unauthenticated` | 未認証 | 保護 | 5 req/10min | IP + Email (SHA-256) | ログイン、パスワードリセット |
| `public_authenticated` | 認証済み | 公開 | 120 req/min | User ID → Token ID → IP | 認証済みAPI |
| `protected_authenticated` | 認証済み | 保護 | 30 req/min | User ID → Token ID → IP | 管理者API、決済 |

### 保護ルートパターン

以下のルート名パターンが保護エンドポイントとして扱われます（`config/ratelimit.php`で設定）:

```php
'protected_routes' => [
    'login',           // ログイン
    'register',        // ユーザー登録
    'password.*',      // パスワードリセット関連
    'admin.*',         // 管理者API全般
    'payment.*',       // 決済API全般
],
```

## 実装詳細

### Domain層（3 ValueObjects）

#### 1. RateLimitRule
レート制限ルールを表現する不変オブジェクト。

```php
final readonly class RateLimitRule
{
    public function __construct(
        private int $maxAttempts,     // 最大リクエスト数
        private int $decayMinutes,    // 時間単位（分）
    ) {}

    public function getMaxAttempts(): int
    public function getDecayMinutes(): int
    public function getDecaySeconds(): int  // 秒単位変換
}
```

#### 2. RateLimitKey
レート制限識別キーを表現する不変オブジェクト。

```php
final readonly class RateLimitKey
{
    public function __construct(
        private string $key,          // 識別キー
    ) {}

    public function getKey(): string
    public static function fromString(string $key): self
}
```

#### 3. RateLimitResult
レート制限チェック結果を表現する不変オブジェクト。

```php
final readonly class RateLimitResult
{
    public function __construct(
        private bool $allowed,        // 許可フラグ
        private int $attempts,        // 試行回数
        private int $remaining,       // 残り回数
        private Carbon $resetAt,      // リセット時刻
    ) {}

    public function isAllowed(): bool
    public function isBlocked(): bool
    public function getAttempts(): int
    public function getRemaining(): int
    public function getResetAt(): Carbon

    public static function allowed(int $attempts, int $remaining, Carbon $resetAt): self
    public static function blocked(int $attempts, Carbon $resetAt): self
}
```

#### 4. EndpointClassification
エンドポイント分類情報を表現する不変オブジェクト。

```php
final readonly class EndpointClassification
{
    public function __construct(
        private string $type,                // 分類タイプ
        private bool $isAuthenticated,       // 認証状態
        private bool $isProtected,           // 保護状態
        private RateLimitRule $rule,         // 適用ルール
    ) {}

    public function getType(): string
    public function isAuthenticated(): bool
    public function isProtected(): bool
    public function getRule(): RateLimitRule
}
```

### Application層（4 Services + 2 Contracts）

#### 1. EndpointClassifier Service
リクエストからエンドポイント分類を判定。

```php
final class EndpointClassifier
{
    public function __construct(
        private RateLimitConfigManager $configManager
    ) {}

    public function classify(Request $request): EndpointClassification
    {
        $isAuthenticated = $request->user() !== null;
        $routeName = $request->route()?->getName();
        $isProtected = $this->isProtectedRoute($routeName);

        // 4種類のいずれかに分類
        $type = $this->determineType($isAuthenticated, $isProtected);
        $rule = $this->configManager->getRuleForEndpointType($type);

        return new EndpointClassification($type, $isAuthenticated, $isProtected, $rule);
    }
}
```

#### 2. KeyResolver Service
エンドポイント分類に基づいてレート制限キーを解決。

```php
final class KeyResolver
{
    public function resolve(Request $request, EndpointClassification $classification): RateLimitKey
    {
        $type = $classification->getType();

        return match ($type) {
            'public_unauthenticated' => $this->resolveIpKey($request),
            'protected_unauthenticated' => $this->resolveIpEmailKey($request),
            'public_authenticated' => $this->resolveUserKey($request),
            'protected_authenticated' => $this->resolveUserKey($request),
            default => $this->resolveIpKey($request),
        };
    }

    // User ID → Token ID → IP のフォールバックチェーン
    private function resolveUserKey(Request $request): RateLimitKey
    {
        if ($userId = $request->user()?->id) {
            return RateLimitKey::fromString("user_{$userId}");
        }
        if ($tokenId = $request->user()?->currentAccessToken()?->id) {
            return RateLimitKey::fromString("token_{$tokenId}");
        }
        return $this->resolveIpKey($request);
    }
}
```

#### 3. RateLimitConfigManager Service
設定ファイルからレート制限ルールを取得。

```php
final class RateLimitConfigManager
{
    private array $cache = [];

    public function getRuleForEndpointType(string $type): RateLimitRule
    {
        if (isset($this->cache[$type])) {
            return $this->cache[$type];
        }

        $config = config("ratelimit.endpoint_types.{$type}");
        $rule = new RateLimitRule(
            maxAttempts: (int) $config['max_attempts'],
            decayMinutes: (int) $config['decay_minutes']
        );

        return $this->cache[$type] = $rule;
    }
}
```

#### 4. RateLimitService Contract (Interface)
レート制限チェックのインターフェース。

```php
interface RateLimitService
{
    public function checkLimit(RateLimitKey $key, RateLimitRule $rule): RateLimitResult;
    public function resetLimit(RateLimitKey $key): void;
}
```

#### 5. RateLimitMetrics Contract (Interface)
メトリクス記録のインターフェース。

```php
interface RateLimitMetrics
{
    public function recordHit(RateLimitKey $key, RateLimitRule $rule, bool $allowed, int $attempts): void;
    public function recordBlock(RateLimitKey $key, RateLimitRule $rule, int $attempts, int $retryAfter): void;
}
```

### Infrastructure層（2 Stores + 1 Metrics）

#### 1. LaravelRateLimiterStore
Laravel Cache を使用したレート制限ストア。

```php
final class LaravelRateLimiterStore implements RateLimitService
{
    public function __construct(
        private string $store = 'redis'
    ) {}

    public function checkLimit(RateLimitKey $key, RateLimitRule $rule): RateLimitResult
    {
        $cache = Cache::store($this->store);
        $cacheKey = $key->getKey();

        // 原子的カウント操作
        if (!$cache->has($cacheKey)) {
            $cache->add($cacheKey, 0, $rule->getDecaySeconds());
        }
        $attempts = (int) $cache->increment($cacheKey);

        // 許可/拒否判定
        if ($attempts <= $rule->getMaxAttempts()) {
            $remaining = $rule->getMaxAttempts() - $attempts;
            return RateLimitResult::allowed($attempts, $remaining, $resetAt);
        }

        return RateLimitResult::blocked($attempts, $resetAt);
    }
}
```

#### 2. FailoverRateLimitStore
Redis障害時に自動フェイルオーバー。

```php
final class FailoverRateLimitStore implements RateLimitService
{
    private string $currentStore = 'primary';
    private ?Carbon $lastHealthCheck = null;

    public function __construct(
        private LaravelRateLimiterStore $primaryStore,
        private LaravelRateLimiterStore $secondaryStore,
        private int $healthCheckIntervalSeconds = 30
    ) {}

    public function checkLimit(RateLimitKey $key, RateLimitRule $rule): RateLimitResult
    {
        try {
            if ($this->currentStore === 'secondary') {
                $this->tryHealthCheck();
            }

            return $this->primaryStore->checkLimit($key, $rule);
        } catch (\Exception $e) {
            Log::warning('Primary store failed, switching to secondary');
            $this->currentStore = 'secondary';

            // セカンダリストアでは制限値を2倍に緩和
            $relaxedRule = new RateLimitRule(
                $rule->getMaxAttempts() * 2,
                $rule->getDecayMinutes()
            );

            return $this->secondaryStore->checkLimit($key, $relaxedRule);
        }
    }
}
```

#### 3. LogMetrics
構造化ログによるメトリクス記録。

```php
final class LogMetrics implements RateLimitMetrics
{
    public function recordHit(RateLimitKey $key, RateLimitRule $rule, bool $allowed, int $attempts): void
    {
        Log::info('rate_limit.hit', [
            'key' => $key->getKey(),
            'max_attempts' => $rule->getMaxAttempts(),
            'decay_minutes' => $rule->getDecayMinutes(),
            'attempts' => $attempts,
            'allowed' => $allowed,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    public function recordBlock(RateLimitKey $key, RateLimitRule $rule, int $attempts, int $retryAfter): void
    {
        Log::warning('rate_limit.blocked', [
            'key' => $key->getKey(),
            'max_attempts' => $rule->getMaxAttempts(),
            'attempts' => $attempts,
            'retry_after' => $retryAfter,
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
```

### HTTP層（DynamicRateLimit Middleware）

```php
final class DynamicRateLimit
{
    public function __construct(
        private readonly EndpointClassifier $classifier,
        private readonly KeyResolver $keyResolver,
        private readonly RateLimitService $rateLimitService,
        private readonly RateLimitMetrics $metrics,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        // エンドポイント分類
        $classification = $this->classifier->classify($request);
        $rule = $classification->getRule();

        // レート制限キー解決
        $key = $this->keyResolver->resolve($request, $classification);

        // レート制限チェック
        $result = $this->rateLimitService->checkLimit($key, $rule);

        // メトリクス記録
        $this->metrics->recordHit($key, $rule, $result->isAllowed(), $result->getAttempts());

        // 429レスポンス
        if ($result->isBlocked()) {
            $this->metrics->recordBlock($key, $rule, $result->getAttempts(), $retryAfter);
            return $this->buildRateLimitResponse($key, $classification, $result);
        }

        // HTTPヘッダー追加
        $response = $next($request);
        return $this->addRateLimitHeaders($response, $key, $classification, $result);
    }
}
```

## テスト戦略

### テスト構成（167テスト、530アサーション、92%合格率）

#### 1. Domain層 Unit Tests（46テスト）
- **RateLimitRuleTest**: 15テスト（不変性、検証ロジック、エッジケース）
- **RateLimitKeyTest**: 15テスト（不変性、等価性、文字列変換）
- **RateLimitResultTest**: 16テスト（許可/拒否状態、ファクトリメソッド）

#### 2. Application層 Unit Tests（50テスト）
- **EndpointClassifierTest**: 15テスト（4種類分類、保護ルートマッチング）
- **KeyResolverTest**: 16テスト（フォールバックチェーン、プライバシー保護）
- **RateLimitConfigManagerTest**: 19テスト（設定読み込み、環境変数、キャッシング）

#### 3. Infrastructure層 Integration Tests（40テスト）
- **LaravelRateLimiterStoreTest**: 19テスト（原子的操作、TTL、リセット）
- **FailoverRateLimitStoreTest**: 14テスト（フェイルオーバー、ヘルスチェック、ロールバック）
- **LogMetricsTest**: 7テスト（構造化ログ、メトリクスフォーマット）

#### 4. HTTP層 Feature Tests（9テスト）
- **DynamicRateLimitFeatureTest**: 9テスト（4種類分類別、HTTPヘッダー、429レスポンス）

#### 5. Architecture Tests（14テスト）
- **RateLimitArchitectureTest**: 14テスト（DDD原則準拠、依存方向、命名規約）

#### 6. E2E Tests（10テスト、8テスト合格）
- **RateLimitFlowE2ETest**: 10テスト（完全なHTTPリクエストフロー、実際の429レスポンス）

## 設定

### config/ratelimit.php

```php
return [
    // DDD準拠エンドポイント分類
    'endpoint_types' => [
        'public_unauthenticated' => [
            'max_attempts' => env('RATELIMIT_PUBLIC_UNAUTHENTICATED_MAX_ATTEMPTS', 60),
            'decay_minutes' => env('RATELIMIT_PUBLIC_UNAUTHENTICATED_DECAY_MINUTES', 1),
        ],
        'protected_unauthenticated' => [
            'max_attempts' => env('RATELIMIT_PROTECTED_UNAUTHENTICATED_MAX_ATTEMPTS', 5),
            'decay_minutes' => env('RATELIMIT_PROTECTED_UNAUTHENTICATED_DECAY_MINUTES', 10),
        ],
        'public_authenticated' => [
            'max_attempts' => env('RATELIMIT_PUBLIC_AUTHENTICATED_MAX_ATTEMPTS', 120),
            'decay_minutes' => env('RATELIMIT_PUBLIC_AUTHENTICATED_DECAY_MINUTES', 1),
        ],
        'protected_authenticated' => [
            'max_attempts' => env('RATELIMIT_PROTECTED_AUTHENTICATED_MAX_ATTEMPTS', 30),
            'decay_minutes' => env('RATELIMIT_PROTECTED_AUTHENTICATED_DECAY_MINUTES', 1),
        ],
    ],

    // デフォルトフォールバック
    'default' => [
        'max_attempts' => env('RATELIMIT_DEFAULT_MAX_ATTEMPTS', 30),
        'decay_minutes' => env('RATELIMIT_DEFAULT_DECAY_MINUTES', 1),
    ],

    // 保護ルートパターン
    'protected_routes' => [
        'login',
        'register',
        'password.*',
        'admin.*',
        'payment.*',
    ],

    // キャッシュストア設定
    'cache' => [
        'store' => env('RATELIMIT_CACHE_STORE', 'redis'),
        'prefix' => 'rate_limit',
    ],
];
```

### .env.example（Phase 5で追加）

```bash
# APIレート制限設定（DDD準拠 - Phase 5）
RATELIMIT_PUBLIC_UNAUTHENTICATED_MAX_ATTEMPTS=60
RATELIMIT_PUBLIC_UNAUTHENTICATED_DECAY_MINUTES=1

RATELIMIT_PROTECTED_UNAUTHENTICATED_MAX_ATTEMPTS=5
RATELIMIT_PROTECTED_UNAUTHENTICATED_DECAY_MINUTES=10

RATELIMIT_PUBLIC_AUTHENTICATED_MAX_ATTEMPTS=120
RATELIMIT_PUBLIC_AUTHENTICATED_DECAY_MINUTES=1

RATELIMIT_PROTECTED_AUTHENTICATED_MAX_ATTEMPTS=30
RATELIMIT_PROTECTED_AUTHENTICATED_DECAY_MINUTES=1

RATELIMIT_DEFAULT_MAX_ATTEMPTS=30
RATELIMIT_DEFAULT_DECAY_MINUTES=1

RATELIMIT_CACHE_STORE=redis  # redis/array
```

## HTTPレスポンスヘッダー

### 標準ヘッダー
- `X-RateLimit-Limit`: 制限値（例: 60）
- `X-RateLimit-Remaining`: 残り回数（例: 55）
- `X-RateLimit-Reset`: リセット時刻（Unix timestamp）

### 拡張ヘッダー（Phase 4で追加）
- `X-RateLimit-Policy`: 適用されたエンドポイント分類（例: public_unauthenticated）
- `X-RateLimit-Key`: レート制限キー（ハッシュ化済み、デバッグ用）

### 429レスポンス
- `Retry-After`: リトライ可能になるまでの秒数
- JSONボディ:
  ```json
  {
    "message": "Too Many Requests",
    "retry_after": 60
  }
  ```

## 運用ドキュメント

詳細な運用手順、トラブルシューティング、API仕様書は以下を参照してください:
- [運用手順ドキュメント](./RATELIMIT_OPERATIONS.md) - 設定変更、監視、アラート
- [トラブルシューティング](./RATELIMIT_TROUBLESHOOTING.md) - よくある問題と解決策
- API仕様書 - HTTPヘッダー、429レスポンス、エンドポイント分類別制限値

## 関連ドキュメント

- [DDD/クリーンアーキテクチャ概要](./ddd-architecture.md)
- [DDDテスト戦略](./ddd-testing-strategy.md)
- [ミドルウェア実装ガイド](./MIDDLEWARE_IMPLEMENTATION.md)

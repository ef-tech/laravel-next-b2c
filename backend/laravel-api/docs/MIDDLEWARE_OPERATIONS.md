# ミドルウェア運用マニュアル

## 目次

1. [監視項目](#監視項目)
2. [ログ確認手順](#ログ確認手順)
3. [トラブルシューティング](#トラブルシューティング)
4. [レート制限調整](#レート制限調整)
5. [パフォーマンスメトリクス確認](#パフォーマンスメトリクス確認)
6. [環境変数設定ガイド](#環境変数設定ガイド)
7. [よくある問題と解決方法](#よくある問題と解決方法)

---

## 監視項目

### 必須監視項目

#### 1. レート制限メトリクス
- **監視内容**: レート制限超過回数
- **ログレベル**: `WARNING`
- **ログ識別子**: `DynamicRateLimit: Rate limit exceeded`
- **対応**: レート制限設定の見直し、攻撃の可能性確認

```bash
# レート制限超過ログの確認（過去24時間）
docker exec laravel.test tail -n 10000 /var/log/laravel.log | grep "Rate limit exceeded"
```

#### 2. パフォーマンスメトリクス
- **監視内容**: レスポンス時間の平均・最大値
- **閾値**:
  - 平均レスポンス時間 < 200ms（推奨）
  - 最大レスポンス時間 < 1000ms（警告）
- **ログレベル**: `INFO`
- **ログ識別子**: `Performance metrics recorded`

```bash
# レスポンス時間が1秒を超えたリクエストの確認
docker exec laravel.test tail -n 10000 /var/log/laravel.log | grep "Performance metrics" | grep -E "duration\":[0-9]{4,}"
```

#### 3. 認証エラー
- **監視内容**: 認証失敗回数、トークン検証エラー
- **ログレベル**: `WARNING`
- **ログ識別子**: `SanctumTokenVerification: Invalid token`, `auth.failed`
- **対応**: 不正アクセスの可能性確認、トークン有効期限設定の見直し

```bash
# 認証エラーログの確認
docker exec laravel.test tail -n 10000 /var/log/laravel.log | grep -E "Invalid token|auth.failed"
```

#### 4. 権限エラー
- **監視内容**: 権限不足によるアクセス拒否
- **ログレベル**: `WARNING`
- **ログ識別子**: `AuthorizationCheck: Access denied`
- **対応**: ユーザー権限設定の確認、不正アクセスの可能性確認

```bash
# 権限エラーログの確認
docker exec laravel.test tail -n 10000 /var/log/laravel.log | grep "Access denied"
```

#### 5. Redis接続エラー
- **監視内容**: Redis接続失敗、タイムアウト
- **ログレベル**: `ERROR`
- **ログ識別子**: `Connection refused`, `ECONNREFUSED`
- **対応**: Redisサービス稼働状況確認、ネットワーク確認

```bash
# Redis接続エラーログの確認
docker exec laravel.test tail -n 10000 /var/log/laravel.log | grep -E "Redis|ECONNREFUSED"
```

#### 6. CSPレポート
- **監視内容**: CSP違反報告
- **エンドポイント**: `/api/csp-report`
- **ログレベル**: `WARNING`
- **対応**: 許可されていないリソースの読み込み確認、CSPポリシー調整

```bash
# CSP違反レポートの確認
docker exec laravel.test tail -n 10000 /var/log/laravel.log | grep "CSP violation"
```

### 推奨監視項目

#### 7. Idempotencyキーエラー
- **監視内容**: 重複リクエスト検出、Idempotencyキーエラー
- **ログレベル**: `INFO`（正常動作）, `WARNING`（エラー）
- **対応**: クライアント実装確認、ネットワーク不安定性確認

#### 8. 監査ログ
- **監視内容**: 重要操作の監査記録
- **ログレベル**: `INFO`
- **ログ識別子**: `AuditTrail: Audit log recorded`
- **対応**: 定期的な監査ログレビュー、異常操作の検出

---

## ログ確認手順

### ログファイルの場所

#### 開発環境（Docker）
```bash
# アプリケーションログ
docker exec laravel.test cat storage/logs/laravel.log

# 最新100行を確認
docker exec laravel.test tail -n 100 storage/logs/laravel.log

# リアルタイムでログを監視
docker exec laravel.test tail -f storage/logs/laravel.log
```

#### 本番環境
```bash
# アプリケーションログ（ログ集約システムに依存）
# 例: CloudWatch Logs, Datadog, Splunk など
```

### ログフォーマット

全てのログは構造化JSON形式で記録されます。

```json
{
  "timestamp": "2025-10-21T12:34:56.789Z",
  "level": "INFO",
  "message": "Request processed successfully",
  "context": {
    "request_id": "550e8400-e29b-41d4-a716-446655440000",
    "correlation_id": "660f9500-f39c-52e5-b827-557766550001",
    "user_id": 123,
    "method": "POST",
    "url": "/api/users",
    "duration": 145.67
  }
}
```

### ログレベル

- **DEBUG**: 詳細なデバッグ情報（開発環境のみ）
- **INFO**: 正常な処理フロー情報
- **WARNING**: 警告（処理は継続）
- **ERROR**: エラー（処理失敗）
- **CRITICAL**: 重大なエラー（システム停止の可能性）

### Request IDとCorrelation IDの追跡

全てのリクエストには一意のRequest IDとCorrelation IDが付与されます。

```bash
# Request IDでログを追跡
docker exec laravel.test cat storage/logs/laravel.log | grep "550e8400-e29b-41d4-a716-446655440000"

# Correlation IDで分散トレーシング
docker exec laravel.test cat storage/logs/laravel.log | grep "660f9500-f39c-52e5-b827-557766550001"
```

---

## トラブルシューティング

### 問題1: レート制限によるHTTP 429エラー

#### 症状
```
HTTP/1.1 429 Too Many Requests
Retry-After: 60
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 0
```

#### 診断手順
1. レート制限設定を確認
   ```bash
   docker exec laravel.test php artisan config:show ratelimit
   ```

2. ログでレート制限超過を確認
   ```bash
   docker exec laravel.test tail -n 100 storage/logs/laravel.log | grep "Rate limit exceeded"
   ```

3. IPアドレス別のリクエスト数を確認（Redis CLI）
   ```bash
   docker exec redis redis-cli KEYS "ratelimit:*"
   docker exec redis redis-cli GET "ratelimit:api:192.168.1.100"
   ```

#### 解決方法
- **一時的な解決**: Redis内のレート制限キーを削除
  ```bash
  docker exec redis redis-cli DEL "ratelimit:api:192.168.1.100"
  ```

- **恒久的な解決**: レート制限設定を調整（後述の「レート制限調整」を参照）

---

### 問題2: Redis接続エラー

#### 症状
```
Connection refused [tcp://redis:6379]
```

#### 診断手順
1. Redisコンテナが起動しているか確認
   ```bash
   docker ps | grep redis
   ```

2. Redisサービスが応答するか確認
   ```bash
   docker exec redis redis-cli PING
   # 期待結果: PONG
   ```

3. Laravel設定を確認
   ```bash
   docker exec laravel.test php artisan config:show database.redis
   ```

#### 解決方法
- **Redisコンテナが停止している場合**
  ```bash
  docker compose up -d redis
  ```

- **Redis接続設定が誤っている場合**
  - `.env`ファイルの`REDIS_HOST`, `REDIS_PORT`, `REDIS_PASSWORD`を確認
  - `config/database.php`の設定を確認

- **グレースフルデグラデーション**
  - Redis接続エラーが発生してもアプリケーションは動作を継続します
  - レート制限やIdempotency機能が一時的に無効化されます
  - エラーログを確認し、早急にRedis接続を復旧してください

---

### 問題3: 認証エラー（HTTP 401）

#### 症状
```
HTTP/1.1 401 Unauthorized
{"message": "Unauthenticated."}
```

#### 診断手順
1. トークンが正しく送信されているか確認
   ```bash
   # リクエストヘッダーを確認
   curl -H "Authorization: Bearer YOUR_TOKEN" https://api.example.com/api/user
   ```

2. トークンがDBに存在するか確認
   ```bash
   docker exec laravel.test php artisan tinker
   >>> \Laravel\Sanctum\PersonalAccessToken::where('token', hash('sha256', 'YOUR_TOKEN'))->first();
   ```

3. ログでトークン検証エラーを確認
   ```bash
   docker exec laravel.test tail -n 100 storage/logs/laravel.log | grep "Invalid token"
   ```

#### 解決方法
- **トークンが無効な場合**: 新しいトークンを発行
  ```bash
  docker exec laravel.test php artisan tinker
  >>> $user = \App\Models\User::find(1);
  >>> $token = $user->createToken('api-token')->plainTextToken;
  >>> echo $token;
  ```

- **トークンの有効期限が切れている場合**: `config/sanctum.php`の`expiration`設定を確認

---

### 問題4: 権限エラー（HTTP 403）

#### 症状
```
HTTP/1.1 403 Forbidden
{"message": "Insufficient permissions."}
```

#### 診断手順
1. ユーザーの権限を確認
   ```bash
   docker exec laravel.test php artisan tinker
   >>> $user = \App\Models\User::find(1);
   >>> // 権限確認ロジックは実装に依存
   ```

2. ログで権限チェック結果を確認
   ```bash
   docker exec laravel.test tail -n 100 storage/logs/laravel.log | grep "Access denied"
   ```

#### 解決方法
- **ユーザーに権限を付与**
  - 権限管理システム（実装に依存）でユーザーに適切な権限を付与

- **AuthorizationServiceの実装を確認**
  - `app/Application/Ports/Security/AuthorizationServiceInterface.php`の実装を確認

---

### 問題5: パフォーマンス低下

#### 症状
- レスポンス時間が1秒以上
- タイムアウトエラー

#### 診断手順
1. パフォーマンスログを確認
   ```bash
   docker exec laravel.test tail -n 100 storage/logs/laravel.log | grep "Performance metrics" | jq '.context.duration'
   ```

2. スロークエリログを確認（PostgreSQL）
   ```bash
   docker exec postgres psql -U laravel -c "SELECT * FROM pg_stat_statements ORDER BY mean_exec_time DESC LIMIT 10;"
   ```

3. APM（Application Performance Monitoring）ツールでボトルネックを特定
   - New Relic, Datadog, Laravel Telescope など

#### 解決方法
- **データベースクエリ最適化**: インデックス追加、N+1クエリ解消
- **キャッシュ導入**: `CacheHeaders`ミドルウェア、Redisキャッシュ
- **スケーリング**: サーバーリソース増強、ロードバランサー導入

---

## レート制限調整

### レート制限設定ファイル

`config/ratelimit.php`

```php
return [
    'endpoints' => [
        'api' => [
            'requests' => env('RATELIMIT_API_REQUESTS', 60),
            'per_minute' => env('RATELIMIT_API_PER_MINUTE', 1),
            'by' => env('RATELIMIT_API_BY', 'ip'),
        ],
        'public' => [
            'requests' => env('RATELIMIT_PUBLIC_REQUESTS', 30),
            'per_minute' => env('RATELIMIT_PUBLIC_PER_MINUTE', 1),
            'by' => env('RATELIMIT_PUBLIC_BY', 'ip'),
        ],
        'webhook' => [
            'requests' => env('RATELIMIT_WEBHOOK_REQUESTS', 100),
            'per_minute' => env('RATELIMIT_WEBHOOK_PER_MINUTE', 1),
            'by' => env('RATELIMIT_WEBHOOK_BY', 'ip'),
        ],
        'strict' => [
            'requests' => env('RATELIMIT_STRICT_REQUESTS', 10),
            'per_minute' => env('RATELIMIT_STRICT_PER_MINUTE', 1),
            'by' => env('RATELIMIT_STRICT_BY', 'user'),
        ],
    ],
];
```

### レート制限調整の手順

#### 1. 現在の設定を確認

```bash
docker exec laravel.test php artisan config:show ratelimit
```

#### 2. 環境変数を変更

`.env`ファイルを編集:

```env
# APIエンドポイント（認証済みユーザー）
RATELIMIT_API_REQUESTS=120      # 1分間に120リクエストまで（デフォルト: 60）
RATELIMIT_API_PER_MINUTE=1      # 1分単位（変更不要）
RATELIMIT_API_BY=user           # ユーザーIDベース（デフォルト: ip）

# Publicエンドポイント（未認証ユーザー）
RATELIMIT_PUBLIC_REQUESTS=60    # 1分間に60リクエストまで（デフォルト: 30）
RATELIMIT_PUBLIC_PER_MINUTE=1
RATELIMIT_PUBLIC_BY=ip          # IPアドレスベース（変更不要）

# Webhookエンドポイント
RATELIMIT_WEBHOOK_REQUESTS=200  # 1分間に200リクエストまで（デフォルト: 100）
RATELIMIT_WEBHOOK_PER_MINUTE=1
RATELIMIT_WEBHOOK_BY=ip

# Strictエンドポイント（管理者APIなど）
RATELIMIT_STRICT_REQUESTS=20    # 1分間に20リクエストまで（デフォルト: 10）
RATELIMIT_STRICT_PER_MINUTE=1
RATELIMIT_STRICT_BY=user
```

#### 3. 設定を反映

```bash
# キャッシュをクリア
docker exec laravel.test php artisan config:clear

# 設定を確認
docker exec laravel.test php artisan config:show ratelimit
```

#### 4. 動作確認

```bash
# レート制限テストスクリプトを実行
for i in {1..70}; do
  curl -s -o /dev/null -w "%{http_code}\n" https://api.example.com/api/health
  sleep 0.5
done

# 期待結果:
# 1-60回目: 200 (成功)
# 61回目以降: 429 (レート制限超過)
```

### レート制限設定の推奨値

| エンドポイント | 用途 | 推奨値（req/min） | 識別子 |
|--------------|------|-----------------|--------|
| `api` | 認証済みAPI | 60-120 | `user` |
| `public` | 未認証API | 30-60 | `ip` |
| `webhook` | Webhook受信 | 100-200 | `ip` |
| `strict` | 管理者API | 10-20 | `user` |

### レート制限のバイパス（緊急時のみ）

緊急時にレート制限を一時的に無効化する場合:

```php
// app/Http/Middleware/DynamicRateLimit.php

public function handle(Request $request, Closure $next, string $endpoint = 'api'): Response
{
    // 緊急バイパス（本番環境では使用しない）
    if (config('app.env') === 'emergency') {
        return $next($request);
    }

    // 通常のレート制限処理
    // ...
}
```

---

## パフォーマンスメトリクス確認

### メトリクス収集の仕組み

`PerformanceMonitoring`ミドルウェアは全てのリクエストのパフォーマンスメトリクスを収集します。

- **測定項目**:
  - リクエスト処理時間（ミリ秒）
  - メモリ使用量
  - SQLクエリ数

- **ログ出力**: `terminate`メソッドで非同期にログ記録

### メトリクス確認方法

#### 1. ログファイルから確認

```bash
# 過去100件のパフォーマンスメトリクスを確認
docker exec laravel.test tail -n 1000 storage/logs/laravel.log | grep "Performance metrics" | jq '.context'
```

**出力例**:
```json
{
  "request_id": "550e8400-e29b-41d4-a716-446655440000",
  "method": "POST",
  "url": "/api/users",
  "duration": 145.67,
  "memory": 12345678,
  "queries": 5
}
```

#### 2. 平均レスポンス時間を計算

```bash
# 過去1000件の平均レスポンス時間を計算
docker exec laravel.test tail -n 5000 storage/logs/laravel.log | \
  grep "Performance metrics" | \
  jq -r '.context.duration' | \
  awk '{sum+=$1; count++} END {print "Average:", sum/count, "ms"}'
```

#### 3. スロークエリを検出

```bash
# レスポンス時間が1000ms（1秒）を超えるリクエストを検出
docker exec laravel.test tail -n 5000 storage/logs/laravel.log | \
  grep "Performance metrics" | \
  jq 'select(.context.duration > 1000)'
```

#### 4. エンドポイント別の統計

```bash
# エンドポイント別の平均レスポンス時間
docker exec laravel.test tail -n 5000 storage/logs/laravel.log | \
  grep "Performance metrics" | \
  jq -r '[.context.url, .context.duration] | @tsv' | \
  awk '{sum[$1]+=$2; count[$1]++} END {for (url in sum) print url, sum[url]/count[url], "ms"}'
```

### パフォーマンスアラート設定

本番環境では、以下の閾値でアラートを設定することを推奨します。

| メトリクス | 警告閾値 | 重大閾値 |
|----------|---------|---------|
| 平均レスポンス時間 | 200ms | 500ms |
| 最大レスポンス時間 | 1000ms | 3000ms |
| P95レスポンス時間 | 500ms | 1500ms |
| P99レスポンス時間 | 1000ms | 2000ms |

### APMツール統合（推奨）

本番環境では、APMツールとの統合を推奨します。

- **New Relic**: Laravelエージェント導入
- **Datadog**: APM統合
- **Laravel Telescope**: 開発・ステージング環境での詳細分析

---

## 環境変数設定ガイド

### 必須環境変数

#### レート制限設定

```env
# APIエンドポイント（認証済み）
RATELIMIT_API_REQUESTS=60        # 1分間のリクエスト数上限
RATELIMIT_API_PER_MINUTE=1       # 時間単位（分）
RATELIMIT_API_BY=ip              # 識別方法（ip or user）

# Publicエンドポイント（未認証）
RATELIMIT_PUBLIC_REQUESTS=30
RATELIMIT_PUBLIC_PER_MINUTE=1
RATELIMIT_PUBLIC_BY=ip

# Webhookエンドポイント
RATELIMIT_WEBHOOK_REQUESTS=100
RATELIMIT_WEBHOOK_PER_MINUTE=1
RATELIMIT_WEBHOOK_BY=ip

# Strictエンドポイント（管理者API）
RATELIMIT_STRICT_REQUESTS=10
RATELIMIT_STRICT_PER_MINUTE=1
RATELIMIT_STRICT_BY=user
```

#### Redis接続設定

```env
REDIS_HOST=redis                  # Redisホスト（Docker: redis, 本番: IPアドレス）
REDIS_PORT=6379                   # Redisポート
REDIS_PASSWORD=null               # Redisパスワード（本番環境では設定必須）
REDIS_DB=0                        # Redis DB番号
```

#### セキュリティヘッダー設定

```env
# HSTS設定
SECURITY_HSTS_MAX_AGE=31536000    # 1年（秒単位）
SECURITY_HSTS_INCLUDE_SUBDOMAINS=true
SECURITY_HSTS_PRELOAD=false       # Preload登録は慎重に（不可逆）

# CSP設定
SECURITY_CSP_ENABLED=true
SECURITY_CSP_REPORT_ONLY=false    # 本番移行前はtrueで検証
SECURITY_CSP_REPORT_URI=/api/csp-report

# Frame Options
SECURITY_FRAME_OPTIONS=DENY       # DENY, SAMEORIGIN, ALLOW-FROM

# Content Type Options
SECURITY_CONTENT_TYPE_OPTIONS=nosniff
```

#### キャッシュ設定

```env
# Cache-Control設定
CACHE_PUBLIC_MAX_AGE=3600         # 公開キャッシュ有効期限（秒）
CACHE_PRIVATE_MAX_AGE=1800        # プライベートキャッシュ有効期限（秒）
CACHE_STALE_WHILE_REVALIDATE=60   # 再検証中のStale許容時間（秒）
```

### オプション環境変数

#### Idempotency設定

```env
IDEMPOTENCY_CACHE_TTL=86400       # Idempotencyキャッシュ有効期限（秒、デフォルト: 24時間）
```

#### パフォーマンス監視設定

```env
PERFORMANCE_SLOW_QUERY_THRESHOLD=1000  # スロークエリ閾値（ミリ秒）
PERFORMANCE_LOG_ENABLED=true           # パフォーマンスログ記録を有効化
```

### 環境別設定例

#### 開発環境（`.env.local`）

```env
APP_ENV=local
APP_DEBUG=true

# レート制限を緩和
RATELIMIT_API_REQUESTS=1000
RATELIMIT_PUBLIC_REQUESTS=1000

# CSPをレポートのみモード
SECURITY_CSP_REPORT_ONLY=true

# キャッシュ無効化
CACHE_PUBLIC_MAX_AGE=0
CACHE_PRIVATE_MAX_AGE=0
```

#### ステージング環境（`.env.staging`）

```env
APP_ENV=staging
APP_DEBUG=false

# 本番に近いレート制限
RATELIMIT_API_REQUESTS=60
RATELIMIT_PUBLIC_REQUESTS=30

# CSPをレポートのみモード（検証用）
SECURITY_CSP_REPORT_ONLY=true

# キャッシュ有効化
CACHE_PUBLIC_MAX_AGE=3600
CACHE_PRIVATE_MAX_AGE=1800
```

#### 本番環境（`.env.production`）

```env
APP_ENV=production
APP_DEBUG=false

# 本番レート制限
RATELIMIT_API_REQUESTS=60
RATELIMIT_PUBLIC_REQUESTS=30

# セキュリティヘッダーを厳格化
SECURITY_CSP_REPORT_ONLY=false
SECURITY_HSTS_PRELOAD=false  # Preload登録後はtrue

# Redisパスワード必須
REDIS_PASSWORD=STRONG_PASSWORD_HERE

# キャッシュ有効化
CACHE_PUBLIC_MAX_AGE=3600
CACHE_PRIVATE_MAX_AGE=1800
```

---

## よくある問題と解決方法

### Q1. レート制限が効いていない

**原因**:
- Redis接続エラー（グレースフルデグラデーション発動）
- 環境変数設定ミス
- ミドルウェアグループ未適用

**解決方法**:
1. Redis接続を確認
   ```bash
   docker exec redis redis-cli PING
   ```

2. レート制限設定を確認
   ```bash
   docker exec laravel.test php artisan config:show ratelimit
   ```

3. ルート設定でミドルウェアグループが適用されているか確認
   ```bash
   docker exec laravel.test php artisan route:list
   ```

---

### Q2. Idempotencyキーが機能しない

**原因**:
- 未認証ユーザー（Idempotencyは認証ユーザーのみ対応）
- Redis接続エラー
- Idempotencyキーヘッダーが送信されていない

**解決方法**:
1. 認証トークンが送信されているか確認
   ```bash
   curl -H "Authorization: Bearer TOKEN" -H "Idempotency-Key: KEY" ...
   ```

2. Redis接続を確認（上記Q1参照）

3. ログでIdempotencyミドルウェア動作を確認
   ```bash
   docker exec laravel.test tail -f storage/logs/laravel.log | grep "IdempotencyKey"
   ```

---

### Q3. ETagが生成されない

**原因**:
- `readonly`ミドルウェアグループが適用されていない
- レスポンスがキャッシュ不可能（動的コンテンツ）

**解決方法**:
1. ルート設定で`readonly`グループが適用されているか確認
   ```php
   Route::get('/api/resource', [Controller::class, 'index'])
       ->middleware(['readonly']);
   ```

2. ETagミドルウェアが実行されているか確認
   ```bash
   curl -v https://api.example.com/api/resource
   # ETagヘッダーが返されることを確認
   ```

---

### Q4. CSP違反が多発する

**原因**:
- 外部リソース（CDN、画像、スクリプト）が許可されていない
- インラインスクリプトが許可されていない

**解決方法**:
1. CSPレポートを確認
   ```bash
   docker exec laravel.test tail -n 100 storage/logs/laravel.log | grep "CSP violation"
   ```

2. CSPポリシーを調整（`app/Http/Middleware/SecurityHeaders.php`）
   ```php
   $csp = [
       "default-src 'self'",
       "script-src 'self' https://cdn.example.com",  // CDN追加
       "img-src 'self' data: https:",                // 画像ソース追加
   ];
   ```

3. 一時的にレポートのみモードで検証
   ```env
   SECURITY_CSP_REPORT_ONLY=true
   ```

---

### Q5. パフォーマンスログが記録されない

**原因**:
- ログレベル設定（`INFO`レベルが無効化されている）
- PerformanceMonitoringミドルウェアが適用されていない

**解決方法**:
1. ログレベルを確認（`.env`）
   ```env
   LOG_LEVEL=info  # debug, info, warning, error, critical
   ```

2. ミドルウェアがグローバルに適用されているか確認
   ```php
   // bootstrap/app.php
   ->withMiddleware(function (Middleware $middleware) {
       $middleware->append([
           \App\Http\Middleware\PerformanceMonitoring::class,
       ]);
   })
   ```

---

### Q6. 監査ログが記録されない

**原因**:
- `auth`または`internal`ミドルウェアグループが適用されていない
- 未認証ユーザー（AuditTrailは認証ユーザーのみ対応）

**解決方法**:
1. ルート設定で`auth`または`internal`グループが適用されているか確認
   ```php
   Route::middleware(['auth'])->group(function () {
       // 監査ログが記録される
   });
   ```

2. 認証済みリクエストか確認
   ```bash
   curl -H "Authorization: Bearer TOKEN" ...
   ```

---

### Q7. ミドルウェアチェーンの実行順序がおかしい

**原因**:
- ミドルウェア登録順序が誤っている
- グローバルミドルウェアとグループミドルウェアの優先度

**解決方法**:
1. ミドルウェア実行順序を確認
   - グローバルミドルウェア → ミドルウェアグループ → ルート個別ミドルウェア

2. `bootstrap/app.php`のミドルウェア登録順序を確認
   ```php
   $middleware->append([
       \App\Http\Middleware\SetRequestId::class,        // 1番目
       \App\Http\Middleware\CorrelationId::class,       // 2番目
       \App\Http\Middleware\ForceJsonResponse::class,   // 3番目
       \App\Http\Middleware\SecurityHeaders::class,     // 4番目
       \App\Http\Middleware\RequestLogging::class,      // 5番目
       \App\Http\Middleware\PerformanceMonitoring::class, // 6番目
   ]);
   ```

---

## まとめ

このマニュアルでは、ミドルウェアの運用に必要な以下の内容を記載しました。

1. **監視項目**: レート制限、パフォーマンス、認証エラー、CSPレポートなど
2. **ログ確認手順**: ログファイルの場所、フォーマット、Request ID追跡
3. **トラブルシューティング**: レート制限エラー、Redis接続エラー、認証・権限エラー、パフォーマンス低下
4. **レート制限調整**: 設定ファイル、環境変数、推奨値、動作確認手順
5. **パフォーマンスメトリクス確認**: ログ分析、平均レスポンス時間、スロークエリ検出、APMツール統合
6. **環境変数設定ガイド**: 必須・オプション環境変数、環境別設定例
7. **よくある問題と解決方法**: 7つの典型的な問題と解決方法

運用中に問題が発生した場合は、このマニュアルを参照して対応してください。

---

**関連ドキュメント**:
- [ミドルウェア実装ガイド](MIDDLEWARE_IMPLEMENTATION.md)
- [README.md](../../README.md)
- [設定ファイル](../config/)

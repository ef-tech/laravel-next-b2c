# APIレート制限 トラブルシューティング

## 目次
- [よくある問題と解決策](#よくある問題と解決策)
- [Redis接続エラー](#redis接続エラー)
- [レート制限誤検知](#レート制限誤検知)
- [ログ分析手順](#ログ分析手順)

## よくある問題と解決策

### 問題1: テスト実行時に "Class Redis not found" エラー

**症状**:
```
Class "Redis" not found
at vendor/laravel/framework/src/Illuminate/Redis/Connectors/PhpRedisConnector.php:80
```

**原因**: PHP Redis拡張がインストールされていない

**解決策**:
```bash
# オプション1: Arrayストアを使用（推奨）
RATELIMIT_CACHE_STORE=array ./vendor/bin/pest --no-coverage

# オプション2: Redis拡張をインストール
# macOS (Homebrew)
brew install php-redis

# Ubuntu/Debian
sudo apt-get install php-redis

# Docker環境
# Dockerfile に追加: RUN pecl install redis && docker-php-ext-enable redis
```

### 問題2: レート制限が機能しない

**症状**: 制限値を超えてもリクエストが許可される

**原因と解決策**:

#### 1. キャッシュストア設定の確認
```bash
# .env ファイルを確認
grep RATELIMIT_CACHE_STORE .env

# 設定値が正しいか確認
php artisan tinker
>>> config('ratelimit.cache.store')
=> "redis"  # または "array"
```

#### 2. Redisサービスの状態確認
```bash
# Redisが起動しているか確認
docker ps | grep redis
# または
systemctl status redis

# Redis接続テスト
php artisan tinker
>>> Cache::store('redis')->put('test', 'value', 60)
>>> Cache::store('redis')->get('test')
=> "value"
```

#### 3. ミドルウェアの設定確認
```php
// config/middleware.php または routes/api.php
Route::middleware([DynamicRateLimit::class])->group(function () {
    // ルート定義
});
```

### 問題3: 429レスポンスの retry_after が負の値

**症状**: HTTPヘッダーまたはJSONボディの `retry_after` が負の値（例: -59）

**原因**: `resetAt` の計算タイミング問題

**解決策**:
```php
// LaravelRateLimiterStore.php の改善が必要
// Issue tracking: https://github.com/your-repo/issues/XXX
```

**一時的な回避策**:
```php
// クライアント側で負の値を0として扱う
$retryAfter = max(0, $response->header('Retry-After'));
```

## Redis接続エラー

### エラーメッセージ例

```
Connection refused [tcp://127.0.0.1:6379]
```

### 診断手順

#### 1. Redisサービスの状態確認
```bash
# Docker環境
docker ps | grep redis
docker logs laravel-redis

# ネイティブ環境
systemctl status redis
redis-cli ping
```

#### 2. 接続設定の確認
```bash
# .env ファイル
REDIS_HOST=127.0.0.1  # Docker環境では "redis"
REDIS_PORT=6379
REDIS_PASSWORD=null
```

#### 3. ネットワーク接続確認
```bash
# ポート確認
telnet 127.0.0.1 6379

# Docker環境でのホスト名解決
docker exec -it laravel-app ping redis
```

### 解決策

#### オプション1: Redisサービスの再起動
```bash
# Docker
docker restart laravel-redis

# ネイティブ
systemctl restart redis
```

#### オプション2: フェイルオーバーの活用
```bash
# セカンダリストアに切り替え（自動フェイルオーバー）
# システムが自動的に Array/File Cache に切り替わります

# ログで確認
tail -f storage/logs/laravel.log | grep rate_limit.failure
```

## レート制限誤検知

### 問題: 正常な利用者がブロックされる

**症状**: APIの正常利用中に429レスポンスを受け取る

**診断手順**:

#### 1. ログからブロック原因を特定
```bash
# 構造化ログから該当ユーザーの履歴を抽出
cat storage/logs/laravel.log | \
  grep "rate_limit.blocked" | \
  jq 'select(.context.key | contains("user_123"))'
```

出力例:
```json
{
  "level": "warning",
  "message": "rate_limit.blocked",
  "context": {
    "key": "user_123",
    "max_attempts": 30,
    "attempts": 31,
    "retry_after": 45,
    "timestamp": "2025-10-23T10:30:00Z"
  }
}
```

#### 2. エンドポイント分類の確認
```bash
# ログから該当リクエストのエンドポイント分類を確認
cat storage/logs/laravel.log | \
  grep "rate_limit.hit" | \
  jq 'select(.context.key | contains("user_123")) | .context'
```

#### 3. レート制限値の妥当性確認
```bash
# 現在の設定値を確認
php artisan tinker
>>> config('ratelimit.endpoint_types')
```

### 解決策

#### オプション1: 制限値の調整
```bash
# .env ファイルで制限値を増やす
RATELIMIT_PUBLIC_AUTHENTICATED_MAX_ATTEMPTS=200  # デフォルト: 120
```

#### オプション2: エンドポイント分類の見直し
```php
// config/ratelimit.php
// 特定のルートを保護ルートから除外
'protected_routes' => [
    'login',
    'register',
    'password.*',
    'admin.*',
    // 'api.frequently-used',  # コメントアウトして除外
],
```

#### オプション3: 一時的なレート制限リセット
```bash
# 特定ユーザーのレート制限カウンターをリセット
php artisan tinker
>>> use Illuminate\Support\Facades\Cache;
>>> Cache::store('redis')->forget('rate_limit:user_123');
```

## ログ分析手順

### 構造化ログの活用

#### 1. レート制限ブロック数の集計
```bash
# 過去1時間のブロック数
cat storage/logs/laravel.log | \
  grep "rate_limit.blocked" | \
  grep $(date -u -d '1 hour ago' +"%Y-%m-%dT%H") | \
  jq -s 'length'
```

#### 2. ブロック頻度の高いIPアドレスの特定
```bash
# IPアドレス別ブロック数（Top 10）
cat storage/logs/laravel.log | \
  grep "rate_limit.blocked" | \
  jq -r '.context.key' | \
  grep "^ip_" | \
  sort | uniq -c | sort -rn | head -10
```

出力例:
```
    45 ip_192.168.1.100
    23 ip_203.0.113.50
    12 ip_198.51.100.25
```

#### 3. エンドポイント分類別の統計
```bash
# エンドポイント分類別のヒット数
cat storage/logs/laravel.log | \
  grep "rate_limit.hit" | \
  jq -r '.context | "\(.max_attempts)req/\(.decay_minutes)min"' | \
  sort | uniq -c | sort -rn
```

出力例:
```
   1500 60req/1min    # public_unauthenticated
    800 120req/1min   # public_authenticated
    150 30req/1min    # protected_authenticated
     45 5req/10min    # protected_unauthenticated
```

#### 4. リアルタイム監視
```bash
# レート制限イベントをリアルタイム監視
tail -f storage/logs/laravel.log | \
  grep --line-buffered "rate_limit\." | \
  jq '.message, .context'
```

### Prometheus/StatsD統合（Phase 2で実装予定）

```bash
# メトリクスエンドポイント
curl http://localhost:9090/metrics | grep rate_limit

# 出力例:
# rate_limit_hit_total{policy="public_unauthenticated"} 1500
# rate_limit_blocked_total{policy="public_unauthenticated"} 45
# rate_limit_failure_total 0
```

## セカンダリストア使用時の制限値緩和

### 仕様
- **プライマリストア障害時**: 自動的にセカンダリストア（Array/File Cache）に切り替え
- **制限値緩和**: `maxAttempts × 2` に緩和
- **理由**: DDoS攻撃対策を維持しつつ、正常な利用者への影響を最小化

### 確認方法

```bash
# ログからフェイルオーバー発生を確認
cat storage/logs/laravel.log | grep "Primary store failed"

# 出力例:
# {
#   "level": "warning",
#   "message": "Primary store failed, switching to secondary",
#   "context": {
#     "error": "Connection refused",
#     "timestamp": "2025-10-23T10:30:00Z"
#   }
# }
```

### 動作検証

```bash
# セカンダリストアで制限値が2倍になることを確認
RATELIMIT_CACHE_STORE=array ./vendor/bin/pest \
  --filter="FailoverRateLimitStoreTest" \
  --no-coverage
```

## 関連ドキュメント

- [実装アーキテクチャ](./RATELIMIT_IMPLEMENTATION.md)
- [運用ガイド](./RATELIMIT_OPERATIONS.md)

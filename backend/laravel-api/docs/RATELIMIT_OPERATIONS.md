# APIレート制限 運用ガイド

## 目次
- [設定変更手順](#設定変更手順)
- [監視とメトリクス](#監視とメトリクス)
- [Redis障害時の対応](#redis障害時の対応)
- [ベストプラクティス](#ベストプラクティス)

## 設定変更手順

### レート制限値の変更

#### 1. 環境変数の更新
`.env` ファイルで制限値を変更:

```bash
# 公開・未認証エンドポイント（デフォルト: 60 req/min）
RATELIMIT_PUBLIC_UNAUTHENTICATED_MAX_ATTEMPTS=100

# 保護・未認証エンドポイント（デフォルト: 5 req/10min）
RATELIMIT_PROTECTED_UNAUTHENTICATED_MAX_ATTEMPTS=10
RATELIMIT_PROTECTED_UNAUTHENTICATED_DECAY_MINUTES=10

# 公開・認証済みエンドポイント（デフォルト: 120 req/min）
RATELIMIT_PUBLIC_AUTHENTICATED_MAX_ATTEMPTS=200

# 保護・認証済みエンドポイント（デフォルト: 30 req/min）
RATELIMIT_PROTECTED_AUTHENTICATED_MAX_ATTEMPTS=50
```

#### 2. 設定の反映
```bash
# キャッシュクリア
php artisan config:clear

# アプリケーション再起動（本番環境）
php artisan octane:reload  # または
systemctl restart laravel-app
```

#### 3. 動作確認
```bash
# レート制限テスト実行
RATELIMIT_CACHE_STORE=array ./vendor/bin/pest --filter="RateLimit" --no-coverage
```

### 保護ルートパターンの追加

`config/ratelimit.php` の `protected_routes` 配列に追加:

```php
'protected_routes' => [
    'login',
    'register',
    'password.*',
    'admin.*',
    'payment.*',
    'api.sensitive-action',  // 新規追加
],
```

## 監視とメトリクス

### 構造化ログ

すべてのレート制限イベントは構造化ログとして記録されます:

```json
{
  "level": "info",
  "message": "rate_limit.hit",
  "context": {
    "key": "ip_192.168.1.100",
    "max_attempts": 60,
    "decay_minutes": 1,
    "attempts": 45,
    "allowed": true,
    "timestamp": "2025-10-23T10:30:00Z"
  }
}
```

```json
{
  "level": "warning",
  "message": "rate_limit.blocked",
  "context": {
    "key": "ip_192.168.1.100",
    "max_attempts": 60,
    "attempts": 61,
    "retry_after": 30,
    "timestamp": "2025-10-23T10:31:00Z"
  }
}
```

### メトリクス監視項目

#### 1. `rate_limit.hit`
- **説明**: レート制限チェック実行
- **頻度**: 全リクエスト
- **監視ポイント**: リクエスト数の急激な増加

#### 2. `rate_limit.blocked`
- **説明**: レート制限超過によるブロック
- **頻度**: 制限値超過時
- **監視ポイント**: ブロック率の上昇（正常利用者への影響）

#### 3. `rate_limit.failure`
- **説明**: レート制限チェック失敗（Redis障害等）
- **頻度**: 障害発生時
- **監視ポイント**: フェイルオーバー発生の検知

### アラート設定例（Prometheus/StatsD統合準備）

```yaml
# Prometheus AlertManager設定例
groups:
  - name: rate_limit_alerts
    rules:
      - alert: HighRateLimitBlockRate
        expr: rate(rate_limit_blocked_total[5m]) > 10
        for: 5m
        labels:
          severity: warning
        annotations:
          summary: "レート制限ブロック率が高い"
          description: "5分間で10回以上のブロックが発生"

      - alert: RateLimitFailureDetected
        expr: rate(rate_limit_failure_total[1m]) > 0
        for: 1m
        labels:
          severity: critical
        annotations:
          summary: "レート制限システム障害"
          description: "Redis障害またはフェイルオーバー発生"
```

## Redis障害時の対応

### 自動フェイルオーバー

Redis障害時、システムは自動的にセカンダリストア（Array/File Cache）に切り替わります:

1. **障害検知**: プライマリストア（Redis）へのアクセス失敗
2. **切り替え**: セカンダリストアへ自動切り替え
3. **制限値緩和**: `maxAttempts × 2` に緩和（DDoS対策維持）
4. **ログ記録**: `rate_limit.failure` ログ出力

### ヘルスチェックとロールバック

- **ヘルスチェック間隔**: 30秒
- **ロールバック条件**: プライマリストアの復旧確認後、自動的にプライマリストアに戻る

### 手動での切り替え（緊急時）

```bash
# セカンダリストアに強制切り替え
RATELIMIT_CACHE_STORE=array php artisan serve

# Redis復旧後、プライマリストアに戻す
RATELIMIT_CACHE_STORE=redis php artisan octane:reload
```

## ベストプラクティス

### 1. 開発環境での設定
```bash
# 緩い制限で開発効率向上
RATELIMIT_PUBLIC_UNAUTHENTICATED_MAX_ATTEMPTS=1000
RATELIMIT_PROTECTED_UNAUTHENTICATED_MAX_ATTEMPTS=100
RATELIMIT_PUBLIC_AUTHENTICATED_MAX_ATTEMPTS=1000
RATELIMIT_PROTECTED_AUTHENTICATED_MAX_ATTEMPTS=1000
```

### 2. テスト環境での設定
```bash
# Arrayストアで並列テスト実行
RATELIMIT_CACHE_STORE=array
```

### 3. 本番環境での設定
```bash
# Redisストアで高速・スケーラブルな運用
RATELIMIT_CACHE_STORE=redis

# 推奨値（.env.exampleを参照）
RATELIMIT_PUBLIC_UNAUTHENTICATED_MAX_ATTEMPTS=60
RATELIMIT_PROTECTED_UNAUTHENTICATED_MAX_ATTEMPTS=5
RATELIMIT_PUBLIC_AUTHENTICATED_MAX_ATTEMPTS=120
RATELIMIT_PROTECTED_AUTHENTICATED_MAX_ATTEMPTS=30
```

### 4. ログローテーション
```bash
# storage/logs/laravel.log のローテーション設定
# logrotate.d/laravel 例:
/path/to/storage/logs/laravel.log {
    daily
    rotate 14
    compress
    delaycompress
    missingok
    notifempty
    create 0644 www-data www-data
}
```

## 関連ドキュメント

- [実装アーキテクチャ](./RATELIMIT_IMPLEMENTATION.md)
- [トラブルシューティング](./RATELIMIT_TROUBLESHOOTING.md)

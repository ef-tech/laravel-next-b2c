# API V2 Roadmap

## 目次

1. [V2実装方針](#v2実装方針)
2. [段階的移行戦略](#段階的移行戦略)
3. [V2技術要件](#v2技術要件)
4. [V1→V2移行タイムライン](#v1v2移行タイムライン)
5. [V1 EOL計画](#v1-eol計画)

## V2実装方針

### 1. V2の主な改善点

#### 1.1 GraphQL対応（検討中）

**現状（V1）**:
- RESTful API
- 過剰なデータ取得（Over-fetching）
- 複数エンドポイント呼び出し必要

**改善（V2）**:
```graphql
query {
  user(id: 1) {
    id
    email
    name
    posts(first: 10) {
      id
      title
      comments {
        id
        body
      }
    }
  }
}
```

**メリット**:
- クライアント主導のデータ取得
- 単一エンドポイント
- 型安全性

**デメリット**:
- 学習コスト
- キャッシュ戦略の複雑化
- パフォーマンス監視が困難

**決定**: Phase 2で再評価

#### 1.2 エラーレスポンスの統一

**現状（V1）**:
```json
{
  "error": "email_already_exists",
  "message": "The email has already been taken."
}
```

**改善（V2）**:
```json
{
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Validation failed",
    "details": [
      {
        "field": "email",
        "code": "ALREADY_EXISTS",
        "message": "The email has already been taken."
      }
    ],
    "trace_id": "550e8400-e29b-41d4-a716-446655440000"
  }
}
```

**RFC 7807準拠**:
```json
{
  "type": "https://api.example.com/errors/validation-error",
  "title": "Validation Error",
  "status": 422,
  "detail": "The email has already been taken",
  "instance": "/api/v2/register",
  "trace_id": "550e8400-e29b-41d4-a716-446655440000"
}
```

#### 1.3 ペジネーションの改善

**現状（V1）**:
```http
GET /api/v1/users?page=2&per_page=20

Response:
{
  "users": [...],
  "current_page": 2,
  "last_page": 10,
  "total": 200
}
```

**改善（V2 - Cursor-based）**:
```http
GET /api/v2/users?cursor=eyJpZCI6MjB9&limit=20

Response:
{
  "data": [...],
  "pagination": {
    "next_cursor": "eyJpZCI6NDB9",
    "prev_cursor": "eyJpZCI6MX0",
    "has_more": true
  }
}
```

**メリット**:
- リアルタイム更新対応
- パフォーマンス向上
- 大規模データセット対応

#### 1.4 レート制限の可視化

**現状（V1）**:
```http
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 58
```

**改善（V2）**:
```http
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 58
X-RateLimit-Reset: 1609459200
X-RateLimit-Resource: users.index
X-RateLimit-Policy: per-user
```

#### 1.5 HATEOAS対応（検討中）

**現状（V1）**:
```json
{
  "id": 1,
  "email": "user@example.com"
}
```

**改善（V2）**:
```json
{
  "id": 1,
  "email": "user@example.com",
  "_links": {
    "self": { "href": "/api/v2/users/1" },
    "posts": { "href": "/api/v2/users/1/posts" },
    "avatar": { "href": "/api/v2/users/1/avatar" }
  }
}
```

## 段階的移行戦略

### Phase 1: V2基盤構築（3ヶ月）

#### Month 1: 設計・プロトタイプ

**Week 1-2**:
- [ ] V2 API仕様策定
  - エラーレスポンス形式（RFC 7807）
  - ペジネーション方式（Cursor-based）
  - 認証方式（OAuth 2.0検討）
- [ ] プロトタイプ実装
  - `/api/v2/health` エンドポイント
  - V2ミドルウェアスタック
  - V2レスポンスフォーマッター

**Week 3-4**:
- [ ] V2ルーティング基盤
  - `routes/api/v2.php` 作成
  - V2専用Middleware作成
  - Architecture Tests更新

#### Month 2: コア機能実装

**Week 5-6**:
- [ ] 認証エンドポイント（V2）
  - POST /api/v2/register
  - POST /api/v2/login
  - POST /api/v2/logout
  - POST /api/v2/refresh-token
- [ ] エラーハンドリング改善
  - RFC 7807準拠エラーレスポンス
  - 多言語対応エラーメッセージ

**Week 7-8**:
- [ ] ユーザー管理エンドポイント（V2）
  - GET /api/v2/users（Cursor-based pagination）
  - GET /api/v2/users/{id}
  - PUT /api/v2/users/{id}
  - DELETE /api/v2/users/{id}

#### Month 3: テスト・ドキュメント

**Week 9-10**:
- [ ] V2 E2Eテスト作成
  - 17+新規テストケース
  - V1/V2比較テスト
  - 後方互換性テスト

**Week 11-12**:
- [ ] V2ドキュメント作成
  - API Reference（OpenAPI 3.1）
  - Migration Guide（V1→V2）
  - Best Practices

### Phase 2: V2機能拡張（3ヶ月）

#### Month 4-5: 高度な機能

- [ ] GraphQL エンドポイント（オプション）
  - Schema定義
  - Resolver実装
  - DataLoader（N+1問題解決）
- [ ] WebSocket対応（リアルタイム通知）
  - Laravel Reverb統合
  - Pusher互換API
- [ ] Webhook管理
  - POST /api/v2/webhooks
  - GET /api/v2/webhooks
  - DELETE /api/v2/webhooks/{id}
  - Webhook配信リトライ

#### Month 6: パフォーマンス最適化

- [ ] Redis キャッシュ戦略
  - エンドポイント毎のTTL設定
  - キャッシュ無効化戦略
  - キャッシュウォーミング
- [ ] CDN統合
  - CloudFlare設定
  - 静的レスポンスのキャッシュ
- [ ] レート制限の高度化
  - IP + User ID複合制限
  - エンドポイント毎の動的制限
  - バースト制御

### Phase 3: V1→V2移行開始（6ヶ月）

#### Month 7-9: クライアント移行

**Month 7**:
- [ ] Admin App V2移行
  - V2 APIクライアント実装
  - V1/V2デュアルスタック運用
  - A/Bテスト実施

**Month 8**:
- [ ] User App V2移行
  - V2 APIクライアント実装
  - 段階的ロールアウト（10% → 50% → 100%）
  - モニタリング強化

**Month 9**:
- [ ] モバイルアプリV2移行（未定）
  - iOS/Android SDK更新
  - アプリバージョン強制アップデート

#### Month 10-12: V1 EOL準備

**Month 10**:
- [ ] V1使用状況分析
  - アクティブクライアント数
  - エンドポイント毎の使用率
  - V2移行率

**Month 11**:
- [ ] V1非推奨アナウンス
  - X-API-Deprecated: true ヘッダー追加
  - ドキュメント更新
  - メール通知

**Month 12**:
- [ ] V1 EOL準備
  - V1クライアントへの最終通知
  - V1データ移行ツール提供
  - V1→V2自動リダイレクト機能

## V2技術要件

### 1. パフォーマンス要件

| 指標 | V1 | V2目標 | 改善率 |
|------|----|----|-------|
| 平均レスポンス時間 | 150ms | 100ms | 33%改善 |
| 95%ile レスポンス時間 | 500ms | 300ms | 40%改善 |
| 同時接続数 | 1000 | 5000 | 5倍 |
| エラー率 | 0.1% | 0.05% | 50%削減 |

### 2. セキュリティ要件

**V1からの改善**:
- [ ] OAuth 2.0対応（検討中）
- [ ] JWT（RS256）による署名検証
- [ ] API Key + Bearer Token 二要素認証
- [ ] CSP Level 3対応
- [ ] Subresource Integrity（SRI）

### 3. 監視要件

**V1からの追加**:
- [ ] Distributed Tracing（OpenTelemetry）
- [ ] メトリクス収集（Prometheus）
- [ ] ログ集約（ELK Stack）
- [ ] リアルタイムアラート（PagerDuty）

### 4. スケーラビリティ要件

**V2アーキテクチャ**:
```
┌─────────────┐
│  CloudFlare │ ← CDN（静的レスポンス）
└──────┬──────┘
       │
┌──────▼──────┐
│ Load Balancer│ ← Auto Scaling
└──────┬──────┘
       │
┌──────▼──────────────────────┐
│ Laravel API (V2)             │
│ - Horizontal Scaling         │
│ - Stateless                  │
│ - Docker + Kubernetes        │
└──────┬──────────────────────┘
       │
┌──────▼──────┬─────────────┬──────────┐
│ PostgreSQL  │ Redis       │ S3       │
│ (RDS)       │ (ElastiCache)│ (Storage)│
└─────────────┴─────────────┴──────────┘
```

## V1→V2移行タイムライン

### 並行運用期間

```
Month 1-3:   [V1 Production] [V2 Development]
Month 4-6:   [V1 Production] [V2 Staging]
Month 7-9:   [V1 Production] [V2 Production (Beta)]
Month 10-12: [V1 Production] [V2 Production (Stable)]
Month 13-18: [V1 Deprecated] [V2 Production]
Month 19+:   [V1 EOL]        [V2 Production]
```

### V1非推奨スケジュール

```
Month 10: X-API-Deprecated: true ヘッダー追加
Month 11: V1 API Rate Limit 50%削減 (60 → 30 req/min)
Month 12: V1 API Rate Limit 75%削減 (60 → 15 req/min)
Month 13: V1 API完全Read-Only化
Month 14: V1 API 自動V2リダイレクト
Month 18: V1 API完全停止（EOL）
```

## V1 EOL計画

### 1. EOL基準

以下のすべてを満たした場合、V1をEOLとする：

- [ ] V2安定運用6ヶ月以上
- [ ] V1使用率5%以下
- [ ] V2エラー率0.05%以下
- [ ] 全クライアントV2対応完了

### 2. EOL手順

#### Step 1: アナウンス（EOL 6ヶ月前）

```
Subject: [重要] API V1 End-of-Life (EOL) のお知らせ

お客様各位

平素よりご利用いただきありがとうございます。

API V1のEnd-of-Life (EOL)を下記の通りお知らせいたします。

■ EOL日時
2026年6月30日 23:59:59 UTC

■ 影響を受けるエンドポイント
/api/v1/* すべて

■ 移行先
API V2 (/api/v2/*)
Migration Guide: https://docs.example.com/v1-to-v2

■ サポート期間
V1は2026年6月30日まで引き続きサポートいたします。
それ以降はV2のみのサポートとなります。

ご不明な点がございましたら、お問い合わせください。
```

#### Step 2: X-API-Deprecatedヘッダー追加（EOL 5ヶ月前）

```http
HTTP/1.1 200 OK
X-API-Version: v1
X-API-Deprecated: true
X-API-Sunset: 2026-06-30T23:59:59Z
X-API-Replacement: /api/v2/users
Link: </api/v2/users>; rel="successor-version"
```

#### Step 3: Rate Limit削減（EOL 3ヶ月前）

```
V1 Rate Limit:
  Before: 60 req/min
  After:  30 req/min (-50%)

V2 Rate Limit:
  Unchanged: 120 req/min
```

#### Step 4: Read-Only化（EOL 2ヶ月前）

```http
POST /api/v1/users HTTP/1.1
Response: 410 Gone

{
  "error": {
    "code": "API_GONE",
    "message": "API V1 is deprecated. Please use V2.",
    "replacement": "/api/v2/users"
  }
}
```

#### Step 5: 完全停止（EOL当日）

```http
GET /api/v1/users HTTP/1.1
Response: 301 Moved Permanently
Location: /api/v2/users

{
  "error": {
    "code": "API_EOL",
    "message": "API V1 has reached end-of-life. Redirecting to V2.",
    "redirect": "/api/v2/users"
  }
}
```

### 3. ロールバック計画

V2に重大な問題が発生した場合、V1に一時的にロールバック：

```bash
# V2無効化
php artisan route:cache --except=v2

# V1 Rate Limit復元
php artisan config:set ratelimit.api.requests 60

# V1 ログ監視強化
php artisan monitor:enable v1 --level=debug
```

## まとめ

V2 API開発は以下の3つのPhaseで進めます：

1. **Phase 1 (3ヶ月)**: V2基盤構築
   - ルーティング、認証、エラーハンドリング

2. **Phase 2 (3ヶ月)**: V2機能拡張
   - GraphQL、WebSocket、Webhook

3. **Phase 3 (6ヶ月)**: V1→V2移行
   - クライアント移行、V1 EOL

**合計期間**: 12ヶ月（V1→V2完全移行）

**次のステップ**: V2 Phase 1着手時に本ドキュメントを更新

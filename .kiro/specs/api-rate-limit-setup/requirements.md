# Requirements Document

## Introduction

本ドキュメントは、Laravel APIアプリケーションにおける包括的なレート制限システムの強化要件を定義します。現在の基本的なDynamicRateLimitミドルウェアには、エンドポイント分類の粗さ、Redis障害時の脆弱性、モニタリング不足などの課題があり、API乱用やサービス不安定化のリスクが存在します。

本機能強化により、以下のビジネス価値を実現します：
- **セキュリティ強化**: ブルートフォース攻撃・DDoS攻撃への耐性向上
- **サービス安定性**: API乱用による過負荷防止、正常ユーザーへの公平なリソース配分
- **運用効率化**: ログ・メトリクスによる早期問題検知、Redis障害時の自動フェイルオーバー
- **保守性向上**: DDD/クリーンアーキテクチャ準拠によるコード品質向上

**GitHub Issue**: [#30 - API レート制限設定](https://github.com/ef-tech/laravel-next-b2c/issues/30)

---

## Requirements

### Requirement 1: エンドポイント分類システム
**Objective:** API開発者として、エンドポイントの性質（認証状態・機密性）に応じた細分化されたレート制限を適用したい。それにより、ログインAPIへの総当たり攻撃を防ぎつつ、通常APIは快適に利用できるようにする。

#### Acceptance Criteria

1. WHEN APIリクエストを受信 THEN Rate Limit Systemは認証状態（未認証/認証済み）と機密性（公開/保護）の2軸でエンドポイントを分類しなければならない
2. WHERE エンドポイントが公開・未認証（例: 商品一覧、ブログ記事） THE Rate Limit Systemは IPアドレスベースで60 requests/minの制限を適用しなければならない
3. WHERE エンドポイントが保護・未認証（例: ログイン、パスワードリセット） THE Rate Limit Systemは IPアドレス + Emailアドレスベースで5 requests/10minの制限を適用しなければならない
4. WHERE エンドポイントが公開・認証済み（例: ユーザー情報取得） THE Rate Limit Systemは User IDベースで120 requests/minの制限を適用しなければならない
5. WHERE エンドポイントが保護・認証済み（例: 決済、機密データ更新） THE Rate Limit Systemは User IDベースで30 requests/minの制限を適用しなければならない
6. WHEN エンドポイント分類が不明 THEN Rate Limit Systemはデフォルトで最も厳格な制限（30 requests/min）を適用しなければならない

---

### Requirement 2: レート制限キー解決戦略
**Objective:** API運用者として、ユーザーを一意に識別する最適なキー戦略を自動適用したい。それにより、複数デバイスからのアクセスでも公平に制限を適用できる。

#### Acceptance Criteria

1. WHEN 認証済みリクエストを受信 AND User IDが取得可能 THEN Rate Limit Systemは User IDを優先的にレート制限キーとして使用しなければならない
2. WHEN 認証済みリクエストを受信 AND User IDが取得不可 AND Personal Access Token IDが取得可能 THEN Rate Limit Systemは Token IDをレート制限キーとして使用しなければならない
3. WHEN 未認証リクエストを受信 THEN Rate Limit Systemは IPアドレスをレート制限キーとして使用しなければならない
4. WHERE 保護エンドポイント（ログイン等）へのリクエスト AND Emailパラメータが存在 THE Rate Limit Systemは IPアドレス + Emailアドレスの組み合わせをレート制限キーとして使用しなければならない
5. WHEN レート制限キーを生成 THEN Rate Limit Systemは `rate_limit:{endpoint_type}:{key}` の形式でキーを構成しなければならない
6. WHEN レート制限キーをHTTPヘッダーで返却 THEN Rate Limit Systemは実際のキー値ではなくSHA-256ハッシュ値を返却しなければならない

---

### Requirement 3: HTTPレスポンスヘッダー強化
**Objective:** APIクライアント開発者として、レート制限の詳細情報（残回数・リセット時刻・適用ポリシー）を取得したい。それにより、クライアント側で適切なリトライ戦略を実装できる。

#### Acceptance Criteria

1. WHEN レート制限チェックを実行 THEN Rate Limit Systemは `X-RateLimit-Limit` ヘッダーで最大試行回数を返却しなければならない
2. WHEN レート制限チェックを実行 THEN Rate Limit Systemは `X-RateLimit-Remaining` ヘッダーで残り試行回数を返却しなければならない
3. WHEN レート制限チェックを実行 THEN Rate Limit Systemは `X-RateLimit-Reset` ヘッダーでUNIXタイムスタンプ形式のリセット時刻を返却しなければならない
4. WHEN レート制限チェックを実行 THEN Rate Limit Systemは `X-RateLimit-Policy` ヘッダーで適用されたエンドポイント分類（api_public, api_protected等）を返却しなければならない
5. WHEN レート制限チェックを実行 THEN Rate Limit Systemは `X-RateLimit-Key` ヘッダーでSHA-256ハッシュ化されたレート制限キーを返却しなければならない
6. WHEN レート制限超過により429レスポンスを返却 THEN Rate Limit Systemは `Retry-After` ヘッダーで再試行可能までの秒数を返却しなければならない
7. WHEN レート制限超過により429レスポンスを返却 THEN Rate Limit Systemは JSONボディで `{ "message": "Too Many Requests", "retry_after": {seconds} }` 形式のエラー詳細を返却しなければならない

---

### Requirement 4: Redis障害時フェイルオーバー戦略
**Objective:** SREエンジニアとして、Redis障害時にもサービスを継続稼働させたい。それにより、キャッシュ障害がAPI全体のダウンタイムに繋がることを防ぐ。

#### Acceptance Criteria

1. WHEN Redisへの接続試行が失敗（RedisException発生） THEN Rate Limit Systemは例外をキャッチし、セカンダリストア（Array/File Cache）へ自動的にフェイルオーバーしなければならない
2. WHEN セカンダリストアへフェイルオーバー THEN Rate Limit Systemは構造化ログチャネル `rate_limit` に WARNING レベルで障害情報を記録しなければならない
3. WHEN セカンダリストアへフェイルオーバー THEN Rate Limit Systemはメトリクス `rate_limit.failure` カウンターをインクリメントしなければならない
4. WHERE 開発環境でRedis障害が発生 THE Rate Limit Systemは Array Cacheストア（プロセス内インメモリ）を使用しなければならない
5. WHERE 本番環境でRedis障害が発生 THE Rate Limit Systemは File Cacheストア（ファイルベース永続化）を使用しなければならない
6. WHILE セカンダリストアを使用中 THE Rate Limit Systemは30秒間隔でRedisへのヘルスチェックを実行しなければならない
7. WHEN Redisヘルスチェックが成功 THEN Rate Limit Systemはプライマリストア（Redis）へ自動的にロールバックしなければならない
8. WHEN セカンダリストア使用時にレート制限判定を実行 THEN Rate Limit Systemはユーザー影響を最小化するため、制限値を通常の2倍（例: 60→120 req/min）に緩和しなければならない

---

### Requirement 5: 構造化ログ・メトリクス統合
**Objective:** 運用チームとして、レート制限の状況をリアルタイムで監視・分析したい。それにより、異常なトラフィックパターンを早期検知し、攻撃や障害に迅速に対応できる。

#### Acceptance Criteria

1. WHEN レート制限チェックを実行 THEN Rate Limit Systemは構造化ログ形式（JSON）でログ出力しなければならない
2. WHEN レート制限チェックを実行 THEN Rate Limit Systemはログに以下のコンテキスト情報を含めなければならない: request_id, endpoint分類, IP, user_id, rate_limit_key, attempts, max_attempts, reset_at
3. WHEN レート制限超過が発生 THEN Rate Limit Systemは WARNING レベルで "Rate limit exceeded" メッセージをログ出力しなければならない
4. WHEN レート制限チェックを実行 THEN Rate Limit Systemはメトリクス `rate_limit.hit.{endpoint}` カウンターをインクリメントしなければならない
5. WHEN レート制限超過が発生 THEN Rate Limit Systemはメトリクス `rate_limit.blocked.{endpoint}` カウンターをインクリメントしなければならない
6. WHEN Redis障害によるスキップが発生 THEN Rate Limit Systemはメトリクス `rate_limit.failure` カウンターをインクリメントしなければならない
7. WHEN レート制限ストアへアクセス THEN Rate Limit Systemはメトリクス `rate_limit.store.{redis|array}.latency_ms` でレスポンスタイムを記録しなければならない
8. WHEN 構造化ログを出力 THEN Rate Limit Systemは非同期ログ出力を使用し、レート制限チェックのブロッキングを回避しなければならない

---

### Requirement 6: Laravel標準ThrottleRequests互換性
**Objective:** Laravel開発者として、既存のLaravel標準ミドルウェアと同じインターフェースでレート制限を利用したい。それにより、学習コストを最小化し、既存知識を活用できる。

#### Acceptance Criteria

1. WHEN `RouteServiceProvider` でレート制限を定義 THEN Rate Limit Systemは `RateLimiter::for('dynamic', function() {})` メソッドをサポートしなければならない
2. WHEN ルート定義で `->middleware('throttle:dynamic')` を指定 THEN Rate Limit Systemは動的レート制限ロジックを適用しなければならない
3. WHEN Laravel標準 `ThrottleRequests` ミドルウェアで生成されるHTTPヘッダー THEN Rate Limit Systemは同じヘッダー名（X-RateLimit-*）を使用しなければならない
4. WHEN Laravel標準 `RateLimiter` ファサードを使用 THEN Rate Limit Systemは `Cache::increment()` および `Cache::add()` メソッドを内部的に使用しなければならない
5. WHEN レート制限値を環境変数で設定 THEN Rate Limit Systemは `config/ratelimit.php` 経由で設定を読み込み、動的に適用しなければならない
6. WHEN 既存のミドルウェアスタックに統合 THEN Rate Limit Systemは `config/middleware.php` の `api` グループに配置可能でなければならない

---

### Requirement 7: DDD/クリーンアーキテクチャ準拠
**Objective:** アーキテクトとして、レート制限機能をDDD/クリーンアーキテクチャ原則に準拠して実装したい。それにより、テスタビリティ・保守性・拡張性を向上させる。

#### Acceptance Criteria

1. WHEN Domain層を実装 THEN Rate Limit Systemは Laravelフレームワークに依存しない（Carbon除く）ValueObjectsとして実装しなければならない
2. WHEN Domain層を実装 THEN Rate Limit Systemは以下のValueObjectsを定義しなければならない: `RateLimitRule` (制限ルール)、`RateLimitKey` (識別キー)、`RateLimitResult` (制限結果)
3. WHEN Application層を実装 THEN Rate Limit Systemは以下のコンポーネントを定義しなければならない: `RateLimitConfigManager`, `EndpointClassifier`, `KeyResolver`, `RateLimitService` (interface), `RateLimitMetrics` (interface)
4. WHEN Application層を実装 THEN Rate Limit Systemは Infrastructure層に依存してはならない（依存性逆転原則）
5. WHEN Infrastructure層を実装 THEN Rate Limit Systemは以下の実装を提供しなければならない: `LaravelRateLimiterStore` (Redis統合)、`FailoverRateLimitStore` (フェイルオーバー)、`LogMetrics` (メトリクス記録)
6. WHEN HTTP層を実装 THEN Rate Limit Systemは Application層のUseCaseを呼び出すのみで、ビジネスロジックを含んではならない
7. WHEN Architecture Testsを実行 THEN Rate Limit Systemは依存方向ルール（HTTP → Application → Domain ← Infrastructure）に違反してはならない
8. WHEN Domain層ValueObjectsを実装 THEN Rate Limit Systemは不変性（Immutable）を保証し、バリデーション（1 <= maxAttempts <= 10000）を実行しなければならない

---

### Requirement 8: 環境変数駆動設定
**Objective:** DevOpsエンジニアとして、環境変数でレート制限値を柔軟に変更したい。それにより、コード変更なしで本番環境の制限値を調整できる。

#### Acceptance Criteria

1. WHEN `.env` ファイルでレート制限設定を定義 THEN Rate Limit Systemは以下の環境変数をサポートしなければならない: `RATELIMIT_CACHE_STORE`, `RATELIMIT_LOGIN_MAX_ATTEMPTS`, `RATELIMIT_API_MAX_ATTEMPTS`, `RATELIMIT_PUBLIC_MAX_ATTEMPTS`, `RATELIMIT_PROTECTED_MAX_ATTEMPTS`
2. WHEN `RATELIMIT_CACHE_STORE` 環境変数を設定 THEN Rate Limit Systemは `redis` または `array` ストアを選択的に使用しなければならない
3. WHEN 環境変数が未設定 THEN Rate Limit Systemはデフォルト値（ログインAPI: 5 req/10min、一般API: 60 req/min）を使用しなければならない
4. WHEN `.env.example` ファイルを提供 THEN Rate Limit Systemは全ての設定可能な環境変数のサンプル値とコメントを含めなければならない
5. WHEN `config/ratelimit.php` を実装 THEN Rate Limit Systemは環境変数を読み込み、型変換（string→int）とバリデーションを実行しなければならない
6. WHEN 本番環境でレート制限値を変更 THEN Rate Limit Systemはアプリケーション再起動なしで設定を反映しなければならない（`php artisan config:cache` 実行後）

---

### Requirement 9: パフォーマンス要件
**Objective:** API利用者として、レート制限チェックによるレイテンシ増加を最小限に抑えたい。それにより、快適なAPI利用体験を維持できる。

#### Acceptance Criteria

1. WHEN レート制限チェックを実行 THEN Rate Limit Systemは平均レスポンスタイムを5-7ms以内に抑えなければならない（P95: 10ms以下）
2. WHEN Redisへアクセス THEN Rate Limit Systemは平均応答時間を1-2ms以内に抑えなければならない（P95: 5ms以下）
3. WHEN レート制限チェックを実行 THEN Rate Limit Systemは全体APIレスポンスタイムへの影響を5%未満に抑えなければならない
4. WHEN Redis Pipelining機能が利用可能 AND バルクリクエスト処理時 THEN Rate Limit Systemは複数のレート制限チェックを1回のRedis通信にまとめなければならない
5. WHEN 頻繁にアクセスされるレート制限キー THEN Rate Limit Systemはキャッシュウォームアップ戦略を適用し、初回アクセス時のレイテンシを低減しなければならない
6. WHEN レート制限ストアへのアクセスが遅延 THEN Rate Limit Systemは5秒のタイムアウトを設定し、長時間ブロックしてはならない

---

### Requirement 10: テスト戦略
**Objective:** QAエンジニアとして、レート制限機能の正確性と堅牢性を包括的に検証したい。それにより、本番環境でのバグやセキュリティ脆弱性を防ぐ。

#### Acceptance Criteria

1. WHEN Unit Testsを実行（Domain層） THEN Rate Limit Systemは95%以上のカバレッジを達成しなければならない
2. WHEN Unit Testsを実行（Application層） THEN Rate Limit Systemは90%以上のカバレッジを達成しなければならない
3. WHEN Feature Testsを実行（HTTP層統合） THEN Rate Limit Systemはエンドポイント別（公開・保護 × 認証・非認証）のレート制限動作を検証しなければならない
4. WHEN Feature Testsを実行 THEN Rate Limit Systemは60リクエスト成功後の61リクエスト目で429レスポンスを返却することを検証しなければならない
5. WHEN Feature Testsを実行 THEN Rate Limit Systemは429レスポンスで `X-RateLimit-*` ヘッダーと `Retry-After` ヘッダーが正しく返却されることを検証しなければならない
6. WHEN Redis障害シミュレーションテストを実行 THEN Rate Limit SystemはセカンダリストアへのフェイルオーバーとRedis復旧後のロールバックを検証しなければならない
7. WHEN Architecture Testsを実行 THEN Rate Limit Systemは依存方向ルール違反とレイヤー分離違反を検出しなければならない
8. WHEN E2E Testsを実行 THEN Rate Limit Systemは実際のRedis環境で完全なレート制限フローを検証しなければならない
9. WHEN 全テストスイートを実行 THEN Rate Limit Systemは85%以上の総合テストカバレッジを達成しなければならない
10. WHEN CI/CD環境でテストを実行 THEN Rate Limit Systemは `RATELIMIT_CACHE_STORE=array` 設定でRedis依存を排除し、高速テスト実行を可能にしなければならない

---

### Requirement 11: ドキュメント整備
**Objective:** 開発チームとして、レート制限機能の実装詳細・運用手順・トラブルシューティングを理解したい。それにより、新規メンバーのオンボーディングと障害対応を迅速化できる。

#### Acceptance Criteria

1. WHEN `docs/RATELIMIT_IMPLEMENTATION.md` を作成 THEN Rate Limit Systemは以下を含めなければならない: 実装アーキテクチャ図、DDD層別の責務説明、クラス図、シーケンス図
2. WHEN `docs/RATELIMIT_OPERATIONS.md` を作成 THEN Rate Limit Systemは以下を含めなければならない: 本番環境でのレート制限値変更手順、Redis障害時の対応フロー、メトリクス監視項目、アラート設定例
3. WHEN `docs/RATELIMIT_TROUBLESHOOTING.md` を作成 THEN Rate Limit Systemは以下を含めなければならない: よくある問題と解決策、Redis接続エラー対処法、レート制限誤検知の調査方法、ログ分析手順
4. WHEN API仕様書を更新 THEN Rate Limit Systemは追加されたHTTPレスポンスヘッダー（`X-RateLimit-Policy`, `X-RateLimit-Key`）の説明を含めなければならない
5. WHEN `.env.example` を更新 THEN Rate Limit Systemは全てのレート制限関連環境変数にコメントで説明を追加しなければならない
6. WHEN READMEを更新 THEN Rate Limit Systemはレート制限機能の概要とクイックスタートガイドを含めなければならない

---

### Requirement 12: CI/CD統合
**Objective:** 開発チームとして、レート制限機能の品質をCI/CDパイプラインで自動検証したい。それにより、コード品質低下やアーキテクチャ違反を早期検知できる。

#### Acceptance Criteria

1. WHEN GitHub Actionsワークフローを実行 THEN Rate Limit Systemは `php-quality.yml` でLaravel Pint + Larastan Level 8チェックを実行しなければならない
2. WHEN GitHub Actionsワークフローを実行 THEN Rate Limit Systemは `test.yml` でPest 4テストスイート（Unit/Feature/Architecture Tests）を実行しなければならない
3. WHEN GitHub Actionsワークフローを実行 THEN Rate Limit Systemはテストカバレッジレポートを生成し、85%以上を確認しなければならない
4. WHEN Pull Request作成時 THEN Rate Limit Systemは全CI/CDワークフローが成功することを確認しなければならない
5. WHEN Architecture Testsが失敗 THEN Rate Limit Systemはマージをブロックし、アーキテクチャ違反の詳細をPRコメントで報告しなければならない
6. WHEN テストカバレッジが85%未満 THEN Rate Limit Systemはワークフローを失敗させ、カバレッジ向上を促さなければならない

---

## Out of Scope (Phase 1では含まれない範囲)

本要件定義は **Phase 1: 基本レート制限強化** に焦点を当てており、以下は今後のPhaseで対応予定です：

- **Phase 2: モニタリング・アラート統合** - Prometheus/StatsD統合、Grafanaダッシュボード、Slack/PagerDuty通知
- **Phase 3: 動的設定変更** - レート制限値のランタイム変更API、Admin App UI統合、設定変更履歴
- **Out of Scope** - WAF統合、API Gateway レベルのレート制限、Machine Learning ベースの異常検知、地理的分散キャッシュ

---

## Performance Metrics (定量的目標)

| メトリクス | 目標値 | 測定方法 |
|-----------|--------|----------|
| **レート制限チェック平均レスポンスタイム** | 5-7ms | `rate_limit.store.redis.latency_ms` メトリクス |
| **Redis応答時間（P95）** | 5ms以下 | Redisモニタリング、Laravelデバッグバー |
| **APIレスポンスタイムへの影響** | 5%未満 | Before/Afterベンチマーク測定 |
| **テストカバレッジ（総合）** | 85%以上 | Pest `--coverage` オプション |
| **Domain層カバレッジ** | 95%以上 | Pest `--coverage` でDomain層のみ指定 |
| **Application層カバレッジ** | 90%以上 | Pest `--coverage` でApplication層のみ指定 |
| **Redis障害検知時間** | 30秒以内 | フェイルオーバーテストで測定 |
| **Redis復旧検知時間** | 30秒以内 | ロールバックテストで測定 |

---

## Glossary (用語集)

| 用語 | 定義 |
|------|------|
| **Rate Limit System** | 本要件で実装するレート制限機能全体の総称 |
| **エンドポイント分類** | 認証状態（未認証/認証済み）× 機密性（公開/保護）の2軸分類体系 |
| **レート制限キー** | ユーザー/IPを識別するための一意なキー（例: `rate_limit:api_public:192.168.1.1`） |
| **EARS形式** | Easy Approach to Requirements Syntax - 要件記述の標準フォーマット |
| **フェイルオーバー** | Redis障害時にArray/File Cacheへ自動切り替えする仕組み |
| **ロールバック** | Redis復旧時にプライマリストアへ自動復帰する仕組み |
| **構造化ログ** | JSON形式で出力され、ログ分析ツールで機械的に処理可能なログ |
| **セカンダリストア** | Redis障害時のバックアップキャッシュストア（Array Cache/File Cache） |
| **Personal Access Token** | Laravel Sanctumで発行されるAPIトークン |

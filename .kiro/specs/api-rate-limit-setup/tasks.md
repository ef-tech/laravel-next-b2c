# Implementation Plan: APIレート制限設定強化

## Phase 1: Domain層ValueObjects実装

- [x] 1. レート制限ルールを表現する不変オブジェクトを実装
- [x] 1.1 エンドポイント分類とレート制限値を保持する値オブジェクトを作成
  - エンドポイントタイプ（公開・未認証、保護・未認証、公開・認証済み、保護・認証済み）を表現
  - 最大試行回数（1-10000の範囲）と制限時間（1-60分の範囲）を保持
  - バリデーションロジックを実装（範囲外の値は例外をスロー）
  - 秒単位の制限時間を取得するメソッドを提供
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 1.6, 7.1, 7.2, 7.8_

- [x] 1.2 レート制限識別キーを表現する値オブジェクトを作成
  - キー文字列（`rate_limit:{endpoint_type}:{identifier}`形式）を保持
  - SHA-256ハッシュ化されたキー値を生成するメソッドを実装
  - キー文字列のバリデーション（プレフィックス確認、最大長255文字）
  - プライバシー保護のためハッシュ値のみを外部公開
  - _Requirements: 2.5, 2.6, 7.1, 7.2, 7.8_

- [x] 1.3 レート制限チェック結果を表現する値オブジェクトを作成
  - 許可/拒否フラグ、試行回数、残り回数、リセット時刻を保持
  - 許可時と拒否時の異なるファクトリメソッドを提供
  - リセット時刻をUNIXタイムスタンプ形式で取得するメソッドを実装
  - 許可状態と拒否状態を判定するメソッドを提供
  - _Requirements: 3.1, 3.2, 3.3, 7.1, 7.2, 7.8_

- [x] 1.4 Domain層ValueObjectsの包括的なUnit Testsを実装
  - レート制限ルールの正常系テスト（有効な値での生成）
  - レート制限ルールの異常系テスト（範囲外の値、空文字列）
  - レート制限ルールの境界値テスト（1, 10000, 1, 60）
  - レート制限キーの正常系テスト（有効なキー形式）
  - レート制限キーの異常系テスト（プレフィックス不正、最大長超過）
  - レート制限キーのSHA-256ハッシュ一貫性テスト
  - レート制限結果の許可/拒否状態テスト
  - レート制限結果のリセット時刻計算テスト
  - テストカバレッジ95%以上を達成（全49テスト、96アサーション成功）
  - _Requirements: 10.1, 10.9_

---

## Phase 2: Application層サービス実装

- [x] 2. エンドポイント分類システムを実装
- [x] 2.1 設定ファイルからレート制限ルールを読み込む管理サービスを作成
  - 設定ファイル（`config/ratelimit.php`）からエンドポイント別ルールを読み込み
  - 環境変数による設定値の上書きをサポート
  - 型変換（string→int）とバリデーションを実行
  - デフォルトルール（最も厳格な制限：30 req/min）を提供
  - 設定値をキャッシュして複数回の読み込みを防止
  - _Requirements: 8.1, 8.2, 8.3, 8.5, 7.3, 7.4_

- [x] 2.2 HTTPリクエストを認証状態と機密性で分類するサービスを実装
  - リクエストの認証状態（未認証/認証済み）を判定
  - エンドポイントの機密性（公開/保護）をルート名パターンマッチングで判定
  - 4種類のエンドポイント分類に基づいて適切なレート制限ルールを返却
  - 保護ルートパターンを設定ファイルから読み込み
  - 分類不明時はデフォルトルール（30 req/min）を適用
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 1.6, 7.3, 7.4_

- [x] 2.3 レート制限識別キーを解決する戦略を実装
  - 認証済みユーザーのUser IDを優先的に使用
  - User ID取得不可時はPersonal Access Token IDを使用
  - 未認証リクエストはIPアドレスを使用
  - 保護エンドポイント（ログイン等）ではIP + Emailの組み合わせを使用
  - Emailアドレスは必ずSHA-256ハッシュ化して使用
  - キー文字列を`rate_limit:{endpoint_type}:{identifier}`形式で構成
  - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 2.6, 7.3, 7.4_

- [x] 2.4 レート制限チェックを実行するサービスインターフェースを定義
  - レート制限キーとルールを受け取り、結果を返すメソッドシグネチャを定義
  - Infrastructure層での実装を想定したインターフェース設計
  - 依存性逆転原則に基づき、Application層はInfrastructure層に依存しない設計
  - _Requirements: 7.3, 7.4, 7.5_
  - ✅ **実装完了**: RateLimitService interface（checkLimit, resetLimit, getStatus）

- [x] 2.5 レート制限メトリクスを記録するサービスインターフェースを定義
  - ヒット数、ブロック数、障害数、レイテンシを記録するメソッドシグネチャを定義
  - 非同期・非ブロッキングな記録を想定したインターフェース設計
  - Infrastructure層での実装を想定したインターフェース設計
  - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5, 5.6, 5.7, 5.8, 7.3, 7.4, 7.5_
  - ✅ **実装完了**: RateLimitMetrics interface（recordHit, recordBlock, recordFailure, recordLatency）

- [x] 2.6 Application層サービスの包括的なUnit Testsを実装
  - 設定管理サービスの設定読み込みテスト
  - 設定管理サービスのデフォルトルール取得テスト
  - エンドポイント分類サービスの4種類分類テスト
  - エンドポイント分類サービスの保護ルートパターンマッチングテスト
  - エンドポイント分類サービスのデフォルト分類テスト
  - キー解決サービスのUser ID/Token ID/IPフォールバックチェーンテスト
  - キー解決サービスのIP + Email組み合わせテスト
  - キー解決サービスのSHA-256ハッシュ化テスト
  - テストカバレッジ90%以上を達成
  - _Requirements: 10.2, 10.9_
  - ✅ **実装完了**: 全50テスト成功（102アサーション）

---

## Phase 3: Infrastructure層ストア実装

- [ ] 3. レート制限ストアとメトリクス記録を実装
- [ ] 3.1 Redisを使用したレート制限ストアを実装
  - Laravel Cache Facadeを使用してRedis接続を確立
  - 原子的カウント操作（`Cache::increment()`と`Cache::add()`の組み合わせ）を実装
  - TTL（Time To Live）を計算してキーの自動削除を設定
  - リセット時刻をCarbon DateTimeオブジェクトで計算
  - Application層のRateLimitServiceインターフェースを実装
  - Redis障害時は`RedisException`をスロー
  - _Requirements: 6.1, 6.2, 6.3, 6.4, 9.1, 9.2, 9.3, 7.5_

- [ ] 3.2 Redis障害時のフェイルオーバーストアを実装
  - プライマリストア（Redis）とセカンダリストア（Array/File Cache）を管理
  - Redis障害検知時に`RedisException`をキャッチしてセカンダリストアへ自動切り替え
  - 30秒間隔でRedis疎通確認（PING）を実行
  - Redis復旧時にプライマリストアへ自動ロールバック
  - セカンダリストア使用時はレート制限値を2倍に緩和（ユーザー影響最小化）
  - フェイルオーバー時とロールバック時に構造化ログを記録
  - フェイルオーバー時にメトリクスを記録（`rate_limit.failure`カウンター）
  - Application層のRateLimitServiceインターフェースを実装
  - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 4.7, 4.8, 7.5_

- [ ] 3.3 構造化ログでメトリクスを記録する実装を作成
  - Laravel標準Logファサードを使用してJSON形式ログを出力
  - `rate_limit`チャネルに専用ログを記録
  - ヒット時はINFOレベル、ブロック時とRedis障害時はWARNINGレベル
  - ログコンテキストに`request_id`、`endpoint_type`、`user_id`、`ip_address`、`attempts`、`max_attempts`、`reset_at`を含める
  - 非同期ログ出力を使用してレート制限チェックのブロッキングを回避
  - Application層のRateLimitMetricsインターフェースを実装
  - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5, 5.6, 5.7, 5.8, 7.5_

- [ ] 3.4 Infrastructure層ストアのIntegration Testsを実装
  - Redisストアの原子的カウント操作テスト（`Cache::increment()`と`Cache::add()`）
  - RedisストアのTTL計算とリセット時刻テスト
  - Redisストアのレート制限超過テスト（60リクエスト目で拒否）
  - フェイルオーバーストアのRedis障害シミュレーションテスト
  - フェイルオーバーストアのセカンダリストアへの自動切り替えテスト
  - フェイルオーバーストアの30秒間隔ヘルスチェックテスト
  - フェイルオーバーストアのRedis復旧後の自動ロールバックテスト
  - フェイルオーバーストアのレート制限値緩和（maxAttempts × 2）テスト
  - 構造化ログ記録のメトリクス出力テスト
  - テストカバレッジ85%以上を達成
  - _Requirements: 10.3, 10.6, 10.8, 10.9_

---

## Phase 4: HTTP層ミドルウェア拡張

- [ ] 4. DynamicRateLimitミドルウェアをApplication層サービスで拡張
- [ ] 4.1 既存DynamicRateLimitミドルウェアをApplication層サービス統合形式に変更
  - コンストラクタでApplication層サービス（EndpointClassifier、KeyResolver、RateLimitService、RateLimitMetrics）を注入
  - `handle()`メソッドでエンドポイント分類サービスを呼び出し
  - `handle()`メソッドでキー解決サービスを呼び出し
  - `handle()`メソッドでレート制限サービスを呼び出してチェックを実行
  - レート制限超過時にメトリクスサービスでブロック数を記録
  - レート制限許可時にメトリクスサービスでヒット数を記録
  - 既存の`config/ratelimit.php`設定ファイル統合を維持
  - _Requirements: 6.1, 6.2, 6.6, 7.6_

- [ ] 4.2 HTTPレスポンスヘッダーを強化
  - `X-RateLimit-Limit`ヘッダーに最大試行回数を設定
  - `X-RateLimit-Remaining`ヘッダーに残り試行回数を設定
  - `X-RateLimit-Reset`ヘッダーにUNIXタイムスタンプ形式のリセット時刻を設定
  - `X-RateLimit-Policy`ヘッダーに適用されたエンドポイント分類を設定
  - `X-RateLimit-Key`ヘッダーにSHA-256ハッシュ化された識別キーを設定
  - レート制限超過時（429レスポンス）に`Retry-After`ヘッダーを追加
  - 429レスポンスのJSONボディに`{"message": "Too Many Requests", "retry_after": {seconds}}`形式のエラー詳細を含める
  - Laravel標準ThrottleRequestsミドルウェアと同じヘッダー名を使用
  - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 3.7, 6.3_

- [ ] 4.3 RouteServiceProviderとの統合を確認
  - `RateLimiter::for('dynamic', function() {})`メソッドのサポートを確認
  - ルート定義で`->middleware('throttle:dynamic')`指定が動作することを確認
  - Laravel標準`Cache::increment()`と`Cache::add()`の使用を確認
  - `config/middleware.php`の`api`グループへの配置を確認
  - _Requirements: 6.1, 6.2, 6.4, 6.5, 6.6_

- [ ] 4.4 HTTP層統合のFeature Testsを実装
  - 公開・未認証エンドポイントのレート制限テスト（60 req/min、IPベース）
  - 保護・未認証エンドポイント（ログイン）のレート制限テスト（5 req/10min、IP + Emailベース）
  - 公開・認証済みエンドポイントのレート制限テスト（120 req/min、User IDベース）
  - 保護・認証済みエンドポイントのレート制限テスト（30 req/min、User IDベース）
  - HTTPヘッダー（`X-RateLimit-*`、`Retry-After`）の正確性テスト
  - 429レスポンスのJSONボディ検証テスト
  - Laravel標準ThrottleRequests互換性テスト
  - _Requirements: 10.3, 10.4, 10.5_

---

## Phase 5: 設定ファイル拡張とDIコンテナ設定

- [ ] 5. 設定ファイルと環境変数を拡張
- [ ] 5.1 `config/ratelimit.php`設定ファイルを拡張
  - 4種類のエンドポイント分類（`public_unauthenticated`、`protected_unauthenticated`、`public_authenticated`、`protected_authenticated`）を追加
  - 各エンドポイント分類に対応する環境変数（`RATELIMIT_PUBLIC_UNAUTHENTICATED_REQUESTS`等）をサポート
  - `protected_routes`配列を追加（保護ルートパターンマッチング用）
  - デフォルト値を設定（ログインAPI: 5 req/10min、一般API: 60 req/min）
  - 既存の設定構造を維持しつつ、新規設定を追加
  - _Requirements: 8.1, 8.3, 8.4, 8.5_

- [ ] 5.2 `.env.example`ファイルを更新
  - 全てのレート制限関連環境変数のサンプル値を追加
  - 各環境変数にコメントで説明を追加
  - `RATELIMIT_CACHE_STORE`（redis/array切り替え）の説明を追加
  - エンドポイント分類別の環境変数（`RATELIMIT_PUBLIC_UNAUTHENTICATED_REQUESTS`等）を追加
  - 保護ルートパターン設定の説明を追加
  - _Requirements: 8.1, 8.4_

- [ ] 5.3 DIコンテナにApplication層サービスを登録
  - ServiceProviderでApplication層サービス（EndpointClassifier、KeyResolver、RateLimitConfigManager）をシングルトン登録
  - ServiceProviderでInfrastructure層サービス（LaravelRateLimiterStore、FailoverRateLimitStore、LogMetrics）をシングルトン登録
  - RateLimitServiceインターフェースにFailoverRateLimitStoreを紐付け
  - RateLimitMetricsインターフェースにLogMetricsを紐付け
  - DynamicRateLimitミドルウェアでコンストラクタインジェクションが動作することを確認
  - _Requirements: 7.3, 7.4, 7.5, 7.6_

---

## Phase 6: Architecture TestsとE2E Tests実装

- [ ] 6. DDD/クリーンアーキテクチャ準拠とE2Eテストを検証
- [ ] 6.1 Architecture Testsを実装してDDD原則準拠を自動検証
  - Domain層がLaravelフレームワークに依存しないことを検証（Carbon除く）
  - Domain層が`Illuminate\`名前空間を使用しないことを検証
  - Application層がInfrastructure層に依存しないことを検証（依存性逆転原則）
  - Application層が`Illuminate\Support\Facades\Cache`を使用しないことを検証
  - HTTP層がApplication層のみに依存し、Infrastructure層を直接呼び出さないことを検証
  - Infrastructure層がDomain/Application層のインターフェースを実装することを検証
  - 命名規約検証（ValueObject、Service、Repository等）
  - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.5, 7.6, 7.7, 10.7_

- [ ] 6.2 E2E Testsを実装して完全なレート制限フローを検証
  - Docker Compose環境でRedis（13379ポート）を起動
  - 実際のHTTPリクエストでレート制限チェックを実行
  - 60リクエスト成功後の61リクエスト目で429レスポンスが返却されることを検証
  - HTTPヘッダー（`X-RateLimit-*`、`Retry-After`）が正しく返却されることを検証
  - Redis障害シミュレーション（Redisコンテナ停止）を実行
  - セカンダリストアへのフェイルオーバーを検証
  - レート制限が継続動作することを検証
  - Redis復旧（Redisコンテナ再起動）を実行
  - 30秒後のヘルスチェック実行を検証
  - プライマリストアへのロールバックを検証
  - _Requirements: 10.6, 10.8_

- [ ] 6.3 テストカバレッジを検証して85%以上達成を確認
  - Pest `--coverage`オプションで全体カバレッジを測定
  - Domain層カバレッジ95%以上を確認
  - Application層カバレッジ90%以上を確認
  - Infrastructure層カバレッジ85%以上を確認
  - カバレッジレポートをCI/CDワークフローで生成
  - カバレッジが85%未満の場合はワークフローを失敗させる
  - _Requirements: 10.1, 10.2, 10.9, 12.3, 12.6_

---

## Phase 7: ドキュメント整備とCI/CD統合

- [ ] 7. ドキュメント作成とCI/CD統合を完了
- [ ] 7.1 実装アーキテクチャドキュメントを作成
  - DDD 4層構造アーキテクチャ図（Mermaid形式）を含める
  - Domain層の責務とValueObjects一覧を記載
  - Application層の責務とサービス一覧を記載
  - Infrastructure層の責務とストア実装一覧を記載
  - HTTP層の責務とミドルウェア拡張内容を記載
  - シーケンス図（レート制限チェックフロー、フェイルオーバーフロー）を含める
  - クラス図（ValueObjects、サービスインターフェース）を含める
  - _Requirements: 11.1_

- [ ] 7.2 運用手順ドキュメントを作成
  - 本番環境でのレート制限値変更手順を記載
  - Redis障害時の対応フロー（自動フェイルオーバーとロールバック）を記載
  - メトリクス監視項目（`rate_limit.hit`、`rate_limit.blocked`、`rate_limit.failure`）を記載
  - アラート設定例（Prometheus/StatsD統合準備）を記載
  - 環境変数設定のベストプラクティスを記載
  - _Requirements: 11.2_

- [ ] 7.3 トラブルシューティングドキュメントを作成
  - よくある問題と解決策を記載
  - Redis接続エラー対処法を記載
  - レート制限誤検知の調査方法を記載
  - ログ分析手順（構造化ログの活用方法）を記載
  - セカンダリストア使用時の制限値緩和について記載
  - _Requirements: 11.3_

- [ ] 7.4 API仕様書を更新
  - 追加されたHTTPレスポンスヘッダー（`X-RateLimit-Policy`、`X-RateLimit-Key`）の説明を記載
  - 429レスポンスのJSONボディ形式を記載
  - エンドポイント分類別のレート制限値を記載
  - レート制限超過時のリトライ戦略を記載
  - _Requirements: 11.4_

- [ ] 7.5 GitHub ActionsワークフローでCI/CD統合を確認
  - `php-quality.yml`ワークフローでLaravel Pint + Larastan Level 8チェックを実行
  - `test.yml`ワークフローでPest 4テストスイート（Unit/Feature/Architecture Tests）を実行
  - テストカバレッジレポートを生成し、85%以上を確認
  - Pull Request作成時に全CI/CDワークフローが成功することを確認
  - Architecture Testsが失敗した場合はマージをブロック
  - テストカバレッジが85%未満の場合はワークフローを失敗させる
  - _Requirements: 12.1, 12.2, 12.3, 12.4, 12.5, 12.6_

---

## Requirements Coverage Matrix

| Requirement | 関連タスク |
|-------------|-----------|
| **Req 1**: エンドポイント分類システム | 1.1, 2.1, 2.2, 4.1, 4.4, 5.1 |
| **Req 2**: レート制限キー解決戦略 | 1.2, 2.3, 4.1, 4.4 |
| **Req 3**: HTTPレスポンスヘッダー強化 | 1.3, 4.2, 4.4, 7.4 |
| **Req 4**: Redis障害時フェイルオーバー戦略 | 3.2, 3.4, 6.2 |
| **Req 5**: 構造化ログ・メトリクス統合 | 2.5, 3.3, 3.4, 4.1 |
| **Req 6**: Laravel標準ThrottleRequests互換性 | 3.1, 4.1, 4.3, 4.4 |
| **Req 7**: DDD/クリーンアーキテクチャ準拠 | 1.1-1.4, 2.1-2.6, 3.1-3.3, 4.1, 5.3, 6.1 |
| **Req 8**: 環境変数駆動設定 | 2.1, 5.1, 5.2 |
| **Req 9**: パフォーマンス要件 | 3.1, 3.4 |
| **Req 10**: テスト戦略 | 1.4, 2.6, 3.4, 4.4, 6.1, 6.2, 6.3 |
| **Req 11**: ドキュメント整備 | 7.1, 7.2, 7.3, 7.4 |
| **Req 12**: CI/CD統合 | 6.3, 7.5 |

---

## Implementation Notes

### 技術的考慮事項
- **Phase 1-2**: Domain層とApplication層はLaravelフレームワーク非依存（Carbonのみ許容）、テスタビリティを最優先
- **Phase 3**: Infrastructure層でRedis接続とフェイルオーバー戦略を実装、30秒間隔ヘルスチェックを忘れずに
- **Phase 4**: 既存DynamicRateLimitミドルウェア(135行)を拡張、Application層サービスを呼び出す薄いレイヤーに変更
- **Phase 5**: 環境変数駆動設定を拡張、既存設定構造を維持しつつ新規設定を追加
- **Phase 6**: Architecture Testsで依存方向ルール違反を自動検知、E2E TestsでRedis障害シミュレーションを実行
- **Phase 7**: 包括的ドキュメントを作成、CI/CDワークフローで品質を自動検証

### パフォーマンス目標
- レート制限チェック平均レスポンスタイム: 5-7ms以内
- Redis応答時間(P95): 5ms以下
- APIレスポンスタイムへの影響: 5%未満
- テストカバレッジ: 全体85%以上、Domain層95%、Application層90%

### 次のステップ
実装タスクの承認後、以下のコマンドで実装を開始してください:

```bash
/kiro:spec-impl api-rate-limit-setup          # 全タスク実行
/kiro:spec-impl api-rate-limit-setup 1.1      # 特定タスク実行
/kiro:spec-impl api-rate-limit-setup 1,2,3    # 複数タスク実行
```

各フェーズを段階的に実装し、Unit Tests、Integration Tests、Feature Tests、Architecture Tests、E2E Testsを通過させてください。

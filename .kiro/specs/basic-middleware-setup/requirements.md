# Requirements Document

## Introduction

Laravel Next.js B2C Application Templateに、API専用最適化されたLaravel 12バックエンド向けの統一ミドルウェアスタックを確立します。本機能は、認証・認可・ログ記録・レート制限・監査・キャッシュ制御などの横断的関心事を標準化し、セキュリティ、パフォーマンス、可観測性を包括的に向上させます。

既存のLaravel Sanctum認証、セキュリティヘッダー（CSP、CORS）、DDD/クリーンアーキテクチャとシームレスに統合し、HTTP層実装とApplication層DIによる適切な責務分離を実現します。12種類のミドルウェアと6種類のミドルウェアグループにより、APIエンドポイントごとに最適な横断的機能を提供します。

**ビジネス価値:**
- 認証・認可の一貫性向上によるセキュリティ強化
- 構造化ログとパフォーマンスメトリクスによる可観測性向上
- レート制限とIdempotencyによる悪用防止とシステム保護
- 監査証跡による重要操作の追跡可能性確保
- キャッシュ制御とETagによるパフォーマンス最適化
- 運用時のトラブルシューティング効率化

## Requirements

### Requirement 1: リクエストトレーシングとロギング
**Objective:** As a システム運用者, I want 全てのAPIリクエストに一意なIDを付与し、構造化ログとして記録する機能, so that 分散システム全体でリクエストを追跡し、問題の迅速な特定と解決が可能になる

#### Acceptance Criteria

1. WHEN APIリクエストを受信する THEN Laravel APIサービス SHALL UUIDv4形式の一意なリクエストIDを生成する
2. WHEN リクエストIDを生成する THEN Laravel APIサービス SHALL `X-Request-Id`ヘッダーをリクエストとレスポンスの両方に設定する
3. WHEN クライアントが`X-Request-Id`ヘッダーを送信する THEN Laravel APIサービス SHALL 既存のリクエストIDを継承し、新規生成せずに使用する
4. WHEN APIリクエストを受信する AND `X-Correlation-Id`ヘッダーが存在する THEN Laravel APIサービス SHALL 分散トレーシング用のCorrelation IDを継承する
5. IF `X-Correlation-Id`ヘッダーが存在しない THEN Laravel APIサービス SHALL 新規Correlation IDを生成する
6. WHEN APIリクエストを受信する AND W3C Trace Context仕様の`traceparent`ヘッダーが存在する THEN Laravel APIサービス SHALL トレースコンテキストを継承する
7. WHEN リクエスト処理を開始する THEN Laravel APIサービス SHALL リクエストID、Correlation ID、トレースコンテキストをログコンテキストに追加する
8. WHEN APIリクエスト処理が完了する THEN Laravel APIサービス SHALL JSON構造化ログとして以下のフィールドを記録する: `request_id`, `correlation_id`, `user_id`, `method`, `url`, `status`, `duration_ms`, `ip`, `user_agent`, `timestamp`
9. WHEN ログに機密データ（パスワード、トークン等）が含まれる THEN Laravel APIサービス SHALL 自動的にマスキング処理を適用する
10. WHEN APIリクエスト処理が完了する THEN Laravel APIサービス SHALL 非同期（`terminate`メソッド使用）でログ出力を実行し、レスポンス時間への影響を最小化する
11. WHEN ログ出力を実行する THEN Laravel APIサービス SHALL 専用ログチャンネル（`middleware`チャンネル）に出力する
12. WHEN ログローテーション期間が経過する THEN Laravel APIサービス SHALL 30日間のログ保持期間後に自動削除する

### Requirement 2: パフォーマンスメトリクス収集と監視
**Objective:** As a 開発チーム, I want APIリクエストのパフォーマンスメトリクスをリアルタイムで収集する機能, so that パフォーマンスボトルネックの特定と継続的な改善が可能になる

#### Acceptance Criteria

1. WHEN APIリクエスト処理を開始する THEN Laravel APIサービス SHALL リクエスト開始時刻をマイクロ秒精度で記録する
2. WHEN APIリクエスト処理が完了する THEN Laravel APIサービス SHALL レスポンス時間をマイクロ秒精度で測定する
3. WHEN APIリクエスト処理が完了する THEN Laravel APIサービス SHALL ピークメモリ使用量を記録する
4. WHEN APIリクエスト処理が完了する THEN Laravel APIサービス SHALL データベースクエリ実行回数をカウントする
5. WHEN パフォーマンスメトリクスを収集する THEN Laravel APIサービス SHALL 専用ログチャンネル（`monitoring`チャンネル）に出力する
6. WHEN レスポンス時間が設定された閾値（デフォルト: 200ms）を超過する THEN Laravel APIサービス SHALL アラートログを記録する
7. WHEN パフォーマンスメトリクスを出力する THEN Laravel APIサービス SHALL パーセンタイル値（P50, P90, P95, P99）の計算を可能にする形式で記録する
8. WHEN メトリクス収集処理を実行する THEN Laravel APIサービス SHALL 非同期（`terminate`メソッド使用）で実行し、レスポンス時間への影響を5ms未満に抑える

### Requirement 3: 動的レート制限とAPI保護
**Objective:** As a セキュリティ担当者, I want APIエンドポイントごとに動的なレート制限を適用する機能, so that ブルートフォース攻撃やDDoS攻撃からシステムを保護する

#### Acceptance Criteria

1. WHEN APIリクエストを受信する THEN Laravel APIサービス SHALL Redis統合によるレート制限を適用する
2. WHEN レート制限を適用する THEN Laravel APIサービス SHALL エンドポイントごとに異なる制限値（リクエスト数/分）を設定可能にする
3. WHEN レート制限キーを生成する THEN Laravel APIサービス SHALL `rate_limit:{endpoint}:{identifier}`形式のキーを使用する
4. WHEN レート制限識別子を決定する THEN Laravel APIサービス SHALL IP アドレス、ユーザーID、トークン、パスのいずれかまたは組み合わせを使用する
5. WHEN レート制限を適用する THEN Laravel APIサービス SHALL レスポンスヘッダーとして`X-RateLimit-Limit`、`X-RateLimit-Remaining`、`X-RateLimit-Reset`を設定する
6. WHEN レート制限を超過する THEN Laravel APIサービス SHALL HTTP 429 Too Many Requestsステータスコードを返す
7. WHEN ログインエンドポイントにアクセスする THEN Laravel APIサービス SHALL IPアドレス単位で5リクエスト/分の厳格な制限を適用する
8. WHEN 認証済みAPIエンドポイントにアクセスする THEN Laravel APIサービス SHALL ユーザーID単位で1000リクエスト/分の制限を適用する
9. WHEN 公開APIエンドポイントにアクセスする THEN Laravel APIサービス SHALL IPアドレス単位で100リクエスト/分の制限を適用する
10. WHEN Redisサービスがダウンしている THEN Laravel APIサービス SHALL レート制限をスキップし、サービス継続性を優先する
11. WHEN レート制限設定を変更する THEN Laravel APIサービス SHALL 環境変数または設定ファイル（`config/ratelimit.php`）での動的設定を可能にする

### Requirement 4: Sanctumトークン詳細検証と認証強化
**Objective:** As a セキュリティ担当者, I want Laravel Sanctumトークンの詳細な検証とライフサイクル管理を強化する機能, so that トークンベース認証のセキュリティと可用性を向上させる

#### Acceptance Criteria

1. WHEN 認証必須APIエンドポイントにアクセスする THEN Laravel APIサービス SHALL Sanctumトークンの有効期限を検証する
2. WHEN トークンが有効期限切れである THEN Laravel APIサービス SHALL HTTP 401 Unauthorizedステータスコードを返す
3. WHEN 有効なトークンでアクセスする THEN Laravel APIサービス SHALL トークンの`last_used_at`タイムスタンプを更新する
4. WHEN トークンAbilities（権限）が設定されている THEN Laravel APIサービス SHALL トークンに付与された権限を検証する準備をする
5. WHEN トークン検証に失敗する THEN Laravel APIサービス SHALL 詳細なエラーログを記録する（有効期限切れ、権限不足、無効トークン等）
6. WHEN 既存の`auth:sanctum`ミドルウェアと併用する THEN Laravel APIサービス SHALL 追加の詳細検証として動作する
7. WHEN トークンライフサイクル情報を更新する THEN Laravel APIサービス SHALL データベース負荷を最小化するため、バッチ更新を使用する

### Requirement 5: 権限ベースアクセス制御
**Objective:** As a システム管理者, I want ユーザーの権限に基づいたきめ細かなアクセス制御を実現する機能, so that 最小権限の原則に従ったセキュアなAPI設計が可能になる

#### Acceptance Criteria

1. WHEN 認証済みユーザーが権限必須エンドポイントにアクセスする THEN Laravel APIサービス SHALL ユーザーの権限を検証する
2. WHEN 権限検証を実行する THEN Laravel APIサービス SHALL Application層の`AuthorizationService`ポートを経由して権限判定を行う（DDD/クリーンアーキテクチャ準拠）
3. WHEN ユーザーが必要な権限を持たない THEN Laravel APIサービス SHALL HTTP 403 Forbiddenステータスコードを返す
4. WHEN 権限検証に成功する THEN Laravel APIサービス SHALL リクエスト処理を継続する
5. WHEN 管理者限定エンドポイントにアクセスする THEN Laravel APIサービス SHALL `admin`権限の保有を検証する
6. WHEN 権限検証結果をログに記録する THEN Laravel APIサービス SHALL ユーザーID、エンドポイント、要求権限、検証結果を記録する
7. WHEN Sanctum Abilitiesと統合する THEN Laravel APIサービス SHALL `abilities:...`形式でトークンに付与された権限を検証可能にする

### Requirement 6: 監査証跡と重要操作の記録
**Objective:** As a コンプライアンス担当者, I want 重要な操作の監査証跡を自動的に記録する機能, so that セキュリティインシデント調査とコンプライアンス要件の遵守が可能になる

#### Acceptance Criteria

1. WHEN 認証済みユーザーがデータ変更操作（POST/PUT/PATCH/DELETE）を実行する THEN Laravel APIサービス SHALL 監査イベントを記録する
2. WHEN 監査イベントを記録する THEN Laravel APIサービス SHALL 以下のフィールドを含める: `user_id`, `action`, `resource`, `changes`, `ip`, `timestamp`
3. WHEN 監査イベントを発火する THEN Laravel APIサービス SHALL Application層の`AuditService`ポートを経由してイベントを発火する（DDD/クリーンアーキテクチャ準拠）
4. WHEN 監査ログを記録する THEN Laravel APIサービス SHALL 非同期（`terminate`メソッド使用）で実行し、レスポンス時間への影響を最小化する
5. WHEN 監査ログを記録する THEN Laravel APIサービス SHALL 変更前後の差分（`changes`フィールド）を記録する
6. WHEN 監査ログを記録する THEN Laravel APIサービス SHALL 機密データをマスキングし、プライバシーを保護する
7. WHEN GETリクエスト（読み取り専用操作）を実行する THEN Laravel APIサービス SHALL 監査イベントを記録しない

### Requirement 7: Idempotency保証と重複防止
**Objective:** As a 外部システム連携担当者, I want Idempotency-Keyヘッダーによる冪等性保証機能, so that ネットワーク障害やリトライによる重複処理を防止する

#### Acceptance Criteria

1. WHEN クライアントがPOST/PUT/PATCH/DELETEリクエストを送信する AND `Idempotency-Key`ヘッダーを含む THEN Laravel APIサービス SHALL Idempotency検証を実行する
2. WHEN Idempotencyキーを受信する THEN Laravel APIサービス SHALL `idempotency:{key}:{user_id}`形式のキーでRedisに保存する
3. WHEN 同じIdempotencyキーで2回目のリクエストを受信する THEN Laravel APIサービス SHALL リクエストペイロードのSHA256指紋を比較する
4. IF ペイロード指紋が一致する THEN Laravel APIサービス SHALL キャッシュ済みレスポンスを返却し、処理を実行しない
5. IF ペイロード指紋が異なる THEN Laravel APIサービス SHALL HTTP 422 Unprocessable Entityステータスコードを返し、リクエストを拒否する
6. WHEN Idempotencyキーをキャッシュする THEN Laravel APIサービス SHALL TTL（有効期限）を24時間に設定する
7. WHEN 24時間経過後に同じキーでリクエストを受信する THEN Laravel APIサービス SHALL 新規リクエストとして処理する
8. WHEN Webhookエンドポイントにアクセスする THEN Laravel APIサービス SHALL Idempotency検証を必須とする

### Requirement 8: キャッシュ制御とHTTPキャッシング最適化
**Objective:** As a パフォーマンス担当者, I want GETエンドポイントに適切なキャッシュ制御ヘッダーを設定する機能, so that クライアント側キャッシングとCDN効率を最大化する

#### Acceptance Criteria

1. WHEN GETリクエストを処理する THEN Laravel APIサービス SHALL `Cache-Control`ヘッダーを設定する
2. WHEN 開発環境で動作する THEN Laravel APIサービス SHALL `Cache-Control: no-cache`を設定する
3. WHEN 本番環境で動作する THEN Laravel APIサービス SHALL エンドポイントごとの`max-age`値を設定する
4. WHEN キャッシュTTLを設定する THEN Laravel APIサービス SHALL `/api/health`エンドポイントに60秒、`/api/user`エンドポイントに300秒のTTLを設定する
5. WHEN `Cache-Control`ヘッダーを設定する THEN Laravel APIサービス SHALL `Expires`ヘッダーも併せて設定する
6. WHEN キャッシュ設定を変更する THEN Laravel APIサービス SHALL 環境変数（`CACHE_HEADERS_ENABLED`）で機能の有効/無効を切り替え可能にする
7. WHEN POST/PUT/PATCH/DELETEリクエストを処理する THEN Laravel APIサービス SHALL キャッシュヘッダーを設定しない

### Requirement 9: ETagによる条件付きGETサポート
**Objective:** As a パフォーマンス担当者, I want ETagヘッダーと条件付きGETリクエストをサポートする機能, so that 帯域幅を削減し、クライアント側のキャッシュ効率を向上させる

#### Acceptance Criteria

1. WHEN GETリクエストのレスポンスを生成する AND レスポンスボディサイズが1MB未満 THEN Laravel APIサービス SHALL レスポンスボディからSHA256ハッシュのETagを生成する
2. WHEN ETagを生成する THEN Laravel APIサービス SHALL `ETag`ヘッダーをレスポンスに設定する
3. WHEN クライアントが`If-None-Match`ヘッダーを送信する AND ETagが一致する THEN Laravel APIサービス SHALL HTTP 304 Not Modifiedステータスコードを返す
4. WHEN HTTP 304レスポンスを返す THEN Laravel APIサービス SHALL レスポンスボディを送信せず、ヘッダーのみ送信する
5. WHEN クライアントが`If-None-Match`ヘッダーを送信する AND ETagが異なる THEN Laravel APIサービス SHALL HTTP 200 OKステータスコードと完全なレスポンスボディを返す
6. WHEN ETag生成処理を実行する THEN Laravel APIサービス SHALL レスポンス時間への影響を10ms未満に抑える
7. WHEN レスポンスボディサイズが1MB以上 THEN Laravel APIサービス SHALL ETag生成をスキップする

### Requirement 10: JSON入出力の強制とAPI統一性
**Objective:** As a API設計者, I want 全てのAPIエンドポイントでJSON形式の入出力を強制する機能, so that API仕様の一貫性を保証し、クライアント実装を簡素化する

#### Acceptance Criteria

1. WHEN APIリクエストを受信する THEN Laravel APIサービス SHALL `Accept: application/json`ヘッダーを強制する
2. WHEN クライアントが`Accept: application/json`以外を送信する THEN Laravel APIサービス SHALL HTTP 406 Not Acceptableステータスコードを返す
3. WHEN POST/PUT/PATCHリクエストを受信する AND `Content-Type`がapplication/json以外 THEN Laravel APIサービス SHALL HTTP 415 Unsupported Media Typeステータスコードを返す
4. WHEN エラー応答を返す THEN Laravel APIサービス SHALL JSON形式のエラーメッセージを返す
5. WHEN 例外が発生する THEN Laravel APIサービス SHALL 統一されたJSON形式のエラー応答を返す

### Requirement 11: ミドルウェアグループ化とルート適用
**Objective:** As a バックエンド開発者, I want APIエンドポイントの特性に応じた適切なミドルウェアグループを適用する機能, so that エンドポイントごとに最適な横断的機能を提供する

#### Acceptance Criteria

1. WHEN 全てのAPIエンドポイントにアクセスする THEN Laravel APIサービス SHALL `api`グループミドルウェア（基底）を適用する
2. WHEN `api`グループミドルウェアを適用する THEN Laravel APIサービス SHALL 以下のミドルウェアを実行する: `TrimStrings`, `ConvertEmptyStringsToNull`, `SubstituteBindings`, `RequestLogging`, `PerformanceTracking`, `EnhancedRateLimit:api`, `SecurityHeaders`, `ContentSecurityPolicy`
3. WHEN 認証必須エンドポイントにアクセスする THEN Laravel APIサービス SHALL `auth`グループミドルウェアを適用する
4. WHEN `auth`グループミドルウェアを適用する THEN Laravel APIサービス SHALL `api`グループに加えて、`auth:sanctum`, `TokenValidation`, `AuditTrail`を実行する
5. WHEN 公開APIエンドポイント（認証不要）にアクセスする THEN Laravel APIサービス SHALL `guest`グループミドルウェアを適用する
6. WHEN `guest`グループミドルウェアを適用する THEN Laravel APIサービス SHALL `api`グループに加えて、`EnhancedRateLimit:public`を実行する
7. WHEN 内部/管理用エンドポイントにアクセスする THEN Laravel APIサービス SHALL `internal`グループミドルウェアを適用する
8. WHEN `internal`グループミドルウェアを適用する THEN Laravel APIサービス SHALL `api`グループに加えて、`auth:sanctum`, `PermissionCheck:admin`, `EnhancedRateLimit:strict`, `AuditTrail`を実行する
9. WHEN Webhookエンドポイントにアクセスする THEN Laravel APIサービス SHALL `webhook`グループミドルウェアを適用する
10. WHEN `webhook`グループミドルウェアを適用する THEN Laravel APIサービス SHALL `api`グループに加えて、`IdempotencyKey`, `EnhancedRateLimit:webhook`を実行する
11. WHEN 読み取り専用エンドポイントにアクセスする THEN Laravel APIサービス SHALL `readonly`グループミドルウェアを適用する
12. WHEN `readonly`グループミドルウェアを適用する THEN Laravel APIサービス SHALL `api`グループに加えて、`CacheHeaders`, `ETag`を実行する

### Requirement 12: グローバルミドルウェアと実行順序
**Objective:** As a システムアーキテクト, I want 全てのリクエストに適用されるグローバルミドルウェアと明確な実行順序を定義する機能, so that ミドルウェアチェーンの予測可能性と保守性を確保する

#### Acceptance Criteria

1. WHEN APIリクエストを受信する THEN Laravel APIサービス SHALL グローバルミドルウェアを以下の順序で実行する: `TrustProxies`, `ValidatePostSize`, `PreventRequestsDuringMaintenance`, `Cors`, `SetRequestId`, `CorrelationId`, `ForceJsonResponse`
2. WHEN グローバルミドルウェア実行後 THEN Laravel APIサービス SHALL ルート固有のミドルウェアグループを実行する
3. WHEN ミドルウェアチェーンを実行する THEN Laravel APIサービス SHALL 既存ミドルウェア（Sanctum、SecurityHeaders、CSP、CORS）との統合を保証する
4. WHEN `bootstrap/app.php`でミドルウェアを登録する THEN Laravel APIサービス SHALL グローバルミドルウェア、ミドルウェアグループ、ミドルウェアエイリアスを明確に定義する

### Requirement 13: 環境変数駆動設定と柔軟性
**Objective:** As a DevOps担当者, I want ミドルウェアの動作を環境変数で制御する機能, so that 開発/ステージング/本番環境で異なる設定を適用し、環境間の移行を簡素化する

#### Acceptance Criteria

1. WHEN ミドルウェアを初期化する THEN Laravel APIサービス SHALL 環境変数から設定を読み込む
2. WHEN レート制限設定を読み込む THEN Laravel APIサービス SHALL `config/ratelimit.php`ファイルから設定を取得する
3. WHEN キャッシュヘッダー設定を読み込む THEN Laravel APIサービス SHALL `CACHE_HEADERS_ENABLED`環境変数で機能の有効/無効を制御する
4. WHEN パフォーマンス監視設定を読み込む THEN Laravel APIサービス SHALL `config/monitoring.php`ファイルから閾値とパーセンタイル設定を取得する
5. WHEN ミドルウェア全体設定を読み込む THEN Laravel APIサービス SHALL `config/middleware.php`ファイルから共通設定を取得する
6. WHEN `.env.example`ファイルを提供する THEN Laravel APIサービス SHALL 全ての新規環境変数を記載し、コメントで説明を追加する

### Requirement 14: テスト環境と品質保証
**Objective:** As a QA担当者, I want ミドルウェアの包括的なテストスイートを提供する機能, so that 高品質なミドルウェア実装とリグレッション防止を保証する

#### Acceptance Criteria

1. WHEN 単体テストを実行する THEN Laravel APIサービス SHALL 各ミドルウェアの個別動作を検証する
2. WHEN 単体テストカバレッジを測定する THEN Laravel APIサービス SHALL 90%以上のカバレッジを達成する
3. WHEN 統合テストを実行する THEN Laravel APIサービス SHALL ミドルウェアチェーン全体の動作を検証する
4. WHEN 統合テストを実行する THEN Laravel APIサービス SHALL 6種類のミドルウェアグループ別の動作を検証する
5. WHEN E2Eテストを実行する THEN Laravel APIサービス SHALL Playwrightを使用して実際のHTTPリクエストで検証する
6. WHEN E2Eテストを実行する THEN Laravel APIサービス SHALL レート制限、Idempotency、パフォーマンスの動作を検証する
7. WHEN CI/CDパイプラインを実行する THEN GitHub Actions SHALL ミドルウェア専用テストワークフロー（`.github/workflows/middleware-tests.yml`）を実行する
8. WHEN ミドルウェアファイルを変更する THEN GitHub Actions SHALL 自動的にテストを実行し、品質を検証する

### Requirement 15: DDD/クリーンアーキテクチャ統合
**Objective:** As a ソフトウェアアーキテクト, I want ミドルウェアをDDD/クリーンアーキテクチャ原則に準拠して実装する機能, so that 適切な責務分離とテスタビリティを確保する

#### Acceptance Criteria

1. WHEN ミドルウェアを配置する THEN Laravel APIサービス SHALL HTTP層（`app/Http/Middleware/`）に配置する
2. WHEN Application層のサービスが必要な場合 THEN Laravel APIサービス SHALL ポート（インターフェース）を経由してDIする
3. WHEN 権限検証を実行する THEN Laravel APIサービス SHALL Application層の`AuthorizationService`ポートをDIする
4. WHEN 監査イベントを発火する THEN Laravel APIサービス SHALL Application層の`AuditService`ポートをDIする
5. WHEN ミドルウェアからDomain層に依存する THEN Laravel APIサービス SHALL 直接依存せず、Application層のユースケースまたはポート経由でアクセスする
6. WHEN ミドルウェアテストを実行する THEN Laravel APIサービス SHALL Application層ポートをモック化してテストする

### Requirement 16: 既存ミドルウェアとの統合
**Objective:** As a システム統合担当者, I want 既存のSanctum、SecurityHeaders、CSP、CORSミドルウェアとシームレスに統合する機能, so that 既存機能を維持しながら新機能を追加する

#### Acceptance Criteria

1. WHEN Sanctum認証ミドルウェア（`auth:sanctum`）と統合する THEN Laravel APIサービス SHALL `TokenValidation`ミドルウェアを追加の詳細検証として動作させる
2. WHEN SecurityHeadersミドルウェアと統合する THEN Laravel APIサービス SHALL `api`グループミドルウェアチェーンにSecurityHeadersを含める
3. WHEN ContentSecurityPolicyミドルウェアと統合する THEN Laravel APIサービス SHALL `api`グループミドルウェアチェーンにCSPを含める
4. WHEN CORSミドルウェアと統合する THEN Laravel APIサービス SHALL グローバルミドルウェアチェーンにCORSを含める
5. WHEN ミドルウェア実行順序を定義する THEN Laravel APIサービス SHALL 既存ミドルウェアとの競合を回避する適切な順序を設定する

### Requirement 17: ドキュメントと運用ガイド
**Objective:** As a システム運用者, I want ミドルウェアの実装ガイドと運用マニュアルを提供する機能, so that 効率的な運用と問題解決を支援する

#### Acceptance Criteria

1. WHEN 実装ガイドを提供する THEN Laravel APIサービス SHALL `backend/laravel-api/docs/middleware-implementation-guide.md`にミドルウェア一覧、責務、実装パターンを記載する
2. WHEN 運用マニュアルを提供する THEN Laravel APIサービス SHALL `backend/laravel-api/docs/middleware-operation-manual.md`に監視項目、ログ確認手順、トラブルシューティングを記載する
3. WHEN README.mdを更新する THEN Laravel APIサービス SHALL ミドルウェア設定概要、主要ミドルウェア説明、設定ファイル一覧を追記する
4. WHEN 設定ファイルを提供する THEN Laravel APIサービス SHALL 各設定項目にコメントで説明を追加する

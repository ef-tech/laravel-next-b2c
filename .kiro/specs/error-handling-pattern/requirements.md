# Requirements Document

## はじめに

本要件定義書は、Laravel 12バックエンドとNext.js 15フロントエンドにおけるRFC 7807 (Problem Details for HTTP APIs) 準拠の統一的なエラーハンドリングパターン実装を定義します。

### ビジネス価値
- **開発者体験の向上**: 標準化されたエラーハンドリングパターンにより、開発効率とコードの保守性が向上
- **トラブルシューティングの効率化**: Request ID・Trace IDによる一貫したエラー追跡で障害対応時間を短縮
- **グローバル対応**: 多言語エラーメッセージによるユーザー体験の向上
- **一貫性のあるAPI設計**: RFC 7807準拠により、業界標準のエラーレスポンス形式を採用

### スコープ
- **対象システム**: Laravel API、Next.js Admin App、Next.js User App
- **対象範囲**: HTTPエラーハンドリング（WebSocketは対象外）
- **段階的移行**: 既存エラーレスポンスとの互換性を維持しながら、新規実装にRFC 7807を適用

---

## Requirements

### Requirement 1: RFC 7807準拠のAPIエラーレスポンス生成

**Objective:** API開発者として、全てのAPIエラーレスポンスがRFC 7807形式で統一されることで、クライアント側のエラーハンドリングを標準化したい

#### Acceptance Criteria

1. WHEN Laravel APIが例外をキャッチする THEN Exception HandlerはRFC 7807形式のJSONレスポンスを返却すること
   - 必須フィールド: `type`（URI）、`title`、`status`（HTTPステータスコード）、`detail`
   - 拡張フィールド: `error_code`（独自エラーコード）、`trace_id`、`instance`（リクエストURI）、`timestamp`（ISO 8601形式）

2. WHEN APIエラーレスポンスを返却する THEN Content-Typeヘッダーは`application/problem+json`であること

3. IF バリデーションエラーが発生する THEN レスポンスに`errors`フィールドを含み、フィールド別のエラーメッセージを提供すること
   - 形式: `{ "errors": { "email": ["メールアドレス形式が不正です"], "password": ["8文字以上必要です"] } }`

4. WHERE 本番環境である THE Exception Handlerは内部エラーの詳細をマスクし、汎用エラーメッセージを返却すること

5. WHERE 開発環境である THE Exception Handlerはスタックトレース・デバッグ情報を含む詳細エラーメッセージを返却すること

6. WHEN DomainExceptionが発生する THEN `toProblemDetails()`メソッドがカスタムエラーコード・ステータスコードを含むRFC 7807レスポンスを生成すること

### Requirement 2: DDD Exception階層の拡張

**Objective:** バックエンド開発者として、DDDレイヤー別に適切なException階層を持つことで、エラーの発生源を明確にしたい

#### Acceptance Criteria

1. WHEN Domain層でビジネスルール違反が発生する THEN `DomainException`（抽象クラス）のサブクラスが例外を投げること
   - 例: `UserEmailAlreadyExistsException`、`InvalidUserAgeException`
   - HTTPステータスコード: 400 Bad Request、409 Conflict

2. WHEN Application層でユースケース実行エラーが発生する THEN `ApplicationException`（基底クラス）のサブクラスが例外を投げること
   - 例: `ResourceNotFoundException`、`UnauthorizedAccessException`
   - HTTPステータスコード: 404 Not Found、403 Forbidden

3. WHEN Infrastructure層で外部システムエラーが発生する THEN `InfrastructureException`（基底クラス）のサブクラスが例外を投げること
   - 例: `DatabaseConnectionException`、`ExternalApiTimeoutException`
   - HTTPステータスコード: 502 Bad Gateway、503 Service Unavailable、504 Gateway Timeout

4. WHEN DomainException・ApplicationException・InfrastructureExceptionが生成される THEN 各例外は`getErrorCode()`メソッドで独自エラーコードを返却すること
   - 形式: `DOMAIN-SUBDOMAIN-CODE`（例: `AUTH-2001`、`VAL-1001`）

5. WHEN 例外がログ記録される THEN 例外クラスは`trace_id`、`error_code`、`user_id`、`request_path`を含む構造化ログ情報を提供すること

### Requirement 3: Request ID伝播とトレーサビリティ

**Objective:** 運用担当者として、Request ID・Trace IDによる一貫したエラー追跡により、障害発生時の原因特定を迅速化したい

#### Acceptance Criteria

1. WHEN APIリクエストを受信する THEN RequestIdMiddlewareはUUID形式の`X-Request-ID`ヘッダーを生成すること
   - クライアントが`X-Request-ID`ヘッダーを送信済みの場合は、その値を使用すること

2. WHEN APIレスポンスを返却する THEN レスポンスヘッダーに`X-Request-ID`を含めること

3. WHEN エラーが発生する THEN RFC 7807レスポンスの`trace_id`フィールドに`X-Request-ID`の値を設定すること

4. WHEN エラーログを記録する THEN `Log::withContext()`を使用して`trace_id`、`error_code`、`user_id`をログコンテキストに追加すること

5. WHERE Monolog UidProcessorが有効である THE ログエントリは一意なUID（`trace_id`）を含むこと

### Requirement 4: エラーコード体系の定義と管理

**Objective:** 開発チームとして、統一されたエラーコード体系により、エラーの分類とドキュメント化を効率化したい

#### Acceptance Criteria

1. WHEN エラーコード体系を定義する THEN `shared/error-codes.json`ファイルにJSON形式で定義すること
   - 必須フィールド: `code`（エラーコード）、`http_status`（HTTPステータスコード）、`type`（RFC 7807 type URI）、`default_message`（デフォルトメッセージ）

2. WHEN エラーコードを使用する THEN 形式は`DOMAIN-SUBDOMAIN-CODE`（例: `AUTH-2001`、`VAL-1001`）であること
   - DOMAIN例: AUTH（認証）、VAL（バリデーション）、BIZ（ビジネスロジック）、INFRA（インフラ）

3. WHEN エラーコード定義を更新する THEN TypeScript型定義ファイル（`shared/error-codes.d.ts`）とPHP Enum（`app/Enums/ErrorCode.php`）が自動生成されること

4. IF エラーコードが`shared/error-codes.json`に存在しない THEN ビルド時にバリデーションエラーが発生すること

5. WHERE エラーコードが多言語対応である THE `lang/{locale}/errors.php`ファイルに翻訳キーとメッセージを定義すること

### Requirement 5: 多言語エラーメッセージ対応

**Objective:** グローバルユーザーとして、自分の言語でエラーメッセージを受け取ることで、問題解決を容易にしたい

#### Acceptance Criteria

1. WHEN クライアントが`Accept-Language`ヘッダーを送信する THEN SetLocaleFromAcceptLanguage MiddlewareはLaravelのアプリケーションロケールを設定すること
   - サポート言語: `ja`（日本語）、`en`（英語）
   - デフォルト: `ja`

2. WHEN エラーレスポンスを生成する THEN `trans()`ヘルパーを使用して、設定されたロケールに基づくエラーメッセージを返却すること

3. WHERE 翻訳キーが存在しない THE フォールバックメッセージ（英語）を返却すること

4. WHEN 多言語リソースを追加・更新する THEN `lang/ja/errors.php`と`lang/en/errors.php`の両方を更新すること

5. IF CI/CDパイプラインが実行される THEN 翻訳キーの存在確認テストが成功すること

### Requirement 6: フロントエンド統一APIクライアント実装

**Objective:** フロントエンド開発者として、統一されたAPIクライアントにより、エラーハンドリングロジックを再利用したい

#### Acceptance Criteria

1. WHEN APIリクエストを送信する THEN ApiClientクラス（fetch wrapper）は以下のヘッダーを自動付与すること
   - `X-Request-ID`: UUID生成（未設定の場合）
   - `Accept-Language`: ブラウザ言語設定
   - `Accept`: `application/problem+json`

2. WHEN APIレスポンスが4xx/5xxエラーである THEN ApiClientはRFC 7807レスポンスを解析し、`ApiError`クラスのインスタンスを投げること

3. WHEN ネットワークエラーが発生する THEN ApiClientは`NetworkError`クラスのインスタンスを投げること
   - 対象: `TypeError: Failed to fetch`、`AbortError`（タイムアウト）

4. WHEN APIリクエストがタイムアウト時間（30秒）を超える THEN AbortControllerによりリクエストをキャンセルし、`NetworkError`を投げること

5. WHERE RFC 7807レスポンスが解析される THE `ApiError`インスタンスは`status`、`errorCode`、`title`、`detail`、`requestId`プロパティを持つこと

6. WHEN `ApiError`インスタンスが生成される THEN ヘルパーメソッド（`isValidationError()`、`isAuthenticationError()`、`isNotFoundError()`）が提供されること

### Requirement 7: Next.js Error Boundaries実装

**Objective:** ユーザーとして、エラー発生時に適切なエラーメッセージと復旧方法を画面で確認したい

#### Acceptance Criteria

1. WHEN Next.jsセグメントでエラーが発生する THEN `app/error.tsx`（Error Boundary）がエラーをキャッチし、専用エラーUIを表示すること
   - 対象アプリ: user-app、admin-app

2. IF エラーが`ApiError`である THEN Error BoundaryはRFC 7807情報（`title`、`detail`、`errorCode`、`requestId`）を画面に表示すること

3. IF エラーが`NetworkError`である THEN Error Boundaryは「ネットワークエラーが発生しました。接続を確認してください。」メッセージと再試行ボタンを表示すること

4. WHEN ルートセグメント（`app/layout.tsx`）でエラーが発生する THEN `app/global-error.tsx`がフォールバックUIを表示すること

5. WHERE Error Boundaryである THE 「再試行」ボタンをクリックすると、`router.refresh()`または`reset()`によりエラーをリカバリーすること

6. WHEN Error Boundaryがエラーを表示する THEN Request ID（`trace_id`）をユーザーに提示し、サポート問い合わせ用の参照IDとすること

7. WHERE 本番環境である THE Error Boundaryは内部エラー詳細をマスクし、汎用エラーメッセージを表示すること

### Requirement 8: エラークラス定義

**Objective:** フロントエンド開発者として、型安全なエラークラスにより、エラーハンドリングコードの品質を向上させたい

#### Acceptance Criteria

1. WHEN `ApiError`クラスを定義する THEN RFC 7807 Problem Detailsのプロパティ（`type`、`title`、`status`、`detail`、`errorCode`、`requestId`、`timestamp`）を型定義すること

2. WHEN `ApiError`インスタンスを生成する THEN コンストラクタはRFC 7807レスポンスJSONオブジェクトを受け取ること

3. WHEN `NetworkError`クラスを定義する THEN `fromFetchError()`ファクトリーメソッドを提供すること
   - 引数: `Error`インスタンス（`TypeError`、`AbortError`）
   - 返却: `NetworkError`インスタンス

4. WHEN エラークラスを使用する THEN TypeScript型チェックにより、存在しないプロパティアクセスをコンパイル時に検出すること

5. WHERE エラーが分類される THE `ApiError`と`NetworkError`は共通の基底インターフェース（`AppError`）を実装すること
   - 必須メソッド: `toString()`、`getDisplayMessage()`

### Requirement 9: 統合テストとカバレッジ

**Objective:** QA担当者として、包括的なテストスイートにより、エラーハンドリングパターンの正確性を保証したい

#### Acceptance Criteria

1. WHEN バックエンドUnit Tests（Pest）を実行する THEN カバレッジが90%以上であること
   - 対象: `ddd/Shared/Exceptions/*Exception.php`、`app/Http/Middleware/RequestIdMiddleware.php`

2. WHEN バックエンドFeature Tests（Pest）を実行する THEN カバレッジが85%以上であること
   - 対象: `tests/Feature/Api/ErrorHandlingTest.php`

3. WHEN フロントエンドUnit Tests（Jest）を実行する THEN カバレッジが80%以上であること
   - 対象: `lib/api/client.ts`、`lib/errors/api-error.ts`、`lib/errors/network-error.ts`

4. WHEN E2E Tests（Playwright）を実行する THEN 以下のシナリオが成功すること:
   - APIエラー表示（RFC 7807情報の画面表示）
   - バリデーションエラー表示（fieldsエラーメッセージ）
   - 認証エラー（401）リダイレクト
   - ネットワークエラー表示
   - 500エラーマスキング（本番環境設定）
   - 再試行ボタン動作
   - Request ID表示（サポート用）

5. WHEN Request ID伝播テストを実行する THEN 以下を検証すること:
   - リクエストヘッダー`X-Request-ID`の生成
   - レスポンスヘッダー`X-Request-ID`の返却
   - エラーレスポンス`trace_id`フィールドへの設定
   - ログコンテキストへの`trace_id`追加

6. WHEN 多言語対応テストを実行する THEN `Accept-Language: ja`と`Accept-Language: en`で異なるエラーメッセージが返却されること

7. WHEN Content-Type検証テストを実行する THEN エラーレスポンスの`Content-Type`ヘッダーが`application/problem+json`であること

### Requirement 10: ドキュメントとCI/CD統合

**Objective:** 開発チームとして、包括的なドキュメントとCI/CD統合により、エラーハンドリングパターンの運用を標準化したい

#### Acceptance Criteria

1. WHEN エラーコード一覧を作成する THEN `docs/error-codes.md`にカテゴリー別一覧表とレスポンス例を記載すること
   - カテゴリー: 認証（AUTH-*）、バリデーション（VAL-*）、ビジネスロジック（BIZ-*）、インフラ（INFRA-*）

2. WHEN トラブルシューティングガイドを作成する THEN `docs/error-handling-troubleshooting.md`によくある問題と解決策を記載すること
   - 例: Request ID追跡方法、多言語メッセージ設定ミス、Error Boundary動作不良

3. WHEN GitHub Actionsワークフローを更新する THEN エラーハンドリングテストスイートを統合すること
   - 実行タイミング: Pull Request作成時、mainブランチpush時

4. WHEN CI/CDパイプラインを実行する THEN カバレッジレポートをCodecovに統合し、カバレッジ閾値を検証すること
   - 閾値: バックエンドUnit 90%、Feature 85%、フロントエンドUnit 80%

5. WHEN テストスイートを実行する THEN 全テスト完了時間が10分以内であること

6. IF カバレッジ閾値を下回る THEN CI/CDパイプラインが失敗し、Pull Requestマージをブロックすること

---

## 非機能要件

### パフォーマンス

1. WHEN Exception生成コストが測定される THEN オーバーヘッドが5ms以下であること

2. WHEN 翻訳処理オーバーヘッドが測定される THEN Laravel Translation Cacheにより、2回目以降のアクセスが1ms以下であること

### セキュリティ

1. WHERE 本番環境である THE Exception Handlerはスタックトレース・内部パス情報をマスクすること

2. WHERE ログに個人情報が含まれる THE 個人情報（メールアドレス、IPアドレス）をハッシュ化してログ記録すること

### 互換性

1. WHEN 既存APIクライアントがRFC 7807形式を期待していない THEN Content-Negotiation（`Accept`ヘッダー）によりレガシーフォーマットとRFC 7807形式を切り替えること

2. WHERE APIバージョニング（`/api/v2`）が実装される THE 新バージョンはRFC 7807形式のみをサポートすること

---

## 対象外範囲（Out of Scope）

1. ❌ **レガシーエラーハンドリング削除**: 既存の非RFC形式エラーレスポンスとの互換性維持（段階的移行戦略）
2. ❌ **外部ログシステム統合**: Sentry、Datadog等の統合は別Issue（基本ログ記録のみ実装）
3. ❌ **フロントエンドエラーバウンダリUIデザイン**: デザインシステム整備は別Issue（基本的なUIのみ実装）
4. ❌ **WebSocketエラーハンドリング**: HTTP APIのみ対象、WebSocketは別Issue

---

## 用語集

| 用語 | 定義 |
|------|------|
| RFC 7807 | Problem Details for HTTP APIs - HTTP APIエラーレスポンスのJSON形式標準仕様 |
| Problem Details | RFC 7807で定義されたエラーレスポンス形式（`type`、`title`、`status`、`detail`等のフィールドを含む） |
| EARS | Easy Approach to Requirements Syntax - 要件記述の標準化手法 |
| DDD | Domain-Driven Design - ドメイン駆動設計 |
| Exception Handler | Laravel例外ハンドラー（`app/Exceptions/Handler.php`） |
| Error Boundary | Next.jsエラー境界（`app/error.tsx`、`app/global-error.tsx`） |
| Request ID | リクエスト識別子（`X-Request-ID`ヘッダー、UUID形式） |
| Trace ID | トレース識別子（Request IDと同義、ログ追跡用） |
| Error Code | 独自エラーコード（`DOMAIN-SUBDOMAIN-CODE`形式、例: `AUTH-2001`） |

---

## 参考資料

1. **RFC 7807**: Problem Details for HTTP APIs
   https://datatracker.ietf.org/doc/html/rfc7807

2. **Laravel Exception Handling**
   https://laravel.com/docs/12.x/errors

3. **Next.js Error Handling**
   https://nextjs.org/docs/app/building-your-application/routing/error-handling

4. **プロジェクト内部資料**
   - `.kiro/steering/structure.md`: プロジェクト構造
   - `.kiro/steering/tech.md`: 技術スタック
   - `backend/laravel-api/docs/ddd-architecture.md`: DDD 4層構造
   - `backend/laravel-api/docs/ddd-development-guide.md`: DDD開発ガイドライン

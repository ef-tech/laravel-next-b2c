# Technology Stack

## アーキテクチャ
- **API専用最適化Laravel**: 必要最小限4パッケージ構成による超高速起動
- **🏗️ DDD/クリーンアーキテクチャ (4層構造)**:
  - **Domain層** (`ddd/Domain/`): Entities、ValueObjects、Repository Interfaces、Domain Events、Domain Services
    - **Shared/Exceptions** (2025-11-20追加): HasProblemDetails trait、DomainException基底クラス（RFC 7807準拠、DRY原則適用）
  - **Application層** (`ddd/Application/`): UseCases、DTOs、Service Interfaces、Queries、Application Exceptions
  - **Infrastructure層** (`ddd/Infrastructure/`): Repository実装（Eloquent）、External Services、Framework固有コード
  - **HTTP層** (`app/Http/`): Controllers、Requests、Resources
  - **依存方向**: HTTP → Application → Domain ← Infrastructure（依存性逆転）
- **ステートレス設計**: `SESSION_DRIVER=array`でセッション除去、水平スケーリング対応
- **マイクロフロントエンド型構成**: 管理者用とユーザー用アプリケーションの完全分離
- **トークンベース認証**: Laravel Sanctum 4.0によるセキュアなステートレス認証
- **Docker化インフラ**: Laravel Sailによるコンテナベース開発環境
- **フルスタックTypeScript**: フロントエンドからバックエンドまでの型安全性

### 🚀 Laravel API最適化成果
**パフォーマンス改善メトリクス**:
- 起動速度: **33.3%向上** (33.3ms達成)
- メモリ効率: **0.33KB/request** (画期的改善)
- 依存関係: **96.5%削減** (114→4パッケージ)
- レスポンス: **11.8ms** (<20ms目標達成)

## フロントエンド技術
### フレームワーク・ライブラリ
- **Next.js**: 15.5.4 (React Server Components、App Router対応)
- **React**: 19.1.0 (最新のConcurrent Features)
- **TypeScript**: ^5 (厳密な型チェック)
  - **型安全性強化**:
    - `satisfies`演算子適用: 型推論最適化とタイプミス防止（`as const satisfies Record<Locale, GlobalErrorMessages>`）
    - 厳格な型チェック: リテラル型の活用による実行時エラーの削減
    - JSDocコメント完備: 詳細な型定義ドキュメントによる開発者体験向上
    - **🌍 locale型明示化（2025-01-13完了）**: i18n統合におけるlocale型をstringに明示的に変換（型エラー解消、next-intl互換性向上）
      - User App/Admin App両方でvalidLocale型を統一（next-intl公式型定義準拠）
      - Error Boundaries i18n完全実装：`locale as string`による型安全性維持
      - 全ページを[locale]ルートに統一配置、i18n対応の完全一貫性確保
  - **✅ 共通TypeScript設定（2025-11-13完了）**: tsconfig.base.json導入による設定共通化
    - **frontend/tsconfig.base.json**: 15個の共通compilerOptionsを集約
      - target: ES2017、strict mode有効、Next.jsプラグイン統合
      - forceConsistentCasingInFileNames: true（大文字小文字一貫性チェック）
    - **継承構造**: User App/Admin Appでextends: "../tsconfig.base.json"
    - **baseUrl明示**: 各アプリでbaseUrl: "."を明示的に設定
    - **保守性向上**: TypeScript設定の重複削減、一元管理による変更容易性向上
- **Tailwind CSS**: ^4.0.0 (最新版CSS framework)
- **next-intl**: ^3.x (多言語化対応、Error Boundaries i18n統合)

### ビルド・開発ツール
- **Turbopack**: Next.js標準バンドラー (`--turbopack`フラグ)
- **ESLint**: ^9 (コード品質管理、モノレポ統一設定)
- **Prettier**: ^3 (コードフォーマッター、Tailwind CSS統合)
- **PostCSS**: Tailwind CSS統合用

### コード品質管理 (モノレポ統一設定)
- **共通設定**: ルート`package.json`でワークスペース全体を管理
- **husky**: ^9.1.7 (Gitフック管理、`.husky/`直下にフック直接配置する推奨方法に移行済み)
- **lint-staged**: ^15 (ステージされたファイルのみlint/format実行)
- **設定ファイル**: 各アプリに`eslint.config.mjs`（ESLint 9対応flat config）
- **テストコード品質管理**:
  - **eslint-plugin-jest**: ^28.14.0 (Jest専用ESLintルール、flat/recommended適用)
  - **eslint-plugin-testing-library**: ^6.5.0 (Testing Library専用ルール、flat/react適用)
  - **eslint-plugin-jest-dom**: ^5.5.0 (Jest-DOM専用ルール、flat/recommended適用)
  - **共通Base設定**: `frontend/.eslint.base.mjs` - テストファイル専用オーバーライド設定
  - **適用ルールレベル**: errorレベル（CI/CD厳格チェック対応）

### デュアルアプリケーション構成
- **Admin App** (`frontend/admin-app/`): 管理者向けダッシュボード
- **User App** (`frontend/user-app/`): エンドユーザー向けアプリケーション
- **🔧 共通ライブラリ** (`frontend/lib/`): DRY原則に基づく共通モジュール（frontend-lib-monorepo-consolidation完了）
  - **@shared/*パスエイリアス実装**: TypeScript paths設定による共通モジュール参照
    - `@shared/lib/*`: 共通ライブラリ関数（Global Error静的辞書等）
    - `@shared/types/*`: 共通型定義（errors.ts、messages.d.ts、api/v1.ts）
    - Admin App/User App両方から統一Import可能
  - **global-error-messages.ts**: Global Error静的辞書（共通モジュール化完了）
    - User AppとAdmin Appの重複メッセージ辞書を統一（~170行コード削減）
    - satisfies演算子適用による型安全性強化
    - 4カテゴリ構造（network, boundary, validation, global）
    - 日本語/英語対応（ja/en）
    - 全54テストpass
  - **types/errors.ts**: エラー型定義（共通モジュール化完了）
    - Laravel Enumから自動生成（generate-error-types.js）
    - ErrorCode Enum/Union型定義
    - RFC 7807準拠エラーレスポンス型
  - **types/messages.d.ts**: メッセージ型定義（共通モジュール化完了）
    - GlobalErrorMessages型定義
    - 型安全なメッセージアクセス
  - **types/api/v1.ts**: V1 API型定義（共通モジュール化完了）
    - Presenter型、リクエスト型、レスポンス型
    - Laravel APIとのインターフェース統一

### テスト環境
- **Jest**: ^29.7.0 (テストランナー、モノレポ対応)
  - **テスト実行の確実性向上**: `--passWithNoTests`オプション削除済み（2025-11-18、PR #140完了）
  - テストファイル削除検知強化、CI品質保証向上、`jest --listTests`補助コマンド追加
  - **ENV系テストコード整理完了（2025-11-25、PR #154完了）**:
    - 不要テストファイル削除（frontend/admin-app/src/__tests__/env.test.ts等）
    - test-utils/env.ts削除（環境変数モックユーティリティ、実際のテストで未使用）
    - env-sync.test.ts DRY原則適用（重複コード削減）
    - env.tsカバレッジ計測除外（設定ファイルのためカバレッジ不要）
    - 保守コスト削減、テストスイート簡潔化
- **React Testing Library**: ^16.3.0 (React 19対応)
- **@testing-library/jest-dom**: ^6.9.1 (DOM matcher拡張)
- **jest-environment-jsdom**: ^29.7.0 (DOM環境シミュレーション)
- **MSW**: ^2.11.3 (APIモック、global.fetch対応)
- **next-router-mock**: ^0.9.13 (Next.js Router モック)
- **テスト構成**: モノレポ共通設定（jest.base.js）+ プロジェクト統括設定（jest.config.js）

### E2Eテスト環境
- **Playwright**: ^1.47.2 (E2Eテストフレームワーク、クロスブラウザ対応)
- **テストプロジェクト構成**: Admin App / User App 分離実行
- **認証統合**: Laravel Sanctum認証対応（global-setup実装済み）
- **Page Object Model**: 保守性の高いテスト設計パターン採用
- **並列実行**: Shard機能によるCI/CD最適化（4並列デフォルト）
- **環境変数管理**: `.env`ファイルによる柔軟なURL/認証情報設定
- **CI/CD統合**: GitHub Actions自動実行（Pull Request時、約2分完了）

## バックエンド技術 - 🏆 API専用最適化済み
### 言語・フレームワーク
- **PHP**: ^8.4 (最新のPHP機能対応)
- **Laravel**: ^12.0 (**API専用最適化済み** - Web機能削除)
- **Composer**: パッケージ管理

### 💾 最小依存関係構成 (4コアパッケージ)
- **Laravel**: ^12.0 (フレームワークコア)
- **Laravel Sanctum**: ^4.0 (トークン認証)
- **Laravel Tinker**: ^2.10 (REPL環境)
- **Laravel Pint**: ^1.24 (コードフォーマッター)

### ステートレスAPI設計詳細
- **セッション除去**: `SESSION_DRIVER=array`でステートレス化
- **Web機能削除**: `routes/web.php`簡略化、View関連機能除去
- **APIルート専用**: `routes/api.php`に集約、RESTful設計
- **CORS最適化**: Next.jsフロントエンドとの完全統合

### 🔐 Laravel Sanctum認証システム詳細
**認証エンドポイント** (`routes/api.php`):
- **POST `/api/login`**: メール・パスワードによるログイン、Personal Access Token発行
- **POST `/api/logout`**: トークン無効化、ログアウト処理
- **GET `/api/me`**: 認証ユーザー情報取得（`auth:sanctum` middleware保護）
- **GET `/api/tokens`**: 発行済みトークン一覧取得
- **POST `/api/tokens/{id}/revoke`**: 特定トークン無効化
- **POST `/api/tokens/refresh`**: トークン更新（新規トークン発行）

**📊 ヘルスチェックエンドポイント** (`routes/api.php`):
- **GET `/api/v1/health`**: APIサーバー稼働状態確認（ルート名: `v1.health`、APIバージョニング対応）
  - **レスポンス**: `{ "status": "ok", "timestamp": "2025-10-12T00:00:00.000000Z" }` (JSON形式、⏰ RFC 3339 UTC ISO 8601形式統一)
  - **用途**: Dockerヘルスチェック統合、ロードバランサー監視、サービス死活監視
  - **動的ポート対応**: `APP_PORT`環境変数による柔軟なポート設定
  - **認証不要**: パブリックエンドポイント（middleware: なし）
- **🔢 APIバージョニング実装**:
  - **V1エンドポイント**: `/api/v1/*`（URLベースバージョニング）
  - **認証API**: `/api/v1/login`, `/api/v1/logout`, `/api/v1/me`
  - **トークン管理API**: `/api/v1/tokens`, `/api/v1/tokens/{id}/revoke`, `/api/v1/tokens/refresh`
  - **CSPレポート**: `/api/v1/csp-report`
  - **段階的移行**: 既存 `/api/*` エンドポイントと共存、非推奨化フロー管理

**トークン管理機能**:
- **Personal Access Tokens**: UUIDベーストークン（`personal_access_tokens`テーブル）
- **有効期限管理**: `SANCTUM_EXPIRATION` 環境変数で設定可能（デフォルト: 60日）
- **自動期限切れ削除**: `tokens:prune` コマンドをScheduler統合（毎日実行）
- **Token Abilities**: 権限管理機能（`*` = 全権限）
- **Last Used At**: トークン最終使用日時記録

**セキュリティ設定**:
- **Middleware**: `auth:sanctum` による認証保護
- **CSRF保護**: SPA用CSRF設定（`config/sanctum.php`）
- **Stateful Domains**: `localhost:13001`, `localhost:13002`（開発環境）
- **レート制限**: API保護設定
- **PHPStan Level 8準拠**: 型安全性保証、静的解析合格

**Scheduled Tasks統合**:
```php
// app/Console/Kernel.php
$schedule->command('tokens:prune')->daily();
```

**🔒 Exception Handler強化**:
- **AuthenticationException**: API専用JSONレスポンス、認証失敗時のloginルートリダイレクト無効化
- **ValidationException**: FormRequestバリデーションエラーのJSON形式返却
- **統一エラーレスポンス**: `{ "message": "...", "errors": {...} }` 形式
- **HTTP Status Code**: 401 Unauthorized（認証失敗）、422 Unprocessable Entity（バリデーションエラー）

**環境変数**:
```env
SANCTUM_STATEFUL_DOMAINS=localhost:13001,localhost:13002
SESSION_DRIVER=array  # ステートレス設計
SANCTUM_EXPIRATION=60 # トークン有効期限（日数）

# 🌐 CORS環境変数設定
CORS_ALLOWED_ORIGINS=http://localhost:13001,http://localhost:13002  # 開発環境
CORS_SUPPORTS_CREDENTIALS=true  # Cookie送信許可（Sanctum認証対応）
# 本番環境例: CORS_ALLOWED_ORIGINS=https://app.example.com,https://admin.example.com

# 🌍 多言語対応（i18n）設定
APP_LOCALE=ja                     # デフォルトロケール（日本語）
APP_FALLBACK_LOCALE=en            # フォールバックロケール（英語）
# SetLocaleFromAcceptLanguageミドルウェアによるAccept-Language header自動検出対応

# 🔐 セキュリティヘッダー設定（OWASP準拠）
SECURITY_ENABLE_CSP=true  # Content Security Policy有効化
SECURITY_CSP_MODE=report-only  # CSPモード: report-only（監視）または enforce（強制）
SECURITY_CSP_SCRIPT_SRC='self' 'unsafe-eval'  # スクリプト読み込み元（開発環境: unsafe-eval許可）
SECURITY_CSP_STYLE_SRC='self' 'unsafe-inline'  # スタイル読み込み元（Tailwind CSS対応）
SECURITY_CSP_REPORT_URI=/api/csp-report  # CSP違反レポート送信先
SECURITY_FORCE_HSTS=false  # HSTS強制（本番環境のみtrue推奨）
SECURITY_HSTS_MAX_AGE=31536000  # HSTS有効期間（1年間）

# 🛡️ ミドルウェア環境変数設定（APIレート制限強化対応）
# レート制限設定（エンドポイント分類細分化）
RATELIMIT_CACHE_STORE=redis  # レート制限キャッシュストア: redis（本番推奨）/ array（テスト環境、Redis障害時フェイルオーバー）
RATELIMIT_LOGIN_MAX_ATTEMPTS=5        # 認証APIレート制限（5回/分）
RATELIMIT_WRITE_API_MAX_ATTEMPTS=10   # 書き込みAPIレート制限（10回/分）
RATELIMIT_READ_API_MAX_ATTEMPTS=60    # 読み取りAPIレート制限（60回/分）
RATELIMIT_ADMIN_API_MAX_ATTEMPTS=100  # 管理者APIレート制限（100回/分）

# Idempotencyキャッシュ設定
IDEMPOTENCY_CACHE_STORE=redis  # 冪等性キャッシュストア: redis（本番推奨）/ array（テスト環境）
IDEMPOTENCY_TTL=86400          # 冪等性キャッシュTTL（24時間）

# ログ個人情報配慮設定
LOG_HASH_SENSITIVE_DATA=true   # 個人情報ハッシュ化有効化（本番環境推奨）
LOG_SENSITIVE_FIELDS=email,ip_address,user_agent  # ハッシュ化対象フィールド（カンマ区切り）

# 環境変数バリデーションスキップ（緊急時のみ、migrate/seed実行時に使用可能）
# ENV_VALIDATION_SKIP=true
```

### 🎯 統一エラーハンドリングパターン詳細

**RFC 7807準拠APIエラーレスポンス**:
```json
{
  "type": "https://api.example.com/errors/validation-error",
  "title": "Validation Error",
  "status": 422,
  "detail": "入力データに問題があります",
  "instance": "/api/v1/users",
  "request_id": "550e8400-e29b-41d4-a716-446655440000",
  "timestamp": "2025-11-22T12:34:56.000000Z",
  "errors": {
    "email": ["メールアドレスの形式が正しくありません"]
  }
}
```

**⏰ timestampフォーマット統一（2025-11-22、PR #147完了）**:
- **統一形式**: `now()->utc()->toIso8601String()` → `2025-11-22T12:34:56.000000Z`
- **RFC 3339準拠**: ISO 8601形式 + タイムゾーン明示（UTC Z suffix）
- **対象ファイル**: backend/laravel-api/内の14ファイル
  - Controllers (HealthController, CspReportController等)
  - Middleware (SetRequestId, IdempotencyKey等)
  - Presenters (HealthPresenter等)
  - Resources (UserResource等)
- **問題解消**: タイムゾーン不明確によるクライアント側パース問題の完全解消
- **マイグレーションスクリプト堅牢性**: 絶対パス化による実行信頼性向上

**エラーコード体系（型安全）**:

Laravel側:
```php
// app/Enums/ErrorCode.php
enum ErrorCode: string
{
    case VALIDATION_ERROR = 'VALIDATION_ERROR';
    case AUTHENTICATION_FAILED = 'AUTHENTICATION_FAILED';
    case RESOURCE_NOT_FOUND = 'RESOURCE_NOT_FOUND';
    // ... その他のエラーコード

    /**
     * RFC 7807 type URI完全統一（2025-11-19実装完了）
     * ErrorCode::getType()メソッドによる単一ソース化（DRY原則徹底）
     *
     * フォールバックURI生成時のサニタイズ処理（2025-11-28実装完了）:
     * - RFC 3986準拠: [a-z0-9\-] のみを許可（unreserved文字セットサブセット）
     * - 正規表現 /[^a-z0-9\-]/ を使用して安全な文字セットのみを許可
     * - 小文字変換後にサニタイズ処理を適用
     * - 空文字列の場合はデフォルト値 'unknown' を使用
     * - 元のエラーコードは error_code フィールドで保持（トレーサビリティ確保）
     * - 参考: Issue #143, PR #156（Codexレビュー指摘によるセキュリティ強化）
     */
    public function getType(): string
    {
        return config('app.url') . '/errors/' . strtolower(str_replace('_', '-', $this->value));
    }

    public static function tryFrom(string $value): ?self
    {
        return self::cases()[$value] ?? null;
    }
}

// ddd/Domain/Shared/Exceptions/HasProblemDetails.php
/**
 * HasProblemDetails trait（2025-11-20実装完了）
 * RFC 7807準拠エラーレスポンス生成ロジック統一
 */
trait HasProblemDetails
{
    abstract public function getErrorCode(): ErrorCode;
    abstract public function getTitle(): string;
    abstract public function getDetail(): string;
    abstract public function getStatus(): int;

    /**
     * RFC 7807 Problem Details形式に変換（DRY原則適用）
     */
    public function toProblemDetails(Request $request): array
    {
        $errorCode = $this->getErrorCode();

        return [
            'type' => $errorCode->getType(),
            'title' => $this->getTitle(),
            'status' => $this->getStatus(),
            'detail' => $this->getDetail(),
            'instance' => $request->path(),
            'request_id' => $request->header('X-Request-ID'),
        ];
    }
}

// ddd/Domain/Shared/Exceptions/DomainException.php
/**
 * DomainException 基底クラス（2025-11-20更新）
 * HasProblemDetails trait適用によるコード重複排除
 */
abstract class DomainException extends \Exception
{
    use HasProblemDetails;

    // toProblemDetails()メソッドは trait から提供（重複削除完了）
}
```

フロントエンド側（自動生成）:
```typescript
// frontend/types/errors.ts
export enum ErrorCode {
  VALIDATION_ERROR = 'VALIDATION_ERROR',
  AUTHENTICATION_FAILED = 'AUTHENTICATION_FAILED',
  RESOURCE_NOT_FOUND = 'RESOURCE_NOT_FOUND',
  // ... 自動生成される型定義
}

export type ErrorCodeType = keyof typeof ErrorCode;
```

**多言語エラーメッセージ（i18n）**:
- **SetLocaleFromAcceptLanguageミドルウェア**: Accept-Language headerを自動検出し、Laravelロケールを設定
- **言語ファイル**: `lang/ja/errors.php`、`lang/en/errors.php` によるメッセージ管理
- **フロントエンド対応**: Accept-Language: ja ヘッダーを自動送信、日本語エラーメッセージ受信

**Request ID伝播フロー**:
1. **Laravel**: SetRequestIdミドルウェアがUUID生成、`X-Request-ID` ヘッダー付与
2. **エラー発生**: Exception Handler が `request_id` をエラーレスポンスに含める
3. **フロントエンド**: エラーオブジェクトに `request_id` を保持、ログに記録
4. **トレーサビリティ**: Laravel logs (`storage/logs/`) でRequest ID検索可能

**NetworkError日本語化**:
```typescript
// frontend/lib/api-client.ts
const ERROR_MESSAGES: Record<string, string> = {
  NETWORK_ERROR: 'ネットワークエラーが発生しました',
  TIMEOUT_ERROR: 'タイムアウトしました',
  // ...
};
```

**📡 RFC 7807準拠Acceptヘッダー設定（2025-11-23実装完了）**:
```typescript
// frontend/lib/api-client.ts, fetch-api.ts等
const headers = {
  'Accept': 'application/problem+json, application/json;q=0.9',
  'Content-Type': 'application/json',
  // ...
};
```
- **Content Negotiation**: RFC 7807準拠のエラーレスポンス優先受信
- **後方互換性**: application/jsonをフォールバックとして維持
- **優先度設定**: problem+json;q=1（最優先）、json;q=0.9（次優先）

**401自動リダイレクト**:
```typescript
// frontend/lib/api-client.ts
if (error.response?.status === 401) {
  router.push('/login');
  return Promise.reject(error);
}
```

**Error Boundaries i18n実装**:
```typescript
// app/[locale]/error.tsx (Admin/User App共通)
'use client';

import { useTranslations } from 'next-intl';

export default function Error({ error, reset }: ErrorProps) {
  const t = useTranslations('error');

  return (
    <div className="error-container">
      <h2>{t('title')}</h2>
      <p>{error.message}</p>
      <button onClick={reset}>{t('retry')}</button>
    </div>
  );
}
```

**next-intl統合機能**:
- **ロケール検出**: NEXT_LOCALE Cookie優先、Accept-Language header自動フォールバック
- **Error Boundaries多言語化**: グローバル/ページレベルError Boundariesでのロケール対応
- **メッセージファイル管理**: `messages/ja.json`、`messages/en.json` による一元管理
- **getMessages明示的locale渡し**: Error Boundaries内でのロケール確実性保証

**自動コード生成スクリプト**:
```bash
# Laravel Enumから TypeScript型定義を自動生成（Prettier自動実行統合、2025-11-19更新）
npm run generate:error-types

# 生成先: frontend/types/errors.ts
# 検証: npm run verify:error-types
# 備考: 型定義生成後にPrettierが自動実行され、コードフォーマットを統一
```

### 🛡️ 基本ミドルウェアスタック詳細

**ミドルウェア構成**（`config/middleware.php`）:

1. **ログ・監視ミドルウェア**:
   - `SetRequestId`: リクエストID自動付与（Laravel標準Str::uuid()使用）、構造化ログ対応
   - `LogPerformance`: パフォーマンス監視、レスポンスタイム記録
   - `LogSecurity`: セキュリティイベントログ分離記録、個人情報配慮対応（環境変数でハッシュ化制御）
   - **ログ個人情報ハッシュ化**: `LOG_HASH_SENSITIVE_DATA`環境変数で制御、email/IP/UAをハッシュ化

2. **APIレート制限ミドルウェア（強化版）**:
   - `DynamicRateLimit`: エンドポイント別レート制限、動的制限値設定
   - **環境変数駆動**: `RATELIMIT_CACHE_STORE`（redis/array切替、Redis障害時フェイルオーバー）
   - **エンドポイント分類細分化**:
     - 認証API: 5回/分（`RATELIMIT_LOGIN_MAX_ATTEMPTS`）
     - 書き込みAPI: 10回/分（`RATELIMIT_WRITE_API_MAX_ATTEMPTS`）
     - 読み取りAPI: 60回/分（`RATELIMIT_READ_API_MAX_ATTEMPTS`）
     - 管理者API: 100回/分（`RATELIMIT_ADMIN_API_MAX_ATTEMPTS`）
   - **キャッシュ競合対策**: `Cache::increment()` + `Cache::add()`アトミック操作組み合わせ
   - **retry_after最適化**: 負の値問題修正、resetAt計算改善
   - **DDD統合**: Application層にRateLimitConfig配置（`ddd/Application/Middleware/Config/RateLimitConfig.php`）

3. **Idempotencyミドルウェア**:
   - `IdempotencyKey`: 冪等性保証、重複リクエスト防止
   - **環境変数駆動**: `IDEMPOTENCY_CACHE_STORE`（redis/array切替）
   - **キャッシュTTL**: 24時間（`IDEMPOTENCY_TTL`）
   - **Webhook対応**: 同一ペイロード検証、タイムスタンプ記録
   - **未認証対応**: IPアドレスベース識別

4. **認証・認可ミドルウェア**:
   - `Authenticate`: Laravel Sanctum統合認証ミドルウェア（`auth:sanctum`）
   - `Authorize`: ポリシーベース認可チェック

5. **監査ログミドルウェア**:
   - `AuditLog`: ユーザー行動追跡、イベントログ記録
   - `SecurityAudit`: セキュリティイベント監査

6. **キャッシュ管理ミドルウェア**:
   - `SetETag`: ETag自動生成、HTTP Cache-Control設定
   - `CheckETag`: 条件付きリクエスト対応（304 Not Modified）

**ミドルウェアグループ設定**:
```php
// config/middleware.php
'api' => [
    SetRequestId::class,           // リクエストID付与（構造化ログ、個人情報ハッシュ化対応）
    DynamicRateLimit::class,       // APIレート制限（エンドポイント分類、Redis障害時フェイルオーバー）
    IdempotencyKey::class,         // 冪等性保証（Webhook対応、24時間キャッシュ）
    Authenticate::class,           // 認証（Laravel Sanctum統合）
    LogPerformance::class,         // パフォーマンス監視
    SetETag::class,                // キャッシュ管理（ETag自動生成）
],
```

**DDD統合アプローチ（完全実装済み）**:
- ミドルウェア設定: Application層に配置（`ddd/Application/Middleware/Config/`）
  - `MiddlewareGroupsConfig.php`: グループ定義（api/auth/public）
  - `RateLimitConfig.php`: エンドポイント別レート制限設定
- Repositoryパターン: ログ記録・監査ログのRepository実装
- イベント駆動: ミドルウェアからDomain Eventsディスパッチ
- Architecture Tests: 依存方向検証、レイヤー分離チェック

### データベース・ストレージ
- **PostgreSQL**: 17-alpine (主データベース - ステートレス設計対応)
  - **接続最適化**: タイムアウト設定（connect_timeout/statement_timeout）、PDOオプション最適化
  - **環境別設定**: Docker/Native/Production環境に応じた接続パラメータ最適化
  - **信頼性向上**: ServiceProvider方式によるタイムアウト設定、エラーハンドリング強化
  - **🔧 主キー設計**: bigint自動採番主キー（Laravel標準構成準拠、UUID比較でパフォーマンス最適化）
- **Redis**: alpine (キャッシュ管理 - セッションストレージ不使用)
- **MinIO**: オブジェクトストレージ (S3互換)

**最適化ポイント**:
- セッションストレージをRedisから除去、キャッシュのみ使用
- ステートレス設計によりDBコネクション最適化
- PostgreSQL接続タイムアウト設定（デフォルト: 接続5秒、ステートメント30秒）
- PDOオプション最適化（エミュレーション無効、エラーモード例外設定）

### 開発・テストツール
- **Laravel Pint**: ^1.24 (コードフォーマッター - コアパッケージ)
- **Larastan (PHPStan)**: ^3.0 (静的解析ツール - Level 8厳格チェック)
- **Pest**: ^3.12 (モダンテストフレームワーク - PHPUnitから完全移行、Architecture Testing統合)
  - **Architecture Tests**: `tests/Arch/` - 依存方向検証、レイヤー分離チェック、命名規約検証
  - **テストカバレッジ**: 96.1%達成（Domain層100%、Application層98%、Infrastructure層94%）
  - **テストDB環境**: SQLite（高速開発）/PostgreSQL（本番同等）の柔軟な切り替え、並列テスト実行対応
- **Laravel Sail**: ^1.41 (Docker開発環境 - カスタムポート対応)
- **Laravel Tinker**: ^2.10.1 (REPL環境 - コアパッケージ)
- **Faker**: ^1.23 (テストデータ生成)

### PHP品質管理システム
**統合コード品質ツール**: Laravel Pint (フォーマット) + Larastan (静的解析) + Git Hooks + CI/CD

#### Laravel Pint設定 (`pint.json`)
```json
{
  "preset": "laravel",
  "rules": {
    "simplified_null_return": true,
    "no_unused_imports": true
  }
}
```

#### Larastan設定 (`phpstan.neon`)
```neon
includes:
    - vendor/larastan/larastan/extension.neon
parameters:
    level: 8
    paths:
        - app
        - config
        - database
        - routes
        - tests
```

#### Git Hooks自動化 (.husky/)
- **設定場所**: ルート`.husky/`ディレクトリ（Husky v9推奨方法: 直接フック配置）
- **Pre-commit** (`.husky/pre-commit`): lint-staged実行 (変更PHPファイルのみPint自動フォーマット、変更TSXファイルはESLint + Prettier)
- **Pre-push** (`.husky/pre-push`): `composer quality`実行 (Pint + Larastan全体チェック)
- **非推奨警告解消済み**: `.husky/_/`内の自動生成フックから`.husky/`直下の推奨方法に完全移行

#### CI/CD統合 (GitHub Actions v4) - 発火タイミング最適化済み

**共通最適化機能**:
- **Concurrency設定**: PR内の連続コミットで古い実行を自動キャンセル（リソース効率化）
- **Paths Filter**: 関連ファイル変更時のみワークフロー実行（実行頻度60-70%削減）
- **Pull Request Types明示**: 必要なイベントのみ実行（opened, synchronize, reopened, ready_for_review）
- **キャッシング統一化**: Node.js（setup-node内蔵）、Composer（cache-files-dir）でヒット率80%以上

**ワークフロー一覧**:

1. **PHP品質チェック** (`.github/workflows/php-quality.yml`)
   - **担当領域**: `backend/laravel-api/**`
   - **自動実行**: Pull Request時（バックエンド変更時のみ）
   - **チェック内容**: Pint検証 + Larastan Level 8静的解析
   - **Concurrency**: `${{ github.workflow }}-${{ github.event_name }}-${{ github.ref }}`

2. **PHPテスト** (`.github/workflows/test.yml`)
   - **担当領域**: `backend/laravel-api/**`
   - **自動実行**: Pull Request時（バックエンド変更時のみ）
   - **テスト内容**: Pest 4テストスイート実行
   - **キャッシング**: Composer cache-files-dir方式（最適化済み）

3. **フロントエンドテスト** (`.github/workflows/frontend-test.yml`)
   - **担当領域**: `frontend/**`, `test-utils/**` + **API契約監視**
   - **API契約監視パス**:
     - `backend/laravel-api/app/Http/Controllers/Api/**`
     - `backend/laravel-api/app/Http/Resources/**`
     - `backend/laravel-api/routes/api.php`
   - **自動実行**: フロントエンド変更時 **または** API契約変更時
   - **テスト内容**: Jest 29 + Testing Library 16（カバレッジ94.73%）
   - **API契約整合性検証**: APIレスポンス形式変更を早期検出

4. **E2Eテスト** (`.github/workflows/e2e-tests.yml`)
   - **担当領域**: `frontend/**`, `backend/**`, `e2e/**`
   - **自動実行**: Pull Request時、mainブランチpush時、手動実行
   - **実行方式**: 4 Shard並列実行（Matrix戦略）、約2分完了
   - **タイムアウト**: 20分（最適化済み、旧60分）
   - **パフォーマンス最適化**:
     - Composerキャッシング（`actions/cache@v4`）
     - Concurrency設定（PR重複実行キャンセル）
     - Paths Filter（影響範囲のみ実行）
   - **実行環境**: Docker開発モード起動（ビルド不要、高速化）
   - **レポート**: Playwright HTML/JUnitレポート、失敗時のスクリーンショット・トレース
   - **Artifacts**: 各Shardごとのテストレポート保存

5. **フロントエンドCI/CD本番ビルド検証** (`.github/workflows/frontend-test.yml`)
   - **実装完了**: 2025-01-12 (PR #132、frontend-cicd-build-validation spec完了)
   - **検証内容**:
     - **TypeScript型チェック**: `npm run type-check`（テストファイル除外、本番コードのみ検証）
     - **Next.js本番ビルド**: `npm run build`（両アプリの本番ビルド成功確認）
     - **環境変数検証**: ビルド前の必須環境変数チェック
   - **最適化施策**:
     - Next.jsテレメトリ無効化（`NEXT_TELEMETRY_DISABLED=1`）によるCI環境ログ簡潔化
     - TypeScript型チェック対象最適化（`tsconfig.json` excludeでテストファイル除外）
     - Matrix戦略によるAdmin App/User App並列ビルド
   - **自動実行**: Pull Request時（frontend/**変更検知）、mainブランチpush時
   - **エラー検知強化**: PR時の本番ビルドエラー早期発見、デプロイ前品質保証

**ワークフロー実行条件マトリクス**:
| 変更内容 | frontend-test | php-quality | test | e2e-tests |
|---------|--------------|-------------|------|-----------|
| フロントエンドのみ | ✅ | ❌ | ❌ | ✅ |
| バックエンドのみ | ❌ | ✅ | ✅ | ✅ |
| API Controllers変更 | ✅ | ✅ | ✅ | ✅ |
| API Resources変更 | ✅ | ✅ | ✅ | ✅ |
| E2Eテストのみ | ❌ | ❌ | ❌ | ✅ |
| README更新のみ | ❌ | ❌ | ❌ | ❌ |

### 📝 最適化ドキュメント体系
**`backend/laravel-api/docs/` に包括的ドキュメントを格納**:

**Laravel API最適化ドキュメント**:
- `laravel-optimization-process.md`: 最適化プロセス完了レポート
- `performance-report.md`: パフォーマンス改善定量分析
- `development-setup.md`: API専用開発環境構築手順
- `database-connection.md`: PostgreSQL接続設定ガイド（環境別設定・タイムアウト最適化・トラブルシューティング）
- `migration-guide.md`: 他プロジェクトへの移行ガイド
- `troubleshooting.md`: トラブルシューティング完全ガイド
- `configuration-changes.md`: 全設定変更の詳細記録
- `laravel-pint-larastan-team-guide.md`: Laravel Pint・Larastanチーム運用ドキュメント

**テストDB運用ドキュメント**:
- `docs/TESTING_DATABASE_WORKFLOW.md`: テストDB設定ワークフローガイド（SQLite/PostgreSQL切り替え、並列テスト実行、Makefileタスク運用）

**フロントエンドテストコードESLintドキュメント** (`docs/`):
- `JEST_ESLINT_INTEGRATION_GUIDE.md`: Jest/Testing Library ESLint統合ガイド（設定概要、プラグイン詳細、適用ルール）
- `JEST_ESLINT_QUICKSTART.md`: クイックスタートガイド（5分セットアップ、基本ワークフロー、トラブルシューティング）
- `JEST_ESLINT_TROUBLESHOOTING.md`: トラブルシューティング完全ガイド（設定問題、実行エラー、ルール調整）
- `JEST_ESLINT_CONFIG_EXAMPLES.md`: 設定サンプル集（プロジェクト別設定例、カスタマイズパターン）

**🌐 CORS環境変数設定ドキュメント** (`docs/`):
- `CORS_CONFIGURATION_GUIDE.md`: CORS環境変数設定完全ガイド（環境別設定、セキュリティベストプラクティス、トラブルシューティング、テスト戦略）

**🔐 セキュリティヘッダー設定ドキュメント**:
- `SECURITY_HEADERS_IMPLEMENTATION_GUIDE.md`: Laravel/Next.js実装手順、環境変数設定、CSPカスタマイズ方法（ルート配置）
- `docs/SECURITY_HEADERS_OPERATION.md`: 日常運用マニュアル、Report-Onlyモード運用、Enforceモード切り替え手順
- `docs/SECURITY_HEADERS_TROUBLESHOOTING.md`: よくある問題、CSP違反デバッグ、CORSエラー対処
- `docs/CSP_DEPLOYMENT_CHECKLIST.md`: CSP本番デプロイチェックリスト、段階的導入フローガイド

**🏗️ DDD/クリーンアーキテクチャドキュメント**:
- `ddd-architecture.md`: DDD 4層構造アーキテクチャ概要、依存方向ルール、主要パターン
- `ddd-development-guide.md`: DDD開発ガイドライン、実装パターン、ベストプラクティス
- `ddd-testing-strategy.md`: DDD層別テスト戦略、Architecture Tests、テストパターン
- `ddd-troubleshooting.md`: DDDトラブルシューティングガイド、よくある問題と解決策

**Dockerトラブルシューティング**:
- `DOCKER_TROUBLESHOOTING.md`: Dockerトラブルシューティング完全ガイド
  - **ポート設定問題**: APP_PORT設定、ポート80で起動する問題の解決方法
  - **イメージ再ビルド**: Dockerfileビルド引数変更時の再ビルド手順
  - **完全クリーンアップ**: コンテナ・イメージ・ボリューム削除手順
  - **プロジェクト固有イメージ命名**: laravel-next-b2c/app による他プロジェクトとの競合回避

## 開発環境

### ⚡ 自動セットアップスクリプト（推奨）

**`make setup` コマンド一つで完全な開発環境を15分以内に構築**:

```bash
# 1. リポジトリのクローンと移動
git clone https://github.com/ef-tech/laravel-next-b2c.git
cd laravel-next-b2c

# 2. 一括セットアップ実行（15分以内）
make setup
```

**セットアップ内容**:
1. **前提条件チェック** (`check_prerequisites`):
   - Docker、Docker Compose、Node.js、npm、Git、makeのバージョン確認
   - 必要バージョン: Docker 20.10+、Node.js 18+、npm 9+、Git 2.30+
   - 不足している場合は推奨インストール方法を案内

2. **環境変数設定** (`setup_env`):
   - `.env`（ルート）、`.env.local`（フロントエンドアプリ）の自動生成
   - Laravelアプリケーションキー（APP_KEY）の自動生成
   - 既存の`.env`ファイルは保持（冪等性保証）

3. **依存関係インストール** (`install_dependencies`):
   - Composer依存関係インストール（backend/laravel-api）
   - npm依存関係インストール（ルート、admin-app、user-app）
   - Dockerイメージのプル（`docker compose pull --ignore-buildable`）

4. **サービス起動** (`start_services`):
   - Docker Composeによる全サービス起動（`docker compose up -d`）
   - 起動サービス: PostgreSQL、Redis、Mailpit、MinIO、Laravel API、User App、Admin App

5. **セットアップ検証** (`verify_setup`):
   - 全サービスのヘルスチェック（最大120秒待機）
   - Laravel API: http://localhost:13000/api/health
   - User App: http://localhost:13001
   - Admin App: http://localhost:13002

**部分的再実行機能**:
```bash
# エラーが発生した場合、指定されたステップから再実行可能
make setup-from STEP=install_dependencies

# 利用可能なステップ:
# - check_prerequisites
# - setup_env
# - install_dependencies
# - start_services
# - verify_setup
```

**冪等性保証**:
- 何度実行しても安全
- 既存の`.env`ファイルやAPP_KEYは保持
- 既存のDockerコンテナは再利用

**エラーハンドリング**:
- わかりやすいエラーメッセージ
- 解決策の具体的な提示
- 実行ログの詳細な記録

### Docker Compose構成（統合環境）
```yaml
サービス構成:
# バックエンド
- laravel-api: Laravel 12 API (PHP 8.4) - ポート: 13000
  - イメージ名: laravel-next-b2c/app（プロジェクト固有、他プロジェクトとの競合回避）
  - APP_PORTデフォルト値: 13000（Dockerfile最適化済み、ランタイム変更可能）
  - healthcheck: curl http://127.0.0.1:${APP_PORT}/api/health (5秒間隔、動的ポート対応)
    - エンドポイント: GET /api/health → { "status": "ok", "timestamp": "..." }
- pgsql: PostgreSQL 17-alpine - ポート: 13432
  - healthcheck: pg_isready -U sail (5秒間隔)
- redis: Redis alpine - ポート: 13379
  - healthcheck: redis-cli ping (5秒間隔)
- mailpit: 開発用メールサーバー - SMTP: 11025, UI: 13025
  - healthcheck: wget --spider http://127.0.0.1:8025 (10秒間隔)
- minio: オブジェクトストレージ - API: 13900, Console: 13010
  - healthcheck: mc ready local (10秒間隔、Codex指摘対応済み)
  - 従来のcurl healthcheck問題解消（mcコマンド使用推奨）

# フロントエンド
- admin-app: Next.js 15.5 管理者アプリ - ポート: 13002
  - healthcheck: curl http://127.0.0.1:13002/api/health (10秒間隔)
  - depends_on: laravel-api (healthy)
- user-app: Next.js 15.5 ユーザーアプリ - ポート: 13001
  - healthcheck: curl http://127.0.0.1:13001/api/health (10秒間隔)
  - depends_on: laravel-api (healthy)

# テスト環境
- e2e-tests: Playwright E2Eテスト (オンデマンド実行)
  - depends_on: admin-app, user-app, laravel-api (全てhealthy)
```

**Docker Compose統合の利点**:
- 全サービス一括起動（`docker compose up -d`）
- 統一されたネットワーク設定
- サービス間通信の最適化
- 環境変数の一元管理
- E2Eテスト環境の完全統合
- プロジェクト固有Dockerイメージ命名（laravel-next-b2c/app）による他プロジェクトとの競合回避

**ヘルスチェック機能統合**:
- 全サービスのヘルスチェック機能による起動状態監視
- `docker compose ps`でリアルタイム状態確認（healthy/unhealthy表示）
- 依存関係の自動管理（depends_on: service_healthy）による起動順序制御
- IPv4明示対応（localhost→127.0.0.1）によるDNS解決問題の回避
- サービス障害の早期検知と自動再起動対応

### Laravel Sail構成（個別起動）
```yaml
Laravel Sailサービス:
- laravel.test: メインアプリケーション (PHP 8.4)
- redis: キャッシュサーバー
- pgsql: PostgreSQL 17
- mailpit: 開発用メールサーバー
- minio: オブジェクトストレージ
```

### 必要ツール
- **Docker**: コンテナ実行環境
- **Docker Compose**: マルチコンテナ管理
- **Node.js**: フロントエンド開発 (LTS推奨)
- **Git**: バージョン管理

## 共通開発コマンド

### セットアップコマンド（初回セットアップ）
```bash
# 一括セットアップ（15分以内）
make setup

# 部分的再実行（エラー時）
make setup-from STEP=install_dependencies  # 依存関係インストールから再実行
make setup-from STEP=start_services         # サービス起動から再実行
make setup-from STEP=verify_setup           # 検証のみ再実行

# 利用可能なステップ
# - check_prerequisites: 前提条件チェック
# - setup_env: 環境変数設定
# - install_dependencies: 依存関係インストール
# - start_services: サービス起動
# - verify_setup: セットアップ検証
```

### 統合開発サーバー起動コマンド（日常開発、3ターミナル方式）

**🎯 推奨起動方式（シンプル・高速）**:

```bash
# Terminal 1: Dockerサービス起動（Laravel API + インフラ）
make dev              # Dockerサービス起動（PostgreSQL、Redis、Mailpit、MinIO、Laravel API）
make stop             # Dockerサービス停止
make clean            # Dockerコンテナ・ボリューム完全削除
make logs             # Dockerサービスログ表示
make ps               # Dockerサービス状態表示
make help             # 利用可能コマンド一覧表示

# Terminal 2: Admin App起動（ネイティブ、推奨）
cd frontend/admin-app
npm run dev           # ポート13002で起動

# Terminal 3: User App起動（ネイティブ、推奨）
cd frontend/user-app
npm run dev           # ポート13001で起動
```

**起動方式の特徴**:
- **Laravel API**: Docker起動（volume mount有効、**ホットリロード1秒以内実現**）
  - `compose.yaml` の `volumes` 設定により、ローカル変更が即座に反映
  - 再ビルド不要、`routes/api.php` 等の変更がリアルタイムで適用
- **Next.jsアプリ**: ネイティブ起動（Turbopack最高速パフォーマンス、ホットリロード1秒以内）
  - Docker起動よりも高速なHMR（Hot Module Replacement）
  - メモリ効率が良く、開発体験が最適化
- **シンプル化**: 複雑なTypeScript/Bash混在スクリプト（`scripts/dev/`）を完全削除
  - 標準的なDocker Composeコマンドのみ使用
  - 保守性・可読性の大幅向上
  - トラブルシューティングが容易

### Docker Compose（推奨 - 統合環境）
```bash
# リポジトリルートで実行

# 全サービス起動
docker compose up -d

# サービス状態確認（ヘルスチェック含む）
docker compose ps
# 出力例:
# NAME         STATUS        HEALTH
# laravel-api  Up 2 minutes  healthy
# admin-app    Up 2 minutes  healthy
# user-app     Up 2 minutes  healthy
# pgsql        Up 2 minutes  healthy
# redis        Up 2 minutes  healthy

# ヘルスチェック詳細確認
docker inspect --format='{{json .State.Health}}' <container-name>

# ログ確認
docker compose logs -f

# 特定サービスのログ確認
docker compose logs -f admin-app
docker compose logs -f user-app
docker compose logs -f laravel-api

# サービス再起動
docker compose restart admin-app
docker compose restart user-app

# 全サービス停止
docker compose down

# ボリューム含めて完全削除
docker compose down -v

# Laravel APIコマンド実行
docker compose exec laravel-api php artisan migrate
docker compose exec laravel-api php artisan db:seed

# E2Eテスト実行（全サービス起動後）
docker compose run --rm e2e-tests
```

### バックエンド (Laravel)
```bash
# 開発サーバー起動 (統合)
composer dev

# 個別コマンド
php artisan serve         # APIサーバー
php artisan queue:listen   # キュー処理
php artisan pail          # ログ監視
npm run dev               # Vite開発サーバー

# テスト実行 (Pest 4 + Architecture Tests)
composer test                    # Pest テストスイート実行（96.1%カバレッジ）
./vendor/bin/pest                # Pest 直接実行
./vendor/bin/pest --coverage     # カバレッジレポート生成
./vendor/bin/pest --parallel     # 並列実行
./vendor/bin/pest tests/Arch     # Architecture Testsのみ実行（依存方向検証）

# テストインフラ管理 (Makefile - プロジェクトルートから実行)
make quick-test                  # 高速SQLiteテスト（~2秒）
make test-pgsql                  # PostgreSQLテスト（本番同等、~5-10秒）
make test-parallel               # 並列テスト実行（4 Shard）
make test-coverage               # カバレッジレポート生成
make ci-test                     # CI/CD相当の完全テスト（~20-30秒）
make test-switch-sqlite          # SQLite環境に切り替え
make test-switch-pgsql           # PostgreSQL環境に切り替え
make test-setup                  # 並列テスト環境構築（PostgreSQL test DBs作成）
make test-cleanup                # テスト環境クリーンアップ（test DBs削除）
make test-db-check               # テスト用DB存在確認

# 推奨テストフロー
# 1. 日常開発: make quick-test (SQLite・2秒)
# 2. 機能完成時: make test-pgsql (PostgreSQL・5-10秒)
# 3. PR前: make ci-test (完全テスト・20-30秒)

# コード品質管理 (統合コマンド)
composer quality          # フォーマットチェック + 静的解析
composer quality:fix      # フォーマット自動修正 + 静的解析

# コードフォーマット (Laravel Pint)
composer pint             # 全ファイル自動フォーマット
composer pint:test        # フォーマットチェックのみ（修正なし）
composer pint:dirty       # Git変更ファイルのみフォーマット
vendor/bin/pint           # 直接実行

# 静的解析 (Larastan/PHPStan Level 8)
composer stan             # 静的解析実行
composer stan:baseline    # ベースライン生成（既存エラー記録）
vendor/bin/phpstan analyse  # 直接実行
```

### フロントエンド (Next.js)
```bash
# 各アプリディレクトリで実行
npm run dev    # 開発サーバー (Turbopack有効)
npm run build  # 本番ビルド
npm start      # 本番サーバー
npm run lint   # ESLintチェック

# モノレポルートから実行可能
npm run lint          # 全ワークスペースでlint実行
npm run lint:fix      # 全ワークスペースでlint自動修正
npm run format        # Prettier実行
npm run format:check  # Prettierチェックのみ
npm run type-check    # TypeScriptチェック

# テスト実行 (Jest + Testing Library)
npm test              # 全テスト実行
npm run test:watch    # ウォッチモード
npm run test:coverage # カバレッジレポート生成
npm run test:admin    # Admin Appのみテスト
npm run test:user     # User Appのみテスト
```

### E2Eテスト (Playwright)
```bash
# e2eディレクトリで実行
cd e2e

# セットアップ（初回のみ）
npm install
npx playwright install chromium

# テスト実行
npm test              # 全E2Eテスト実行
npm run test:ui       # UIモードで実行（デバッグ推奨）
npm run test:debug    # デバッグモード
npm run test:admin    # Admin Appテストのみ
npm run test:user     # User Appテストのみ
npm run report        # HTMLレポート表示

# CI/CD環境
npm run test:ci       # CI環境用実行（headless）

# コード生成（Codegen）
npm run codegen:admin # Admin App用テスト自動生成
npm run codegen:user  # User App用テスト自動生成
```

### Laravel Sail環境（個別起動）
```bash
# Laravel APIディレクトリで実行
cd backend/laravel-api

# 環境起動・停止
./vendor/bin/sail up -d
./vendor/bin/sail down

# Laravel Artisanコマンド
./vendor/bin/sail artisan <command>

# Composer操作
./vendor/bin/sail composer <command>
```

### 統合テスト実行コマンド（全テストスイート）
```bash
# 全テストスイート統合実行（プロジェクトルートから）
make test-all                # 全テスト実行（SQLite、約30秒）
make test-all-pgsql          # 全テスト実行（PostgreSQL並列、約5-10分）

# 個別テストスイート実行
make test-backend-only       # バックエンドテストのみ（約2秒）
make test-frontend-only      # フロントエンドテストのみ（約15秒）
make test-e2e-only           # E2Eテストのみ（約2-5分）

# PR前推奨テスト
make test-pr                 # Lint + PostgreSQL + カバレッジ（約3-5分）
make test-with-coverage      # 全テスト + カバレッジ（約5-10分）

# スモークテスト・診断
make test-smoke              # 高速ヘルスチェック（約5秒）
make test-diagnose           # テスト環境診断（ポート・環境変数・Docker・DB・ディスク・メモリ確認）

# テスト実行スクリプト直接実行
./scripts/test/run-all-tests.sh         # 全テストオーケストレーション
./scripts/test/run-backend-tests.sh     # バックエンドテスト（SQLite/PostgreSQL切替）
./scripts/test/run-frontend-tests.sh    # フロントエンドテスト（Jest並列実行）
./scripts/test/run-e2e-tests.sh         # E2Eテスト（Playwright 4 Shard）
./scripts/test/generate-test-report.sh  # テストレポート生成（JUnit XML統合）
./scripts/test/diagnose-test-env.sh     # テスト環境診断

# レポート出力先
# - JUnit XML: test-results/*.xml（バックエンド/フロントエンド/E2E統合）
# - カバレッジ: coverage/（Jest/Pest統合カバレッジ）
```

## 環境変数設定

### 🔒 セキュリティ強化設定
```env
# ユーザー登録セキュリティ
# - ユーザー登録時のpassword必須化（デフォルトパスワード削除済み）
# - RegisterRequest バリデーション: password required|min:8
# - UserFactory: パスワード生成強制（デフォルト値なし）

# Exception Handler設定
# - API専用JSONレスポンス（Web向けリダイレクト無効化）
# - AuthenticationException: 401 Unauthorized + JSON
# - ValidationException: 422 Unprocessable Entity + JSON + errors配列
```

### ポート設定 (カスタマイズ済み)

#### バックエンドポート (backend/laravel-api/.env)
```env
APP_PORT=13000                    # Laravel アプリケーション
                                  # - Dockerfileデフォルト値: 13000（最適化済み、旧80から変更）
                                  # - ランタイム変更可能（再ビルド不要）
                                  # - compose.yamlで環境変数として設定
FORWARD_REDIS_PORT=13379          # Redis
FORWARD_DB_PORT=13432             # PostgreSQL
FORWARD_MAILPIT_PORT=11025        # Mailpit SMTP
FORWARD_MAILPIT_DASHBOARD_PORT=13025  # Mailpit UI
FORWARD_MINIO_PORT=13900          # MinIO API
FORWARD_MINIO_CONSOLE_PORT=13010  # MinIO Console
```

#### フロントエンドポート（固定設定）
```env
# User App: http://localhost:13001
# - frontend/user-app/package.json の dev/start スクリプトで --port 13001 指定
# - Dockerfile: EXPOSE 13001
# - docker-compose.yml: ports: "13001:13001"

# Admin App: http://localhost:13002
# - frontend/admin-app/package.json の dev/start スクリプトで --port 13002 指定
# - Dockerfile: EXPOSE 13002
# - docker-compose.yml: ports: "13002:13002"
```

**ポート固定設計の利点**:
- **13000番台統一**: 複数プロジェクト並行開発時のポート競合回避
- **固定ポート**: チーム開発での環境統一、E2Eテスト安定性向上、Docker環境統一
- **デフォルトポート回避**: 他のNext.js/Laravelプロジェクトとの同時実行可能
- **Docker統合**: コンテナポートマッピングの一貫性、環境変数不要
- **E2Eテスト**: テストURLの固定化、環境差異の最小化
- **Dockerfileデフォルト値最適化**: APP_PORT=13000（旧80から変更、ランタイム変更可能）
- **プロジェクト固有イメージ**: laravel-next-b2c/app（他プロジェクトとの競合回避）

### E2Eテスト環境変数 (e2e/.env)
```env
E2E_ADMIN_URL=http://localhost:13002  # Admin App URL (固定ポート)
E2E_USER_URL=http://localhost:13001   # User App URL (固定ポート)
E2E_API_URL=http://localhost:13000    # Laravel API URL

E2E_ADMIN_EMAIL=admin@example.com     # 管理者メールアドレス
E2E_ADMIN_PASSWORD=password           # 管理者パスワード

E2E_USER_EMAIL=user@example.com       # ユーザーメールアドレス
E2E_USER_PASSWORD=password            # ユーザーパスワード
```

### 主要設定
- **Database**: SQLite (開発用デフォルト) / PostgreSQL (Docker環境)
  - **PostgreSQL接続設定** (`.env`):
    - `DB_CONNECTION=pgsql`
    - `DB_HOST=pgsql` (Docker) / `DB_HOST=127.0.0.1` (Native)
    - `DB_PORT=13432` (統一ポート)
    - `DB_CONNECT_TIMEOUT=5` (接続タイムアウト: 5秒)
    - `DB_STATEMENT_TIMEOUT=30000` (ステートメントタイムアウト: 30秒)
    - `DB_SSLMODE=prefer` (開発) / `DB_SSLMODE=verify-full` (本番)
  - **環境別テンプレート**: `backend/laravel-api/.env.docker`, `.env.native`, `.env.production`
- **Cache**: Database (デフォルト) / Redis (Docker環境)
- **Queue**: Database / Redis
- **Mail**: ログ出力 / Mailpit (開発環境)
- **File Storage**: Local / MinIO (オブジェクトストレージ)

## 🌳 Git Worktree並列開発環境

### 概要
**Git Worktree並列開発環境**により、**5-8個の独立した開発環境**を同時に起動し、複数の機能開発やバグ修正を並行して進めることができます。Claude Code等のAI支援開発ツールとの並列実行に最適化された環境です。

### 🎯 主な特徴
- ✅ **完全分離アーキテクチャ**: 各WorktreeにDB、Redis、Docker環境を独立して構築
- ✅ **ポート番号レンジ分離**: 自動ポート割り当て（衝突なし）
- ✅ **自動セットアップ**: `make worktree-create`コマンド一つで環境構築完了
- ✅ **ID再利用**: Worktree削除後、IDを自動的に再利用
- ✅ **Breaking Change対応**: 既存環境からの移行手順を完備

### 📊 ポート番号レンジ分離方式
各Worktreeは100ポートレンジを使用し、完全に分離されています：

| サービス | Worktree 0 | Worktree 1 | Worktree 2 | レンジ |
|---------|-----------|-----------|-----------|--------|
| **Laravel API** | 13000 | 13001 | 13002 | 13000-13099 |
| **User App** | 13100 | 13101 | 13102 | 13100-13199 |
| **Admin App** | 13200 | 13201 | 13202 | 13200-13299 |
| **MinIO Console** | 13300 | 13301 | 13302 | 13300-13399 |
| **PostgreSQL** | 14000 | 14001 | 14002 | 14000-14099 |
| **Redis** | 14100 | 14101 | 14102 | 14100-14199 |
| **Mailpit UI** | 14200 | 14201 | 14202 | 14200-14299 |
| **Mailpit SMTP** | 14300 | 14301 | 14302 | 14300-14399 |
| **MinIO API** | 14400 | 14401 | 14402 | 14400-14499 |

### 🗄️ データベース分離戦略
各Worktreeは完全に独立したデータベースを使用します：
- **DB名**: `laravel_wt0`, `laravel_wt1`, `laravel_wt2`...
- **キャッシュプレフィックス**: `wt0_`, `wt1_`, `wt2_`...
- **Dockerネットワーク**: `wt0-network`, `wt1-network`, `wt2-network`...
- **Dockerボリューム**: `wt0-pgsql`, `wt1-pgsql`, `wt2-pgsql`...

### 💾 リソース使用量
| Worktree数 | メモリ使用量 | 推奨システム要件 |
|-----------|------------|----------------|
| 1-2個 | ~1-2GB | 最小 8GB RAM |
| 3-5個 | ~3-5GB | 推奨 16GB RAM |
| 6-8個 | ~6-8GB | 推奨 32GB RAM |

### 🚀 Makefileコマンド
```bash
# Worktree作成
make worktree-create BRANCH=feature/existing-branch
make worktree-create BRANCH=feature/new-feature FROM=origin/main

# Worktree一覧表示
make worktree-list

# ポート番号一覧表示
make worktree-ports

# Worktree削除のみ
make worktree-remove PATH=../laravel-next-b2c-wt0

# Worktree + Docker完全削除（推奨）
make worktree-clean ID=0
make worktree-clean ID=../laravel-next-b2c-wt0
```

### 💡 使用例：2つのWorktreeで並列開発
```bash
# Terminal 1: Worktree 0でfeature/user-authを開発
make worktree-create BRANCH=feature/user-auth
cd ../laravel-next-b2c-wt0
make dev  # ポート: Laravel API=13000, User App=13100, Admin App=13200

# Terminal 2: Worktree 1でfix/cors-issueを開発
make worktree-create BRANCH=fix/cors-issue
cd ../laravel-next-b2c-wt1
make dev  # ポート: Laravel API=13001, User App=13101, Admin App=13201

# 両方のWorktreeが独立して動作
# - DB: laravel_wt0, laravel_wt1
# - キャッシュ: wt0_, wt1_
# - Dockerネットワーク: wt0-network, wt1-network
```

### 🔧 ポート管理スクリプト
```bash
# 次に利用可能なWorktree IDを取得
./scripts/worktree/port-manager.sh next-id

# Worktree IDからポート番号を計算
./scripts/worktree/port-manager.sh calculate-ports 0

# 全Worktreeのポート番号一覧を表示
./scripts/worktree/port-manager.sh list

# ポート番号からWorktree IDを逆算
./scripts/worktree/port-manager.sh reverse-lookup 13100
```

## セキュリティ・品質管理
- **トークンベース認証**: Laravel Sanctum 4.0によるセキュアなステートレス認証
- **🔐 包括的セキュリティヘッダー**: OWASP準拠の攻撃防御実装
  - **X-Frame-Options**: クリックジャッキング攻撃防止（Admin: DENY、User/Laravel: SAMEORIGIN）
  - **X-Content-Type-Options**: MIMEスニッフィング攻撃防止（全サービス: nosniff）
  - **Referrer-Policy**: リファラー情報漏洩防止（Admin: no-referrer、他: strict-origin-when-cross-origin）
  - **Content-Security-Policy**: XSS攻撃防御、動的CSP構築、Report-Only/Enforceモード切替可能
  - **Permissions-Policy**: ブラウザAPI悪用防止（User/Admin App設定済み）
  - **Strict-Transport-Security**: HTTPS強制、ダウングレード攻撃防止（本番環境のみ）
  - **CSP違反レポート収集**: Laravel/Next.js両対応、application/json互換性、違反分析による最適化
  - **段階的導入**: Report-Onlyモード運用 → 違反分析 → Enforceモード切り替え
- **CSRFプロテクション**: APIエンドポイント専用設定
- **CORS最適化**: Next.jsフロントエンドとの統合設定、credentials対応
- **XDEBUG**: 開発・デバッグサポート
- **環境分離**: .env設定による環境別管理
- **型安全性**: TypeScript全面採用
- **コード品質**: ESLint 9 + Prettier + Laravel Pint統合
- **自動品質チェック**: husky + lint-stagedによるpre-commitフック
- **モダンテストフレームワーク**: Pest 4による包括的テスト（12+テストケース）、Architecture Testingサポート
- **統合.gitignore**: モノレポ全体のファイル管理（2024年12月更新）

## パフォーマンス最適化 - 🏆 業界標準以上の成果
### バックエンド最適化
- **最小依存関係**: 114→**4パッケージ** (96.5%削減)
- **ステートレス設計**: セッション除去でメモリ効率最大化
- **API専用最適化**: Web機能削除で起動速度**33.3%向上**
- **Redis**: 高速キャッシング (セッションストレージ不使用)
- **PostgreSQL**: 高性能データベース、ステートレス設計対応

### フロントエンド最適化
- **Turbopack**: Next.js 15.5最新バンドラーで高速ビルド
- **React 19**: 最新のConcurrent Featuresでパフォーマンス最大化
- **Tailwind CSS 4**: 最新CSSフレームワークでスタイル効率化

### 統合最適化
- **オプコード最適化**: Laravel標準最適化 + カスタム追加最適化
- **定量的パフォーマンス測定**: 90+テストケースで継続的パフォーマンス監視
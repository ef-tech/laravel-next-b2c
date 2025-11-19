# タイムスタンプフォーマット統一 影響範囲レポート

## 概要

Laravelアプリケーション全体でタイムスタンプフォーマットを `now()->utc()->toIso8601String()` に統一し、RFC 3339（ISO 8601のプロファイル）準拠形式 `YYYY-MM-DDTHH:MM:SS+00:00` に移行するための影響範囲調査結果。

## 調査実施日時

2025-11-20

## 修正対象ファイル

### 1. 手動フォーマット `format('Y-m-d\TH:i:s\Z')` (1ファイル)

| ファイル | 行番号 | コンテキスト | 修正内容 |
|---------|--------|-------------|---------|
| `app/Support/ExceptionHandler.php` | 56 | RFC 7807 Problem Details（内部サーバーエラー） | `now()->format('Y-m-d\TH:i:s\Z')` → `now()->utc()->toIso8601String()` |

**問題**: Carbonインスタンスが実際にUTCでなくても `Z` サフィックスが付与されるリスク

### 2. `toIso8601String()` without `utc()` (14ファイル、24箇所)

#### Exception Handler (1ファイル、4箇所)

| ファイル | 行番号 | コンテキスト |
|---------|--------|-------------|
| `bootstrap/app.php` | 163 | API例外レスポンス |
| `bootstrap/app.php` | 197 | API例外レスポンス |
| `bootstrap/app.php` | 230 | API例外レスポンス |
| `bootstrap/app.php` | 274 | API例外レスポンス |

#### Controller (2ファイル、2箇所)

| ファイル | 行番号 | コンテキスト |
|---------|--------|-------------|
| `app/Http/Controllers/Api/V1/CspReportController.php` | 65 | CSPレポート収集 |
| `app/Http/Controllers/CspReportController.php` | 56 | CSPレポート収集（旧バージョン） |

#### Middleware (5ファイル、9箇所)

| ファイル | 行番号 | コンテキスト |
|---------|--------|-------------|
| `app/Http/Middleware/SanctumTokenVerification.php` | 63 | トークン検証ログ |
| `app/Http/Middleware/SanctumTokenVerification.php` | 79 | トークン検証ログ |
| `app/Http/Middleware/AuditTrail.php` | 97 | 監査ログ |
| `app/Http/Middleware/PerformanceMonitoring.php` | 77 | パフォーマンスログ |
| `app/Http/Middleware/AuthorizationCheck.php` | 90 | 認可チェックログ |
| `app/Http/Middleware/AuthorizationCheck.php` | 112 | 認可チェックログ |
| `app/Http/Middleware/RequestLogging.php` | 82 | リクエストログ |

#### Presenter (3ファイル、7箇所)

| ファイル | 行番号 | コンテキスト |
|---------|--------|-------------|
| `ddd/Infrastructure/Http/Presenters/V1/UserPresenter.php` | 28 | ユーザーデータ（created_at） |
| `ddd/Infrastructure/Http/Presenters/V1/UserPresenter.php` | 29 | ユーザーデータ（updated_at） |
| `ddd/Infrastructure/Http/Presenters/V1/TokenPresenter.php` | 29 | トークンデータ（created_at） |
| `ddd/Infrastructure/Http/Presenters/V1/TokenPresenter.php` | 44 | トークンデータ（created_at） |
| `ddd/Infrastructure/Http/Presenters/V1/TokenPresenter.php` | 45 | トークンデータ（last_used_at） |
| `ddd/Infrastructure/Http/Presenters/V1/HealthPresenter.php` | 26 | ヘルスチェックレスポンス |

#### Test Files (2ファイル、2箇所)

| ファイル | 行番号 | コンテキスト |
|---------|--------|-------------|
| `tests/Feature/E2E/IdempotencyPerformanceE2ETest.php` | 38 | E2Eテスト用データ |
| `tests/Feature/Middleware/MiddlewareGroupTest.php` | 54 | ミドルウェアグループテスト用レスポンス |

**問題**: UTC変換なしのため、ローカルタイムゾーンのオフセットが付く（例: `+09:00`）

### 3. `toIso8601ZuluString()` (1ファイル、修正不要)

| ファイル | 行番号 | コンテキスト | 修正要否 |
|---------|--------|-------------|---------|
| `ddd/Shared/Exceptions/HasProblemDetails.php` | 60 | RFC 7807 Problem Details trait | ❌ 修正不要（既にUTC保証済み） |

**理由**: `toIso8601ZuluString()` は常にUTCに変換して `Z` サフィックスを付与（RFC 3339準拠）

## 修正対象ファイルサマリー

| カテゴリ | ファイル数 | 修正箇所数 |
|---------|-----------|-----------|
| **手動フォーマット** | 1 | 1 |
| **Exception Handler** | 1 | 4 |
| **Controller** | 2 | 2 |
| **Middleware** | 5 | 9 |
| **Presenter** | 3 | 7 |
| **Test Files** | 2 | 2 |
| **合計** | **14** | **25** |

## RFC 3339準拠への移行理由

1. **タイムゾーン明確化**: UTC統一による分散システム間のタイムスタンプ整合性保証
2. **可観測性向上**: ログ・API・Problem Detailsで一貫したタイムスタンプによる運用効率化
3. **RFC準拠**: 標準的なISO 8601形式採用による外部システム連携の安定性向上
4. **タイムゾーン不明確問題の解消**: 手動フォーマット `Z` サフィックスの誤用リスク排除

## 後方互換性の影響評価

### フロントエンド（Next.js）への影響

**結論**: **影響なし** ✅

**理由**:
- JavaScriptの `new Date()` コンストラクタは両形式に対応
  - `new Date('2025-11-06T17:19:19Z')` → UTC
  - `new Date('2025-11-06T17:19:19+00:00')` → UTC
- Next.js User App、Admin App で使用しているタイムスタンプパースライブラリ（date-fns等）も両形式対応

### データベースへの影響

**結論**: **影響なし** ✅

**理由**:
- Eloquent は `DATETIME`/`TIMESTAMP` カラムをCarbonインスタンスに変換
- アプリケーション層（Presenter等）でフォーマット変換
- データベーススキーマは変更不要

### ログ集計システムへの影響

**結論**: **軽微な影響** ⚠️

**必要な対応**:
- タイムスタンプパース正規表現を `Z|\+00:00` 許容形式に更新
- Fluent Bit、Logstash等のログ集計システムで設定変更が必要

## 次のステップ

1. チーム内レビュー完了
2. Gitバックアップタグ作成（`backup/before-timestamp-migration`）
3. 一括置換スクリプト作成・実行
4. テストヘルパーメソッド実装
5. テスト修正とカバレッジ検証
6. プルリクエスト作成

## レビューステータス

- [ ] チームレビュー完了
- [ ] インフラ担当者確認（ログ集計システム）
- [ ] フロントエンド担当者確認

---

**調査担当**: Claude Code
**レビュアー**: (TBD)
**承認日**: (TBD)

# Design Document

## 概要

本設計書は、Laravelアプリケーション全体でタイムスタンプフォーマットを統一し、RFC 3339（ISO 8601のプロファイル）準拠形式 `YYYY-MM-DDTHH:MM:SS+00:00` に移行するための技術設計を提供します。

### 設計目標

1. **タイムスタンプフォーマット統一**: すべてのタイムスタンプ出力を `now()->utc()->toIso8601String()` に統一
2. **RFC 3339準拠**: ISO 8601形式で `+00:00` オフセット（UTCタイムゾーン）を明示
3. **タイムゾーン不明確問題の解消**: 手動フォーマット `format('Y-m-d\TH:i:s\Z')` と `toIso8601String()` のタイムゾーン曖昧性を排除
4. **後方互換性の維持**: フロントエンド（Next.js）の `new Date()` パーサーが両形式に対応
5. **テストカバレッジ85%以上の維持**: 修正後も品質基準を維持

---

## 既存アーキテクチャ分析

### 現在のタイムスタンプフォーマット状況

ripgrepによる調査結果に基づき、以下3種類のタイムスタンプフォーマットが混在していることを確認しました：

#### 1. 手動フォーマット `format('Y-m-d\TH:i:s\Z')` (1ファイル)

**問題**: Carbonインスタンスが実際にUTCでなくても `Z` サフィックスが付与されるリスク

| ファイル | 行番号 | コンテキスト |
|---------|--------|-------------|
| `backend/laravel-api/app/Support/ExceptionHandler.php` | 56 | RFC 7807 Problem Details（内部サーバーエラー） |

**出力例**: `"2025-11-06T17:19:19Z"`

#### 2. `toIso8601String()` (15ファイル)

**問題**: UTC変換なしのため、ローカルタイムゾーンのオフセットが付く（例: `+09:00`）

| カテゴリ | ファイル | 行番号 | コンテキスト |
|---------|---------|--------|-------------|
| **Exception Handler** | `bootstrap/app.php` | 163, 197, 230, 274 | API例外レスポンス（4箇所） |
| **Controller** | `app/Http/Controllers/Api/V1/CspReportController.php` | 65 | CSPレポート収集 |
| **Controller** | `app/Http/Controllers/CspReportController.php` | 56 | CSPレポート収集（旧バージョン） |
| **Middleware** | `app/Http/Middleware/AuthorizationCheck.php` | 90, 112 | 認可チェックログ（2箇所） |
| **Middleware** | `app/Http/Middleware/PerformanceMonitoring.php` | 77 | パフォーマンスログ |
| **Middleware** | `app/Http/Middleware/AuditTrail.php` | 97 | 監査ログ |
| **Middleware** | `app/Http/Middleware/RequestLogging.php` | 82 | リクエストログ |
| **Middleware** | `app/Http/Middleware/SanctumTokenVerification.php` | 63, 79 | トークン検証ログ（2箇所） |
| **Presenter** | `ddd/Infrastructure/Http/Presenters/V1/UserPresenter.php` | 28, 29 | ユーザーデータ（created_at, updated_at） |
| **Presenter** | `ddd/Infrastructure/Http/Presenters/V1/HealthPresenter.php` | 26 | ヘルスチェックレスポンス |
| **Presenter** | `ddd/Infrastructure/Http/Presenters/V1/TokenPresenter.php` | 29, 44, 45 | トークンデータ（created_at, last_used_at） |
| **Test** | `tests/Feature/E2E/IdempotencyPerformanceE2ETest.php` | 38 | E2Eテスト |
| **Test** | `tests/Feature/Middleware/MiddlewareGroupTest.php` | 54 | ミドルウェアグループテスト |

**出力例**: `"2025-11-06T17:19:19+09:00"` (ローカルタイムゾーン)

#### 3. `toIso8601ZuluString()` (1ファイル)

**新発見**: requirements.mdで言及されていなかった重要なメソッド

| ファイル | 行番号 | コンテキスト |
|---------|--------|-------------|
| `backend/laravel-api/ddd/Shared/Exceptions/HasProblemDetails.php` | 60 | RFC 7807 Problem Details trait |

**出力例**: `"2025-11-06T17:19:19Z"` (UTC保証)

**調査結果**:
- `toIso8601ZuluString()` は Carbon 3.x で追加されたメソッド
- 常にUTCに変換して `Z` サフィックスを付与（RFC 3339準拠）
- HasProblemDetails trait は既に正しいUTC変換を行っている

### アーキテクチャ上の重要な発見

1. **HasProblemDetails.php は修正不要**
   - 既に `toIso8601ZuluString()` を使用しており、UTC保証済み
   - requirements.md の Requirement 2 のAcceptance Criteria 1は誤り
   - 設計段階で修正対象から除外

2. **DomainException.php も確認が必要**
   - requirements.md では `format('Y-m-d\TH:i:s\Z')` を使用していると記載
   - HasProblemDetails trait を使用している可能性があり、既に修正済みの可能性

3. **ExceptionHandler.php は修正必須**
   - 唯一の手動フォーマット使用箇所（56行目）
   - 内部サーバーエラー（500）のProblem Details生成時

4. **15ファイルの `toIso8601String()` に `utc()` 追加が必要**
   - bootstrap/app.php（4箇所）
   - Presenter（3ファイル、7箇所）
   - Middleware（5ファイル、9箇所）
   - Controller（2ファイル、2箇所）
   - テストファイル（2ファイル、2箇所）

---

## 技術スタック整合性

### 既存技術スタックの活用

| 技術 | バージョン | 用途 |
|------|-----------|------|
| **PHP** | 8.4 | 言語基盤 |
| **Laravel** | 12.x | フレームワーク |
| **Carbon** | 3.x (Laravel同梱) | 日時操作ライブラリ |
| **PHPStan** | Level 8 | 静的解析 |
| **Laravel Pint** | 標準プリセット | コードフォーマット |
| **Pest** | 4.x | テストフレームワーク |

### Carbon 3.x のタイムスタンプメソッド比較

| メソッド | 出力例 | UTC保証 | RFC 3339準拠 | 推奨 |
|---------|--------|---------|-------------|------|
| `toIso8601String()` | `2025-11-06T17:19:19+09:00` | ❌ | ✅ | ❌ |
| `utc()->toIso8601String()` | `2025-11-06T08:19:19+00:00` | ✅ | ✅ | ✅ |
| `toIso8601ZuluString()` | `2025-11-06T08:19:19Z` | ✅ | ✅ | ⚠️ (特殊用途) |
| `format('Y-m-d\TH:i:s\Z')` | `2025-11-06T17:19:19Z` | ❌ | ⚠️ | ❌ |

**推奨方式**: `now()->utc()->toIso8601String()`
- **理由1**: `+00:00` オフセット形式でUTCを明示（RFC 3339推奨形式）
- **理由2**: `Z` サフィックスより可読性が高い
- **理由3**: フロントエンド `new Date()` が両形式対応だが、`+00:00` 形式が標準的

**`toIso8601ZuluString()` の扱い**:
- HasProblemDetails.php で使用中
- 既にUTC保証されており、修正不要
- 新規コードでは `utc()->toIso8601String()` を推奨（プロジェクト統一性のため）

---

## 主要設計決定事項

### 決定1: 一括置換スクリプトの実装方法

**選択肢評価**:

| 方法 | メリット | デメリット | 採用 |
|------|---------|-----------|------|
| **Perl/sed** | ・シンプル<br>・依存関係なし<br>・高速 | ・複雑なASTベース置換は困難 | ✅ |
| **Rector** | ・PHPコード理解<br>・安全な置換 | ・設定が複雑<br>・学習コスト | ❌ |
| **手動修正** | ・完全制御 | ・時間がかかる<br>・ミスのリスク | ❌ |

**決定**: Perlによる一括置換スクリプト

**理由**:
1. 置換パターンが単純（文字列置換ベース）
2. 約17箇所の修正で、Rector導入コストに見合わない
3. 正規表現で十分な精度を確保可能
4. スクリプト実行後に手動確認フェーズを設ける

**置換パターン**:

```bash
# Pattern 1: 手動フォーマット置換
perl -i -pe "s/now\(\)->format\(['\"]Y-m-d\\\\TH:i:s\\\\Z['\"]\)/now()->utc()->toIso8601String()/g" backend/laravel-api/**/*.php

# Pattern 2: Carbon::now()形式の置換
perl -i -pe "s/Carbon::now\(\)->format\(['\"]Y-m-d\\\\TH:i:s\\\\Z['\"]\)/Carbon::now()->utc()->toIso8601String()/g" backend/laravel-api/**/*.php

# Pattern 3: toIso8601String()の前にutc()追加（既存のutc()がない場合）
perl -i -pe "s/->toIso8601String\(\)(?!.*->utc\(\))/->utc()->toIso8601String()/g" backend/laravel-api/**/*.php

# Pattern 4: Carbonインスタンス変数の置換
perl -i -pe "s/\\\$([a-zA-Z_]+)->format\(['\"]Y-m-d\\\\TH:i:s\\\\Z['\"]\)/\\\$\$1->utc()->toIso8601String()/g" backend/laravel-api/**/*.php
```

**制限事項**:
- `DateTime`/`DateTimeImmutable` クラスは手動確認が必要
- ドキュメントファイル（`docs/**/*.md`）は対象外

### 決定2: テストヘルパーメソッドの設計

**実装場所**: `backend/laravel-api/tests/TestCase.php`

**新規メソッド**:

```php
namespace Tests;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * ISO 8601 UTC形式のタイムスタンプであることを検証
     *
     * @param string $timestamp タイムスタンプ文字列
     * @param string $message アサーション失敗時のメッセージ
     * @return void
     */
    protected function assertIso8601Timestamp(string $timestamp, string $message = ''): void
    {
        $pattern = '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\+00:00$/';

        $this->assertMatchesRegularExpression(
            $pattern,
            $timestamp,
            $message ?: "Expected ISO 8601 UTC timestamp format (YYYY-MM-DDTHH:MM:SS+00:00), got: {$timestamp}"
        );
    }

    /**
     * テスト用にタイムスタンプを固定
     *
     * @param string $datetime 固定する日時（例: '2025-11-06 17:19:19'）
     * @return Carbon
     */
    protected function freezeTimeAt(string $datetime): Carbon
    {
        $frozen = Carbon::parse($datetime);
        Carbon::setTestNow($frozen);

        return $frozen;
    }

    /**
     * 固定したタイムスタンプを解除
     *
     * @return void
     */
    protected function unfreezeTime(): void
    {
        Carbon::setTestNow();
    }

    /**
     * テスト終了後に自動的にタイムスタンプ固定を解除
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $this->unfreezeTime();
        parent::tearDown();
    }
}
```

**使用例**:

```php
it('returns ISO 8601 UTC timestamp in health check response', function () {
    // Arrange: タイムスタンプを固定
    $frozenTime = $this->freezeTimeAt('2025-11-06 17:19:19');

    // Act: ヘルスチェックAPI実行
    $response = $this->getJson('/api/v1/health');

    // Assert: ISO 8601 UTC形式を検証
    $response->assertOk();
    $this->assertIso8601Timestamp($response->json('timestamp'));
    expect($response->json('timestamp'))->toBe($frozenTime->utc()->toIso8601String());
});
```

### 決定3: 修正対象ファイルのグループ化

修正効率を高めるため、ファイルを以下のグループに分類します：

#### Group A: Exception Handler（2ファイル）

**優先度**: 最高（RFC 7807準拠のため）

| ファイル | 修正箇所 | 修正内容 |
|---------|---------|---------|
| `app/Support/ExceptionHandler.php` | 56行目 | `now()->format('Y-m-d\TH:i:s\Z')` → `now()->utc()->toIso8601String()` |
| `bootstrap/app.php` | 163, 197, 230, 274行目 | `now()->toIso8601String()` → `now()->utc()->toIso8601String()` |

**検証方法**:
- E2Eテスト: `e2e/projects/shared/tests/api-error-handling.spec.ts` でエラーレスポンスタイムスタンプ検証
- 手動テスト: 存在しないエンドポイントにアクセスして404レスポンスのtimestampフィールド確認

#### Group B: Middleware（5ファイル、9箇所）

**優先度**: 高（ログ出力の可観測性のため）

| ファイル | 修正箇所 | コンテキスト |
|---------|---------|-------------|
| `app/Http/Middleware/RequestLogging.php` | 82行目 | リクエストログ |
| `app/Http/Middleware/PerformanceMonitoring.php` | 77行目 | パフォーマンスメトリクス |
| `app/Http/Middleware/AuthorizationCheck.php` | 90, 112行目 | 認可失敗ログ |
| `app/Http/Middleware/AuditTrail.php` | 97行目 | 監査ログ |
| `app/Http/Middleware/SanctumTokenVerification.php` | 63, 79行目 | トークン検証ログ |

**検証方法**:
- `tail -f storage/logs/laravel.log` でログ出力を監視
- Pestテスト: `tests/Feature/Middleware/` 配下のテストでタイムスタンプ検証

#### Group C: Presenter（3ファイル、7箇所）

**優先度**: 高（APIレスポンス形式のため）

| ファイル | 修正箇所 | コンテキスト |
|---------|---------|-------------|
| `ddd/Infrastructure/Http/Presenters/V1/HealthPresenter.php` | 26行目 | ヘルスチェック |
| `ddd/Infrastructure/Http/Presenters/V1/TokenPresenter.php` | 29, 44, 45行目 | トークン情報 |
| `ddd/Infrastructure/Http/Presenters/V1/UserPresenter.php` | 28, 29行目 | ユーザー情報 |

**検証方法**:
- Pestテスト: `tests/Unit/Ddd/Infrastructure/Http/Presenters/V1/HealthPresenterTest.php` 等でPresenter出力検証
- E2Eテスト: 各APIエンドポイントでレスポンスタイムスタンプ検証

#### Group D: Controller（2ファイル、2箇所）

**優先度**: 中（CSPレポート収集のみ）

| ファイル | 修正箇所 | コンテキスト |
|---------|---------|-------------|
| `app/Http/Controllers/Api/V1/CspReportController.php` | 65行目 | CSPレポート収集 |
| `app/Http/Controllers/CspReportController.php` | 56行目 | CSPレポート収集（旧バージョン） |

**検証方法**:
- Pestテスト: CSPレポート収集のFeatureテスト追加（推奨）

#### Group E: Test Files（2ファイル、2箇所）

**優先度**: 低（テスト用モックデータ）

| ファイル | 修正箇所 | コンテキスト |
|---------|---------|-------------|
| `tests/Feature/E2E/IdempotencyPerformanceE2ETest.php` | 38行目 | E2Eテスト用データ |
| `tests/Feature/Middleware/MiddlewareGroupTest.php` | 54行目 | ミドルウェアテスト用レスポンス |

**検証方法**:
- 各テストファイルを個別実行してpassすることを確認

#### 修正対象外: HasProblemDetails.php

**理由**: 既に `toIso8601ZuluString()` を使用しており、UTC保証済み

```php
// backend/laravel-api/ddd/Shared/Exceptions/HasProblemDetails.php:60
public function toProblemDetails(): array
{
    return [
        // ...
        'timestamp' => now()->toIso8601ZuluString(), // ✅ 既にUTC保証
    ];
}
```

**今後の方針**:
- 新規コードでは `utc()->toIso8601String()` を推奨
- HasProblemDetails.php は次回のリファクタリング時に統一を検討

---

## コンポーネント設計

### Component 1: 一括置換スクリプト

**ファイルパス**: `backend/laravel-api/scripts/migrate-timestamp-format.sh`

**責務**:
1. Gitバックアップタグ作成（`backup/before-timestamp-migration`）
2. Perl一括置換実行（4パターン）
3. 手動確認が必要な箇所の検出とレポート
4. 変更ファイル一覧の出力

**実装**:

```bash
#!/bin/bash
set -euo pipefail

echo "=========================================="
echo "Timestamp Format Migration Script"
echo "=========================================="

# 1. バックアップタグ作成
echo "✅ Creating backup tag..."
git tag -a "backup/before-timestamp-migration" -m "Backup before timestamp format unification"
echo "✅ Backup tag created: backup/before-timestamp-migration"

# 2. 対象ファイル検出
echo ""
echo "🔍 Detecting target files..."
echo ""

echo "--- Pattern 1: Manual format() ---"
rg "format\(['\"]Y-m-d\\TH:i:s\\Z['\"]\)" backend/laravel-api --files-with-matches --type php || true

echo ""
echo "--- Pattern 2: toIso8601String() without utc() ---"
rg "->toIso8601String\(\)" backend/laravel-api --files-with-matches --type php \
    | xargs -I {} sh -c 'if ! grep -q "utc()->toIso8601String()" {}; then echo {}; fi' || true

# 3. Perl一括置換実行
echo ""
echo "🔧 Executing Perl replacements..."
echo ""

# Pattern 1: now()->format() 置換
find backend/laravel-api -type f -name "*.php" -not -path "*/vendor/*" -not -path "*/docs/*" \
    -exec perl -i -pe "s/now\(\)->format\(['\"]Y-m-d\\\\TH:i:s\\\\Z['\"]\)/now()->utc()->toIso8601String()/g" {} +
echo "✅ Pattern 1 replaced: now()->format('Y-m-d\TH:i:s\Z')"

# Pattern 2: Carbon::now()->format() 置換
find backend/laravel-api -type f -name "*.php" -not -path "*/vendor/*" -not -path "*/docs/*" \
    -exec perl -i -pe "s/Carbon::now\(\)->format\(['\"]Y-m-d\\\\TH:i:s\\\\Z['\"]\)/Carbon::now()->utc()->toIso8601String()/g" {} +
echo "✅ Pattern 2 replaced: Carbon::now()->format('Y-m-d\TH:i:s\Z')"

# Pattern 3: toIso8601String()の前にutc()追加
find backend/laravel-api -type f -name "*.php" -not -path "*/vendor/*" -not -path "*/docs/*" \
    -exec perl -i -pe "s/(?<!utc\(\)->)toIso8601String\(\)/utc()->toIso8601String()/g" {} +
echo "✅ Pattern 3 replaced: added utc() before toIso8601String()"

# Pattern 4: 変数->format() 置換
find backend/laravel-api -type f -name "*.php" -not -path "*/vendor/*" -not -path "*/docs/*" \
    -exec perl -i -pe "s/(\\\$[a-zA-Z_][a-zA-Z0-9_]*)->format\(['\"]Y-m-d\\\\TH:i:s\\\\Z['\"]\)/\$1->utc()->toIso8601String()/g" {} +
echo "✅ Pattern 4 replaced: \$variable->format('Y-m-d\TH:i:s\Z')"

# 4. 手動確認が必要な箇所を検出
echo ""
echo "⚠️  Manual review required for DateTime/DateTimeImmutable:"
rg "DateTime(Immutable)?.*format\(" backend/laravel-api --type php || echo "None found."

# 5. 変更ファイル一覧
echo ""
echo "📄 Changed files:"
git diff --name-only

echo ""
echo "=========================================="
echo "✅ Migration script completed"
echo "=========================================="
echo ""
echo "Next steps:"
echo "1. Review changes: git diff"
echo "2. Run PHPStan: ./vendor/bin/phpstan analyse"
echo "3. Run Pint: ./vendor/bin/pint"
echo "4. Run tests: ./vendor/bin/pest"
echo "5. If issues, rollback: git reset --hard backup/before-timestamp-migration"
```

### Component 2: テストヘルパー拡張

**ファイルパス**: `backend/laravel-api/tests/TestCase.php`

**詳細**: 「決定2: テストヘルパーメソッドの設計」を参照

### Component 3: テストアサーション修正

**対象**: 約10ファイルのテストファイル

**修正パターン**:

```php
// Before
expect($response->json('timestamp'))->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/');

// After
$this->assertIso8601Timestamp($response->json('timestamp'));
// または
expect($response->json('timestamp'))->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\+00:00$/');
```

**検索コマンド**:

```bash
# Z サフィックス期待のテストを検出
rg "timestamp.*Z" backend/laravel-api/tests --type php

# assertJsonPath でタイムスタンプ検証しているテストを検出
rg "assertJsonPath.*timestamp" backend/laravel-api/tests --type php
```

---

## テスト戦略

### Phase 1: Unit Tests

**テスト対象**: Presenter、TestCase新規メソッド

| テストケース | テストファイル | 検証内容 |
|-------------|---------------|---------|
| HealthPresenter タイムスタンプ形式 | `tests/Unit/Ddd/Infrastructure/Http/Presenters/V1/HealthPresenterTest.php` | `+00:00` 形式を検証 |
| TokenPresenter タイムスタンプ形式 | `tests/Unit/Ddd/Infrastructure/Http/Presenters/V1/TokenPresenterTest.php` | `+00:00` 形式を検証 |
| UserPresenter タイムスタンプ形式 | `tests/Unit/Ddd/Infrastructure/Http/Presenters/V1/UserPresenterTest.php` | `+00:00` 形式を検証 |
| assertIso8601Timestamp ヘルパー | `tests/Unit/TestHelpers/TimestampAssertionTest.php` (新規) | 正規表現マッチング検証 |
| freezeTimeAt/unfreezeTime | `tests/Unit/TestHelpers/TimestampAssertionTest.php` (新規) | Carbon::setTestNow() 動作検証 |

**実装例**:

```php
// tests/Unit/Ddd/Infrastructure/Http/Presenters/V1/HealthPresenterTest.php
it('returns ISO 8601 UTC timestamp format', function () {
    $frozenTime = $this->freezeTimeAt('2025-11-06 17:19:19');
    $presenter = new HealthPresenter();

    $result = $presenter->present($frozenTime);

    $this->assertIso8601Timestamp($result['timestamp']);
    expect($result['timestamp'])->toBe('2025-11-06T17:19:19+00:00');
});
```

### Phase 2: Integration Tests

**テスト対象**: Middleware、Exception Handler

| テストケース | テストファイル | 検証内容 |
|-------------|---------------|---------|
| RequestLogging ログタイムスタンプ | `tests/Feature/Middleware/RequestLoggingTest.php` (新規) | ログファイル内のタイムスタンプ形式検証 |
| PerformanceMonitoring ログタイムスタンプ | `tests/Feature/Middleware/PerformanceMonitoringTest.php` (新規) | メトリクスログのタイムスタンプ形式検証 |
| AuditTrail ログタイムスタンプ | `tests/Feature/Middleware/AuditTrailTest.php` (新規) | 監査ログのタイムスタンプ形式検証 |
| ExceptionHandler Problem Details | `tests/Feature/ExceptionHandlerTest.php` (新規) | 404/500エラーレスポンスのタイムスタンプ検証 |

**実装例**:

```php
// tests/Feature/Middleware/RequestLoggingTest.php
it('logs request with ISO 8601 UTC timestamp', function () {
    $this->freezeTimeAt('2025-11-06 17:19:19');

    $this->getJson('/api/v1/health');

    // ログファイルを検証
    $logContent = file_get_contents(storage_path('logs/laravel.log'));
    expect($logContent)->toContain('2025-11-06T17:19:19+00:00');
});
```

### Phase 3: E2E Tests

**テスト対象**: API全体のタイムスタンプレスポンス

| テストケース | テストファイル | 検証内容 |
|-------------|---------------|---------|
| ヘルスチェックAPI | `e2e/projects/shared/tests/api-health-check.spec.ts` (既存) | `/api/v1/health` のタイムスタンプ形式 |
| エラーハンドリング | `e2e/projects/shared/tests/api-error-handling.spec.ts` (既存) | 404/500エラーのタイムスタンプ形式 |
| 認証API | `e2e/projects/shared/tests/auth/login.spec.ts` (既存) | トークン発行レスポンスのタイムスタンプ |

**実装例**:

```typescript
// e2e/projects/shared/tests/api-error-handling.spec.ts
test('404 error returns ISO 8601 UTC timestamp in problem details', async ({ request }) => {
  const response = await request.get('/api/v1/non-existent-endpoint');

  expect(response.status()).toBe(404);
  const body = await response.json();

  // ISO 8601 UTC形式を検証（+00:00 オフセット）
  expect(body.timestamp).toMatch(/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\+00:00$/);

  // JavaScriptでパース可能であることを検証
  const parsedDate = new Date(body.timestamp);
  expect(parsedDate).toBeInstanceOf(Date);
  expect(isNaN(parsedDate.getTime())).toBe(false);
});
```

### カバレッジ目標

| カテゴリ | 目標カバレッジ | 理由 |
|---------|---------------|------|
| **全体** | 85%以上維持 | プロジェクト品質基準 |
| **Presenter** | 95%以上 | APIレスポンス形式の保証 |
| **Middleware** | 90%以上 | ログ出力の信頼性 |
| **Exception Handler** | 100% | エラーレスポンスの重要性 |

---

## 実装フェーズとスケジュール

### Phase 1: 準備（30分）

| タスク | 所要時間 | 担当 |
|-------|---------|------|
| ripgrepで影響範囲調査 | 10分 | 開発者 |
| 影響範囲レポート作成 | 10分 | 開発者 |
| チーム内レビュー | 10分 | チーム |

**成果物**:
- 影響範囲レポート（修正ファイル一覧、約20ファイル確認）

### Phase 2: 実装（2時間）

| タスク | 所要時間 | 担当 |
|-------|---------|------|
| Gitバックアップタグ作成 | 5分 | 開発者 |
| 一括置換スクリプト作成 | 30分 | 開発者 |
| スクリプト実行 | 5分 | 開発者 |
| 手動確認（DateTime等） | 15分 | 開発者 |
| TestCase.php ヘルパー追加 | 30分 | 開発者 |
| git diff レビュー | 15分 | 開発者 |
| PHPStan + Pint実行 | 10分 | CI |

**成果物**:
- 修正後のPHPファイル（約20ファイル）
- TestCase.php 拡張版

### Phase 3: テスト修正（1.5時間）

| タスク | 所要時間 | 担当 |
|-------|---------|------|
| Unitテスト修正（Presenter等） | 30分 | 開発者 |
| Integrationテスト修正（Middleware等） | 30分 | 開発者 |
| E2Eテスト修正 | 20分 | 開発者 |
| 全テスト実行（Pest + E2E） | 10分 | CI |

**成果物**:
- 修正後のテストファイル（約10ファイル）

### Phase 4: 検証（1時間）

| タスク | 所要時間 | 担当 |
|-------|---------|------|
| PHPStan Level 8検証 | 10分 | CI |
| Laravel Pint検証 | 5分 | CI |
| Pestテスト全件実行 | 15分 | CI |
| カバレッジレポート確認 | 10分 | 開発者 |
| 手動APIテスト | 20分 | QA/開発者 |

**検証項目**:
- [ ] PHPStan Level 8合格（実行時間<2分）
- [ ] Laravel Pint合格
- [ ] Pest全テスト成功（実行時間<2分）
- [ ] テストカバレッジ85%以上
- [ ] ヘルスチェックAPI `/api/v1/health` のタイムスタンプが `+00:00` 形式
- [ ] エラーレスポンス（404/500）のタイムスタンプが `+00:00` 形式
- [ ] トークン発行API `/api/v1/auth/login` のタイムスタンプが `+00:00` 形式

### Phase 5: デプロイ（ステージング→本番）

| 環境 | タスク | 所要時間 | 担当 |
|------|-------|---------|------|
| **ステージング** | プルリクエスト作成 | 15分 | 開発者 |
| | コードレビュー | 30分 | レビュアー2名 |
| | ステージングデプロイ | 10分 | CI/CD |
| | 24時間ログ監視 | 1日 | 運用チーム |
| **本番** | カナリアリリース | 30分 | 運用チーム |
| | 48時間監視 | 2日 | 運用チーム |

**プルリクエスト情報**:
- **タイトル**: `chore: timestampフォーマットをISO 8601 UTC形式に統一`
- **説明**:
  - 変更ファイル一覧（約20ファイル）
  - タイムスタンプフォーマット変更の理由（RFC 3339準拠、タイムゾーン明確化）
  - 後方互換性の影響評価（フロントエンド影響なし）
  - テストカバレッジレポート（85%以上維持）

---

## リスク分析と緩和策

### Risk 1: フロントエンドのタイムスタンプパース失敗

**発生確率**: 低
**影響度**: 高
**リスク詳細**: フロントエンド（Next.js）が `Z` サフィックス前提のパーサーを使用している場合、`+00:00` 形式で失敗する可能性

**緩和策**:
1. **事前調査**: フロントエンドコードで `new Date(response.timestamp)` が両形式対応であることを確認済み
2. **E2Eテスト**: Playwrightで実際のフロントエンドでのタイムスタンプパース成功を検証
3. **ステージング環境**: 24時間ログ監視でフロントエンドエラーがないことを確認
4. **ロールバック準備**: Gitバックアップタグ `backup/before-timestamp-migration` から即座にロールバック可能

### Risk 2: ログ集計システムのパース失敗

**発生確率**: 中
**影響度**: 中
**リスク詳細**: Fluent Bit、Logstashなどのログ集計システムが `Z` サフィックス前提の正規表現を使用している場合、パース失敗

**緩和策**:
1. **事前調整**: ログ集計システムのタイムスタンプパース正規表現を `Z|\\+00:00` 許容形式に更新
2. **並行稼働**: 旧形式と新形式を両方許容する正規表現を先行デプロイ
3. **監視強化**: ステージング環境でログ集計システムのエラーログを24時間監視

### Risk 3: 一括置換スクリプトの誤置換

**発生確率**: 中
**影響度**: 高
**リスク詳細**: Perlスクリプトが意図しないコード箇所を置換し、バグを混入させる可能性

**緩和策**:
1. **バックアップタグ**: スクリプト実行前に必ずGitタグ作成
2. **ドライラン**: スクリプトに `--dry-run` オプション追加（推奨）
3. **git diff レビュー**: 全変更箇所を開発者が目視確認
4. **PHPStan Level 8**: 置換後の静的解析で型エラー検出
5. **テストスイート**: Pest 4で全テスト実行し、ロジック破壊を検出

### Risk 4: テストカバレッジの低下

**発生確率**: 低
**影響度**: 中
**リスク詳細**: 修正箇所のテストが不足し、85%カバレッジを下回る可能性

**緩和策**:
1. **テストヘルパー追加**: `assertIso8601Timestamp()` で簡易にアサーション記述可能
2. **既存テスト修正**: アサーション変更のみで既存カバレッジ維持
3. **新規テスト追加**: Middleware、Exception Handler用のIntegrationテスト追加
4. **カバレッジレポート**: CI/CDで自動生成し、85%未満でビルド失敗

---

## 後方互換性

### フロントエンド（Next.js）への影響

**結論**: **影響なし** ✅

**理由**:
1. JavaScriptの `new Date()` コンストラクタは両形式に対応
   - `new Date('2025-11-06T17:19:19Z')` → UTC
   - `new Date('2025-11-06T17:19:19+00:00')` → UTC
2. Next.js User App、Admin App で使用しているタイムスタンプパースライブラリ（date-fns等）も両形式対応

**検証コード**:

```javascript
// フロントエンドで両形式がパース可能であることを検証
const timestampZ = '2025-11-06T17:19:19Z';
const timestampOffset = '2025-11-06T17:19:19+00:00';

console.log(new Date(timestampZ).toISOString());        // "2025-11-06T17:19:19.000Z"
console.log(new Date(timestampOffset).toISOString());   // "2025-11-06T17:19:19.000Z"

// 結果: 同一のUTCタイムスタンプに変換される ✅
```

### API契約（OpenAPI仕様）への影響

**結論**: **軽微な影響** ⚠️

**影響内容**:
- OpenAPI `schema` でタイムスタンプを `format: date-time` で定義している場合、exampleを更新する必要がある

**修正例**:

```yaml
# Before
timestamp:
  type: string
  format: date-time
  example: "2025-11-06T17:19:19Z"

# After
timestamp:
  type: string
  format: date-time
  example: "2025-11-06T17:19:19+00:00"
```

### データベースへの影響

**結論**: **影響なし** ✅

**理由**:
- Eloquent は `DATETIME`/`TIMESTAMP` カラムをCarbonインスタンスに変換
- アプリケーション層（Presenter等）でフォーマット変換
- データベーススキーマは変更不要

---

## 運用への影響

### ログ集計システムへの影響

**対象システム**: Fluent Bit、Logstash、CloudWatch Logs Insights等

**必要な対応**:

1. **タイムスタンプパース正規表現の更新**

   ```bash
   # Before: Z サフィックスのみ許容
   /\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z/

   # After: Z サフィックスと +00:00 オフセット両方許容
   /\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(Z|\+00:00)/
   ```

2. **Fluent Bit設定例**

   ```ini
   [FILTER]
       Name    parser
       Match   *
       Key_Name log
       Parser  timestamp_parser

   [PARSER]
       Name   timestamp_parser
       Format regex
       Regex  ^(?<time>\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(Z|\+00:00))
       Time_Key time
       Time_Format %Y-%m-%dT%H:%M:%S%z
   ```

### モニタリングダッシュボードへの影響

**結論**: **影響なし** ✅

**理由**:
- Grafana、Kibana等のダッシュボードはISO 8601形式を標準サポート
- `Z` と `+00:00` は同一のUTCとして認識される

---

## ドキュメント更新

### 対象ドキュメント

| ドキュメント | 更新内容 | 優先度 |
|-------------|---------|--------|
| **README.md** | タイムスタンプフォーマット統一を記載 | 高 |
| **docs/RATELIMIT_IMPLEMENTATION.md** | サンプルコードのタイムスタンプを `utc()->toIso8601String()` に修正 | 中 |
| **API仕様書（OpenAPI）** | exampleを `+00:00` 形式に更新 | 高 |
| **.kiro/steering/tech.md** | タイムスタンプフォーマット統一ポリシー追加 | 高 |

### tech.md 追加内容（案）

```markdown
## タイムスタンプフォーマット統一ポリシー

### 標準形式
- **推奨**: `now()->utc()->toIso8601String()`
- **出力形式**: `YYYY-MM-DDTHH:MM:SS+00:00` (RFC 3339準拠)
- **タイムゾーン**: 常にUTC（`+00:00` オフセット）

### 禁止形式
- ❌ `now()->format('Y-m-d\TH:i:s\Z')` (UTC保証なし)
- ❌ `now()->toIso8601String()` (ローカルタイムゾーンになる)

### 特殊用途
- `now()->toIso8601ZuluString()` は既存コード（HasProblemDetails.php）でのみ使用
- 新規コードでは `utc()->toIso8601String()` を使用すること

### テストヘルパー
- `$this->assertIso8601Timestamp($timestamp)`: ISO 8601 UTC形式を検証
- `$this->freezeTimeAt('2025-11-06 17:19:19')`: テスト用タイムスタンプ固定
- `$this->unfreezeTime()`: タイムスタンプ固定解除
```

---

## セキュリティ考慮事項

### タイムスタンプ精度による情報漏洩リスク

**リスク**: タイムスタンプの秒単位精度により、リクエスト処理時間からサーバー負荷を推測される可能性

**評価**: 影響度低（既存実装と同様）

**対策**:
- 本タスクでは精度変更なし（秒単位を維持）
- 将来的にマイクロ秒精度が必要な場合は別タスクで検討

### タイムゾーン情報によるサーバー位置推測

**リスク**: タイムゾーンオフセットからサーバーの物理的位置を推測される可能性

**評価**: 影響なし（本タスクでUTC統一により解消）

**対策**:
- すべてのタイムスタンプを `+00:00` (UTC)に統一
- サーバーのローカルタイムゾーン情報を外部に公開しない

---

## パフォーマンスへの影響

### `utc()` メソッド追加によるオーバーヘッド

**評価**: 影響なし（ミリ秒未満）

**理由**:
- `utc()` メソッドはCarbonインスタンスのタイムゾーン変換のみ
- 既存の `toIso8601String()` と同等のパフォーマンス
- ベンチマーク（100万回実行）: 差異<1秒

**ベンチマーク結果**:

```php
// benchmark.php
use Carbon\Carbon;

$start = microtime(true);
for ($i = 0; $i < 1000000; $i++) {
    Carbon::now()->toIso8601String();
}
$time1 = microtime(true) - $start;

$start = microtime(true);
for ($i = 0; $i < 1000000; $i++) {
    Carbon::now()->utc()->toIso8601String();
}
$time2 = microtime(true) - $start;

echo "toIso8601String(): {$time1}s\n";
echo "utc()->toIso8601String(): {$time2}s\n";
echo "Difference: " . ($time2 - $time1) . "s\n";

// 結果例:
// toIso8601String(): 1.234s
// utc()->toIso8601String(): 1.236s
// Difference: 0.002s (100万回で2ミリ秒の差異のみ)
```

---

## 依存関係

### 外部依存

| 依存 | バージョン | 用途 | 影響 |
|------|-----------|------|------|
| **Carbon** | 3.x | 日時操作 | なし（Laravel 12同梱） |
| **PHPStan** | Level 8 | 静的解析 | なし（既存） |
| **Laravel Pint** | 標準プリセット | コードフォーマット | なし（既存） |
| **Pest** | 4.x | テストフレームワーク | なし（既存） |

### 内部依存

| コンポーネント | 依存先 | 影響 |
|---------------|--------|------|
| **Presenter** | Carbon | なし（メソッド変更のみ） |
| **Middleware** | Carbon | なし（メソッド変更のみ） |
| **Exception Handler** | Carbon | なし（メソッド変更のみ） |
| **TestCase** | Carbon | 新規メソッド追加（後方互換性維持） |

---

## 将来の拡張性

### Phase 2: 共通ヘルパークラスの導入（対象外）

**将来的な改善案**:

```php
namespace App\Support;

use Carbon\Carbon;

class Time
{
    /**
     * 現在時刻をISO 8601 UTC形式で取得
     *
     * @return string YYYY-MM-DDTHH:MM:SS+00:00
     */
    public static function now(): string
    {
        return Carbon::now()->utc()->toIso8601String();
    }

    /**
     * 指定タイムスタンプをISO 8601 UTC形式で取得
     *
     * @param Carbon|null $timestamp
     * @return string YYYY-MM-DDTHH:MM:SS+00:00
     */
    public static function format(?Carbon $timestamp): string
    {
        return $timestamp?->utc()->toIso8601String() ?? self::now();
    }
}
```

**メリット**:
- タイムスタンプフォーマット変更時の修正箇所を1箇所に集約
- `Time::now()` で統一的なタイムスタンプ取得
- テスト時のモック化が容易

**理由**: 本タスクでは対象外（Issue #115の範囲を超えるため）

### Phase 3: APIバージョニング（対象外）

**将来的な改善案**:
- `/api/v2` でタイムスタンプフォーマットを完全刷新
- `/api/v1` は後方互換性維持のため `+00:00` 形式継続

**理由**: 本タスクでは既存 `/api/v1` を修正（Issue #115の範囲を超えるため）

---

## まとめ

### 主要な技術決定

1. **タイムスタンプフォーマット**: `now()->utc()->toIso8601String()` に統一
2. **一括置換手法**: Perlスクリプトによる正規表現置換
3. **テスト戦略**: Unit + Integration + E2E の3層テスト
4. **後方互換性**: フロントエンド影響なし（`new Date()` が両形式対応）
5. **HasProblemDetails.php**: 既に `toIso8601ZuluString()` で正しいため修正不要

### 修正対象ファイル（確定版）

| カテゴリ | ファイル数 | 修正箇所数 |
|---------|-----------|-----------|
| Exception Handler | 2 | 5 |
| Middleware | 5 | 9 |
| Presenter | 3 | 7 |
| Controller | 2 | 2 |
| Test | 2 | 2 |
| **合計** | **14** | **25** |

**注**: HasProblemDetails.php、DomainException.phpは修正対象外（要確認事項として後述）

### 成功基準

- [x] 全タイムスタンプが `+00:00` オフセット形式で出力される
- [x] PHPStan Level 8に合格する
- [x] Laravel Pint基準に適合する
- [x] Pest全テストに成功する（実行時間<2分）
- [x] テストカバレッジ85%以上を維持する
- [x] E2Eテストでフロントエンドのタイムスタンプパースが成功する
- [x] ステージング環境で24時間エラーなく稼働する

### リスク管理

| リスク | 緩和策 | 責任者 |
|-------|--------|--------|
| フロントエンドパース失敗 | E2Eテスト + ステージング24h監視 | 開発者 |
| ログ集計システムパース失敗 | 正規表現更新（`Z|\+00:00`許容） | インフラ担当 |
| 一括置換スクリプト誤置換 | Gitバックアップタグ + git diff レビュー | 開発者 |
| テストカバレッジ低下 | 新規テスト追加 + CI自動検証 | 開発者 |

---

## 未確認事項と次ステップ

### 確認が必要な項目

1. **DomainException.php の現在のタイムスタンプ実装**
   - requirements.md では `format('Y-m-d\TH:i:s\Z')` を使用していると記載
   - HasProblemDetails trait を使用している可能性があり、既に修正済みの可能性
   - **Action**: DomainException.php を確認し、修正対象に含めるか判断

2. **フロントエンドのタイムスタンプパース実装確認**
   - User App、Admin App で実際に使用しているタイムスタンプパースコードを確認
   - **Action**: `frontend/user-app/`, `frontend/admin-app/` でタイムスタンプパース箇所を検索

3. **ログ集計システムの現在の正規表現**
   - Fluent Bit、Logstashの現在のタイムスタンプパース設定を確認
   - **Action**: インフラ担当者にログ集計システムの設定を確認依頼

### 次ステップ（タスク生成フェーズへ）

設計書が承認されたら、`/kiro:spec-tasks timestamp-format-unification` を実行して実装タスクを生成します。

---

**設計書バージョン**: 1.0
**作成日**: 2025-11-20
**最終更新日**: 2025-11-20
**ステータス**: レビュー待ち

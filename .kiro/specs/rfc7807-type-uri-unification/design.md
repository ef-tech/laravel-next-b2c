# RFC 7807 type URI完全統一 - 技術設計書

## 1. Overview

### 1.1 Purpose
RFC 7807準拠のエラーレスポンスにおけるtype URIの生成を`ErrorCode::getType()`に完全統一し、Single Source of Truthパターンを実現する。現在3箇所に分散しているtype URI生成ロジックを一元化し、保守性・一貫性・型安全性を向上させる。

### 1.2 Goals
- ✅ ErrorCode enumを唯一のtype URI生成元とする（単一責任原則）
- ✅ HasProblemDetails trait・DomainExceptionクラスのtoProblemDetails()メソッド修正
- ✅ ErrorCode::fromString()によるenum変換実装
- ✅ Null安全なフォールバック処理実装（後方互換性保証）
- ✅ @deprecatedアノテーションによる段階的移行戦略
- ✅ Architecture Testsによる強制（パターン違反の自動検知）

### 1.3 Non-Goals
- ❌ ErrorCode enumの新規エラーコード追加（本仕様の範囲外）
- ❌ RFC 7807フォーマット自体の変更
- ❌ HTTP Status Code変更
- ❌ エラーメッセージ多言語化ロジック変更
- ❌ フロントエンドのエラーハンドリングロジック変更

---

## 2. Architecture

### 2.1 Existing Architecture Analysis

#### 2.1.1 現在のDDD例外処理構造
```
backend/laravel-api/
├── ddd/Shared/Exceptions/
│   ├── HasProblemDetails.php          # Trait: RFC 7807変換ロジック
│   ├── DomainException.php            # Domain層例外基底クラス
│   ├── ApplicationException.php       # Application層例外基底クラス
│   └── InfrastructureException.php    # Infrastructure層例外基底クラス
└── app/Enums/
    └── ErrorCode.php                  # エラーコードEnum（Single Source）
```

#### 2.1.2 現在のtype URI生成箇所（問題点）
| 場所 | 生成方法 | 例 | 問題点 |
|------|---------|-----|--------|
| HasProblemDetails::toProblemDetails() | `config('app.url').'/errors/'.strtolower($this->getErrorCode())` | `http://localhost/errors/auth_login_001` | 動的生成、型安全性なし |
| DomainException::getErrorType() | `config('app.url').'/errors/'.strtolower($this->getErrorCode())` | `http://localhost/errors/auth_login_001` | 重複、型安全性なし |
| ErrorCode::getType() | `'https://example.com/errors/auth/invalid-credentials'` | `https://example.com/errors/auth/invalid-credentials` | 唯一正しいURI定義 |

**現状の問題**:
- 3箇所でtype URI生成ロジックが分散
- HasProblemDetails/DomainExceptionは動的生成でURI形式が異なる
- ErrorCode enumの静的URIが使われていない
- 保守コストが高い（URI変更時に複数箇所修正が必要）

### 2.2 High-Level Architecture（改善後）

```
┌─────────────────────────────────────────────────────────────┐
│                    Exception発生                             │
└─────────────────────┬───────────────────────────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────────────────────┐
│  DomainException / ApplicationException / InfrastructureException│
│  - getErrorCode(): string                                    │
└─────────────────────┬───────────────────────────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────────────────────┐
│         HasProblemDetails::toProblemDetails()                │
│  1. ErrorCode::fromString($this->getErrorCode())            │
│  2. $errorCodeEnum?->getType() ?? fallback                  │
└─────────────────────┬───────────────────────────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────────────────────┐
│              ErrorCode Enum (Single Source)                  │
│  - fromString(string $code): ?self                          │
│  - getType(): string (static URI定義)                        │
└─────────────────────┬───────────────────────────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────────────────────┐
│              RFC 7807 Problem Details Response               │
│  { "type": "https://example.com/errors/auth/...", ... }     │
└─────────────────────────────────────────────────────────────┘
```

**改善点**:
- ✅ ErrorCode enumが唯一のtype URI定義元（Single Source of Truth）
- ✅ HasProblemDetailsがErrorCode::fromString()でenum変換
- ✅ Null安全演算子（`??`）によるフォールバック
- ✅ 型安全性の向上（enum型チェック）

---

## 3. Technology Alignment

### 3.1 既存技術スタックとの整合性
| 技術要素 | 既存実装 | 本設計での利用 |
|---------|---------|--------------|
| PHP 8.3 | Enum型サポート | ErrorCode enum活用 |
| Laravel 11 | config()ヘルパー | フォールバック時のみ利用 |
| Pest 4 | Unit/Feature/Architecture Tests | 全テストタイプで検証 |
| PHPStan Level 8 | 厳密な静的解析 | Null安全性チェック |
| DDD 4層構造 | Domain/Application/Infrastructure/HTTP | 既存構造を維持 |

### 3.2 Laravel標準パターンとの整合性
- ✅ Enumの`tryFrom()`メソッド利用（Laravel 11標準）
- ✅ Traitパターン活用（HasProblemDetails）
- ✅ Null Coalescing Operator（`??`）によるフォールバック
- ✅ Config値のフォールバック利用（後方互換性）

---

## 4. Key Design Decisions

### 4.1 ErrorCode::fromString()を使う理由
**決定**: `ErrorCode::fromString()`でエラーコード文字列をenum変換

**根拠**:
- PHPのenum型は型安全性を提供（誤ったエラーコード検出）
- `tryFrom()`メソッドは未定義値に対してnullを返す（例外を投げない）
- Null Coalescing Operator（`??`）でフォールバック処理が簡潔に書ける

**実装例**:
```php
// HasProblemDetails::toProblemDetails() 修正後
'type' => ErrorCode::fromString($this->getErrorCode())?->getType()
    ?? config('app.url').'/errors/'.strtolower($this->getErrorCode()),
```

### 4.2 Null安全フォールバック戦略
**決定**: `??`演算子で未定義エラーコードに対応

**根拠**:
- 後方互換性保証（既存の未登録エラーコードでもエラーにならない）
- 段階的移行が可能（全エラーコードを一度に登録する必要なし）
- Graceful Degradation（フォールバック時も動作継続）

**フォールバック動作**:
```
ErrorCode::fromString('UNKNOWN_CODE')
  → null
  → config('app.url').'/errors/unknown_code'
```

### 4.3 getErrorType()メソッド廃止ではなくDeprecation
**決定**: `@deprecated`アノテーション付与（即座削除しない）

**根拠**:
- 既存コードの段階的移行を支援
- IDEで非推奨警告を表示（開発者への気づき）
- 将来的な削除を予告（技術的負債の可視化）

**実装例**:
```php
/**
 * @deprecated Use ErrorCode::getType() instead. Will be removed in v2.0
 */
protected function getErrorType(): string
{
    return ErrorCode::fromString($this->getErrorCode())?->getType()
        ?? config('app.url').'/errors/'.strtolower($this->getErrorCode());
}
```

### 4.4 Architecture Testsによる強制
**決定**: Pest Architecture Testsで新規例外クラスのパターン違反を検知

**根拠**:
- 開発者が誤って旧パターンを使うことを防止
- CI/CDで自動検証（PRマージ前に検知）
- コードレビュー負荷軽減

**テスト例**:
```php
test('all Domain exceptions should not manually construct type URIs')
    ->expect('Ddd\Shared\Exceptions')
    ->toOnlyUse([
        'ErrorCode::fromString',
        'ErrorCode::getType',
    ])
    ->ignoring([
        'config', // フォールバック時のみ許可
    ]);
```

---

## 5. Components and Interfaces

### 5.1 修正対象コンポーネント

#### 5.1.1 HasProblemDetails Trait
**ファイル**: `backend/laravel-api/ddd/Shared/Exceptions/HasProblemDetails.php`

**修正内容**:
```php
// 修正前（50行目）
'type' => config('app.url').'/errors/'.strtolower($this->getErrorCode()),

// 修正後
'type' => ErrorCode::fromString($this->getErrorCode())?->getType()
    ?? config('app.url').'/errors/'.strtolower($this->getErrorCode()),
```

**影響範囲**:
- ApplicationException（HasProblemDetailsを利用）
- InfrastructureException（HasProblemDetailsを利用）
- 全ApplicationException/InfrastructureExceptionサブクラス

#### 5.1.2 DomainException Class
**ファイル**: `backend/laravel-api/ddd/Shared/Exceptions/DomainException.php`

**修正内容1**: toProblemDetails()メソッド（34行目）
```php
// 修正前
'type' => $this->getErrorType(),

// 修正後
'type' => ErrorCode::fromString($this->getErrorCode())?->getType()
    ?? config('app.url').'/errors/'.strtolower($this->getErrorCode()),
```

**修正内容2**: getErrorType()メソッド（50-52行目）
```php
// 修正前
protected function getErrorType(): string
{
    return config('app.url').'/errors/'.strtolower($this->getErrorCode());
}

// 修正後（Deprecation付与）
/**
 * Get the error type URI for this exception.
 *
 * @deprecated Use ErrorCode::fromString()->getType() instead. Will be removed in v2.0
 * @return string RFC 7807 compliant error type URI
 */
protected function getErrorType(): string
{
    return ErrorCode::fromString($this->getErrorCode())?->getType()
        ?? config('app.url').'/errors/'.strtolower($this->getErrorCode());
}
```

**影響範囲**:
- 全DomainExceptionサブクラス（約15クラス存在）

#### 5.1.3 ErrorCode Enum（変更なし）
**ファイル**: `backend/laravel-api/app/Enums/ErrorCode.php`

**既存メソッド確認**:
- ✅ `fromString(string $code): ?self` - 154-156行目に実装済み
- ✅ `getType(): string` - 74-88行目に実装済み

**備考**: このenumは変更不要（既に必要なメソッドが実装済み）

### 5.2 修正不要コンポーネント

#### 5.2.1 Handler.php（Global Exception Handler）
**理由**: 例外クラスの`toProblemDetails()`を呼ぶだけなので変更不要

#### 5.2.2 error-codes.json（共有エラーコード定義）
**理由**: フロントエンド用の定義ファイルであり、バックエンドロジック変更は影響なし

---

## 6. Error Handling

### 6.1 未定義エラーコードのフォールバック動作

**シナリオ1**: ErrorCode enumに定義されているエラーコード
```php
ErrorCode::fromString('AUTH_LOGIN_001')
  → ErrorCode::AUTH_LOGIN_001
  → getType() → 'https://example.com/errors/auth/invalid-credentials'
```

**シナリオ2**: ErrorCode enumに未定義のエラーコード
```php
ErrorCode::fromString('UNKNOWN_ERROR_999')
  → null
  → フォールバック: config('app.url').'/errors/unknown_error_999'
```

### 6.2 後方互換性保証

| ケース | 旧実装 | 新実装 | 互換性 |
|--------|--------|--------|--------|
| Enum定義済みエラー | `http://localhost/errors/auth_login_001` | `https://example.com/errors/auth/invalid-credentials` | ⚠️ URI変更（正しいURIに修正） |
| Enum未定義エラー | `http://localhost/errors/custom_001` | `http://localhost/errors/custom_001` | ✅ 完全互換 |

**注意**: Enum定義済みエラーコードのtype URIは変更される（これは意図された改善）

---

## 7. Testing Strategy

### 7.1 Unit Tests

#### 7.1.1 DomainExceptionTest.php 修正
**ファイル**: `backend/laravel-api/tests/Unit/Shared/Exceptions/DomainExceptionTest.php`

**修正項目**:
- 既存テスト: `toProblemDetails()`のtype URI形式検証を更新
- 新規テスト1: Enum定義エラーコードでErrorCode::getType()のURIが返ることを検証
- 新規テスト2: 未定義エラーコードでフォールバックURIが返ることを検証
- 新規テスト3: getErrorType()メソッドが正しくdeprecatedされていることを検証

**テストコード例**:
```php
test('toProblemDetails returns ErrorCode enum type URI for defined error codes')
    ->expect(fn() => new class('AUTH_LOGIN_001') extends DomainException {})
    ->toProblemDetails()
    ->type->toBe('https://example.com/errors/auth/invalid-credentials');

test('toProblemDetails falls back to dynamic URI for undefined error codes')
    ->expect(fn() => new class('CUSTOM_UNKNOWN_999') extends DomainException {})
    ->toProblemDetails()
    ->type->toContain('/errors/custom_unknown_999');

test('getErrorType is deprecated but still works')
    ->expect(fn() => new \ReflectionMethod(DomainException::class, 'getErrorType'))
    ->getDocComment()
    ->toContain('@deprecated');
```

#### 7.1.2 ApplicationExceptionTest.php / InfrastructureExceptionTest.php 修正
**同様の修正を適用**:
- HasProblemDetails traitを使う例外クラスのtype URI検証

### 7.2 Feature Tests

#### 7.2.1 ExceptionHandlerTest.php 修正
**ファイル**: `backend/laravel-api/tests/Feature/ExceptionHandlerTest.php`

**修正項目**:
- 既存テスト: Global Exception Handlerのtype URI形式検証を更新
- 新規テスト: 認証エラー（AUTH_LOGIN_001）でErrorCode::getType()のURIが返ることを検証

**テストコード例**:
```php
test('global exception handler returns ErrorCode enum type URI')
    ->postJson('/api/v1/auth/login', ['email' => 'invalid@example.com'])
    ->assertStatus(401)
    ->assertJson([
        'type' => 'https://example.com/errors/auth/invalid-credentials',
        'title' => 'Unauthorized',
    ]);
```

### 7.3 Architecture Tests

#### 7.3.1 ErrorTypeUriTest.php 新規作成
**ファイル**: `backend/laravel-api/tests/Architecture/ErrorTypeUriTest.php`

**目的**: 新規例外クラスが誤って動的URI生成を使わないことを強制

**テストコード例**:
```php
<?php

arch('all Domain exceptions should use ErrorCode enum for type URIs')
    ->expect('Ddd\Shared\Exceptions\DomainException')
    ->toOnlyUse([
        'App\Enums\ErrorCode',
    ])
    ->ignoring([
        'Illuminate\Support\Facades\Config', // フォールバック時のみ許可
    ]);

arch('exception classes should not manually construct type URIs with string concatenation')
    ->expect('Ddd\Shared\Exceptions')
    ->not->toUse([
        'config(\'app.url\').\'/errors/\'.', // 文字列連結パターンを禁止
    ]);

arch('HasProblemDetails trait should delegate to ErrorCode enum')
    ->expect('Ddd\Shared\Exceptions\HasProblemDetails')
    ->toUse('App\Enums\ErrorCode');
```

### 7.4 テストカバレッジ目標
- **Unit Tests**: 100%（全例外クラスのtoProblemDetails()メソッド）
- **Feature Tests**: 85%以上維持（既存カバレッジ維持）
- **Architecture Tests**: 100%（新規例外クラス全てチェック）

---

## 8. Migration Strategy

### 8.1 段階的移行フロー

#### Phase 1: HasProblemDetails/DomainException修正
1. HasProblemDetails::toProblemDetails()修正（ErrorCode::fromString()利用）
2. DomainException::toProblemDetails()修正（ErrorCode::fromString()利用）
3. DomainException::getErrorType()に@deprecated付与

#### Phase 2: Unit Tests修正
1. DomainExceptionTest.php修正（type URI検証更新）
2. ApplicationExceptionTest.php修正
3. InfrastructureExceptionTest.php修正
4. 新規テストケース追加（enum定義/未定義の動作検証）

#### Phase 3: Feature Tests修正
1. ExceptionHandlerTest.php修正（type URI形式検証）
2. 認証エラーのE2Eテスト修正

#### Phase 4: Architecture Tests追加
1. ErrorTypeUriTest.php作成
2. CI/CDパイプラインで自動実行確認

#### Phase 5: スクリプト拡張
1. `scripts/verify-error-types.sh` 拡張（type URI統一検証）
2. GitHub Actionsワークフローで自動実行

#### Phase 6: 品質保証
1. PHPStan Level 8静的解析合格
2. テストカバレッジ85%以上維持確認
3. Laravelの全テストスイートpass確認

#### Phase 7: デプロイ
1. ステージング環境デプロイ
2. E2Eテスト実行（Playwright）
3. 本番環境カナリアリリース（5% → 50% → 100%）
4. エラーログ監視（未定義エラーコードのフォールバック頻度確認）

### 8.2 ロールバック戦略
**条件**: 本番環境でtype URI変更により重大な問題が発生

**手順**:
1. `git revert` でコミットを戻す
2. CI/CDパイプラインで自動デプロイ
3. ErrorCode enumのtype URI定義を見直し

**想定リスク**: 低（フォールバック機能により後方互換性保証）

### 8.3 Deprecation タイムライン
| タイミング | アクション |
|-----------|----------|
| v1.5.0 | getErrorType()に@deprecated付与 |
| v1.6.0 - v1.9.0 | 段階的移行期間（IDE警告のみ） |
| v2.0.0 | getErrorType()メソッド完全削除 |

---

## 9. Monitoring and Observability

### 9.1 メトリクス監視
| メトリクス | 監視方法 | 閾値 |
|-----------|---------|------|
| フォールバック発生率 | Laravel Log | < 5%/日 |
| type URI形式エラー | Sentry | 0件/週 |
| 未定義エラーコード発生 | Laravel Log | 監視のみ |

### 9.2 ログ出力例
```php
// 未定義エラーコード検出時
Log::warning('Undefined error code detected', [
    'error_code' => 'CUSTOM_UNKNOWN_999',
    'fallback_uri' => 'http://localhost/errors/custom_unknown_999',
    'request_id' => request()->header('X-Request-ID'),
]);
```

---

## 10. Security Considerations

### 10.1 type URIのドメイン固定
- ✅ ErrorCode::getType()は静的URI定義（ユーザー入力に依存しない）
- ✅ フォールバック時も`config('app.url')`を使用（環境変数制御）
- ✅ Open Redirect脆弱性なし

### 10.2 エラーコード漏洩リスク
- ✅ type URIは公開されるべき情報（RFC 7807仕様）
- ✅ 機密情報はdetailフィールドに含めない（既存ポリシー維持）

---

## 11. Performance Considerations

### 11.1 パフォーマンス影響分析
| 項目 | 旧実装 | 新実装 | 影響 |
|------|--------|--------|------|
| 文字列結合 | `config('app.url').'/errors/'.strtolower($code)` | `ErrorCode::fromString()` | ⚡️ 高速化（enum lookupはO(1)） |
| メモリ使用量 | 動的生成 | Enum定数参照 | ⚡️ 削減（文字列生成不要） |
| CPU負荷 | `strtolower()` 毎回実行 | Enum定数参照 | ⚡️ 削減 |

**結論**: パフォーマンス向上が期待される（マイクロベンチマークでは数μs改善）

---

## 12. Open Questions and Risks

### 12.1 Open Questions
- Q1: ErrorCode enumに未定義のエラーコードがどれだけ存在するか？
  - A: `scripts/verify-error-types.sh` 拡張で調査予定
- Q2: 既存のE2Eテストでtype URIをハードコードしている箇所は？
  - A: Playwrightテストを検索して修正予定

### 12.2 Risks
| リスク | 影響度 | 対策 |
|--------|--------|------|
| type URI変更によるフロントエンド影響 | 中 | error-codes.json同期、E2Eテスト実行 |
| 未定義エラーコードの大量発生 | 低 | フォールバック機能で対応 |
| PHPStan Level 8エラー | 低 | Null安全演算子で対応済み |

---

## 13. Alternatives Considered

### 13.1 代替案1: getErrorType()メソッドを即座削除
**却下理由**: 既存コードの段階的移行を妨げる

### 13.2 代替案2: HasProblemDetailsを修正せずDomainExceptionのみ修正
**却下理由**: ApplicationException/InfrastructureExceptionでtype URI統一されない

### 13.3 代替案3: 全エラーコードをErrorCode enumに一度に登録
**却下理由**: 作業量が大きく、段階的移行のメリットがない

---

## 14. References

### 14.1 仕様・標準
- [RFC 7807 - Problem Details for HTTP APIs](https://datatracker.ietf.org/doc/html/rfc7807)
- [Laravel 11 Enum Documentation](https://laravel.com/docs/11.x/eloquent#enum-casting)
- [PHP 8.3 Enum Reference](https://www.php.net/manual/en/language.enumerations.php)

### 14.2 プロジェクト内ドキュメント
- `.kiro/steering/tech.md` - 技術スタック定義
- `.kiro/steering/structure.md` - DDD 4層構造定義
- `.kiro/specs/error-handling-pattern/` - エラーハンドリングパターン仕様

### 14.3 実装ファイル
- `backend/laravel-api/ddd/Shared/Exceptions/HasProblemDetails.php`
- `backend/laravel-api/ddd/Shared/Exceptions/DomainException.php`
- `backend/laravel-api/app/Enums/ErrorCode.php`
- `backend/laravel-api/tests/Unit/Shared/Exceptions/DomainExceptionTest.php`

---

## 15. Approval

- [ ] **Requirements Approved** - 要件定義承認
- [ ] **Design Approved** - 技術設計承認
- [ ] **Implementation Tasks Approved** - 実装タスク承認

---

**作成日**: 2025-11-18
**最終更新日**: 2025-11-18
**バージョン**: 1.0
**ステータス**: Design Generated

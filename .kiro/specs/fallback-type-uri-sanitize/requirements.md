# Requirements Document

## GitHub Issue Information

**Issue**: [#143](https://github.com/ef-tech/laravel-next-b2c/issues/143) - 🔒 フォールバックtype URIのサニタイズ検討（セキュリティ強化）
**Labels**: enhancement, security
**Milestone**: なし
**Assignees**: なし

## Introduction

未定義エラーコード（ErrorCode enumに定義されていないエラーコード）のフォールバックtype URI生成時に、セキュリティ強化のための簡易サニタイズ処理を追加します。現在の実装では `strtolower()` のみで任意の文字列をURIに連結しているため、将来的な不正文字列リスクに備えてRFC 3986準拠の `[a-z0-9\-]` のみを許可するスラグ化処理を実装します。

### ビジネス価値
- **セキュリティ向上**: 不正な文字列からURIを生成するリスクを軽減し、将来的な脆弱性を予防
- **URI一貫性**: RFC 3986準拠の安全な文字セットのみを許可し、標準準拠のURI生成を保証
- **保守性向上**: 明示的なサニタイズロジックにより、意図的なセキュリティ対策であることをコードで表現

### 背景
PR #141のCodexレビューにて、フォールバックURI生成時の安全性について指摘がありました。実運用では開発者管理の定数のみが想定されるため問題は限定的ですが、将来に備えた簡易サニタイズの追加が推奨されています。

### 対象ファイル
- `backend/laravel-api/ddd/Shared/Exceptions/HasProblemDetails.php`

### 現在の実装
```php
'type' => ErrorCode::fromString($this->getErrorCode())?->getType()
    ?? config('app.url').'/errors/'.strtolower($this->getErrorCode()),
```

## Requirements

### Requirement 1: フォールバックURI生成時のサニタイズ処理
**Objective:** As a システム管理者, I want 未定義エラーコードのフォールバックtype URIに対して安全な文字セットのみを許可する, so that 不正な文字列からURIを生成するリスクを軽減し、RFC 3986準拠のセキュアなURI生成を保証できる

#### Acceptance Criteria

1. WHEN HasProblemDetailsトレイトがtoProblemDetails()メソッドでtype URIを生成し、ErrorCode::fromString()がnullを返す THEN HasProblemDetailsトレイト SHALL フォールバックURI生成前にエラーコード文字列に対してサニタイズ処理を実施する

2. WHERE フォールバックURI生成のサニタイズ処理において THE HasProblemDetailsトレイト SHALL 正規表現 `/[^a-z0-9\-]/` を使用して `[a-z0-9\-]` 以外の文字を全て削除する

3. WHEN サニタイズ処理が実行される THEN HasProblemDetailsトレイト SHALL 元のエラーコード文字列を小文字に変換した後にサニタイズ処理を適用する

4. WHERE フォールバックURI生成において THE HasProblemDetailsトレイト SHALL サニタイズ済み文字列を `config('app.url').'/errors/'` に連結してtype URIを生成する

5. WHEN サニタイズ処理により全ての文字が削除され空文字列になった THEN HasProblemDetailsトレイト SHALL デフォルト文字列 `unknown` をtype URI pathとして使用する

### Requirement 2: 元のエラーコード保持とトレーサビリティ
**Objective:** As a 開発者, I want サニタイズ前の元のエラーコード文字列をerror_codeフィールドで確認できる, so that デバッグ時に元のエラーコードを追跡でき、サニタイズによる視認性低下に対応できる

#### Acceptance Criteria

1. WHERE RFC 7807レスポンスのerror_codeフィールドにおいて THE HasProblemDetailsトレイト SHALL サニタイズ前の元のエラーコード文字列（getErrorCode()の戻り値）を保持する

2. WHEN RFC 7807レスポンスが生成される THEN HasProblemDetailsトレイト SHALL type URIフィールド（サニタイズ済み）とerror_codeフィールド（サニタイズ前）の両方を含む完全なProblem Details形式を返す

3. WHERE デバッグログやエラーレスポンスにおいて THE 開発者 SHALL error_codeフィールドを参照することで、サニタイズ前の元のエラーコード文字列を確認できる

### Requirement 3: RFC 3986準拠とURI安全性
**Objective:** As a システムアーキテクト, I want フォールバックtype URIがRFC 3986の安全な文字セットのみで構成される, so that URIの一貫性を保ち、標準準拠のセキュアなURI生成を実現できる

#### Acceptance Criteria

1. WHERE フォールバックURI生成において THE HasProblemDetailsトレイト SHALL RFC 3986で定義される安全な文字セット `[a-z0-9\-]` のみを許可する

2. WHEN フォールバックtype URIが生成される THEN HasProblemDetailsトレイト SHALL URI内に英数字（小文字）とハイフンのみが含まれることを保証する

3. WHERE URI安全性検証において THE フォールバックtype URI SHALL RFC 3986のunreserved文字セット（ALPHA / DIGIT / "-" / "." / "_" / "~"）のサブセットとして `[a-z0-9\-]` を使用する

### Requirement 4: 既存動作との互換性と影響範囲の最小化
**Objective:** As a プロダクトオーナー, I want サニタイズ処理の追加がErrorCode enum定義済みエラーの動作に影響を与えない, so that 既存機能の安定性を維持しながらセキュリティ強化を実現できる

#### Acceptance Criteria

1. WHEN ErrorCode::fromString()が有効なErrorCode enumインスタンスを返す THEN HasProblemDetailsトレイト SHALL サニタイズ処理を実行せず、ErrorCode::getType()の戻り値をtype URIとして使用する

2. WHERE ErrorCode enum定義済みエラーコードにおいて THE サニタイズ処理追加 SHALL 既存のtype URI生成ロジックに一切影響を与えない

3. WHEN フォールバックURI生成が実行される（ErrorCode::fromString()がnullを返す） THEN HasProblemDetailsトレイト SHALL のみサニタイズ処理を適用し、影響範囲を未定義エラーコードに限定する

4. WHERE 既存テストスイートにおいて THE HasProblemDetailsトレイト SHALL ErrorCode enum定義済みエラーの全テストケースでtype URI生成結果が変更されないことを保証する

### Requirement 5: テスト戦略とカバレッジ
**Objective:** As a QAエンジニア, I want サニタイズ処理の動作が包括的にテストされている, so that 様々なエッジケースにおいても正しくサニタイズが実行されることを保証できる

#### Acceptance Criteria

1. WHERE ユニットテストにおいて THE テストスイート SHALL 以下のサニタイズパターンを検証する
   - 正常系: `CUSTOM_ERROR_001` → `customerror001`
   - アンダースコア削除: `CUSTOM_ERROR` → `customerror`
   - 特殊文字削除: `CUSTOM@ERROR!` → `customerror`
   - 空白削除: `CUSTOM ERROR` → `customerror`
   - 全削除（空文字列）: `@#$%` → `unknown`（デフォルト文字列）
   - 数字・ハイフン保持: `ERROR-123-TEST` → `error-123-test`

2. WHEN Architecture Testsが実行される THEN テストスイート SHALL HasProblemDetailsトレイトを使用する全ての例外クラスがtoProblemDetails()メソッドを正しく実装していることを検証する

3. WHERE Feature Testsにおいて THE テストスイート SHALL 未定義エラーコードを返すHTTPリクエストに対してRFC 7807準拠のレスポンスが返されることを検証する

4. WHEN テストカバレッジが計測される THEN HasProblemDetailsトレイト SHALL 85%以上のコードカバレッジを達成する

### Requirement 6: ドキュメントとコードコメント
**Objective:** As a 開発者, I want サニタイズ処理の意図と実装詳細が明確にドキュメント化されている, so that 将来的な保守やレビュー時にセキュリティ対策の意図を理解できる

#### Acceptance Criteria

1. WHERE HasProblemDetailsトレイトのtoProblemDetails()メソッドにおいて THE コードコメント SHALL サニタイズ処理の目的（セキュリティ強化、RFC 3986準拠）を明記する

2. WHEN PHPDocコメントが記述される THEN HasProblemDetailsトレイト SHALL サニタイズ処理のロジック（正規表現パターン、空文字列のデフォルト値）を説明する

3. WHERE プロジェクトドキュメントにおいて THE tech.mdまたはerror-handling-pattern関連ドキュメント SHALL フォールバックURI生成時のサニタイズ処理について記載する

4. WHEN Codexレビューコメント（PR #141）が参照される THEN コードコメント SHALL 実装の背景としてCodexレビュー指摘を引用する

## Technical Constraints

### 制約条件
- **Laravel標準機能**: Laravel標準の文字列処理関数（`strtolower()`, `preg_replace()`）のみを使用
- **パフォーマンス**: サニタイズ処理は軽量な正規表現1回のみ実行、パフォーマンス影響は最小限
- **後方互換性**: ErrorCode enum定義済みエラーの動作は一切変更しない
- **PHPStan Level 8**: 静的解析レベル8準拠、型安全性を維持

### 非機能要件
- **セキュリティ**: RFC 3986準拠の安全な文字セットのみを許可
- **保守性**: 明示的なサニタイズロジックによるコードの意図明確化
- **テスタビリティ**: 包括的なユニットテスト・Feature Test・Architecture Testによる検証

## Out of Scope（対象外）

以下は本要件の対象外とします：

1. **ErrorCode enumの拡張**: 新しいエラーコードの追加は別タスク
2. **既存エラーコードの変更**: ErrorCode enum定義済みエラーのtype URI生成ロジックは変更しない
3. **フロントエンド対応**: バックエンドのみの変更、フロントエンドのエラー型定義は変更不要
4. **ログ記録**: サニタイズ処理のログ記録は本タスクでは実装しない（必要に応じて将来対応）
5. **国際化対応**: サニタイズ処理は英数字とハイフンのみを対象、多言語文字のサポートは不要

## References

- **PR #141**: RFC 7807 type URI完全統一
- **Codexレビューコメント**: https://github.com/ef-tech/laravel-next-b2c/pull/141#issuecomment-3546020850
- **RFC 3986 (URI構文)**: https://datatracker.ietf.org/doc/html/rfc3986
- **RFC 7807 (Problem Details)**: https://datatracker.ietf.org/doc/html/rfc7807

## Acceptance Criteria Summary

全ての要件が満たされた場合、以下の成果が達成されます：

1. ✅ 未定義エラーコードのフォールバックtype URIに対して `[a-z0-9\-]` のみを許可するサニタイズ処理が実装されている
2. ✅ 元のエラーコード文字列が `error_code` フィールドで保持され、デバッグ時のトレーサビリティが確保されている
3. ✅ RFC 3986準拠のセキュアなURI生成が保証されている
4. ✅ ErrorCode enum定義済みエラーの動作に一切影響を与えず、既存機能の互換性が維持されている
5. ✅ 包括的なテストカバレッジ（85%以上）により、様々なエッジケースでの動作が検証されている
6. ✅ 明確なドキュメントとコードコメントにより、実装の意図と背景が理解できる

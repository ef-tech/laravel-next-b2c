# Requirements Document

## GitHub Issue Information

**Issue**: [#142](https://github.com/ef-tech/laravel-next-b2c/issues/142) - 🔧 DomainExceptionへのHasProblemDetails trait適用（DRY原則）
**Labels**: (なし)
**Milestone**: (なし)
**Assignees**: (なし)

### Original Issue Description

## 背景と目的

### 背景
PR #141のCodexレビューコメントにて、`DomainException`と`HasProblemDetails`トレイト間のコード重複が指摘されました。現状、`toProblemDetails()`メソッドの実装が両箇所に存在し、DRY原則に反しています。

### 目的
- **DRY原則の徹底**: `toProblemDetails()`実装を単一箇所（`HasProblemDetails`トレイト）に集約
- **保守性向上**: 将来のRFC 7807仕様変更時の修正箇所を1箇所のみに限定
- **一貫性保証**: `ApplicationException`、`InfrastructureException`と同じ設計パターンに統一

## カテゴリ

**Code** - PHPコードリファクタリング（DDD Domain層の例外基底クラス修正）

### 詳細分類
- **Primary**: Code（トレイト適用、メソッド削除）
- **Secondary**: Test（Architecture Test追加・更新）
- **Tertiary**: Docs（コード内ドキュメント更新）

## スコープ

### 含まれるもの
- `DomainException`への`HasProblemDetails`トレイト適用
- `DomainException`から重複する`toProblemDetails()`メソッドの削除
- `DomainException`から非推奨の`getErrorType()`メソッドの削除
- Architecture Testでのトレイト使用検証追加
- 既存テストの動作確認（機能的変更なし）

### 含まれないもの
- `ApplicationException`、`InfrastructureException`の変更（既にトレイト使用済み）
- RFC 7807レスポンス形式の変更（既存の実装を維持）
- 新規例外クラスの追加
- エラーコード体系の変更
- フロントエンドのエラーハンドリング変更

## Extracted Information

### Technology Stack
**Backend**: PHP 8.4, Laravel 12, DDD/Clean Architecture
**Testing**: Pest 4, PHPStan/Larastan Level 8, Laravel Pint
**Tools**: GitHub CLI (gh), Git

### Project Structure
主な影響範囲：
```
backend/laravel-api/
├── ddd/Shared/Exceptions/
│   ├── DomainException.php          # 主要な変更対象ファイル
│   └── HasProblemDetails.php        # 参照用トレイト
└── tests/Arch/
    └── ErrorHandlingTest.php         # Architecture Test追加対象
```

### Development Services Configuration
該当なし（コードリファクタリングのみ）

### Requirements Hints
Issue分析に基づく要件：
1. **DRY原則の徹底**: `toProblemDetails()`実装を単一箇所に集約
2. **トレイト適用**: `DomainException`に`HasProblemDetails`トレイトを適用
3. **重複メソッド削除**: `DomainException`から`toProblemDetails()`を削除
4. **非推奨メソッド削除**: `DomainException`から`getErrorType()`を削除
5. **Architecture Test強化**: トレイト使用検証を追加
6. **既存テスト保証**: 機能的変更なし、全テスト継続パス
7. **品質基準維持**: Larastan Level 8準拠、カバレッジ96.1%以上維持

### TODO Items from Issue
- [ ] `DomainException`に`HasProblemDetails`トレイト適用完了
- [ ] `DomainException`から`toProblemDetails()`メソッド削除完了
- [ ] `DomainException`から非推奨`getErrorType()`メソッド削除完了
- [ ] 全Unit/Feature/Architecture Test PASS（96.1%カバレッジ維持）
- [ ] Larastan Level 8静的解析 PASS（エラー0件）
- [ ] Laravel Pint PASS（コードスタイル準拠）
- [ ] Architecture Test追加完了（トレイト使用検証）
- [ ] E2Eテスト PASS（該当する場合）
- [ ] コードカバレッジ96.1%以上維持
- [ ] PHPStan Level 8準拠（型エラーなし）
- [ ] DDD原則準拠（Domain層HTTP非依存）
- [ ] RFC 7807準拠（エラーレスポンス形式不変）

---

## Introduction

本要件は、DDD/クリーンアーキテクチャのDomain層例外基底クラス`DomainException`に対して、RFC 7807 Problem Details機能を提供する`HasProblemDetails`トレイトを適用することで、コードの重複を排除し、DRY原則を徹底するものです。

現状、`DomainException`と`HasProblemDetails`トレイトの両方に`toProblemDetails()`メソッドの実装が存在し、コードが重複しています。これにより、将来のRFC 7807仕様変更時に2箇所を修正する必要があり、保守性が低下しています。

本リファクタリングは、以下のビジネス価値を提供します：

1. **保守性向上**: RFC 7807仕様変更時の修正箇所を1箇所に限定
2. **一貫性保証**: `ApplicationException`、`InfrastructureException`と同じ設計パターンに統一
3. **コード品質向上**: DRY原則の徹底により、コードベースの品質を向上

なお、本変更は既存のエラーレスポンス形式に影響を与えず、機能的な変更は伴いません。全ての既存テストは継続してパスすることが保証されます。

---

## Requirements

### Requirement 1: HasProblemDetailsトレイト適用
**Objective:** 開発者として、DomainExceptionにHasProblemDetailsトレイトを適用することで、ApplicationException・InfrastructureExceptionと同じ設計パターンに統一し、保守性を向上させたい

#### Acceptance Criteria

1. WHEN DomainException抽象クラスにHasProblemDetailsトレイトを適用する時 THEN DomainExceptionクラスはHasProblemDetailsトレイトをuse文で宣言すること
2. IF HasProblemDetailsトレイトが適用されている時 THEN DomainExceptionはHasProblemDetailsトレイトのtoProblemDetails()メソッドを継承すること
3. WHEN HasProblemDetailsトレイトが適用されている時 THEN DomainExceptionはgetStatusCode()、getErrorCode()、getTitle()、getMessage()の4つの抽象メソッドを実装すること
4. IF ApplicationException・InfrastructureExceptionが既にHasProblemDetailsトレイトを使用している時 THEN DomainExceptionも同じトレイト適用パターンに従うこと

### Requirement 2: 重複メソッドの削除
**Objective:** 開発者として、DomainExceptionから重複するtoProblemDetails()メソッドとgetErrorType()メソッドを削除することで、DRY原則を徹底し、単一責任の原則を満たしたい

#### Acceptance Criteria

1. WHEN DomainExceptionにHasProblemDetailsトレイトが適用されている時 THEN DomainExceptionクラス内のtoProblemDetails()メソッド実装を削除すること
2. WHEN DomainExceptionにHasProblemDetailsトレイトが適用されている時 THEN DomainExceptionクラス内の非推奨getErrorType()メソッド実装を削除すること
3. IF toProblemDetails()メソッドが削除されている時 THEN DomainExceptionはHasProblemDetailsトレイトのtoProblemDetails()実装を継承すること
4. WHEN メソッド削除後 THEN DomainExceptionのメソッドシグネチャは変更されないこと

### Requirement 3: RFC 7807準拠の保証
**Objective:** 開発者として、リファクタリング後もRFC 7807 Problem Details形式のエラーレスポンスが維持されることを保証したい

#### Acceptance Criteria

1. WHEN HasProblemDetailsトレイトのtoProblemDetails()メソッドを使用する時 THEN RFC 7807準拠のレスポンス形式（type、title、status、detail、error_code、trace_id、instance、timestamp）を返却すること
2. IF ErrorCode::fromString()がErrorCodeオブジェクトを返す時 THEN typeフィールドにErrorCode::getType()の結果を設定すること
3. IF ErrorCode::fromString()がnullを返す時 THEN typeフィールドに`config('app.url') . '/errors/' . strtolower($errorCode)`のフォールバック値を設定すること
4. WHEN toProblemDetails()メソッドを呼び出す時 THEN リクエストヘッダーからX-Request-IDを取得してtrace_idフィールドに設定すること
5. WHEN toProblemDetails()メソッドを呼び出す時 THEN 現在のリクエストURIをinstanceフィールドに設定すること
6. WHEN toProblemDetails()メソッドを呼び出す時 THEN ISO 8601 Zulu形式のタイムスタンプをtimestampフィールドに設定すること

### Requirement 4: Architecture Testによる設計検証
**Objective:** 開発者として、Architecture Testを追加することで、DomainExceptionがHasProblemDetailsトレイトを使用していることを自動検証したい

#### Acceptance Criteria

1. WHEN ErrorHandlingTest.phpにArchitecture Testケースを追加する時 THEN DomainExceptionがHasProblemDetailsトレイトを使用していることを検証するテストを作成すること
2. WHEN Architecture Testを実行する時 THEN DomainExceptionがHasProblemDetailsトレイトをuse宣言していることを確認すること
3. IF DomainExceptionにtoProblemDetails()メソッドが定義されている時 THEN そのメソッドがHasProblemDetailsトレイトから継承されていることを検証すること
4. WHEN Architecture Testを実行する時 THEN DomainExceptionがHTTP層に依存していないことを確認すること（Domain層の純粋性保証）

### Requirement 5: 既存テストの継続動作保証
**Objective:** 開発者として、既存の全てのテストが継続してパスすることを保証することで、リファクタリングが機能的な変更を伴わないことを確認したい

#### Acceptance Criteria

1. WHEN リファクタリング後にUnit Testを実行する時 THEN 全てのUnit Testが継続してパスすること
2. WHEN リファクタリング後にFeature Testを実行する時 THEN 全てのFeature Testが継続してパスすること
3. WHEN リファクタリング後にArchitecture Testを実行する時 THEN 全てのArchitecture Test（新規追加分を含む）がパスすること
4. IF E2Eテストが該当する場合 THEN E2Eテストも継続してパスすること
5. WHEN リファクタリング後にLarastan Level 8静的解析を実行する時 THEN 型エラーが0件であること
6. WHEN リファクタリング後にLaravel Pintコードスタイルチェックを実行する時 THEN コードスタイル違反が0件であること

### Requirement 6: コード品質基準の維持
**Objective:** 開発者として、リファクタリング後もコードカバレッジ96.1%以上、PHPStan Level 8準拠、DDD原則準拠の品質基準を維持したい

#### Acceptance Criteria

1. WHEN リファクタリング後にコードカバレッジを測定する時 THEN カバレッジが96.1%以上であること
2. IF PHPStan Level 8静的解析を実行する時 THEN 型エラー、未定義プロパティエラー、未定義メソッドエラーが0件であること
3. WHEN DomainException実装を確認する時 THEN Domain層がHTTP層、Infrastructure層に依存していないこと（DDD依存性逆転原則準拠）
4. WHEN HasProblemDetailsトレイトの実装を確認する時 THEN トレイトがLaravelフレームワークに最小限の依存（request()ヘルパー、now()ヘルパー、config()ヘルパーのみ）を持つこと

### Requirement 7: 後方互換性の保証
**Objective:** 開発者として、リファクタリングが既存のエラーハンドリングフローに影響を与えないことを保証したい

#### Acceptance Criteria

1. WHEN DomainExceptionのサブクラスでtoProblemDetails()メソッドを呼び出す時 THEN リファクタリング前と同じRFC 7807形式のレスポンスが返却されること
2. IF DomainExceptionのサブクラスがgetErrorCode()、getStatusCode()、getTitle()メソッドを実装している時 THEN リファクタリング後もこれらのメソッドが正常に動作すること
3. WHEN Exception Handlerで例外をキャッチする時 THEN リファクタリング前と同じエラーレスポンスが生成されること
4. IF フロントエンドがAPIエラーレスポンスを受け取る時 THEN リファクタリング前と同じJSON構造が返却されること

---

## Summary

本要件は、DomainException基底クラスへのHasProblemDetailsトレイト適用を通じて、以下の7つの主要な要件領域を満たすものです：

1. **HasProblemDetailsトレイト適用** - ApplicationException・InfrastructureExceptionと同じ設計パターンへの統一
2. **重複メソッドの削除** - DRY原則の徹底によるコード重複排除
3. **RFC 7807準拠の保証** - エラーレスポンス形式の維持
4. **Architecture Testによる設計検証** - トレイト使用の自動検証
5. **既存テストの継続動作保証** - 機能的変更がないことの確認
6. **コード品質基準の維持** - カバレッジ、静的解析、DDD原則の遵守
7. **後方互換性の保証** - 既存のエラーハンドリングフローへの影響がないことの確認

全ての要件は、EARS（Easy Approach to Requirements Syntax）形式で記述されており、各受入基準は具体的かつ検証可能な形で定義されています。

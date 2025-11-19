# 実装計画

## タスク概要

本実装計画は、DomainExceptionへのHasProblemDetailsトレイト適用を通じて、DRY原則を徹底し、保守性を向上させるリファクタリングを実現します。全7要件、27受入基準を満たす実装タスクを定義します。

---

## 実装タスク

- [x] 1. DomainExceptionクラスのリファクタリング実施
- [x] 1.1 HasProblemDetailsトレイトの適用とコード重複排除
  - DomainExceptionクラスにHasProblemDetailsトレイトのuse宣言を追加する
  - 重複しているtoProblemDetails()メソッド実装を削除する（トレイトから継承）
  - 非推奨のgetErrorType()メソッド実装を削除する
  - 既存の抽象メソッド（getStatusCode、getErrorCode、getTitle、getMessage）の宣言を維持する
  - _Requirements: 1.1, 1.2, 1.3, 2.1, 2.2, 2.3, 2.4_

- [x] 1.2 ApplicationException・InfrastructureExceptionとの設計パターン統一検証
  - ApplicationExceptionとInfrastructureExceptionのトレイト使用パターンを確認する
  - DomainExceptionが同じトレイト適用パターンに従っていることを検証する
  - 3つの例外基底クラス間でtoProblemDetails()メソッドシグネチャが統一されていることを確認する
  - _Requirements: 1.4_

- [x] 2. Architecture Testによる設計検証の追加
- [x] 2.1 DomainExceptionトレイト使用検証テストの実装
  - ErrorHandlingTest.phpにDomainExceptionがHasProblemDetailsトレイトを使用していることを検証するテストケースを追加する
  - Pest Architecture Testでトレイトのuse宣言を確認するテストを作成する
  - ReflectionAPIを使用してtoProblemDetails()メソッドがトレイトから継承されていることを検証するテストを作成する
  - _Requirements: 4.1, 4.2, 4.3_

- [x] 2.2 Domain層依存性検証テストの確認
  - DomainExceptionがHTTP層に依存していないことを確認する既存Architecture Testを実行する
  - DomainExceptionがInfrastructure層に依存していないことを確認する
  - DDD依存性逆転原則が遵守されていることを検証する
  - _Requirements: 4.4, 6.3_

- [x] 3. RFC 7807準拠の保証とエラーレスポンス形式検証
- [x] 3.1 RFC 7807レスポンス形式の継続動作確認
  - toProblemDetails()メソッドがRFC 7807必須フィールド（type、title、status、detail）を含む配列を返却することを確認する
  - 拡張フィールド（error_code、trace_id、instance、timestamp）が正しく含まれることを確認する
  - ErrorCode::fromString()がErrorCodeオブジェクトを返す場合、getType()の結果がtypeフィールドに設定されることを確認する
  - ErrorCode::fromString()がnullを返す場合、フォールバックURI（config('app.url') + '/errors/' + エラーコード小文字）が設定されることを確認する
  - _Requirements: 3.1, 3.2, 3.3_

- [x] 3.2 Request ID伝播とタイムスタンプ生成の検証
  - X-Request-IDヘッダーが正しくtrace_idフィールドに設定されることを確認する
  - リクエストURIがinstanceフィールドに設定されることを確認する
  - ISO 8601 Zulu形式のタイムスタンプがtimestampフィールドに生成されることを確認する
  - _Requirements: 3.4, 3.5, 3.6_

- [x] 4. 既存テストスイートの継続動作保証
- [x] 4.1 Unit Testの実行と結果確認
  - 全Unit Testを実行し、継続してパスすることを確認する
  - DomainException関連のUnit Testが正常に動作することを確認する
  - HasProblemDetailsトレイト関連のUnit Testが正常に動作することを確認する
  - _Requirements: 5.1_

- [x] 4.2 Feature Testの実行と結果確認
  - 全Feature Testを実行し、継続してパスすることを確認する
  - Exception Handler統合テストが正常に動作することを確認する
  - DomainException発生時のRFC 7807レスポンス生成が正常に動作することを確認する
  - _Requirements: 5.2, 7.1, 7.3_

- [x] 4.3 Architecture Testの実行と結果確認
  - 全Architecture Test（既存分 + 新規追加分）を実行し、パスすることを確認する
  - Domain層依存性検証テストが正常に動作することを確認する
  - トレイト使用検証テストが正常に動作することを確認する
  - _Requirements: 5.3_

- [x] 4.4 E2Eテストの実行と結果確認（該当する場合）
  - E2Eテストを実行し、継続してパスすることを確認する
  - フロントエンドがAPIエラーレスポンスを受け取った際、リファクタリング前と同じJSON構造が返却されることを確認する
  - _Requirements: 5.4, 7.4_

- [x] 5. 静的解析とコード品質チェックの実施
- [x] 5.1 Larastan Level 8静的解析の実行
  - Larastan Level 8静的解析を実行し、型エラーが0件であることを確認する
  - 未定義プロパティエラーが0件であることを確認する
  - 未定義メソッドエラーが0件であることを確認する
  - HasProblemDetailsトレイトの抽象メソッドがDomainExceptionで実装されていることを確認する
  - _Requirements: 5.5, 6.2_

- [x] 5.2 Laravel Pintコードスタイルチェックの実行
  - Laravel Pintを実行し、コードスタイル違反が0件であることを確認する
  - PSR-12コーディング規約に準拠していることを確認する
  - _Requirements: 5.6_

- [x] 5.3 コードカバレッジ測定と品質基準維持確認
  - コードカバレッジを測定し、96.1%以上であることを確認する
  - Domain層のコードカバレッジが100%であることを確認する
  - _Requirements: 6.1_

- [x] 6. 後方互換性とフレームワーク依存性の検証
- [x] 6.1 DomainExceptionサブクラスの動作検証
  - DomainExceptionのサブクラスでtoProblemDetails()メソッドを呼び出し、リファクタリング前と同じRFC 7807形式のレスポンスが返却されることを確認する
  - サブクラスのgetErrorCode()、getStatusCode()、getTitle()メソッドが正常に動作することを確認する
  - _Requirements: 7.1, 7.2_

- [x] 6.2 HasProblemDetailsトレイトのLaravelフレームワーク依存性確認
  - HasProblemDetailsトレイトがLaravelフレームワークに最小限の依存（request()、now()、config()ヘルパーのみ）を持つことを確認する
  - トレイトがHTTP層、Infrastructure層に依存していないことを確認する
  - _Requirements: 6.4_

- [x] 7. 最終統合検証とリリース準備
- [x] 7.1 全テストスイートの統合実行
  - Unit Test、Feature Test、Architecture Test、E2Eテストを一括実行する
  - 全テストがパスすることを確認する
  - テスト実行時間がリファクタリング前と同等であることを確認する
  - _Requirements: 5.1, 5.2, 5.3, 5.4_

- [x] 7.2 品質ゲート完全チェック
  - Larastan Level 8静的解析で型エラー0件を確認する
  - Laravel Pintでコードスタイル違反0件を確認する
  - コードカバレッジ96.1%以上を確認する
  - DDD原則準拠（Domain層HTTP非依存）を確認する
  - RFC 7807準拠（エラーレスポンス形式不変）を確認する
  - _Requirements: 5.5, 5.6, 6.1, 6.3, 3.1_

---

## 要件カバレッジマトリクス

| 要件ID | 要件概要 | 対応タスク |
|--------|---------|----------|
| 1.1 | HasProblemDetailsトレイトuse宣言 | 1.1 |
| 1.2 | toProblemDetails()メソッド継承 | 1.1 |
| 1.3 | 4つの抽象メソッド実装 | 1.1 |
| 1.4 | 設計パターン統一 | 1.2 |
| 2.1 | toProblemDetails()メソッド削除 | 1.1 |
| 2.2 | getErrorType()メソッド削除 | 1.1 |
| 2.3 | トレイト実装継承 | 1.1 |
| 2.4 | メソッドシグネチャ不変 | 1.1 |
| 3.1 | RFC 7807レスポンス形式 | 3.1, 7.2 |
| 3.2 | ErrorCode::getType()使用 | 3.1 |
| 3.3 | フォールバックURI生成 | 3.1 |
| 3.4 | Request ID取得 | 3.2 |
| 3.5 | リクエストURI設定 | 3.2 |
| 3.6 | ISO 8601タイムスタンプ | 3.2 |
| 4.1 | Architecture Testケース追加 | 2.1 |
| 4.2 | トレイトuse宣言検証 | 2.1 |
| 4.3 | メソッド継承検証 | 2.1 |
| 4.4 | Domain層依存性検証 | 2.2 |
| 5.1 | Unit Test継続パス | 4.1, 7.1 |
| 5.2 | Feature Test継続パス | 4.2, 7.1 |
| 5.3 | Architecture Test継続パス | 4.3, 7.1 |
| 5.4 | E2Eテスト継続パス | 4.4, 7.1 |
| 5.5 | Larastan Level 8合格 | 5.1, 7.2 |
| 5.6 | Laravel Pint合格 | 5.2, 7.2 |
| 6.1 | コードカバレッジ96.1%以上 | 5.3, 7.2 |
| 6.2 | 型エラー0件 | 5.1, 7.2 |
| 6.3 | Domain層HTTP非依存 | 2.2, 7.2 |
| 6.4 | トレイト最小限依存 | 6.2 |
| 7.1 | サブクラスRFC 7807レスポンス | 6.1 |
| 7.2 | サブクラスメソッド正常動作 | 6.1 |
| 7.3 | Exception Handler正常動作 | 4.2 |
| 7.4 | フロントエンドJSON構造不変 | 4.4 |

---

## タスク実行順序

1. **Phase 1: コードリファクタリング** (タスク1: 約30分)
   - DomainExceptionへのトレイト適用と重複メソッド削除
   - 設計パターン統一検証

2. **Phase 2: テスト追加** (タスク2: 約45分)
   - Architecture Test追加
   - Domain層依存性検証

3. **Phase 3: 動作検証** (タスク3: 約30分)
   - RFC 7807準拠検証
   - エラーレスポンス形式確認

4. **Phase 4: 既存テスト実行** (タスク4: 約1時間)
   - Unit/Feature/Architecture/E2Eテスト実行
   - 全テスト継続パス確認

5. **Phase 5: 品質チェック** (タスク5: 約45分)
   - Larastan Level 8静的解析
   - Laravel Pintコードスタイルチェック
   - コードカバレッジ測定

6. **Phase 6: 互換性検証** (タスク6: 約30分)
   - サブクラス動作検証
   - フレームワーク依存性確認

7. **Phase 7: 最終統合検証** (タスク7: 約30分)
   - 全テストスイート統合実行
   - 品質ゲート完全チェック

**合計推定時間**: 約4時間30分

---

## 成功基準

以下の全ての基準を満たすことで、実装完了と判断します：

1. ✅ **コードリファクタリング完了**: DomainExceptionにHasProblemDetailsトレイト適用、重複メソッド削除完了
2. ✅ **Architecture Test追加完了**: トレイト使用検証テスト追加、全Architecture Testパス
3. ✅ **RFC 7807準拠維持**: エラーレスポンス形式不変、8フィールド正常生成
4. ✅ **既存テスト全パス**: Unit/Feature/Architecture/E2Eテスト継続パス
5. ✅ **静的解析合格**: Larastan Level 8型エラー0件
6. ✅ **コードスタイル合格**: Laravel Pintコードスタイル違反0件
7. ✅ **カバレッジ維持**: コードカバレッジ96.1%以上
8. ✅ **後方互換性保証**: DomainExceptionサブクラス正常動作、メソッドシグネチャ不変
9. ✅ **DDD原則準拠**: Domain層HTTP非依存、依存性逆転原則遵守
10. ✅ **設計パターン統一**: ApplicationException・InfrastructureExceptionとの一貫性確保

---

## 注意事項

- **機能的変更なし**: 本リファクタリングは既存のエラーレスポンス形式に影響を与えません
- **後方互換性保証**: 全ての既存テストが継続してパスすることを必ず確認してください
- **段階的実装**: 各フェーズを順番に実行し、前フェーズの完了を確認してから次フェーズに進んでください
- **テスト駆動**: コード変更後、必ず対応するテストを実行して動作確認してください
- **環境変数設定**: テスト実行時は`ENV_VALIDATION_SKIP=true RATELIMIT_CACHE_STORE=array`を設定してください

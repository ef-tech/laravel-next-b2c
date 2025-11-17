# 実装計画: RFC 7807 type URI完全統一

## タスク概要

RFC 7807準拠のエラーレスポンスにおけるtype URI生成を`ErrorCode::getType()`に完全統一し、Single Source of Truthパターンを実現します。現在3箇所に分散しているtype URI生成ロジックを一元化し、保守性・一貫性・型安全性を向上させます。

---

## 実装タスク

- [x] 1. 例外クラスのtype URI生成ロジック統一実装
- [x] 1.1 HasProblemDetails traitの修正
  - ErrorCode enumを使用したtype URI生成機能を実装
  - エラーコード文字列からenum変換を行う機能を追加
  - 未定義エラーコードに対するnull安全なフォールバック処理を実装
  - 既存の動的URI生成ロジックをフォールバック用に保持
  - _Requirements: 1.1, 1.5, 2.1, 2.2, 2.3, 2.4_

- [x] 1.2 DomainExceptionクラスのtype URI生成修正
  - toProblemDetails()メソッドでErrorCode enumからtype URIを取得する機能を実装
  - getErrorType()メソッドに非推奨マークを付与し段階的移行を支援
  - getErrorType()メソッド内部でもErrorCode enumを参照するよう修正
  - フォールバック処理により後方互換性を保証
  - _Requirements: 1.2, 1.5, 2.1, 3.1, 3.2, 3.3_

- [x] 2. Unit Testsの更新と新規テストケース追加
- [x] 2.1 DomainExceptionTest.phpの既存テスト修正
  - type URI形式検証を厳密化（`/errors/`を含むことを確認）
  - ErrorCode enum定義済みエラーコードのtype URI検証を追加
  - enum未定義エラーコードのフォールバックURI検証を追加
  - getErrorType()メソッドの非推奨化を確認するテストを追加
  - _Requirements: 4.1, 4.2, 4.3, 4.4_

- [x] 2.2 ApplicationExceptionTest.phpの修正
  - HasProblemDetails traitを使用する例外クラスのtype URI検証を実装
  - enum定義済みエラーコードでの動作確認テストを追加
  - フォールバック動作の検証テストを追加
  - _Requirements: 1.3, 4.1, 4.2_

- [x] 2.3 InfrastructureExceptionTest.phpの修正
  - HasProblemDetails traitを使用する例外クラスのtype URI検証を実装
  - フォールバック動作の検証テストを追加
  - null安全性のテストを追加
  - _Requirements: 1.4, 4.1, 4.5_

- [x] 3. Feature Testsの更新
- [x] 3.1 ExceptionHandlerTest.phpの既存テスト修正
  - Global Exception HandlerのRFC 7807形式レスポンス検証を更新
  - DomainException発生時のtype URI形式検証を厳密化
  - ApplicationException/InfrastructureException発生時のテストを追加
  - Request IDとtrace_idフィールドの一致確認テストを追加
  - _Requirements: 5.1, 5.2, 5.3, 5.4_

- [x] 3.2 認証エラーのtype URI検証テスト追加
  - 認証エラー（AUTH_LOGIN_001）でErrorCode::getType()のURIが返ることを検証
  - カスタムエラーコードの後方互換性テストを追加
  - エラーレスポンスの全フィールド検証を実装
  - _Requirements: 5.2, 5.5_

- [x] 4. Architecture Testsの新規作成
- [x] 4.1 ErrorTypeUriTest.php Architecture Test作成
  - DomainExceptionクラスのtoProblemDetails()メソッドソースコード検証を実装（ErrorCode::fromString、->getType()を含むことを確認）
  - HasProblemDetails traitのソースコード検証を実装（ErrorCode enumを使用することを確認）
  - ErrorCode enumの全ケースのtype URI検証を実装（文字列型、https://で始まる、/errors/を含む）
  - 動的type URI生成の禁止ルール検証を実装（config('app.url')による直接生成を検出）
  - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5_

- [x] 5. CI/CD統合とスクリプト拡張
- [x] 5.1 verify-error-types.shスクリプト拡張
  - ddd/Shared/Exceptions/ディレクトリ内の直接的なtype URI生成パターン検出機能を追加
  - Architecture Testの自動実行機能を追加
  - 違反検出時のエラーメッセージ出力機能を実装
  - exit code制御機能を実装（違反検出時は1で終了）
  - _Requirements: 7.1, 7.2, 7.3, 7.4_
  - **実装完了**: RFC 7807 type URI統一検証ロジックを追加、ErrorTypeUriTest.php実行統合

- [x] 5.2 GitHub ActionsワークフローへのArchitecture Test統合
  - .github/workflows/test.ymlにverify-error-types.sh実行ステップを追加
  - Architecture Testを既存テストスイートの一部として統合
  - 全テスト成功時のみマージを許可する品質ゲートを設定
  - _Requirements: 7.5, 7.6_
  - **実装完了**: verify-typesジョブ追加、testジョブの前に実行（needs: verify-types）

- [x] 6. 品質保証と静的解析
- [x] 6.1 PHPStan Level 8静的解析の実行と合格
  - 全ファイルでPHPStan Level 8静的解析を実行
  - 新規エラー0件を確認
  - null安全性の検証を実施
  - 非推奨メソッド使用箇所の警告検出を確認
  - _Requirements: 3.4, 8.3_

- [x] 6.2 Laravel Pint自動フォーマットの実行
  - 全修正ファイルでLaravel Pint自動フォーマットを実行
  - Laravel Pint規約への準拠を確認
  - コーディングスタイルの一貫性を保証
  - _Requirements: 8.4_

- [x] 6.3 テストカバレッジ検証
  - Pestカバレッジレポートを生成
  - テストカバレッジ85%以上を維持していることを確認
  - 既存カバレッジ（Domain層100%、Application層98%、Infrastructure層94%）の低下がないことを検証
  - _Requirements: 8.2, 8.5_

- [x] 6.4 全テストスイート実行と実行時間検証
  - 全テストスイート（Unit/Feature/Architecture）を実行
  - 実行時間が既存ベースライン（Unit: <5秒、Feature: <30秒）を超えないことを確認
  - 全テストの成功を確認
  - _Requirements: 8.1_

- [x] 7. 統合検証とデプロイ準備（サーバー未準備のためスキップ）
- [x] 7.1 ステージング環境デプロイとE2Eテスト実行（スキップ）
  - ステージング環境へのデプロイを実施
  - 全エラーケース（認証エラー、バリデーションエラー、カスタムエラー）のE2Eテストを実行
  - type URI形式の検証を実施
  - 24時間のエラーログ監視を実施
  - _Requirements: 9.1, 9.3_
  - **Note**: サーバー環境未準備のため、本タスクはスキップ

- [x] 7.2 本番環境カナリアリリース準備（スキップ）
  - カナリアリリース計画の策定（5%→25%→100%）
  - エラーレート監視設定の確認
  - ロールバック手順の確認
  - デプロイ承認プロセスの確認
  - _Requirements: 9.2, 9.4, 9.5, 9.6_
  - **Note**: サーバー環境未準備のため、本タスクはスキップ

---

## 要件カバレッジマトリクス

| 要件ID | 対応タスク | カバレッジ |
|--------|-----------|----------|
| Req 1.1-1.5 | 1.1 | ErrorCode Enum単一ソース化 |
| Req 2.1-2.4 | 1.1, 1.2 | Null安全なフォールバック処理 |
| Req 3.1-3.4 | 1.2, 6.1 | 段階的移行戦略（Deprecation） |
| Req 4.1-4.5 | 2.1, 2.2, 2.3 | Unit Tests更新 |
| Req 5.1-5.5 | 3.1, 3.2 | Feature Tests更新 |
| Req 6.1-6.5 | 4.1 | Architecture Tests追加 |
| Req 7.1-7.6 | 5.1, 5.2 | CI/CD統合とスクリプト拡張 |
| Req 8.1-8.5 | 6.1, 6.2, 6.3, 6.4 | 品質保証とカバレッジ維持 |
| Req 9.1-9.6 | 7.1, 7.2 | デプロイと本番環境監視 |

---

## 実装完了基準

- [x] 全例外クラス（HasProblemDetails、DomainException）でErrorCode enumからtype URIを取得
- [x] 未定義エラーコードに対するnull安全なフォールバック処理が動作
- [x] getErrorType()メソッドに@deprecatedアノテーション付与完了
- [x] 全Unit Tests（DomainException、ApplicationException、InfrastructureException）が成功
- [x] 全Feature Tests（ExceptionHandler、認証エラー）が成功
- [x] Architecture Tests（ErrorTypeUriTest.php）が成功
- [x] verify-error-types.shスクリプト拡張完了とCI/CD統合完了
- [x] PHPStan Level 8静的解析合格（新規エラー0件）
- [x] Laravel Pint自動フォーマット合格
- [x] テストカバレッジ85%以上維持
- [x] 全テストスイート実行時間が既存ベースライン以内
- [x] ステージング環境E2Eテスト成功（スキップ: サーバー環境未準備）
- [x] 本番環境カナリアリリース準備完了（スキップ: サーバー環境未準備）

---

**作成日**: 2025-11-18
**言語**: ja
**フェーズ**: tasks-generated
**総タスク数**: 14（メジャータスク7、サブタスク14）

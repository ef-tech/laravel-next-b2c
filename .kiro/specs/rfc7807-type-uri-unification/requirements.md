# Requirements Document

## Introduction

本仕様は、RFC 7807 Problem Details形式のtype URI生成処理を統一し、`ErrorCode::getType()`メソッドを単一ソースとすることで、エラーハンドリングの一貫性と保守性を向上させることを目的とします。

### 背景

現在、type URI生成が以下の3箇所で重複・混在しています：
1. HasProblemDetails trait（動的URI生成: `config('app.url')/errors/{code}`）
2. DomainException class（動的URI生成: 同上）
3. ErrorCode enum（静的URI定義: `https://example.com/errors/{category}/{name}`）

この重複により、type URI変更時に複数箇所の修正が必要となり、保守性が低下しています。また、`shared/error-codes.json`から自動生成されるErrorCode enumの定義が活用されていません。

### ビジネス価値

- **保守性向上**: type URI変更時はJSONファイル更新と再生成のみで完結
- **一貫性確保**: すべてのエラーtype URIが単一ソースから生成
- **開発効率向上**: エラーコード追加時の修正箇所削減
- **後方互換性保証**: 既存のカスタムエラーコードへの影響最小化

---

## Requirements

### Requirement 1: ErrorCode Enum単一ソース化

**Objective:** システム開発者として、RFC 7807 type URIを`ErrorCode::getType()`から一元的に取得したい。これにより、type URI定義の重複を排除し、保守性を向上させる。

#### Acceptance Criteria

1. WHEN HasProblemDetails traitの`toProblemDetails()`メソッドが呼び出される THEN Laravel APIシステム SHALL `ErrorCode::fromString()`によりエラーコード文字列をenum変換し、`getType()`メソッドからtype URIを取得する

2. WHEN DomainExceptionクラスの`toProblemDetails()`メソッドが呼び出される THEN Laravel APIシステム SHALL HasProblemDetails traitと同様の実装により`ErrorCode::getType()`からtype URIを取得する

3. WHEN ApplicationExceptionクラスの`toProblemDetails()`メソッドが呼び出される THEN Laravel APIシステム SHALL DomainExceptionと同様の実装により`ErrorCode::getType()`からtype URIを取得する

4. WHEN InfrastructureExceptionクラスの`toProblemDetails()`メソッドが呼び出される THEN Laravel APIシステム SHALL DomainExceptionと同様の実装により`ErrorCode::getType()`からtype URIを取得する

5. WHERE ErrorCode enumに定義されているエラーコード（例: `AUTH-LOGIN-001`）THE Laravel APIシステム SHALL `ErrorCode::getType()`で定義された完全修飾URI（例: `https://example.com/errors/auth/invalid-credentials`）を返す

### Requirement 2: Null安全なフォールバック処理

**Objective:** システム開発者として、ErrorCode enumに未定義のエラーコードが使用された場合でも、システムが正常に動作し、適切なtype URIを返すようにしたい。これにより、後方互換性を保証し、既存のカスタムエラーコードの動作を維持する。

#### Acceptance Criteria

1. WHEN `ErrorCode::fromString()`がnullを返す（未定義エラーコード） THEN Laravel APIシステム SHALL null合体演算子（`??`）により既存の動的URI生成ロジック（`config('app.url').'/errors/'.strtolower($errorCode)`）を使用する

2. IF エラーコードがErrorCode enumに存在しない（例: `CUSTOM-ERROR-001`、`INFRA-DB-5001`） THEN Laravel APIシステム SHALL フォールバックURIとして`{APP_URL}/errors/{lowercase-error-code}`形式のURIを生成する

3. WHEN フォールバック処理が動作する THEN Laravel APIシステム SHALL 既存テストの破壊を防止し、カスタムエラーコードのレスポンス形式を維持する

4. WHERE `config('app.url')`が`https://api.example.com`に設定されている AND エラーコードが`CUSTOM-ERROR-001` THE Laravel APIシステム SHALL type URIとして`https://api.example.com/errors/custom-error-001`を返す

### Requirement 3: 段階的移行戦略（Deprecation）

**Objective:** システム開発者として、既存の`getErrorType()`メソッドを使用しているコードへの影響を最小化しつつ、新しい実装への移行を促進したい。これにより、破壊的変更を回避し、計画的な移行を実現する。

#### Acceptance Criteria

1. WHEN DomainExceptionクラスの`getErrorType()`メソッドが存在する THEN Laravel APIシステム SHALL `@deprecated`アノテーションをメソッドドキュメントに追加し、代替として`ErrorCode::getType()`の使用を推奨する

2. WHEN 非推奨化された`getErrorType()`メソッドが呼び出される THEN Laravel APIシステム SHALL 内部で`ErrorCode::getType()`を参照する実装に変更し、一貫性を保証する

3. WHERE `getErrorType()`メソッドが`@deprecated`としてマークされている THE Laravel APIシステム SHALL 既存の呼び出し元コードとの互換性を維持し、動作を変更しない

4. WHEN PHPStan Level 8静的解析が実行される THEN Laravel APIシステム SHALL 非推奨メソッド使用箇所を警告として検出する

### Requirement 4: Unit Tests更新

**Objective:** QAエンジニアとして、RFC 7807 type URI統一機能が正しく動作することを検証したい。これにより、既存機能の回帰を防止し、新機能の品質を保証する。

#### Acceptance Criteria

1. WHEN `DomainExceptionTest.php`のテストが実行される THEN テストシステム SHALL type URIの形式検証を厳密化し、`/errors/`を含む文字列であることを確認する

2. WHEN ErrorCode enumに定義されたエラーコード（例: `AUTH-LOGIN-001`）を使用する例外のテストが実行される THEN テストシステム SHALL `ErrorCode::getType()`で定義されたURI（`https://example.com/errors/auth/invalid-credentials`）と一致することを確認する

3. WHEN ErrorCode enumに未定義のエラーコード（例: `CUSTOM-ERROR-001`）を使用する例外のテストが実行される THEN テストシステム SHALL フォールバックURI（`{APP_URL}/errors/custom-error-001`）が返されることを確認する

4. WHEN `getErrorType()`メソッド（非推奨）のテストが実行される THEN テストシステム SHALL `toProblemDetails()`メソッドと同じtype URIを返すことを確認する

5. IF `getErrorCode()`メソッドが空文字列を返す THEN テストシステム SHALL null安全なフォールバック動作を検証し、システムがクラッシュしないことを確認する

### Requirement 5: Feature Tests更新

**Objective:** QAエンジニアとして、HTTPレイヤーにおけるRFC 7807形式のエラーレスポンスが正しく生成されることを検証したい。これにより、API統合の正確性を保証する。

#### Acceptance Criteria

1. WHEN `ExceptionHandlerTest.php`のDomainException発生テストが実行される THEN テストシステム SHALL RFC 7807形式のレスポンスを検証し、type URIが`/errors/`を含むことを確認する

2. WHEN ApplicationException発生時のテストが実行される THEN テストシステム SHALL enum定義type URI（`https://example.com/errors/...`）が返されることを確認する

3. WHEN InfrastructureException発生時のテストが実行される THEN テストシステム SHALL フォールバックtype URIが返されることを確認する

4. WHEN エラーレスポンスが生成される THEN テストシステム SHALL Request IDと`trace_id`フィールドが一致することを確認する

5. WHERE カスタムエラーコード（`email_already_exists`等）を使用する既存テストケース THE テストシステム SHALL type URIにエラーコード小文字変換形式（`email_already_exists`）を含むことを確認し、後方互換性を検証する

### Requirement 6: Architecture Tests追加

**Objective:** アーキテクトとして、RFC 7807 type URI統一のアーキテクチャルール遵守を自動検証したい。これにより、将来的なコード変更時にアーキテクチャ原則からの逸脱を防止する。

#### Acceptance Criteria

1. WHEN `ErrorTypeUriTest.php` Architecture Testが実行される THEN テストシステム SHALL DomainExceptionクラスの`toProblemDetails()`メソッドソースコードに`ErrorCode::fromString`文字列が含まれることを検証する

2. WHEN Architecture Testが実行される THEN テストシステム SHALL DomainExceptionクラスの`toProblemDetails()`メソッドソースコードに`->getType()`メソッド呼び出しが含まれることを検証する

3. WHEN Architecture Testが実行される THEN テストシステム SHALL HasProblemDetails traitの`toProblemDetails()`メソッドソースコードに`ErrorCode::fromString`と`->getType()`が含まれることを検証する

4. WHEN ErrorCode enumの全ケースに対するtype URI検証テストが実行される THEN テストシステム SHALL 各ケースの`getType()`が文字列型、`https://`で始まる、`/errors/`を含む、の3条件を満たすことを確認する

5. WHERE `config('app.url')`による直接的なtype URI生成が`ddd/Shared/Exceptions/`ディレクトリ内に存在する THE テストシステム SHALL Architecture Test失敗により違反を検出する

### Requirement 7: CI/CD統合とスクリプト拡張

**Objective:** DevOpsエンジニアとして、RFC 7807 type URI統一がCI/CDパイプラインで自動検証されることを保証したい。これにより、マージ前にアーキテクチャ違反を検出し、品質ゲートを強化する。

#### Acceptance Criteria

1. WHEN `scripts/verify-error-types.sh`スクリプトが実行される THEN CI/CDシステム SHALL `ddd/Shared/Exceptions/`ディレクトリ内で`config('app.url').*'/errors/'`パターンによる直接的なtype URI生成の有無を検証する

2. IF 直接的なtype URI生成が検出される THEN CI/CDシステム SHALL エラーメッセージ「HasProblemDetails/DomainExceptionで直接type URIを生成しています。ErrorCode::getType()を使用してください」を出力し、exit code 1で終了する

3. WHEN `verify-error-types.sh`スクリプトが実行される THEN CI/CDシステム SHALL `tests/Architecture/ErrorTypeUriTest.php`を自動実行し、Architecture Testの成否を検証する

4. IF Architecture Testが失敗する THEN CI/CDシステム SHALL エラーメッセージ「Architecture test failed」を出力し、exit code 1で終了する

5. WHEN GitHub Actionsワークフローの`.github/workflows/test.yml`が実行される THEN CI/CDシステム SHALL PHPテストワークフロー内で`verify-error-types.sh`スクリプトを自動実行する

6. WHERE 既存のPHPテストワークフロー THE CI/CDシステム SHALL Architecture Testを既存テストスイートの一部として実行し、全テスト成功時のみマージを許可する

### Requirement 8: 品質保証とカバレッジ維持

**Objective:** QAエンジニアとして、RFC 7807 type URI統一実装が既存のテストカバレッジを低下させず、品質基準を満たすことを保証したい。これにより、リグレッション防止とコード品質維持を実現する。

#### Acceptance Criteria

1. WHEN 全テストスイート（Unit/Feature/Architecture）が実行される THEN テストシステム SHALL 実行時間が既存ベースライン（Unit: <5秒、Feature: <30秒）を超えないことを確認する

2. WHEN Pestカバレッジレポートが生成される THEN テストシステム SHALL テストカバレッジ85%以上を維持することを確認する

3. WHEN PHPStan Level 8静的解析が実行される THEN Laravel APIシステム SHALL 新規エラー0件、既存エラー増加なしの基準を満たす

4. WHEN Laravel Pint自動フォーマットが実行される THEN Laravel APIシステム SHALL 全ファイルがLaravel Pint規約に準拠することを確認する

5. WHERE 既存の実装カバレッジ（Domain層100%、Application層98%、Infrastructure層94%） THE テストシステム SHALL RFC 7807 type URI統一実装後もカバレッジ低下がないことを検証する

### Requirement 9: デプロイと本番環境監視

**Objective:** DevOpsエンジニアとして、RFC 7807 type URI統一実装が本番環境で安全にデプロイされ、エラーレスポンスが正常に動作することを保証したい。これにより、ユーザー影響最小化と迅速なロールバック対応を実現する。

#### Acceptance Criteria

1. WHEN ステージング環境へのデプロイが完了する THEN 運用チーム SHALL 全エラーケース（認証エラー、バリデーションエラー、カスタムエラー）のE2Eテストを実行し、type URI形式を確認する

2. WHEN 本番環境へのカナリアリリースが開始される THEN 運用チーム SHALL 5%→25%→100%の段階的ロールアウトを実施し、各段階でエラーレート監視を行う

3. IF ステージング環境のエラーログ監視で24時間異常が検出されない THEN 運用チーム SHALL 本番環境へのデプロイ承認を実施する

4. WHEN 本番環境デプロイ後のエラーログ監視が実施される THEN 運用チーム SHALL type URI形式の変更（enum定義エラーコード: `https://example.com/errors/...`、カスタムエラーコード: `{APP_URL}/errors/...`）を確認する

5. IF 本番環境デプロイ前後でエラーレート変動が検出される THEN 運用チーム SHALL 即座にロールバック手順を実施し、エラーレート正常化を確認する

6. WHERE カナリアリリース各段階（5%、25%、100%） THE 運用チーム SHALL エラーレート、レスポンスタイム、type URI形式の3指標を監視し、異常検知時は自動ロールバックを実施する

---

## Non-Functional Requirements

### パフォーマンス要件

1. WHEN `ErrorCode::fromString()`メソッドが呼び出される THEN Laravel APIシステム SHALL 1ms以内にenum変換を完了する
2. WHEN フォールバック処理（null合体演算子）が動作する THEN Laravel APIシステム SHALL 既存実装と同等のレスポンスタイム（<1ms追加オーバーヘッド）を維持する

### セキュリティ要件

1. WHERE type URIがエラーレスポンスに含まれる THE Laravel APIシステム SHALL type URI内に機密情報（パスワード、トークン、個人情報）を含まない
2. WHEN フォールバックURI生成時にエラーコード文字列が使用される THEN Laravel APIシステム SHALL エラーコード文字列をサニタイズし、XSS攻撃リスクを排除する

### 後方互換性要件

1. WHERE 既存のカスタムエラーコード（`email_already_exists`、`CUSTOM-ERROR-001`等）を使用するコード THE Laravel APIシステム SHALL フォールバック処理により既存のtype URI形式を維持する
2. WHEN `getErrorType()`メソッド（非推奨）が呼び出される THEN Laravel APIシステム SHALL 既存の呼び出し元コードの動作を変更しない

### 保守性要件

1. WHEN 新規エラーコードが`shared/error-codes.json`に追加される THEN システム開発者 SHALL `npm run generate:error-types`コマンド実行のみで`ErrorCode` enum更新とtype URI定義追加を完了する
2. WHERE type URI形式変更が必要 THE システム開発者 SHALL `shared/error-codes.json`のtype URI定義のみを変更し、例外クラスの修正を不要とする

---

## Related Specifications

- **error-handling-pattern** - RFC 7807準拠エラーハンドリングパターン実装（本仕様の基盤）
- **laravel-ddd-clean-architecture-solid** - DDD/クリーンアーキテクチャ4層構造（例外クラスの設計原則）
- **api-versioning** - APIバージョニング実装（V1エンドポイントとのtype URI統合）

## References

- [RFC 7807 - Problem Details for HTTP APIs](https://datatracker.ietf.org/doc/html/rfc7807)
- [RFC 7807 type URI設計ベストプラクティス](https://datatracker.ietf.org/doc/html/rfc7807#section-3.1)
- Issue #111: エラーハンドリングパターン作成（Codexレビュー指摘元）
- `backend/laravel-api/docs/error-codes.md`: エラーコード定義ドキュメント
- `backend/laravel-api/docs/error-handling-troubleshooting.md`: トラブルシューティングガイド

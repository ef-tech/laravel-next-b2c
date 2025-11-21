# Requirements Document

## はじめに

本要件は、Laravelアプリケーション全体でタイムスタンプフォーマットを統一し、RFC 3339（ISO 8601のプロファイル）準拠形式に移行することを目的としています。

### 背景と課題

現在、アプリケーション内で以下2種類のタイムスタンプフォーマットが混在しており、可観測性とタイムゾーン明確性に問題があります：

1. **手動フォーマット** (3箇所): `now()->format('Y-m-d\TH:i:s\Z')` → 出力例: `"2025-11-06T17:19:19Z"`
   - **問題**: Carbonインスタンスが実際にUTCでなくても `Z` が付与されるリスク

2. **toIso8601String()** (約18箇所): `now()->toIso8601String()` → 出力例: `"2025-11-06T17:19:19+09:00"`
   - **問題**: UTC変換なしのため、ローカルタイムゾーンのオフセットが付く

### 関連Issue
- **Issue #115**: timestampフォーマット統一（toIso8601String()）
- **Issue #111**: エラーハンドリングパターン作成（Codexレビュー指摘）

### ビジネス価値
- **可観測性向上**: ログ・API・Problem Detailsで一貫したタイムスタンプによる運用効率化
- **タイムゾーン明確化**: UTC統一による分散システム間のタイムスタンプ整合性保証
- **RFC準拠**: 標準的なISO 8601形式採用による外部システム連携の安定性向上

---

## Requirements

### Requirement 1: タイムスタンプフォーマット統一

**Objective**: 開発者として、Laravelアプリケーション全体でタイムスタンプフォーマットを `now()->utc()->toIso8601String()` に統一したい。これにより、RFC 3339準拠のISO 8601形式（`YYYY-MM-DDTHH:MM:SS+00:00`）で一貫したタイムスタンプを出力し、可観測性を向上させる。

#### Acceptance Criteria

1. WHEN Laravel Applicationがタイムスタンプを出力する必要がある場合 THEN Laravel Application SHALL `now()->utc()->toIso8601String()` メソッドを使用する

2. WHEN 手動フォーマット `now()->format('Y-m-d\TH:i:s\Z')` が検出された場合 THEN Laravel Application SHALL `now()->utc()->toIso8601String()` に置換する

3. WHEN `toIso8601String()` メソッドが使用されている箇所で UTC変換がない場合 THEN Laravel Application SHALL `utc()` メソッドを前置する

4. WHERE RFC 7807 Problem Details、Presenter、Middlewareログ、APIレスポンス THE Laravel Application SHALL ISO 8601形式（`+00:00` オフセット）でタイムスタンプを出力する

5. WHEN タイムスタンプを出力する際 THEN Laravel Application SHALL タイムゾーンオフセットとして常に `+00:00` を使用する（`Z` サフィックスは使用しない）

---

### Requirement 2: 手動フォーマット箇所の修正

**Objective**: 開発者として、手動フォーマット `now()->format('Y-m-d\TH:i:s\Z')` を使用している3箇所のファイルを特定し、`now()->utc()->toIso8601String()` に置換したい。これにより、タイムゾーン不明確問題を解消する。

#### Acceptance Criteria

1. WHERE `backend/laravel-api/ddd/Shared/Exceptions/HasProblemDetails.php:57` THE Laravel Application SHALL `now()->format('Y-m-d\TH:i:s\Z')` を `now()->utc()->toIso8601String()` に置換する

2. WHERE `backend/laravel-api/ddd/Shared/Exceptions/DomainException.php:41` THE Laravel Application SHALL `now()->format('Y-m-d\TH:i:s\Z')` を `now()->utc()->toIso8601String()` に置換する

3. WHERE `backend/laravel-api/app/Support/ExceptionHandler.php:56` THE Laravel Application SHALL `now()->format('Y-m-d\TH:i:s\Z')` を `now()->utc()->toIso8601String()` に置換する

4. WHEN 置換後の全ファイル THEN Laravel Application SHALL PHPStan Level 8静的解析に合格する

5. WHEN 置換後の全ファイル THEN Laravel Application SHALL Laravel Pint自動フォーマット基準に適合する

---

### Requirement 3: toIso8601String()へのUTC変換追加

**Objective**: 開発者として、`toIso8601String()` を使用している約18箇所のファイルに `utc()` メソッドを追加したい。これにより、すべてのタイムスタンプをUTCに統一する。

#### Acceptance Criteria

1. WHERE `bootstrap/app.php`（lines 163,197,230,274） THE Laravel Application SHALL `toIso8601String()` の前に `utc()` を追加する

2. WHERE Presenterクラス（HealthPresenter.php、TokenPresenter.php、UserPresenter.php） THE Laravel Application SHALL `toIso8601String()` の前に `utc()` を追加する

3. WHERE Middlewareクラス（RequestLogging.php、PerformanceMonitoring.php、AuthorizationCheck.php、AuditTrail.php、SanctumTokenVerification.php） THE Laravel Application SHALL `toIso8601String()` の前に `utc()` を追加する

4. WHERE Controllerクラス（CspReportController.php、Api/V1/CspReportController.php） THE Laravel Application SHALL `toIso8601String()` の前に `utc()` を追加する

5. WHEN すべてのファイル修正後 THEN Laravel Application SHALL 全体で約20ファイルの変更を完了する

6. WHEN 修正後の各ファイル THEN Laravel Application SHALL タイムスタンプ出力時に常に `+00:00` オフセットを含む形式を使用する

---

### Requirement 4: 一括置換スクリプト実装

**Objective**: 開発者として、Perl/sedまたはRectorによる一括置換スクリプトを作成し、効率的にタイムスタンプフォーマットを統一したい。これにより、手動修正の手間とミスを削減する。

#### Acceptance Criteria

1. WHEN 一括置換スクリプトを実行する場合 THEN Replace Script SHALL `now()->format('Y-m-d\TH:i:s\Z')` を `now()->utc()->toIso8601String()` に置換する

2. WHEN 一括置換スクリプトを実行する場合 THEN Replace Script SHALL `Carbon::now()->format('Y-m-d\TH:i:s\Z')` を `Carbon::now()->utc()->toIso8601String()` に置換する

3. WHEN 一括置換スクリプトを実行する場合 THEN Replace Script SHALL `$variable->format('Y-m-d\TH:i:s\Z')` を `$variable->utc()->toIso8601String()` に置換する

4. WHEN 一括置換スクリプトを実行する場合 AND `utc()` メソッドが前置されていない場合 THEN Replace Script SHALL `toIso8601String()` の前に `utc()->` を追加する

5. WHEN 一括置換スクリプト実行前 THEN Replace Script SHALL Gitバックアップタグ（`backup/before-timestamp-migration`）を作成する

6. IF スクリプトが DateTime/DateTimeImmutable を検出した場合 THEN Replace Script SHALL 手動確認フラグを出力する

---

### Requirement 5: テスト修正とヘルパー実装

**Objective**: 開発者として、タイムスタンプアサーションを修正し、テストヘルパーメソッドを追加したい。これにより、テストの安定性と保守性を向上させる。

#### Acceptance Criteria

1. WHERE `backend/laravel-api/tests/TestCase.php` THE Test Suite SHALL `assertIso8601Timestamp(string $timestamp)` メソッドを追加する

2. WHEN `assertIso8601Timestamp()` を実行する場合 THEN Test Helper SHALL タイムスタンプが `/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\+00:00$/` 正規表現にマッチすることを検証する

3. WHERE `tests/TestCase.php` THE Test Suite SHALL `freezeTimeAt(string $datetime)` メソッドを追加する

4. WHEN `freezeTimeAt()` を実行する場合 THEN Test Helper SHALL `Carbon::setTestNow()` でタイムスタンプを固定する

5. WHERE `tests/TestCase.php` THE Test Suite SHALL `unfreezeTime()` メソッドを追加する

6. WHEN `unfreezeTime()` を実行する場合 THEN Test Helper SHALL `Carbon::setTestNow()` をクリアする

7. WHERE timestampアサーションを含む全テストファイル THE Test Suite SHALL `Z` サフィックス期待を `+00:00` 形式に変更する

8. WHEN テスト内でタイムスタンプを比較する場合 THEN Test Suite SHALL `Carbon::setTestNow()` でタイムスタンプを固定する

9. WHEN テストアサーション修正後 THEN Test Suite SHALL 約10ファイルのテストファイルを更新する

---

### Requirement 6: 検証と品質保証

**Objective**: 開発者として、修正後のコードが静的解析・テスト・手動検証に合格することを確認したい。これにより、品質を保証し、安全にデプロイする。

#### Acceptance Criteria

1. WHEN 全ファイル修正完了後 THEN Laravel Application SHALL PHPStan Level 8静的解析に合格する（実行時間<2分、メモリ上限2GB）

2. WHEN 全ファイル修正完了後 THEN Laravel Application SHALL Laravel Pint自動フォーマットを適用し、基準に適合する

3. WHEN 全テスト実行時 THEN Test Suite SHALL Pestテストスイート全テストに成功する（実行時間<2分）

4. WHEN 並列テスト実行時 THEN Test Suite SHALL `php artisan test --parallel` で全テストに成功する

5. WHEN カバレッジレポート生成時 THEN Test Suite SHALL テストカバレッジ85%以上を維持する

6. WHEN 手動APIテストを実行する場合 THEN Manual Tester SHALL ヘルスチェックエンドポイント（`GET /api/v1/health`）のタイムスタンプが `+00:00` 形式であることを確認する

7. WHEN 手動APIテストを実行する場合 THEN Manual Tester SHALL エラーレスポンス（RFC 7807 Problem Details）のタイムスタンプが `+00:00` 形式であることを確認する

8. WHEN 認証トークン発行APIテスト時 THEN Manual Tester SHALL レスポンスのタイムスタンプフィールドが `+00:00` 形式であることを確認する

---

### Requirement 7: 影響範囲調査とレポート作成

**Objective**: 開発者として、修正対象ファイルをripgrepで検出し、影響範囲レポートを作成したい。これにより、修正漏れを防ぎ、チームに変更内容を明確に伝える。

#### Acceptance Criteria

1. WHEN ripgrepで対象ファイル検出を実行する場合 THEN Analysis Tool SHALL `format\(['"]Y-m-d\\TH:i:s\\Z['"]` パターンを検索する

2. WHEN ripgrepで対象ファイル検出を実行する場合 THEN Analysis Tool SHALL `toIso8601String\(\)` パターンを検索し、`utc()` がない箇所を抽出する

3. WHEN ripgrepで対象ファイル検出を実行する場合 THEN Analysis Tool SHALL RFC 7807関連ファイル（`ddd/Shared/Exceptions/`, `app/Support/ExceptionHandler.php`）で `timestamp` キーワードを検索する

4. WHEN ripgrepで対象ファイル検出を実行する場合 THEN Analysis Tool SHALL テストファイル内の `assertJson.*timestamp|assertJsonPath.*timestamp` パターンを検索する

5. WHEN 影響範囲レポート作成時 THEN Project Documentation SHALL 以下の情報を含む：
   - 手動フォーマット箇所（約3ファイル）
   - toIso8601String()でutc()なし箇所（約18ファイル）
   - 合計修正ファイル数（約20ファイル）
   - RFC 3339準拠への移行理由
   - 後方互換性の影響評価

6. WHEN 影響範囲レポート完成時 THEN Project Team SHALL チーム内レビューを完了する

---

### Requirement 8: 後方互換性とリスク管理

**Objective**: 開発者として、APIクライアント（フロントエンド、外部システム）への影響を評価し、後方互換性を確保したい。これにより、安全にタイムスタンプフォーマットを変更する。

#### Acceptance Criteria

1. WHERE フロントエンド（Next.js User App、Admin App） THE Frontend Application SHALL `new Date(response.timestamp)` でタイムスタンプをパースする（両形式対応）

2. IF フロントエンドが `Z` サフィックス前提のパーサーを使用している場合 THEN Frontend Application SHALL 正規表現 `/Z|(\+00:00)/` で両形式を許容する

3. WHERE ログ集計システム（Fluent Bit、Logstash等） THE Monitoring System SHALL タイムスタンプパース正規表現を `Z|\\+00:00` 許容形式に更新する

4. WHEN ステージング環境デプロイ後 THEN Operations Team SHALL 24時間ログ監視を実行し、タイムスタンプパース失敗がないことを確認する

5. WHEN 本番環境デプロイ後 THEN Operations Team SHALL 48時間APIクライアントエラー監視を実行し、タイムスタンプ関連エラーがないことを確認する

6. IF タイムスタンプフォーマット変更により問題が発生した場合 THEN Operations Team SHALL Gitバックアップタグからロールバックを実行できる

---

### Requirement 9: CI/CD統合とワークフロー成功

**Objective**: 開発者として、修正後のコードがGitHub Actions全ワークフローに合格することを確認したい。これにより、CI/CDパイプラインの品質を保証する。

#### Acceptance Criteria

1. WHEN GitHub Actions PHP Quality Checkワークフロー実行時 THEN CI Pipeline SHALL Pint + Larastan検証に成功する

2. WHEN GitHub Actions Backend Testsワークフロー実行時 THEN CI Pipeline SHALL Pest 4テストスイート全テストに成功する

3. WHEN GitHub Actions E2E Testsワークフロー実行時 THEN CI Pipeline SHALL Playwrightテストスイートに成功する

4. WHERE E2Eテスト（`e2e/projects/shared/tests/api-error-handling.spec.ts`） THE E2E Test Suite SHALL エラーレスポンスのタイムスタンプが `+00:00` 形式であることを検証する

5. WHEN E2Eテスト実行時 THEN E2E Test Suite SHALL タイムスタンプをJavaScript `Date` オブジェクトで正しくパース可能であることを確認する

---

### Requirement 10: デプロイメントとロールアウト戦略

**Objective**: 運用担当者として、段階的デプロイ（ステージング→本番）により安全にタイムスタンプフォーマット変更を適用したい。これにより、本番環境への影響を最小化する。

#### Acceptance Criteria

1. WHEN プルリクエスト作成時 THEN Developer SHALL タイトルを `chore: timestampフォーマットをISO 8601 UTC形式に統一` とする

2. WHEN プルリクエスト作成時 THEN Developer SHALL 以下の情報を含む：
   - 変更ファイル一覧（約20ファイル）
   - タイムスタンプフォーマット変更の理由
   - 後方互換性の影響評価
   - テストカバレッジレポート

3. WHEN プルリクエストレビュー時 THEN Code Reviewer SHALL 2名以上の承認を取得する

4. WHEN ステージング環境デプロイ時 THEN Deployment Pipeline SHALL デプロイに成功する

5. WHEN ステージング環境デプロイ後 THEN Operations Team SHALL 24時間ログ監視を実行し、タイムスタンプ形式確認と異常検知を行う

6. WHEN 本番環境デプロイ時 THEN Deployment Pipeline SHALL カナリアリリース方式でデプロイする（推奨）

7. WHEN 本番環境デプロイ後 THEN Operations Team SHALL 48時間APIクライアントエラー監視を実行し、異常がないことを確認する

8. IF 本番環境で問題が発生した場合 THEN Operations Team SHALL Gitバックアップタグ（`backup/before-timestamp-migration`）からロールバックを実行する

---

## 対象外（Out of Scope）

以下の項目は本要件の対象外とします：

1. **フロントエンドコード修正**: `frontend/admin-app/`, `frontend/user-app/` のタイムスタンプパース側は変更不要（`new Date()` が両形式対応のため）

2. **データベーススキーマ変更**: `DATETIME`/`TIMESTAMP` カラムはUTCで保存（既存のEloquent動作維持）

3. **APIバージョニング**: `/api/v2` の導入は本タスク対象外（既存 `/api/v1` を修正）

4. **Feature Flag導入**: 段階的ロールアウトは任意（推奨だが必須ではない）

5. **共通ヘルパークラス作成**: `App\Support\Time` 等の将来的なリファクタリングは対象外

---

## 技術的制約

1. **PHP**: ^8.4
2. **Laravel**: ^12.0
3. **Carbon**: Laravel同梱バージョン
4. **PHPStan**: Level 8準拠
5. **Laravel Pint**: Laravel標準プリセット
6. **Pest**: ^4.0
7. **テストカバレッジ**: 85%以上維持

---

## 参考情報

### RFC仕様
- [RFC 3339 - Date and Time on the Internet: Timestamps](https://datatracker.ietf.org/doc/html/rfc3339)
- [RFC 7807 - Problem Details for HTTP APIs](https://datatracker.ietf.org/doc/html/rfc7807)
- [ISO 8601 - Date and time format](https://www.iso.org/iso-8601-date-and-time-format.html)

### Carbon Documentation
- [Carbon - toIso8601String()](https://carbon.nesbot.com/docs/#api-formatting)
- [Carbon - utc()](https://carbon.nesbot.com/docs/#api-timezone)

### プロジェクト内ドキュメント
- Issue #111: エラーハンドリングパターン作成（Codexレビュー指摘）
- Issue #115: timestampフォーマット統一（toIso8601String()）

### 推定工数
- **影響調査**: 30分
- **コード修正**: 2時間
- **テスト修正**: 1.5時間
- **検証**: 1時間
- **合計**: 5時間

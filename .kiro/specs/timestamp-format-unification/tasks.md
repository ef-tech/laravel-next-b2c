# 実装タスク

## タスク概要

本タスクリストは、Laravelアプリケーション全体でタイムスタンプフォーマットを `now()->utc()->toIso8601String()` に統一し、RFC 3339（ISO 8601のプロファイル）準拠形式 `YYYY-MM-DDTHH:MM:SS+00:00` に移行するための実装手順を定義します。

**推定総工数**: 5時間

---

## タスク一覧

- [ ] 1. 準備作業と影響範囲調査を実施する
- [ ] 1.1 ripgrepで対象ファイルを検出し影響範囲レポートを作成する
  - 手動フォーマット `format('Y-m-d\TH:i:s\Z')` パターンを検索
  - `toIso8601String()` メソッドで `utc()` がない箇所を検索
  - RFC 7807関連ファイルで `timestamp` キーワードを検索
  - テストファイル内のタイムスタンプアサーションを検索
  - 検出結果を影響範囲レポートとしてまとめる（修正ファイル一覧、合計約14ファイル確認）
  - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.5_

- [ ] 1.2 チーム内レビューを実施し影響範囲を共有する
  - 影響範囲レポートをチームメンバーに共有
  - RFC 3339準拠への移行理由を説明
  - 後方互換性の影響評価を確認
  - フロントエンドへの影響なし（`new Date()` 両形式対応）を確認
  - _Requirements: 7.6, 8.1, 8.2_

- [ ] 1.3 Gitバックアップタグを作成する
  - `backup/before-timestamp-migration` タグを作成
  - タグ作成コマンド実行後、タグが正常に作成されたことを確認
  - ロールバック手順をドキュメント化
  - _Requirements: 4.5, 8.6_

- [ ] 2. タイムスタンプフォーマット一括置換を実施する
- [ ] 2.1 一括置換スクリプトを作成する
  - Perlによる正規表現ベースの置換スクリプトを実装
  - Pattern 1: `now()->format('Y-m-d\TH:i:s\Z')` → `now()->utc()->toIso8601String()` 置換
  - Pattern 2: `Carbon::now()->format('Y-m-d\TH:i:s\Z')` → `Carbon::now()->utc()->toIso8601String()` 置換
  - Pattern 3: `toIso8601String()` の前に `utc()` を追加（既存のutc()がない場合のみ）
  - Pattern 4: `$variable->format('Y-m-d\TH:i:s\Z')` → `$variable->utc()->toIso8601String()` 置換
  - スクリプトにバックアップタグ作成処理を含める
  - DateTime/DateTimeImmutable 検出時の手動確認フラグ出力機能を追加
  - 変更ファイル一覧の出力機能を追加
  - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 4.6_

- [ ] 2.2 一括置換スクリプトを実行する
  - スクリプト実行前にバックアップタグが作成されることを確認
  - 4パターンの置換を実行
  - 置換実行後の変更ファイル一覧を確認（約14ファイル）
  - 手動確認が必要な箇所のレポートを確認
  - _Requirements: 1.2, 1.3, 2.3, 3.1, 3.2, 3.3, 3.4_

- [ ] 2.3 置換結果を手動で確認する
  - `git diff` で全変更箇所を目視レビュー
  - Exception Handler（ExceptionHandler.php、bootstrap/app.php）の修正を確認
  - Middleware（5ファイル、9箇所）の修正を確認
  - Presenter（3ファイル、7箇所）の修正を確認
  - Controller（2ファイル、2箇所）の修正を確認
  - Test Files（2ファイル、2箇所）の修正を確認
  - DateTime/DateTimeImmutable を使用している箇所があれば手動で修正
  - _Requirements: 1.1, 1.5, 2.1, 2.2, 2.3, 3.5, 3.6_

- [ ] 3. テストヘルパーメソッドを実装する
- [ ] 3.1 TestCaseクラスにタイムスタンプ検証ヘルパーを追加する
  - `assertIso8601Timestamp(string $timestamp, string $message = '')` メソッドを実装
  - ISO 8601 UTC形式の正規表現検証（`/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\+00:00$/`）
  - わかりやすいエラーメッセージを提供
  - _Requirements: 5.1, 5.2_

- [ ] 3.2 TestCaseクラスにタイムスタンプ固定ヘルパーを追加する
  - `freezeTimeAt(string $datetime)` メソッドを実装（`Carbon::setTestNow()` を使用）
  - `unfreezeTime()` メソッドを実装（`Carbon::setTestNow()` をクリア）
  - `tearDown()` メソッドをオーバーライドして自動的にタイムスタンプ固定を解除
  - Carbonインスタンスを返すように実装
  - _Requirements: 5.3, 5.4, 5.5, 5.6_

- [ ] 4. 既存テストのタイムスタンプアサーションを修正する
- [ ] 4.1 `Z` サフィックス期待のテストを検索して修正する
  - `rg "timestamp.*Z"` でテストファイルを検索
  - 検出された全テストファイルで `Z` サフィックス期待を `+00:00` 形式に変更
  - 新しい `assertIso8601Timestamp()` ヘルパーを使用
  - 約10ファイルのテストファイルを更新
  - _Requirements: 5.7, 5.9_

- [ ] 4.2 テスト内でタイムスタンプ比較に `Carbon::setTestNow()` を使用する
  - タイムスタンプを固定する必要があるテストを特定
  - `freezeTimeAt()` ヘルパーを使用してタイムスタンプを固定
  - テスト実行前のタイムスタンプ固定を徹底
  - `Carbon::setTestNow()` の使用を統一
  - _Requirements: 5.8_

- [ ] 5. Unit Testsを追加・修正する
- [ ] 5.1 Presenterのタイムスタンプ形式をテストする
  - HealthPresenterのタイムスタンプ形式テストを追加・修正
  - TokenPresenterのタイムスタンプ形式テストを追加・修正
  - UserPresenterのタイムスタンプ形式テストを追加・修正
  - `freezeTimeAt()` でタイムスタンプを固定
  - `assertIso8601Timestamp()` で `+00:00` 形式を検証
  - _Requirements: 1.4, 3.2, 3.6, 5.1, 5.2_

- [ ] 5.2 TestCaseヘルパーメソッドの動作をテストする
  - `assertIso8601Timestamp()` の正規表現マッチング検証テストを追加
  - `freezeTimeAt()` / `unfreezeTime()` の `Carbon::setTestNow()` 動作検証テストを追加
  - 正常系・異常系の両方をテスト
  - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5, 5.6_

- [ ] 6. Integration Testsを追加・修正する
- [ ] 6.1 Middlewareログのタイムスタンプ形式をテストする
  - RequestLoggingのログタイムスタンプ形式テストを追加
  - PerformanceMonitoringのログタイムスタンプ形式テストを追加
  - AuditTrailのログタイムスタンプ形式テストを追加
  - ログファイル内のタイムスタンプが `+00:00` 形式であることを検証
  - _Requirements: 3.3, 3.6_

- [ ] 6.2 Exception HandlerのProblem Detailsをテストする
  - 404エラーレスポンスのタイムスタンプ検証テストを追加
  - 500エラーレスポンスのタイムスタンプ検証テストを追加
  - RFC 7807形式のタイムスタンプフィールドが `+00:00` 形式であることを検証
  - _Requirements: 1.4, 2.1, 2.2, 2.3, 3.1_

- [ ] 7. E2E Testsを追加・修正する
- [ ] 7.1 APIレスポンスのタイムスタンプ形式を検証する
  - ヘルスチェックAPI (`/api/v1/health`) のタイムスタンプ形式をテスト
  - エラーレスポンス（404/500）のタイムスタンプ形式をテスト
  - 認証トークン発行APIレスポンスのタイムスタンプ形式をテスト
  - ISO 8601 UTC形式（`+00:00` オフセット）を検証
  - JavaScript `Date` オブジェクトで正しくパース可能であることを確認
  - _Requirements: 1.4, 6.6, 6.7, 6.8, 9.4, 9.5_

- [ ] 8. 品質保証とコード検証を実施する
- [ ] 8.1 PHPStan Level 8静的解析を実行する
  - 全ファイル修正完了後にPHPStan Level 8を実行
  - 静的解析に合格することを確認（実行時間<2分、メモリ上限2GB）
  - 型エラーが検出された場合は修正
  - _Requirements: 2.4, 6.1_

- [ ] 8.2 Laravel Pint自動フォーマットを適用する
  - Laravel Pint自動フォーマットを実行
  - フォーマット基準に適合することを確認
  - コードスタイルの一貫性を保証
  - _Requirements: 2.5, 6.2_

- [ ] 8.3 Pestテストスイート全テストを実行する
  - 全テストを実行してpassすることを確認（実行時間<2分）
  - 並列テスト実行 (`php artisan test --parallel`) でも成功することを確認
  - テストカバレッジレポートを生成して85%以上を確認
  - 失敗したテストがあれば修正
  - _Requirements: 6.3, 6.4, 6.5_

- [ ] 8.4 手動APIテストを実施する
  - ヘルスチェックエンドポイント (`GET /api/v1/health`) を手動テスト
  - エラーレスポンス（404/500）を手動テスト（存在しないエンドポイントにアクセス）
  - 認証トークン発行API (`POST /api/v1/login`) を手動テスト
  - 全てのレスポンスタイムスタンプが `+00:00` 形式であることを確認
  - _Requirements: 6.6, 6.7, 6.8_

- [ ] 9. CI/CDワークフローを検証する
- [ ] 9.1 GitHub Actions PHP Quality Checkを確認する
  - プルリクエスト作成後、Pint + Larastan検証が成功することを確認
  - ワークフローが正常に実行されることを確認
  - _Requirements: 9.1_

- [ ] 9.2 GitHub Actions Backend Testsを確認する
  - プルリクエスト作成後、Pest 4テストスイートが成功することを確認
  - 全テストがpassすることを確認
  - _Requirements: 9.2_

- [ ] 9.3 GitHub Actions E2E Testsを確認する
  - プルリクエスト作成後、Playwrightテストスイートが成功することを確認
  - E2Eテストでタイムスタンプ形式が正しいことを確認
  - _Requirements: 9.3, 9.4, 9.5_

- [ ] 10. ドキュメントを更新する
- [ ] 10.1 プロジェクトドキュメントを更新する
  - README.mdにタイムスタンプフォーマット統一を記載
  - docs/RATELIMIT_IMPLEMENTATION.mdのサンプルコードを `utc()->toIso8601String()` に修正
  - API仕様書（OpenAPI）のexampleを `+00:00` 形式に更新（該当する場合）
  - _Requirements: タイムスタンプフォーマット統一の記録_

- [ ] 10.2 .kiro/steering/tech.mdを更新する
  - タイムスタンプフォーマット統一ポリシーセクションを追加
  - 標準形式（`now()->utc()->toIso8601String()`）を記載
  - 禁止形式（手動format、UTC変換なしのtoIso8601String）を記載
  - テストヘルパーメソッドの使用方法を記載
  - _Requirements: タイムスタンプフォーマット統一の標準化_

- [ ] 11. プルリクエストを作成する
- [ ] 11.1 変更内容をレビューしてプルリクエストを作成する
  - タイトル: `chore: timestampフォーマットをISO 8601 UTC形式に統一`
  - 変更ファイル一覧（約14ファイル）を記載
  - タイムスタンプフォーマット変更の理由（RFC 3339準拠、タイムゾーン明確化）を記載
  - 後方互換性の影響評価（フロントエンド影響なし）を記載
  - テストカバレッジレポート（85%以上維持）を添付
  - _Requirements: 10.1, 10.2_

- [ ] 11.2 コードレビュー承認を取得する
  - 2名以上のレビュアーから承認を取得
  - レビューコメントに対応
  - _Requirements: 10.3_

---

## タスク実行時の注意事項

### 重要な確認事項
1. **HasProblemDetails.php は修正不要**: 既に `toIso8601ZuluString()` を使用しており、UTC保証済み
2. **DomainException.php の確認**: HasProblemDetails trait を使用している可能性があり、既に修正済みの可能性
3. **後方互換性**: フロントエンド（Next.js）の `new Date()` は両形式対応のため影響なし

### ロールバック手順
問題が発生した場合:
```bash
git reset --hard backup/before-timestamp-migration
```

### テスト実行コマンド
```bash
# PHPStan Level 8静的解析
./vendor/bin/phpstan analyse

# Laravel Pint自動フォーマット
./vendor/bin/pint

# Pestテスト全件実行
./vendor/bin/pest

# 並列テスト実行
php artisan test --parallel

# カバレッジレポート生成
./vendor/bin/pest --coverage
```

### 手動APIテストエンドポイント
- ヘルスチェック: `GET http://localhost:13000/api/v1/health`
- エラーテスト: `GET http://localhost:13000/api/v1/non-existent-endpoint`
- 認証API: `POST http://localhost:13000/api/v1/login`

---

## 次のステップ

タスクを順番に実行し、各タスク完了時にチェックマークを付けてください。

実装開始コマンド:
```bash
/kiro:spec-impl timestamp-format-unification          # 全タスク実行
/kiro:spec-impl timestamp-format-unification 1.1      # 特定タスク実行
/kiro:spec-impl timestamp-format-unification 1,2,3    # 複数タスク実行
```

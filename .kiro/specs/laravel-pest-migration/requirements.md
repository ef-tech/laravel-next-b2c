# Requirements Document

## GitHub Issue Information

**Issue**: [#10](https://github.com/ef-tech/laravel-next-b2c/issues/10) - Laravel PHPUnit を Pest に移行して設定とテストサンプル作成

## Introduction

本仕様は、Laravel 12プロジェクトにおけるテストフレームワークをPHPUnit 11.5からPest 4へ移行することを目的とする。Pest 4の直感的な構文により、テストコードの可読性と保守性を向上させ、Architecture Testing機能による設計ルール自動検証を導入する。また、API専用アーキテクチャに特化したテストサンプル（Sanctum認証、CORS、JSON API）を提供し、将来的なBrowser Testing機能の活用基盤を整備する。

既存のPHPUnit テスト（約20ファイル）は段階的に移行し、移行期間中は両フレームワークを併用する。CI/CDパイプラインはTest Sharding（4並列）に対応し、テストカバレッジ80%以上を維持しながら、開発者の学習コストを最小限に抑える移行戦略を採用する。

## Requirements

### Requirement 1: Pest 4環境のインストールと基本設定

**Objective:** テスト担当開発者として、Pest 4テストフレームワークを既存のLaravel 12プロジェクトに導入し、PHPUnitと共存できる環境を構築したい。これにより、段階的な移行を可能にし、既存テストが引き続き動作することを保証する。

#### Acceptance Criteria

1. WHEN 開発者が `composer require --dev pestphp/pest:^4.0` を実行 THEN Pest環境 SHALL Pest 4とプラグイン（pest-plugin-laravel ^4.0, pest-plugin-arch ^4.0, nunomaduro/collision ^8.0）を正常にインストールする
2. WHEN 開発者が `php artisan pest:install` を実行 THEN Pest環境 SHALL `tests/Pest.php` ファイルを生成し、Laravel統合設定を初期化する
3. WHEN composer.jsonに `test-pest` スクリプトが定義されている AND 開発者が `composer test-pest` を実行 THEN Pest環境 SHALL Pestテストのみを実行し、設定クリア後にテスト結果を表示する
4. WHEN composer.jsonに `test-all` スクリプトが定義されている AND 開発者が `composer test-all` を実行 THEN テスト環境 SHALL PHPUnitテストとPestテストの両方を順次実行し、全テスト結果を統合表示する
5. WHEN `tests/Pest.php` に `uses(TestCase::class)->in('Feature', 'Unit')` が記述されている THEN Pest環境 SHALL Feature/UnitディレクトリのPestテストでLaravel TestCaseを自動適用する
6. WHEN `pest.xml` にカバレッジ設定が記述されている THEN Pest環境 SHALL テスト実行時にカバレッジレポート（HTML/Clover形式）を生成する

### Requirement 2: Laravel統合設定とカスタムExpectationの作成

**Objective:** テスト担当開発者として、PestをLaravel 12のAPI専用アーキテクチャに統合し、API特有のテストパターンを簡潔に記述できるカスタムExpectationを提供したい。これにより、Sanctum認証やCORS検証などのAPI専用テストを効率的に記述できる。

#### Acceptance Criteria

1. WHEN `tests/Pest.php` に `uses(RefreshDatabase::class)->in('Feature')` が記述されている THEN Pest環境 SHALL Featureテスト実行時にデータベースを自動的にリフレッシュする
2. WHEN `tests/Pest.php` に `actingAsApi()` ヘルパー関数が定義されている AND テストコードで `actingAsApi($user, ['read'])` を呼び出す THEN Pest環境 SHALL Laravel Sanctumでトークン認証を設定し、指定したabilitiesでユーザーを認証する
3. WHEN `tests/Pest.php` に `toBeJsonOk` カスタムExpectationが定義されている AND テストコードで `expect($response)->toBeJsonOk()` を実行 THEN Pest環境 SHALL HTTPステータス200とContent-Type: application/jsonヘッダーを検証する
4. WHEN `tests/Pest.php` に `toHaveCors` カスタムExpectationが定義されている AND テストコードで `expect($response)->toHaveCors('http://localhost:3000')` を実行 THEN Pest環境 SHALL Access-Control-Allow-Origin、Access-Control-Allow-Methods、Access-Control-Allow-Headersヘッダーの存在を検証する
5. WHEN `tests/Pest.php` に `jsonHeaders()` ヘルパー関数が定義されている AND テストコードで `jsonHeaders(['Authorization' => 'Bearer token'])` を呼び出す THEN Pest環境 SHALL Accept: application/json、Content-Type: application/jsonと追加ヘッダーをマージして返す
6. WHEN `tests/Pest.php` にグローバル `beforeEach()` が定義されている THEN Pest環境 SHALL 全Pestテスト実行前に共通セットアップ処理を実行する

### Requirement 3: API専用テストサンプルの作成（Sanctum認証）

**Objective:** バックエンド開発者として、Laravel Sanctum認証を使用するAPI専用テストのベストプラクティスサンプルを参照したい。これにより、トークンベース認証、権限検証、未認証アクセス拒否のテストパターンを理解し、新規API開発時に適用できる。

#### Acceptance Criteria

1. WHEN `tests/Feature/Api/AuthenticationTest.php` に認証済みユーザープロファイル取得テストが存在する AND テストが実行される THEN テストシステム SHALL 認証済みユーザーで `/api/me` にGETリクエストを送信し、200 OKとユーザーID/emailを含むJSONレスポンスを検証する
2. WHEN `tests/Feature/Api/AuthenticationTest.php` に未認証アクセス拒否テストが存在する AND テストが実行される THEN テストシステム SHALL 認証なしで `/api/me` にGETリクエストを送信し、401 Unauthorizedレスポンスを検証する
3. WHEN `tests/Feature/Api/AuthenticationTest.php` にトークンabilitiesテストが存在する AND テストが実行される THEN テストシステム SHALL 読み取り権限のみのトークンで `/api/me` (GET) が成功し、`/api/users` (POST) が403 Forbiddenになることを検証する
4. WHEN AuthenticationTestファイルがPest形式で記述されている THEN テストシステム SHALL `it('returns profile for authenticated user', function() {...})` 構文でテストを定義する
5. WHEN AuthenticationTestで `actingAsApi($user)` ヘルパーを使用 THEN テストシステム SHALL Sanctum認証設定を簡潔に記述できる
6. WHEN AuthenticationTestで `expect($response)->toBeJsonOk()` を使用 THEN テストシステム SHALL カスタムExpectationによりアサーションを簡潔に記述できる

### Requirement 4: API専用テストサンプルの作成（CORS検証）

**Objective:** バックエンド開発者として、Next.jsフロントエンド（localhost:3000, 3001）からのクロスオリジンリクエストに対するCORS設定を検証するテストサンプルを参照したい。これにより、APIがフロントエンドと正常に連携できることを保証するテストパターンを理解できる。

#### Acceptance Criteria

1. WHEN `tests/Feature/Api/CorsTest.php` に許可オリジンからのOPTIONSリクエストテストが存在する AND テストが実行される THEN テストシステム SHALL Origin: http://localhost:3000ヘッダーで `/api/up` にOPTIONSリクエストを送信し、CORS関連ヘッダー（Access-Control-Allow-Origin等）の存在を検証する
2. WHEN `tests/Feature/Api/CorsTest.php` に実際のリクエストでのCORSヘッダーテストが存在する AND テストが実行される THEN テストシステム SHALL Origin: http://localhost:3000ヘッダーで `/api/up` にGETリクエストを送信し、Access-Control-Allow-Origin: http://localhost:3000ヘッダーを検証する
3. WHEN CorsTestファイルがPest形式で記述されている THEN テストシステム SHALL `it('allows requests from allowed origin', function() {...})` 構文でテストを定義する
4. WHEN CorsTestで `expect($response)->toHaveCors($origin)` カスタムExpectationを使用 THEN テストシステム SHALL CORSヘッダー検証を簡潔に記述できる
5. WHEN CorsTestで `jsonHeaders()` ヘルパーと `withHeaders()` を併用 THEN テストシステム SHALL Originヘッダーを含むリクエストヘッダーを効率的に構築できる

### Requirement 5: API専用テストサンプルの作成（JSON API レスポンス検証）

**Objective:** バックエンド開発者として、RESTful JSON APIのレスポンス構造とペジネーションを検証するテストサンプルを参照したい。これにより、標準的なJSON:API仕様に準拠したレスポンス形式を保証するテストパターンを理解できる。

#### Acceptance Criteria

1. WHEN `tests/Feature/Api/JsonApiTest.php` にリソース作成テストが存在する AND テストが実行される THEN テストシステム SHALL 認証済みユーザーで `/api/resources` にPOSTリクエストを送信し、201 Createdと標準JSON:API構造（data.id, name, description, created_at, updated_at）を検証する
2. WHEN JsonApiTestのリソース作成テストが実行される THEN テストシステム SHALL `assertJson(fn ($json) => $json->where('data.name', 'Test Resource')->has('data.id'))` でレスポンス内容を詳細検証する
3. WHEN `tests/Feature/Api/JsonApiTest.php` にペジネーションテストが存在する AND テストが実行される THEN テストシステム SHALL `/api/users?page=1&per_page=10` にGETリクエストを送信し、200 OKとペジネーションメタデータ（meta.total, per_page, current_page, last_page, links）を検証する
4. WHEN JsonApiTestのペジネーションテストが実行される THEN テストシステム SHALL `expect($response->json('meta.per_page'))->toBe(10)` でページサイズを検証し、`count($response->json('data'))`で実際のデータ件数を検証する
5. WHEN JsonApiTestファイルがPest形式で記述されている THEN テストシステム SHALL `it('creates a resource and returns canonical JSON:API payload', function() {...})` 構文でテストを定義する
6. WHEN JsonApiTestで `actingAsApi($user)` と `jsonHeaders()` を使用 THEN テストシステム SHALL 認証とヘッダー設定を簡潔に記述できる

### Requirement 6: アーキテクチャテストの作成（レイヤー分離・命名規則・コード品質）

**Objective:** アーキテクチャ担当開発者として、Pest Architecture Testing機能を使用してコードベースの設計ルール（レイヤー分離、命名規則、コード品質）を自動検証したい。これにより、プロジェクト全体でアーキテクチャ原則が維持され、設計の逸脱を早期に検出できる。

#### Acceptance Criteria

1. WHEN `tests/Architecture/LayerTest.php` にレイヤー分離テストが存在する AND テストが実行される THEN テストシステム SHALL `arch('controllers should not depend on models directly')` で Controllers層がModels層に直接依存していないことを検証する
2. WHEN LayerTestが実行される THEN テストシステム SHALL `expect('App\Http\Controllers')->not->toUse('App\Models')` でControllerからModelへの直接参照を禁止し、`->toOnlyUse(['Illuminate', 'App\Services', 'App\Http\Requests'])` で許可された依存関係のみを確認する
3. WHEN `tests/Architecture/NamingTest.php` に命名規則テストが存在する AND テストが実行される THEN テストシステム SHALL `arch('controllers should be suffixed with Controller')` でコントローラー命名規則を検証する
4. WHEN NamingTestが実行される THEN テストシステム SHALL `expect('App\Http\Controllers')->toHaveSuffix('Controller')` でController接尾辞、`expect('App\Http\Requests')->toHaveSuffix('Request')` でRequest接尾辞、`expect('App\Http\Resources')->toHaveSuffix('Resource')` でResource接尾辞を検証する
5. WHEN `tests/Architecture/QualityTest.php` にコード品質テストが存在する AND テストが実行される THEN テストシステム SHALL `arch('no debugging functions in production code')` でデバッグ関数（dd, dump, var_dump, print_r, ray）の使用を禁止する
6. WHEN QualityTestが実行される THEN テストシステム SHALL `expect('App')->toUseStrictTypes()` でstrictモード宣言、`expect('App\ValueObjects')->toBeFinal()` でValueObjectsのfinalクラス宣言を検証する

### Requirement 7: 段階的移行ツールとドキュメントの整備

**Objective:** テストリード開発者として、PHPUnitからPestへの段階的移行を支援するツールとドキュメントを提供したい。これにより、開発チームが計画的に移行を進め、移行時のエラーや混乱を最小限に抑えることができる。

#### Acceptance Criteria

1. WHEN `backend/laravel-api/scripts/convert-phpunit-to-pest.sh` 変換スクリプトが存在する AND 開発者が `bash convert-phpunit-to-pest.sh tests/Feature/ExampleTest.php` を実行 THEN 変換システム SHALL PHPUnitテストファイルのバックアップを作成し、基本的なPest構文への変換（namespace削除、class定義削除、test_メソッドをit()に変換）を実行する
2. WHEN 変換スクリプトが実行される THEN 変換システム SHALL 変換結果を表示し、`./vendor/bin/pest <file>` で検証する手順を案内する
3. WHEN `backend/laravel-api/docs/PEST_MIGRATION_CHECKLIST.md` 移行チェックリストが存在する THEN ドキュメントシステム SHALL Phase 1（準備）、Phase 2（新規テストでPest採用）、Phase 3（既存テスト移行）、Phase 4（完全移行）の4段階チェックリストを提供する
4. WHEN `backend/laravel-api/docs/PEST4_NEW_FEATURES.md` 新機能ガイドが存在する THEN ドキュメントシステム SHALL Pest 4の Browser Testing、Test Sharding、Visual Testing機能の概要と将来的な活用方法を説明する
5. WHEN `backend/laravel-api/docs/PEST_TROUBLESHOOTING.md` トラブルシューティングガイドが存在する THEN ドキュメントシステム SHALL よくあるエラー、マイグレーション時の注意点、デバッグ方法、パフォーマンス最適化を説明する
6. WHEN `backend/laravel-api/docs/PEST_CODING_STANDARDS.md` コーディング規約が存在する THEN ドキュメントシステム SHALL テスト命名規則、ファイル構成ルール、カスタムExpectation作成方法、データセット使い方を説明する

### Requirement 8: CI/CD設定の更新とTest Sharding対応

**Objective:** DevOps担当開発者として、GitHub ActionsのCI/CDパイプラインをPest 4に対応させ、Test Sharding（4並列）による高速テスト実行を実現したい。これにより、Pull Request時のテストフィードバック時間を短縮し、開発効率を向上させる。

#### Acceptance Criteria

1. WHEN `.github/workflows/test.yml` にPest対応ワークフローが存在する AND Pull Requestが作成される THEN CI/CDシステム SHALL test-suite: [pest, phpunit, integration] と shard: [1, 2, 3, 4] のマトリックス戦略でテストを並列実行する
2. WHEN CI/CDでtest-suite=pestが実行される THEN CI/CDシステム SHALL `./vendor/bin/pest --shard=${{ matrix.shard }}/4` でPestテストを4並列で実行する
3. WHEN CI/CDでtest-suite=phpunitが実行される THEN CI/CDシステム SHALL `composer test-phpunit` で既存PHPUnitテストを実行する（移行期間中のみ）
4. WHEN CI/CDでtest-suite=integrationが実行される THEN CI/CDシステム SHALL `composer test-all` でPHPUnitとPestの両方を統合実行する
5. WHEN CI/CDでtest-suite=pest AND shard=1が実行される THEN CI/CDシステム SHALL カバレッジレポート（coverage.xml）を生成し、Codecovにアップロードする
6. WHEN `.github/workflows/test.yml` にPHP 8.4環境設定が存在する THEN CI/CDシステム SHALL shivammathur/setup-php@v2でPHP 8.4と必要な拡張（dom, curl, libxml, mbstring, zip, pcntl, pdo, pdo_pgsql）とXdebugカバレッジを設定する

### Requirement 9: Composer Scripts統合とテストカバレッジ設定

**Objective:** テスト担当開発者として、Composer Scriptsで統一されたテスト実行コマンドを提供し、テストカバレッジ80%以上を維持する品質ゲートを設定したい。これにより、開発チームが一貫したコマンドでテストを実行し、品質基準を満たすことができる。

#### Acceptance Criteria

1. WHEN composer.jsonに `test` スクリプトが定義されている AND 開発者が `composer test` を実行 THEN テストシステム SHALL 設定をクリアし、デフォルトテストフレームワーク（Pest）を実行する
2. WHEN composer.jsonに `test-coverage` スクリプトが定義されている AND 開発者が `composer test-coverage` を実行 THEN テストシステム SHALL XDEBUG_MODE=coverageでカバレッジレポート（clover.xml, coverage-html/）を生成し、最小カバレッジ80%を検証する
3. WHEN composer.jsonに `test-parallel` スクリプトが定義されている AND 開発者が `composer test-parallel` を実行 THEN テストシステム SHALL `./vendor/bin/pest --parallel` で並列テスト実行を行う
4. WHEN composer.jsonに `test-shard` スクリプトが定義されている AND 開発者が `composer test-shard` を実行 THEN テストシステム SHALL `./vendor/bin/pest --shard=1/4` でシャーディングテスト実行を行う
5. WHEN テストカバレッジが80%未満の場合 THEN テストシステム SHALL `composer test-coverage` 実行時にエラーを返し、カバレッジ不足を報告する
6. WHEN pest.xmlにカバレッジ設定が存在する THEN テストシステム SHALL `app/` ディレクトリをカバレッジ対象とし、HTMLレポート（coverage-html/）とCloverレポート（coverage.xml）を出力する

### Requirement 10: 動作確認とテスト品質保証

**Objective:** QA担当開発者として、Pest移行後のテストシステムが正常に動作し、既存テストと新規Pestテストの両方が成功することを確認したい。これにより、移行がプロジェクトの品質を低下させないことを保証する。

#### Acceptance Criteria

1. WHEN 全Pest環境セットアップが完了した後 AND 開発者が `composer test-pest` を実行 THEN テストシステム SHALL 全PestサンプルテストがPASSし、エラーなく完了する
2. WHEN 既存PHPUnitテストが存在する AND 開発者が `composer test-phpunit` を実行 THEN テストシステム SHALL 全既存テストがPASSし、既存機能が正常動作していることを確認する
3. WHEN 両フレームワークのテストが存在する AND 開発者が `composer test-all` を実行 THEN テストシステム SHALL PHPUnitとPestの両方を順次実行し、全テストがPASSすることを確認する
4. WHEN 開発者が `composer test-coverage` を実行 THEN テストシステム SHALL カバレッジレポートを生成し、カバレッジ80%以上を達成していることを確認する
5. WHEN 開発者が `composer test-parallel` を実行 THEN テストシステム SHALL 並列実行で全テストがPASSし、実行時間が短縮されていることを確認する
6. WHEN GitHub Actionsで全CI/CDパイプラインが実行される THEN CI/CDシステム SHALL pest/phpunit/integrationマトリックスと4並列shardingで全テストがPASSし、カバレッジレポートが正常にアップロードされることを確認する

## Extracted Information（参考情報）

### Technology Stack

**Backend**: PHP 8.4, Laravel 12.0, PHPUnit 11.5
**Testing**: Pest 4, pestphp/pest-plugin-laravel 4.0, pestphp/pest-plugin-arch 4.0, nunomaduro/collision 8.0
**Infrastructure**: Laravel Sail, Docker
**Tools**: Composer, GitHub Actions, Xdebug

### Project Structure

```
backend/laravel-api/tests/Pest.php
backend/laravel-api/pest.xml
backend/laravel-api/tests/Feature/Api/AuthenticationTest.php
backend/laravel-api/tests/Feature/Api/CorsTest.php
backend/laravel-api/tests/Feature/Api/JsonApiTest.php
backend/laravel-api/tests/Architecture/LayerTest.php
backend/laravel-api/tests/Architecture/NamingTest.php
backend/laravel-api/tests/Architecture/QualityTest.php
backend/laravel-api/scripts/convert-phpunit-to-pest.sh
backend/laravel-api/docs/PEST_MIGRATION_CHECKLIST.md
backend/laravel-api/docs/PEST4_NEW_FEATURES.md
backend/laravel-api/docs/PEST_TROUBLESHOOTING.md
backend/laravel-api/docs/PEST_CODING_STANDARDS.md
.github/workflows/test.yml
```

### Scope

**対象範囲**:
- Pest 4のインストールと基本設定
- `tests/Pest.php` の作成（Laravel統合設定）
- API専用テストサンプルの作成（Sanctum認証、CORS、JSONレスポンス検証）
- アーキテクチャテストのサンプル作成
- テストカバレッジ設定と品質ゲート
- 開発者向けドキュメント作成
- CI/CD設定の更新（段階的移行対応）

**対象外（将来対応）**:
- 既存PHPUnitテストの一括自動変換（段階的に手動移行）
- Browser Testing（Playwright統合）- Pest 4機能として別Issue検討
- Visual Regression Testing - Pest 4機能として別Issue検討
- パフォーマンステストフレームワーク（別途検討）
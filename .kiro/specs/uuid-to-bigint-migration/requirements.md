# Requirements Document

## GitHub Issue Information

**Issue**: [#100](https://github.com/ef-tech/laravel-next-b2c/issues/100) - 主キーの uuid を bigint に戻す
**Labels**: なし
**Milestone**: なし
**Assignees**: なし

---

## Introduction

本要件は、Laravel APIプロジェクトにおける主キーのデータ型をUUIDからbigint（Laravel標準の`$table->id()`）に変更するものです。

### ビジネス価値
- **Laravel標準構成への準拠**: フレームワークの標準パターンに従うことでコード保守性が向上
- **パフォーマンス最適化**: bigint主キーはUUIDと比較してインデックスサイズが小さく、検索・結合操作が高速化
- **開発効率向上**: Factory/Seederでの自動ID生成がシンプル化し、テストデータ作成が容易
- **PostgreSQL最適化**: bigint SERIAL型はPostgreSQLの推奨パターンで、データベースネイティブの最適化が活用可能

### スコープ
本プロジェクトは開発初期段階であるため、通常の「マイグレーション追加（append）」ルールではなく、**既存マイグレーションファイルの直接編集**で対応します。

**対象テーブル**:
- `users` テーブル（主キー`id`）
- `personal_access_tokens` テーブル（ポリモーフィック外部キー`tokenable_id`）
- `sessions` テーブル（外部キー`user_id`）

**対象ファイルカテゴリ**:
- マイグレーションファイル（2ファイル）
- Eloquentモデル（1ファイル）
- Factory/Seeder（複数ファイル）
- テストファイル（複数ファイル）

---

## Requirements

### Requirement 1: マイグレーションファイルの主キー型変更
**Objective:** 開発者として、既存マイグレーションファイルを編集してUUID主キーをbigint主キーに変更したい。これによりLaravel標準構成に準拠し、データベースパフォーマンスが最適化される。

#### Acceptance Criteria

1. WHEN `0001_01_01_000000_create_users_table.php`マイグレーションファイルを編集するとき THEN Laravel Migrationシステム は `users`テーブルの主キー`id`カラムを`$table->uuid('id')->primary()`から`$table->id()`に変更しなければならない

2. WHEN `0001_01_01_000000_create_users_table.php`マイグレーションファイルを編集するとき THEN Laravel Migrationシステム は `sessions`テーブルの外部キー`user_id`カラムを`$table->foreignUuid('user_id')`から`$table->foreignId('user_id')`に変更しなければならない

3. WHEN `2025_09_29_083259_create_personal_access_tokens_table.php`マイグレーションファイルを編集するとき THEN Laravel Migrationシステム は ポリモーフィック外部キー`tokenable`を`$table->uuidMorphs('tokenable')`から`$table->morphs('tokenable')`に変更しなければならない

4. WHEN マイグレーションファイルを編集するとき THEN Laravel Migrationシステム は 変更理由を明記するコメントを追加しなければならない

5. WHEN `php artisan migrate:fresh`を実行するとき THEN Laravel Migrationシステム は エラーなく全マイグレーションを実行しなければならない

6. WHEN マイグレーション実行後にテーブル構造を確認するとき THEN PostgreSQLデータベース は `users.id`カラムが`bigint UNSIGNED`型かつ`auto_increment`であることを保証しなければならない

7. WHEN マイグレーション実行後にテーブル構造を確認するとき THEN PostgreSQLデータベース は `personal_access_tokens.tokenable_id`カラムが`bigint UNSIGNED`型であることを保証しなければならない

8. WHEN マイグレーション実行後にテーブル構造を確認するとき THEN PostgreSQLデータベース は `sessions.user_id`カラムが`bigint UNSIGNED`型であることを保証しなければならない

### Requirement 2: Eloquentモデル設定の修正
**Objective:** 開発者として、Userモデルの主キー設定をbigint自動インクリメント用に変更したい。これによりEloquent ORMがLaravel標準の整数主キーを正しく扱える。

#### Acceptance Criteria

1. WHEN `app/Models/User.php`を編集するとき THEN Userモデル は `public $incrementing = false;`プロパティを削除しなければならない（デフォルト値`true`を使用）

2. WHEN `app/Models/User.php`を編集するとき THEN Userモデル は `protected $keyType = 'string';`プロパティを削除しなければならない（デフォルト値`'int'`を使用）

3. WHEN `app/Models/User.php`を編集するとき THEN Userモデル は UUID関連のコメントを削除しなければならない

4. WHEN Userモデルを使用してレコードを作成するとき THEN Eloquent ORM は 自動インクリメント整数IDを正しく生成しなければならない

5. WHEN Userモデルを使用してレコードを取得するとき THEN Eloquent ORM は `id`属性を整数型として返さなければならない

### Requirement 3: Factory/Seederの修正
**Objective:** 開発者として、Factory/SeederのUUID生成ロジックを削除して自動インクリメントID生成に切り替えたい。これによりテストデータ生成がシンプル化される。

#### Acceptance Criteria

1. WHEN `database/factories/UserFactory.php`を確認するとき IF UUID生成ロジック（`'id' => Str::uuid()`）が存在する THEN UserFactory は 該当コードを削除しなければならない

2. WHEN `database/factories/UserFactory.php`の`definition()`メソッドを確認するとき THEN UserFactory は `id`フィールドを含まない（自動生成に委ねる）配列を返さなければならない

3. WHEN `database/seeders/`配下の全Seederファイルを確認するとき IF UUID指定が存在する THEN 各Seederクラス は UUID指定コードを削除しなければならない

4. WHEN `User::factory()->create()`を実行するとき THEN Laravelテストシステム は エラーなくユーザーを生成し、整数型IDを割り当てなければならない

5. WHEN `php artisan db:seed`を実行するとき THEN Laravelシーダーシステム は エラーなく全Seederを実行しなければならない

### Requirement 4: テストファイルの修正
**Objective:** 開発者として、UUID前提のテストケースをbigint主キー用に修正したい。これによりテストスイートが新しい主キー型で正常に動作する。

#### Acceptance Criteria

1. WHEN `tests/`ディレクトリ配下のPHPファイルを検索するとき THEN テスト修正プロセス は UUID関連コード（`Str::uuid()`, `toBeString()`等）を全て検出しなければならない

2. WHEN UUID前提のテストケースを修正するとき THEN 各テストケース は `expect($user->id)->toBeString()`を`expect($user->id)->toBeInt()`に変更しなければならない

3. WHEN UUID生成コードがテストケースに存在するとき THEN 各テストケース は `User::factory()->create(['id' => Str::uuid()])`を`User::factory()->create()`に変更しなければならない

4. WHEN Feature Testsを修正するとき THEN テストスイート は 整数型IDを前提としたアサーションに変更しなければならない

5. WHEN Unit Testsを修正するとき THEN テストスイート は 整数型IDを前提としたアサーションに変更しなければならない

6. WHEN Architecture Testsを確認するとき THEN テストスイート は DDD層の依存関係に影響がないことを保証しなければならない

7. WHEN `./vendor/bin/pest`を実行するとき THEN Pestテストフレームワーク は 全テストケースが成功しなければならない

8. WHEN テストカバレッジを測定するとき THEN Pestテストフレームワーク は 85%以上のカバレッジを維持しなければならない

### Requirement 5: データベース再構築と検証
**Objective:** 開発者として、既存データベースを完全に再構築して新しいマイグレーションを検証したい。これにより主キー型変更が正しく適用されていることを確認できる。

#### Acceptance Criteria

1. WHEN `php artisan migrate:fresh --seed`を実行するとき THEN Laravelマイグレーションシステム は エラーなく全マイグレーションとSeederを実行しなければならない

2. WHEN PostgreSQLデータベースでテーブル構造を確認するとき THEN データベース管理システム は `Schema::getColumnType('users', 'id')`が`"bigint"`を返すことを保証しなければならない

3. WHEN PostgreSQLデータベースで最初のユーザーレコードを取得するとき THEN Eloquent ORM は `User::first()->id`が整数値（例: `1`）を返すことを保証しなければならない

4. WHEN `SELECT id FROM users LIMIT 5;`クエリを実行するとき THEN PostgreSQLデータベース は 整数値のID列（1, 2, 3, ...）を返さなければならない

5. WHEN Laravel Sanctumトークンを発行するとき THEN 認証システム は エラーなくトークンを生成し、bigint型`tokenable_id`で正しく関連付けなければならない

### Requirement 6: テストスイート全体の実行と品質保証
**Objective:** 開発者として、全テストスイートを実行して主キー型変更による影響がないことを確認したい。これによりリグレッションを防止し、本番環境デプロイの安全性を保証する。

#### Acceptance Criteria

1. WHEN `make test-all`を実行するとき THEN テスト実行システム は SQLite環境で全テストが成功しなければならない

2. WHEN `make test-pgsql`を実行するとき THEN テスト実行システム は PostgreSQL環境（本番同等）で全テストが成功しなければならない

3. WHEN `make test-parallel`を実行するとき THEN テスト実行システム は 並列実行（4 Shard）で全テストが成功しなければならない

4. WHEN `make test-e2e-only`を実行するとき THEN E2Eテストシステム は 全E2Eテストが成功しなければならない

5. WHEN CI/CDパイプライン（GitHub Actions）を実行するとき THEN 自動化システム は 全ワークフローが成功しなければならない

6. WHEN テストカバレッジレポートを生成するとき THEN Pestテストフレームワーク は 85%以上のカバレッジを維持しなければならない

### Requirement 7: コード品質チェック
**Objective:** 開発者として、コード品質チェックツールを実行して全変更がプロジェクト品質基準を満たすことを確認したい。これによりコードスタイルと静的解析の品質を保証する。

#### Acceptance Criteria

1. WHEN `composer pint`を実行するとき THEN Laravel Pint は 全PHPファイルがコーディング規約に準拠していることを保証しなければならない

2. WHEN `composer stan`を実行するとき THEN Larastan は PHPStan Level 8静的解析で全エラーがゼロであることを保証しなければならない

3. WHEN `composer quality`を実行するとき THEN 品質統合チェックシステム は Laravel Pint + Larastan両方のチェックが成功しなければならない

4. WHEN Pre-commitフック（`.husky/pre-commit`）が実行されるとき THEN Git Hooksシステム は 変更ファイルのlint-stagedチェックが成功しなければならない

5. WHEN Pre-pushフック（`.husky/pre-push`）が実行されるとき THEN Git Hooksシステム は `composer quality`チェックが成功しなければならない

### Requirement 8: API応答とエンドポイント動作確認
**Objective:** 開発者として、APIエンドポイントが整数型IDで正しく動作することを確認したい。これにより既存API契約が維持されつつ、内部実装がbigintに変更されていることを保証する。

#### Acceptance Criteria

1. WHEN `/api/register`エンドポイントにユーザー登録リクエストを送信するとき THEN Laravel API は JSONレスポンスで整数型`id`（例: `{"id": 1, "name": "Test", ...}`）を返さなければならない

2. WHEN `/api/me`エンドポイントに認証済みリクエストを送信するとき THEN Laravel API は JSONレスポンスで認証ユーザーの整数型`id`を返さなければならない

3. WHEN `/api/login`エンドポイントでトークンを取得するとき THEN Laravel Sanctum は エラーなくトークンを発行し、bigint型`tokenable_id`で関連付けなければならない

4. WHEN `/api/tokens`エンドポイントでトークン一覧を取得するとき THEN Laravel Sanctum は 整数型`tokenable_id`を含むトークン情報を返さなければならない

5. WHEN ユーザー削除時にセッションがクリアされるとき THEN Laravel認証システム は bigint型`user_id`外部キーで正しくカスケード削除を実行しなければならない

### Requirement 9: ドキュメントとADR（オプション）
**Objective:** 開発者として、主キー型変更の意思決定記録を残したい。これにより将来の開発者が変更理由を理解し、アーキテクチャ判断の一貫性を保てる。

#### Acceptance Criteria

1. WHEN README.mdを確認するとき IF UUID主キーへの言及が存在する THEN ドキュメント は bigint主キーに関する記述に更新しなければならない

2. WHEN Architecture Decision Record（ADR）を作成するとき THEN ドキュメントシステム は 以下の情報を含むADRファイルを生成しなければならない:
   - 変更の背景（UUIDからbigintへの移行理由）
   - 意思決定の根拠（Laravel標準準拠、パフォーマンス最適化、開発効率）
   - 影響範囲（マイグレーション、モデル、Factory、テスト）
   - 代替案の検討（UUIDのまま維持する選択肢の評価）
   - 結論（bigint採用の最終判断）

3. WHEN マイグレーションファイルにコメントを追加するとき THEN コメント は 変更理由と影響範囲を明確に記述しなければならない

---

## Technology Stack
**Backend**: Laravel 12, PHP 8.4, PostgreSQL 17, Eloquent ORM
**Frontend**: N/A (バックエンドのみの変更)
**Infrastructure**: Docker Compose, Laravel Sail
**Tools**: Pest 4, Laravel Pint, Larastan/PHPStan Level 8, Factory/Seeder

---

## Project Structure
```
backend/laravel-api/database/migrations/0001_01_01_000000_create_users_table.php
backend/laravel-api/database/migrations/2025_09_29_083259_create_personal_access_tokens_table.php
backend/laravel-api/app/Models/User.php
backend/laravel-api/database/factories/UserFactory.php
backend/laravel-api/database/seeders/**
backend/laravel-api/tests/**/*.php
```

---

## Scope Boundaries

### In-Scope（対象範囲）
- ✅ 既存マイグレーションファイルの直接編集
- ✅ Userモデルの主キー設定修正
- ✅ Factory/SeederのUUID生成ロジック削除
- ✅ テストファイルのUUID前提コード修正
- ✅ データベース再構築と検証
- ✅ 全テストスイート実行（SQLite、PostgreSQL、E2E）
- ✅ コード品質チェック（Pint、Larastan）
- ✅ CI/CDパイプライン成功確認
- ✅ API応答検証（整数型ID）
- ✅ ドキュメント更新（README.md、ADR作成）

### Out-of-Scope（対象外）
- ❌ 新規マイグレーションファイルの追加（既存ファイル編集で対応）
- ❌ 既存データの移行処理（開発初期段階のため既存データなし）
- ❌ API契約変更（エンドポイントURLやリクエスト形式の変更なし）
- ❌ フロントエンド側の変更（JSONレスポンスのID型変更に対する影響はあるが、本要件の対象外）
- ❌ 他のテーブルの主キー変更（usersテーブル関連のみ対象）

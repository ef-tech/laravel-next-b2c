# Requirements Document

## GitHub Issue Information

**Issue**: [#100](https://github.com/ef-tech/laravel-next-b2c/issues/100) - 主キーの uuid を bigint に戻す
**Labels**: refactoring, backend
**Milestone**: -
**Assignees**: -

### Original Issue Description

# 主キーのuuidをbigintに戻す

## Background & Objectives

### 背景
プロジェクト開発初期段階において、`users`テーブルの主キーがUUID型で実装されていますが、以下の理由によりbigint型（Laravel標準の`id()`メソッド）への変更が必要です:

- **Laravel標準構成への準拠**: `$table->id()`は自動インクリメントbigintの標準パターン
- **パフォーマンス最適化**: bigint主キーはUUIDと比較してインデックスサイズが小さく、検索・結合操作が高速
- **開発効率**: Factory/Seederでの自動ID生成がシンプル
- **PostgreSQL最適化**: bigint SERIAL型は PostgreSQL の推奨パターン

### 目的
既存マイグレーションファイルを直接編集し、UUID主キーをbigint主キーに変更する。

**重要**: 本プロジェクトは開発初期段階のため、通常の「マイグレーション追加（append）」ルールではなく、**既存マイグレーションファイルの直接編集**で対応します。

---

## Category

**主カテゴリ**: Code + DB

**詳細分類**:
- **DB Migration** (40%): 既存マイグレーションファイル編集（users, personal_access_tokens, sessions）
- **Code** (50%): Eloquentモデル設定、Factory、Seeder修正
- **Test** (10%): テストケース修正、検証

---

## Scope

### 対象ファイル（In-Scope）

#### 1. マイグレーションファイル
- ✅ `database/migrations/0001_01_01_000000_create_users_table.php`
  - `$table->uuid('id')->primary()` → `$table->id()`
  - `$table->foreignUuid('user_id')` → `$table->foreignId('user_id')`

- ✅ `database/migrations/2025_09_29_083259_create_personal_access_tokens_table.php`
  - `$table->uuidMorphs('tokenable')` → `$table->morphs('tokenable')`

#### 2. Eloquentモデル
- ✅ `app/Models/User.php`
  - `public $incrementing = false;` → 削除（デフォルト値true）
  - `protected $keyType = 'string';` → 削除（デフォルト値'int'）

#### 3. Factory/Seeder
- ✅ `database/factories/UserFactory.php`: UUID生成ロジック削除
- ✅ `database/seeders/**`: UUID指定削除

#### 4. テストファイル
- ✅ `tests/**/*.php`: UUID前提のテストケース修正

### 非対象（Out-of-Scope）
- ❌ 新規マイグレーションファイルの追加（既存ファイル編集で対応）
- ❌ データ移行処理（開発初期段階のため不要）
- ❌ API契約変更（JSONレスポンスは整数値で返すが、エンドポイント構造は不変）

---

## Extracted Information

### Technology Stack
**Backend**: Laravel 12, PHP 8.4, Eloquent ORM
**Database**: PostgreSQL 17, bigint SERIAL
**Framework**: Laravel Sanctum (認証システム)
**Testing**: Pest 4, PHPUnit

### Project Structure
対象ファイル構造:
```
backend/laravel-api/
├── database/
│   ├── migrations/
│   │   ├── 0001_01_01_000000_create_users_table.php
│   │   └── 2025_09_29_083259_create_personal_access_tokens_table.php
│   ├── factories/
│   │   └── UserFactory.php
│   └── seeders/
│       └── **/*.php
├── app/
│   └── Models/
│       └── User.php
└── tests/
    ├── Feature/
    ├── Unit/
    └── Arch/
```

### Requirements Hints
Based on issue analysis:
- usersテーブルの主キーをUUID型からbigint型に変更
- Laravel標準構成（$table->id()メソッド）への準拠
- パフォーマンス最適化（bigintはUUIDより高速）
- Sanctum認証システムとの整合性確保
- 既存マイグレーションファイルの直接編集（開発初期段階）

### TODO Items from Issue
- [ ] マイグレーションファイル修正（users, personal_access_tokens, sessions）
- [ ] Eloquentモデル設定修正（User.php）
- [ ] Factory/Seeder修正（UUID生成ロジック削除）
- [ ] テストファイル修正（UUID前提コード削除）
- [ ] データベース再構築（migrate:fresh）
- [ ] 全テスト実行（Pest, SQLite, PostgreSQL, E2E）
- [ ] コード品質チェック（Pint, Larastan）
- [ ] API動作確認（Sanctum認証）

---

## Introduction

本要件は、Laravel Next.js B2Cアプリケーションテンプレートにおいて、`users`テーブルおよび関連テーブルの主キーをUUID型からbigint型に変更するためのリファクタリング作業を定義します。

### ビジネス価値

1. **パフォーマンス向上**: bigint主キーはUUID型と比較してインデックスサイズが小さく、検索・結合操作が高速化されます。
2. **Laravel標準準拠**: `$table->id()`メソッドによる自動インクリメントbigintは、Laravel標準パターンであり、フレームワークの最適化を最大限に活用できます。
3. **開発効率化**: Factory/Seederでの自動ID生成がシンプルになり、テストデータ作成が容易になります。
4. **PostgreSQL最適化**: bigint SERIAL型はPostgreSQLの推奨パターンであり、データベースエンジンの最適化を活用できます。

### 対象システム

- **Laravel API**: バックエンドAPI層（`backend/laravel-api/`）
- **データベース**: PostgreSQL 17
- **認証システム**: Laravel Sanctum 4.0

### 影響範囲

開発初期段階のため、既存データへの影響はありません。マイグレーションファイルの直接編集により、クリーンな状態で主キー型を変更します。

---

## Requirements

### Requirement 1: データベーススキーマ変更

**Objective:** プロジェクト管理者として、usersテーブルとその関連テーブルの主キーをUUID型からbigint型に変更することで、Laravel標準構成に準拠し、パフォーマンスを最適化したい。

#### Acceptance Criteria

1. WHEN `0001_01_01_000000_create_users_table.php` マイグレーションファイルが実行される THEN Laravel Migration System SHALL usersテーブルの主キー`id`をbigint UNSIGNED auto_increment型として作成する
2. WHEN `0001_01_01_000000_create_users_table.php` マイグレーションファイルが実行される THEN Laravel Migration System SHALL sessionsテーブルの外部キー`user_id`をbigint UNSIGNED型として作成する
3. WHEN `2025_09_29_083259_create_personal_access_tokens_table.php` マイグレーションファイルが実行される THEN Laravel Migration System SHALL personal_access_tokensテーブルのポリモーフィック外部キー`tokenable_id`をbigint UNSIGNED型として作成する
4. WHEN マイグレーション実行後にテーブル構造を確認する THEN PostgreSQL SHALL usersテーブルの`id`カラムをbigint型として定義する
5. WHEN マイグレーション実行後にテーブル構造を確認する THEN PostgreSQL SHALL sessionsテーブルの`user_id`カラムをbigint型として定義する
6. WHEN マイグレーション実行後にテーブル構造を確認する THEN PostgreSQL SHALL personal_access_tokensテーブルの`tokenable_id`カラムをbigint型として定義する

### Requirement 2: Eloquentモデル設定変更

**Objective:** バックエンド開発者として、Userモデルの主キー設定をLaravel標準のデフォルト値（自動インクリメント、整数型）に戻すことで、Eloquent ORMの最適化を活用したい。

#### Acceptance Criteria

1. WHEN `app/Models/User.php` ファイルが読み込まれる THEN User Model SHALL `$incrementing` プロパティを持たない（デフォルト値`true`を使用）
2. WHEN `app/Models/User.php` ファイルが読み込まれる THEN User Model SHALL `$keyType` プロパティを持たない（デフォルト値`'int'`を使用）
3. WHEN Eloquentモデルが主キーの型を判定する THEN User Model SHALL 主キーを整数型（int）として扱う
4. WHEN Eloquentモデルが新規レコードを作成する THEN User Model SHALL 主キーを自動インクリメントする

### Requirement 3: Factory/Seeder修正

**Objective:** テストエンジニアおよびバックエンド開発者として、Factory/SeederでのユーザーID生成をbigint自動インクリメントに対応させることで、テストデータ作成をシンプルにしたい。

#### Acceptance Criteria

1. WHEN `database/factories/UserFactory.php` の `definition()` メソッドが実行される THEN UserFactory SHALL `id` フィールドを返り値に含めない（自動生成に委ねる）
2. WHEN `User::factory()->create()` が実行される THEN Laravel Factory System SHALL 自動インクリメントされた整数型IDを持つUserレコードを作成する
3. IF Seederファイル内に明示的なUUID指定がある THEN Seeder SHALL UUID指定コードを削除し、自動ID生成を使用する
4. WHEN Seederが実行される THEN Laravel Seeder System SHALL 連番の整数型IDを持つUserレコードを作成する

### Requirement 4: テストケース修正

**Objective:** テストエンジニアとして、UUID型主キーを前提としたテストケースをbigint型に対応させることで、全テストが正常に動作するようにしたい。

#### Acceptance Criteria

1. WHEN テストケース内でユーザーIDの型をアサートする THEN Test Suite SHALL ユーザーIDを整数型（int）として検証する
2. WHEN テストケース内でユーザーを作成する THEN Test Suite SHALL UUID生成コードを使用せず、Factory/Seederの自動ID生成を使用する
3. IF テストケース内に `Str::uuid()` を使用したID生成がある THEN Test Suite SHALL そのコードを削除し、自動ID生成に変更する
4. IF テストケース内に `expect($user->id)->toBeString()` のようなアサーションがある THEN Test Suite SHALL `expect($user->id)->toBeInt()` に変更する
5. WHEN Pest 4テストスイートが実行される THEN Test Suite SHALL 全テストケースが成功する

### Requirement 5: データベース再構築

**Objective:** プロジェクト管理者として、既存データベースを完全に再構築することで、主キー型変更を確実に反映させたい。

#### Acceptance Criteria

1. WHEN `php artisan migrate:fresh` コマンドが実行される THEN Laravel Migration System SHALL 既存の全テーブルを削除する
2. WHEN `php artisan migrate:fresh` コマンドが実行される THEN Laravel Migration System SHALL 修正済みマイグレーションファイルを使用して全テーブルを再作成する
3. WHEN `php artisan migrate:fresh --seed` コマンドが実行される THEN Laravel Seeder System SHALL 整数型IDを持つテストデータを作成する
4. WHEN マイグレーション再実行後にデータベースを確認する THEN PostgreSQL SHALL usersテーブルに整数型主キーを持つレコードが存在する

### Requirement 6: 認証システム整合性確保

**Objective:** バックエンド開発者として、Laravel Sanctum認証システムがbigint型主キーで正常に動作することを確認したい。

#### Acceptance Criteria

1. WHEN ユーザーがログインAPIエンドポイント（`POST /api/login`）にアクセスする THEN Laravel Sanctum SHALL 整数型ユーザーIDに対してPersonal Access Tokenを発行する
2. WHEN ユーザーが認証保護されたAPIエンドポイント（`GET /api/me`）にアクセスする THEN Laravel Sanctum SHALL 整数型ユーザーIDを持つユーザー情報を返す
3. WHEN personal_access_tokensテーブルのtokenable_idカラムを確認する THEN PostgreSQL SHALL bigint型の整数値を格納している
4. WHEN APIレスポンスのユーザーIDを確認する THEN Laravel API SHALL JSON形式で整数型ユーザーID（例: `{"id": 1, "name": "Test User"}`）を返す

### Requirement 7: テストスイート実行

**Objective:** テストエンジニアとして、全テストスイート（ユニット、統合、E2E）が成功することで、主キー型変更が既存機能に悪影響を与えないことを確認したい。

#### Acceptance Criteria

1. WHEN `./vendor/bin/pest` コマンドが実行される THEN Pest Test Runner SHALL 全ユニットテストおよびFeatureテストを成功させる
2. WHEN `make test-all` コマンドが実行される THEN Test Orchestration System SHALL SQLite環境で全テストスイートを成功させる
3. WHEN `make test-pgsql` コマンドが実行される THEN Test Orchestration System SHALL PostgreSQL環境で全テストスイートを成功させる
4. WHEN `make test-e2e-only` コマンドが実行される THEN Playwright Test Runner SHALL E2Eテストを成功させる
5. WHEN Architecture Testsが実行される THEN Pest Architecture Testing SHALL DDD層依存方向の検証を成功させる
6. WHEN テストカバレッジレポートが生成される THEN Test Coverage System SHALL 85%以上のカバレッジを維持する

### Requirement 8: コード品質保証

**Objective:** プロジェクト管理者として、コードフォーマットと静的解析が成功することで、コード品質を保証したい。

#### Acceptance Criteria

1. WHEN `composer pint` コマンドが実行される THEN Laravel Pint SHALL 全PHPファイルをLaravel標準フォーマットに準拠させる
2. WHEN `composer stan` コマンドが実行される THEN Larastan SHALL PHPStan Level 8静的解析を成功させる
3. WHEN `composer quality` コマンドが実行される THEN Quality Check System SHALL Pint検証とLarastan解析の両方を成功させる
4. IF コード品質チェックで問題が検出される THEN Quality Check System SHALL 具体的なエラーメッセージと修正箇所を表示する

### Requirement 9: CI/CDパイプライン検証

**Objective:** DevOpsエンジニアとして、GitHub Actionsパイプラインが成功することで、主キー型変更がCI/CD環境で問題なく動作することを確認したい。

#### Acceptance Criteria

1. WHEN Pull Requestが作成される THEN GitHub Actions Workflow SHALL `php-quality.yml` ワークフローを実行し、成功させる
2. WHEN Pull Requestが作成される THEN GitHub Actions Workflow SHALL `test.yml` ワークフローを実行し、全テストを成功させる
3. WHEN Pull Requestが作成される THEN GitHub Actions Workflow SHALL `frontend-test.yml` ワークフローを実行し、成功させる（API契約変更がないため）
4. WHEN Pull Requestが作成される THEN GitHub Actions Workflow SHALL `e2e-tests.yml` ワークフローを実行し、E2Eテストを成功させる
5. IF いずれかのワークフローが失敗する THEN GitHub Actions Workflow SHALL Pull Requestチェックを失敗ステータスにする

### Requirement 10: ドキュメント更新

**Objective:** プロジェクト管理者として、主キー型変更がドキュメントに反映されることで、将来の開発者が正しい情報を参照できるようにしたい。

#### Acceptance Criteria

1. IF プロジェクトドキュメント（README.md、steering documents等）にUUID主キーへの言及がある THEN Documentation System SHALL bigint主キーに関する記述に更新する
2. WHEN マイグレーションファイルにコメントが追加される THEN Documentation System SHALL UUID型からbigint型への変更理由を記載する
3. IF Architecture Decision Record（ADR）を作成する方針である THEN Documentation System SHALL 主キー型変更の決定記録を作成する（オプション）

---

## Non-Functional Requirements

### Performance Requirements

1. WHEN usersテーブルに対してSELECTクエリが実行される THEN Database System SHALL UUID型主キーと比較して検索速度が向上する
2. WHEN usersテーブルとpersonal_access_tokensテーブルが結合される THEN Database System SHALL UUID型外部キーと比較して結合操作が高速化される

### Maintainability Requirements

1. WHEN 新規開発者がマイグレーションファイルを確認する THEN Migration Files SHALL Laravel標準の`$table->id()`パターンを使用していることで理解が容易である
2. WHEN 新規開発者がFactoryファイルを確認する THEN Factory Files SHALL 明示的なID生成ロジックを持たず、Laravel標準の自動ID生成を使用していることで保守が容易である

### Compatibility Requirements

1. WHEN Laravel 12フレームワークがアップデートされる THEN Application System SHALL bigint主キーがLaravel標準パターンであるため、互換性が保証される
2. WHEN PostgreSQL 17データベースが運用される THEN Database System SHALL bigint SERIAL型がPostgreSQL推奨パターンであるため、最適化が活用される

---

## Constraints

### Technical Constraints

1. **開発初期段階対応**: 本プロジェクトは開発初期段階のため、既存データが存在しません。したがって、通常の「マイグレーション追加（append）」ルールではなく、**既存マイグレーションファイルの直接編集**で対応します。
2. **データ移行不要**: 開発初期段階のため、UUID型からbigint型へのデータ移行処理は不要です。
3. **API契約不変**: JSONレスポンスのID型は整数値に変わりますが、エンドポイント構造やレスポンス形式は不変です。

### Project Constraints

1. **テストカバレッジ維持**: 主キー型変更後も、テストカバレッジ85%以上を維持する必要があります。
2. **コード品質基準**: Laravel Pint、Larastan Level 8の品質基準をすべて満たす必要があります。
3. **CI/CD成功**: 全GitHub Actionsワークフローが成功する必要があります。

---

## Dependencies

### Internal Dependencies

- **Laravel 12 Framework**: Eloquent ORM、Migration System、Factory/Seeder機能
- **Laravel Sanctum 4.0**: Personal Access Tokens認証システム
- **Pest 4**: テストフレームワーク、Architecture Testing機能

### External Dependencies

- **PostgreSQL 17**: データベースエンジン、bigint SERIAL型サポート
- **GitHub Actions**: CI/CDパイプライン実行環境

---

## Acceptance Test Scenarios

### Scenario 1: マイグレーション成功

```bash
# Given: 既存データベースが存在する
# When: migrate:freshコマンドを実行する
php artisan migrate:fresh --seed

# Then: usersテーブルがbigint主キーで作成される
# Then: 整数型IDを持つテストデータが作成される
# Then: マイグレーションエラーが発生しない
```

### Scenario 2: Eloquentモデル動作確認

```bash
# Given: データベースが再構築されている
# When: Tinkerでユーザーを作成する
php artisan tinker
>>> $user = User::factory()->create()
>>> $user->id

# Then: 整数型ID（例: 1）が返される
# Then: UUID形式のID（例: "550e8400-e29b-41d4-a716-446655440000"）は返されない
```

### Scenario 3: Sanctum認証動作確認

```bash
# Given: ユーザーが作成されている
# When: ログインAPIを実行する
curl -X POST http://localhost:13000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"password"}'

# Then: 整数型ユーザーIDを含むレスポンスが返される
# Response: {"token": "...", "user": {"id": 1, "name": "Test User", ...}}
```

### Scenario 4: テストスイート成功

```bash
# Given: 全コード修正が完了している
# When: 全テストスイートを実行する
make test-all

# Then: Pestテストが成功する
# Then: フロントエンドテストが成功する
# Then: E2Eテストが成功する
# Then: テストカバレッジが85%以上である
```

### Scenario 5: コード品質チェック成功

```bash
# Given: 全コード修正が完了している
# When: コード品質チェックを実行する
composer quality

# Then: Laravel Pintチェックが成功する
# Then: Larastan Level 8解析が成功する
# Then: 品質エラーが0件である
```

---

## Success Metrics

### Quantitative Metrics

1. **テスト成功率**: 100%（全テストケースが成功）
2. **テストカバレッジ**: 85%以上を維持
3. **マイグレーション実行時間**: migrate:fresh完了時間が10秒以内
4. **コード品質エラー**: 0件（Pint、Larastan）

### Qualitative Metrics

1. **コード可読性**: Laravel標準パターン準拠により、新規開発者の理解が容易
2. **保守性**: 明示的なID生成ロジック削除により、Factory/Seederの保守が容易
3. **パフォーマンス**: bigint主キーによる検索・結合操作の高速化
4. **互換性**: Laravel標準構成準拠により、フレームワークアップデート時の互換性保証

---

## Risk Assessment

### High Risk

- **マイグレーション失敗**: 構文エラーによるマイグレーション失敗のリスク
  - **軽減策**: 開発環境で事前検証、migrate:fresh実行、テストスイート実行

### Medium Risk

- **テスト失敗**: UUID前提のテストケース修正漏れ
  - **軽減策**: UUID前提コード検索（`grep -r "uuid\|Str::uuid\|toBeString.*id" tests/`）、全テストスイート実行

### Low Risk

- **既存データ損失**: 開発初期段階のため影響最小
  - **軽減策**: 開発初期段階対応のため、データ移行不要

---

## Appendix

### Related Documentation

- **Laravel Migrations**: https://laravel.com/docs/12.x/migrations#available-column-types
- **Eloquent Primary Keys**: https://laravel.com/docs/12.x/eloquent#primary-keys
- **Laravel Sanctum**: https://laravel.com/docs/12.x/sanctum
- **Pest Testing Framework**: https://pestphp.com/

### Glossary

- **bigint**: PostgreSQLの64ビット整数型（-9223372036854775808 ～ 9223372036854775807）
- **UUID**: 128ビットの一意識別子（例: 550e8400-e29b-41d4-a716-446655440000）
- **SERIAL**: PostgreSQLの自動インクリメント型（内部的にはシーケンスを使用）
- **Eloquent ORM**: LaravelのActive Recordパターン実装
- **Pest**: PHP向けモダンテストフレームワーク
- **Architecture Tests**: Pestによる依存方向とレイヤー分離の自動検証機能

# Implementation Plan

## 概要

本実装計画は、usersテーブルおよび関連テーブルの主キーをUUID型からbigint型に変更するためのリファクタリングタスクを定義します。開発初期段階のため、既存マイグレーションファイルの直接編集により実現し、DDD層、Factory/Seeder、テストケースを含む全関連コードを更新します。

**実装目標**:
- Laravel標準構成（`$table->id()`）への準拠
- パフォーマンス最適化（検索・結合操作の高速化）
- 開発効率向上（Factory/Seederのシンプル化）
- 全テストスイート成功維持（85%以上カバレッジ）

---

## Implementation Tasks

- [ ] 1. マイグレーションファイルをbigint主キーに変更する
- [ ] 1.1 usersテーブルマイグレーションをbigint主キーに更新する
  - `0001_01_01_000000_create_users_table.php`でusersテーブル主キーをUUID型からbigint型に変更
  - `$table->uuid('id')->primary()`を`$table->id()`に変更
  - sessionsテーブル外部キー`user_id`をUUID型からbigint型に変更
  - `$table->foreignUuid('user_id')`を`$table->foreignId('user_id')`に変更
  - マイグレーションファイルにUUID→bigint変更理由のコメントを追加
  - _Requirements: 1.1, 1.2, 1.4, 1.5_

- [ ] 1.2 personal_access_tokensテーブルマイグレーションをbigint外部キーに更新する
  - `2025_09_29_083259_create_personal_access_tokens_table.php`でポリモーフィック外部キーをUUID型からbigint型に変更
  - `$table->uuidMorphs('tokenable')`を`$table->morphs('tokenable')`に変更
  - マイグレーションファイルに変更理由のコメントを追加
  - _Requirements: 1.3, 1.6_

- [ ] 2. EloquentモデルをLaravel標準デフォルト値に戻す
- [ ] 2.1 Userモデルの主キー設定をLaravel標準に変更する
  - `app/Models/User.php`から`public $incrementing = false`プロパティを削除
  - `app/Models/User.php`から`protected $keyType = 'string'`プロパティを削除
  - Laravel標準デフォルト値（`$incrementing = true`, `$keyType = 'int'`）を活用
  - Eloquent ORMが主キーを整数型として扱い、自動インクリメントすることを確認
  - _Requirements: 2.1, 2.2, 2.3, 2.4_

- [ ] 3. DDD層のUserId Value Objectを整数型に対応させる
- [ ] 3.1 UserId Value Objectを整数型に変更する
  - `ddd/Domain/User/ValueObjects/UserId.php`の内部型を`string`から`int`に変更
  - コンストラクタのバリデーションロジックをUUID v4正規表現検証から整数型範囲検証（`$value > 0`）に変更
  - `fromString(string)`メソッドを削除し、`fromInt(int)`メソッドに置き換え
  - `value()`メソッドの戻り値型を`string`から`int`に変更
  - 不変性（readonly）を維持
  - _Requirements: DDD層UserId Value Object対応_

- [ ] 3.2 UserIdTestをbigint型に対応させる
  - `tests/Unit/Ddd/Domain/User/ValueObjects/UserIdTest.php`でUUID型前提のテストケースを整数型に変更
  - `fromString()`呼び出しを`fromInt()`に変更
  - UUID型検証テストを整数型検証テストに変更（`$value > 0`）
  - 無効な整数ID（0、負の値）のエラーテストを追加
  - 等価性テスト（`equals()`）を整数型IDで実行
  - _Requirements: 4.1, 4.2, 4.4_

- [ ] 4. DDD層のEloquentUserRepositoryを自動インクリメントに対応させる
- [ ] 4.1 EloquentUserRepository nextIdメソッドを削除する
  - `ddd/Infrastructure/Persistence/Eloquent/Repositories/EloquentUserRepository.php`から`nextId(): UserId`メソッドを削除
  - `save(User $user)`メソッドで保存後に自動生成されたIDを`User` Entityに設定するロジックを追加
  - データベース自動インクリメントに責務を委譲
  - _Requirements: DDD層Repository nextId対応_

- [ ] 4.2 User Entityのregisterメソッドシグネチャを変更する
  - `ddd/Domain/User/Entities/User.php`の`User::register()`メソッドから`UserId`引数を削除
  - ID生成責務をデータベースに委譲（保存後にIDを設定）
  - 既存のビジネスロジック（Email、Nameバリデーション）を維持
  - _Requirements: DDD層Entity対応_

- [ ] 4.3 EloquentUserRepositoryTestをbigint型に対応させる
  - `tests/Feature/Ddd/Infrastructure/Persistence/Eloquent/Repositories/EloquentUserRepositoryTest.php`でUUID型前提のテストケースを整数型に変更
  - `save()`実行後の自動生成整数型ID確認テストを追加
  - `find()`メソッドが整数型IDで正常動作することを確認
  - `nextId()`メソッド呼び出しを削除
  - _Requirements: 4.1, 4.2_

- [ ] 5. Factory/Seederのユーザー生成をbigint自動インクリメントに対応させる
- [ ] 5.1 UserFactoryのUUID生成ロジックを削除する
  - `database/factories/UserFactory.php`の`definition()`メソッドから`'id' => Str::uuid()->toString()`を削除
  - Laravel標準の自動インクリメントID生成に委譲
  - Factory実行時に整数型ID（1, 2, 3...）が自動生成されることを確認
  - _Requirements: 3.1, 3.2_

- [ ] 5.2 SeederのUUID指定を削除する
  - `database/seeders/DatabaseSeeder.php`内に明示的なUUID指定がないことを確認
  - Seeder実行時に連番の整数型IDが作成されることを確認
  - Factory自動ID生成を使用
  - _Requirements: 3.3, 3.4_

- [ ] 5.3 UserFactoryTestをbigint型に対応させる
  - `tests/Feature/Factories/UserFactoryTest.php`でFactory自動ID生成テストを追加
  - `User::factory()->create()`実行後に整数型IDが生成されることを確認
  - 複数ユーザー作成時に連番ID（1, 2, 3）が生成されることを確認
  - _Requirements: 4.1, 4.2_

- [ ] 6. テストケースをbigint型に対応させる
- [ ] 6.1 UUID前提コードをテストケースから削除する
  - 全テストファイルでUUID前提コード検索（`grep -r "uuid\|Str::uuid\|toBeString.*id" tests/`）を実行
  - `Str::uuid()`を使用したID生成コードを削除
  - UUID形式のアサーション（例: 正規表現マッチ）を削除
  - 整数型IDアサーションに変更
  - _Requirements: 4.3_

- [ ] 6.2 テストケースのIDアサーションを整数型に変更する
  - `expect($user->id)->toBeString()`を`expect($user->id)->toBeInt()`に変更
  - `expect($user->id)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/')`を削除
  - 整数型ID範囲検証（`expect($user->id)->toBeGreaterThan(0)`）を追加
  - _Requirements: 4.4_

- [ ] 7. Sanctum認証システムをbigint型に対応させる
- [ ] 7.1 Sanctumログイン機能が整数型IDで動作することを確認する
  - `tests/Feature/Auth/LoginTest.php`でログインAPIレスポンスのユーザーIDが整数型であることを確認
  - `POST /api/login`実行後のレスポンスJSONで`{"user": {"id": 1, ...}}`形式を検証
  - Personal Access Tokenが整数型ユーザーIDに対して発行されることを確認
  - _Requirements: 6.1, 6.4_

- [ ] 7.2 Sanctum認証保護エンドポイントが整数型IDで動作することを確認する
  - `GET /api/me`エンドポイントが整数型ユーザーIDを返すことを確認
  - personal_access_tokensテーブルのtokenable_idカラムがbigint型整数値を格納していることを確認
  - トークン管理API（`GET /api/tokens`, `POST /api/tokens/{id}/revoke`）が整数型IDで正常動作することを確認
  - _Requirements: 6.2, 6.3_

- [ ] 8. データベースをbigint主キーで再構築する
- [ ] 8.1 マイグレーション再実行でbigint主キーを作成する
  - `php artisan migrate:fresh --seed`コマンドを実行
  - usersテーブル主キー`id`がbigint UNSIGNED auto_increment型で作成されることを確認
  - personal_access_tokensテーブル`tokenable_id`がbigint UNSIGNED型で作成されることを確認
  - sessionsテーブル外部キー`user_id`がbigint UNSIGNED型で作成されることを確認
  - _Requirements: 5.1, 5.2_

- [ ] 8.2 Seeder実行で整数型IDテストデータを作成する
  - `php artisan migrate:fresh --seed`実行後に整数型IDを持つテストデータが作成されることを確認
  - usersテーブルに連番ID（1, 2, 3...）を持つレコードが存在することを確認
  - Factory自動ID生成が正常動作することを確認
  - _Requirements: 5.3, 5.4_

- [ ] 9. 全テストスイートを実行して成功を確認する
- [ ] 9.1 ユニットテストおよびFeatureテストを実行する
  - `./vendor/bin/pest`コマンドで全ユニットテストおよびFeatureテストを実行
  - UserId Value Object、User Entity、Repository実装のテストが成功することを確認
  - Eloquentモデル、Factory、Seederのテストが成功することを確認
  - _Requirements: 7.1_

- [ ] 9.2 SQLite環境で全テストスイートを実行する
  - `make test-all`コマンドでSQLite環境の全テストスイートを実行
  - バックエンドテスト、フロントエンドテスト、E2Eテストが成功することを確認
  - 実行時間が30秒以内であることを確認（高速環境）
  - _Requirements: 7.2_

- [ ] 9.3 PostgreSQL環境で全テストスイートを実行する
  - `make test-pgsql`コマンドでPostgreSQL環境の全テストスイートを実行
  - 本番環境同等のデータベースエンジンでテストが成功することを確認
  - bigint SERIAL型が正常動作することを確認
  - _Requirements: 7.3_

- [ ] 9.4 E2Eテストを実行する
  - `make test-e2e-only`コマンドでPlaywright E2Eテストを実行
  - API統合テスト（Sanctum認証）が成功することを確認
  - レスポンスID型が整数型であることを確認
  - _Requirements: 7.4_

- [ ] 9.5 Architecture Testsを実行する
  - Pest Architecture Testingを実行してDDD層依存方向を検証
  - Domain層が他の層に依存していないことを確認
  - Infrastructure層がDomain層インターフェースを実装していることを確認
  - _Requirements: 7.5_

- [ ] 9.6 テストカバレッジを確認する
  - `./vendor/bin/pest --coverage --min=85`コマンドでカバレッジレポートを生成
  - テストカバレッジが85%以上であることを確認
  - Domain層100%、Application層98%、Infrastructure層94%のカバレッジを維持
  - _Requirements: 7.6_

- [ ] 10. コード品質チェックを実行する
- [ ] 10.1 Laravel Pintでコードフォーマットを検証する
  - `composer pint`コマンドでLaravel Pint自動フォーマットを実行
  - 全PHPファイルがLaravel標準フォーマットに準拠していることを確認
  - フォーマットエラーが0件であることを確認
  - _Requirements: 8.1_

- [ ] 10.2 LarastanでPHPStan Level 8静的解析を実行する
  - `composer stan`コマンドでLarastan Level 8静的解析を実行
  - 型エラー、未定義メソッド呼び出し、不正な引数型などのエラーが0件であることを確認
  - DDD層、Eloquentモデル、Repository実装の型安全性を確認
  - _Requirements: 8.2_

- [ ] 10.3 統合品質チェックを実行する
  - `composer quality`コマンドでPint検証とLarastan解析を統合実行
  - 品質チェックが成功し、エラーが0件であることを確認
  - コード品質基準を満たすことを確認
  - _Requirements: 8.3, 8.4_

- [ ] 11. CI/CDパイプラインを検証する
- [ ] 11.1 GitHub Actionsパイプラインを実行する
  - Pull Request作成後にGitHub Actionsワークフローが自動実行されることを確認
  - `php-quality.yml`ワークフローが成功することを確認（Pint + Larastan）
  - `test.yml`ワークフローが成功することを確認（Pestテストスイート）
  - `frontend-test.yml`ワークフローが成功することを確認（API契約変更なし）
  - `e2e-tests.yml`ワークフローが成功することを確認（E2Eテスト）
  - _Requirements: 9.1, 9.2, 9.3, 9.4_

- [ ] 11.2 CI/CD失敗時のエラーハンドリングを確認する
  - いずれかのワークフローが失敗した場合、Pull Requestチェックが失敗ステータスになることを確認
  - GitHub Actionsログでエラー詳細を確認できることを確認
  - _Requirements: 9.5_

- [ ] 12. ドキュメントを更新する
- [ ] 12.1 UUID主キーに関する記述をbigint主キーに更新する
  - プロジェクトドキュメント（README.md、steering documents）でUUID主キーへの言及をbigint主キーに更新
  - マイグレーションファイルコメントにUUID→bigint変更理由を記載
  - 必要に応じてArchitecture Decision Record（ADR）を作成
  - _Requirements: 10.1, 10.2, 10.3_

---

## Implementation Notes

### 実装順序の重要性

タスクは記載された順序で実行する必要があります:
1. **Phase 1-2**: マイグレーションファイルとEloquentモデルの修正（データベーススキーマ変更の基盤）
2. **Phase 3-4**: DDD層とRepository実装の修正（ビジネスロジック層の対応）
3. **Phase 5**: Factory/Seederの修正（テストデータ生成の対応）
4. **Phase 6**: テストケースの修正（UUID前提コードの削除）
5. **Phase 7**: Sanctum認証の確認（認証システムの整合性確認）
6. **Phase 8**: データベース再構築（実際のbigint主キー作成）
7. **Phase 9**: 全テストスイート実行（統合検証）
8. **Phase 10**: コード品質チェック（品質保証）
9. **Phase 11**: CI/CD検証（パイプライン成功確認）
10. **Phase 12**: ドキュメント更新（最終仕上げ）

### 重要な注意事項

- **開発初期段階対応**: 本プロジェクトは開発初期段階のため、既存マイグレーションファイルの直接編集で対応します。
- **データ移行不要**: 既存データが存在しないため、UUID→bigintのデータ移行処理は不要です。
- **API契約不変**: JSONレスポンスのID型は整数値に変わりますが、エンドポイント構造は不変です。
- **テストカバレッジ維持**: 主キー型変更後も85%以上のテストカバレッジを維持します。
- **Architecture Tests**: DDD層の依存方向検証を必ず実行し、クリーンアーキテクチャを維持します。

### ロールバック戦略

各フェーズで問題が発生した場合:
1. Git変更を元に戻す（`git checkout .`）
2. データベースを削除・再作成（`php artisan migrate:fresh --seed`）
3. 原因を調査・修正後、該当フェーズから再実行

### パフォーマンス期待値

UUID型からbigint型への変更により、以下のパフォーマンス改善が期待されます:
- 主キーサイズ: 50%削減（16バイト→8バイト）
- 主キー検索速度: 約30%高速化
- 結合クエリ速度: 約20%高速化

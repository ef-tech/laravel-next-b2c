# 実装タスク計画

## 1. マイグレーションファイルの主キー型変更

- [x] 1.1 `0001_01_01_000000_create_users_table.php` マイグレーションファイルの主キー定義変更
  - `users` テーブルの主キー `id` を UUID から bigint に変更
  - `$table->uuid('id')->primary()` を `$table->id()` に置換
  - `sessions` テーブルの外部キー `user_id` を UUID から bigint に変更
  - `$table->foreignUuid('user_id')` を `$table->foreignId('user_id')` に置換
  - 変更理由と日時を記載したコメントを追加
  - _Requirements: 1.1, 1.2, 1.4_

- [x] 1.2 `2025_09_29_083259_create_personal_access_tokens_table.php` マイグレーションファイルのポリモーフィック外部キー変更
  - `personal_access_tokens` テーブルのポリモーフィック外部キー `tokenable` を UUID から bigint に変更
  - `$table->uuidMorphs('tokenable')` を `$table->morphs('tokenable')` に置換
  - 変更理由を記載したコメントを追加（`// Changed from uuidMorphs() to morphs() for bigint primary key migration (Issue #100)`）
  - _Requirements: 1.3, 1.4_

- [x] 1.3 マイグレーション実行と検証
  - 既存データベースを完全削除して新規マイグレーション実行（`php artisan migrate:fresh`）
  - PostgreSQL テーブル構造の確認（`users.id` が `bigint SERIAL` 型であることを検証）
  - `personal_access_tokens.tokenable_id` が `bigint UNSIGNED` 型であることを検証
  - `sessions.user_id` が `bigint UNSIGNED` 型であることを検証
  - _Requirements: 1.5, 1.6, 1.7, 1.8, 5.1, 5.2_

## 2. Eloquent モデル設定の修正

- [x] 2.1 User モデルの主キー設定プロパティ削除
  - `public $incrementing = false;` プロパティを削除（Laravel デフォルト値 `true` を使用）
  - `protected $keyType = 'string';` プロパティを削除（Laravel デフォルト値 `'int'` を使用）
  - UUID 関連のコメントを削除
  - _Requirements: 2.1, 2.2, 2.3_

- [ ] 2.2 User モデルの動作検証
  - Eloquent ORM が自動インクリメント整数 ID を正しく生成することを確認
  - `User::create()` 実行後に `$user->id` が整数型として返されることを検証
  - Tinker または簡易テストスクリプトでの動作確認
  - _Requirements: 2.4, 2.5_

## 3. Factory/Seeder の修正

- [x] 3.1 UserFactory の UUID 生成ロジック削除
  - `database/factories/UserFactory.php` の `definition()` メソッドを確認
  - UUID 生成ロジック（`'id' => Str::uuid()->toString()`）が存在する場合は削除
  - `definition()` メソッドが `id` フィールドを含まないことを確認（Eloquent 自動生成に委ねる）
  - _Requirements: 3.1, 3.2_

- [ ] 3.2 Seeder ファイルの UUID 指定削除
  - `database/seeders/` 配下の全 Seeder ファイルを確認
  - UUID 明示的指定コード（`'id' => Str::uuid()`）が存在する場合は削除
  - Eloquent 自動インクリメント機能に ID 生成を委ねる
  - _Requirements: 3.3_

- [ ] 3.3 Factory/Seeder の動作検証
  - `User::factory()->create()` 実行時に整数型 ID が自動割り当てされることを確認
  - `php artisan db:seed` 実行時にエラーなく全 Seeder が実行されることを確認
  - データベースで `SELECT id FROM users LIMIT 5;` を実行し、整数値の ID 列（1, 2, 3, ...）を確認
  - _Requirements: 3.4, 3.5, 5.4_

## 4. テストファイルの修正

- [ ] 4.1 UUID 前提のテストケース検索と修正
  - `grep -r "Str::uuid\|toBeString.*id" tests/` で UUID 関連コードを全検出
  - `expect($user->id)->toBeString()` を `expect($user->id)->toBeInt()` に変更
  - `User::factory()->create(['id' => Str::uuid()])` を `User::factory()->create()` に変更
  - Feature Tests の整数型 ID 前提アサーションへの変更
  - Unit Tests の整数型 ID 前提アサーションへの変更
  - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5_

- [ ] 4.2 Architecture Tests の依存関係確認
  - DDD 層の依存関係に影響がないことを確認
  - Domain 層が Infrastructure 層に依存していないことを検証
  - User モデルが DDD 層のインターフェースに準拠していることを確認
  - _Requirements: 4.6_

- [ ] 4.3 テストスイートの実行と検証
  - `./vendor/bin/pest` でユニットテスト実行
  - 全テストケースが成功することを確認
  - テストカバレッジ測定（`./vendor/bin/pest --coverage`）で 85% 以上を維持
  - _Requirements: 4.7, 4.8_

## 5. データベース再構築と完全検証

- [ ] 5.1 データベース完全再構築と Seeder 実行
  - `php artisan migrate:fresh --seed` で全マイグレーションと Seeder を実行
  - PostgreSQL データベースでテーブル構造を確認（`Schema::getColumnType('users', 'id')` が `"bigint"` を返すことを検証）
  - 最初のユーザーレコードを取得（`User::first()->id` が整数値を返すことを確認）
  - _Requirements: 5.1, 5.2, 5.3_

- [ ] 5.2 Laravel Sanctum トークン発行検証
  - Laravel Sanctum トークンを発行して bigint 型 `tokenable_id` で正しく関連付けられることを確認
  - `/api/login` エンドポイントでトークン取得後、`personal_access_tokens` テーブルの `tokenable_id` が整数型であることを検証
  - _Requirements: 5.5_

## 6. テストスイート全体の実行と品質保証

- [ ] 6.1 SQLite 環境での全テスト実行
  - `make test-all` で SQLite 環境の全テストを実行
  - 全テストが成功することを確認
  - _Requirements: 6.1_

- [ ] 6.2 PostgreSQL 環境での全テスト実行
  - `make test-pgsql` で PostgreSQL 環境（本番同等）の全テストを実行
  - 全テストが成功することを確認
  - _Requirements: 6.2_

- [ ] 6.3 並列テスト実行の検証
  - `make test-parallel` で並列実行（4 Shard）の全テストを実行
  - 全テストが成功することを確認
  - _Requirements: 6.3_

- [ ] 6.4 E2E テストの実行
  - `make test-e2e-only` で全 E2E テストを実行
  - 全 E2E テストが成功することを確認
  - _Requirements: 6.4_

- [ ] 6.5 テストカバレッジレポート生成
  - Pest テストフレームワークでカバレッジレポートを生成
  - 85% 以上のカバレッジを維持していることを確認
  - _Requirements: 6.6_

## 7. コード品質チェック

- [ ] 7.1 Laravel Pint によるコードフォーマット検証
  - `composer pint` を実行して全 PHP ファイルがコーディング規約に準拠していることを確認
  - コードスタイル違反がゼロであることを検証
  - _Requirements: 7.1_

- [ ] 7.2 Larastan 静的解析の実行
  - `composer stan` を実行して PHPStan Level 8 静的解析を実行
  - 全エラーがゼロであることを確認
  - _Requirements: 7.2_

- [ ] 7.3 品質統合チェックの実行
  - `composer quality` を実行して Laravel Pint + Larastan 両方のチェックが成功することを確認
  - _Requirements: 7.3_

- [ ] 7.4 Git Hooks の動作確認
  - Pre-commit フック（`.husky/pre-commit`）が実行され、変更ファイルの lint-staged チェックが成功することを確認
  - Pre-push フック（`.husky/pre-push`）が実行され、`composer quality` チェックが成功することを確認
  - _Requirements: 7.4, 7.5_

## 8. API 応答とエンドポイント動作確認

- [ ] 8.1 ユーザー登録エンドポイントの検証
  - `/api/register` エンドポイントにユーザー登録リクエストを送信
  - JSON レスポンスで整数型 `id`（例: `{"id": 1, "name": "Test", ...}`）を返すことを確認
  - _Requirements: 8.1_

- [ ] 8.2 認証ユーザー情報取得エンドポイントの検証
  - `/api/me` エンドポイントに認証済みリクエストを送信
  - JSON レスポンスで認証ユーザーの整数型 `id` を返すことを確認
  - _Requirements: 8.2_

- [ ] 8.3 Laravel Sanctum ログインとトークン発行の検証
  - `/api/login` エンドポイントでトークンを取得
  - Laravel Sanctum がエラーなくトークンを発行し、bigint 型 `tokenable_id` で関連付けられることを確認
  - _Requirements: 8.3_

- [ ] 8.4 トークン一覧取得エンドポイントの検証
  - `/api/tokens` エンドポイントでトークン一覧を取得
  - 整数型 `tokenable_id` を含むトークン情報を返すことを確認
  - _Requirements: 8.4_

- [ ] 8.5 セッションカスケード削除の検証
  - ユーザー削除時にセッションがクリアされることを確認
  - Laravel 認証システムが bigint 型 `user_id` 外部キーで正しくカスケード削除を実行することを検証
  - _Requirements: 8.5_

## 9. CI/CD パイプライン実行確認

- [ ] 9.1 GitHub Actions ワークフロー実行の検証
  - Feature ブランチをリモートリポジトリに push
  - GitHub Actions 全ワークフロー（`php-quality.yml`, `test.yml`, `frontend-test.yml`, `e2e-tests.yml`）が自動実行されることを確認
  - 全ワークフローが成功することを検証
  - _Requirements: 6.5_

## 10. ドキュメント更新（オプション）

- [ ] 10.1 README.md の UUID 参照更新
  - README.md に UUID 主キーへの言及が存在する場合、bigint 主キーに関する記述に更新
  - _Requirements: 9.1_

- [ ] 10.2 Architecture Decision Record（ADR）作成
  - 変更の背景（UUID から bigint への移行理由）を記載
  - 意思決定の根拠（Laravel 標準準拠、パフォーマンス最適化、開発効率）を記載
  - 影響範囲（マイグレーション、モデル、Factory、テスト）を記載
  - 代替案の検討（UUID のまま維持する選択肢の評価）を記載
  - 結論（bigint 採用の最終判断）を記載
  - _Requirements: 9.2_

- [ ] 10.3 マイグレーションファイルのコメント確認
  - マイグレーションファイルに追加したコメントが変更理由と影響範囲を明確に記述していることを確認
  - _Requirements: 9.3_

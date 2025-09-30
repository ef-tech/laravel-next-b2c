# Pest Troubleshooting Guide

Pest実行時のよくあるエラーと解決方法を説明します。

---

## 環境設定エラー

### 1. Pest CLIが見つからない

**エラー**:
```
bash: ./vendor/bin/pest: No such file or directory
```

**原因**: Composerインストールが完了していない、またはPestパッケージがインストールされていない。

**解決方法**:
```bash
# Composerインストール確認
composer install

# Pestパッケージ確認
composer show | grep pest

# Pestパッケージインストール（未インストールの場合）
composer require --dev pestphp/pest:^4.0
```

---

### 2. テストケースクラスが見つからない

**エラー**:
```
Error: Class 'Tests\TestCase' not found
```

**原因**: `tests/Pest.php` で `uses(TestCase::class)` が正しく設定されていない。

**解決方法**:
```php
// tests/Pest.php
uses(Tests\TestCase::class)->in('Feature', 'Unit', 'Architecture');
```

**確認**:
```bash
# TestCase.php が存在するか確認
ls tests/TestCase.php
```

---

### 3. カスタムExpectationが動作しない

**エラー**:
```
Error: Call to undefined method toBeJsonOk()
```

**原因**: `tests/Pest.php` でカスタムExpectationが定義されていない、または構文エラーがある。

**解決方法**:
```php
// tests/Pest.php
expect()->extend('toBeJsonOk', function () {
    $response = $this->value;
    $response->assertOk()->assertHeader('Content-Type', 'application/json');
    return $this;
});
```

**デバッグ**:
```bash
# tests/Pest.php の構文チェック
php -l tests/Pest.php
```

---

## データベース関連エラー

### 4. データベース接続エラー

**エラー**:
```
SQLSTATE[HY000] [2002] Connection refused
```

**原因**: テスト用データベース（PostgreSQL）が起動していない、または接続設定が間違っている。

**解決方法**:
```bash
# Docker環境の場合、Sailを起動
./vendor/bin/sail up -d

# 接続確認
./vendor/bin/sail exec pgsql psql -U sail -d testing -c "SELECT 1;"

# .env.testing または phpunit.xml の設定確認
# DB_CONNECTION=pgsql
# DB_HOST=pgsql
# DB_PORT=13432
# DB_DATABASE=testing
# DB_USERNAME=sail
# DB_PASSWORD=password
```

---

### 5. テスト用データベースが作成されていない

**エラー**:
```
SQLSTATE[3D000]: Invalid catalog name: database "testing" does not exist
```

**原因**: テスト用データベース `testing` が作成されていない。

**解決方法**:
```bash
# Docker環境の場合
./vendor/bin/sail shell -c "PGPASSWORD=password createdb -h pgsql -U sail -p 13432 laravel_test"

# または手動作成
./vendor/bin/sail exec pgsql psql -U sail -c "CREATE DATABASE testing;"

# マイグレーション実行
./vendor/bin/sail artisan migrate --database=pgsql --env=testing
```

---

## カバレッジ関連エラー

### 6. Xdebugが有効化されていない

**エラー**:
```
No code coverage driver available
```

**原因**: Xdebug拡張がインストールされていない、またはXDEBUG_MODEが設定されていない。

**解決方法**:
```bash
# Xdebugインストール確認
php -m | grep xdebug

# Docker環境の場合はSail設定を確認
./vendor/bin/sail php -m | grep xdebug

# カバレッジモードで実行
XDEBUG_MODE=coverage ./vendor/bin/pest --coverage

# Composerスクリプト使用
composer test-coverage
```

---

### 7. カバレッジレポートが生成されない

**エラー**: `coverage-html/` ディレクトリが作成されない

**原因**: `pest.xml` のカバレッジ設定が間違っている、またはメモリ不足。

**解決方法**:
```xml
<!-- pest.xml -->
<coverage>
    <report>
        <html outputDirectory="coverage-html"/>
        <clover outputFile="coverage.xml"/>
    </report>
</coverage>
```

**メモリ不足の場合**:
```bash
# PHPメモリ制限を増加
php -d memory_limit=512M ./vendor/bin/pest --coverage
```

---

## Sharding関連エラー

### 8. Sharding実行でテストが重複実行される

**エラー**: 同じテストが複数回実行される

**原因**: Sharding番号が間違っている、または総数が一致していない。

**解決方法**:
```bash
# 正しいSharding構文
./vendor/bin/pest --shard=1/4  # Shard 1 of 4
./vendor/bin/pest --shard=2/4  # Shard 2 of 4
./vendor/bin/pest --shard=3/4  # Shard 3 of 4
./vendor/bin/pest --shard=4/4  # Shard 4 of 4

# 間違った構文（避ける）
./vendor/bin/pest --shard=1/4 --shard=2/4  # NG: 複数Sharding指定
```

---

### 9. 並列実行でデータベース競合

**エラー**: `Database locked` または `Deadlock detected`

**原因**: 並列実行時に同じテスト用データベースを使用している。

**解決方法**:
```bash
# Pestの並列実行は自動的にDB分離を行う
# ただし、RefreshDatabaseを必ず使用すること

# tests/Pest.php
uses(RefreshDatabase::class)->in('Feature');
```

---

## Composer Scripts関連エラー

### 10. `composer test-pest` でconfig:clearエラー

**エラー**:
```
Could not open input file: artisan
```

**原因**: Composerスクリプトが`backend/laravel-api/`ディレクトリ以外から実行されている。

**解決方法**:
```bash
# 正しいディレクトリに移動
cd backend/laravel-api/

# Composerスクリプト実行
composer test-pest
```

---

## アーキテクチャテスト関連エラー

### 11. アーキテクチャテストで依存関係違反

**エラー**:
```
Architecture Test Failed: App\Http\Controllers\UserController uses App\Models\User
```

**原因**: ControllerがModelに直接依存している（設計原則違反）。

**解決方法**:
```php
// BAD: Controller が Model に直接依存
class UserController extends Controller
{
    public function index()
    {
        $users = User::all(); // NG
    }
}

// GOOD: Service層を経由
class UserController extends Controller
{
    public function __construct(private UserService $service) {}

    public function index()
    {
        $users = $this->service->getAllUsers(); // OK
    }
}
```

---

### 12. ValueObjectsでfinalクラスエラー

**エラー**:
```
Architecture Test Failed: App\ValueObjects\SomeObject is not final
```

**原因**: ValueObjectsディレクトリ内のクラスが `final` 宣言されていない。

**解決方法**:
```php
// ValueObjects は final クラスにする
final class Money
{
    public function __construct(public readonly int $amount) {}
}
```

---

## パフォーマンス最適化

### 13. テスト実行が遅い

**問題**: テスト実行に時間がかかりすぎる

**最適化方法**:
```bash
# 並列実行
composer test-parallel

# Sharding実行（4並列）
composer test-shard

# カバレッジなしで実行（開発時）
./vendor/bin/pest

# 特定のテストのみ実行
./vendor/bin/pest tests/Feature/Api/AuthenticationTest.php
```

---

### 14. カバレッジ計測が遅い

**問題**: カバレッジ計測に時間がかかる

**最適化方法**:
```bash
# pcov を使用（Xdebugより高速）
# composer.json に追加
"require-dev": {
    "pcov/clobber": "^2.0"
}

# pcov でカバレッジ計測
php -d pcov.enabled=1 ./vendor/bin/pest --coverage
```

---

## デバッグTips

### テスト出力を詳細化

```bash
# 詳細出力
./vendor/bin/pest --verbose

# テスト名を表示
./vendor/bin/pest --testdox

# 失敗したテストのスタックトレースを表示
./vendor/bin/pest --verbose --stop-on-failure
```

### 特定のテストをデバッグ

```bash
# 特定のテストファイルのみ実行
./vendor/bin/pest tests/Feature/Api/AuthenticationTest.php

# 特定のテストケースのみ実行（--filter オプション）
./vendor/bin/pest --filter="returns profile for authenticated user"
```

### dd()とdump()の使用

```php
it('debugs user data', function () {
    $user = User::factory()->create();

    // デバッグ出力
    dump($user);  // 実行を継続
    dd($user);    // ここで停止

    expect($user)->toBeInstanceOf(User::class);
});
```

---

## サポート

### 公式ドキュメント
- [Pest公式ドキュメント](https://pestphp.com/docs)
- [Laravel Testing Documentation](https://laravel.com/docs/12.x/testing)

### コミュニティ
- [Pest GitHub Issues](https://github.com/pestphp/pest/issues)
- [Laravel Discord](https://discord.gg/laravel)
# Pest Coding Standards

Pest 4を使用したテストコードのコーディング規約とベストプラクティスを定義します。

---

## テスト命名規則

### ファイル命名規則

**Feature Test**:
```
tests/Feature/{Domain}/{Feature}Test.php
```

例:
- `tests/Feature/Api/AuthenticationTest.php`
- `tests/Feature/Api/CorsTest.php`
- `tests/Feature/Admin/UserManagementTest.php`

**Unit Test**:
```
tests/Unit/{Class}Test.php
```

例:
- `tests/Unit/UserServiceTest.php`
- `tests/Unit/EmailValidatorTest.php`

**Architecture Test**:
```
tests/Architecture/{Aspect}Test.php
```

例:
- `tests/Architecture/LayerTest.php`
- `tests/Architecture/NamingTest.php`
- `tests/Architecture/QualityTest.php`

### テストケース命名規則

**it() 構文**: 自然言語で動作を記述

```php
// ✅ GOOD: 明確で読みやすい
it('returns profile for authenticated user', function () {
    // ...
});

it('rejects unauthenticated access to protected route', function () {
    // ...
});

it('validates token abilities', function () {
    // ...
});

// ❌ BAD: 不明瞭
it('test user', function () {
    // ...
});

it('checks auth', function () {
    // ...
});
```

**test() 構文**: PHPUnitライクな命名（使用は最小限に）

```php
// it() を優先するが、test() も使用可能
test('user can login with valid credentials', function () {
    // ...
});
```

---

## ファイル構成ルール

### 1. strict types宣言

**全テストファイルで必須**:

```php
<?php

declare(strict_types=1);

// テストコード
```

### 2. use文の配置

```php
<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\UserService;

// テストコード
```

### 3. テストケースのグルーピング

```php
// ✅ GOOD: 関連するテストをグループ化
describe('User Authentication', function () {
    it('returns profile for authenticated user', function () {
        // ...
    });

    it('rejects unauthenticated access', function () {
        // ...
    });
});

describe('Token Abilities', function () {
    it('validates token abilities', function () {
        // ...
    });
});
```

---

## Expectation使用ルール

### 1. カスタムExpectationの優先使用

**API専用カスタムExpectation**:

```php
// ✅ GOOD: カスタムExpectationを使用
expect($response)->toBeJsonOk();

// ❌ BAD: 冗長なアサーション
$response->assertOk();
$response->assertHeader('Content-Type', 'application/json');
```

**CORS検証**:

```php
// ✅ GOOD
expect($response)->toHaveCors('http://localhost:3000');

// ❌ BAD
$response->assertHeader('Access-Control-Allow-Origin', 'http://localhost:3000');
$response->assertHeader('Access-Control-Allow-Methods');
$response->assertHeader('Access-Control-Allow-Headers');
```

### 2. チェーンアサーション

```php
// ✅ GOOD: チェーンアサーション
expect($response)
    ->toBeJsonOk()
    ->and($response->json('data.id'))->toBe($user->id)
    ->and($response->json('data.email'))->toBe($user->email);

// ❌ BAD: 個別アサーション
expect($response)->toBeJsonOk();
expect($response->json('data.id'))->toBe($user->id);
expect($response->json('data.email'))->toBe($user->email);
```

### 3. Expectation vs PHPUnit Assertion

```php
// ✅ GOOD: Pest Expectation優先
expect($user->email)->toBe('test@example.com');
expect($users)->toHaveCount(5);
expect($service)->toBeInstanceOf(UserService::class);

// ❌ BAD: PHPUnit Assertion（Pestでは避ける）
$this->assertEquals('test@example.com', $user->email);
$this->assertCount(5, $users);
$this->assertInstanceOf(UserService::class, $service);
```

---

## ヘルパー関数使用ルール

### 1. actingAsApi() の使用

**Sanctum認証が必要な場合は必ず使用**:

```php
// ✅ GOOD: actingAsApi() ヘルパー使用
it('returns user profile', function () {
    $user = User::factory()->create();
    actingAsApi($user);

    $response = $this->getJson('/api/me', jsonHeaders());
    expect($response)->toBeJsonOk();
});

// ❌ BAD: 直接Sanctum使用（冗長）
it('returns user profile', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user, ['*']);

    $response = $this->getJson('/api/me', [
        'Accept' => 'application/json',
        'Content-Type' => 'application/json',
    ]);
    $response->assertOk();
});
```

**Token abilities指定**:

```php
// ✅ GOOD: abilities明示
actingAsApi($user, ['read']);  // 読み取り権限のみ

// ✅ GOOD: 全権限（デフォルト）
actingAsApi($user);  // ['*'] がデフォルト
```

### 2. jsonHeaders() の使用

**APIリクエストでは必ず使用**:

```php
// ✅ GOOD: jsonHeaders() ヘルパー使用
$this->getJson('/api/users', jsonHeaders());
$this->postJson('/api/users', $payload, jsonHeaders());

// ✅ GOOD: 追加ヘッダーをマージ
$this->getJson('/api/users', jsonHeaders([
    'Authorization' => 'Bearer token',
    'Origin' => 'http://localhost:3000',
]));

// ❌ BAD: ヘッダー手動指定（冗長）
$this->getJson('/api/users', [
    'Accept' => 'application/json',
    'Content-Type' => 'application/json',
]);
```

---

## カスタムExpectation作成方法

### 基本構文

```php
// tests/Pest.php
expect()->extend('toBePositive', function () {
    $value = $this->value;

    expect($value)->toBeGreaterThan(0);

    return $this;  // メソッドチェーン可能にする
});
```

### 引数付きカスタムExpectation

```php
expect()->extend('toBeWithinRange', function (int $min, int $max) {
    $value = $this->value;

    expect($value)
        ->toBeGreaterThanOrEqual($min)
        ->toBeLessThanOrEqual($max);

    return $this;
});

// 使用例
expect(50)->toBeWithinRange(1, 100);
```

### TestResponse専用カスタムExpectation

```php
expect()->extend('toHaveJsonKey', function (string $key) {
    /** @var TestResponse $response */
    $response = $this->value;

    $response->assertJsonPath($key, fn ($value) => $value !== null);

    return $this;
});

// 使用例
expect($response)->toHaveJsonKey('data.user.id');
```

---

## Dataset（パラメータ化テスト）

### 基本使用法

```php
it('validates email format', function (string $email, bool $expected) {
    $validator = Validator::make(['email' => $email], ['email' => 'email']);
    expect($validator->passes())->toBe($expected);
})->with([
    ['test@example.com', true],
    ['invalid-email', false],
    ['test@localhost', true],
    ['', false],
]);
```

### 名前付きDataset

```php
it('validates email format', function (string $email, bool $expected) {
    $validator = Validator::make(['email' => $email], ['email' => 'email']);
    expect($validator->passes())->toBe($expected);
})->with([
    'valid email' => ['test@example.com', true],
    'invalid format' => ['invalid-email', false],
    'localhost' => ['test@localhost', true],
    'empty string' => ['', false],
]);
```

### 外部Datasetファイル

```php
// tests/Datasets/Emails.php
dataset('emails', function () {
    yield ['test@example.com', true];
    yield ['invalid-email', false];
});

// テストファイル
it('validates email format', function (string $email, bool $expected) {
    $validator = Validator::make(['email' => $email], ['email' => 'email']);
    expect($validator->passes())->toBe($expected);
})->with('emails');
```

---

## テストスキップと条件実行

### テストスキップ

```php
// 実装未完了の場合スキップ
it('creates a resource and returns JSON:API payload', function () {
    // ...
})->skip('API resource endpoint not yet implemented');

// 条件付きスキップ
it('runs only on CI', function () {
    // ...
})->skipOnLocal();

it('runs only locally', function () {
    // ...
})->skipOnCI();
```

### 条件実行

```php
// 特定の環境でのみ実行
it('runs only on production', function () {
    // ...
})->skipUnless(app()->environment('production'));

// PHPバージョン指定
it('uses PHP 8.4 features', function () {
    // ...
})->skipUnless(PHP_VERSION_ID >= 80400);
```

---

## アーキテクチャテストルール

### レイヤー分離

```php
// ✅ GOOD: 明確なレイヤー分離ルール
arch('controllers should not depend on models directly')
    ->expect('App\Http\Controllers')
    ->not->toUse('App\Models')
    ->toOnlyUse([
        'Illuminate',
        'App\Services',
        'App\Http\Requests',
        'App\Http\Resources',
    ]);
```

### 命名規則

```php
// ✅ GOOD: 一貫性のある命名規則
arch('controllers should be suffixed with Controller')
    ->expect('App\Http\Controllers')
    ->toHaveSuffix('Controller');
```

### コード品質

```php
// ✅ GOOD: 品質基準の自動検証
arch('no debugging functions in production code')
    ->expect(['dd', 'dump', 'var_dump', 'print_r'])
    ->not->toBeUsed();

arch('strict types should be declared')
    ->expect('App')
    ->toUseStrictTypes();
```

---

## テストデータ生成

### Factory使用

```php
// ✅ GOOD: Factoryで明示的にデータ生成
$user = User::factory()->create([
    'name' => 'Test User',
    'email' => 'test@example.com',
]);

// ✅ GOOD: 複数レコード生成
$users = User::factory()->count(10)->create();

// ❌ BAD: 手動でデータ生成（避ける）
$user = new User();
$user->name = 'Test User';
$user->email = 'test@example.com';
$user->save();
```

### beforeEach() の使用

```php
// ✅ GOOD: 共通セットアップ
beforeEach(function () {
    $this->user = User::factory()->create();
    actingAsApi($this->user);
});

it('accesses protected route', function () {
    $response = $this->getJson('/api/me', jsonHeaders());
    expect($response)->toBeJsonOk();
});
```

---

## まとめ

### チェックリスト

- [ ] 全テストファイルで `declare(strict_types=1)` 宣言
- [ ] `it()` 構文で自然言語のテスト名
- [ ] カスタムExpectation（`toBeJsonOk`, `toHaveCors`）を優先使用
- [ ] ヘルパー関数（`actingAsApi`, `jsonHeaders`）を使用
- [ ] チェーンアサーションで可読性向上
- [ ] Dataset（パラメータ化テスト）で重複削減
- [ ] アーキテクチャテストで設計原則を強制
- [ ] Factoryでテストデータ生成

### 参考リンク

- [Pest公式ドキュメント](https://pestphp.com/docs)
- [Laravel Testing Documentation](https://laravel.com/docs/12.x/testing)
- [プロジェクト内サンプル](tests/Feature/Api/)
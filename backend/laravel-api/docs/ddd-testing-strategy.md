# DDD Testing Strategy

## 目次

- [概要](#概要)
- [テスト戦略の全体像](#テスト戦略の全体像)
- [各層のテスト方針](#各層のテスト方針)
  - [Domain層のテスト](#domain層のテスト)
  - [Application層のテスト](#application層のテスト)
  - [Infrastructure層のテスト](#infrastructure層のテスト)
  - [HTTP層のテスト](#http層のテスト)
- [Architecture Tests](#architecture-tests)
- [テストヘルパーとモックパターン](#テストヘルパーとモックパターン)
- [カバレッジ目標と測定方法](#カバレッジ目標と測定方法)
- [CI/CD統合](#cicd統合)
- [テスト実行コマンド](#テスト実行コマンド)
- [ベストプラクティス](#ベストプラクティス)

---

## 概要

本ドキュメントでは、DDD（ドメイン駆動設計）アーキテクチャにおけるテスト戦略を定義します。

### テスト戦略の目的

1. **品質保証**: ビジネスロジックの正確性を保証する
2. **リグレッション防止**: 既存機能の破壊を検出する
3. **設計改善**: テスタビリティの高い設計を促進する
4. **ドキュメント**: テストコードが仕様書として機能する
5. **アーキテクチャ遵守**: 依存関係ルールの自動検証

### テスト原則

- **TDD（Test-Driven Development）**: テストファーストで実装する
- **Fast Feedback**: 高速なフィードバックループを維持する
- **Isolation**: 各層のテストを独立させる
- **Readability**: テストコードの可読性を最優先する
- **Maintainability**: メンテナンスしやすいテストを書く

---

## テスト戦略の全体像

### テストピラミッド

```
       ┌─────────────┐
       │  E2E Tests  │  少ない・遅い・高コスト
       │   (Pest)    │
       ├─────────────┤
       │Integration  │  中程度・中速・中コスト
       │   Tests     │
       │   (Pest)    │
       ├─────────────┤
       │   Feature   │  中程度・中速・中コスト
       │   Tests     │
       │   (Pest)    │
       ├─────────────┤
       │    Unit     │  多い・速い・低コスト
       │   Tests     │
       │   (Pest)    │
       └─────────────┘
```

### テスト種別と役割

| テスト種別 | 役割 | 対象層 | Laravel機能 | データベース |
|-----------|-----|-------|------------|------------|
| **Unit Tests** | 単体ロジック検証 | Domain層 | 使用しない | 使用しない |
| **Feature Tests** | ビジネスフロー検証 | Application層 | DI使用 | モック or In-memory |
| **Integration Tests** | 外部連携検証 | Infrastructure層 | 全機能使用 | 実データベース（SQLite） |
| **E2E Tests** | エンドツーエンド検証 | HTTP層 | 全機能使用 | 実データベース（SQLite） |
| **Architecture Tests** | 依存関係検証 | 全層 | Pestプラグイン | 使用しない |

---

## 各層のテスト方針

### Domain層のテスト

#### 目的

- ビジネスルールの正確性を検証する
- ValueObjectとEntityの不変性を保証する
- Domain Eventの発火を確認する

#### テスト種別

**Unit Tests** - Laravel機能非依存の高速テスト

#### テスト対象

1. **ValueObject**
   - 妥当な値での生成成功
   - 不正な値での例外送出
   - `equals()`メソッドの同値性判定
   - 不変性の保証

2. **Entity**
   - ファクトリメソッドでの生成
   - ビジネスルールの検証
   - Domain Eventの記録
   - `pullDomainEvents()`の動作

3. **Domain Service**
   - ビジネスロジックの正確性
   - 集約間の整合性

#### サンプルコード

**ValueObject Unit Test** (`tests/Unit/Ddd/Domain/User/ValueObjects/EmailTest.php`)

```php
<?php

declare(strict_types=1);

use Ddd\Domain\User\ValueObjects\Email;
use Ddd\Shared\Domain\Exceptions\ValidationException;

test('can create email with valid email address', function (): void {
    $email = Email::fromString('test@example.com');

    expect($email->value())->toBe('test@example.com');
});

test('throws validation exception with invalid email address', function (): void {
    Email::fromString('invalid-email');
})->throws(ValidationException::class, 'Invalid email address');

test('equals returns true for same email addresses', function (): void {
    $email1 = Email::fromString('test@example.com');
    $email2 = Email::fromString('test@example.com');

    expect($email1->equals($email2))->toBeTrue();
});

test('equals returns false for different email addresses', function (): void {
    $email1 = Email::fromString('test1@example.com');
    $email2 = Email::fromString('test2@example.com');

    expect($email1->equals($email2))->toBeFalse();
});
```

**Entity Unit Test** (`tests/Unit/Ddd/Domain/User/Entities/UserTest.php`)

```php
<?php

declare(strict_types=1);

use Ddd\Domain\User\Entities\User;
use Ddd\Domain\User\Events\UserRegistered;
use Ddd\Domain\User\ValueObjects\Email;
use Ddd\Domain\User\ValueObjects\UserId;
use Ddd\Shared\Domain\Exceptions\ValidationException;

test('can register user with valid data', function (): void {
    $userId = UserId::generate();
    $email = Email::fromString('test@example.com');

    $user = User::register(
        id: $userId,
        email: $email,
        name: 'Test User'
    );

    expect($user->id()->equals($userId))->toBeTrue();
    expect($user->email()->equals($email))->toBeTrue();
    expect($user->name())->toBe('Test User');
});

test('records user registered event when registering', function (): void {
    $userId = UserId::generate();
    $email = Email::fromString('test@example.com');

    $user = User::register(
        id: $userId,
        email: $email,
        name: 'Test User'
    );

    $events = $user->pullDomainEvents();
    expect($events)->toHaveCount(1);
    expect($events[0])->toBeInstanceOf(UserRegistered::class);
    expect($events[0]->userId->equals($userId))->toBeTrue();
});

test('throws validation exception when name is less than 2 characters', function (): void {
    $userId = UserId::generate();
    $email = Email::fromString('test@example.com');

    User::register(
        id: $userId,
        email: $email,
        name: 'A'
    );
})->throws(ValidationException::class, 'Name must be at least 2 characters');

test('can change name with valid name', function (): void {
    $user = User::register(
        id: UserId::generate(),
        email: Email::fromString('test@example.com'),
        name: 'Test User'
    );

    $user->changeName('New Name');

    expect($user->name())->toBe('New Name');
});

test('pulling domain events clears the events', function (): void {
    $user = User::register(
        id: UserId::generate(),
        email: Email::fromString('test@example.com'),
        name: 'Test User'
    );

    $events = $user->pullDomainEvents();
    expect($events)->toHaveCount(1);

    $events = $user->pullDomainEvents();
    expect($events)->toHaveCount(0);
});
```

#### テストの特徴

- **Laravel機能非依存**: DIコンテナ、Eloquent、Facadeを使用しない
- **高速実行**: データベース不要、瞬時に完了
- **純粋なPHP**: `new`演算子で直接インスタンス化
- **ビジネスルール中心**: ドメインロジックのみ検証

---

### Application層のテスト

#### 目的

- UseCaseのビジネスフローを検証する
- トランザクション境界の動作を確認する
- Domain Eventの発火を確認する

#### テスト種別

**Feature Tests** - DIコンテナを使用したビジネスフロー検証

#### テスト対象

1. **UseCase**
   - 入力DTOから出力DTOへの変換
   - ビジネスフロー全体の成功
   - 異常系での例外送出
   - トランザクション境界の動作

2. **Domain Event Handler**
   - イベント受信後の処理
   - 外部サービス連携

#### サンプルコード

**UseCase Feature Test** (`tests/Feature/Ddd/Application/User/UseCases/RegisterUserUseCaseTest.php`)

```php
<?php

declare(strict_types=1);

use Ddd\Application\User\UseCases\RegisterUser\RegisterUserInput;
use Ddd\Application\User\UseCases\RegisterUser\RegisterUserUseCase;
use Ddd\Domain\User\Repositories\UserRepository;
use Ddd\Domain\User\ValueObjects\Email;
use Ddd\Shared\Domain\Exceptions\DomainException;

test('can register user successfully', function (): void {
    $useCase = app(RegisterUserUseCase::class);

    $input = new RegisterUserInput(
        email: 'test@example.com',
        name: 'Test User'
    );

    $output = $useCase->execute($input);

    expect($output->userId)->not->toBeNull();

    // データベース検証
    $repository = app(UserRepository::class);
    $user = $repository->find($output->userId);
    expect($user)->not->toBeNull();
    expect($user->email()->value())->toBe('test@example.com');
    expect($user->name())->toBe('Test User');
});

test('throws domain exception when email already exists', function (): void {
    $useCase = app(RegisterUserUseCase::class);

    // 1回目の登録
    $input = new RegisterUserInput(
        email: 'duplicate@example.com',
        name: 'First User'
    );
    $useCase->execute($input);

    // 2回目の登録（重複）
    $input = new RegisterUserInput(
        email: 'duplicate@example.com',
        name: 'Second User'
    );
    $useCase->execute($input);
})->throws(DomainException::class, 'Email already exists');

test('does not save user when transaction fails', function (): void {
    $useCase = app(RegisterUserUseCase::class);
    $repository = app(UserRepository::class);

    // トランザクション失敗をシミュレート
    // （実装により方法は異なる、ここではモック例）
    // ...

    $input = new RegisterUserInput(
        email: 'test@example.com',
        name: 'Test User'
    );

    try {
        $useCase->execute($input);
    } catch (\Exception $e) {
        // トランザクションロールバック後、データが存在しないことを確認
        $user = $repository->findByEmail(Email::fromString('test@example.com'));
        expect($user)->toBeNull();
    }
});
```

#### テストの特徴

- **DIコンテナ使用**: `app(RegisterUserUseCase::class)`でインスタンス取得
- **実データベース**: SQLite in-memoryで高速実行
- **RefreshDatabase**: テストごとにデータベースをリセット
- **ビジネスフロー検証**: UseCaseの入力→出力を確認

---

### Infrastructure層のテスト

#### 目的

- Eloquent Modelとの変換を検証する
- Repository実装の正確性を確認する
- 外部サービス連携を検証する

#### テスト種別

**Integration Tests** - 実データベースを使用した統合テスト

#### テスト対象

1. **Repository Implementation**
   - 永続化・取得の正確性
   - Mapperによる変換
   - クエリの正確性

2. **Mapper**
   - Eloquent Model → Domain Entity変換
   - Domain Entity → Eloquent Model変換
   - Carbon型の日時変換

3. **Event Bus**
   - イベントディスパッチの動作
   - afterCommitの動作

4. **Transaction Manager**
   - トランザクション境界の動作
   - ロールバックの動作

#### サンプルコード

**Repository Integration Test** (`tests/Feature/Ddd/Infrastructure/Persistence/Eloquent/Repositories/EloquentUserRepositoryTest.php`)

```php
<?php

declare(strict_types=1);

use Ddd\Domain\User\Entities\User;
use Ddd\Domain\User\Repositories\UserRepository;
use Ddd\Domain\User\ValueObjects\Email;
use Ddd\Domain\User\ValueObjects\UserId;

test('can save and find user by ID', function (): void {
    $repository = app(UserRepository::class);

    $userId = $repository->nextId();
    $user = User::register(
        id: $userId,
        email: Email::fromString('test@example.com'),
        name: 'Test User'
    );

    $repository->save($user);

    $foundUser = $repository->find($userId);
    expect($foundUser)->not->toBeNull();
    expect($foundUser->id()->equals($userId))->toBeTrue();
    expect($foundUser->email()->value())->toBe('test@example.com');
    expect($foundUser->name())->toBe('Test User');
});

test('can find user by email', function (): void {
    $repository = app(UserRepository::class);

    $userId = $repository->nextId();
    $email = Email::fromString('find@example.com');
    $user = User::register(
        id: $userId,
        email: $email,
        name: 'Find User'
    );

    $repository->save($user);

    $foundUser = $repository->findByEmail($email);
    expect($foundUser)->not->toBeNull();
    expect($foundUser->id()->equals($userId))->toBeTrue();
});

test('returns null when user not found by ID', function (): void {
    $repository = app(UserRepository::class);

    $nonExistentId = UserId::fromString('99999999-9999-4999-9999-999999999999');
    $foundUser = $repository->find($nonExistentId);

    expect($foundUser)->toBeNull();
});

test('can check if email exists', function (): void {
    $repository = app(UserRepository::class);

    $email = Email::fromString('exists@example.com');
    $user = User::register(
        id: $repository->nextId(),
        email: $email,
        name: 'Exists User'
    );

    $repository->save($user);

    expect($repository->existsByEmail($email))->toBeTrue();
    expect($repository->existsByEmail(Email::fromString('notexists@example.com')))->toBeFalse();
});

test('can update existing user', function (): void {
    $repository = app(UserRepository::class);

    $userId = $repository->nextId();
    $user = User::register(
        id: $userId,
        email: Email::fromString('update@example.com'),
        name: 'Original Name'
    );

    $repository->save($user);

    // 名前を変更
    $user->changeName('Updated Name');
    $repository->save($user);

    $foundUser = $repository->find($userId);
    expect($foundUser->name())->toBe('Updated Name');
});

test('can delete user', function (): void {
    $repository = app(UserRepository::class);

    $userId = $repository->nextId();
    $user = User::register(
        id: $userId,
        email: Email::fromString('delete@example.com'),
        name: 'Delete User'
    );

    $repository->save($user);
    expect($repository->find($userId))->not->toBeNull();

    $repository->delete($userId);
    expect($repository->find($userId))->toBeNull();
});

test('nextId generates valid UUID', function (): void {
    $repository = app(UserRepository::class);

    $userId1 = $repository->nextId();
    $userId2 = $repository->nextId();

    expect($userId1)->toBeInstanceOf(UserId::class);
    expect($userId2)->toBeInstanceOf(UserId::class);
    expect($userId1->equals($userId2))->toBeFalse();
});

test('mapper converts domain entity to eloquent model correctly', function (): void {
    $repository = app(UserRepository::class);

    $userId = $repository->nextId();
    $user = User::register(
        id: $userId,
        email: Email::fromString('mapper@example.com'),
        name: 'Mapper User'
    );

    $repository->save($user);

    // Eloquent Model直接取得
    $eloquentUser = \App\Models\User::find($userId->value());
    expect($eloquentUser)->not->toBeNull();
    expect($eloquentUser->id)->toBe($userId->value());
    expect($eloquentUser->email)->toBe('mapper@example.com');
    expect($eloquentUser->name)->toBe('Mapper User');
});

test('mapper converts eloquent model to domain entity correctly', function (): void {
    $repository = app(UserRepository::class);

    // Eloquent Modelで直接作成
    $eloquentUser = \App\Models\User::create([
        'id' => '88888888-8888-4888-8888-888888888888',
        'email' => 'eloquent@example.com',
        'name' => 'Eloquent User',
        'email_verified_at' => now(),
    ]);

    // Repository経由でDomain Entity取得
    $userId = UserId::fromString($eloquentUser->id);
    $user = $repository->find($userId);

    expect($user)->not->toBeNull();
    expect($user->id()->value())->toBe('88888888-8888-4888-8888-888888888888');
    expect($user->email()->value())->toBe('eloquent@example.com');
    expect($user->name())->toBe('Eloquent User');
});
```

#### テストの特徴

- **実データベース使用**: SQLite in-memoryで高速実行
- **RefreshDatabase trait**: テストごとにマイグレーション実行
- **Mapper検証**: 双方向変換の正確性を確認
- **実装詳細検証**: Eloquent Modelとの統合を確認

---

### HTTP層のテスト

#### 目的

- APIエンドポイントの動作を検証する
- HTTPリクエスト・レスポンスの形式を確認する
- バリデーションエラーのハンドリングを検証する

#### テスト種別

**E2E Tests** - エンドツーエンドでの全体動作検証

#### テスト対象

1. **Controller**
   - HTTPリクエスト → Input DTO変換
   - UseCase実行
   - Output DTO → JSONレスポンス変換

2. **Form Request**
   - バリデーションルールの正確性
   - エラーメッセージの形式

3. **Exception Handler**
   - Domain Exceptionのステータスコード変換
   - エラーレスポンス形式

#### サンプルコード

**Controller E2E Test** (`tests/Feature/Http/Controllers/UserControllerTest.php`)

```php
<?php

declare(strict_types=1);

test('can register user successfully', function (): void {
    $response = $this->postJson('/api/users', [
        'email' => 'test@example.com',
        'name' => 'Test User',
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['id']);

    // データベース検証
    $this->assertDatabaseHas('users', [
        'email' => 'test@example.com',
        'name' => 'Test User',
    ]);
});

test('returns 422 when email already exists', function (): void {
    // 1回目の登録
    $this->postJson('/api/users', [
        'email' => 'duplicate@example.com',
        'name' => 'First User',
    ]);

    // 2回目の登録（重複）
    $response = $this->postJson('/api/users', [
        'email' => 'duplicate@example.com',
        'name' => 'Second User',
    ]);

    $response->assertStatus(422)
        ->assertJsonStructure(['error', 'message']);
});

test('returns 422 when email format is invalid', function (): void {
    $response = $this->postJson('/api/users', [
        'email' => 'invalid-email',
        'name' => 'Test User',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('returns 422 when name is too short', function (): void {
    $response = $this->postJson('/api/users', [
        'email' => 'test@example.com',
        'name' => 'A',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});

test('returns 422 when required fields are missing', function (): void {
    $response = $this->postJson('/api/users', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email', 'name']);
});
```

#### テストの特徴

- **実HTTPリクエスト**: `postJson()`でAPIエンドポイント呼び出し
- **ステータスコード検証**: `assertStatus(201)`等で確認
- **JSONレスポンス検証**: `assertJsonStructure()`等で確認
- **データベース検証**: `assertDatabaseHas()`で永続化確認

---

## Architecture Tests

### 目的

依存関係ルールを自動検証し、アーキテクチャの健全性を保証する。

### テスト対象

1. **Domain層の独立性**
   - Illuminate、Laravel、Eloquentに依存しない
   - Infrastructure層に依存しない

2. **Application層の独立性**
   - Infrastructure層に依存しない（Interfaceのみ依存）

3. **Interface Adaptersの依存方向**
   - ControllerはDDD層を使用する
   - 直接Eloquent Modelを使用しない

### サンプルコード

**Architecture Test** (`tests/Architecture/DddArchitectureTest.php`)

```php
<?php

declare(strict_types=1);

test('Domain layer does not depend on Illuminate', function (): void {
    expect('Ddd\Domain')
        ->not->toUse([
            'Illuminate',
            'Laravel',
            'Eloquent',
        ]);
});

test('Domain layer does not depend on Infrastructure layer', function (): void {
    expect('Ddd\Domain')
        ->not->toUse('Ddd\Infrastructure');
});

test('Application layer does not depend on Infrastructure layer', function (): void {
    expect('Ddd\Application')
        ->not->toUse('Ddd\Infrastructure');
});

test('Controllers use Application layer instead of Models', function (): void {
    expect('App\Http\Controllers')
        ->toOnlyUse([
            'App\Http\Controllers',
            'App\Http\Requests',
            'Ddd\Application',
            'Ddd\Shared',
            'Illuminate\Http',
            'Illuminate\Routing',
        ]);
});
```

### 実行タイミング

- **ローカル開発**: コミット前に実行
- **CI/CD**: プルリクエストごとに自動実行

---

## テストヘルパーとモックパターン

### テストヘルパー

#### RefreshDatabase Trait

全テストでデータベースをリセットする。

```php
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);
```

#### カスタムヘルパー

**UserFactory Helper** (`tests/Helpers/UserFactory.php`)

```php
<?php

declare(strict_types=1);

namespace Tests\Helpers;

use Ddd\Domain\User\Entities\User;
use Ddd\Domain\User\ValueObjects\Email;
use Ddd\Domain\User\ValueObjects\UserId;

final class UserFactory
{
    public static function create(
        ?UserId $id = null,
        ?Email $email = null,
        ?string $name = null
    ): User {
        return User::register(
            id: $id ?? UserId::generate(),
            email: $email ?? Email::fromString('test@example.com'),
            name: $name ?? 'Test User'
        );
    }
}
```

使用例:

```php
use Tests\Helpers\UserFactory;

test('can create user with factory', function (): void {
    $user = UserFactory::create();

    expect($user)->toBeInstanceOf(User::class);
});
```

---

### モックパターン

#### Repository Mock

**Mockery使用例**

```php
use Ddd\Domain\User\Repositories\UserRepository;
use Ddd\Domain\User\ValueObjects\Email;
use Mockery;

test('can mock repository', function (): void {
    $mockRepository = Mockery::mock(UserRepository::class);

    $mockRepository->shouldReceive('existsByEmail')
        ->with(Mockery::type(Email::class))
        ->andReturn(false);

    app()->instance(UserRepository::class, $mockRepository);

    // テスト実行
    // ...
});
```

#### EventBus Mock

```php
use Ddd\Application\Shared\Services\EventBus\EventBus;
use Mockery;

test('can verify event dispatch', function (): void {
    $mockEventBus = Mockery::mock(EventBus::class);

    $mockEventBus->shouldReceive('dispatch')
        ->once()
        ->with(
            Mockery::type(\Ddd\Domain\User\Events\UserRegistered::class),
            true
        );

    app()->instance(EventBus::class, $mockEventBus);

    // テスト実行
    // ...
});
```

#### TransactionManager Mock

```php
use Ddd\Application\Shared\Services\TransactionManager\TransactionManager;
use Mockery;

test('can mock transaction manager', function (): void {
    $mockTransactionManager = Mockery::mock(TransactionManager::class);

    $mockTransactionManager->shouldReceive('run')
        ->once()
        ->andReturnUsing(function (callable $callback) {
            return $callback();
        });

    app()->instance(TransactionManager::class, $mockTransactionManager);

    // テスト実行
    // ...
});
```

---

## カバレッジ目標と測定方法

### カバレッジ目標

| 層 | 目標カバレッジ | 理由 |
|----|-------------|-----|
| **Domain層** | 100% | ビジネスロジック中心、最重要 |
| **Application層** | 100% | ビジネスフロー、高重要度 |
| **Infrastructure層** | 95%以上 | 外部連携あり、ほぼ必須 |
| **HTTP層** | 90%以上 | E2Eでカバー、高カバレッジ推奨 |
| **全体** | 85%以上 | プロジェクト全体の品質基準 |

### カバレッジ測定方法

#### ローカル実行

**全体カバレッジ測定**

```bash
cd backend/laravel-api
XDEBUG_MODE=coverage ./vendor/bin/pest --coverage
```

**最小カバレッジ閾値指定**

```bash
XDEBUG_MODE=coverage ./vendor/bin/pest --coverage --min=85
```

**HTMLレポート生成**

```bash
XDEBUG_MODE=coverage ./vendor/bin/pest --coverage --coverage-html=coverage-report
```

ブラウザで `coverage-report/index.html` を開く。

#### CI/CD実行

`.github/workflows/test.yml`

```yaml
coverage:
  runs-on: ubuntu-latest
  needs: test
  steps:
    - uses: actions/checkout@v4

    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.4'
        coverage: xdebug

    - name: Install Dependencies
      run: |
        cd backend/laravel-api
        composer install --no-interaction --no-progress

    - name: Run Tests with Coverage
      run: |
        cd backend/laravel-api
        XDEBUG_MODE=coverage ./vendor/bin/pest --coverage --min=85
      env:
        DB_CONNECTION: sqlite
        DB_DATABASE: ':memory:'

    - name: Upload Coverage Report
      if: always()
      uses: actions/upload-artifact@v4
      with:
        name: coverage-report
        path: backend/laravel-api/coverage-report
```

---

## CI/CD統合

### GitHub Actionsワークフロー

#### テストジョブ

```yaml
test:
  runs-on: ubuntu-latest
  strategy:
    matrix:
      php-version: ['8.4']
  steps:
    - uses: actions/checkout@v4

    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: ${{ matrix.php-version }}
        extensions: mbstring, xml, ctype, iconv, intl, pdo_sqlite
        coverage: none

    - name: Install Dependencies
      run: |
        cd backend/laravel-api
        composer install --no-interaction --no-progress

    - name: Run Tests
      run: |
        cd backend/laravel-api
        ./vendor/bin/pest
      env:
        DB_CONNECTION: sqlite
        DB_DATABASE: ':memory:'
```

#### PHPStanジョブ

```yaml
phpstan:
  runs-on: ubuntu-latest
  steps:
    - uses: actions/checkout@v4

    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.4'
        coverage: none

    - name: Install Dependencies
      run: |
        cd backend/laravel-api
        composer install --no-interaction --no-progress

    - name: Run PHPStan
      run: |
        cd backend/laravel-api
        ./vendor/bin/phpstan analyse
```

#### Coverageジョブ

```yaml
coverage:
  runs-on: ubuntu-latest
  needs: test
  steps:
    - uses: actions/checkout@v4

    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.4'
        coverage: xdebug

    - name: Install Dependencies
      run: |
        cd backend/laravel-api
        composer install --no-interaction --no-progress

    - name: Run Tests with Coverage
      run: |
        cd backend/laravel-api
        XDEBUG_MODE=coverage ./vendor/bin/pest --coverage --min=85
      env:
        DB_CONNECTION: sqlite
        DB_DATABASE: ':memory:'

    - name: Upload Coverage Report
      if: always()
      uses: actions/upload-artifact@v4
      with:
        name: coverage-report
        path: backend/laravel-api/coverage-report
```

---

## テスト実行コマンド

### ローカル開発

#### 全テスト実行

```bash
cd backend/laravel-api
./vendor/bin/pest
```

#### 特定層のテスト実行

**Domain層のみ**

```bash
./vendor/bin/pest tests/Unit/Ddd/Domain
```

**Application層のみ**

```bash
./vendor/bin/pest tests/Feature/Ddd/Application
```

**Infrastructure層のみ**

```bash
./vendor/bin/pest tests/Feature/Ddd/Infrastructure
```

**HTTP層のみ**

```bash
./vendor/bin/pest tests/Feature/Http
```

**Architecture Testsのみ**

```bash
./vendor/bin/pest tests/Architecture
```

#### フィルタ実行

**特定テストケース**

```bash
./vendor/bin/pest --filter="can register user successfully"
```

**特定ファイル**

```bash
./vendor/bin/pest tests/Unit/Ddd/Domain/User/ValueObjects/EmailTest.php
```

#### 並列実行

```bash
./vendor/bin/pest --parallel
```

#### 詳細出力

```bash
./vendor/bin/pest --verbose
```

#### 品質チェック一括実行

```bash
composer quality
```

（`composer.json`の`scripts`セクションに定義）

```json
{
  "scripts": {
    "quality": [
      "./vendor/bin/pint",
      "./vendor/bin/phpstan analyse",
      "./vendor/bin/pest"
    ]
  }
}
```

---

## ベストプラクティス

### テスト記述

#### 1. テスト名は日本語で明確に

**Good**

```php
test('メールアドレスが不正な場合はValidationExceptionを送出する', function (): void {
    Email::fromString('invalid-email');
})->throws(ValidationException::class);
```

**Bad**

```php
test('invalid email', function (): void {
    Email::fromString('invalid-email');
})->throws(ValidationException::class);
```

#### 2. Arrange-Act-Assert パターンを使用

```php
test('can register user successfully', function (): void {
    // Arrange: テストデータ準備
    $useCase = app(RegisterUserUseCase::class);
    $input = new RegisterUserInput(
        email: 'test@example.com',
        name: 'Test User'
    );

    // Act: 実行
    $output = $useCase->execute($input);

    // Assert: 検証
    expect($output->userId)->not->toBeNull();
});
```

#### 3. テストは独立させる

各テストは他のテストに依存せず、独立して実行可能にする。

```php
// Bad: 他のテストに依存
test('step 1', function (): void {
    $this->userId = createUser();
});

test('step 2', function (): void {
    updateUser($this->userId); // step 1に依存
});

// Good: 独立
test('can create user', function (): void {
    $userId = createUser();
    expect($userId)->not->toBeNull();
});

test('can update user', function (): void {
    $userId = createUser(); // 自分で準備
    updateUser($userId);
    expect(...)->toBe(...);
});
```

#### 4. 異常系テストを忘れない

正常系だけでなく、異常系も必ずテストする。

```php
test('throws exception when email is invalid', function (): void {
    Email::fromString('invalid-email');
})->throws(ValidationException::class);

test('throws exception when name is too short', function (): void {
    User::register(
        id: UserId::generate(),
        email: Email::fromString('test@example.com'),
        name: 'A'
    );
})->throws(ValidationException::class);
```

---

### パフォーマンス最適化

#### 1. SQLite in-memoryを使用

`phpunit.xml`

```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```

#### 2. 並列実行を活用

```bash
./vendor/bin/pest --parallel
```

#### 3. 不要なカバレッジ測定を避ける

通常実行時はカバレッジなし、CI/CDでのみ測定。

```bash
# 通常（高速）
./vendor/bin/pest

# カバレッジあり（低速）
XDEBUG_MODE=coverage ./vendor/bin/pest --coverage
```

---

### メンテナンス性向上

#### 1. テストヘルパーを活用

共通処理はヘルパー関数・クラスに抽出。

```php
// tests/Helpers/UserFactory.php
final class UserFactory
{
    public static function create(/* ... */): User { /* ... */ }
}

// tests/Feature/...Test.php
test('can register user', function (): void {
    $user = UserFactory::create();
    // ...
});
```

#### 2. データセット機能を活用

複数パターンのテストを簡潔に記述。

```php
test('validates email format', function (string $email, bool $isValid): void {
    if ($isValid) {
        expect(Email::fromString($email))->toBeInstanceOf(Email::class);
    } else {
        expect(fn() => Email::fromString($email))->toThrow(ValidationException::class);
    }
})->with([
    ['test@example.com', true],
    ['invalid-email', false],
    ['test@', false],
    ['@example.com', false],
]);
```

#### 3. setUp/tearDownを活用

共通の準備・後片付け処理を定義。

```php
beforeEach(function (): void {
    // 各テスト前に実行
    $this->repository = app(UserRepository::class);
});

afterEach(function (): void {
    // 各テスト後に実行
    // クリーンアップ処理
});
```

---

## まとめ

### テスト戦略のポイント

1. **層ごとに適切なテスト種別を選択**
   - Domain: Unit Tests（Laravel非依存）
   - Application: Feature Tests（DIコンテナ使用）
   - Infrastructure: Integration Tests（実DB使用）
   - HTTP: E2E Tests（実HTTPリクエスト）

2. **Architecture Testsで依存関係を自動検証**
   - Domain層の独立性を保証
   - Application層のインターフェース依存を検証
   - Controller層のDDD層使用を確認

3. **カバレッジ目標を明確化**
   - Domain/Application: 100%
   - Infrastructure: 95%以上
   - HTTP: 90%以上
   - 全体: 85%以上

4. **CI/CD統合で自動品質管理**
   - GitHub Actionsで全テスト実行
   - PHPStan Level 8静的解析
   - カバレッジレポート生成

5. **ベストプラクティス遵守**
   - 日本語テスト名
   - Arrange-Act-Assertパターン
   - テスト独立性
   - 異常系テスト

この戦略により、高品質で保守性の高いDDDアプリケーションを実現できます。
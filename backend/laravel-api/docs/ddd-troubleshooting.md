# DDD Troubleshooting Guide

## 目次

- [概要](#概要)
- [PSR-4オートロード問題](#psr-4オートロード問題)
- [DIコンテナ解決失敗](#diコンテナ解決失敗)
- [Architecture Tests失敗](#architecture-tests失敗)
- [テスト実行エラー](#テスト実行エラー)
- [データベース関連エラー](#データベース関連エラー)
- [Mapper変換エラー](#mapper変換エラー)
- [Domain Event関連エラー](#domain-event関連エラー)
- [パフォーマンス最適化](#パフォーマンス最適化)
- [PHPStan静的解析エラー](#phpstan静的解析エラー)

---

## 概要

本ドキュメントでは、DDDアーキテクチャ実装時によく遭遇するエラーと解決方法を記載します。

---

## PSR-4オートロード問題

### エラー1: Class not found

#### 症状

```
Error: Class "Ddd\Domain\User\ValueObjects\Email" not found
```

#### 原因

1. `composer.json`のPSR-4設定が正しくない
2. `composer dump-autoload`が実行されていない
3. ファイルパスと名前空間が一致していない

#### 解決方法

**1. composer.jsonの確認**

```json
{
  "autoload": {
    "psr-4": {
      "Ddd\\Domain\\": "ddd/Domain/",
      "Ddd\\Application\\": "ddd/Application/",
      "Ddd\\Infrastructure\\": "ddd/Infrastructure/",
      "Ddd\\Shared\\": "ddd/Shared/"
    }
  },
  "autoload-dev": {
    "psr-4": {
      "Tests\\": "tests/"
    }
  }
}
```

**2. オートロード再生成**

```bash
composer dump-autoload
```

**3. ファイルパスと名前空間の一致確認**

```
ddd/Domain/User/ValueObjects/Email.php
→ namespace Ddd\Domain\User\ValueObjects;
→ class Email
```

#### 検証方法

```bash
# オートロードが正しく生成されているか確認
cat vendor/composer/autoload_psr4.php | grep Ddd
```

---

### エラー2: Cannot declare class, because the name is already in use

#### 症状

```
Fatal error: Cannot declare class Ddd\Domain\User\ValueObjects\Email,
because the name is already in use
```

#### 原因

1. 同じクラス名が複数のファイルに存在する
2. PSR-4設定が重複している

#### 解決方法

**1. 重複クラスの検索**

```bash
find ddd/ -name "Email.php"
```

**2. composer.jsonの重複確認**

```json
// Bad: 重複設定
{
  "autoload": {
    "psr-4": {
      "Ddd\\Domain\\": "ddd/Domain/",
      "Ddd\\Domain\\": "ddd/Domain/User/" // 重複
    }
  }
}
```

**3. オートロード再生成**

```bash
composer dump-autoload
```

---

## DIコンテナ解決失敗

### エラー3: Target interface is not instantiable

#### 症状

```
Target [Ddd\Domain\User\Repositories\UserRepository] is not instantiable.
```

#### 原因

InterfaceがDIコンテナにバインドされていない。

#### 解決方法

**1. DddServiceProviderの作成**

```php
// app/Providers/DddServiceProvider.php

<?php

declare(strict_types=1);

namespace App\Providers;

use Ddd\Application\Shared\Services\EventBus\EventBus;
use Ddd\Application\Shared\Services\TransactionManager\TransactionManager;
use Ddd\Domain\User\Repositories\UserRepository;
use Ddd\Infrastructure\Persistence\Eloquent\Repositories\EloquentUserRepository;
use Ddd\Infrastructure\Services\EventBus\LaravelEventBus;
use Ddd\Infrastructure\Services\TransactionManager\LaravelTransactionManager;
use Illuminate\Support\ServiceProvider;

final class DddServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Repository bindings
        $this->app->bind(
            UserRepository::class,
            EloquentUserRepository::class
        );

        // Service bindings
        $this->app->singleton(
            TransactionManager::class,
            LaravelTransactionManager::class
        );

        $this->app->singleton(
            EventBus::class,
            LaravelEventBus::class
        );
    }
}
```

**2. config/app.phpに登録**

```php
// config/app.php

'providers' => [
    // ...
    App\Providers\DddServiceProvider::class,
],
```

**3. 確認**

```bash
php artisan tinker

>>> app(\Ddd\Domain\User\Repositories\UserRepository::class);
=> Ddd\Infrastructure\Persistence\Eloquent\Repositories\EloquentUserRepository {#...}
```

---

### エラー4: Class does not exist

#### 症状

```
Class "Ddd\Infrastructure\Persistence\Eloquent\Repositories\EloquentUserRepository" does not exist
```

#### 原因

実装クラスが存在しない、またはPSR-4オートロードが正しくない。

#### 解決方法

**1. ファイルの存在確認**

```bash
ls -la ddd/Infrastructure/Persistence/Eloquent/Repositories/EloquentUserRepository.php
```

**2. 名前空間の確認**

```php
// ddd/Infrastructure/Persistence/Eloquent/Repositories/EloquentUserRepository.php

namespace Ddd\Infrastructure\Persistence\Eloquent\Repositories; // 正しい名前空間

use Ddd\Domain\User\Repositories\UserRepository;

final readonly class EloquentUserRepository implements UserRepository
{
    // ...
}
```

**3. オートロード再生成**

```bash
composer dump-autoload
```

---

## Architecture Tests失敗

### エラー5: Expecting not to use 'Illuminate', but it uses 'Illuminate\Support\Str'

#### 症状

```
Expecting 'Ddd\Domain' not to use 'Illuminate'.
However, it also uses 'Illuminate\Support\Str' at:
- Ddd\Domain\User\ValueObjects\UserId
```

#### 原因

Domain層でLaravelの機能（Illuminate、Eloquent等）を直接使用している。

#### 解決方法

**1. Laravel機能の削除**

```php
// Bad: Domain層でIlluminate使用
namespace Ddd\Domain\User\ValueObjects;

use Illuminate\Support\Str;

final readonly class UserId
{
    public static function generate(): self
    {
        return new self(Str::uuid()->toString()); // NG
    }
}
```

```php
// Good: ramsey/uuid等を使用
namespace Ddd\Domain\User\ValueObjects;

use Ramsey\Uuid\Uuid;

final readonly class UserId
{
    public static function generate(): self
    {
        return new self(Uuid::uuid4()->toString());
    }
}
```

**2. 依存パッケージのインストール**

```bash
composer require ramsey/uuid
```

**3. テスト実行**

```bash
./vendor/bin/pest tests/Architecture
```

---

### エラー6: Expecting 'App\Http\Controllers' to only use [...], but it uses 'response'

#### 症状

```
Expecting 'App\Http\Controllers' to only use [
    'App\Http\Controllers',
    'App\Http\Requests',
    'Ddd\Application',
    'Illuminate\Http',
    'Illuminate\Routing',
].
However, it also uses 'response'.
```

#### 原因

グローバルヘルパー関数 `response()` を使用している。

#### 解決方法

**直接クラスインスタンス化に変更**

```php
// Bad: response()ヘルパー使用
use Illuminate\Http\JsonResponse;

public function register(RegisterUserRequest $request): JsonResponse
{
    // ...
    return response()->json(['id' => $output->userId->value()], 201); // NG
}
```

```php
// Good: JsonResponse直接生成
use Illuminate\Http\JsonResponse;

public function register(RegisterUserRequest $request): JsonResponse
{
    // ...
    return new JsonResponse(['id' => $output->userId->value()], 201);
}
```

---

## テスト実行エラー

### エラー7: could not find driver (Connection: pgsql)

#### 症状

```
SQLSTATE[HY000] [2002] could not find driver (Connection: pgsql, SQL: ...)
```

#### 原因

テスト実行時にPostgreSQLドライバーが見つからない。

#### 解決方法

**phpunit.xmlでSQLite in-memory使用**

```xml
<!-- phpunit.xml -->

<php>
    <env name="APP_ENV" value="testing"/>
    <env name="DB_CONNECTION" value="sqlite"/>
    <env name="DB_DATABASE" value=":memory:"/>
    <!-- ... -->
</php>
```

**確認**

```bash
./vendor/bin/pest
```

---

### エラー8: SQLSTATE[HY000]: General error: 20 datatype mismatch

#### 症状

```
SQLSTATE[HY000]: General error: 20 datatype mismatch
```

#### 原因

UUID主キーをEloquent Modelで使用する際、デフォルトの整数型として扱われている。

#### 解決方法

**1. Eloquent Modelの設定**

```php
// app/Models/User.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    public $incrementing = false; // 自動インクリメント無効
    protected $keyType = 'string'; // キー型を文字列に指定

    protected $fillable = [
        'id',
        'email',
        'name',
        'email_verified_at',
    ];
}
```

**2. Migrationの設定**

```php
// database/migrations/xxxx_create_users_table.php

Schema::create('users', function (Blueprint $table) {
    $table->uuid('id')->primary(); // UUIDを主キーに指定
    $table->string('email')->unique();
    $table->string('name');
    $table->string('password')->nullable();
    $table->timestamp('email_verified_at')->nullable();
    $table->rememberToken();
    $table->timestamps();
});
```

**3. テスト実行**

```bash
./vendor/bin/pest
```

---

### エラー9: Invalid user ID (must be UUID v4): 00000000-0000-0000-0000-000000000000

#### 症状

```
Ddd\Shared\Domain\Exceptions\ValidationException: Invalid user ID (must be UUID v4): 00000000-0000-0000-0000-000000000000
```

#### 原因

Nil UUID（全ゼロ）はUUID v4の仕様に準拠していない。

#### 解決方法

**有効なUUID v4を使用**

```php
// Bad: Nil UUID
$userId = UserId::fromString('00000000-0000-0000-0000-000000000000'); // NG

// Good: 有効なUUID v4
$userId = UserId::fromString('99999999-9999-4999-9999-999999999999'); // OK
```

**UUID v4形式**

- バージョンフィールド（9番目の文字）: `4`
- バリアントフィールド（14番目の文字）: `8`, `9`, `a`, `b`

```
xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx
              ^    ^
              |    バリアント（8/9/a/b）
              バージョン（4）
```

---

## データベース関連エラー

### エラー10: SQLSTATE[42S02]: Base table or view not found

#### 症状

```
SQLSTATE[42S02]: Base table or view not found: 1146 Table 'test.users' doesn't exist
```

#### 原因

テスト実行時にマイグレーションが実行されていない。

#### 解決方法

**RefreshDatabase traitの使用**

```php
// tests/Pest.php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->in('Feature');
```

**確認**

```bash
./vendor/bin/pest
```

---

### エラー11: N+1クエリ問題

#### 症状

複数のUserを取得する際、1件ごとにクエリが発行される（N+1問題）。

#### 解決方法

**Eager Loading使用**

```php
// Bad: N+1クエリ
public function findAll(): array
{
    $eloquentUsers = EloquentUser::all();

    return array_map(
        fn(EloquentUser $eloquentUser) => $this->mapper->toEntity($eloquentUser),
        $eloquentUsers->all()
    );
}
```

```php
// Good: Eager Loading
public function findAll(): array
{
    $eloquentUsers = EloquentUser::with(['profile', 'roles'])->get();

    return array_map(
        fn(EloquentUser $eloquentUser) => $this->mapper->toEntity($eloquentUser),
        $eloquentUsers->all()
    );
}
```

**確認**

```bash
# クエリログ有効化
DB::enableQueryLog();

// 処理実行
$users = $repository->findAll();

// クエリ数確認
dd(DB::getQueryLog());
```

---

## Mapper変換エラー

### エラー12: Cannot access private property

#### 症状

```
Error: Cannot access private property Ddd\Domain\User\Entities\User::$id
```

#### 原因

MapperでEntity/ValueObjectの`private`プロパティにアクセスしようとしている。

#### 解決方法

**Reflection APIの使用**

```php
// ddd/Infrastructure/Persistence/Eloquent/Mappers/UserMapper.php

namespace Ddd\Infrastructure\Persistence\Eloquent\Mappers;

use App\Models\User as EloquentUser;
use Ddd\Domain\User\Entities\User;
use Ddd\Domain\User\ValueObjects\Email;
use Ddd\Domain\User\ValueObjects\UserId;
use ReflectionClass;

final readonly class UserMapper
{
    public function toEntity(EloquentUser $eloquentUser): User
    {
        $reflection = new ReflectionClass(User::class);
        $instance = $reflection->newInstanceWithoutConstructor();

        // Reflection APIでprivateプロパティに値をセット
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($instance, UserId::fromString($eloquentUser->id));

        $emailProperty = $reflection->getProperty('email');
        $emailProperty->setAccessible(true);
        $emailProperty->setValue($instance, Email::fromString($eloquentUser->email));

        $nameProperty = $reflection->getProperty('name');
        $nameProperty->setAccessible(true);
        $nameProperty->setValue($instance, $eloquentUser->name);

        return $instance;
    }

    public function toModel(User $user, ?EloquentUser $eloquentUser = null): EloquentUser
    {
        $eloquentUser = $eloquentUser ?? new EloquentUser();

        $eloquentUser->id = $user->id()->value();
        $eloquentUser->email = $user->email()->value();
        $eloquentUser->name = $user->name();

        return $eloquentUser;
    }
}
```

---

### エラー13: Mapper変換のパフォーマンス問題

#### 症状

大量データ取得時にMapper変換で処理時間が増加する。

#### 原因

1件ごとのReflection API使用によるオーバーヘッド。

#### 解決方法

**1. バッチ処理の最適化**

```php
public function findAll(): array
{
    $eloquentUsers = EloquentUser::all();

    // Mapperインスタンスを再利用
    $mapper = $this->mapper;

    return array_map(
        fn(EloquentUser $eloquentUser) => $mapper->toEntity($eloquentUser),
        $eloquentUsers->all()
    );
}
```

**2. Lazy Loadingの活用**

```php
use Illuminate\Support\LazyCollection;

public function findAllLazy(): LazyCollection
{
    return EloquentUser::cursor()
        ->map(fn(EloquentUser $eloquentUser) => $this->mapper->toEntity($eloquentUser));
}
```

**3. 必要な列のみ取得**

```php
public function findAllMinimal(): array
{
    $eloquentUsers = EloquentUser::select(['id', 'email', 'name'])->get();

    return array_map(
        fn(EloquentUser $eloquentUser) => $this->mapper->toEntity($eloquentUser),
        $eloquentUsers->all()
    );
}
```

**パフォーマンス目標**

- Mapper変換オーバーヘッド: 1件あたり5ms以内
- 1000件取得時: 合計5秒以内（変換含む）

---

## Domain Event関連エラー

### エラー14: Events not dispatched

#### 症状

Domain Eventが発火されない。

#### 原因

1. `pullDomainEvents()`が呼ばれていない
2. EventBusの`dispatch()`が呼ばれていない
3. DddServiceProviderでEventBusがバインドされていない

#### 解決方法

**1. UseCaseでの正しい実装**

```php
// ddd/Application/User/UseCases/RegisterUser/RegisterUserUseCase.php

public function execute(RegisterUserInput $input): RegisterUserOutput
{
    return $this->transactionManager->run(function () use ($input): RegisterUserOutput {
        // Email重複チェック
        if ($this->userRepository->existsByEmail($email)) {
            throw new DomainException('Email already exists');
        }

        // User登録
        $user = User::register(
            id: $this->userRepository->nextId(),
            email: $email,
            name: $input->name
        );

        $this->userRepository->save($user);

        // Domain Eventの取得と発火
        $events = $user->pullDomainEvents(); // ここで取得
        foreach ($events as $event) {
            $this->eventBus->dispatch($event, afterCommit: true); // ここで発火
        }

        return new RegisterUserOutput(userId: $user->id());
    });
}
```

**2. EventBusのバインド確認**

```php
// app/Providers/DddServiceProvider.php

public function register(): void
{
    $this->app->singleton(
        EventBus::class,
        LaravelEventBus::class
    );
}
```

**3. テストでの確認**

```php
use Illuminate\Support\Facades\Event;
use Ddd\Domain\User\Events\UserRegistered;

test('dispatches user registered event', function (): void {
    Event::fake();

    $useCase = app(RegisterUserUseCase::class);
    $input = new RegisterUserInput(
        email: 'test@example.com',
        name: 'Test User'
    );

    $useCase->execute($input);

    Event::assertDispatched(UserRegistered::class);
});
```

---

### エラー15: Events dispatched before commit

#### 症状

トランザクションコミット前にDomain Eventが発火され、外部サービスが不整合状態を参照する。

#### 原因

`EventBus::dispatch()`の`afterCommit`オプションが`false`になっている。

#### 解決方法

**afterCommit: trueを指定**

```php
// Bad: トランザクションコミット前に発火
$this->eventBus->dispatch($event, afterCommit: false); // NG

// Good: トランザクションコミット後に発火
$this->eventBus->dispatch($event, afterCommit: true); // OK
```

**EventBus実装の確認**

```php
// ddd/Infrastructure/Services/EventBus/LaravelEventBus.php

final readonly class LaravelEventBus implements EventBus
{
    public function dispatch(object $event, bool $afterCommit = false): void
    {
        if ($afterCommit) {
            DB::afterCommit(fn() => $this->dispatcher->dispatch($event));
        } else {
            $this->dispatcher->dispatch($event);
        }
    }
}
```

---

## パフォーマンス最適化

### 問題16: テスト実行が遅い

#### 症状

全テスト実行に10分以上かかる。

#### 解決方法

**1. SQLite in-memoryの使用**

```xml
<!-- phpunit.xml -->

<php>
    <env name="DB_CONNECTION" value="sqlite"/>
    <env name="DB_DATABASE" value=":memory:"/>
</php>
```

**2. 並列実行の活用**

```bash
./vendor/bin/pest --parallel
```

**3. カバレッジなし実行**

```bash
# 通常実行（高速）
./vendor/bin/pest

# カバレッジあり（低速）
XDEBUG_MODE=coverage ./vendor/bin/pest --coverage
```

**4. 不要なRefreshDatabaseの削除**

```php
// Bad: 全テストでRefreshDatabase
uses(RefreshDatabase::class)->in('Unit'); // NG（Unitテストに不要）

// Good: 必要なテストのみ
uses(RefreshDatabase::class)->in('Feature'); // OK
```

**目標実行時間**

- Unit Tests: 10秒以内
- Feature Tests: 30秒以内
- Integration Tests: 1分以内
- E2E Tests: 2分以内
- **全体: 5分以内**

---

### 問題17: API応答が遅い

#### 症状

User登録APIのレスポンスタイムが500ms以上かかる。

#### 解決方法

**1. N+1クエリの解消**

```php
// with()でEager Loading
$users = EloquentUser::with(['profile', 'roles'])->get();
```

**2. 不要なDomain Event Handlerの削除**

```php
// Event Handlerで重い処理を避ける
class SendWelcomeEmail
{
    public function handle(UserRegistered $event): void
    {
        // Bad: 同期的にメール送信（遅い）
        Mail::to($event->email->value())->send(new WelcomeEmail());

        // Good: キューで非同期送信（速い）
        Mail::to($event->email->value())->queue(new WelcomeEmail());
    }
}
```

**3. データベースインデックスの追加**

```php
// Migration
Schema::table('users', function (Blueprint $table) {
    $table->index('email'); // email検索用インデックス
});
```

**4. キャッシュの活用**

```php
use Illuminate\Support\Facades\Cache;

public function find(UserId $id): ?User
{
    return Cache::remember(
        key: "user.{$id->value()}",
        ttl: 300, // 5分
        callback: fn() => $this->findFromDatabase($id)
    );
}
```

**目標レスポンスタイム**

- User登録API: 200ms以内
- User取得API: 100ms以内

---

## PHPStan静的解析エラー

### エラー18: Unable to resolve the template type

#### 症状

```
Unable to resolve the template type TCallbackReturnType in call to method
static method Illuminate\Database\Connection::transaction()
```

#### 原因

LaravelのDB::transaction()のcallable型とTransactionManager::run()のcallable型が一致しない。

#### 解決方法

**phpstan-ignoreアノテーション追加**

```php
// ddd/Infrastructure/Services/TransactionManager/LaravelTransactionManager.php

final readonly class LaravelTransactionManager implements TransactionManager
{
    public function run(callable $callback): mixed
    {
        /** @phpstan-ignore argument.type, argument.templateType */
        return DB::transaction($callback);
    }
}
```

**確認**

```bash
./vendor/bin/phpstan analyse
```

---

### エラー19: Property is never read, only written

#### 症状

```
Property Ddd\Domain\User\Entities\User::$domainEvents is never read, only written.
```

#### 原因

RecordsDomainEventsトレイトの`$domainEvents`プロパティがprivateで、外部から読み取られていない（ただし`pullDomainEvents()`で使用されている）。

#### 解決方法

**phpstan-ignoreアノテーション追加**

```php
// ddd/Shared/Domain/Traits/RecordsDomainEvents.php

trait RecordsDomainEvents
{
    /** @phpstan-ignore property.onlyWritten */
    private array $domainEvents = [];

    // ...
}
```

または

**phpstan.neonで除外**

```neon
# phpstan.neon

parameters:
    ignoreErrors:
        - '#Property .+::\$domainEvents is never read, only written\.#'
```

---

## まとめ

### トラブルシューティングのポイント

1. **PSR-4オートロード問題**
   - `composer dump-autoload`を実行
   - ファイルパスと名前空間の一致確認

2. **DIコンテナ解決失敗**
   - DddServiceProviderでInterfaceをバインド
   - config/app.phpに登録

3. **Architecture Tests失敗**
   - Domain層でLaravel機能を使用しない
   - グローバルヘルパー関数を避ける

4. **テスト実行エラー**
   - SQLite in-memoryを使用
   - UUID主キーの設定確認

5. **データベース関連エラー**
   - RefreshDatabase traitを使用
   - N+1クエリをEager Loadingで解消

6. **Mapper変換エラー**
   - Reflection APIでprivateプロパティにアクセス
   - パフォーマンス最適化（バッチ処理、Lazy Loading）

7. **Domain Event関連エラー**
   - `pullDomainEvents()`と`EventBus::dispatch()`を確認
   - `afterCommit: true`でトランザクション後に発火

8. **パフォーマンス最適化**
   - SQLite in-memory、並列実行、カバレッジなし実行
   - N+1クエリ解消、非同期処理、キャッシュ活用

9. **PHPStan静的解析エラー**
   - phpstan-ignoreアノテーション追加
   - phpstan.neonで除外設定

これらのトラブルシューティング方法を活用することで、スムーズなDDD実装が可能になります。

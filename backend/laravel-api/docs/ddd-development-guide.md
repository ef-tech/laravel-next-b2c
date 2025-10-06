# DDD開発ガイドライン

## 目次
- [新規機能開発手順](#新規機能開発手順)
- [コーディング規約](#コーディング規約)
- [命名規則](#命名規則)
- [ディレクトリ配置ルール](#ディレクトリ配置ルール)
- [実装パターン](#実装パターン)

## 新規機能開発手順

### 1. Domain層の実装（ビジネスロジック）

#### 1.1 ValueObjectの作成
```php
// ddd/Domain/{集約名}/ValueObjects/{名前}.php

declare(strict_types=1);

namespace Ddd\Domain\{集約名}\ValueObjects;

use Ddd\Shared\Exceptions\ValidationException;

final readonly class {名前}
{
    private function __construct(private string $value)
    {
        // バリデーション
        if (/* 検証条件 */) {
            throw ValidationException::invalid{名前}($value);
        }
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals({名前} $other): bool
    {
        return $this->value === $other->value;
    }
}
```

**ポイント**:
- `readonly` キーワードで不変性を保証
- Private Constructorで直接生成を防止
- Named Constructor (`fromString()`) で生成
- バリデーションをコンストラクタで実施
- `equals()` で同値性判定

#### 1.2 Entityの作成
```php
// ddd/Domain/{集約名}/Entities/{名前}.php

declare(strict_types=1);

namespace Ddd\Domain\{集約名}\Entities;

use Carbon\Carbon;
use Ddd\Shared\Traits\RecordsDomainEvents;

final class {名前}
{
    use RecordsDomainEvents;

    private function __construct(
        private {名前}Id $id,
        private /* ValueObjects... */,
        private Carbon $createdAt
    ) {}

    public static function create(/* params */): self
    {
        // ビジネスルール検証
        if (/* 条件 */) {
            throw /* Exception */;
        }

        $entity = new self(/* ... */);
        $entity->recordThat(new {名前}Created(/* ... */));
        return $entity;
    }

    // ビジネスロジックメソッド
    public function changeXxx(/* params */): void
    {
        // ビジネスルール検証
        $this->xxx = /* value */;
        $this->recordThat(new {名前}XxxChanged(/* ... */));
    }

    // Getters
    public function id(): {名前}Id { return $this->id; }
    // ...
}
```

**ポイント**:
- `RecordsDomainEvents` トレイトを使用
- Private Constructorで直接生成を防止
- Factory Method (`create()`) でビジネスルール適用
- ビジネスロジックをメソッドでカプセル化
- 状態変更時にドメインイベント記録

#### 1.3 Repository Interfaceの定義
```php
// ddd/Domain/{集約名}/Repositories/{名前}Repository.php

declare(strict_types=1);

namespace Ddd\Domain\{集約名}\Repositories;

use Ddd\Domain\{集約名}\Entities\{名前};
use Ddd\Domain\{集約名}\ValueObjects\{名前}Id;

interface {名前}Repository
{
    public function nextId(): {名前}Id;
    public function find({名前}Id $id): ?{名前};
    public function save({名前} $entity): void;
    public function delete({名前}Id $id): void;
}
```

#### 1.4 Domain Eventの作成
```php
// ddd/Domain/{集約名}/Events/{名前}{イベント}.php

declare(strict_types=1);

namespace Ddd\Domain\{集約名}\Events;

final readonly class {名前}{イベント}
{
    public function __construct(
        public {名前}Id $id,
        // その他のイベントデータ
    ) {}
}
```

### 2. Infrastructure層の実装（データ永続化）

#### 2.1 Mapperの作成
```php
// ddd/Infrastructure/Persistence/Eloquent/Mappers/{名前}Mapper.php

declare(strict_types=1);

namespace Ddd\Infrastructure\Persistence\Eloquent\Mappers;

use App\Models\{名前} as Eloquent{名前};
use Ddd\Domain\{集約名}\Entities\{名前};

final class {名前}Mapper
{
    public function toEntity(Eloquent{名前} $model): {名前}
    {
        $reflection = new \ReflectionClass({名前}::class);
        $entity = $reflection->newInstanceWithoutConstructor();

        // プロパティ設定
        $idProperty = $reflection->getProperty('id');
        $idProperty->setValue($entity, {名前}Id::fromString($model->id));
        // ...

        return $entity;
    }

    public function toModel({名前} $entity, Eloquent{名前} $model): void
    {
        $model->id = $entity->id()->value();
        // ...
    }
}
```

**ポイント**:
- Reflection APIで private constructor をバイパス
- ValueObjectへの変換を適切に実施
- Carbonオブジェクトの変換処理

#### 2.2 Repository Implementationの作成
```php
// ddd/Infrastructure/Persistence/Eloquent/Repositories/Eloquent{名前}Repository.php

declare(strict_types=1);

namespace Ddd\Infrastructure\Persistence\Eloquent\Repositories;

use App\Models\{名前} as Eloquent{名前};
use Ddd\Domain\{集約名}\Entities\{名前};
use Ddd\Domain\{集約名}\Repositories\{名前}Repository;
use Ddd\Domain\{集約名}\ValueObjects\{名前}Id;
use Illuminate\Support\Str;

final class Eloquent{名前}Repository implements {名前}Repository
{
    public function __construct(
        private {名前}Mapper $mapper
    ) {}

    public function nextId(): {名前}Id
    {
        return {名前}Id::fromString(Str::uuid()->toString());
    }

    public function find({名前}Id $id): ?{名前}
    {
        $model = Eloquent{名前}::find($id->value());
        return $model ? $this->mapper->toEntity($model) : null;
    }

    public function save({名前} $entity): void
    {
        $model = Eloquent{名前}::findOrNew($entity->id()->value());
        $this->mapper->toModel($entity, $model);
        $model->save();
    }

    public function delete({名前}Id $id): void
    {
        Eloquent{名前}::destroy($id->value());
    }
}
```

### 3. Application層の実装（ユースケース）

#### 3.1 DTOの作成
```php
// ddd/Application/{集約名}/UseCases/{ユースケース名}/{ユースケース名}Input.php

declare(strict_types=1);

namespace Ddd\Application\{集約名}\UseCases\{ユースケース名};

final readonly class {ユースケース名}Input
{
    public function __construct(
        public /* ValueObjects or primitive types */
    ) {}
}
```

```php
// ddd/Application/{集約名}/UseCases/{ユースケース名}/{ユースケース名}Output.php

declare(strict_types=1);

namespace Ddd\Application\{集約名}\UseCases\{ユースケース名};

final readonly class {ユースケース名}Output
{
    public function __construct(
        public {名前}Id $id
    ) {}
}
```

#### 3.2 UseCaseの作成
```php
// ddd/Application/{集約名}/UseCases/{ユースケース名}/{ユースケース名}UseCase.php

declare(strict_types=1);

namespace Ddd\Application\{集約名}\UseCases\{ユースケース名};

use Ddd\Application\Shared\Services\Events\EventBus;
use Ddd\Application\Shared\Services\TransactionManager\TransactionManager;
use Ddd\Domain\{集約名}\Repositories\{名前}Repository;

final readonly class {ユースケース名}UseCase
{
    public function __construct(
        private {名前}Repository $repository,
        private TransactionManager $transactionManager,
        private EventBus $eventBus
    ) {}

    public function execute({ユースケース名}Input $input): {ユースケース名}Output
    {
        // ビジネスルールチェック
        if (/* 条件 */) {
            throw /* DomainException */;
        }

        // トランザクション実行
        $id = $this->transactionManager->run(function () use ($input) {
            // ドメインロジック実行
            $entity = {名前}::create(/* ... */);
            $this->repository->save($entity);

            // ドメインイベント発火
            foreach ($entity->pullDomainEvents() as $event) {
                $this->eventBus->dispatch($event, afterCommit: true);
            }

            return $entity->id();
        });

        return new {ユースケース名}Output($id);
    }
}
```

### 4. HTTP層の実装（Controller）

#### 4.1 Request Validationの作成
```php
// app/Http/Requests/{ユースケース名}Request.php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class {ユースケース名}Request extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'field' => ['required', 'string', 'max:255'],
            // ...
        ];
    }
}
```

#### 4.2 Controllerの作成
```php
// app/Http/Controllers/Api/{名前}Controller.php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\{ユースケース名}Request;
use Ddd\Application\{集約名}\UseCases\{ユースケース名}\{ユースケース名}Input;
use Ddd\Application\{集約名}\UseCases\{ユースケース名}\{ユースケース名}UseCase;
use Illuminate\Http\JsonResponse;

final class {名前}Controller extends Controller
{
    public function __construct(
        private readonly {ユースケース名}UseCase $useCase
    ) {}

    public function {メソッド名}({ユースケース名}Request $request): JsonResponse
    {
        $input = new {ユースケース名}Input(/* ... */);
        $output = $this->useCase->execute($input);

        return new JsonResponse([
            'id' => $output->id->value(),
        ], 201);
    }
}
```

### 5. DI Containerへの登録
```php
// app/Providers/DddServiceProvider.php

public function register(): void
{
    // Repository bindings
    $this->app->bind(
        \Ddd\Domain\{集約名}\Repositories\{名前}Repository::class,
        \Ddd\Infrastructure\Persistence\Eloquent\Repositories\Eloquent{名前}Repository::class
    );
}
```

### 6. Routesへの登録
```php
// routes/api.php

use App\Http\Controllers\Api\{名前}Controller;

Route::post('/{エンドポイント}', [{名前}Controller::class, '{メソッド名}']);
```

## コーディング規約

### Carbon使用ルール
- **Domain層**: Carbon使用可（Steering規約で許可）
- **日時生成**: `Carbon::now()` を使用
- **日時比較**: Carbonメソッドを活用

```php
// OK
private Carbon $registeredAt;
$user = new self($id, $email, $name, Carbon::now());

// NG (DateTimeImmutableは使用しない)
private DateTimeImmutable $registeredAt;
```

### Enum活用
PHP 8.1+ Enumを積極的に活用

```php
enum UserStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Suspended = 'suspended';
}

final class User
{
    private function __construct(
        private UserId $id,
        private UserStatus $status
    ) {}
}
```

### 配列 vs Entity
- **Domain層**: Entityオブジェクトで扱う
- **HTTP層**: 配列で受け取り、ValueObject/DTOに変換

```php
// Controller (配列で受け取る)
public function register(RegisterUserRequest $request): JsonResponse
{
    $input = new RegisterUserInput(
        email: Email::fromString($request->input('email')),
        name: $request->input('name')
    );
    // ...
}

// UseCase (Entityで扱う)
public function execute(RegisterUserInput $input): RegisterUserOutput
{
    $user = User::register($input->email, $input->name);
    // ...
}
```

### 例外処理
- **Domain Exception**: ビジネスルール違反
- **Application Exception**: ユースケース固有のエラー

```php
// Domain Exception
throw ValidationException::invalidEmail($value);
throw EmailAlreadyExistsException::forEmail($email);

// Application Exception (必要に応じて)
throw UserNotFoundException::forId($id);
```

## 命名規則

### クラス名
- **ValueObject**: 名詞単数形 (`Email`, `UserId`)
- **Entity**: 名詞単数形 (`User`, `Product`)
- **Repository Interface**: `{Entity名}Repository`
- **Repository Implementation**: `Eloquent{Entity名}Repository`
- **UseCase**: `{動詞}{Entity名}UseCase` (`RegisterUserUseCase`)
- **DTO**: `{UseCase名}Input/Output`
- **Domain Event**: `{Entity名}{過去分詞}` (`UserRegistered`)
- **Mapper**: `{Entity名}Mapper`

### メソッド名
- **Factory Method**: `create`, `register`, `from{Type}`
- **ビジネスロジック**: 動詞+名詞 (`changeName`, `activate`)
- **Repository**: CRUD操作 (`find`, `save`, `delete`)
- **UseCase**: `execute`
- **Getter**: プロパティ名そのまま (`id()`, `email()`)

### ファイル名
- **1クラス1ファイル**: クラス名と同じファイル名
- **PSR-4準拠**: 名前空間とディレクトリ構造を一致

## ディレクトリ配置ルール

### Domain層
```
ddd/Domain/{集約名}/
├── Entities/         # エンティティ
├── ValueObjects/     # 値オブジェクト
├── Repositories/     # リポジトリインターフェース
├── Events/           # ドメインイベント
└── Services/         # ドメインサービス（必要に応じて）
```

### Application層
```
ddd/Application/{集約名}/
├── UseCases/
│   └── {ユースケース名}/
│       ├── {ユースケース名}UseCase.php
│       ├── {ユースケース名}Input.php
│       └── {ユースケース名}Output.php
├── Queries/          # CQRS クエリ（必要に応じて）
└── Exceptions/       # アプリケーション例外
```

### Infrastructure層
```
ddd/Infrastructure/
├── Persistence/
│   └── Eloquent/
│       ├── Repositories/  # リポジトリ実装
│       └── Mappers/        # Mapper実装
└── Services/              # 外部サービス実装
    ├── TransactionManager/
    └── Events/
```

### Shared層
```
ddd/Shared/
├── Exceptions/       # 基底例外
└── Traits/           # 共通トレイト
```

## 実装パターン

### パターン1: UUID主キー設定
```php
// 1. Migration
Schema::create('users', function (Blueprint $table) {
    $table->uuid('id')->primary();
    // ...
});

// 2. Eloquent Model
public $incrementing = false;
protected $keyType = 'string';

// 3. Repository
public function nextId(): UserId
{
    return UserId::fromString(Str::uuid()->toString());
}
```

### パターン2: ドメインイベントの記録と発火
```php
// 1. Entity
final class User
{
    use RecordsDomainEvents;

    public static function register(/* ... */): self
    {
        $user = new self(/* ... */);
        $user->recordThat(new UserRegistered(/* ... */));
        return $user;
    }
}

// 2. UseCase
public function execute(RegisterUserInput $input): RegisterUserOutput
{
    $user = User::register(/* ... */);
    $this->repository->save($user);

    foreach ($user->pullDomainEvents() as $event) {
        $this->eventBus->dispatch($event, afterCommit: true);
    }
}
```

### パターン3: トランザクション管理
```php
$result = $this->transactionManager->run(function () use ($input) {
    // トランザクション内の処理
    $entity = Entity::create(/* ... */);
    $this->repository->save($entity);

    // イベント発火（コミット後）
    foreach ($entity->pullDomainEvents() as $event) {
        $this->eventBus->dispatch($event, afterCommit: true);
    }

    return $entity->id();
});
```

### パターン4: バリデーション戦略
```php
// 1. フォーマットバリデーション（ValueObject）
final readonly class Email
{
    private function __construct(private string $value)
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::invalidEmail($value);
        }
    }
}

// 2. ビジネスルールバリデーション（Entity）
public static function register(/* ... */): self
{
    if (strlen($name) < 2) {
        throw ValidationException::invalidName('Name must be at least 2 characters');
    }
    // ...
}

// 3. 一意性チェック（UseCase）
if ($this->repository->existsByEmail($input->email)) {
    throw EmailAlreadyExistsException::forEmail($input->email->value());
}
```

## チェックリスト

新規機能開発時のチェックリスト：

- [ ] Domain層
  - [ ] ValueObject作成（バリデーション実装）
  - [ ] Entity作成（Factory Method、ビジネスロジック）
  - [ ] Repository Interface定義
  - [ ] Domain Event作成
  - [ ] Domain Unit Tests作成

- [ ] Infrastructure層
  - [ ] Mapper作成
  - [ ] Repository Implementation作成
  - [ ] Infrastructure Integration Tests作成

- [ ] Application層
  - [ ] DTO作成（Input/Output）
  - [ ] UseCase作成（トランザクション、イベント発火）

- [ ] HTTP層
  - [ ] Request Validation作成
  - [ ] Controller作成（thin adapter）
  - [ ] Routes登録
  - [ ] E2E Tests作成

- [ ] DI Container
  - [ ] DddServiceProvider に登録

- [ ] 品質チェック
  - [ ] Laravel Pint実行 (`composer pint`)
  - [ ] PHPStan Level 8実行 (`composer stan`)
  - [ ] Architecture Tests実行 (`./vendor/bin/pest tests/Architecture/`)
  - [ ] 全テスト実行 (`./vendor/bin/pest`)

## まとめ

- **開発順序**: Domain → Infrastructure → Application → HTTP
- **テスト駆動**: 各層で独立したテスト作成
- **命名規則**: 一貫性のある命名
- **ディレクトリ配置**: PSR-4準拠の明確な配置
- **品質チェック**: Pint + PHPStan + Architecture Tests

詳細は以下のドキュメントを参照してください：
- [アーキテクチャ概要](./ddd-architecture.md)
- [テスト戦略](./ddd-testing-strategy.md)
- [トラブルシューティング](./ddd-troubleshooting.md)

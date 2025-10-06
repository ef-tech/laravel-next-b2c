# DDD/クリーンアーキテクチャ 概要

## 目次
- [アーキテクチャ概要](#アーキテクチャ概要)
- [4層構造の説明](#4層構造の説明)
- [依存方向ルール](#依存方向ルール)
- [主要パターン](#主要パターン)
- [既存MVCとDDD層の共存戦略](#既存mvcとddd層の共存戦略)

## アーキテクチャ概要

本プロジェクトでは、DDD (Domain-Driven Design)、クリーンアーキテクチャ、SOLID原則を導入し、以下の4層構造でコードを整理しています。

```
┌─────────────────────────────────────────────────┐
│           HTTP Layer (Interface)                │
│  ┌────────────────────────────────────────────┐ │
│  │ Controllers / Requests / Resources         │ │
│  └────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────┘
                      ▼ 依存
┌─────────────────────────────────────────────────┐
│       Application Layer (Use Cases)             │
│  ┌────────────────────────────────────────────┐ │
│  │ UseCases / DTOs / Queries                  │ │
│  │ Services Interfaces                        │ │
│  └────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────┘
                      ▼ 依存
┌─────────────────────────────────────────────────┐
│         Domain Layer (Business Logic)           │
│  ┌────────────────────────────────────────────┐ │
│  │ Entities / ValueObjects / Domain Events    │ │
│  │ Repository Interfaces                      │ │
│  │ Domain Services                            │ │
│  └────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────┘
                      ▲ 実装
┌─────────────────────────────────────────────────┐
│    Infrastructure Layer (External Systems)      │
│  ┌────────────────────────────────────────────┐ │
│  │ Repository Implementations (Eloquent)      │ │
│  │ External Services (DB, Mail, Storage)      │ │
│  │ Framework-specific Code (Laravel)          │ │
│  └────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────┘
```

## 4層構造の説明

### 1. Domain層 (`ddd/Domain/`)
**責務**: ビジネスロジックの中核

- **Entities**: ビジネスエンティティ（集約ルート）
- **ValueObjects**: 不変な値オブジェクト（Email、UserId等）
- **Repository Interfaces**: データ永続化のインターフェース定義
- **Domain Events**: ドメインイベント定義
- **Domain Services**: ドメインロジックのサービス

**特徴**:
- Laravelフレームワークに依存しない（Carbon除く）
- ビジネスルールを純粋なPHPで表現
- 他の層から独立してテスト可能

**例**:
```php
// ddd/Domain/User/Entities/User.php
final class User
{
    use RecordsDomainEvents;

    public static function register(UserId $id, Email $email, string $name): self
    {
        // ビジネスルール検証
        if (strlen($name) < 2) {
            throw ValidationException::invalidName('Name must be at least 2 characters');
        }

        $user = new self($id, $email, $name, Carbon::now());
        $user->recordThat(new UserRegistered($id, $email, $name));
        return $user;
    }
}
```

### 2. Application層 (`ddd/Application/`)
**責務**: ユースケースの実装とオーケストレーション

- **UseCases**: アプリケーション固有のビジネスフロー
- **DTOs (Input/Output)**: ユースケースの入出力データ構造
- **Service Interfaces**: 外部サービスのインターフェース定義（TransactionManager、EventBus等）
- **Queries**: データ取得クエリ（CQRS）
- **Application Exceptions**: アプリケーション層の例外

**特徴**:
- Infrastructure層に依存しない（依存性逆転）
- トランザクション境界を管理
- ドメインイベントのディスパッチ

**例**:
```php
// ddd/Application/User/UseCases/RegisterUser/RegisterUserUseCase.php
final readonly class RegisterUserUseCase
{
    public function __construct(
        private UserRepository $userRepository,
        private TransactionManager $transactionManager,
        private EventBus $eventBus
    ) {}

    public function execute(RegisterUserInput $input): RegisterUserOutput
    {
        // Email重複チェック
        if ($this->userRepository->existsByEmail($input->email)) {
            throw EmailAlreadyExistsException::forEmail($input->email->value());
        }

        // トランザクション実行
        $userId = $this->transactionManager->run(function () use ($input) {
            $id = $this->userRepository->nextId();
            $user = User::register($id, $input->email, $input->name);
            $this->userRepository->save($user);

            // ドメインイベント発火
            foreach ($user->pullDomainEvents() as $event) {
                $this->eventBus->dispatch($event, afterCommit: true);
            }

            return $id;
        });

        return new RegisterUserOutput($userId);
    }
}
```

### 3. Infrastructure層 (`ddd/Infrastructure/`)
**責務**: 外部システムとの統合

- **Repository Implementations**: Eloquentを使用したリポジトリ実装
- **Mappers**: Eloquent Model ↔ Domain Entity 変換
- **External Services**: Laravel固有サービスの実装（TransactionManager、EventBus等）
- **Framework-specific Code**: Laravelフレームワーク依存コード

**特徴**:
- Domain/Application層のインターフェースを実装
- Laravelフレームワークへの依存を集約
- データ永続化の詳細を隠蔽

**例**:
```php
// ddd/Infrastructure/Persistence/Eloquent/Repositories/EloquentUserRepository.php
final class EloquentUserRepository implements UserRepository
{
    public function save(User $user): void
    {
        $model = EloquentUser::findOrNew($user->id()->value());
        $this->mapper->toModel($user, $model);
        $model->save();
    }

    public function find(UserId $id): ?User
    {
        $model = EloquentUser::find($id->value());
        return $model ? $this->mapper->toEntity($model) : null;
    }
}
```

### 4. Shared層 (`ddd/Shared/`)
**責務**: 複数層で共有される基盤コンポーネント

- **Exceptions**: 基底例外クラス
- **Traits**: 共通トレイト（RecordsDomainEvents等）
- **Interfaces**: 共通インターフェース

## 依存方向ルール

**厳格な依存方向（依存性逆転原則）**:

```
Infrastructure → Application → Domain
       ↑              ↑           ↑
       │              │           │
    実装のみ      インターフェース  ビジネスロジック
```

**禁止事項**:
- ❌ Domain層がInfrastructure層に依存
- ❌ Domain層がApplication層に依存
- ❌ Application層がInfrastructure層に依存

**Architecture Testsで自動検証**:
```php
// tests/Architecture/DddArchitectureTest.php
arch('Domain layer must not depend on Infrastructure')
    ->expect('Ddd\Domain')
    ->not->toUse('Ddd\Infrastructure');

arch('Application layer must not depend on Infrastructure')
    ->expect('Ddd\Application')
    ->not->toUse('Ddd\Infrastructure');
```

## 主要パターン

### 1. ValueObject パターン
**不変な値オブジェクト**

```php
final readonly class Email
{
    private function __construct(private string $value)
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::invalidEmail($value);
        }
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function value(): string { return $this->value; }

    public function equals(Email $other): bool
    {
        return $this->value === $other->value;
    }
}
```

**特徴**:
- `readonly` キーワードで不変性を保証
- Named Constructorパターン（`fromString()`）
- バリデーションをコンストラクタで実施
- `equals()` で同値性判定

### 2. Entity / Aggregate Root パターン
**ビジネスエンティティ**

```php
final class User
{
    use RecordsDomainEvents;

    private function __construct(
        private UserId $id,
        private Email $email,
        private string $name,
        private Carbon $registeredAt
    ) {}

    public static function register(UserId $id, Email $email, string $name): self
    {
        // Factory Method + Domain Event Recording
        $user = new self($id, $email, $name, Carbon::now());
        $user->recordThat(new UserRegistered($id, $email, $name));
        return $user;
    }

    public function changeName(string $newName): void
    {
        // Business Rule Validation
        if (strlen($newName) < 2) {
            throw ValidationException::invalidName('Name must be at least 2 characters');
        }
        $this->name = $newName;
    }
}
```

**特徴**:
- Private Constructorで直接生成を防止
- Factory Methodでビジネスルール適用
- ドメインイベント記録（`RecordsDomainEvents` trait）
- ビジネスロジックをメソッドでカプセル化

### 3. Repository パターン
**データ永続化の抽象化**

**Interface (Domain層)**:
```php
interface UserRepository
{
    public function nextId(): UserId;
    public function find(UserId $id): ?User;
    public function findByEmail(Email $email): ?User;
    public function existsByEmail(Email $email): bool;
    public function save(User $user): void;
    public function delete(UserId $id): void;
}
```

**Implementation (Infrastructure層)**:
```php
final class EloquentUserRepository implements UserRepository
{
    public function __construct(private UserMapper $mapper) {}

    public function save(User $user): void
    {
        $model = EloquentUser::findOrNew($user->id()->value());
        $this->mapper->toModel($user, $model);
        $model->save();
    }
}
```

### 4. UseCase パターン
**アプリケーションフロー**

```php
final readonly class RegisterUserUseCase
{
    public function execute(RegisterUserInput $input): RegisterUserOutput
    {
        // 1. Business Rule Check
        if ($this->userRepository->existsByEmail($input->email)) {
            throw EmailAlreadyExistsException::forEmail($input->email->value());
        }

        // 2. Transaction Boundary
        $userId = $this->transactionManager->run(function () use ($input) {
            // 3. Domain Logic
            $user = User::register(/* ... */);
            $this->userRepository->save($user);

            // 4. Domain Event Dispatch
            foreach ($user->pullDomainEvents() as $event) {
                $this->eventBus->dispatch($event, afterCommit: true);
            }

            return $user->id();
        });

        return new RegisterUserOutput($userId);
    }
}
```

### 5. Mapper パターン
**Eloquent ↔ Domain Entity 変換**

```php
final class UserMapper
{
    public function toEntity(EloquentUser $model): User
    {
        // Reflection API で private constructor をバイパス
        $reflection = new \ReflectionClass(User::class);
        $entity = $reflection->newInstanceWithoutConstructor();

        // プロパティ設定
        $idProperty = $reflection->getProperty('id');
        $idProperty->setValue($entity, UserId::fromString($model->id));

        // ...

        return $entity;
    }

    public function toModel(User $entity, EloquentUser $model): void
    {
        $model->id = $entity->id()->value();
        $model->email = $entity->email()->value();
        $model->name = $entity->name();
        $model->created_at = $entity->registeredAt();
    }
}
```

## 既存MVCとDDD層の共存戦略

### Stranglerパターンによる段階的移行

```
┌─────────────────────────────────────────┐
│        既存Laravel MVC構造               │
│  ┌───────────────────────────────────┐  │
│  │ app/Http/Controllers/             │  │
│  │ app/Models/ (Eloquent)            │  │
│  │ app/Services/                     │  │
│  └───────────────────────────────────┘  │
└─────────────────────────────────────────┘
              共存 ⬇
┌─────────────────────────────────────────┐
│        新規DDD構造                       │
│  ┌───────────────────────────────────┐  │
│  │ ddd/Domain/                       │  │
│  │ ddd/Application/                  │  │
│  │ ddd/Infrastructure/               │  │
│  └───────────────────────────────────┘  │
└─────────────────────────────────────────┘
```

### 移行戦略

**1. 新規機能はDDD構造で実装**
```php
// 新規機能: DDD UseCase
Route::post('/api/users', [UserController::class, 'register']);

class UserController extends Controller
{
    public function __construct(
        private readonly RegisterUserUseCase $registerUserUseCase
    ) {}

    public function register(RegisterUserRequest $request): JsonResponse
    {
        $input = new RegisterUserInput(/* ... */);
        $output = $this->registerUserUseCase->execute($input);
        return new JsonResponse(['id' => $output->userId->value()], 201);
    }
}
```

**2. 既存機能は触らない**
```php
// 既存機能: 従来のMVC
Route::get('/api/products', [ProductController::class, 'index']);

class ProductController extends Controller
{
    public function index()
    {
        // 既存のEloquent直接利用コード
        $products = Product::paginate(20);
        return ProductResource::collection($products);
    }
}
```

**3. 段階的にDDD化**
- 優先度: 高頻度API、ビジネスロジック複雑なAPI
- 移行単位: 1機能ずつ
- リグレッションテスト必須

### DI Container設定

```php
// app/Providers/DddServiceProvider.php
final class DddServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Repository bindings
        $this->app->bind(
            \Ddd\Domain\User\Repositories\UserRepository::class,
            \Ddd\Infrastructure\Persistence\Eloquent\Repositories\EloquentUserRepository::class
        );

        // Infrastructure services (singleton)
        $this->app->singleton(
            \Ddd\Application\Shared\Services\TransactionManager\TransactionManager::class,
            \Ddd\Infrastructure\Services\TransactionManager\LaravelTransactionManager::class
        );

        $this->app->singleton(
            \Ddd\Application\Shared\Services\Events\EventBus::class,
            \Ddd\Infrastructure\Services\Events\LaravelEventBus::class
        );
    }
}
```

## ディレクトリ構成

```
backend/laravel-api/
├── app/                          # 既存Laravel MVC
│   ├── Http/Controllers/         # MVCコントローラー（既存）
│   ├── Models/                   # Eloquent Models
│   └── Providers/
│       └── DddServiceProvider.php  # DDD DI設定
├── ddd/                          # 新規DDD構造
│   ├── Domain/                   # Domain層
│   │   └── User/
│   │       ├── Entities/         # User.php
│   │       ├── ValueObjects/     # Email.php, UserId.php
│   │       ├── Repositories/     # UserRepository.php (Interface)
│   │       └── Events/           # UserRegistered.php
│   ├── Application/              # Application層
│   │   ├── User/
│   │   │   └── UseCases/
│   │   │       └── RegisterUser/
│   │   │           ├── RegisterUserUseCase.php
│   │   │           ├── RegisterUserInput.php
│   │   │           └── RegisterUserOutput.php
│   │   └── Shared/Services/      # Service Interfaces
│   │       ├── TransactionManager/
│   │       └── Events/
│   ├── Infrastructure/           # Infrastructure層
│   │   ├── Persistence/Eloquent/
│   │   │   ├── Repositories/     # EloquentUserRepository.php
│   │   │   └── Mappers/          # UserMapper.php
│   │   └── Services/             # Service Implementations
│   │       ├── TransactionManager/
│   │       └── Events/
│   └── Shared/                   # 共通コンポーネント
│       ├── Exceptions/           # DomainException.php
│       └── Traits/               # RecordsDomainEvents.php
└── tests/
    ├── Unit/Ddd/Domain/          # Domain Unit Tests
    ├── Feature/Ddd/Infrastructure/ # Infrastructure Integration Tests
    ├── Feature/Http/Controllers/  # E2E Tests
    └── Architecture/              # Architecture Tests
```

## まとめ

- **4層構造**: Domain → Application → Infrastructure の依存方向
- **依存性逆転**: Infrastructure層がDomain層のインターフェースを実装
- **主要パターン**: ValueObject、Entity、Repository、UseCase、Mapper
- **段階的移行**: Stranglerパターンで既存MVCと共存
- **テスト戦略**: 各層で独立したテスト（Unit/Integration/E2E/Architecture）

詳細は以下のドキュメントを参照してください：
- [開発ガイドライン](./ddd-development-guide.md)
- [テスト戦略](./ddd-testing-strategy.md)
- [トラブルシューティング](./ddd-troubleshooting.md)

# Requirements Document

## はじめに

本要件定義は、Laravel 12プロジェクトにDDD（ドメイン駆動設計）、クリーンアーキテクチャ、SOLID原則を導入し、ビジネスロジックの明確化、テスタビリティ向上、保守性向上、チーム開発効率化を実現するためのものです。

### 背景

現在のLaravel 12プロジェクトは標準的なMVCアーキテクチャで構築されており、ビジネスロジックの複雑化に伴い以下の課題が顕在化しています：

- ビジネスロジックがController/Model層に分散
- テスタビリティの低下（Laravel依存による単体テスト困難）
- ドメイン知識の暗黙化（コード構造からビジネスルールが読み取りにくい）
- 変更影響範囲の拡大（疎結合性の欠如）

### ビジネス価値

DDD/クリーンアーキテクチャ/SOLID導入により以下のビジネス価値を実現します：

- **開発速度向上**: レイヤー境界の明確化による並行開発の促進（初期3ヶ月後に従来レベル回復、12ヶ月後に30%向上）
- **品質向上**: ドメインロジックバグ50%削減、リグレッションバグ70%削減
- **保守性向上**: コードレビュー時間50%削減、変更影響範囲の局所化
- **テスト効率化**: フレームワーク非依存のDomain層でユニットテスト高速化

---

## Requirements

### Requirement 1: アーキテクチャ基盤構築

**目的:** As a 開発チーム, I want DDD/クリーンアーキテクチャの4層構造を確立する, so that ビジネスロジックを明確に分離し、依存方向を制御できる

#### Acceptance Criteria

1. WHEN プロジェクトルート`backend/laravel-api/`配下に`ddd/`ディレクトリを作成する THEN Laravel API SHALL 4層構造（Domain/Application/Infrastructure/Shared）のディレクトリ階層を持つ
2. WHEN `composer.json`にPSR-4オートロード設定を追加する THEN Laravel API SHALL `Ddd\Domain\`, `Ddd\Application\`, `Ddd\Infrastructure\`, `Ddd\Shared\`名前空間をサポートする
3. WHEN `DddServiceProvider`を作成する THEN Laravel API SHALL DI Container経由でDDD層のクラスをオートロードできる
4. WHERE 依存方向が`Infrastructure → Application → Domain`および`Infrastructure/Application/Domain → Shared`である THE Laravel API SHALL 各層の依存方向ルールに従う
5. IF Domain層がLaravel固有の機能（Eloquent、Facades等）に依存する THEN Architecture Tests SHALL テスト失敗を報告する

### Requirement 2: Domain層設計と実装

**目的:** As a 開発者, I want Laravel非依存の純粋なビジネスロジックをDomain層に実装する, so that フレームワークに依存しない高速なユニットテストが可能になる

#### Acceptance Criteria

1. WHEN ValueObject（値オブジェクト）を実装する THEN Domain層 SHALL `readonly`キーワードで不変性を保証し、named constructorパターン（`fromString`等）を提供する
2. WHEN Entity（エンティティ）またはAggregate Root（集約ルート）を実装する THEN Domain層 SHALL private constructorで生成を制御し、ファクトリメソッドで不変条件を保証する
3. WHEN Repository Interface（リポジトリ契約）を定義する THEN Domain層 SHALL 集約ルート単位でインターフェースを作成し、戻り値/引数は常にDomainオブジェクトを使用する
4. IF ビジネスルール検証が必要な場合 THEN Domain Services SHALL 複数の集約をまたぐビジネスロジックを実装する
5. WHEN Domain Eventsを記録する THEN Entity SHALL `RecordsDomainEvents` Traitを使用し、イベントを内部コレクションに保持する
6. WHERE Domain層のコードが`Illuminate\*`名前空間をimportする THE Architecture Tests SHALL テスト失敗を報告する

### Requirement 3: Repository Pattern実装

**目的:** As a 開発者, I want Repository Patternでデータ永続化を抽象化する, so that Domain層がデータベース実装詳細に依存しなくなる

#### Acceptance Criteria

1. WHEN Repository Interfaceを`ddd/Domain/*/Repositories/`に定義する THEN Repository Interface SHALL 集約ルート単位で`find`, `save`, `delete`メソッドを提供する
2. WHEN Eloquent Repository実装を作成する THEN Infrastructure層 SHALL `ddd/Infrastructure/Persistence/Eloquent/Repositories/`にRepository Interface実装を配置する
3. WHEN Eloquent Modelを使用する THEN Infrastructure層 SHALL `app/Models/`配下のEloquent Modelを`use App\Models\User as EloquentUser`形式で参照する
4. IF Eloquent Model（`app/Models/User.php`）とDomain Entity（`ddd/Domain/User/Entities/User.php`）が存在する THEN Infrastructure層 SHALL Mapperクラスで相互変換を提供する
5. WHEN Mapperを実装する THEN Infrastructure層 SHALL `toEntity(EloquentModel): DomainEntity`および`toModel(DomainEntity, EloquentModel): void`メソッドを提供する

### Requirement 4: Application層（UseCase）実装

**目的:** As a 開発者, I want UseCase層でトランザクション境界とビジネスフローを管理する, so that 集約のオーケストレーションとイベント発火を一元管理できる

#### Acceptance Criteria

1. WHEN UseCaseを実装する THEN Application層 SHALL `ddd/Application/*/UseCases/`配下に機能別ディレクトリ（`RegisterUser/`, `GetUser/`等）を作成する
2. WHEN UseCaseクラスを作成する THEN Application層 SHALL `execute(Input): Output`メソッドを提供し、Input/Output DTOで入出力を定義する
3. WHEN UseCase内でトランザクションが必要な場合 THEN UseCase SHALL `TransactionManager::run(callable)`でトランザクション境界を管理する
4. WHEN Domain Eventsを発火する THEN UseCase SHALL `$entity->pullDomainEvents()`でイベントを取り出し、`EventBus::dispatch($event, afterCommit: true)`でコミット後発火する
5. IF 複数の集約を更新する場合 THEN UseCase SHALL 単一トランザクション内で全ての集約を保存する

### Requirement 5: Infrastructure層サービス実装

**目的:** As a 開発者, I want Laravel固有機能をInfrastructure層に隔離する, so that Domain/Application層がフレームワーク非依存を維持できる

#### Acceptance Criteria

1. WHEN TransactionManagerを実装する THEN Infrastructure層 SHALL `LaravelTransactionManager`で`DB::transaction()`をラップする
2. WHEN EventBusを実装する THEN Infrastructure層 SHALL `LaravelEventBus`で`Dispatcher::dispatch()`をラップし、`afterCommit`オプションをサポートする
3. IF Sanctum認証統合が必要な場合 THEN Infrastructure層 SHALL `AuthTokenIssuer`ポートをApplication層に定義し、`SanctumTokenIssuer`実装をInfrastructure層に配置する
4. WHEN 外部サービス（Queue、Cache、Mail等）と統合する THEN Infrastructure層 SHALL Application層のインターフェース定義に従い、Laravel機能をラップする

### Requirement 6: Dependency Injection設定

**目的:** As a 開発者, I want DI Containerで依存関係を自動解決する, so that 手動でのインスタンス生成を排除できる

#### Acceptance Criteria

1. WHEN `DddServiceProvider`を作成する THEN ServiceProvider SHALL `app/Providers/DddServiceProvider.php`に配置し、`config/app.php`の`providers`配列に登録する
2. WHEN Repository Interfaceをバインドする THEN DddServiceProvider SHALL `$this->app->bind(UserRepository::class, EloquentUserRepository::class)`形式でバインドする
3. WHEN TransactionManager/EventBusをバインドする THEN DddServiceProvider SHALL `$this->app->singleton()`でシングルトン登録する
4. WHEN Controllerでインジェクションする THEN Laravel API SHALL コンストラクタインジェクションで自動解決する

### Requirement 7: Interface Adapters（Controller/Request）実装

**目的:** As a 開発者, I want ControllerをUseCaseの薄いアダプター層として実装する, so that HTTP層とビジネスロジックを分離できる

#### Acceptance Criteria

1. WHEN Controllerを実装する THEN Controller SHALL UseCaseクラスをコンストラクタインジェクションで受け取る
2. WHEN HTTP Requestを処理する THEN Controller SHALL Laravel Request Validationで検証後、Input DTOに変換してUseCaseに渡す
3. WHEN UseCaseの実行結果を返す THEN Controller SHALL Output DTOをHTTP Response（JsonResponse等）に変換する
4. IF Domain/Application Exceptionが発生する THEN ExceptionHandler SHALL 適切なHTTPステータスコード（400/404/422/500等）に変換する
5. WHERE Controllerが直接Eloquent Modelやビジネスロジックを持つ THE Architecture Tests SHALL テスト失敗を報告する

### Requirement 8: テスト戦略実装

**目的:** As a 開発チーム, I want 各層に適したテスト戦略を確立する, so that カバレッジ85%以上を達成し、リグレッション防止を実現できる

#### Acceptance Criteria

1. WHEN Domain層のUnit Testsを実装する THEN Tests SHALL `tests/Unit/Ddd/Domain/`配下に配置し、Laravel機能を使用せず高速実行する
2. WHEN Application層のFeature Testsを実装する THEN Tests SHALL `tests/Feature/Ddd/Application/`配下に配置し、Mockeryでリポジトリをモック化する
3. WHEN Infrastructure層のIntegration Testsを実装する THEN Tests SHALL 実データベースを使用し、Eloquent ↔ Domain Entity変換を検証する
4. WHEN Architecture Testsを実装する THEN Tests SHALL `tests/Architecture/DddArchitectureTest.php`で依存関係ルール（Domain層がIlluminate/Eloquent/Facadesに依存しない等）を検証する
5. IF テストカバレッジが85%未満の場合 THEN CI/CD SHALL ビルド失敗を報告する
6. WHEN カバレッジレポートを生成する THEN Tests SHALL Domain層90%以上、Application層85%以上、Infrastructure層80%以上を目標とする

### Requirement 9: ドキュメント整備と移行戦略

**目的:** As a 開発チーム, I want 包括的なドキュメントと段階的移行計画を策定する, so that チーム全員がDDD実装可能になり、既存コードの安全な移行を実現できる

#### Acceptance Criteria

1. WHEN アーキテクチャ概要ドキュメントを作成する THEN ドキュメント SHALL 4層構造、依存方向ルール、主要パターン（ValueObject/Entity/Repository/UseCase）を説明する
2. WHEN 開発ガイドラインを作成する THEN ドキュメント SHALL 新規機能開発時のDDD実装手順、コーディング規約、命名規則を記載する
3. WHEN テスト戦略ドキュメントを作成する THEN ドキュメント SHALL 各層のテスト方針、テストヘルパー使用方法、カバレッジ目標を記載する
4. WHEN トラブルシューティングガイドを作成する THEN ドキュメント SHALL よくあるエラー、Architecture Tests失敗時の対処法、パフォーマンス最適化方法を記載する
5. WHEN 既存コード移行計画を策定する THEN 移行戦略 SHALL Stranglerパターン採用（新機能からDDD適用）、1機能ずつ段階的移行、移行チェックリストを含む
6. IF 新機能開発を行う場合 THEN 開発チーム SHALL 既存MVCではなくDDD構造で実装する
7. WHEN 既存機能を移行する THEN 開発チーム SHALL リグレッションテストとパフォーマンステストを実行し、移行前後で動作/性能を維持する

---

## 成功指標

### 短期目標（3ヶ月）
- DDD基盤構築完了（4層構造確立）
- 新機能実装でDDD採用率100%
- テストカバレッジ85%達成
- CI/CD統合完了

### 中期目標（6ヶ月）
- 既存コア機能のDDD移行完了（50%以上）
- ドメインロジックバグ50%削減
- 開発速度の従来レベル回復
- チーム全員のDDD実装スキル習得

### 長期目標（12ヶ月）
- 全機能のDDD移行完了（90%以上）
- リグレッションバグ70%削減
- 新機能開発速度30%向上
- コードレビュー時間50%削減

---

## 技術制約

### Laravel標準ツール利用方針
- `php artisan make:*`で生成されるファイルは Laravel 標準位置（`app/`配下）を維持する
- Eloquent Modelsは`app/Models/`に配置（`artisan make:model`標準位置）
- Controllers/Requests/Middleware/Commands/Providersも全て`app/`配下の標準位置
- DDD層（`ddd/`配下）は手動作成・ビジネスロジック専用

### コーディング規約
- 日時データ: Domain層でも`Carbon`使用（`DateTimeImmutable`は使用しない）
- 配列使用: 単純リストはOK、連想配列は避けてEntityクラス作成
- Enum活用: `app/Enums`の既存Enum積極利用（マジックナンバー/ストリング禁止）
- クラス名: use文で短縮形呼び出し（FQCN直接呼び出し禁止）

### パフォーマンス考慮事項
- Eloquent関連ロードは`with()`で明示的先読み
- コレクション変換は`Collection::map()`でバッチ処理
- 読み取り専用複雑クエリは`Application/Queries`で最適化（CQRS）
- Mapper変換コストが高い場合はキャッシュ/インターン検討

---

## 非機能要件

### パフォーマンス
- API レスポンスタイム: 移行前後で変化なし（既存性能維持）
- Mapper変換オーバーヘッド: 1リクエストあたり5ms以内
- ユニットテスト実行時間: Domain層テスト100ケースあたり1秒以内

### 保守性
- Architecture Tests: 依存関係ルール違反を自動検出
- PHPStan Level 8: 静的解析で型安全性を保証
- Laravel Pint: コードスタイル統一を自動保証

### テスタビリティ
- Domain層: 90%以上のカバレッジ
- Application層: 85%以上のカバレッジ
- Infrastructure層: 80%以上のカバレッジ
- 全体平均: 85%以上のカバレッジ

### 学習曲線
- 初期学習コスト: 3ヶ月で開発速度20%低下許容
- ドキュメント整備: テンプレートコード、ガイドライン、トラブルシューティング提供
- ペアプログラミング推奨: 経験者と未経験者のペア編成

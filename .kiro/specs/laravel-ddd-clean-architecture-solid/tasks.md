# Implementation Plan

## Phase 1: プロジェクト基盤構築

- [ ] 1. DDD層のディレクトリ構造を作成する
  - Domain/Application/Infrastructure/Shared の4層構造をbackend/laravel-api/ddd/配下に作成
  - User集約用のサブディレクトリ構造を準備（ValueObjects、Entities、Repositories等）
  - 各層の責務を明確に分離したディレクトリ階層を確立
  - _Requirements: 1.1_

- [ ] 2. PSR-4オートロード設定とDIコンテナを構成する
- [ ] 2.1 composer.jsonにDDD層の名前空間を追加する
  - Ddd\Domain、Ddd\Application、Ddd\Infrastructure、Ddd\Shared の4つの名前空間を登録
  - PSR-4オートロード規約に準拠した設定を追加
  - composer dump-autoloadで設定を反映
  - _Requirements: 1.2_

- [ ] 2.2 DddServiceProviderを作成してDIバインディングを設定する
  - app/Providers/DddServiceProvider.phpを作成
  - Repository InterfaceとInfrastructure実装のバインディング設定を追加
  - TransactionManagerとEventBusをシングルトン登録
  - config/app.phpのprovidersに登録して有効化
  - _Requirements: 1.3, 6.1, 6.2, 6.3_

## Phase 2: Domain層実装（User集約）

- [ ] 3. Domain層の基盤となる共通コンポーネントを実装する
- [ ] 3.1 Shared層の基底例外とTraitを作成する
  - DomainExceptionとValidationExceptionの基底クラスを実装
  - RecordsDomainEventsトレイトを実装（イベント記録・取得機能）
  - ドメインイベント管理の共通ロジックを提供
  - _Requirements: 2.5, Shared層の横断的関心事_

- [ ] 3.2 User集約のValueObjectを実装する
  - UserId ValueObjectをUUID v4形式で実装（readonly、named constructor、妥当性検証）
  - Email ValueObjectをRFC 5322準拠で実装（readonly、named constructor、妥当性検証）
  - 不変性を保証し、equals()メソッドで同値性判定を提供
  - _Requirements: 2.1_

- [ ] 4. User集約のEntityとRepository Interfaceを実装する
- [ ] 4.1 User Entityを実装する
  - private constructorで生成を制御
  - register()ファクトリメソッドで集約生成とUserRegisteredイベント記録
  - changeName()でビジネスルール実装（2文字以上制約）
  - RecordsDomainEventsトレイトでイベント管理
  - _Requirements: 2.2, 2.5_

- [ ] 4.2 Domain Eventを実装する
  - UserRegisteredイベントクラスを作成（UserId、Email、nameを保持）
  - イベント発火時のペイロード構造を定義
  - _Requirements: 2.5_

- [ ] 4.3 UserRepository Interfaceを定義する
  - nextId()、find()、findByEmail()、existsByEmail()、save()、delete()メソッドを定義
  - 戻り値・引数は全てDomainオブジェクト型（UserId、Email、User Entity）
  - _Requirements: 2.3, 3.1_

## Phase 3: Infrastructure層実装

- [ ] 5. データ永続化層のMapperとRepositoryを実装する
- [ ] 5.1 UserMapperを実装する
  - toEntity()メソッドでEloquent Model→Domain Entity変換
  - toModel()メソッドでDomain Entity→Eloquent Model変換
  - Carbon型の日時データ変換を適切に処理
  - _Requirements: 3.4, 3.5_

- [ ] 5.2 EloquentUserRepositoryを実装する
  - UserRepository Interfaceの全メソッドを実装
  - nextId()でUUID v4生成（Illuminate\Support\Str::uuid()使用）
  - find()、findByEmail()でMapperを経由してDomain Entity返却
  - save()でfindOrNew()とMapperを使用してEloquent Model永続化
  - app/Models/User.phpをuse App\Models\User as EloquentUser形式で参照
  - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5_

- [ ] 6. トランザクション管理とイベントバス機能を実装する
- [ ] 6.1 TransactionManagerを実装する
  - TransactionManager Interface定義（run()メソッド）
  - LaravelTransactionManager実装でDB::transaction()をラップ
  - callableの戻り値を返却し、例外時は自動ロールバック
  - _Requirements: 5.1_

- [ ] 6.2 EventBusを実装する
  - EventBus Interface定義（dispatch()メソッド、afterCommitオプション）
  - LaravelEventBus実装でDispatcher::dispatch()とDB::afterCommit()をラップ
  - afterCommit: trueの場合、トランザクションコミット後にイベント発火
  - _Requirements: 5.2_

## Phase 4: Application層実装

- [ ] 7. User登録ユースケースを実装する
- [ ] 7.1 RegisterUserUseCaseのDTOを作成する
  - RegisterUserInput DTO（Email、name）をreadonly classで実装
  - RegisterUserOutput DTO（UserId）をreadonly classで実装
  - 入出力の型安全性を保証
  - _Requirements: 4.2_

- [ ] 7.2 RegisterUserUseCaseを実装する
  - UserRepository、TransactionManager、EventBusをコンストラクタインジェクション
  - execute()メソッドでUser登録フロー実装
  - Email重複チェック（existsByEmail()）→DomainException送出
  - User::register()で集約生成→repository->save()で永続化
  - pullDomainEvents()→EventBus::dispatch(afterCommit: true)でイベント発火
  - TransactionManager::run()でトランザクション境界管理
  - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5_

## Phase 5: HTTP層実装（Interface Adapters）

- [ ] 8. User登録APIのControllerとRequest Validationを実装する
- [ ] 8.1 RegisterUserRequestを作成する
  - Laravel FormRequestを継承してバリデーションルール定義
  - email（required、email、max:255）、name（required、string、min:2、max:255）
  - _Requirements: 7.2_

- [ ] 8.2 UserControllerを実装する
  - RegisterUserUseCaseをコンストラクタインジェクション
  - register()メソッドでHTTP Request→Input DTO変換
  - RegisterUserUseCase::execute()を呼び出し
  - Output DTO→JsonResponse（201 Created、{id: string}）変換
  - _Requirements: 7.1, 7.2, 7.3_

- [ ] 8.3 Exception Handlerを更新する
  - DomainException、ApplicationExceptionをHTTPステータスコードに変換
  - EmailAlreadyExists→422、UserNotFound→404、InvalidName→422
  - エラーレスポンス形式を統一（{error: string, message: string}）
  - _Requirements: 7.4_

- [ ] 9. API routesを定義する
  - routes/api.phpにPOST /api/usersルート追加
  - UserController@registerメソッドにマッピング
  - 既存のSanctum認証設定と整合性を保つ
  - _Requirements: 7.1, 7.2, 7.3_

## Phase 6: テスト実装

- [ ] 10. Domain層のUnit Testsを実装する
- [ ] 10.1 ValueObject Unit Testsを作成する
  - EmailTest.php（妥当なEmail生成、不正Email例外、equals()検証）
  - UserIdTest.php（妥当なUUID生成、不正UUID例外）
  - Laravel機能非依存、高速実行を確認
  - _Requirements: 8.1_

- [ ] 10.2 Entity Unit Testsを作成する
  - UserTest.php（register()でUser生成とイベント記録、changeName()検証、pullDomainEvents()検証）
  - ビジネスルール（name 2文字以上）の検証
  - RecordsDomainEventsトレイトの動作確認
  - _Requirements: 8.1_

- [ ] 11. Application層のFeature Testsを実装する
- [ ] 11.1 RegisterUserUseCase Feature Testsを作成する
  - 正常系: User登録成功、RegisterUserOutput返却
  - 異常系: Email重複時にDomainException送出
  - Mockeryでリポジトリ、TransactionManager、EventBusをモック化
  - トランザクションロールバックとイベント発火を検証
  - _Requirements: 8.2_

- [ ] 12. Infrastructure層のIntegration Testsを実装する
- [ ] 12.1 EloquentUserRepository Integration Testsを作成する
  - save()→find()でDomain Entity永続化・取得を検証
  - findByEmail()、existsByEmail()の動作確認
  - Mapper変換（Eloquent ↔ Domain Entity）の正常動作確認
  - 実データベース（RefreshDatabase trait）使用
  - _Requirements: 8.3_

- [ ] 13. Architecture Testsを実装する
- [ ] 13.1 DDD層の依存関係ルールを検証するテストを追加する
  - Domain層がIlluminate、Laravel、Eloquentに依存しないことを検証
  - Domain層がInfrastructure層に依存しないことを検証
  - Application層がInfrastructure層に依存しないことを検証
  - ControllerがModelsではなくDdd\Applicationを使用することを検証
  - tests/Architecture/DddArchitectureTest.phpに追加
  - _Requirements: 1.4, 1.5, 8.4_

- [ ] 14. E2E Testsを実装する
- [ ] 14.1 User登録APIのE2Eテストを作成する
  - POST /api/usersで201 Created、User登録成功を検証
  - Email重複時に422 Unprocessable Entity返却を検証
  - 不正なEmail形式で400 Bad Request返却を検証
  - tests/Feature/Http/Controllers/UserControllerTest.phpに追加
  - _Requirements: 8.1, 8.2, 8.3_

- [ ] 15. テストカバレッジを測定して目標達成を確認する
  - ./vendor/bin/pest --coverage --min=85 を実行
  - Domain層90%以上、Application層85%以上、Infrastructure層80%以上を達成
  - カバレッジ未達の箇所を特定して追加テスト作成
  - _Requirements: 8.5, 8.6_

## Phase 7: CI/CD統合

- [ ] 16. GitHub Actionsワークフローを更新する
  - .github/workflows/test.ymlにArchitecture Tests実行ステップを追加
  - PHPStan Level 8静的解析をDDD層に適用
  - カバレッジレポート生成とビルド失敗条件（85%未満）を設定
  - _Requirements: CI/CD統合_

## Phase 8: ドキュメント整備

- [ ] 17. アーキテクチャドキュメントを作成する
- [ ] 17.1 アーキテクチャ概要ドキュメントを作成する
  - 4層構造の説明と依存方向ルール
  - 主要パターン（ValueObject、Entity、Repository、UseCase）の解説
  - 既存MVCとDDD層の共存戦略
  - backend/laravel-api/docs/ddd-architecture.mdに配置
  - _Requirements: 9.1_

- [ ] 17.2 開発ガイドラインを作成する
  - 新規機能開発時のDDD実装手順
  - コーディング規約（Carbon使用、Enum活用、配列vs Entity等）
  - 命名規則とディレクトリ配置ルール
  - backend/laravel-api/docs/ddd-development-guide.mdに配置
  - _Requirements: 9.2_

- [ ] 17.3 テスト戦略ドキュメントを作成する
  - 各層のテスト方針（Unit/Feature/Integration/Architecture）
  - テストヘルパー使用方法とモックパターン
  - カバレッジ目標と測定方法
  - backend/laravel-api/docs/ddd-testing-strategy.mdに配置
  - _Requirements: 9.3_

- [ ] 17.4 トラブルシューティングガイドを作成する
  - よくあるエラーと解決方法（PSR-4オートロード、DI解決失敗等）
  - Architecture Tests失敗時の対処法
  - パフォーマンス最適化方法（Mapper変換、N+1クエリ対策等）
  - backend/laravel-api/docs/ddd-troubleshooting.mdに配置
  - _Requirements: 9.4_

## Phase 9: 既存コード移行（段階的実施）

- [ ] 18. 既存機能の移行計画を策定する
  - 移行対象API選定（優先度: 高頻度API、ビジネスロジック複雑なAPI）
  - 1機能ずつ段階的移行のチェックリスト作成
  - リグレッションテストとパフォーマンステスト計画
  - _Requirements: 9.5, 9.6, 9.7_

- [ ] 19. 新機能開発でDDD構造を適用する
  - 新規APIはDDD構造（Domain/Application/Infrastructure層）で実装
  - 既存MVCコードは触らず、新規コードのみDDD化
  - Stranglerパターンで段階的にDDDカバレッジを拡大
  - _Requirements: 9.6_

- [ ] 20. 移行後の検証と最適化を実施する
  - リグレッションテスト実行（既存機能の動作維持確認）
  - パフォーマンステスト実行（APIレスポンスタイム維持確認）
  - Mapper変換オーバーヘッドが5ms以内であることを確認
  - カバレッジ85%以上を維持
  - _Requirements: 9.7_

---

## 実装優先順位

**高優先度（Phase 1-6）**:
- タスク1-15: 基盤構築、User集約実装、テスト整備
- 目標: 2-3週間でDDD基盤とサンプル実装完了

**中優先度（Phase 7-8）**:
- タスク16-17: CI/CD統合、ドキュメント整備
- 目標: 1週間でCI/CD統合とドキュメント完備

**低優先度（Phase 9）**:
- タスク18-20: 既存コード移行（継続的実施）
- 目標: 6-12ヶ月で段階的移行完了

---

## 完了定義

各タスク完了時に以下を確認してください：

- [ ] コードがLaravel Pintでフォーマット済み
- [ ] PHPStan Level 8静的解析をパス
- [ ] 関連するテストが全て通過
- [ ] Architecture Testsが通過（依存関係ルール違反なし）
- [ ] コミットメッセージが規約準拠（日本語、Prefix + Emoji）

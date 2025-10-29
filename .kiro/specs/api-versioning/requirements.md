# Requirements Document

## Introduction

本ドキュメントは、Laravel Next.js B2CアプリケーションテンプレートにおけるAPIバージョニング機能（V1実装）の要件を定義します。本機能は、後方互換性を維持しながらAPI仕様を進化させ、DDD/クリーンアーキテクチャ準拠の拡張可能な基盤を提供することを目的としています。

### ビジネス価値
- **後方互換性の維持**: 既存クライアント（Next.js admin-app, user-app）への影響を最小化しつつ新機能を追加
- **API仕様の進化**: レスポンス形式の標準化、エラーハンドリングの改善、セキュリティ強化
- **将来の拡張性**: V2以降の実装に向けた拡張可能な基盤構築

### スコープ
本要件は **V1実装のみ** を対象とします。V2以降は実装方針のドキュメント化のみを含みます。

---

## Requirements

### Requirement 1: URLベースAPIバージョニング基盤

**Objective:** APIクライアント開発者として、明示的なバージョン指定によりAPI仕様の変更から保護され、安定したAPI利用ができるようにしたい。これにより、既存クライアントへの影響なく新バージョンAPIを段階的に導入できる。

#### Acceptance Criteria

1. WHEN APIクライアントが `/api/v1/{endpoint}` 形式のURLでリクエストを送信する THEN Laravel API Service SHALL V1バージョン固有のコントローラーにルーティングする

2. WHEN APIクライアントが `/api/{endpoint}` 形式（バージョン指定なし）のURLでリクエストを送信する THEN Laravel API Service SHALL デフォルトバージョン（v1）のコントローラーにルーティングする

3. WHEN APIクライアントが `X-API-Version: v1` ヘッダーを含むリクエストを送信する THEN Laravel API Service SHALL URLベースバージョニングの補助手段としてヘッダーバージョンを認識する

4. WHEN APIリクエストのURLとヘッダーで異なるバージョンが指定される AND ヘッダーバージョンが有効である THEN Laravel API Service SHALL ヘッダーバージョンを優先して適用する

5. WHEN APIリクエストが存在しないバージョン（例: `/api/v99`）を指定する THEN Laravel API Service SHALL HTTP 404 Not Found レスポンスを返却する

6. WHERE APIレスポンスの全てのバージョンに対して THE Laravel API Service SHALL `X-API-Version` レスポンスヘッダーに使用されたバージョン番号（例: `v1`）を含める

### Requirement 2: 既存コントローラーのV1ディレクトリへの移行

**Objective:** API保守担当者として、既存のAPIエンドポイントをV1バージョンとして明示的に管理し、将来のバージョン追加時の混乱を防ぎたい。これにより、各バージョンの責務が明確化され、保守性が向上する。

#### Acceptance Criteria

1. WHEN 既存の全てのAPIコントローラー（HealthController, LoginController, MeController, TokenController, CspReportController）が移行される THEN Laravel API Service SHALL これらを `app/Http/Controllers/Api/V1/` ディレクトリ配下に配置する

2. WHERE V1コントローラー全てに対して THE Laravel API Service SHALL 名前空間 `App\Http\Controllers\Api\V1` を使用する

3. WHEN V1コントローラーが既存のユースケース（Application層）を呼び出す THEN Laravel API Service SHALL Application層への依存関係を変更せずに維持する

4. WHEN 既存のAPIテストが実行される THEN Test Suite SHALL 全てのFeature Testsが引き続き成功する（回帰テスト）

5. WHERE 全てのV1エンドポイントに対して THE Laravel API Service SHALL 既存のレスポンス形式・HTTPステータスコード・ヘッダーを保持する

### Requirement 3: DDD 4層構造との統合

**Objective:** アーキテクチャ設計者として、Domain/Application層をバージョンに依存させず、HTTP/Infrastructure層でのみバージョン差分を吸収したい。これにより、ビジネスロジックの再利用性と保守性を最大化する。

#### Acceptance Criteria

1. WHERE Domain層とApplication層の全てのコードに対して THE Laravel API Service SHALL バージョン固有の依存関係を含めない

2. WHEN 異なるバージョンのAPIエンドポイントが同一のユースケースを使用する THEN Laravel API Service SHALL Application層のユースケースを再利用する

3. WHERE HTTP層（Controllerディレクトリ）とInfrastructure層（Presenter/Requestディレクトリ）に対して THE Laravel API Service SHALL バージョン固有のディレクトリ構造（例: `V1/`, `V2/`）を許可する

4. WHEN V1 Presenterがドメインエンティティをレスポンスに変換する THEN Laravel API Service SHALL Infrastructure層の `ddd/Infrastructure/Http/Presenters/V1/` に配置されたPresenterを使用する

5. WHEN V1 Requestがリクエストデータをバリデーションする THEN Laravel API Service SHALL Infrastructure層の `ddd/Infrastructure/Http/Requests/V1/` に配置されたRequestを使用する

6. WHEN Pest Architecture Testsが実行される THEN Test Suite SHALL Domain/Application層がHTTP層に依存していないことを検証する

7. WHEN Pest Architecture Testsが実行される THEN Test Suite SHALL Domain/Application層がバージョン固有のクラスに依存していないことを検証する

### Requirement 4: 既存ミドルウェアスタックとの統合

**Objective:** API運用担当者として、APIレート制限、リクエストID管理、認証・認可などの既存ミドルウェア機能をV1エンドポイントでも継続して利用したい。これにより、セキュリティと運用監視の一貫性を保つ。

#### Acceptance Criteria

1. WHEN V1エンドポイントにリクエストが送信される THEN Laravel API Service SHALL 既存の `DynamicRateLimit` ミドルウェアによるレート制限を適用する

2. WHEN V1エンドポイントにリクエストが送信される THEN Laravel API Service SHALL 既存の `SetRequestId` ミドルウェアによりリクエストIDを付与する

3. WHEN V1の認証必須エンドポイント（例: `/api/v1/me`, `/api/v1/tokens`）にリクエストが送信される THEN Laravel API Service SHALL 既存の `auth:sanctum` ミドルウェアにより認証を検証する

4. WHEN V1エンドポイントに対するレート制限設定が必要な場合 THEN Laravel API Service SHALL Application層の `RateLimitConfig` を参照してエンドポイント分類別の制限値を適用する

5. WHERE 全てのV1エンドポイントに対して THE Laravel API Service SHALL 既存のミドルウェアグループ（api, auth, public）設定を継承する

### Requirement 5: ApiVersionミドルウェアの実装

**Objective:** API基盤開発者として、APIバージョニングロジックを一元管理するミドルウェアを実装したい。これにより、ルーティング層でのバージョン判定とバージョン情報のレスポンスヘッダー付与を自動化する。

#### Acceptance Criteria

1. WHEN ApiVersionミドルウェアがリクエストを処理する THEN Laravel API Service SHALL リクエストURLから正規表現 `/^\/api\/v(\d+)\//` によりバージョン番号を抽出する

2. WHEN ApiVersionミドルウェアがバージョン番号を検出する THEN Laravel API Service SHALL リクエスト属性に `api_version` として保存する（例: `v1`）

3. WHEN ApiVersionミドルウェアがリクエスト属性に `api_version` を設定した後 THEN Laravel API Service SHALL 後続のミドルウェアとコントローラーから `request()->get('api_version')` で取得可能にする

4. WHEN ApiVersionミドルウェアがレスポンスを返却する前に処理する THEN Laravel API Service SHALL `X-API-Version` レスポンスヘッダーにバージョン番号を追加する

5. WHEN ApiVersionミドルウェアが設定ファイル `config/api.php` を参照する THEN Laravel API Service SHALL デフォルトバージョン値（`default_version`）とサポートバージョンリスト（`supported_versions`）を取得する

6. WHERE 全てのAPIルート（`routes/api.php`および`routes/api/v1.php`）に対して THE Laravel API Service SHALL ApiVersionミドルウェアをグローバルミドルウェアまたはAPIミドルウェアグループに登録して適用する

### Requirement 6: ルーティング分離とV1ルート定義

**Objective:** API基盤開発者として、V1エンドポイントのルーティング定義を独立したファイルに分離したい。これにより、各バージョンのルート管理が明確化され、将来のバージョン追加が容易になる。

#### Acceptance Criteria

1. WHEN Laravel API Serviceが起動する THEN Routing System SHALL `routes/api/v1.php` ファイルをロードしてV1ルートを登録する

2. WHERE `routes/api/v1.php` の全てのルート定義に対して THE Routing System SHALL プレフィックス `/api/v1` を自動付与する

3. WHERE `routes/api/v1.php` の全てのルート定義に対して THE Routing System SHALL 既存のAPIミドルウェアグループ設定を継承する

4. WHEN `routes/api/v1.php` が既存エンドポイントを定義する THEN Routing System SHALL 以下のルートを含む:
   - `GET /api/v1/health` → `V1\HealthController@show`
   - `POST /api/v1/login` → `V1\LoginController@login`
   - `POST /api/v1/logout` → `V1\LoginController@logout`
   - `GET /api/v1/me` → `V1\MeController@show`
   - `GET /api/v1/tokens` → `V1\TokenController@index`
   - `POST /api/v1/tokens/{id}/revoke` → `V1\TokenController@revoke`
   - `POST /api/v1/tokens/refresh` → `V1\TokenController@refresh`
   - `POST /api/v1/csp-report` → `V1\CspReportController@store`

5. WHEN `routes/api.php` が既存のルート定義を保持する THEN Routing System SHALL デフォルトバージョン（v1）として動作するルートを維持する

6. WHERE 全てのV1ルート定義に対して THE Routing System SHALL ルート名にバージョンプレフィックス（例: `v1.health`, `v1.login`）を付与する

### Requirement 7: フロントエンド型定義の分離

**Objective:** フロントエンド開発者として、V1 APIのレスポンス型定義を独立したファイルで管理したい。これにより、各バージョンのAPI契約が明確化され、TypeScript型チェックによる安全性が向上する。

#### Acceptance Criteria

1. WHEN フロントエンドプロジェクトが型定義ファイルを読み込む THEN Frontend Application SHALL `frontend/types/api/v1.ts` ファイルを使用する

2. WHERE `frontend/types/api/v1.ts` の全ての型定義に対して THE Frontend Application SHALL V1 APIレスポンス形式を正確に反映した型を提供する

3. WHEN `frontend/types/api/v1.ts` が既存のAPIレスポンス型を定義する THEN Frontend Application SHALL 以下の型を含む:
   - `V1HealthResponse`: ヘルスチェックレスポンス型（`{ status: string, timestamp: string }`）
   - `V1LoginResponse`: ログインレスポンス型（`{ token: string, user: User }`）
   - `V1UserResponse`: ユーザー情報レスポンス型（UserResourceの型定義）
   - `V1TokenListResponse`: トークン一覧レスポンス型（`{ tokens: Token[] }`）
   - `V1ErrorResponse`: エラーレスポンス型（`{ message: string, errors?: object }`）

4. WHERE `frontend/types/api/v1.ts` の全てのエクスポート型名に対して THE Frontend Application SHALL `V1` プレフィックスを付与する

5. WHEN フロントエンド開発者がAPIクライアントを実装する THEN Frontend Application SHALL 環境変数 `NEXT_PUBLIC_API_VERSION`（デフォルト: `v1`）を使用してAPIバージョンを指定する

### Requirement 8: 完全なテスト戦略（V1のみ）

**Objective:** QA担当者として、V1 APIバージョニング機能の品質を保証するため、Feature Tests、Architecture Tests、E2E Testsの包括的なテストカバレッジを実現したい。これにより、リグレッション防止と継続的な品質維持が可能になる。

#### Acceptance Criteria

1. WHEN `tests/Feature/Middleware/ApiVersionTest.php` が実行される THEN Test Suite SHALL ApiVersionミドルウェアの以下の動作を検証する:
   - URLからのバージョン番号抽出
   - リクエスト属性への `api_version` 保存
   - `X-API-Version` レスポンスヘッダー付与
   - 存在しないバージョンへの404レスポンス

2. WHEN `tests/Feature/Api/V1/` 配下の全てのテストが実行される THEN Test Suite SHALL 各V1エンドポイントの動作を検証する（回帰テスト）

3. WHEN `tests/Arch/ApiVersionArchitectureTest.php` が実行される THEN Test Suite SHALL 以下のアーキテクチャ制約を検証する:
   - Domain層とApplication層がバージョン固有クラスに依存していないこと
   - V1コントローラーが `App\Http\Controllers\Api\V1` 名前空間に配置されていること
   - V1 Presenter/Requestが `Ddd\Infrastructure\Http\Presenters\V1` および `Ddd\Infrastructure\Http\Requests\V1` 名前空間に配置されていること

4. WHEN `e2e/projects/shared/tests/api-versioning.spec.ts` が実行される THEN Test Suite SHALL 以下のE2Eシナリオを検証する:
   - `/api/v1/health` エンドポイントへのアクセスとレスポンス検証
   - `/api/v1/login` エンドポイントでの認証フロー検証
   - `X-API-Version: v1` レスポンスヘッダーの存在確認

5. WHEN 全てのテスト（Feature/Architecture/E2E）が実行される THEN Test Suite SHALL 85%以上のコードカバレッジを維持する

6. WHEN GitHub Actions CI/CD環境で全テストが実行される THEN Test Suite SHALL 全てのテストが成功することを検証する

### Requirement 9: CI/CD統合とOpenAPI仕様生成

**Objective:** DevOps担当者として、APIバージョニング機能のCI/CD統合とOpenAPI仕様の自動生成により、継続的な品質保証とAPI文書化を実現したい。これにより、開発効率とAPI利用者への情報提供が向上する。

#### Acceptance Criteria

1. WHEN GitHub Actions workflow `.github/workflows/test.yml` が実行される THEN CI/CD System SHALL `paths` 設定に以下のパスを含める:
   - `backend/laravel-api/app/Http/Controllers/Api/V1/**`
   - `backend/laravel-api/app/Http/Middleware/ApiVersion.php`
   - `backend/laravel-api/routes/api/v1.php`
   - `backend/laravel-api/ddd/Infrastructure/Http/Presenters/V1/**`
   - `backend/laravel-api/ddd/Infrastructure/Http/Requests/V1/**`
   - `frontend/types/api/v1.ts`

2. WHEN V1 APIバージョニング関連のファイルが変更される AND GitHub Actions workflowがトリガーされる THEN CI/CD System SHALL 全てのテスト（Feature/Architecture/E2E）を実行する

3. WHEN OpenAPI仕様生成コマンドが実行される THEN Laravel API Service SHALL `docs/openapi-v1.yaml` ファイルを生成する

4. WHERE `docs/openapi-v1.yaml` の全てのエンドポイント定義に対して THE OpenAPI Specification SHALL 以下の情報を含む:
   - エンドポイントパス（例: `/api/v1/health`）
   - HTTPメソッド（GET, POST等）
   - リクエストパラメータ定義
   - レスポンススキーマ定義
   - 認証要件（Sanctum Bearer Token）
   - レスポンスヘッダー（`X-API-Version`）

5. WHEN `docs/openapi-v1.yaml` がSwagger UIで読み込まれる THEN Swagger UI SHALL V1 APIの全エンドポイントを視覚的に表示する

### Requirement 10: ドキュメント整備（実装ガイド + V2ロードマップ）

**Objective:** API利用者と将来の開発者として、V1実装の詳細ガイドとV2以降の実装方針を文書化したドキュメントを提供したい。これにより、実装パターンの理解促進と将来の拡張計画の明確化を実現する。

#### Acceptance Criteria

1. WHEN `backend/laravel-api/docs/api-versioning-guide.md` が作成される THEN Documentation System SHALL 以下の内容を含む:
   - 設計思想（URLベースバージョニング採用理由、DDD統合方針）
   - 実装パターン（ApiVersionミドルウェア、ルーティング分離、コントローラー配置）
   - テスト戦略（Feature/Architecture/E2E Testsの実行方法）
   - トラブルシューティング（よくある問題と解決策）

2. WHEN `backend/laravel-api/docs/api-versioning-v2-roadmap.md` が作成される THEN Documentation System SHALL 以下の内容を含む:
   - V2実装方針（V2導入のタイミング、段階的移行戦略）
   - 段階的移行戦略（既存V1クライアントのサポート継続方針）
   - V2技術要件（新しいレスポンス形式、エラーハンドリング改善案）
   - V2テスト戦略（V1/V2並行稼働時のテスト手法）
   - Deprecation/Sunset運用方針（V1廃止計画、クライアント移行期間）

3. WHERE 全てのドキュメント（実装ガイド、V2ロードマップ）に対して THE Documentation System SHALL 日本語で記述する

4. WHERE 全てのドキュメントに対して THE Documentation System SHALL コードサンプル、設定例、実行コマンド例を含める

5. WHEN 開発者がドキュメントを参照する THEN Documentation System SHALL 明確な目次、セクション分け、参照リンクを提供する

---

## Non-Functional Requirements

### Performance Requirements

1. WHEN V1エンドポイントへのリクエストが処理される THEN Laravel API Service SHALL バージョニングによるオーバーヘッドを5ms未満に抑える

2. WHEN ApiVersionミドルウェアがバージョン判定を行う THEN Laravel API Service SHALL 正規表現処理を1ms未満で完了する

### Security Requirements

1. WHERE 全てのV1認証必須エンドポイントに対して THE Laravel API Service SHALL Sanctum Bearer Token認証を強制する

2. WHEN 既存のセキュリティヘッダー設定（CSP, X-Frame-Options等）が適用される THEN Laravel API Service SHALL V1エンドポイントでも同一のセキュリティレベルを維持する

### Maintainability Requirements

1. WHERE Domain層とApplication層の全てのコードに対して THE Laravel API Service SHALL バージョン固有の依存関係を含めない（DDD原則準拠）

2. WHEN Pest Architecture Testsが実行される THEN Test Suite SHALL 依存方向違反がゼロであることを検証する

### Scalability Requirements

1. WHEN 将来V2, V3以降のバージョンが追加される THEN Laravel API Service SHALL 各バージョンのコントローラー・Presenter・Requestを独立したディレクトリに配置可能である

2. WHERE 複数バージョンのAPI並行稼働に対して THE Laravel API Service SHALL 共通のApplication層ユースケースを再利用できる設計を維持する

---

## Out of Scope

以下の機能は本要件のスコープ外です:

1. **V2コントローラー実装**: V2以降の実装はロードマップドキュメント化のみ
2. **V3以降のバージョン実装**: 本要件ではV1のみを対象
3. **GraphQLバージョニング**: REST API専用の実装
4. **自動マイグレーション機能**: クライアントコードの自動書き換えは含まれない
5. **API契約監視（oasdiff統合）**: V2実装時に検討
6. **Deprecation/Sunsetヘッダーの実装**: V2実装時に検討（ロードマップ文書化のみ）

---

## Glossary

- **URLベースバージョニング**: APIエンドポイントのURLパスにバージョン番号を含める方式（例: `/api/v1/users`）
- **EARS形式**: Easy Approach to Requirements Syntax。要件を構造化された文法で記述する手法
- **DDD**: Domain-Driven Design。ドメイン駆動設計
- **Presenter**: Infrastructure層でドメインエンティティをAPIレスポンス形式に変換するクラス
- **Sanctum**: Laravel公式のトークンベース認証ライブラリ
- **Pest**: Laravel推奨のモダンなPHPテストフレームワーク
- **Architecture Tests**: コードの依存方向やレイヤー分離を自動検証するテスト
- **OpenAPI**: REST APIの仕様を記述する標準フォーマット（旧Swagger）

---

## Traceability Matrix

| Requirement ID | GitHub Issue #107 対応項目 | Test Coverage |
|---------------|--------------------------|---------------|
| Requirement 1 | URLベースバージョニング実装 | ApiVersionTest.php |
| Requirement 2 | V1コントローラー移行 | Api/V1/*Test.php |
| Requirement 3 | DDD 4層構造統合 | ApiVersionArchitectureTest.php |
| Requirement 4 | 既存ミドルウェア統合 | DynamicRateLimitTest.php |
| Requirement 5 | ApiVersionミドルウェア実装 | ApiVersionTest.php |
| Requirement 6 | ルーティング分離 | Api/V1/*Test.php |
| Requirement 7 | フロントエンド型定義分離 | TypeScript型チェック |
| Requirement 8 | 完全なテスト戦略 | 全テストスイート |
| Requirement 9 | CI/CD統合 | GitHub Actions workflow |
| Requirement 10 | ドキュメント整備 | ドキュメントレビュー |

---

## References

### Internal Documentation
- DDD/クリーンアーキテクチャ: `backend/laravel-api/docs/ddd-architecture.md`
- Sanctum認証ガイド: `backend/laravel-api/docs/sanctum-authentication-guide.md`
- テストDB運用: `docs/TESTING_DATABASE_WORKFLOW.md`
- 基本ミドルウェア設定: `.kiro/specs/basic-middleware-setup/`

### External References
- Laravel 12 Routing: https://laravel.com/docs/12.x/routing
- Laravel Sanctum: https://laravel.com/docs/12.x/sanctum
- Pest Testing: https://pestphp.com/docs/
- Pest Architecture Testing: https://pestphp.com/docs/arch-testing
- OpenAPI Specification v3.1: https://spec.openapis.org/oas/v3.1.0
- Playwright Testing: https://playwright.dev/docs/intro
- EARS Requirements Syntax: https://alistairmavin.com/ears/

---

## Approval History

- **Requirements Generated**: 2025-10-29
- **Requirements Approved**: Pending
- **Design Approved**: Pending
- **Tasks Approved**: Pending

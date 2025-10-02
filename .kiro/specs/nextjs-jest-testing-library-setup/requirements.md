# Requirements Document

## GitHub Issue Information

**Issue**: [#11](https://github.com/ef-tech/laravel-next-b2c/issues/11) - Next.js Jest + Testing Library 設定とテストサンプル作成
**Labels**: なし
**Milestone**: なし
**Assignees**: なし

## Introduction

本要件は、Next.js 15.5.4 + React 19.1.0で構築されたフロントエンドアプリケーション（admin-app/user-app）に対して、Jest 29とReact Testing Library 16を使用した包括的なテスト環境を構築するためのものです。

### ビジネス価値
- **品質保証基盤の確立**: 自動テストによる品質ゲート設定でバグの早期発見
- **開発効率向上**: TDD実現による迅速なフィードバックループ
- **リファクタリング安全性**: テストによるリグレッション防止で安全な改善
- **チーム開発の円滑化**: 統一されたテスト規約とベストプラクティスの共有
- **CI/CD統合**: 自動テスト実行による継続的品質保証

### 技術コンテキスト
- **プロジェクト**: Laravel Next.js B2Cアプリケーションテンプレート（モノレポ構成）
- **既存フロントエンド**: Next.js 15.5.4 + React 19.1.0 + TypeScript 5 + Turbopack
- **既存品質管理**: ESLint 9 + Prettier + husky + lint-staged
- **バックエンドテスト**: Pest 4（既に導入済み）

## Requirements

### Requirement 1: テストフレームワークのインストールと基本設定
**Objective:** フロントエンド開発者として、Jest 29とReact Testing Library 16を使用してコンポーネントとロジックをテストできる環境を構築したい。これにより、品質保証の自動化とテスト駆動開発が可能になる。

#### Acceptance Criteria

1. WHEN プロジェクトルートで `npm ci` を実行 THEN Test System SHALL Jest 29、jest-environment-jsdom 29、@testing-library/react 16、@testing-library/jest-dom 6、@testing-library/user-event 14、@types/jest 29、whatwg-fetch 3、msw 2、next-router-mock 0.9 をdevDependenciesとしてインストールする
2. WHEN ルートpackage.jsonのworkspacesフィールドを確認 THEN Test System SHALL `["frontend/admin-app", "frontend/user-app"]` を含む
3. WHEN ルートpackage.jsonのscriptsフィールドを確認 THEN Test System SHALL `test`、`test:watch`、`test:coverage`、`test:admin`、`test:user` スクリプトを含む
4. WHEN 各フロントエンドアプリ（admin-app/user-app）のpackage.jsonを確認 THEN Test System SHALL `test`、`test:watch`、`test:coverage` スクリプトを含む

### Requirement 2: Jest設定ファイルの構築（モノレポ共通設定）
**Objective:** テスト環境管理者として、モノレポ全体で統一されたJest設定を管理したい。これにより、重複設定を排除し、保守性を向上させる。

#### Acceptance Criteria

1. WHEN `/jest.base.js` ファイルを確認 THEN Test System SHALL testEnvironment='jsdom'、setupFilesAfterEnv、testMatch、moduleNameMapper、transformIgnorePatterns、collectCoverageFrom、coverageThreshold（branches/functions/lines/statements: 80%）を含む基本設定を提供する
2. WHEN `/jest.config.js` ファイルを確認 THEN Test System SHALL projects配列に `frontend/admin-app` と `frontend/user-app` を含む統括設定を提供する
3. WHEN `frontend/admin-app/jest.config.js` ファイルを確認 THEN Test System SHALL next/jestのcreateJestConfigを使用し、jest.baseを継承し、displayName='admin-app'、rootDir=__dirname、setupFilesAfterEnv='../../jest.setup.ts'を含む
4. WHEN `frontend/user-app/jest.config.js` ファイルを確認 THEN Test System SHALL next/jestのcreateJestConfigを使用し、jest.baseを継承し、displayName='user-app'、rootDir=__dirname、setupFilesAfterEnv='../../jest.setup.ts'を含む

### Requirement 3: Jest共通セットアップファイルの作成
**Objective:** テスト開発者として、Next.js特有の機能（Image、Font、Navigation）とMSWによるAPIモックを統一的に設定したい。これにより、全テストで一貫したモック戦略を適用できる。

#### Acceptance Criteria

1. WHEN `/jest.setup.ts` ファイルを確認 THEN Test System SHALL @testing-library/jest-domとwhatwg-fetchをimportし、TextEncoderとTextDecoderのPolyfillを含む
2. WHEN jest.setup.tsのnext/imageモックを確認 THEN Test System SHALL <img>タグをレンダリングする代替実装を提供する
3. WHEN jest.setup.tsのnext/font/localモックを確認 THEN Test System SHALL className=''を返す代替実装を提供する
4. WHEN jest.setup.tsのnext/navigationモックを確認 THEN Test System SHALL next-router-mockを使用する代替実装を提供する
5. WHEN jest.setup.tsのMSW設定を確認 THEN Test System SHALL setupServerインスタンスを作成し、beforeAll、afterEach、afterAllフックでサーバーライフサイクルを管理する

### Requirement 4: App Router対応テストサンプルの作成
**Objective:** フロントエンド開発者として、Next.js App Routerの主要パターン（Client Component、Server Actions、カスタムフック、API Fetch）に対応するテストサンプルを参照したい。これにより、実装パターンを理解し、新規テスト作成が容易になる。

#### Acceptance Criteria

1. WHEN `frontend/admin-app/src/components/Button/Button.test.tsx` を確認 THEN Test System SHALL Client Componentの基本的なレンダリング、イベントハンドリング、ナビゲーション、バリアント切り替えをテストするサンプルを提供する
2. WHEN `frontend/admin-app/src/app/actions.test.ts` を確認 THEN Test System SHALL Server ActionsのrevalidatePath呼び出しとバリデーションエラーハンドリングをテストするサンプルを提供する
3. WHEN `frontend/admin-app/src/hooks/useAuth.test.ts` を確認 THEN Test System SHALL カスタムフックのクエリパラメータ取得と非同期データフェッチをテストするサンプルを提供する
4. WHEN `frontend/admin-app/src/lib/api.test.ts` を確認 THEN Test System SHALL MSWを使用したAPI成功・失敗ケースをテストするサンプルを提供する

### Requirement 5: テストユーティリティの整備
**Objective:** テスト開発者として、環境変数モック、Routerモック、カスタムレンダリング関数などの共通ユーティリティを使用したい。これにより、テストコードの重複を削減し、保守性を向上させる。

#### Acceptance Criteria

1. WHEN `/test-utils/env.ts` ファイルを確認 THEN Test System SHALL setEnv関数（環境変数設定）とresetEnv関数（環境変数リセット）を提供する
2. WHEN `/test-utils/router.ts` ファイルを確認 THEN Test System SHALL setupRouter関数（pathname/query設定）を提供し、next-router-mockのsetCurrentUrlとpushを使用する
3. WHEN `/test-utils/render.tsx` ファイルを確認 THEN Test System SHALL カスタムrender関数（将来的なProvider追加用）と@testing-library/reactの全エクスポートを提供する

### Requirement 6: TypeScript統合設定
**Objective:** TypeScript開発者として、テストファイルとユーティリティに対してJestとTesting Libraryの型定義を適用したい。これにより、テストコードでも型安全性が保証される。

#### Acceptance Criteria

1. WHEN `/tsconfig.test.json` ファイルを確認 THEN Test System SHALL tsconfig.jsonを継承し、types配列に`jest`、`@testing-library/jest-dom`、`node`を含み、jsx='react-jsx'を設定する
2. WHEN tsconfig.test.jsonのinclude配列を確認 THEN Test System SHALL `frontend/**/src/**/*.test.ts`、`frontend/**/src/**/*.test.tsx`、`jest.setup.ts`、`test-utils/**/*.ts` を含む

### Requirement 7: ドキュメント整備
**Objective:** チーム開発者として、テスト記述ガイドラインとトラブルシューティングガイドを参照したい。これにより、テストのベストプラクティスを理解し、問題解決が迅速になる。

#### Acceptance Criteria

1. WHEN `frontend/TESTING_GUIDE.md` ファイルを確認 THEN Test System SHALL テストファイル命名規則、Arrange-Act-Assertパターン、モック使用ガイドライン、スナップショットテスト運用ルールを含むガイドラインを提供する
2. WHEN `frontend/TESTING_TROUBLESHOOTING.md` ファイルを確認 THEN Test System SHALL よくあるエラーと対処法、非同期テストのデバッグ、モック関連の問題、CI/CD失敗時の対応を含むガイドを提供する

### Requirement 8: CI/CD統合設定
**Objective:** DevOpsエンジニアとして、GitHub Actionsでフロントエンドテストを自動実行し、カバレッジレポートを生成したい。これにより、Pull Request時の品質ゲートを設定できる。

#### Acceptance Criteria

1. WHEN `.github/workflows/frontend-test.yml` ファイルを確認 THEN Test System SHALL `push`（main/develop、frontend/**パス）と`pull_request`（main、frontend/**パス）トリガーを含む
2. WHEN frontend-test.ymlのstrategy.matrixを確認 THEN Test System SHALL node-version配列に`18.x`と`20.x`を含み、app配列に`admin-app`と`user-app`を含む
3. WHEN frontend-test.ymlのtestジョブステップを確認 THEN Test System SHALL actions/checkout@v4、actions/setup-node@v4、npm ci（ルート）、npm ci（アプリ）、npm test（--coverage --watchAll=false --maxWorkers=2）、codecov/codecov-action@v3を含む
4. WHEN frontend-test.ymlのcoverage-reportジョブを確認 THEN Test System SHALL testジョブに依存し、actions/download-artifact@v3とromeovs/lcov-reporter-action@v0.3.1を含む

### Requirement 9: テスト実行コマンドの動作保証
**Objective:** 開発者として、定義されたテストコマンドが正常に動作することを確認したい。これにより、テスト環境の正常性が保証される。

#### Acceptance Criteria

1. WHEN プロジェクトルートで `npm test` を実行 THEN Test System SHALL admin-appとuser-appの全テストを並列実行し、成功ステータスを返す
2. WHEN プロジェクトルートで `npm test:admin` を実行 THEN Test System SHALL admin-appのテストのみを実行し、成功ステータスを返す
3. WHEN プロジェクトルートで `npm test:user` を実行 THEN Test System SHALL user-appのテストのみを実行し、成功ステータスを返す
4. WHEN プロジェクトルートで `npm test:coverage` を実行 THEN Test System SHALL 全テストを実行し、カバレッジレポート（lcov.info、HTMLレポート）を生成する
5. WHEN frontend/admin-appディレクトリで `npm test` を実行 THEN Test System SHALL admin-appのテストのみを実行し、成功ステータスを返す
6. WHEN frontend/user-appディレクトリで `npm test` を実行 THEN Test System SHALL user-appのテストのみを実行し、成功ステータスを返す

### Requirement 10: カバレッジ閾値の設定と検証
**Objective:** 品質管理者として、コードカバレッジの最低基準（80%）を設定し、テスト実行時に検証したい。これにより、品質基準を維持できる。

#### Acceptance Criteria

1. WHEN jest.base.jsのcoverageThreshold設定を確認 THEN Test System SHALL global.branches、global.functions、global.lines、global.statements全てに80%の閾値を設定する
2. WHEN カバレッジが80%未満のファイルが存在する状態でnpm test:coverageを実行 THEN Test System SHALL エラーステータスを返し、閾値未達成のメトリクスを表示する
3. WHEN 全ファイルのカバレッジが80%以上の状態でnpm test:coverageを実行 THEN Test System SHALL 成功ステータスを返し、カバレッジレポートを生成する

### Requirement 11: 既存品質管理ツールとの統合
**Objective:** プロジェクト管理者として、既存のESLint + Prettier + husky + lint-staged設定とJestを統合したい。これにより、統一された品質管理フローを実現できる。

#### Acceptance Criteria

1. WHEN ルートpackage.jsonのlint-staged設定を確認 THEN Test System SHALL フロントエンドTypeScript/JavaScriptファイルに対してESLintとPrettierを実行する設定を維持する
2. WHEN .husky/pre-commitフックを確認 THEN Test System SHALL lint-stagedを実行する設定を維持する
3. WHEN プロジェクトルートで `npm run lint` を実行 THEN Test System SHALL 全ワークスペース（admin-app/user-app）でESLintを実行し、テストファイル（*.test.tsx、*.test.ts）も対象とする
4. WHEN プロジェクトルートで `npm run format` を実行 THEN Test System SHALL 全フロントエンドファイル（テストファイル含む）に対してPrettierを実行する

## 対象外（Out of Scope）

以下の項目は本要件の対象外とし、将来の別Issueで検討する：

1. **E2Eテストフレームワーク（Playwright/Cypress）**: ブラウザ統合テストは別途設定
2. **Visual Regression Testing（Percy/Chromatic）**: UIスナップショット比較は別途設定
3. **パフォーマンステスト**: Lighthouseやカスタムパフォーマンス測定は別途設定
4. **Storybook統合**: コンポーネントカタログとの統合は別途設定
5. **既存コンポーネントへのテスト追加**: サンプルテストのみ作成し、全コンポーネントへの適用は段階的に実施

## 技術的制約

1. **Next.js 15.5.4互換性**: Jest設定はnext/jestのcreateJestConfigを使用し、Next.jsの内部設定との互換性を保つ
2. **React 19対応**: React Testing Library 16はReact 19に対応しているが、React 19特有の警告をjest.setupで抑制する
3. **Turbopack対応**: Jestは通常のJavaScript/TypeScript変換を使用し、Turbopackとは独立して動作する
4. **モノレポ構成**: workspaces機能を活用し、ルートnode_modulesで共通依存関係を管理する
5. **ESLint 9 Flat Config**: eslint.config.mjsでテストファイルパターン（**/*.test.{ts,tsx}）を適切に設定する

## 完了条件（Definition of Done）

本要件は以下の条件を全て満たす場合に完了とする：

1. ✅ 全Acceptance Criteriaが実装され、検証可能
2. ✅ `npm test` コマンドで全テストが成功
3. ✅ `npm test:coverage` コマンドでカバレッジレポートが生成され、80%閾値を達成可能
4. ✅ CI/CD（GitHub Actions）でテストが自動実行され、成功
5. ✅ テスト記述ガイドラインとトラブルシューティングガイドが作成
6. ✅ 4種類のテストサンプル（Client Component、Server Actions、カスタムフック、API Fetch）が正常動作
7. ✅ 既存のESLint + Prettierワークフローとの統合が確認済み

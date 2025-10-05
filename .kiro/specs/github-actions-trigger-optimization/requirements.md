# Requirements Document

## GitHub Issue Information

**Issue**: [#66](https://github.com/ef-tech/template/issues/66) - GitHub Actions の発火タイミングを最適化
**Labels**: なし
**Milestone**: なし
**Assignees**: なし

## Introduction

GitHub Actions ワークフローの発火タイミング最適化により、CI/CDパイプラインの効率化とコスト削減を実現する。現在、4つのワークフロー（frontend-test.yml、php-quality.yml、test.yml、e2e-tests.yml）が存在し、関連性のないファイル変更でもすべて実行される問題がある。本要件では、concurrency設定、paths設定、pull_request.types明示、キャッシング統一化を通じて、実行時間30-40%削減、実行頻度60-70%削減を目標とする。

### ビジネス価値
- **コスト削減**: GitHub Actions使用コストの削減（実行頻度60-70%削減）
- **開発効率向上**: CI/CDフィードバック時間の短縮（実行時間30-40%削減）
- **品質保証強化**: API契約変更の早期検出によるバグ予防

### 対象範囲
- 対象ワークフロー: frontend-test.yml、php-quality.yml、test.yml、e2e-tests.yml
- 対象外: ワークフロー実行環境変更、テストコード最適化、新規ワークフロー追加

## Requirements

### Requirement 1: Concurrency制御による重複実行削減
**Objective:** CI/CDエンジニアとして、Pull Request時の最新コミット以外のワークフロー実行をキャンセルしたい。これにより、古いコミットの不要な実行を削減し、リソースを効率化できる。

#### Acceptance Criteria

1. WHEN Pull Request に新しいコミットがプッシュされた THEN GitHub Actions ワークフロー SHALL 同じPR内の古い実行をキャンセルする
2. WHEN main ブランチに直接プッシュされた THEN GitHub Actions ワークフロー SHALL 並列実行を許可する（キャンセルしない）
3. WHERE frontend-test.yml ワークフロー THE concurrency設定 SHALL `group: ${{ github.workflow }}-${{ github.event_name }}-${{ github.ref }}` を使用する
4. WHERE frontend-test.yml ワークフロー THE concurrency設定 SHALL `cancel-in-progress: ${{ github.event_name == 'pull_request' }}` を使用する
5. WHERE php-quality.yml ワークフロー THE concurrency設定 SHALL frontend-test.ymlと同一のgroup/cancel-in-progress設定を使用する
6. WHERE test.yml ワークフロー THE concurrency設定 SHALL frontend-test.ymlと同一のgroup/cancel-in-progress設定を使用する
7. WHERE e2e-tests.yml ワークフロー THE concurrency設定 SHALL 既に実装済みの設定を維持する

### Requirement 2: Paths設定による担当領域の明確化
**Objective:** CI/CDエンジニアとして、ファイル変更に応じて必要なワークフローのみを実行したい。これにより、不要なワークフロー実行を削減し、CI/CDパイプラインを高速化できる。

#### Acceptance Criteria

1. WHEN フロントエンド関連ファイルのみが変更された THEN frontend-test.yml ワークフロー SHALL 実行される
2. WHEN フロントエンド関連ファイルのみが変更された THEN php-quality.yml と test.yml ワークフロー SHALL スキップされる
3. WHEN バックエンド関連ファイルのみが変更された THEN php-quality.yml と test.yml ワークフロー SHALL 実行される
4. WHEN バックエンド関連ファイルのみが変更された THEN frontend-test.yml ワークフロー SHALL スキップされる（ただしAPI契約関連ファイルを除く）
5. WHERE frontend-test.yml ワークフロー THE paths設定 SHALL フロントエンドディレクトリ（frontend/**, test-utils/**）を含む
6. WHERE frontend-test.yml ワークフロー THE paths設定 SHALL テスト設定ファイル（jest.base.js, jest.config.js, jest.setup.ts, tsconfig.test.json）を含む
7. WHERE frontend-test.yml ワークフロー THE paths設定 SHALL コード品質設定ファイル（eslint.config.mjs, frontend/.eslint.base.mjs, .prettierrc, .prettierignore）を含む
8. WHERE frontend-test.yml ワークフロー THE paths設定 SHALL 依存関係ファイル（package.json, package-lock.json, frontend/**/package.json, frontend/**/package-lock.json）を含む
9. WHERE frontend-test.yml ワークフロー THE paths設定 SHALL ワークフロー自身（.github/workflows/frontend-test.yml）を含む
10. WHERE php-quality.yml ワークフロー THE paths設定 SHALL バックエンドディレクトリ（backend/laravel-api/**）を含む
11. WHERE php-quality.yml ワークフロー THE paths設定 SHALL ワークフロー自身（.github/workflows/php-quality.yml）を含む
12. WHERE test.yml ワークフロー THE paths設定 SHALL バックエンドディレクトリ（backend/laravel-api/**）を含む
13. WHERE test.yml ワークフロー THE paths設定 SHALL ワークフロー自身（.github/workflows/test.yml）を含む
14. WHERE e2e-tests.yml ワークフロー THE paths設定 SHALL 既存設定を維持する（frontend/**, backend/laravel-api/app/**, e2e/**など）

### Requirement 3: API契約整合性の早期検出
**Objective:** フロントエンドエンジニアとして、バックエンドAPIのレスポンス形式変更を早期に検出したい。これにより、APIモック（MSW）との不整合によるバグを予防できる。

#### Acceptance Criteria

1. WHEN バックエンドAPI Controllers（backend/laravel-api/app/Http/Controllers/Api/**）が変更された THEN frontend-test.yml ワークフロー SHALL 実行される
2. WHEN バックエンドAPI Resources（backend/laravel-api/app/Http/Resources/**）が変更された THEN frontend-test.yml ワークフロー SHALL 実行される
3. WHEN APIルート定義（backend/laravel-api/routes/api.php）が変更された THEN frontend-test.yml ワークフロー SHALL 実行される
4. WHEN バックエンドModels（backend/laravel-api/app/Models/**）が変更された THEN frontend-test.yml ワークフロー SHALL 実行される
5. WHERE frontend-test.yml ワークフロー THE paths設定 SHALL backend/laravel-api/app/Http/Controllers/Api/** を含む
6. WHERE frontend-test.yml ワークフロー THE paths設定 SHALL backend/laravel-api/app/Http/Resources/** を含む
7. WHERE frontend-test.yml ワークフロー THE paths設定 SHALL backend/laravel-api/routes/api.php を含む
8. WHERE frontend-test.yml ワークフロー THE paths設定 SHALL backend/laravel-api/app/Models/** を含む
9. IF actions.test.ts または api.test.ts のAPIモックテストが存在する THEN frontend-test.yml SHALL バックエンドAPI変更時に実行されてAPI契約整合性を検証する

### Requirement 4: Pull Request Types の明示
**Objective:** CI/CDエンジニアとして、必要なPull Requestイベントのみでワークフローを実行したい。これにより、ラベル追加などの軽微なイベントでの不要な実行を削減できる。

#### Acceptance Criteria

1. WHEN Pull Request が opened された THEN 全ワークフロー SHALL 実行される
2. WHEN Pull Request が synchronize された THEN 全ワークフロー SHALL 実行される
3. WHEN Pull Request が reopened された THEN 全ワークフロー SHALL 実行される
4. WHEN Pull Request が ready_for_review された THEN 全ワークフロー SHALL 実行される
5. WHEN Pull Request にラベルが追加された THEN 全ワークフロー SHALL スキップされる
6. WHERE frontend-test.yml ワークフロー THE pull_request.types設定 SHALL [opened, synchronize, reopened, ready_for_review] を指定する
7. WHERE php-quality.yml ワークフロー THE pull_request.types設定 SHALL [opened, synchronize, reopened, ready_for_review] を指定する
8. WHERE test.yml ワークフロー THE pull_request.types設定 SHALL [opened, synchronize, reopened, ready_for_review] を指定する
9. WHERE e2e-tests.yml ワークフロー THE pull_request.types設定 SHALL [opened, synchronize, reopened, ready_for_review] を指定する

### Requirement 5: 依存関係キャッシング統一化
**Objective:** CI/CDエンジニアとして、ワークフロー間でキャッシング戦略を統一したい。これにより、キャッシュ効率を向上し、実行時間を10-20%削減できる。

#### Acceptance Criteria

1. WHERE frontend-test.yml ワークフロー THE Node.jsキャッシュ SHALL setup-node内蔵キャッシュ（cache: npm）を使用する
2. WHERE frontend-test.yml ワークフロー THE setup-node設定 SHALL cache-dependency-pathに package-lock.json, frontend/admin-app/package-lock.json, frontend/user-app/package-lock.json を指定する
3. WHERE php-quality.yml ワークフロー THE Composerキャッシュ SHALL cache-files-dirキャッシュを使用する
4. WHERE php-quality.yml ワークフロー THE Composerキャッシュ SHALL `composer config cache-files-dir` の出力パスをキャッシュする
5. WHERE test.yml ワークフロー THE Composerキャッシュ SHALL php-quality.ymlと同一のcache-files-dir方式を使用する
6. WHERE e2e-tests.yml ワークフロー THE Node.jsキャッシュ SHALL setup-node内蔵キャッシュ（cache: npm）を使用する（既存設定維持）
7. WHERE e2e-tests.yml ワークフロー THE Composerキャッシュ SHALL cache-files-dir方式を使用する（既存設定維持）
8. IF キャッシュキー生成時 THEN 全ワークフロー SHALL composer.lock または package-lock.json のハッシュ値を使用する
9. IF キャッシュが見つからない場合 THEN 全ワークフロー SHALL restore-keysで部分一致キャッシュを使用する

### Requirement 6: ブランチプロテクション対応
**Objective:** CI/CDエンジニアとして、paths設定追加後もブランチプロテクションの必須チェックが正常に動作することを保証したい。これにより、mainブランチへのマージ制御を維持できる。

#### Acceptance Criteria

1. IF paths設定によりワークフローがスキップされた場合 THEN ブランチプロテクション SHALL 必須チェックを満たしたと認識する
2. WHEN フロントエンドのみ変更されたPRで php-quality.yml がスキップされた THEN ブランチプロテクション SHALL PRマージを許可する
3. WHEN バックエンドのみ変更されたPRで frontend-test.yml がスキップされた THEN ブランチプロテクション SHALL PRマージを許可する
4. IF ブランチプロテクション設定で必須チェックが指定されている THEN GitHub Actions SHALL paths設定によるスキップを成功として扱う

### Requirement 7: php-quality.yml と test.yml の統合検討
**Objective:** CI/CDエンジニアとして、php-quality.yml と test.yml の統合可能性を評価したい。これにより、ワークフロー管理を一元化し、設定の重複を削減できる。

#### Acceptance Criteria

1. IF php-quality.yml と test.yml が統合される場合 THEN 新ワークフロー SHALL backend-quality-tests.yml として作成される
2. IF php-quality.yml と test.yml が統合される場合 THEN 新ワークフロー SHALL quality ジョブと test ジョブを別々に定義する
3. IF 統合ワークフロー作成時 THEN 共有セットアップ（checkout, PHP setup）SHALL 各ジョブで独立して実行される（ジョブ間依存なし）
4. IF 統合ワークフロー作成時 THEN paths設定、concurrency設定、pull_request.types設定 SHALL 一元管理される
5. IF php-quality.yml と test.yml が分離維持される場合 THEN 両ワークフロー SHALL 同一のpaths設定、concurrency設定を維持する

### Requirement 8: パフォーマンス目標達成
**Objective:** プロダクトオーナーとして、GitHub Actions最適化の定量的な成果を確認したい。これにより、投資対効果を評価し、継続的改善の基準を設定できる。

#### Acceptance Criteria

1. WHEN 最適化完了後のベンチマーク測定時 THEN CI/CDパイプライン全体の実行時間 SHALL 最適化前比で30-40%削減されている
2. WHEN 最適化完了後の1週間の実行ログ分析時 THEN ワークフロー実行頻度 SHALL 最適化前比で60-70%削減されている
3. WHEN キャッシュヒット率測定時 THEN Node.js/Composerキャッシュ SHALL 80%以上のヒット率を達成する
4. WHEN frontend-test.yml 実行頻度測定時 THEN バックエンドAPI契約変更時の実行頻度 SHALL 適切な範囲内である（過剰実行なし）
5. IF パフォーマンス目標未達成の場合 THEN 実装チーム SHALL 原因分析と追加最適化施策を実施する

### Requirement 9: 動作確認とテスト戦略
**Objective:** QAエンジニアとして、最適化後のワークフローが正常に動作することを検証したい。これにより、本番環境での不具合を予防できる。

#### Acceptance Criteria

1. WHEN 同じPRに2つのコミットを連続プッシュしたテスト THEN GitHub Actions SHALL 1つ目の実行をキャンセルする
2. WHEN フロントエンドのみ変更したPRのテスト THEN frontend-test.yml のみ SHALL 実行される
3. WHEN バックエンドのみ変更したPRのテスト THEN php-quality.yml と test.yml のみ SHALL 実行される
4. WHEN バックエンドAPI Resourcesのみ変更したPRのテスト THEN frontend-test.yml, php-quality.yml, test.yml, e2e-tests.yml すべて SHALL 実行される
5. WHEN キャッシュ動作確認テスト THEN GitHub Actionsログ SHALL Cache hit を表示する
6. WHEN PRラベル追加テスト THEN 全ワークフロー SHALL スキップされる

### Requirement 10: ドキュメント更新
**Objective:** ドキュメンテーションチームとして、最適化内容を文書化したい。これにより、チームメンバーが変更内容を理解し、将来の保守を容易にできる。

#### Acceptance Criteria

1. WHEN 最適化完了時 THEN README.md SHALL GitHub Actions最適化セクションを含む
2. WHERE README.md GitHub Actions最適化セクション THE ドキュメント SHALL concurrency設定の説明を含む
3. WHERE README.md GitHub Actions最適化セクション THE ドキュメント SHALL paths設定の説明を含む
4. WHERE README.md GitHub Actions最適化セクション THE ドキュメント SHALL API契約整合性検証の説明を含む
5. WHERE README.md GitHub Actions最適化セクション THE ドキュメント SHALL パフォーマンス改善メトリクスを含む
6. IF .kiro/docs/ にドキュメント作成する場合 THEN ドキュメント SHALL github-actions-optimization-plan.md を含む
7. IF .kiro/docs/ にドキュメント作成する場合 THEN ドキュメント SHALL 段階的実装手順を含む
8. IF .kiro/docs/ にドキュメント作成する場合 THEN ドキュメント SHALL トラブルシューティングガイドを含む

## Technology Stack

**Backend**: Laravel 12, PHP 8.4, Composer, Pest 4
**Frontend**: Next.js 15.5, React 19, Jest 29, Testing Library 16, Node.js 20
**Infrastructure**: GitHub Actions, Docker, Docker Compose, Ubuntu
**Tools**: Playwright 1.47.2, Laravel Pint, Larastan (PHPStan Level 8), ESLint 9, Prettier 3, Codecov

## Project Structure

```
.github/workflows/
  ├── frontend-test.yml       # Jest + Testing Library テスト
  ├── php-quality.yml         # Laravel Pint + Larastan 静的解析
  ├── test.yml                # Pest 4 テスト (4 Shards 並列実行)
  └── e2e-tests.yml           # Playwright E2E テスト (4 Shards 並列実行)

backend/laravel-api/
  ├── app/Http/Controllers/Api/**  # API Controllers（API契約）
  ├── app/Http/Resources/**        # API Resources（API契約）
  ├── routes/api.php               # APIルート定義（API契約）
  └── app/Models/**                # Models（API契約）

frontend/
  ├── admin-app/              # 管理者向けアプリケーション
  └── user-app/               # エンドユーザー向けアプリケーション

e2e/                          # E2Eテスト環境
test-utils/                   # 共通テストユーティリティ
```

## Definition of Done

- [ ] 全ワークフローに concurrency 設定追加完了
- [ ] 全ワークフローに paths 設定追加完了（frontend-test.yml にAPI契約関連ファイル含む）
- [ ] 全ワークフローに pull_request.types 明示完了
- [ ] キャッシング統一化完了
- [ ] php-quality.yml と test.yml の統合判断完了（統合 or 分離維持）
- [ ] 動作確認テスト全項目クリア（Concurrency、Paths、API契約変更テスト）
- [ ] ブランチプロテクション設定の影響確認完了
- [ ] パフォーマンス目標達成（実行時間30-40%削減、実行頻度60-70%削減、キャッシュヒット率80%以上）
- [ ] ドキュメント更新完了（README.md、.kiro/docs/）
- [ ] チームレビュー完了

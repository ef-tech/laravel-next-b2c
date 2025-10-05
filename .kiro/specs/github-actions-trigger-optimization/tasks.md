# Implementation Plan

## Phase 1: Concurrency設定追加（Week 1）

- [ ] 1. ベースライン測定とワークフロー分析
  - 現在のワークフロー実行時間を測定し、ベースラインを記録
  - 過去1週間のワークフロー実行頻度を集計
  - 各ワークフローの平均実行時間とリソース使用量を記録
  - パフォーマンス目標（30-40%実行時間削減、60-70%実行頻度削減）のベースラインを設定
  - _Requirements: 8.1, 8.2_

- [x] 2. frontend-test.ymlにconcurrency設定を追加
  - ワークフローファイルの先頭にconcurrency設定を追加
  - groupキーに `${{ github.workflow }}-${{ github.event_name }}-${{ github.ref }}` を設定
  - cancel-in-progressに `${{ github.event_name == 'pull_request' }}` を設定
  - Pull Request時の重複実行削減機能を実装
  - _Requirements: 1.1, 1.2, 1.3, 1.4_

- [x] 3. php-quality.ymlにconcurrency設定を追加
  - frontend-test.ymlと同一のconcurrency設定を追加
  - groupキーとcancel-in-progress条件を統一
  - バックエンド品質チェックの重複実行削減を実装
  - _Requirements: 1.1, 1.2, 1.5_

- [x] 4. test.ymlにconcurrency設定を追加
  - frontend-test.ymlと同一のconcurrency設定を追加
  - 4 Shards並列実行と併用可能なconcurrency設定を実装
  - バックエンドテストの重複実行削減を実装
  - _Requirements: 1.1, 1.2, 1.6_

- [x] 5. e2e-tests.ymlのconcurrency設定を確認
  - 既存のconcurrency設定が要件を満たしているか確認
  - groupキーとcancel-in-progress設定の動作を検証
  - 必要に応じて設定を調整
  - _Requirements: 1.7_

- [x] 6. Concurrency動作確認テスト
  - 同じPRに2つのコミットを連続プッシュし、古い実行がキャンセルされることを確認
  - mainブランチへの直接プッシュで並列実行が許可されることを確認
  - GitHub ActionsログでConcurrency動作を検証
  - 重複実行削減による実行頻度の変化を測定
  - _Requirements: 9.1_

## Phase 2: Paths設定追加（Week 2-3）

- [x] 7. frontend-test.ymlにpaths設定を追加（基本パス）
  - on.push.pathsとon.pull_request.pathsを追加
  - フロントエンドディレクトリ（frontend/**, test-utils/**）を指定
  - テスト設定ファイル（jest.base.js, jest.config.js, jest.setup.ts, tsconfig.test.json）を指定
  - コード品質設定ファイル（eslint.config.mjs, frontend/.eslint.base.mjs, .prettierrc, .prettierignore）を指定
  - _Requirements: 2.1, 2.5, 2.6, 2.7_

- [x] 8. frontend-test.ymlにpaths設定を追加（依存関係とワークフロー）
  - 依存関係ファイル（package.json, package-lock.json, frontend/**/package.json, frontend/**/package-lock.json）を指定
  - ワークフロー自身（.github/workflows/frontend-test.yml）を指定
  - フロントエンド関連ファイル変更時のみ実行される設定を完成
  - _Requirements: 2.8, 2.9_

- [x] 9. frontend-test.ymlにAPI契約関連パスを追加
  - backend/laravel-api/app/Http/Controllers/Api/** を追加
  - backend/laravel-api/app/Http/Resources/** を追加
  - backend/laravel-api/routes/api.php を追加
  - backend/laravel-api/app/Models/** を追加
  - バックエンドAPI変更時のフロントエンドテスト実行を実装
  - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 3.7, 3.8, 3.9_

- [x] 10. php-quality.ymlにpaths設定を追加
  - on.push.pathsとon.pull_request.pathsを追加
  - バックエンドディレクトリ（backend/laravel-api/**）を指定
  - ワークフロー自身（.github/workflows/php-quality.yml）を指定
  - バックエンド関連ファイル変更時のみ実行される設定を実装
  - _Requirements: 2.3, 2.10, 2.11_

- [x] 11. test.ymlにpaths設定を追加
  - on.push.pathsとon.pull_request.pathsを追加
  - バックエンドディレクトリ（backend/laravel-api/**）を指定
  - ワークフロー自身（.github/workflows/test.yml）を指定
  - バックエンド関連ファイル変更時のみ実行される設定を実装
  - _Requirements: 2.3, 2.12, 2.13_

- [x] 12. e2e-tests.ymlのpaths設定を確認
  - 既存のpaths設定が要件を満たしているか確認
  - frontend/**, backend/laravel-api/app/**, e2e/** の設定を検証
  - 必要に応じてpaths設定を拡張
  - _Requirements: 2.14_

- [x] 13. Paths Filter動作確認テスト（フロントエンドのみ変更）
  - フロントエンド関連ファイルのみを変更
  - frontend-test.ymlのみが実行されることを確認
  - php-quality.ymlとtest.ymlがスキップされることを確認
  - GitHub ActionsログでPaths Filter動作を検証
  - _Requirements: 9.2_

- [x] 14. Paths Filter動作確認テスト（バックエンドのみ変更）
  - バックエンド関連ファイルのみを変更
  - php-quality.ymlとtest.ymlのみが実行されることを確認
  - frontend-test.ymlがスキップされることを確認（API契約関連ファイル以外）
  - GitHub ActionsログでPaths Filter動作を検証
  - _Requirements: 9.3_

- [x] 15. Paths Filter動作確認テスト（API契約変更）
  - バックエンドAPI Resources（backend/laravel-api/app/Http/Resources/**）を変更
  - frontend-test.yml, php-quality.yml, test.yml, e2e-tests.ymlすべてが実行されることを確認
  - API契約整合性の早期検出が機能することを検証
  - GitHub ActionsログでPaths Filter動作を検証
  - _Requirements: 9.4_

## Phase 3: Pull Request Types明示（Week 3）

- [x] 16. 全ワークフローにpull_request.types設定を追加
  - frontend-test.ymlのon.pull_request.typesに [opened, synchronize, reopened, ready_for_review] を指定
  - php-quality.ymlのon.pull_request.typesに [opened, synchronize, reopened, ready_for_review] を指定
  - test.ymlのon.pull_request.typesに [opened, synchronize, reopened, ready_for_review] を指定
  - e2e-tests.ymlのon.pull_request.typesに [opened, synchronize, reopened, ready_for_review] を指定
  - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.6, 4.7, 4.8, 4.9_

- [ ] 17. Pull Request Types動作確認テスト
  - Pull Requestにラベルを追加し、全ワークフローがスキップされることを確認
  - Draft PRをready_for_reviewに変更し、全ワークフローが実行されることを確認
  - 必要なイベントタイプのみでワークフローが実行されることを検証
  - _Requirements: 4.5, 9.6_

## Phase 4: キャッシング統一化（Week 4）

- [x] 18. frontend-test.ymlのキャッシュ設定をsetup-node内蔵に統一
  - actions/setup-node@v4のcacheパラメータに npm を指定
  - cache-dependency-pathに package-lock.json, frontend/admin-app/package-lock.json, frontend/user-app/package-lock.json を指定
  - 既存の独自キャッシュ設定（actions/cache@v4でのnpm cache）を削除
  - setup-node内蔵キャッシュによる効率化を実装
  - _Requirements: 5.1, 5.2_

- [x] 19. php-quality.ymlのキャッシュ設定をcache-files-dirに統一
  - composer config cache-files-dirの出力パスをキャッシュする設定を確認
  - キャッシュキーに composer.lock のハッシュ値を使用
  - restore-keysで部分一致キャッシュを設定
  - Composerキャッシュの効率化を実装
  - _Requirements: 5.3, 5.4, 5.8, 5.9_

- [x] 20. test.ymlのキャッシュ設定をcache-files-dirに統一
  - php-quality.ymlと同一のComposerキャッシュ設定を適用
  - キャッシュキーとrestore-keysを統一
  - Composerキャッシュの効率化を実装
  - _Requirements: 5.5, 5.8, 5.9_

- [x] 21. e2e-tests.ymlのキャッシュ設定を確認
  - 既存のsetup-node内蔵キャッシュ設定を確認
  - 既存のComposer cache-files-dirキャッシュ設定を確認
  - キャッシュ設定が最適化されていることを検証
  - _Requirements: 5.6, 5.7_

- [ ] 22. キャッシュ動作確認テスト
  - GitHub ActionsログでCache hitが表示されることを確認
  - Node.js/Composerキャッシュのヒット率が80%以上であることを測定
  - キャッシュによる実行時間短縮効果を検証
  - _Requirements: 8.3, 9.5_

## Phase 5: パフォーマンス測定と検証（Week 4-5）

- [ ] 23. 最適化後のベンチマーク測定
  - 全ワークフローの実行時間を測定
  - ベースラインと比較し、30-40%の実行時間削減を確認
  - 実行時間削減が目標に達していない場合、原因分析を実施
  - _Requirements: 8.1_

- [ ] 24. ワークフロー実行頻度の測定
  - 最適化後1週間のワークフロー実行ログを集計
  - ベースラインと比較し、60-70%の実行頻度削減を確認
  - paths設定による不要実行削減効果を検証
  - _Requirements: 8.2_

- [ ] 25. frontend-test.yml実行頻度の詳細分析
  - バックエンドAPI契約変更時のfrontend-test.yml実行頻度を測定
  - 過剰実行がないことを確認
  - API契約関連パス追加の影響を評価
  - _Requirements: 8.4_

- [ ] 26. パフォーマンス目標未達成時の追加最適化
  - 目標未達成の場合、原因を分析（キャッシュミス、paths設定の過不足など）
  - 追加最適化施策を実施（paths設定の調整、キャッシュ戦略の見直しなど）
  - 再測定し、目標達成を確認
  - _Requirements: 8.5_

## Phase 6: ブランチプロテクション検証（Week 5）

- [ ] 27. ブランチプロテクション動作確認テスト
  - フロントエンドのみ変更したPRでphp-quality.ymlがスキップされることを確認
  - スキップされたPRでもブランチプロテクションがPRマージを許可することを確認
  - paths設定によるスキップが成功として扱われることを検証
  - _Requirements: 6.1, 6.2, 6.3, 6.4_

## Phase 7: ドキュメント更新（Week 5）

- [x] 28. README.mdにGitHub Actions最適化セクションを追加
  - concurrency設定の説明を追加
  - paths設定の説明を追加
  - API契約整合性検証の説明を追加
  - パフォーマンス改善メトリクス（実行時間30-40%削減、実行頻度60-70%削減、キャッシュヒット率80%以上）を追加
  - _Requirements: 10.1, 10.2, 10.3, 10.4, 10.5_

- [x] 29. .kiro/docs/にドキュメントを作成
  - github-actions-optimization-plan.md を作成し、最適化全体計画を記録
  - 段階的実装手順を記録
  - トラブルシューティングガイドを記録
  - _Requirements: 10.6, 10.7, 10.8_

## 要件カバレッジ確認

- Requirement 1（Concurrency制御）: Task 2, 3, 4, 5, 6
- Requirement 2（Paths設定）: Task 7, 8, 10, 11, 12, 13, 14
- Requirement 3（API契約整合性）: Task 9, 15
- Requirement 4（Pull Request Types）: Task 16, 17
- Requirement 5（キャッシング統一化）: Task 18, 19, 20, 21, 22
- Requirement 6（ブランチプロテクション）: Task 27
- Requirement 7（php-quality.yml/test.yml統合）: 設計判断で分離維持を選択（タスクなし）
- Requirement 8（パフォーマンス目標）: Task 1, 23, 24, 25, 26
- Requirement 9（動作確認）: Task 6, 13, 14, 15, 17, 22
- Requirement 10（ドキュメント更新）: Task 28, 29

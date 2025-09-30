# Pest 4 Migration Checklist

## Phase 1: 準備（Week 1）

### 環境セットアップ
- [x] Pest 4とプラグインのインストール（pest, pest-plugin-laravel, pest-plugin-arch）
- [x] `tests/Pest.php` 設定ファイル作成
- [x] `pest.xml` カバレッジ設定作成
- [x] Composer Scripts統合（test-pest, test-all, test-coverage等）
- [x] サンプルテスト作成（API専用、アーキテクチャ）

### 動作確認
- [ ] `composer test-pest` で空テスト成功
- [ ] `composer test-phpunit` で既存PHPUnitテスト成功
- [ ] `composer test-all` で両フレームワーク統合実行成功
- [ ] カスタムExpectation（toBeJsonOk, toHaveCors）が正常動作
- [ ] ヘルパー関数（actingAsApi, jsonHeaders）が正常動作

---

## Phase 2: 新規テストでPest採用（Week 2-3）

### 新機能開発時のルール
- [ ] 新規Feature Testは全てPestで作成
- [ ] 新規Unit TestもPestで作成
- [ ] チームメンバーへのPest構文トレーニング実施
- [ ] サンプルテスト（AuthenticationTest, CorsTest）を参考に作成

### 品質確認
- [ ] 新規Pestテストが5ファイル以上作成された
- [ ] 全テストが成功（`composer test-all`）
- [ ] コードレビューでPest構文の使い方を確認
- [ ] カスタムExpectationの活用が定着

---

## Phase 3: 既存テストの段階的移行（Week 4-6）

### 移行計画
- [ ] 既存テストファイル一覧を作成（Feature/Unit別）
- [ ] 移行優先度付け（Unit → Feature、シンプル → 複雑）
- [ ] 1日2-3ファイルペースで移行スケジュール作成

### 移行実施（各ファイルごと）
- [ ] `bash scripts/convert-phpunit-to-pest.sh <file>` で基本変換
- [ ] 手動レビュー・修正
  - `$this->assert*` → `expect()` 構文に変換
  - `setUp()` → `beforeEach()` に変換
  - `tearDown()` → `afterEach()` に変換
- [ ] `./vendor/bin/pest <file>` でテスト成功確認
- [ ] カバレッジ維持確認（`composer test-coverage`）
- [ ] 既存PHPUnitテストとの統合確認（`composer test-all`）

### 週次チェックポイント
- [ ] Week 4: Unit Test 50%移行完了
- [ ] Week 5: Unit Test 100%移行完了、Feature Test 30%移行完了
- [ ] Week 6: Feature Test 80%移行完了

---

## Phase 4: 完全移行（Week 7-8）

### 最終移行
- [ ] 残りのFeature Test 100%移行完了
- [ ] 全Pestテストが成功（`composer test-pest`）
- [ ] 全テスト統合実行成功（`composer test-all`）
- [ ] カバレッジ80%以上維持確認

### アーキテクチャテスト追加
- [ ] LayerTest（レイヤー分離）追加・実行成功
- [ ] NamingTest（命名規則）追加・実行成功
- [ ] QualityTest（コード品質）追加・実行成功
- [ ] アーキテクチャテストで設計違反がないことを確認

### CI/CD統合
- [ ] `.github/workflows/test.yml` でPest対応ワークフロー作成
- [ ] Test Sharding（4並列）設定
- [ ] カバレッジレポートCodecov連携
- [ ] Pull Request時のCI/CD全パイプライン成功

### ドキュメント整備
- [ ] Pest 4新機能ガイド作成（PEST4_NEW_FEATURES.md）
- [ ] トラブルシューティングガイド作成（PEST_TROUBLESHOOTING.md）
- [ ] コーディング規約作成（PEST_CODING_STANDARDS.md）
- [ ] チームへの移行完了報告

### PHPUnit完全削除（オプション）
- [ ] PHPUnitを完全削除するか判断（PHPUnit保守維持も選択肢）
- [ ] PHPUnit削除する場合：`composer.json`からPHPUnit削除、`test-phpunit`スクリプト削除
- [ ] 削除後の全テスト実行確認

---

## Rollback Trigger（ロールバック基準）

各フェーズで以下の問題が発生した場合、前フェーズに戻る：

- **Phase 1**: Pestインストール失敗、サンプルテスト動作せず
- **Phase 2**: Pest構文エラーが頻発、開発速度が大幅に低下
- **Phase 3**: 移行後テストが継続失敗、カバレッジ80%未満
- **Phase 4**: アーキテクチャテストで致命的な設計違反検出、CI/CD失敗継続

---

## Tips

- **変換スクリプトは基本的な構文のみサポート**: 複雑なテストは手動変換が必要
- **並行開発**: 既存テストはPHPUnit、新規テストはPestで並行開発可能
- **カバレッジ維持**: 移行中もカバレッジ80%以上を維持
- **アーキテクチャテスト**: Phase 4で追加、設計原則の自動検証を実現
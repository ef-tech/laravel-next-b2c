# 実装計画

## 実装タスク概要

本実装計画は、Laravel 12プロジェクトにPest 4テストフレームワークを導入し、既存PHPUnit 11.5テストと段階的に併用する環境を構築する。API専用アーキテクチャに特化したテストサンプル（Sanctum認証、CORS、JSON API）とアーキテクチャテストを提供し、CI/CDパイプラインをTest Sharding（4並列）に対応させる。

---

- [ ] 1. Pest 4環境のインストールと基本設定
- [ ] 1.1 Pest 4とプラグインのインストール
  - Composer経由でPest 4コアパッケージとプラグイン（pest-plugin-laravel、pest-plugin-arch、collision）をインストール
  - composer.jsonのrequire-devセクションにパッケージバージョンを記録
  - composer.lockを更新してパッケージ依存関係を固定
  - _Requirements: 1.1_

- [ ] 1.2 Pest環境の初期化と設定ファイル作成
  - Laravel Artisan pest:installコマンドを実行してPest環境を初期化
  - tests/Pest.phpファイルを生成しLaravel統合設定を記述
  - pest.xmlを作成してテストスイート定義とカバレッジ設定を記述
  - _Requirements: 1.2, 1.6_

- [ ] 1.3 Composer Scriptsの統合とテスト実行コマンド追加
  - composer.jsonにtest-pestスクリプトを追加（設定クリア後にPest実行）
  - composer.jsonにtest-allスクリプトを追加（PHPUnit→Pest順次実行）
  - composer.jsonにtest-coverage、test-parallel、test-shardスクリプトを追加
  - 各スクリプトが正常に動作することを確認
  - _Requirements: 1.3, 1.4, 9.1, 9.2, 9.3, 9.4_

- [ ] 2. Laravel統合設定とカスタムExpectationの作成
- [ ] 2.1 tests/Pest.phpにLaravel統合設定を記述
  - uses(TestCase::class)->in('Feature', 'Unit')でLaravel TestCaseを自動適用
  - uses(RefreshDatabase::class)->in('Feature')でFeatureテスト時のDB自動リフレッシュを有効化
  - beforeEach()でグローバル共通セットアップ処理を定義
  - _Requirements: 1.5, 2.1, 2.6_

- [ ] 2.2 API専用カスタムExpectationの定義
  - toBeJsonOk Custom Expectationを定義（HTTP 200 + Content-Type: application/json検証）
  - toHaveCors Custom Expectationを定義（CORSヘッダー3種検証）
  - カスタムExpectationが正しく動作することを簡易テストで確認
  - _Requirements: 2.3, 2.4_

- [ ] 2.3 API専用ヘルパー関数の実装
  - actingAsApi()ヘルパー関数を定義（Sanctum認証設定とabilities指定）
  - jsonHeaders()ヘルパー関数を定義（Accept/Content-Type: application/json + 追加ヘッダーマージ）
  - ヘルパー関数がグローバルスコープで使用可能であることを確認
  - _Requirements: 2.2, 2.5_

- [ ] 3. API専用テストサンプルの作成
- [ ] 3.1 Sanctum認証テストサンプルの作成
  - tests/Feature/Api/AuthenticationTest.phpを作成
  - 認証済みユーザープロファイル取得テストを実装（/api/me → 200 OK + ユーザーID/email検証）
  - 未認証アクセス拒否テストを実装（/api/me → 401 Unauthorized検証）
  - トークンabilitiesテストを実装（読み取り権限のみで書き込みAPI → 403 Forbidden検証）
  - actingAsApi()とtoBeJsonOk()を使用してテストコードを簡潔化
  - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 3.6_

- [ ] 3.2 CORSテストサンプルの作成
  - tests/Feature/Api/CorsTest.phpを作成
  - 許可オリジンからのOPTIONSリクエストテストを実装（Origin: http://localhost:3000 → CORSヘッダー検証）
  - 実際のGETリクエストでのCORSヘッダーテストを実装（Origin: http://localhost:3000 → Access-Control-Allow-Origin検証）
  - toHaveCors()カスタムExpectationとjsonHeaders()ヘルパーを活用
  - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5_

- [ ] 3.3 JSON APIレスポンステストサンプルの作成
  - tests/Feature/Api/JsonApiTest.phpを作成
  - リソース作成テストを実装（POST /api/resources → 201 Created + JSON:API構造検証）
  - ペジネーションテストを実装（GET /api/users?page=1&per_page=10 → ペジネーションメタデータ検証）
  - assertJson()とassertJsonStructure()でレスポンス詳細検証を実装
  - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5, 5.6_

- [ ] 4. アーキテクチャテストの作成
- [ ] 4.1 レイヤー分離テストの作成
  - tests/Architecture/LayerTest.phpを作成
  - arch()構文でControllerがModelに直接依存しないルールを実装
  - toOnlyUse()でControllerの許可依存関係（Illuminate、Services、Requests）を定義
  - ModelがControllerに依存しないルールを実装
  - _Requirements: 6.1, 6.2_

- [ ] 4.2 命名規則テストの作成
  - tests/Architecture/NamingTest.phpを作成
  - arch()構文でController、Request、Resourceの接尾辞検証ルールを実装
  - toHaveSuffix()でクラス命名規則を自動検証
  - ModelにModel接尾辞がないことを検証（Laravel規約準拠）
  - _Requirements: 6.3, 6.4_

- [ ] 4.3 コード品質テストの作成
  - tests/Architecture/QualityTest.phpを作成
  - arch()構文でデバッグ関数（dd、dump、var_dump等）の使用を禁止
  - toUseStrictTypes()でstrictモード宣言を検証
  - toBeFinal()でValueObjectsのfinalクラス宣言を検証
  - _Requirements: 6.5, 6.6_

- [ ] 5. 段階的移行ツールとドキュメントの整備
- [ ] 5.1 PHPUnit→Pest変換スクリプトの作成
  - backend/laravel-api/scripts/convert-phpunit-to-pest.shを作成
  - Bashスクリプトで基本的なPest構文への変換ロジックを実装（namespace削除、class定義削除、test_メソッド→it()変換）
  - 元ファイルのバックアップ作成（.bak拡張子）と変換結果表示機能を実装
  - スクリプト実行権限を付与（chmod +x）
  - _Requirements: 7.1, 7.2_

- [ ] 5.2 移行チェックリストドキュメントの作成
  - backend/laravel-api/docs/PEST_MIGRATION_CHECKLIST.mdを作成
  - Phase 1（準備）、Phase 2（新規テストPest採用）、Phase 3（既存テスト移行）、Phase 4（完全移行）の4段階チェックリストを記述
  - 各フェーズのタスク詳細と検証項目を記載
  - _Requirements: 7.3_

- [ ] 5.3 Pest 4新機能ガイドの作成
  - backend/laravel-api/docs/PEST4_NEW_FEATURES.mdを作成
  - Pest 4のBrowser Testing、Test Sharding、Visual Testing機能の概要を記述
  - 将来的な活用方法と参考リンクを記載
  - _Requirements: 7.4_

- [ ] 5.4 トラブルシューティングガイドとコーディング規約の作成
  - backend/laravel-api/docs/PEST_TROUBLESHOOTING.mdを作成（よくあるエラー、デバッグ方法、パフォーマンス最適化）
  - backend/laravel-api/docs/PEST_CODING_STANDARDS.mdを作成（テスト命名規則、ファイル構成ルール、カスタムExpectation作成方法）
  - 開発者向けベストプラクティスを記載
  - _Requirements: 7.5, 7.6_

- [ ] 6. CI/CD設定の更新とTest Sharding対応
- [ ] 6.1 GitHub Actionsテストワークフローの作成
  - .github/workflows/test.ymlを作成
  - マトリックス戦略でtest-suite（pest、phpunit、integration）とshard（1、2、3、4）の2次元並列化を定義
  - PHP 8.4環境セットアップ（shivammathur/setup-php@v2使用）と必要な拡張インストールを設定
  - _Requirements: 8.1, 8.6_

- [ ] 6.2 Pestテスト並列実行とShardingの実装
  - Pest test-suite実行時に--shard=${{ matrix.shard }}/4オプションを指定
  - PHPUnit test-suite実行時にcomposer test-phpunitを実行（移行期間中のみ）
  - Integration test-suite実行時にcomposer test-allを実行（両フレームワーク統合）
  - _Requirements: 8.2, 8.3, 8.4_

- [ ] 6.3 カバレッジレポート生成とCodecov連携
  - Pest test-suite、shard=1実行時にカバレッジレポート（coverage.xml）を生成
  - codecov/codecov-action@v3でCodecovにカバレッジレポートをアップロード
  - カバレッジ最小80%の品質ゲート検証を設定
  - _Requirements: 8.5, 9.5, 9.6_

- [ ] 7. 動作確認とテスト品質保証
- [ ] 7.1 Pestサンプルテストの実行と検証
  - composer test-pestを実行して全Pestサンプルテスト（API専用、アーキテクチャ）が成功することを確認
  - テスト結果が緑色（PASS）で表示され、エラーがないことを確認
  - _Requirements: 10.1_

- [ ] 7.2 既存PHPUnitテストとの統合確認
  - composer test-phpunitを実行して既存PHPUnitテストが正常動作することを確認
  - composer test-allを実行してPHPUnitとPestの両方が順次実行され、全テストが成功することを確認
  - _Requirements: 10.2, 10.3_

- [ ] 7.3 カバレッジと並列実行の検証
  - composer test-coverageを実行してカバレッジレポートが生成され、カバレッジ80%以上を達成していることを確認
  - composer test-parallelを実行して並列実行が成功し、実行時間が短縮されていることを確認
  - _Requirements: 10.4, 10.5_

- [ ] 7.4 CI/CD全パイプライン統合確認
  - GitHub Actionsで全CI/CDパイプラインを実行し、pest/phpunit/integrationマトリックスと4並列shardingで全テストが成功することを確認
  - カバレッジレポートがCodecovに正常にアップロードされることを確認
  - Pull Requestステータスチェックで緑色チェックマークが表示されることを確認
  - _Requirements: 10.6_

---

## 要件カバレッジマップ

| タスク | カバーする要件 |
|--------|--------------|
| 1.1 | Requirement 1.1 |
| 1.2 | Requirement 1.2, 1.6 |
| 1.3 | Requirement 1.3, 1.4, 9.1, 9.2, 9.3, 9.4 |
| 2.1 | Requirement 1.5, 2.1, 2.6 |
| 2.2 | Requirement 2.3, 2.4 |
| 2.3 | Requirement 2.2, 2.5 |
| 3.1 | Requirement 3.1-3.6 |
| 3.2 | Requirement 4.1-4.5 |
| 3.3 | Requirement 5.1-5.6 |
| 4.1 | Requirement 6.1, 6.2 |
| 4.2 | Requirement 6.3, 6.4 |
| 4.3 | Requirement 6.5, 6.6 |
| 5.1 | Requirement 7.1, 7.2 |
| 5.2 | Requirement 7.3 |
| 5.3 | Requirement 7.4 |
| 5.4 | Requirement 7.5, 7.6 |
| 6.1 | Requirement 8.1, 8.6 |
| 6.2 | Requirement 8.2, 8.3, 8.4 |
| 6.3 | Requirement 8.5, 9.5, 9.6 |
| 7.1 | Requirement 10.1 |
| 7.2 | Requirement 10.2, 10.3 |
| 7.3 | Requirement 10.4, 10.5 |
| 7.4 | Requirement 10.6 |

## 実装の注意事項

- **段階的移行**: 既存PHPUnitテストは保持し、新規テストはPestで作成することで学習コストを分散
- **カスタムExpectation活用**: API専用テストパターンを簡潔化し、テストコードの可読性を向上
- **Test Sharding**: CI/CD並列実行で開発効率を向上、Pull Request時のフィードバック時間を短縮
- **品質保証**: カバレッジ80%以上を維持し、アーキテクチャテストで設計原則を自動検証
# 要件ドキュメント

## プロジェクト概要（入力）
Laravel の PHPUnit テストを Pest テストに完全移行する

## 導入

本プロジェクトは、Laravel 12アプリケーションの既存PHPUnitテストスイート（21ファイル、90+テストケース）をPest 4テストフレームワークに完全移行することを目的としています。Pest移行基盤は既に構築済み（`laravel-pest-migration`仕様完了）であり、以下が利用可能です：

- **Pest 4基盤**: 設定済みの`tests/Pest.php`とPestサンプルテスト
- **変換スクリプト**: `scripts/convert-phpunit-to-pest.sh`による基本変換ツール
- **テスト体制**: API専用テストサンプル（Sanctum認証、CORS、JSON）とアーキテクチャテスト
- **CI/CD統合**: 段階的移行対応のGitHub Actions設定

本移行により、Pestのモダンな構文による可読性向上、開発者体験の改善、テストメンテナンスの効率化を実現します。移行後もすべてのテストケースが同等の品質と機能を維持し、既存の90+テストケースすべてがPest形式で実行可能となります。

## 要件

### 要件1: PHPUnit テストファイルのPest形式への完全移行
**目的:** テスト保守担当者として、既存のPHPUnitテストをすべてPest形式に変換し、統一されたテストコードベースを維持したい。これにより、新規テスト作成時の一貫性を確保し、開発者の学習コストを削減する。

#### 受入基準
1. WHEN バックエンドテストディレクトリに21個のPHPUnitテストファイルが存在する THEN テスト移行システム SHALL すべてのPHPUnitテストをPest形式に変換する
2. WHEN PHPUnitクラスベーステスト（`class XxxTest extends TestCase`）が検出される THEN テスト移行システム SHALL Pest関数ベース構文（`it('description', function() {})`）に変換する
3. WHEN PHPUnitアサーション（`$this->assert*`）が使用されている THEN テスト移行システム SHALL Pestの`expect()`構文に変換する
4. WHEN 複数のテストメソッド（`test_xxx()`または`/** @test */`アノテーション付きメソッド）がクラス内に存在する THEN テスト移行システム SHALL 各メソッドを独立した`it()`ブロックに分割する
5. WHEN setUp()メソッドまたはtearDown()メソッドが存在する THEN テスト移行システム SHALL Pestの`beforeEach()`/`afterEach()`に変換する

### 要件2: 既存テスト機能の完全互換性維持
**目的:** 品質保証担当者として、移行後もすべての既存テストケースが同等の品質で動作し、アプリケーションの品質保証レベルを維持したい。これにより、回帰バグの発生を防止し、継続的な品質保証を実現する。

#### 受入基準
1. WHEN 90以上のPHPUnitテストケースがすべてPest形式に変換される THEN テスト実行システム SHALL すべてのテストケースが成功する（グリーン状態）
2. WHEN Laravel固有のテストヘルパー（`$this->get()`, `$this->post()`, `assertStatus()`, `assertJson()`等）が使用されている THEN 移行後のPestテスト SHALL Laravelテストヘルパーを完全に利用可能とする
3. WHEN データベーステスト用の`RefreshDatabase`トレイトが使用されている THEN Pestテスト SHALL `uses(RefreshDatabase::class)`により同等のDB初期化機能を提供する
4. WHEN Sanctum認証テスト（`Sanctum::actingAs()`）が含まれる THEN Pestテスト SHALL カスタムヘルパー関数`actingAsApi()`により同等の認証機能を提供する
5. WHEN CORSやJSON APIレスポンス検証が含まれる THEN Pestテスト SHALL カスタム期待値（`toBeJsonOk()`, `toHaveCors()`）により同等の検証機能を提供する

### 要件3: テスト分類とディレクトリ構造の維持
**目的:** 開発者として、移行後もテストの分類（Feature/Unit/Architecture）とディレクトリ構造を維持し、テストの検索性と保守性を確保したい。これにより、既存の開発フローを変更せずに移行を完了する。

#### 受入基準
1. WHEN `tests/Feature/`ディレクトリに18個のFeatureテストが存在する THEN テスト移行システム SHALL 同一ディレクトリ構造でPestテストを作成する
2. WHEN `tests/Unit/`ディレクトリにUnitテストが存在する THEN テスト移行システム SHALL 同一ディレクトリ構造でPestテストを作成する
3. WHEN `tests/Architecture/`ディレクトリに3個のアーキテクチャテストが存在する THEN テスト移行システム SHALL 既存のPestアーキテクチャテスト（LayerTest.php, NamingTest.php, QualityTest.php）をそのまま維持する
4. WHEN `tests/Feature/Api/`サブディレクトリにAPIテストが存在する THEN テスト移行システム SHALL 既存のPest形式APIテスト（AuthenticationTest.php, CorsTest.php, JsonApiTest.php）をそのまま維持する
5. WHEN 移行完了後 THEN テスト移行システム SHALL Pest.php設定により`Feature`, `Unit`, `Architecture`ディレクトリすべてに対してTestCaseクラスを自動適用する

### 要件4: 移行ツールとドキュメントの整備
**目的:** 移行担当者として、段階的な移行をサポートするツールとドキュメントを整備し、安全で効率的な移行プロセスを実現したい。これにより、移行中のリスクを最小化し、他のプロジェクトへの移行ノウハウを蓄積する。

#### 受入基準
1. WHEN 移行担当者が変換スクリプト`scripts/convert-phpunit-to-pest.sh`を実行する THEN スクリプト SHALL 基本的な構文変換（namespace削除、クラス定義削除、test_メソッド変換）を自動実行する
2. WHEN 変換スクリプトが完了する THEN スクリプト SHALL バックアップファイル（`.bak`拡張子）を自動生成し、元ファイルの復元を可能にする
3. WHEN 移行ドキュメント`docs/PEST_MIGRATION_CHECKLIST.md`が参照される THEN ドキュメント SHALL 移行手順、注意点、変換パターンを明記する
4. WHEN 移行後のコーディング規約が必要となる THEN ドキュメント`docs/PEST_CODING_STANDARDS.md` SHALL Pest専用のベストプラクティスと命名規則を提供する
5. WHEN トラブルシューティングが必要となる THEN ドキュメント`docs/PEST_TROUBLESHOOTING.md` SHALL 一般的な移行エラーと解決策を提供する

### 要件5: Composer スクリプトとCI/CD設定の統合
**目的:** 開発者として、移行後も既存のテスト実行コマンドを維持し、CI/CDパイプラインがPestテストを自動実行できるようにしたい。これにより、開発フローの中断を防止し、継続的な品質保証を維持する。

#### 受入基準
1. WHEN 開発者が`composer test`コマンドを実行する THEN テスト実行システム SHALL Pestテストスイート全体を実行する（`./vendor/bin/pest`）
2. WHEN 移行期間中にPHPUnitテストとPestテストが混在する THEN Composer SHALL `test-phpunit`および`test-pest`コマンドを個別に提供する
3. WHEN 移行完了後 THEN Composer SHALL `test-all`コマンドによりすべてのPestテストを実行する（PHPUnitコマンド削除）
4. WHEN CI/CDパイプラインが実行される THEN GitHub Actions SHALL `.github/workflows/test.yml`によりPestテストを自動実行する
5. WHEN カバレッジレポートが必要となる THEN Composer `test-coverage`コマンド SHALL Xdebugを使用してPestカバレッジレポートを生成する

### 要件6: 移行後の検証と品質保証
**目的:** QA担当者として、移行後のテストスイートが同等以上の品質を維持し、すべてのテストが正常に動作することを検証したい。これにより、移行完了後の信頼性を確保する。

#### 受入基準
1. WHEN 移行後に`composer test`が実行される THEN テスト実行システム SHALL すべてのテストケースが成功する（0 failures）
2. WHEN Larastan静的解析が実行される THEN 静的解析システム SHALL Pestテストファイルに対してもLevel 8品質基準を満たす
3. WHEN Laravel Pintコードフォーマットが実行される THEN フォーマットシステム SHALL Pestテストファイルに対してもLaravel規約を適用する
4. WHEN 並列テスト実行（`composer test-parallel`）が実行される THEN テスト実行システム SHALL Pestの並列実行機能により高速テストを実現する
5. WHEN テストシャーディング（`composer test-shard`）が実行される THEN テスト実行システム SHALL Pestのシャーディング機能によりCI/CD最適化を実現する

### 要件7: PHPUnitファイルとレガシーコードの削除
**目的:** プロジェクトメンテナーとして、移行完了後に不要なPHPUnitファイルと設定を削除し、コードベースをクリーンに保ちたい。これにより、混乱を防止し、長期的な保守性を向上させる。

#### 受入基準
1. WHEN 21個のPHPUnitテストファイルすべてがPest形式に変換され検証完了する THEN 移行システム SHALL 元のPHPUnitファイルを削除する（バックアップ除く）
2. WHEN PHPUnit設定ファイル`phpunit.xml`が不要となる THEN 移行システム SHALL `phpunit.xml`を削除し、Pest設定のみを維持する（`pest.xml`またはPest.php設定）
3. WHEN Composer.jsonの`test-phpunit`コマンドが不要となる THEN 移行システム SHALL PHPUnit専用コマンドを削除し、Pestコマンドのみを残す
4. WHEN `tests/TestCase.php`がPHPUnit専用の実装を含む THEN 移行システム SHALL TestCaseをPest互換形式に更新する（必要に応じて）
5. WHEN 移行完了後のドキュメント更新が必要となる THEN 移行システム SHALL `README.md`および関連ドキュメントのテスト実行コマンドをPest形式に更新する
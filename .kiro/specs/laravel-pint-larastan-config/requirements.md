# Requirements Document

## イントロダクション

Laravel 12.0 + Sanctumプロジェクトにおいて、PHPコードの品質を自動的にチェックし、バグを早期に発見するためのコード品質管理システムを構築します。Laravel Pint v1.24（コードフォーマッター）とLarastan v3.7（静的解析ツール）を最適化設定し、コード品質の統一とチーム開発効率を向上させます。

このシステムは、開発者の手動作業を最小限に抑えながら、コミット前・プッシュ前・CI/CDパイプラインでの自動品質チェックを実現し、高品質なコードベースの維持を目指します。

### ビジネス価値
- **コード品質の統一**: チーム全体で一貫したコーディングスタイルと品質基準を維持
- **バグの早期発見**: 静的解析により、実行前に潜在的な問題を検出
- **開発効率の向上**: 自動化により、レビュー時間を削減し、開発に集中
- **技術的負債の削減**: 継続的な品質チェックにより、長期的な保守性を確保

## Requirements

### Requirement 1: Laravel Pint コードフォーマッター設定

**Objective:** 開発者として、統一されたコーディングスタイルを自動適用することで、コードレビューの時間を削減し、コードの可読性を向上させたい

#### Acceptance Criteria

1. WHEN `backend/laravel-api/pint.json`設定ファイルが作成される THEN Laravel Pint SHALL Laravel公式プリセットを基本設定として使用する
2. WHEN Pint設定ファイルが作成される THEN Laravel Pint SHALL 除外パス（vendor, storage, bootstrap/cache）を設定に含める
3. WHEN Pint設定ファイルが作成される THEN Laravel Pint SHALL 自動import整理とsingle quote統一のカスタムルールを適用する
4. WHEN `vendor/bin/pint`コマンドが実行される THEN Laravel Pint SHALL 全PHPファイルをフォーマットし、変更内容を出力する
5. WHEN `vendor/bin/pint --test`コマンドが実行される THEN Laravel Pint SHALL フォーマットが必要なファイルを検出し、変更せずに終了コード1を返す
6. WHEN `vendor/bin/pint --dirty`コマンドが実行される THEN Laravel Pint SHALL Git差分のあるファイルのみをフォーマットする

### Requirement 2: Larastan 静的解析設定

**Objective:** 開発者として、厳格な静的解析により潜在的なバグを早期に発見し、型安全性を確保したい

#### Acceptance Criteria

1. WHEN `backend/laravel-api/phpstan.neon.dist`設定ファイルが作成される THEN Larastan SHALL レベル8の厳格な型チェックを有効にする
2. WHEN PHPStan設定ファイルが作成される THEN Larastan SHALL 並列処理設定（jobSize: 20, maximumNumberOfProcesses: 32）を含める
3. WHEN PHPStan設定ファイルが作成される THEN Larastan SHALL キャッシュディレクトリを`storage/framework/cache/phpstan`に設定する
4. WHEN PHPStan設定ファイルが作成される THEN Larastan SHALL 除外パス（vendor, storage, bootstrap, database/migrations）を設定に含める
5. WHEN `vendor/bin/phpstan analyse`コマンドが実行される THEN Larastan SHALL 全PHPファイルを解析し、型エラーを検出して報告する
6. WHEN PHPStan解析がメモリ不足になる THEN Larastan SHALL `--memory-limit=2G`オプションで実行可能である
7. WHEN 既存コードで大量のエラーが検出される THEN Larastan SHALL ベースライン生成機能により段階的にエラーを解決できる

### Requirement 3: Composer Scripts 統合

**Objective:** 開発者として、統一されたコマンドで品質チェックを実行することで、操作を簡素化し、チーム全体で一貫した運用を実現したい

#### Acceptance Criteria

1. WHEN `composer.json`の`scripts`セクションが更新される THEN Composer SHALL `composer quality`コマンドで品質チェック（Pint test + PHPStan）を実行する
2. WHEN `composer quality`コマンドが実行される THEN Composer SHALL PintのテストモードとPHPStan解析を順次実行し、どちらかが失敗した場合は終了コード1を返す
3. WHEN `composer.json`の`scripts`セクションが更新される THEN Composer SHALL `composer quality:fix`コマンドで自動修正（Pint）と解析（PHPStan）を実行する
4. WHEN `composer.json`の`scripts`セクションが更新される THEN Composer SHALL `composer pint`、`composer pint:test`、`composer pint:dirty`コマンドを提供する
5. WHEN `composer.json`の`scripts`セクションが更新される THEN Composer SHALL `composer stan`、`composer stan:baseline`コマンドを提供する
6. WHEN PHPStan関連コマンドが実行される THEN Composer SHALL `--memory-limit=2G`オプションを自動適用する

### Requirement 4: Git Hooks 強化

**Objective:** 開発者として、コミット前・プッシュ前に自動的に品質チェックを実行することで、品質の低いコードがリポジトリに入ることを防ぎたい

#### Acceptance Criteria

1. WHEN 開発者がgitコミットを実行する THEN Git Hooks SHALL pre-commitフックで変更されたPHPファイルのみをPintでチェックする
2. WHEN pre-commitフックが実行される AND Pintがフォーマットエラーを検出する THEN Git Hooks SHALL コミットを中断し、エラー内容を表示する
3. WHEN 開発者がgit pushを実行する THEN Git Hooks SHALL pre-pushフックで全体品質チェック（`composer quality`）を実行する
4. WHEN pre-pushフックが実行される AND 品質チェックが失敗する THEN Git Hooks SHALL プッシュを中断し、エラー内容を表示する
5. WHEN Git Hooksが実行される THEN Git Hooks SHALL 変更ファイル限定実行により、チェック時間を最小化する

### Requirement 5: CI/CD 統合

**Objective:** チームとして、CI/CDパイプラインで自動的に品質チェックを実行することで、品質ゲートを確立し、マージ前にコード品質を保証したい

#### Acceptance Criteria

1. WHEN GitHub Actionsワークフローファイル（`.github/workflows/ci.yml`）が更新される THEN CI/CD Pipeline SHALL PHP品質チェックステップを含める
2. WHEN CI/CD品質チェックステップが実行される THEN CI/CD Pipeline SHALL `composer quality`コマンドを実行する
3. WHEN CI/CD品質チェックステップが実行される THEN CI/CD Pipeline SHALL PHPStanキャッシュを有効にして実行時間を短縮する
4. WHEN CI/CD品質チェックステップが実行される THEN CI/CD Pipeline SHALL `composer validate --strict`を実行してcomposer.jsonの整合性を検証する
5. WHEN 品質チェックが失敗する THEN CI/CD Pipeline SHALL ワークフローを失敗させ、プルリクエストのマージをブロックする
6. WHERE キャッシュキーが適切に設定される THE CI/CD Pipeline SHALL Composerおよびnode_modules依存関係をキャッシュする

### Requirement 6: IDE 統合

**Objective:** 開発者として、IDE上でリアルタイムにコード品質フィードバックを受け取ることで、コミット前にエラーを修正し、開発効率を向上させたい

#### Acceptance Criteria

1. WHEN VSCode設定ファイル（`.vscode/settings.json`）が作成される THEN IDE Integration SHALL Laravel Pint設定を認識し、保存時に自動フォーマットを実行する
2. WHEN VSCode設定ファイルが作成される THEN IDE Integration SHALL PHPStan設定を認識し、リアルタイムで型エラーを表示する
3. WHEN PhpStorm設定が構成される THEN IDE Integration SHALL Code Styleに`pint.json`の設定を反映する
4. WHEN PhpStorm設定が構成される THEN IDE Integration SHALL InspectionsにLarastan（PHPStan）の設定を反映する
5. WHEN IDEでPHPファイルが保存される THEN IDE Integration SHALL Format on Save機能により自動的にPintルールを適用する

### Requirement 7: パフォーマンス最適化

**Objective:** 開発者として、品質チェックツールの実行時間を最小化することで、開発フローを妨げずに高品質なコードを維持したい

#### Acceptance Criteria

1. WHEN PHPStanが実行される THEN Performance Optimization SHALL 並列処理により解析時間を短縮する
2. WHEN PHPStanが実行される THEN Performance Optimization SHALL キャッシュを活用して2回目以降の実行を高速化する
3. WHEN Pintが実行される THEN Performance Optimization SHALL `--dirty`オプションにより変更ファイルのみを処理する
4. WHEN Git Hooksが実行される THEN Performance Optimization SHALL 変更ファイル限定実行によりコミット時間を最小化する
5. WHEN CI/CDパイプラインが実行される THEN Performance Optimization SHALL Composer依存関係とPHPStanキャッシュを活用して実行時間を短縮する

### Requirement 8: 段階的ロールアウト

**Objective:** チームとして、既存コードへの影響を最小限に抑えながら、段階的に品質管理システムを導入し、チーム全体の受け入れを促進したい

#### Acceptance Criteria

1. WHEN Larastanが既存コードで大量のエラーを検出する THEN Rollout Strategy SHALL ベースライン機能により既存エラーを記録し、新規エラーのみを検出する
2. WHEN ベースラインが生成される THEN Rollout Strategy SHALL `phpstan-baseline.neon`ファイルに既存エラーを記録する
3. WHEN チーム運用ドキュメントが作成される THEN Rollout Strategy SHALL ツールの使用方法、トラブルシューティング、ベストプラクティスを含める
4. WHEN 段階的ロールアウトが完了する THEN Rollout Strategy SHALL 全チームメンバーがツールを利用可能であり、品質チェックが自動化されている

## テスト戦略

### 単体テスト
- **Pint**: 個別PHPファイルのフォーマットが正しく適用されることを確認
- **PHPStan**: 型エラー検出が期待通りに機能することを確認

### 統合テスト
- **Git Hooks**: コミット・プッシュ時に品質チェックが正しく動作することを確認
- **CI/CDパイプライン**: GitHub Actionsワークフローが期待通りに品質チェックを実行することを確認

### パフォーマンステスト
- **大規模ファイル**: 大きなPHPファイルやプロジェクト全体での実行時間を測定
- **キャッシュ効果**: キャッシュ有効時の実行時間短縮を測定

### チームテスト
- **複数開発者環境**: 異なる開発環境（macOS、Linux、Windows）での動作を確認
- **IDE統合**: VSCodeとPhpStormでの統合動作を確認

## パフォーマンス最適化目標

- **PHPStan**: 並列処理、キャッシュ、メモリ制限設定により実行時間を最小化
- **Pint**: `--dirty`オプションで変更ファイルのみ処理し、コミット時間を短縮
- **Git Hooks**: 変更ファイル限定実行でコミット時間を最小化（目標: 5秒以内）
- **CI/CD**: Composerおよびnode_modulesキャッシュ活用により、パイプライン実行時間を短縮

## リスクと緩和策

### 初期設定時間
**リスク**: 設定ファイル作成とツール設定に時間がかかる
**緩和策**: テンプレート設定ファイルを提供し、段階的に導入

### 既存コードの大量警告
**リスク**: 既存コードでLarastanが大量のエラーを検出する
**緩和策**: ベースライン機能を活用し、既存エラーを記録して段階的に解決

### チーム受け入れ
**リスク**: 新しいツールやワークフローへの抵抗
**緩和策**: 詳細なドキュメント、トレーニング、段階的ロールアウトによる受け入れ促進

## Definition of Done

- [ ] `backend/laravel-api/pint.json`が作成され、Laravel Pintが正しく動作する
- [ ] `backend/laravel-api/phpstan.neon.dist`が作成され、Larastanがレベル8で正しく動作する
- [ ] `composer.json`に品質チェックコマンドが統合され、`composer quality`が動作する
- [ ] Git Hooksが設定され、コミット前・プッシュ前に自動品質チェックが実行される
- [ ] GitHub Actionsワークフローに品質チェックステップが追加され、CI/CDで自動実行される
- [ ] VSCodeおよびPhpStorm設定が完了し、IDE上でリアルタイムフィードバックが機能する
- [ ] チーム運用ドキュメントが作成され、全チームメンバーが利用可能である
- [ ] 段階的ロールアウトが完了し、全チームメンバーがツールを使用している

## 参考文献

- [Laravel Pint Documentation](https://laravel.com/docs/pint)
- [Larastan Documentation](https://github.com/larastan/larastan)
- [PHPStan Documentation](https://phpstan.org/user-guide/getting-started)
- [Laravel 12.0 Documentation](https://laravel.com/docs)
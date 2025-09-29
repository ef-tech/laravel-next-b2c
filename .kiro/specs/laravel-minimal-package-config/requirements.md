# Requirements Document

## GitHub Issue Information

**Issue**: [#35](https://github.com/ef-tech/laravel-next-b2c/issues/35) - Laravel 必要最小限パッケージ構成
**Labels**: なし
**Milestone**: なし
**Assignees**: なし

### Original Issue Description
現在のLaravel 12.0標準スケルトン構成をAPI開発に特化した軽量・効率的なパッケージ構成に最適化する。不要なビュー・セッション機能を削除し、パフォーマンス・セキュリティ・保守性を向上させる。

**Code/Architecture** - Laravel フレームワーク最適化
- コアパッケージ依存関係の最適化
- アーキテクチャパターンの簡素化
- ミドルウェア・サービスプロバイダ構成変更
- 設定ファイル最適化

## Extracted Information

### Technology Stack
**Backend**: PHP 8.4, Laravel 12.0, Laravel Sanctum 4.0, Laravel Tinker
**Frontend**: なし（API専用）
**Infrastructure**: Composer, OPcache
**Tools**: PHPUnit, Pest, PHPStan, Larastan, PHP CS Fixer, Pint, wrk, Apache Bench

### Project Structure
```
composer.json
bootstrap/app.php
routes/web.php → 削除予定
routes/api.php
routes/console.php
config/*
.env
resources/views → 削除予定
```

### Development Services Configuration
- Laravel: API Port 13000, Health Endpoint /up

### Requirements Hints
Issue 解析から抽出された要件：
- API開発に特化した軽量・効率的なパッケージ構成への最適化
- 不要なビュー・セッション機能の削除
- パフォーマンス・セキュリティ・保守性の向上
- 起動速度20-30%向上、メモリ使用量15-25%削減
- 依存関係30%以上削減

### TODO Items from Issue
Issue から抽出されたタスクリスト：

#### Phase 1: 準備・分析
- [ ] 現在の依存関係調査・文書化
- [ ] 削除対象機能の参照箇所特定
- [ ] バックアップブランチ作成

#### Phase 2: 依存関係最適化
- [ ] composer.json 不要パッケージ削除
- [ ] composer dump-autoload 実行・確認
- [ ] 基本ヘルスチェック (php artisan about)

#### Phase 3: アーキテクチャ変更
- [ ] .env セッション設定変更
- [ ] ミドルウェア構成最適化
- [ ] bootstrap/app.php API専用構成変更
- [ ] routes/web.php 削除
- [ ] resources/views ディレクトリ削除

#### Phase 4: 設定最適化
- [ ] config/session.php 最適化
- [ ] config/cors.php API専用設定
- [ ] config/auth.php トークン認証に集約
- [ ] 不要設定ファイル削除

#### Phase 5: テスト・検証
- [ ] PHPUnit/Pest テスト実行
- [ ] 静的解析実行 (phpstan/larastan)
- [ ] コード整形 (php artisan pint)
- [ ] ベンチマークテスト実装・実行

#### Phase 6: 本番最適化
- [ ] php artisan optimize 実行
- [ ] OPcache 設定最適化
- [ ] パフォーマンス計測・結果文書化

## Requirements

### Requirement 1: 依存関係の最適化
**Objective:** API開発者として、Laravel アプリケーションの依存関係を最小限に抑えることで、軽量で高速なAPIサーバーを構築したい。

#### Acceptance Criteria
1. WHEN composer.json を最適化するとき THEN Laravel システム SHALL PHP 8.4、Laravel 12.0、Laravel Sanctum 4.0、Laravel Tinker のみを必須依存関係として保持する
2. WHEN 不要なパッケージを削除するとき THEN Laravel システム SHALL ビュー・セッション関連の依存関係を完全に除外する
3. WHEN `composer dump-autoload` を実行するとき THEN Laravel システム SHALL オートローダーを最適化して起動時間を短縮する
4. WHEN 依存関係の最適化が完了したとき THEN Laravel システム SHALL 元の依存関係から30%以上の削減を達成する

### Requirement 2: アーキテクチャの簡素化
**Objective:** API開発者として、API専用の軽量なアーキテクチャに変更することで、不要な機能を排除し、セキュリティと保守性を向上させたい。

#### Acceptance Criteria
1. WHEN セッション設定を変更するとき THEN Laravel システム SHALL .env ファイルで SESSION_DRIVER=array を設定する
2. WHEN ミドルウェア構成を最適化するとき THEN Laravel システム SHALL EncryptCookies、StartSession、VerifyCsrfToken ミドルウェアを削除する
3. WHEN bootstrap/app.php を API専用に変更するとき THEN Laravel システム SHALL API ルートのみをロードし、Web ルートを除外する
4. WHEN routes/web.php を削除するとき THEN Laravel システム SHALL Web ルートファイルを完全に除去する
5. WHEN resources/views ディレクトリを削除するとき THEN Laravel システム SHALL ビューテンプレート関連ファイルを完全に除去する

### Requirement 3: 設定ファイルの最適化
**Objective:** システム管理者として、API専用の設定に最適化することで、不要な設定を削除し、明確で保守しやすい構成を実現したい。

#### Acceptance Criteria
1. WHEN config/session.php を最適化するとき THEN Laravel システム SHALL API専用のセッション設定に変更する
2. WHEN config/cors.php を設定するとき THEN Laravel システム SHALL API アクセス専用の CORS 設定を適用する
3. WHEN config/auth.php を変更するとき THEN Laravel システム SHALL トークン認証（Sanctum）に集約した認証設定を適用する
4. WHEN 不要な設定ファイルを削除するとき THEN Laravel システム SHALL ビュー・セッション関連の設定ファイルを除去する

### Requirement 4: パフォーマンスの向上
**Objective:** 運用チームとして、アプリケーションのパフォーマンスを大幅に改善することで、より効率的なリソース使用と高速な応答を実現したい。

#### Acceptance Criteria
1. WHEN アプリケーションの起動時間を測定するとき THEN Laravel システム SHALL 最適化前と比較して20-30%の起動速度向上を達成する
2. WHEN メモリ使用量を測定するとき THEN Laravel システム SHALL 最適化前と比較して15-25%のメモリ使用量削減を達成する
3. WHEN `php artisan optimize` を実行するとき THEN Laravel システム SHALL 本番環境用の最適化を適用する
4. WHEN OPcache 設定を最適化するとき THEN Laravel システム SHALL PHP オプコードキャッシュの効率を最大化する

### Requirement 5: セキュリティの強化
**Objective:** セキュリティチームとして、攻撃対象面積を削減することで、より安全なAPIアプリケーションを構築したい。

#### Acceptance Criteria
1. WHEN 不要な機能を削除するとき THEN Laravel システム SHALL CSRF攻撃の対象となるWeb機能を完全に除去する
2. WHEN セッション機能を無効化するとき THEN Laravel システム SHALL セッション関連の脆弱性リスクを排除する
3. WHEN API専用構成を適用するとき THEN Laravel システム SHALL 認証にトークンベース認証のみを使用する
4. WHEN ヘルスチェックエンドポイントを設定するとき THEN Laravel システム SHALL /up エンドポイントで基本的な動作確認を提供する

### Requirement 6: テスト・検証・品質保証
**Objective:** 開発チームとして、最適化後の動作を包括的に検証することで、機能の完全性と品質を保証したい。

#### Acceptance Criteria
1. WHEN PHPUnit/Pest テストを実行するとき THEN Laravel システム SHALL 全ての既存テストが正常に通過する
2. WHEN 静的解析を実行するとき THEN Laravel システム SHALL PHPStan/Larastan Level 8 で0件のエラーを達成する
3. WHEN コード整形を実行するとき THEN Laravel システム SHALL PHP CS Fixer/Pint の品質基準に準拠する
4. WHEN ベンチマークテストを実行するとき THEN Laravel システム SHALL 定量的なパフォーマンス改善結果を提供する
5. WHEN パフォーマンス計測を実行するとき THEN Laravel システム SHALL 起動速度、メモリ使用量、依存関係数の改善データを記録する

### Requirement 7: 文書化・マイグレーション支援
**Objective:** チーム全体として、変更内容を適切に文書化し、他のプロジェクトへの適用を可能にしたい。

#### Acceptance Criteria
1. WHEN 最適化プロセスを完了するとき THEN Laravel システム SHALL 変更内容の詳細文書を提供する
2. WHEN セットアップ手順を更新するとき THEN Laravel システム SHALL 新しい構成での開発環境構築手順を提供する
3. WHEN マイグレーションガイドを作成するとき THEN Laravel システム SHALL 既存プロジェクトの移行手順を提供する
4. WHEN パフォーマンステスト結果を記録するとき THEN Laravel システム SHALL 最適化の効果を定量的に示すレポートを提供する

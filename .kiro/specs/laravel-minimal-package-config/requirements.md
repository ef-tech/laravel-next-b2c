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
- Laravel: API Port 8000, Health Endpoint /up

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
<!-- Will be generated in /kiro:spec-requirements phase -->
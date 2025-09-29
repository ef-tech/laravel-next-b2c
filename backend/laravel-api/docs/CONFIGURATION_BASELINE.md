# Laravel 最小限パッケージ構成 - ベースライン記録

## 記録日時
2025-09-29 17:30:00 JST

## 現在の依存関係分析

### 本番依存関係 (require)
```json
{
    "php": "^8.4",
    "laravel/framework": "^12.0",
    "laravel/tinker": "^2.10.1"
}
```
**本番パッケージ数**: 3個

### 開発依存関係 (require-dev)
```json
{
    "fakerphp/faker": "^1.23",
    "laravel/pail": "^1.2.2",
    "laravel/pint": "^1.24",
    "laravel/sail": "^1.41",
    "mockery/mockery": "^1.6",
    "nunomaduro/collision": "^8.6",
    "phpunit/phpunit": "^11.5.3"
}
```
**開発パッケージ数**: 7個

**総依存関係数**: 10個

## Web機能利用状況

### ファイル存在確認
- ✅ `routes/web.php` - 存在（7行、ビューレンダリング使用）
- ✅ `resources/views/` - ディレクトリ存在（Bladeテンプレート対応）
- ✅ `config/session.php` - セッション設定ファイル存在
- ✅ `bootstrap/app.php` - Webルート読み込み設定あり

### Web機能詳細
```php
// routes/web.php の内容
Route::get('/', function () {
    return view('welcome');
});
```

```php
// bootstrap/app.php の関連箇所
->withRouting(
    web: __DIR__.'/../routes/web.php',
    commands: __DIR__.'/../routes/console.php',
    health: '/up',
)
```

## 現在のアーキテクチャ特徴

### セッション設定
- デフォルトドライバー: `database` (推定)
- セッション関連ミドルウェア: 有効
- Cookie暗号化: 有効
- CSRF保護: 有効

### 認証設定
- デフォルト認証: セッションベース
- API認証: 未設定（Sanctumなし）
- パスワードリセット: 標準設定

### ルーティング
- Web + API両対応構成
- ヘルスチェック: `/up` エンドポイント有効

## セキュリティ攻撃面

### 現在の攻撃対象
- セッションハイジャック可能性
- CSRF攻撃対象の存在
- Cookie関連脆弱性の可能性
- ビューテンプレート経由のXSS可能性

## 最適化前のパフォーマンス指標

### 起動時間
- 測定方法: microtime(true) によるブートタイム測定
- 基準: config読み込み + router初期化

### メモリ使用量
- 測定方法: memory_get_usage(true)
- 計測タイミング: アプリケーション初期化後

### 依存関係
- **削減目標**: 現在の10個から30%以上削減 → 7個以下
- **追加予定**: Laravel Sanctum (認証用)

## 最適化対象の特定

### 削除予定機能
1. **Web ルート機能**
   - `routes/web.php` ファイル
   - Web用ミドルウェアスタック
   - セッション機能全般

2. **ビュー機能**
   - `resources/views/` ディレクトリ
   - Bladeテンプレートエンジン関連

3. **セッション・Cookie機能**
   - セッションストレージ
   - Cookie暗号化ミドルウェア
   - CSRF保護ミドルウェア

### 追加予定機能
1. **Laravel Sanctum**
   - API トークン認証
   - ステートレス認証システム

### 設定変更予定箇所
- `bootstrap/app.php` → API専用ルーティング
- `config/auth.php` → Sanctum認証設定
- `config/session.php` → array driver設定
- 新規: `config/cors.php` → API用CORS設定

## バックアップ戦略

### Git管理状況
- ✅ Gitリポジトリ: 存在（プロジェクトルート）
- ✅ バックアップ対象ファイル: 全て追跡済み

### 重要ファイル一覧
- `composer.json` - 依存関係定義
- `bootstrap/app.php` - アプリケーション設定
- `routes/web.php` - 削除予定Webルート
- `config/auth.php` - 認証設定
- `config/session.php` - セッション設定

### 復旧手順
1. 現在のブランチでバックアップコミット作成
2. 各段階でコミット実行
3. 問題発生時の即座ロールバック対応

## 最適化計画概要

1. **Phase 1**: Laravel Sanctum追加
2. **Phase 2**: Web機能完全削除
3. **Phase 3**: API専用設定適用
4. **Phase 4**: パフォーマンス測定・検証

---
*ベースライン記録完了: 2025-09-29 17:30:00 JST*
# Laravel 最小限パッケージ構成 - 最適化対象詳細分析

## 分析日時
2025-09-29 17:50:00 JST

## 削除可能パッケージとリスク評価

### 現在の依存関係状況
- **本番依存関係**: 3個（php, laravel/framework, laravel/tinker）
- **開発依存関係**: 7個（faker, pail, pint, sail, mockery, collision, phpunit）

### 削除対象パッケージ
**現在の状況**: 既に最小限の構成のため、本番依存関係から削除すべきパッケージなし

### 追加予定パッケージ
1. **laravel/sanctum** - API トークン認証用
   - **追加理由**: API専用アーキテクチャでステートレス認証が必要
   - **リスク**: なし（新規追加のため）
   - **影響**: セッションベース認証からの移行が必要

## Web機能削除による影響範囲

### 削除対象ファイル・ディレクトリ

#### 1. routes/web.php
```php
// 現在の内容
Route::get('/', function () {
    return view('welcome');
});
```
- **削除理由**: Web ルートはAPI専用化で不要
- **影響**: welcome ページへのアクセス不可
- **リスク**: 低（フロントエンドが別途存在するため）

#### 2. resources/views/ ディレクトリ
- **削除対象**: 全Bladeテンプレートファイル
- **現在の状況**: welcome.blade.php等が存在
- **影響**: ビューレンダリング機能完全除去
- **リスク**: 低（API専用のため）

#### 3. セッション・Cookie関連機能
- **セッションストレージ**: 完全無効化
- **Cookie暗号化**: ミドルウェア削除
- **CSRF保護**: ミドルウェア削除
- **影響**: ステートレス化による性能向上
- **リスク**: 中（既存セッション依存機能があれば影響）

## 設定ファイル変更箇所の洗い出し

### 1. bootstrap/app.php
**現在の設定**:
```php
->withRouting(
    web: __DIR__.'/../routes/web.php',
    commands: __DIR__.'/../routes/console.php',
    health: '/up',
)
```

**変更予定**:
```php
->withRouting(
    api: __DIR__.'/../routes/api.php',
    commands: __DIR__.'/../routes/console.php',
    health: '/up',
)
```

**影響**: Web ルート無効化、API ルート専用化

### 2. config/auth.php
**現在の設定**:
- デフォルトガード: 'web' (セッション認証)
- パスワードリセット: 標準設定

**変更予定**:
- デフォルトガード: 'sanctum' (API トークン認証)
- API guards追加
- Web guards削除

### 3. config/session.php
**変更予定**:
- SESSION_DRIVER: 'array' （セッション無効化）

### 4. 新規作成: config/cors.php
**必要な理由**: Next.jsフロントエンド（ポート3000, 3001）からのAPI アクセス
**設定内容**:
- 許可オリジン: localhost:3000, localhost:3001
- 許可メソッド: GET, POST, PUT, DELETE
- 認証情報: 必要に応じて許可

## ミドルウェア削除・追加計画

### 削除対象ミドルウェア
1. **\Illuminate\Cookie\Middleware\EncryptCookies**
   - **理由**: Cookie使用しないため不要
   - **リスク**: 低

2. **\Illuminate\Session\Middleware\StartSession**
   - **理由**: セッション無効化のため不要
   - **リスク**: 低

3. **\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken**
   - **理由**: APIはCSRF不要（トークン認証使用）
   - **リスク**: 低

### 保持するミドルウェア
1. **\Illuminate\Routing\Middleware\ThrottleRequests**
   - **理由**: API レート制限は必要

2. **\Illuminate\Routing\Middleware\SubstituteBindings**
   - **理由**: ルートモデルバインディングは有用

## テストケース実行可能性の確認

### 現在のテスト環境
- ✅ phpunit.xml存在
- ✅ tests/ディレクトリ構造正常
- ✅ SQLiteテストDB設定済み
- ✅ ヘルスチェックエンドポイント動作確認済み

### 最適化後のテスト影響
1. **Webルートテスト**: 削除または修正必要
2. **セッションテスト**: API認証テストに変更
3. **機能テスト**: API エンドポイント中心に再構成

## セキュリティ攻撃面の変化

### 削除される攻撃面
- ✅ セッションハイジャック: セッション無効化で排除
- ✅ CSRF攻撃: Web機能削除で排除
- ✅ Cookie関連脆弱性: Cookie暗号化無効化で排除

### 新たなセキュリティ考慮事項
- 🔒 API トークン管理: 適切な有効期限設定
- 🔒 CORS設定: 必要最小限の許可オリジン
- 🔒 レート制限: API 悪用防止

## 最適化の優先順位

### Phase 1: 高優先度（即座実行可能）
1. Laravel Sanctum 追加
2. Web ルート削除
3. セッション無効化

### Phase 2: 中優先度（注意深く実行）
1. ミドルウェア最適化
2. 認証設定変更
3. CORS設定追加

### Phase 3: 低優先度（最終調整）
1. ビューディレクトリ削除
2. 設定ファイル不要項目削除
3. パフォーマンス最適化

## リスク軽減策

### バックアップ戦略
- ✅ backup/before-optimization ブランチ作成済み
- 各フェーズでコミット作成
- 即座復旧可能な手順確立

### 段階的実装
1. 機能追加 → テスト → 確認
2. 機能削除 → テスト → 確認
3. 設定変更 → テスト → 確認

### 検証項目
- 全テストパス確認
- ヘルスチェック動作確認
- パフォーマンス改善確認

---
*最適化対象分析完了: 2025-09-29 17:50:00 JST*
# トラブルシューティングガイド

Laravel最小限パッケージ構成最適化後の一般的な問題と解決方法をまとめました。

## 目次
- [認証エラー](#認証エラー)
- [テスト失敗](#テスト失敗)
- [パフォーマンス問題](#パフォーマンス問題)
- [CORS関連エラー](#cors関連エラー)
- [依存関係問題](#依存関係問題)
- [環境設定エラー](#環境設定エラー)

## 認証エラー

### エラー: "Unauthenticated" (401)

**症状**: APIリクエストで401 Unauthenticatedエラーが発生

**考えられる原因**:
1. トークンが正しく設定されていない
2. トークンの有効期限切れ
3. Sanctum設定の問題

**解決方法**:

```bash
# 1. Sanctum設定の確認
php artisan config:clear
php artisan config:cache

# 2. personal_access_tokensテーブルの存在確認
php artisan migrate:status

# 3. トークン生成テスト
php artisan tinker
>>> $user = User::first();
>>> $token = $user->createToken('test-token');
>>> echo $token->plainTextToken;
```

**フロントエンド側の確認**:
```javascript
// Authorization headerが正しく設定されているか確認
headers: {
    'Authorization': `Bearer ${token}`,
    'Accept': 'application/json',
    'Content-Type': 'application/json',
}
```

### エラー: CSRF Token Mismatch

**症状**: CSRF token mismatchエラーが表示される

**原因**: Web機能が完全に削除されているため、CSRFトークンは不要

**解決方法**:
```javascript
// CSRFトークンは使用せず、Bearerトークンのみ使用
// X-CSRF-TOKENヘッダーを削除し、Authorizationヘッダーのみ設定
headers: {
    'Authorization': `Bearer ${token}`,
    // 'X-CSRF-TOKEN': token, // これは削除
}
```

## テスト失敗

### エラー: "Session store not set on request"

**症状**: テスト実行時にセッション関連エラーが発生

**原因**: SESSION_DRIVER=arrayによりセッション機能が無効化されている

**解決方法**:
```php
// テスト用の.env.testingファイルで設定
// tests/TestCase.php で適切な認証設定を使用

public function setUp(): void
{
    parent::setUp();

    // テスト用のSanctum認証
    $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
}

// 認証が必要なテストの場合
protected function authenticatedUser()
{
    $user = User::factory()->create();
    $token = $user->createToken('test-token');

    return $this->withHeaders([
        'Authorization' => 'Bearer ' . $token->plainTextToken,
    ]);
}
```

### エラー: ビュー関連テスト失敗

**症状**: ビューやBladeテンプレートに関するテストが失敗

**原因**: resources/viewsディレクトリが削除されている

**解決方法**:
```php
// ビュー関連テストを削除または修正
// API専用のレスポンステストに変更

// 変更前
$response = $this->get('/home');
$response->assertViewIs('home');

// 変更後
$response = $this->getJson('/api/dashboard');
$response->assertJson(['status' => 'success']);
```

## パフォーマンス問題

### 問題: 期待したパフォーマンス改善が見られない

**確認事項**:

1. **OPcache設定の確認**:
```bash
php -i | grep opcache
# opcache.enable=1 が設定されているか確認
```

2. **Laravel最適化の実行**:
```bash
php artisan optimize
php artisan config:cache
php artisan route:cache
php artisan view:clear  # ビューキャッシュをクリア
```

3. **依存関係の最適化**:
```bash
composer dump-autoload --optimize --no-dev
```

4. **パフォーマンス測定**:
```bash
# テスト実行
php artisan test --filter=PerformanceBenchmarkTest
```

### 問題: メモリ使用量が期待より高い

**デバッグ方法**:
```php
// アプリケーション内でメモリ使用量を測定
echo "Memory: " . memory_get_usage(true) / 1024 / 1024 . " MB\n";
echo "Peak: " . memory_get_peak_usage(true) / 1024 / 1024 . " MB\n";

// 大きなオブジェクトがないか確認
$objects = get_declared_classes();
foreach ($objects as $class) {
    if (strpos($class, 'App\\') === 0) {
        echo $class . "\n";
    }
}
```

## CORS関連エラー

### エラー: "CORS policy: No 'Access-Control-Allow-Origin' header"

**症状**: フロントエンドからのAPIアクセスでCORSエラーが発生

**解決方法**:

1. **CORS設定の確認** (`config/cors.php`):
```php
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'up'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [
        'http://localhost:3000',  // Next.js dev server
        'http://localhost:3001',  // 追加ポート
        // 本番環境のドメインも追加
    ],
    'allowed_headers' => ['*'],
    'supports_credentials' => true,
];
```

2. **CORS設定の反映**:
```bash
php artisan config:clear
php artisan config:cache
```

3. **フロントエンド側の設定確認**:
```javascript
// axios設定例
axios.defaults.withCredentials = true;
axios.defaults.headers.common['Accept'] = 'application/json';
```

## 依存関係問題

### エラー: Class not found

**症状**: 特定のクラスが見つからないエラー

**解決方法**:

1. **Composer autoload の再生成**:
```bash
composer dump-autoload --optimize
```

2. **必要なパッケージの確認**:
```bash
composer show  # インストール済みパッケージ一覧
composer require <package-name>  # 不足パッケージの追加
```

3. **名前空間の確認**:
```php
// use文が正しく設定されているか確認
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;
```

### エラー: Sanctum関連機能が動作しない

**症状**: トークン生成やAPI認証が失敗する

**解決方法**:

1. **Sanctum設定の公開**:
```bash
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
```

2. **マイグレーション実行**:
```bash
php artisan migrate
```

3. **Userモデルの確認**:
```php
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens; // このトレイトが追加されているか確認
}
```

## 環境設定エラー

### エラー: 環境変数が反映されない

**症状**: .envファイルの変更が反映されない

**解決方法**:
```bash
# 設定キャッシュのクリア
php artisan config:clear

# 必要に応じて設定キャッシュの再作成
php artisan config:cache

# .envファイルの権限確認
ls -la .env
```

### エラー: Docker環境での接続問題

**症状**: データベースやRedisへの接続が失敗する

**解決方法**:

1. **Docker Sailの状態確認**:
```bash
./vendor/bin/sail ps
```

2. **ネットワーク設定の確認**:
```bash
# .envファイルでホスト名を確認
DB_HOST=pgsql  # dockerサービス名を使用
REDIS_HOST=redis
```

3. **ポート設定の確認**:
```bash
# compose.yamlで定義されたポートを確認
# APP_PORT=13000 などカスタムポートが正しく設定されているか
```

## ヘルプとサポート

### 追加の診断コマンド

**システム情報の確認**:
```bash
php artisan about
php artisan route:list
php artisan config:show auth
php artisan config:show cors
```

**ログの確認**:
```bash
tail -f storage/logs/laravel.log
```

**テスト実行でのデバッグ**:
```bash
php artisan test --verbose
php artisan test --filter=SanctumAuthenticationTest
```

### よくある質問

**Q: セッション機能を部分的に復活させることは可能ですか？**
A: 技術的には可能ですが、API専用設計の利点（ステートレス、スケーラビリティ）が失われます。代わりにSanctumトークンの活用を推奨します。

**Q: 既存のWebページを復活させたい場合は？**
A: `routes/web.php`の再作成と`resources/views`の復元が必要ですが、最適化の効果が減少します。別プロジェクトでの実装を推奨します。

**Q: パフォーマンステストの結果が環境によって異なります**
A: Docker環境、ローカル環境、本番環境でハードウェア性能が異なるため、相対的な改善率を重視してください。

### サポート情報

**追加ドキュメント**:
- [Laravel最適化プロセス詳細](./laravel-optimization-process.md)
- [設定変更詳細](./configuration-changes.md)
- [パフォーマンスレポート](./performance-report.md)

**関連リソース**:
- Laravel Sanctum公式ドキュメント: https://laravel.com/docs/12.x/sanctum
- Laravel最適化ガイド: https://laravel.com/docs/12.x/deployment#optimization
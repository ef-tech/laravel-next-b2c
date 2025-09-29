# 設定変更詳細

Laravel最小限パッケージ構成の最適化において実施された全ての設定変更を詳細に記録します。

## 変更概要

| ファイル | 変更種別 | 概要 |
|---------|---------|------|
| `composer.json` | 変更 | Laravel Sanctum追加、不要パッケージ削除 |
| `bootstrap/app.php` | 変更 | API専用ルーティング構成 |
| `.env` | 変更 | セッション無効化設定 |
| `config/auth.php` | 変更 | Sanctum認証中心設定 |
| `config/cors.php` | 新規作成 | Next.js連携CORS設定 |
| `app/Models/User.php` | 変更 | HasApiTokensトレイト追加 |
| `routes/web.php` | 削除 | Web機能完全除去 |
| `resources/views/` | 削除 | ビューテンプレート完全除去 |

---

## composer.json

### 変更前
```json
{
    "name": "laravel/laravel",
    "type": "project",
    "description": "The skeleton application for the Laravel framework.",
    "keywords": ["laravel", "framework"],
    "license": "MIT",
    "require": {
        "php": "^8.2",
        "laravel/framework": "^11.9",
        "laravel/tinker": "^2.9"
    },
    "require-dev": {
        "fakerphp/faker": "^1.23",
        "laravel/pint": "^1.13",
        "laravel/sail": "^1.26",
        "mockery/mockery": "^1.6",
        "nunomaduro/collision": "^8.0",
        "phpunit/phpunit": "^11.0.1"
    }
}
```

### 変更後
```json
{
    "name": "laravel/laravel",
    "type": "project",
    "description": "The skeleton application for the Laravel framework.",
    "keywords": ["laravel", "framework"],
    "license": "MIT",
    "require": {
        "php": "^8.4",
        "laravel/framework": "^12.0",
        "laravel/sanctum": "^4.0",
        "laravel/tinker": "^2.10.1"
    },
    "require-dev": {
        "fakerphp/faker": "^1.23",
        "larastan/larastan": "^3.7",
        "laravel/pint": "^1.24",
        "mockery/mockery": "^1.6",
        "nunomaduro/collision": "^8.7.2",
        "phpunit/phpunit": "^11.5.3"
    }
}
```

### 変更理由
1. **Laravel Sanctum追加**: API認証機能の提供
2. **PHP 8.4 & Laravel 12.0**: 最新バージョンによる性能向上
3. **Larastan追加**: 静的解析による品質向上
4. **Laravel Sail削除**: Docker開発環境は別途管理

---

## bootstrap/app.php

### 変更前
```php
<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
```

### 変更後
```php
<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);

        $middleware->alias([
            'auth' => \App\Http\Middleware\Authenticate::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
```

### 変更点
1. **Webルート削除**: `web: __DIR__.'/../routes/web.php',` を完全削除
2. **Sanctum middleware追加**: API認証のためのミドルウェア設定
3. **認証エイリアス追加**: API認証での利用を明確化

---

## .env設定

### 変更内容
```env
# 追加された設定
SESSION_DRIVER=array

# 既存設定は維持
APP_NAME=Laravel
APP_ENV=local
APP_KEY=base64:...
APP_DEBUG=true
APP_TIMEZONE=UTC
APP_URL=http://localhost
APP_PORT=13000

# データベース設定（既存維持）
DB_CONNECTION=pgsql
DB_HOST=pgsql
DB_PORT=5432
DB_DATABASE=laravel
DB_USERNAME=sail
DB_PASSWORD=password

# その他の設定も既存を維持
```

### 変更理由
- **SESSION_DRIVER=array**: セッション機能を完全無効化してステートレスAPI実現

---

## config/auth.php

### 変更箇所
```php
// デフォルト認証ガードの変更
'defaults' => [
    'guard' => env('AUTH_GUARD', 'sanctum'), // 'web' から 'sanctum' に変更
    'passwords' => env('AUTH_PASSWORD_BROKER', 'users'),
],

// 認証ガード設定の追加
'guards' => [
    'web' => [
        'driver' => 'session',
        'provider' => 'users',
    ],
    // Sanctumガードを追加
    'sanctum' => [
        'driver' => 'sanctum',
        'provider' => null,
    ],
],
```

### 変更理由
1. **デフォルトガード変更**: API専用認証への移行
2. **Sanctumガード追加**: トークンベース認証の有効化

---

## config/cors.php

### 新規作成ファイル
```php
<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie', 'up'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost:3000',
        'http://localhost:3001',
        'http://127.0.0.1:3000',
        'http://127.0.0.1:3001',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
```

### 設定理由
1. **APIパス指定**: `/api/*` ルートでのCORSを有効化
2. **Next.js連携**: localhost:3000/3001でのフロントエンド開発対応
3. **認証情報サポート**: `supports_credentials: true`でトークン認証対応

---

## app/Models/User.php

### 変更前
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
```

### 変更後
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
```

### 変更点
1. **HasApiTokensトレイト追加**: Sanctum認証機能の有効化
2. **use文追加**: Laravel\Sanctum\HasApiTokensのインポート

---

## 削除されたファイル

### routes/web.php
**削除理由**: API専用アーキテクチャでWeb機能は不要

### resources/views/ ディレクトリ全体
**削除内容**:
- `resources/views/welcome.blade.php`
- その他のBladeテンプレートファイル

**削除理由**: ビューレンダリング機能が不要なため完全除去

---

## 影響を受けるミドルウェア

### 除去されたミドルウェア
Laravel標準のWeb用ミドルウェアが自動的に除外されます：

1. **StartSession**: セッション開始処理
2. **EncryptCookies**: Cookie暗号化処理
3. **VerifyCsrfToken**: CSRF保護処理
4. **SubstituteBindings**: ルートモデルバインディング（Web用）

### 保持されたAPIミドルウェア
API専用の必要なミドルウェアのみ保持：

1. **SubstituteBindings**: APIルートモデルバインディング
2. **Throttle**: レート制限
3. **EnsureFrontendRequestsAreStateful**: Sanctum SPA認証サポート

---

## 環境変数対応表

| 設定項目 | 変更前 | 変更後 | 用途 |
|---------|--------|--------|------|
| `SESSION_DRIVER` | `database` | `array` | セッション無効化 |
| `AUTH_GUARD` | 未設定(web) | `sanctum` | デフォルト認証 |
| `APP_PORT` | 8000 | 13000 | カスタムポート維持 |

---

## データベースマイグレーション

### 追加マイグレーション
```bash
php artisan migrate
```

**追加テーブル**:
- `personal_access_tokens`: Sanctum認証トークン管理

**テーブル構造**:
```php
Schema::create('personal_access_tokens', function (Blueprint $table) {
    $table->id();
    $table->morphs('tokenable');
    $table->string('name');
    $table->string('token', 64)->unique();
    $table->text('abilities')->nullable();
    $table->timestamp('last_used_at')->nullable();
    $table->timestamp('expires_at')->nullable();
    $table->timestamps();
});
```

---

## 設定値の検証

### 設定確認コマンド
```bash
# 認証設定の確認
php artisan config:show auth

# CORS設定の確認
php artisan config:show cors

# 全体設定の確認
php artisan about
```

### 期待される出力例
```
Environment ........................... local
Debug Mode ............................ true
URL ............................... http://localhost:13000
Timezone ................................ UTC

Cache .................................. array
Database ............................ pgsql
Queue ................................. sync
Session .............................. array
```

## トラブルシューティング参照

設定変更に関連する問題については、以下を参照してください：
- [トラブルシューティングガイド](./troubleshooting.md)
- [最適化プロセス詳細](./laravel-optimization-process.md)
# セキュリティベストプラクティス - 認証機能

Laravel + Next.js B2C アプリケーションにおける認証セキュリティのベストプラクティス

## 目次

- [トークンストレージ](#トークンストレージ)
- [CSRF対策](#csrf対策)
- [XSS対策](#xss対策)
- [パスワードハッシュ化](#パスワードハッシュ化)
- [レート制限](#レート制限)
- [セッションセキュリティ](#セッションセキュリティ)
- [HTTPS/TLS](#httpstls)
- [セキュリティヘッダー](#セキュリティヘッダー)
- [脆弱性対策](#脆弱性対策)

---

## トークンストレージ

### ストレージ方式の比較

| ストレージ | セキュリティ | 利便性 | 推奨度 |
|----------|-----------|-------|-------|
| **HttpOnly Cookie** | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐ | ✅ 最推奨 |
| **Secure Cookie (HTTPS)** | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐ | ✅ 本番環境推奨 |
| **LocalStorage** | ⭐⭐ | ⭐⭐⭐⭐⭐ | ⚠️ CSP必須 |
| **SessionStorage** | ⭐⭐ | ⭐⭐⭐ | ⚠️ CSP必須 |
| **Memory (State)** | ⭐⭐⭐⭐⭐ | ⭐⭐ | ❌ リフレッシュで消失 |

### HttpOnly Cookie（推奨）

#### メリット

- **XSS攻撃からトークンを保護**: JavaScript からアクセス不可能
- **自動送信**: ブラウザが自動的に Cookie を送信
- **サーバー側で制御可能**: `Secure`, `SameSite` 属性を設定可能

#### 実装例

**Laravel (バックエンド)**:

```php
// app/Http/Controllers/Api/V1/User/LoginController.php
public function login(LoginRequest $request): JsonResponse
{
    $credentials = $request->validated();

    try {
        $response = $this->loginUserUseCase->execute(
            $credentials['email'],
            $credentials['password']
        );

        // Cookie でトークンを返す
        return response()->json([
            'user' => $response->user,
        ])->cookie(
            'auth_token',           // Cookie 名
            $response->token,       // トークン
            60 * 24,                // 有効期限（分）
            '/',                    // パス
            null,                   // ドメイン
            true,                   // Secure（本番環境では true）
            true,                   // HttpOnly
            false,                  // Raw
            'lax'                   // SameSite
        );
    } catch (InvalidCredentialsException $e) {
        return response()->json([
            'code' => 'AUTH.INVALID_CREDENTIALS',
            'message' => $e->getMessage(),
            'details' => [],
        ], 401);
    }
}
```

**Next.js (フロントエンド)**:

```typescript
// frontend/user-app/src/contexts/AuthContext.tsx
const login = async (email: string, password: string) => {
  const response = await fetch(`${API_URL}/api/v1/user/login`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ email, password }),
    credentials: 'include', // ✅ Cookie を送受信するために必須
  });

  if (!response.ok) {
    throw new Error('Login failed');
  }

  const data = await response.json();
  setUser(data.user);
  // トークンは HttpOnly Cookie に保存されているため、
  // JavaScript からアクセスする必要なし
};
```

---

### LocalStorage（条件付き推奨）

#### メリット

- **SPA での実装が簡単**: JavaScript から直接アクセス可能
- **サーバー負荷が少ない**: Cookie の自動送信がない

#### デメリット

- **XSS 攻撃に脆弱**: JavaScript から読み取り可能
- **CSP 必須**: Content Security Policy で XSS 対策必須

#### 実装例（CSP 必須）

**Next.js (フロントエンド)**:

```typescript
// frontend/user-app/src/lib/auth-storage.ts
export const AuthStorage = {
  getToken(): string | null {
    if (typeof window === 'undefined') return null;
    return localStorage.getItem('auth_token');
  },

  setToken(token: string): void {
    if (typeof window === 'undefined') return;
    localStorage.setItem('auth_token', token);
  },

  clearToken(): void {
    if (typeof window === 'undefined') return;
    localStorage.removeItem('auth_token');
  },
};

// API クライアント
const fetchWithAuth = async (url: string, options: RequestInit = {}) => {
  const token = AuthStorage.getToken();

  return fetch(url, {
    ...options,
    headers: {
      ...options.headers,
      'Authorization': token ? `Bearer ${token}` : '',
      'Content-Type': 'application/json',
    },
  });
};
```

**CSP 設定（必須）**:

```typescript
// next.config.js
const cspHeader = `
  default-src 'self';
  script-src 'self' 'unsafe-eval' 'unsafe-inline';
  style-src 'self' 'unsafe-inline';
  img-src 'self' blob: data:;
  font-src 'self';
  object-src 'none';
  base-uri 'self';
  form-action 'self';
  frame-ancestors 'none';
  upgrade-insecure-requests;
`;

module.exports = {
  async headers() {
    return [
      {
        source: '/(.*)',
        headers: [
          {
            key: 'Content-Security-Policy',
            value: cspHeader.replace(/\n/g, ''),
          },
        ],
      },
    ];
  },
};
```

---

## CSRF対策

### Sanctum ステートレストークンの利用

Laravel Sanctum のステートレストークン（Bearer Token）を使用する場合、**CSRF 対策は不要** です。

#### なぜ CSRF 対策が不要か

1. **Cookie ベースではない**: Bearer Token は `Authorization` ヘッダーで送信
2. **Origin チェック**: CORS で Origin を検証
3. **自動送信されない**: ブラウザが自動的にトークンを送信しない

#### ステートレストークンの使用例

```typescript
// ✅ CSRF トークン不要
fetch('http://localhost:13000/api/v1/user/profile', {
  method: 'GET',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json',
  },
});
```

---

### Cookie ベース認証の場合（参考）

Cookie ベースの認証を使用する場合は、CSRF トークンが必要です。

#### CSRF トークン取得

```typescript
// CSRF Cookie 取得
await fetch('http://localhost:13000/sanctum/csrf-cookie', {
  credentials: 'include',
});

// API リクエスト（CSRF トークン自動送信）
await fetch('http://localhost:13000/api/v1/user/profile', {
  method: 'GET',
  credentials: 'include',
});
```

---

## XSS対策

### Content Security Policy (CSP)

CSP は XSS 攻撃を防ぐための最も効果的な方法です。

#### Laravel (バックエンド)

```php
// config/security.php
return [
    'csp' => [
        'enabled' => env('SECURITY_ENABLE_CSP', true),
        'report_only' => env('SECURITY_CSP_REPORT_ONLY', false),
        'report_uri' => env('SECURITY_CSP_REPORT_URI', '/api/csp-report'),

        'directives' => [
            'default-src' => ["'self'"],
            'script-src' => ["'self'"],
            'style-src' => ["'self'", "'unsafe-inline'"],
            'img-src' => ["'self'", 'data:', 'https:'],
            'font-src' => ["'self'"],
            'connect-src' => ["'self'"],
            'frame-ancestors' => ["'none'"],
            'form-action' => ["'self'"],
            'upgrade-insecure-requests' => [],
        ],
    ],
];

// app/Http/Middleware/SetSecurityHeaders.php
public function handle($request, Closure $next)
{
    $response = $next($request);

    if (config('security.csp.enabled')) {
        $csp = $this->buildCspHeader(config('security.csp.directives'));
        $headerName = config('security.csp.report_only')
            ? 'Content-Security-Policy-Report-Only'
            : 'Content-Security-Policy';

        $response->headers->set($headerName, $csp);
    }

    return $response;
}
```

#### Next.js (フロントエンド)

```typescript
// next.config.js
const cspHeader = `
  default-src 'self';
  script-src 'self' ${
    process.env.NODE_ENV === 'production' ? '' : "'unsafe-eval'"
  };
  style-src 'self' 'unsafe-inline';
  img-src 'self' blob: data: https:;
  font-src 'self';
  object-src 'none';
  base-uri 'self';
  form-action 'self';
  frame-ancestors 'none';
  upgrade-insecure-requests;
`;

module.exports = {
  async headers() {
    return [
      {
        source: '/(.*)',
        headers: [
          {
            key: 'Content-Security-Policy',
            value: cspHeader.replace(/\s{2,}/g, ' ').trim(),
          },
        ],
      },
    ];
  },
};
```

---

### 入力値のサニタイゼーション

#### Laravel (バックエンド)

```php
// ❌ 危険: 生の入力をそのまま出力
echo $request->input('name');

// ✅ 安全: Laravel Blade の自動エスケープ
{{ $user->name }}

// ✅ 安全: htmlspecialchars を使用
echo htmlspecialchars($request->input('name'), ENT_QUOTES, 'UTF-8');
```

#### Next.js (フロントエンド)

```tsx
// ✅ React は自動的にエスケープ
<h1>{user.name}</h1>

// ❌ 危険: dangerouslySetInnerHTML は使用しない
<div dangerouslySetInnerHTML={{ __html: user.bio }} />

// ✅ 安全: DOMPurify でサニタイズ
import DOMPurify from 'isomorphic-dompurify';

<div dangerouslySetInnerHTML={{
  __html: DOMPurify.sanitize(user.bio)
}} />
```

---

## パスワードハッシュ化

### bcrypt アルゴリズム

Laravel は デフォルトで **bcrypt** を使用しています。

#### ハッシュ化設定

```php
// config/hashing.php
return [
    'driver' => 'bcrypt',

    'bcrypt' => [
        'rounds' => env('BCRYPT_ROUNDS', 12), // ✅ 12以上推奨
    ],
];
```

#### コスト係数の推奨値

| 環境 | コスト係数 | ハッシュ化時間 |
|-----|----------|-------------|
| **開発環境** | 10 | ~100ms |
| **本番環境** | 12 | ~250ms |
| **高セキュリティ** | 14 | ~1000ms |

#### パスワードハッシュ化実装

```php
// ✅ 正しい実装
use Illuminate\Support\Facades\Hash;

// パスワードハッシュ化
$hashedPassword = Hash::make($password);

// パスワード検証
if (Hash::check($inputPassword, $hashedPassword)) {
    // パスワード正しい
}

// ❌ 間違った実装（bcrypt を直接使用しない）
$hashedPassword = password_hash($password, PASSWORD_BCRYPT);
```

---

### パスワードポリシー

#### 推奨ポリシー

| 項目 | 推奨値 |
|-----|-------|
| **最小文字数** | 8文字以上 |
| **文字種類** | 英大文字・小文字・数字・記号のうち3種類以上 |
| **辞書攻撃対策** | 一般的なパスワードを拒否 |
| **有効期限** | 90日（オプション） |
| **履歴** | 過去5回のパスワードを拒否（オプション） |

#### Laravel バリデーション実装

```php
// app/Http/Requests/Api/V1/User/LoginRequest.php
public function rules(): array
{
    return [
        'email' => 'required|email|max:255',
        'password' => [
            'required',
            'string',
            'min:8',                    // 最小8文字
            'regex:/[a-z]/',            // 小文字必須
            'regex:/[A-Z]/',            // 大文字必須
            'regex:/[0-9]/',            // 数字必須
            // 'regex:/[@$!%*#?&]/',    // 記号必須（オプション）
        ],
    ];
}

public function messages(): array
{
    return [
        'password.min' => 'パスワードは8文字以上である必要があります',
        'password.regex' => 'パスワードは英大文字・小文字・数字を含む必要があります',
    ];
}
```

---

## レート制限

### DynamicRateLimit Middleware の活用

このプロジェクトでは、`DynamicRateLimit` ミドルウェアを使用しています。

#### レート制限設定

```php
// app/Http/Middleware/DynamicRateLimit.php
protected array $limits = [
    'login' => [
        'max_attempts' => 5,
        'decay_minutes' => 1,
    ],
    'api' => [
        'max_attempts' => 60,
        'decay_minutes' => 1,
    ],
];

// routes/api.php
Route::prefix('v1')->group(function () {
    Route::prefix('user')->group(function () {
        // ログインエンドポイント: 1分間に5回まで
        Route::post('/login', [User\LoginController::class, 'login'])
            ->middleware('throttle:login');

        // その他のエンドポイント: 1分間に60回まで
        Route::middleware(['auth:sanctum', UserGuard::class])
            ->group(function () {
                Route::get('/profile', [User\ProfileController::class, 'show'])
                    ->middleware('throttle:api');
            });
    });
});
```

#### レート制限の推奨値

| エンドポイント | 制限 | 期間 | 理由 |
|--------------|------|------|------|
| **ログイン** | 5回 | 1分 | ブルートフォース攻撃対策 |
| **パスワードリセット** | 3回 | 1時間 | アカウント列挙攻撃対策 |
| **API (一般)** | 60回 | 1分 | DoS 攻撃対策 |
| **検索API** | 20回 | 1分 | リソース集約的な操作の制限 |

---

### Redis を使用したレート制限

#### Redis 設定

```.env
# backend/laravel-api/.env
RATELIMIT_CACHE_STORE=redis
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379
```

#### メリット

- **分散環境対応**: 複数サーバー間でレート制限を共有
- **永続化**: サーバー再起動後もカウントが保持される
- **パフォーマンス**: 高速なメモリベースストレージ

---

## セッションセキュリティ

### セッション設定

```.env
# backend/laravel-api/.env
SESSION_DRIVER=cookie
SESSION_LIFETIME=120          # 120分（2時間）
SESSION_EXPIRE_ON_CLOSE=false
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=localhost
SESSION_SECURE_COOKIE=true    # 本番環境では true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
```

### セッション固定攻撃対策

```php
// ログイン成功時にセッションIDを再生成
Auth::login($user);
$request->session()->regenerate();
```

---

## HTTPS/TLS

### 本番環境での HTTPS 強制

#### Laravel (バックエンド)

```php
// app/Providers/AppServiceProvider.php
public function boot()
{
    if ($this->app->environment('production')) {
        URL::forceScheme('https');
    }
}

// .env
APP_URL=https://api.example.com
```

#### Next.js (フロントエンド)

```typescript
// next.config.js
module.exports = {
  async headers() {
    return [
      {
        source: '/(.*)',
        headers: [
          {
            key: 'Strict-Transport-Security',
            value: 'max-age=31536000; includeSubDomains',
          },
        ],
      },
    ];
  },
};
```

---

## セキュリティヘッダー

### Laravel (バックエンド)

```php
// app/Http/Middleware/SetSecurityHeaders.php
public function handle($request, Closure $next)
{
    $response = $next($request);

    $response->headers->set('X-Content-Type-Options', 'nosniff');
    $response->headers->set('X-Frame-Options', 'DENY');
    $response->headers->set('X-XSS-Protection', '1; mode=block');
    $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
    $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');

    if ($this->app->environment('production')) {
        $response->headers->set(
            'Strict-Transport-Security',
            'max-age=31536000; includeSubDomains; preload'
        );
    }

    return $response;
}
```

### Next.js (フロントエンド)

```typescript
// next.config.js
module.exports = {
  async headers() {
    return [
      {
        source: '/(.*)',
        headers: [
          { key: 'X-Content-Type-Options', value: 'nosniff' },
          { key: 'X-Frame-Options', value: 'DENY' },
          { key: 'X-XSS-Protection', value: '1; mode=block' },
          { key: 'Referrer-Policy', value: 'strict-origin-when-cross-origin' },
          { key: 'Permissions-Policy', value: 'geolocation=(), microphone=(), camera=()' },
        ],
      },
    ];
  },
};
```

---

## 脆弱性対策

### 1. SQLインジェクション対策

```php
// ✅ Eloquent ORM を使用（自動的にプレースホルダー使用）
$user = User::where('email', $email)->first();

// ✅ Query Builder でバインディング使用
DB::table('users')
    ->where('email', '=', $email)
    ->first();

// ❌ 生のSQLクエリ（危険）
DB::select("SELECT * FROM users WHERE email = '{$email}'");

// ✅ 生のSQLクエリでもバインディング使用
DB::select('SELECT * FROM users WHERE email = ?', [$email]);
```

---

### 2. マスアサインメント対策

```php
// app/Models/User.php
class User extends Authenticatable
{
    // ✅ ホワイトリスト方式（推奨）
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    // または ❌ ブラックリスト方式（非推奨）
    // protected $guarded = ['id', 'is_admin'];
}
```

---

### 3. 認可チェック

```php
// ✅ ポリシーで認可チェック
public function update(Request $request, User $user)
{
    $this->authorize('update', $user);

    $user->update($request->validated());

    return response()->json($user);
}
```

---

### 4. ログとモニタリング

```php
// ログイン失敗をログに記録
Log::warning('Login attempt failed', [
    'email' => $email,
    'ip' => $request->ip(),
    'user_agent' => $request->userAgent(),
]);

// 不正なAPI アクセスをログに記録
Log::error('Unauthorized API access attempt', [
    'endpoint' => $request->path(),
    'method' => $request->method(),
    'ip' => $request->ip(),
]);
```

---

## セキュリティチェックリスト

### 開発環境

- [ ] `.env.example` に機密情報を含めない
- [ ] Git に `.env` をコミットしない
- [ ] デバッグモードを有効化 (`APP_DEBUG=true`)

### 本番環境

- [ ] HTTPS を強制する
- [ ] デバッグモードを無効化 (`APP_DEBUG=false`)
- [ ] 強力なアプリケーションキーを生成 (`php artisan key:generate`)
- [ ] セキュリティヘッダーを設定
- [ ] CSP を有効化
- [ ] レート制限を設定
- [ ] ログモニタリングを設定
- [ ] 定期的にパッケージを更新 (`composer update`, `npm update`)
- [ ] 脆弱性スキャンを実行 (`composer audit`, `npm audit`)

---

## 関連ドキュメント

- [セットアップガイド](./SETUP_GUIDE.md)
- [API仕様書](./API_SPECIFICATION.md)
- [認証フロー図](./AUTHENTICATION_FLOW.md)
- [トラブルシューティングガイド](./TROUBLESHOOTING.md)

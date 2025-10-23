<?php

namespace App\Providers;

use App\Bootstrap\ValidateEnvironment;
use Ddd\Application\RateLimit\Contracts\RateLimitService;
use Ddd\Application\RateLimit\Services\EndpointClassifier;
use Ddd\Application\RateLimit\Services\KeyResolver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->validateEnvironmentVariables();
        $this->validateCorsConfiguration();
        $this->configureRateLimiting();
    }

    /**
     * Configure rate limiting for the application.
     *
     * Register the 'dynamic' rate limiter that uses DDD services.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('dynamic', function (Request $request) {
            // DDD層のサービスを取得
            $classifier = app(EndpointClassifier::class);
            $keyResolver = app(KeyResolver::class);
            $rateLimitService = app(RateLimitService::class);

            // エンドポイント分類を取得
            $classification = $classifier->classify($request);
            $rule = $classification->getRule();

            // レート制限キーを解決
            $key = $keyResolver->resolve($request, $classification);

            // レート制限チェック
            $result = $rateLimitService->checkLimit($key, $rule);

            // Limit::perMinutes() を使用してLaravel標準のLimitオブジェクトを返す
            return Limit::perMinutes(
                $rule->getDecayMinutes(),
                $rule->getMaxAttempts()
            )->by($key->getKey());
        });
    }

    /**
     * 環境変数を検証する
     *
     * 起動時に環境変数のバリデーションを実行する
     */
    private function validateEnvironmentVariables(): void
    {
        $validator = new ValidateEnvironment;
        $validator->bootstrap($this->app);
    }

    /**
     * CORS設定の妥当性を検証する
     *
     * 起動時にCORS設定オリジンのURL形式を検証し、本番環境でのHTTPS強制やワイルドカード使用を警告する
     */
    private function validateCorsConfiguration(): void
    {
        $origins = config('cors.allowed_origins', []);
        $isProduction = app()->environment('production');

        foreach ($origins as $origin) {
            // URL形式検証
            $parsed = parse_url($origin);
            if (! $parsed || empty($parsed['scheme']) || empty($parsed['host'])) {
                Log::warning('Invalid CORS origin format', [
                    'origin' => $origin,
                    'parsed' => $parsed,
                ]);

                continue;
            }

            // 本番環境でのHTTPS検証
            if ($isProduction && $parsed['scheme'] !== 'https') {
                Log::warning('Non-HTTPS origin in production CORS', [
                    'origin' => $origin,
                    'environment' => 'production',
                ]);
            }
        }

        // ワイルドカード検証（本番環境）
        if ($isProduction && in_array('*', $origins, true)) {
            Log::warning('Wildcard origin in production is not recommended', [
                'environment' => 'production',
            ]);
        }
    }
}

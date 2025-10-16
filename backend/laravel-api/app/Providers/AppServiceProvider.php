<?php

namespace App\Providers;

use App\Bootstrap\ValidateEnvironment;
use Illuminate\Support\Facades\Log;
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

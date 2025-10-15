<?php

declare(strict_types=1);

namespace App\Bootstrap;

use App\Support\EnvSchema;
use App\Support\EnvValidator;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ValidateEnvironment
{
    /**
     * Bootstrap the given application.
     */
    public function bootstrap(Application $app): void
    {
        // ENV_VALIDATION_SKIP=true の場合はバリデーションをスキップ
        if ($this->shouldSkipValidation()) {
            Log::info('Environment variable validation skipped (ENV_VALIDATION_SKIP=true)');

            return;
        }

        // バリデーションモードを取得（デフォルトは 'error'）
        $mode = $this->getValidationMode();

        // 環境変数スキーマを取得
        $schema = EnvSchema::getSchema();

        // バリデーション実行
        $validator = new EnvValidator($schema, $_ENV, $mode);
        $result = $validator->validate();

        // バリデーション結果の処理
        if (! $result['valid']) {
            // エラーモードの場合は例外を投げる
            $this->handleValidationFailure($result['errors']);
        }

        // エラーがある場合はログに記録（警告モード時）
        if (! empty($result['errors'])) {
            $this->logValidationErrors($result['errors'], $mode);
        }

        // バリデーション成功をログに記録
        if (empty($result['errors'])) {
            Log::info('Environment variable validation passed successfully');
        }
    }

    /**
     * バリデーションをスキップすべきか判定
     */
    private function shouldSkipValidation(): bool
    {
        // getenv() と $_ENV の両方をチェック（CI/CD環境対応）
        $skipFlag = getenv('ENV_VALIDATION_SKIP') ?: ($_ENV['ENV_VALIDATION_SKIP'] ?? 'false');

        return strtolower($skipFlag) === 'true' || $skipFlag === '1';
    }

    /**
     * バリデーションモードを取得
     */
    private function getValidationMode(): string
    {
        // getenv() と $_ENV の両方をチェック（CI/CD環境対応）
        $mode = getenv('ENV_VALIDATION_MODE') ?: ($_ENV['ENV_VALIDATION_MODE'] ?? 'error');

        return in_array($mode, ['error', 'warning'], true) ? $mode : 'error';
    }

    /**
     * バリデーション失敗時の処理
     *
     * @param  array<string, string>  $errors
     */
    private function handleValidationFailure(array $errors): void
    {
        $errorMessages = [];
        foreach ($errors as $key => $message) {
            $errorMessages[] = "  - {$key}: {$message}";
        }

        $errorMessage = sprintf(
            "Environment variable validation failed:\n%s",
            implode("\n", $errorMessages)
        );

        Log::error($errorMessage);

        throw new RuntimeException($errorMessage);
    }

    /**
     * バリデーションエラーをログに記録
     *
     * @param  array<string, string>  $errors
     */
    private function logValidationErrors(array $errors, string $mode): void
    {
        $errorMessages = [];
        foreach ($errors as $key => $message) {
            $errorMessages[] = "  - {$key}: {$message}";
        }

        $logMessage = sprintf(
            "Environment variable validation errors (mode=%s):\n%s",
            $mode,
            implode("\n", $errorMessages)
        );

        Log::warning($logMessage);
    }
}

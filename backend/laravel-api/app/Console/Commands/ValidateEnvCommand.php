<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\EnvSchema;
use App\Support\EnvValidator;
use Illuminate\Console\Command;

class ValidateEnvCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'env:validate {--mode=error : Validation mode (error|warning)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Validate environment variables against schema definition';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Validating environment variables...');
        $this->newLine();

        // バリデーションモードを取得
        $mode = $this->option('mode');
        if (! in_array($mode, ['error', 'warning'], true)) {
            $this->warn("Invalid mode '{$mode}'. Using 'error' mode.");
            $mode = 'error';
        }

        // 環境変数スキーマを取得
        $schema = EnvSchema::getSchema();

        // バリデーション実行
        $validator = new EnvValidator($schema, $_ENV, $mode);
        $result = $validator->validate();

        // バリデーション結果の表示
        if (! $result['valid']) {
            $this->error('Environment variable validation failed!');
            $this->newLine();

            foreach ($result['errors'] as $key => $message) {
                $this->line("  <fg=red>✗</> {$key}");
                $this->line("    {$message}");
                $this->newLine();
            }

            return Command::FAILURE;
        }

        // エラーがある場合（警告モード）
        if (! empty($result['errors'])) {
            $this->warn('Environment variable validation completed with warnings:');
            $this->newLine();

            foreach ($result['errors'] as $key => $message) {
                $this->line("  <fg=yellow>⚠</> {$key}");
                $this->line("    {$message}");
                $this->newLine();
            }

            $this->info('Note: Running in warning mode. These issues should be addressed.');

            return Command::SUCCESS;
        }

        // バリデーション成功
        $this->line('  <fg=green>✓</> Environment variable validation passed successfully');
        $this->newLine();

        return Command::SUCCESS;
    }
}

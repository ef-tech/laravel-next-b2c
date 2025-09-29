<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MeasurePerformanceBaseline extends Command
{
    protected $signature = 'measure:baseline {--output=docs/performance_baseline.json : Output file path}';

    protected $description = 'Measure current performance baseline for optimization comparison';

    public function handle(): int
    {
        $this->info('Measuring performance baseline...');

        // 複数回測定して平均値を算出
        $measurements = [];
        $iterations = 5;

        for ($i = 0; $i < $iterations; $i++) {
            $measurements[] = $this->singleMeasurement();
            $this->info('Measurement '.($i + 1).' completed');
        }

        // 統計値計算
        $baseline = $this->calculateStatistics($measurements);
        $baseline['measured_at'] = now()->toISOString();
        $baseline['iterations'] = $iterations;
        $baseline['measurements'] = $measurements;

        // 依存関係数の追加
        $baseline['dependencies'] = $this->countDependencies();

        // 結果をファイルに保存
        $outputPath = $this->option('output');
        $fullPath = base_path($outputPath);

        // ディレクトリが存在しない場合は作成
        $directory = dirname($fullPath);
        if (! File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        File::put($fullPath, json_encode($baseline, JSON_PRETTY_PRINT));

        $this->info("Performance baseline saved to: {$outputPath}");
        $this->displayResults($baseline);

        return Command::SUCCESS;
    }

    private function singleMeasurement(): array
    {
        // メモリ使用量測定（開始時）
        $memoryStart = memory_get_usage(true);
        $memoryPeakStart = memory_get_peak_usage(true);

        // 起動時間測定
        $startTime = microtime(true);

        // Laravel アプリケーションの典型的な処理をシミュレート
        $this->simulateBootProcess();

        $bootTime = microtime(true) - $startTime;

        // メモリ使用量測定（終了時）
        $memoryEnd = memory_get_usage(true);
        $memoryPeak = memory_get_peak_usage(true);

        return [
            'boot_time_ms' => round($bootTime * 1000, 3),
            'memory_usage_mb' => round($memoryEnd / 1024 / 1024, 2),
            'memory_peak_mb' => round($memoryPeak / 1024 / 1024, 2),
            'memory_delta_mb' => round(($memoryEnd - $memoryStart) / 1024 / 1024, 2),
        ];
    }

    private function simulateBootProcess(): void
    {
        // Laravel の典型的なブート処理をシミュレート
        config('app.name');
        config('app.env');
        config('database.default');

        app('router');
        app('cache');
        app('db');

        // ルートキャッシュの読み込みシミュレーション
        if (file_exists(base_path('bootstrap/cache/routes-v7.php'))) {
            include base_path('bootstrap/cache/routes-v7.php');
        }

        // サービスプロバイダの初期化シミュレーション
        app('view');
        app('auth');
    }

    private function calculateStatistics(array $measurements): array
    {
        $bootTimes = array_column($measurements, 'boot_time_ms');
        $memoryUsages = array_column($measurements, 'memory_usage_mb');
        $memoryPeaks = array_column($measurements, 'memory_peak_mb');

        return [
            'boot_time_ms' => [
                'min' => min($bootTimes),
                'max' => max($bootTimes),
                'avg' => round(array_sum($bootTimes) / count($bootTimes), 3),
                'median' => $this->median($bootTimes),
            ],
            'memory_usage_mb' => [
                'min' => min($memoryUsages),
                'max' => max($memoryUsages),
                'avg' => round(array_sum($memoryUsages) / count($memoryUsages), 2),
                'median' => $this->median($memoryUsages),
            ],
            'memory_peak_mb' => [
                'min' => min($memoryPeaks),
                'max' => max($memoryPeaks),
                'avg' => round(array_sum($memoryPeaks) / count($memoryPeaks), 2),
                'median' => $this->median($memoryPeaks),
            ],
        ];
    }

    private function median(array $values): float
    {
        sort($values);
        $count = count($values);
        $middle = floor(($count - 1) / 2);

        if ($count % 2) {
            return $values[$middle];
        } else {
            return ($values[$middle] + $values[$middle + 1]) / 2;
        }
    }

    private function countDependencies(): array
    {
        $composerPath = base_path('composer.json');
        $composerContent = json_decode(File::get($composerPath), true);

        $productionCount = count($composerContent['require'] ?? []);
        $devCount = count($composerContent['require-dev'] ?? []);

        return [
            'production' => $productionCount,
            'development' => $devCount,
            'total' => $productionCount + $devCount,
            'packages' => [
                'production' => array_keys($composerContent['require'] ?? []),
                'development' => array_keys($composerContent['require-dev'] ?? []),
            ],
        ];
    }

    private function displayResults(array $baseline): void
    {
        $this->table(['Metric', 'Min', 'Max', 'Average', 'Median'], [
            [
                'Boot Time (ms)',
                $baseline['boot_time_ms']['min'],
                $baseline['boot_time_ms']['max'],
                $baseline['boot_time_ms']['avg'],
                $baseline['boot_time_ms']['median'],
            ],
            [
                'Memory Usage (MB)',
                $baseline['memory_usage_mb']['min'],
                $baseline['memory_usage_mb']['max'],
                $baseline['memory_usage_mb']['avg'],
                $baseline['memory_usage_mb']['median'],
            ],
            [
                'Memory Peak (MB)',
                $baseline['memory_peak_mb']['min'],
                $baseline['memory_peak_mb']['max'],
                $baseline['memory_peak_mb']['avg'],
                $baseline['memory_peak_mb']['median'],
            ],
        ]);

        $deps = $baseline['dependencies'];
        $this->info("Dependencies: {$deps['production']} production, {$deps['development']} development, {$deps['total']} total");
    }
}

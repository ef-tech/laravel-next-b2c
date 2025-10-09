<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Laravel\Sanctum\PersonalAccessToken;

class PruneExpiredTokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tokens:prune {--days=90 : Number of days to keep unused tokens}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prune unused personal access tokens older than specified days';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cutoffDate = now()->subDays($days);

        // Delete tokens that haven't been used for the specified number of days
        /** @var int $deletedCount */
        $deletedCount = PersonalAccessToken::where(function ($query) use ($cutoffDate): void {
            $query->where('last_used_at', '<', $cutoffDate)
                ->orWhere(function ($q) use ($cutoffDate): void {
                    $q->whereNull('last_used_at')
                        ->where('created_at', '<', $cutoffDate);
                });
        })->delete();

        $this->info("Pruned {$deletedCount} unused tokens older than {$days} days.");

        return self::SUCCESS;
    }
}

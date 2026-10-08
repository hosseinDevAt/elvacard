<?php

namespace App\Console\Commands;

use App\Services\CustomDesign\CustomDesignService;
use Illuminate\Console\Command;

class PruneCustomDesignsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'custom-designs:prune {--hours=24 : Number of hours before temporary custom designs are pruned}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prune stale customer custom design temporary uploads from private storage';

    /**
     * Execute the console command.
     */
    public function handle(CustomDesignService $service): int
    {
        $hours = (int) $this->option('hours');
        if ($hours < 0) {
            $hours = 24;
        }

        $this->info("Pruning temporary custom designs older than {$hours} hours...");

        $deletedCount = $service->pruneStaleTemp($hours);

        $this->info("Pruned {$deletedCount} stale temporary custom design(s).");

        return self::SUCCESS;
    }
}

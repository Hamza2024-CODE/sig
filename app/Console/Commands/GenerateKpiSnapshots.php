<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\DecisionEngine;
use Illuminate\Support\Facades\Schema;

class GenerateKpiSnapshots extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:kpi-snapshots';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Aggregates operational database metrics and stores daily snapshots in the kpi_snapshots table';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting KPI daily aggregation...');

        if (!Schema::hasTable('kpi_snapshots')) {
            $this->error('The table "kpi_snapshots" does not exist in the database.');
            $this->warn('Please run the migration manually using: php artisan migrate');
            return 1;
        }

        try {
            $engine = new DecisionEngine();
            $engine->runDailyAggregation();
            $this->info('Daily KPI snapshots generated and recorded successfully.');
            return 0;
        } catch (\Exception $e) {
            $this->error('Failed to generate KPI snapshots: ' . $e->getMessage());
            return 1;
        }
    }
}

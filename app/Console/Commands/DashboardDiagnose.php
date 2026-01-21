<?php

namespace App\Console\Commands;

use App\Services\DashboardMetricsService;
use Illuminate\Console\Command;

class DashboardDiagnose extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dashboard:diagnose';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Print dashboard metrics and recent rides';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $service = app(DashboardMetricsService::class);
        $metrics = $service->getDashboardMetrics();
        $recent = $service->getRecentRides(3);

        $this->info('Environment:');
        $this->line('MOCK_MODE=' . (isMockMode() ? 'true' : 'false'));
        $this->line('FIRESTORE_ENABLED=' . (isFirestoreEnabled() ? 'true' : 'false'));
        $this->info('Dashboard Metrics:');
        $this->line(json_encode($metrics, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        $this->info('Recent Rides (first 3):');
        $this->line(json_encode($recent, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        $this->info('SQL status: blocked in MOCK_MODE');

        return Command::SUCCESS;
    }
}

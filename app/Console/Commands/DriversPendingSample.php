<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\DriversService;

class DriversPendingSample extends Command
{
    protected $signature = 'drivers:pending-sample';
    protected $description = 'Print first 5 pending drivers from Firestore';

    public function handle()
    {
        $drivers = app(DriversService::class)->listPendingDrivers();
        $sample = array_slice($drivers, 0, 5);
        $this->info('Pending drivers sample:');
        foreach ($sample as $row) {
            $this->line(($row['uid'] ?? '-') . ' | ' . ($row['name'] ?? '-') . ' | ' . ($row['verificationStatus'] ?? '-'));
        }
        return 0;
    }
}

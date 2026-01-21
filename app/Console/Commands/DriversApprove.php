<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\DriversService;

class DriversApprove extends Command
{
    protected $signature = 'drivers:approve {uid}';
    protected $description = 'Approve driver by uid';

    public function handle()
    {
        $uid = (string) $this->argument('uid');
        $ok = app(DriversService::class)->approveDriver($uid);
        $this->info($ok ? 'Approved: ' . $uid : 'Failed to approve: ' . $uid);
        return $ok ? 0 : 1;
    }
}

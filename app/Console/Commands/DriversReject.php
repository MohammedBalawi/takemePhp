<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\DriversService;

class DriversReject extends Command
{
    protected $signature = 'drivers:reject {uid} {reason?}';
    protected $description = 'Reject driver by uid with optional reason';

    public function handle()
    {
        $uid = (string) $this->argument('uid');
        $reason = $this->argument('reason');
        $ok = app(DriversService::class)->rejectDriver($uid, $reason);
        $this->info($ok ? 'Rejected: ' . $uid : 'Failed to reject: ' . $uid);
        return $ok ? 0 : 1;
    }
}

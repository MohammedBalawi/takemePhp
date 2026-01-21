<?php

namespace App\Console\Commands;

use App\Services\FirestoreRestService;
use Illuminate\Console\Command;

class FirebasePing extends Command
{
    protected $signature = 'firebase:ping';

    protected $description = 'Ping Firestore via REST and report latency';

    public function handle(): int
    {
        if (function_exists('isFirestoreEnabled') && !isFirestoreEnabled()) {
            $this->error('FIRESTORE_ENABLED is false.');
            return Command::FAILURE;
        }

        $start = microtime(true);
        $service = app(FirestoreRestService::class);
        $doc = $service->getDocument('app_settings', 'default');
        $elapsed = (microtime(true) - $start) * 1000;

        if (!is_array($doc)) {
            $this->error('Firestore ping failed.');
            $this->line('response_ms=' . number_format($elapsed, 2));
            return Command::FAILURE;
        }

        $this->info('Firestore ping OK.');
        $this->line('response_ms=' . number_format($elapsed, 2));
        return Command::SUCCESS;
    }
}

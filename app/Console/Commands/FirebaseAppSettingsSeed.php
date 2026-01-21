<?php

namespace App\Console\Commands;

use App\Services\FirestoreRestService;
use Illuminate\Console\Command;

class FirebaseAppSettingsSeed extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firebase:appsettings:seed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed Firestore app_settings/default with mock defaults';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $defaults = config('mock_data.app_settings', []);
        if (!is_array($defaults) || count($defaults) === 0) {
            $this->error('No mock app_settings defaults found.');
            return Command::FAILURE;
        }
        if (!isset($defaults['language_option'])) {
            $defaults['language_option'] = 'ar';
        }

        $service = app(FirestoreRestService::class);
        $ok = $service->patchAppSettings($defaults);

        if ($ok) {
            $this->info('Firestore app_settings/default seeded with mock defaults.');
            return Command::SUCCESS;
        }

        $this->error('Failed to seed Firestore app_settings/default.');
        return Command::FAILURE;
    }
}

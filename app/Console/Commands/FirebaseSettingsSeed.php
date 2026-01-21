<?php

namespace App\Console\Commands;

use App\Services\FirestoreRestService;
use Illuminate\Console\Command;

class FirebaseSettingsSeed extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firebase:settings:seed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed Firestore settings/app_info with mock defaults';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $service = app(FirestoreRestService::class);
            $defaults = config('mock_data.settings.app_info', []);
            if (!is_array($defaults) || count($defaults) === 0) {
                $defaults = [
                    'app_title' => 'Take Me',
                    'image_title' => 'Take Me',
                    'app_name' => 'Take Me',
                ];
            }

            $ok = $service->patchDocument('settings', 'app_info', $defaults);

            if ($ok) {
                $this->info('Firestore settings/app_info seeded with mock defaults.');
                return Command::SUCCESS;
            }

            $this->error('Failed to seed Firestore settings/app_info (check credentials and project ID).');
            return Command::FAILURE;
        } catch (\Throwable $e) {
            $this->error('Failed to seed Firestore settings/app_info.');
            return Command::FAILURE;
        }
    }
}

<?php

namespace App\Console\Commands;

use App\Services\FirestoreRestService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class FirebaseFrontendSeed extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firebase:frontend:seed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed Firestore frontend_data documents with mock defaults';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $types = $this->detectTypes();
        $mockDefaults = config('mock_data.frontend_data', []);
        if (is_array($mockDefaults)) {
            $types = array_merge($types, array_keys($mockDefaults));
        }
        if (count($types) === 0) {
            $types = ['our_mission'];
        }

        $service = app(FirestoreRestService::class);
        $seeded = [];
        $failed = [];

        foreach ($types as $type) {
            $label = $this->labelFromType($type);
            $fields = [
                'title' => $label,
                'subtitle' => $label,
                'description' => 'Placeholder content for ' . $label . '.',
                'image' => '',
            ];
            if (is_array($mockDefaults) && isset($mockDefaults[$type]) && is_array($mockDefaults[$type])) {
                $fields = array_merge($fields, $mockDefaults[$type]);
            }

            $ok = $service->patchFrontendData($type, $fields);

            if ($ok) {
                $seeded[] = $type;
            } else {
                $failed[] = $type;
            }
        }

        if (count($seeded) > 0) {
            $this->info('Seeded frontend_data: ' . implode(', ', $seeded));
        }
        if (count($failed) > 0) {
            $this->error('Failed to seed frontend_data: ' . implode(', ', $failed));
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function detectTypes(): array
    {
        $paths = [
            app_path(),
            resource_path('views'),
        ];

        $types = [];
        foreach ($paths as $path) {
            if (!is_dir($path)) {
                continue;
            }

            foreach (File::allFiles($path) as $file) {
                $contents = File::get($file->getPathname());
                if (strpos($contents, 'FrontendData') === false && strpos($contents, 'frontend_data') === false) {
                    continue;
                }

                if (preg_match_all("/->where\\(\\s*['\"]type['\"]\\s*,\\s*['\"]([^'\"]+)['\"]/m", $contents, $matches)) {
                    foreach ($matches[1] as $match) {
                        $types[] = $match;
                    }
                }
            }
        }

        $types[] = 'our_mission';

        $types = array_values(array_unique($types));
        sort($types);
        return $types;
    }

    private function labelFromType(string $type): string
    {
        return ucwords(str_replace('_', ' ', $type));
    }
}

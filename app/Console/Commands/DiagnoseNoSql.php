<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

class DiagnoseNoSql extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:diagnose:nosql';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Diagnose mock/no-sql mode and render key routes';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('MOCK_MODE=' . (isMockMode() ? 'true' : 'false'));
        $this->info('FIRESTORE_ENABLED=' . (isFirestoreEnabled() ? 'true' : 'false'));

        $kernel = app(Kernel::class);

        $this->renderRoute($kernel, '/', 'Homepage');
        $this->renderRoute($kernel, '/home', 'Dashboard');

        return Command::SUCCESS;
    }

    private function renderRoute(Kernel $kernel, string $path, string $label): void
    {
        try {
            $request = Request::create($path, 'GET');
            $response = $kernel->handle($request);
            $kernel->terminate($request, $response);
            $this->info($label . ' rendered (status ' . $response->getStatusCode() . ')');
        } catch (\Throwable $e) {
            $this->error($label . ' error: ' . $e->getMessage());
        }
    }
}

<?php

namespace App\Console\Commands;

use App\Services\FirestoreRestService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class VerifyFirestore extends Command
{
    protected $signature = 'firestore:verify';
    protected $description = 'Verify Firestore connectivity and admins lookup';

    public function handle(): int
    {
        $enabled = (bool) env('FIRESTORE_ENABLED', false);
        $projectId = config('firebase.project_id');
        $credPath = config('firebase.credentials_path');

        $this->info('FIRESTORE_ENABLED=' . ($enabled ? 'true' : 'false'));
        $this->info('project_id=' . ($projectId ?: 'null'));
        $this->info('credentials_path=' . ($credPath ?: 'null'));
        $this->info('credentials_readable=' . ((is_string($credPath) && File::isFile($credPath)) ? 'true' : 'false'));

        $service = app(FirestoreRestService::class);

        $pingId = 'ping_' . uniqid();
        $okWrite = $service->patchDocumentTyped('debug', $pingId, ['ok' => true]);
        $this->info('write_ping=' . ($okWrite ? 'ok' : 'fail'));
        $okDelete = $service->deleteDocument('debug', $pingId);
        $this->info('delete_ping=' . ($okDelete ? 'ok' : 'fail'));

        $admin = $service->getAdminByEmail('admin@example.com');
        $this->info('getAdminByEmail(admin@example.com)=' . (empty($admin) ? 'not_found' : 'ok'));

        return Command::SUCCESS;
    }
}

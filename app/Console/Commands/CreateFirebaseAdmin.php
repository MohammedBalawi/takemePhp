<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use App\Services\FirestoreRestService;

class CreateFirebaseAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firebase:admin:create {--email=} {--password=} {--name=} {--roles=*} {--active=1}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create or update a Firestore admin profile via REST.';

    public function handle(): int
    {
        $email = strtolower(trim((string) $this->option('email')));
        $password = (string) $this->option('password');
        $name = (string) ($this->option('name') ?? 'Admin');
        $roles = $this->option('roles');
        $activeOption = (string) $this->option('active');

        $roles = is_array($roles) && count($roles) > 0 ? array_values($roles) : ['admin'];
        $isActive = !in_array(strtolower($activeOption), ['0', 'false', 'no', 'off'], true);

        if ($email === '' || $password === '') {
            $this->error('Email and password are required.');
            return Command::FAILURE;
        }

        $docId = 'admin';
        $fields = [
            'email' => $email,
            'name' => $name,
            'password_hash' => Hash::make($password),
            'is_active' => $isActive,
            'roles' => $roles,
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ];

        $service = app(FirestoreRestService::class);
        $ok = $service->upsertAdmin($docId, $fields);
        if (!$ok) {
            $this->error('Failed to upsert Firestore admin.');
            return Command::FAILURE;
        }

        $this->info('Firestore admin upserted.');
        $this->line('email: ' . $email);
        $this->line('doc: admins/' . $docId);
        $this->line('Usage example: php artisan firebase:admin:create --email="admin@example.com" --password="Password123" --name="Admin Name" --roles=admin --active=1');

        return Command::SUCCESS;
    }
}

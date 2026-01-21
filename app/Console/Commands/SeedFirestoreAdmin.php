<?php

namespace App\Console\Commands;

use App\Services\AdminAuth\AdminFirestoreRepository;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class SeedFirestoreAdmin extends Command
{
    protected $signature = 'admin:seed-firestore {--email=} {--password=} {--roles=}';
    protected $description = 'Upsert admin into Firestore admins collection';

    public function handle(): int
    {
        $email = (string) $this->option('email');
        $password = (string) $this->option('password');
        $roles = (string) $this->option('roles');

        if ($email === '' || $password === '') {
            $this->error('Email and password are required.');
            return Command::FAILURE;
        }

        $rolesArray = [];
        if ($roles !== '') {
            $rolesArray = array_values(array_unique(array_filter(array_map('trim', explode(',', $roles)))));
        }

        $fields = [
            'email' => strtolower($email),
            'name' => 'Admin',
            'roles' => $rolesArray,
            'is_active' => true,
            'password_hash' => Hash::make($password),
            'updated_at' => Carbon::now('UTC'),
        ];

        try {
            $ok = app(AdminFirestoreRepository::class)->upsertAdmin($fields);
        } catch (\Throwable $e) {
            $projectId = config('firebase.project_id');
            $credPath = config('firebase.credentials_path');
            $this->error('Failed to upsert admin: ' . $e->getMessage());
            $this->error('Firestore project_id=' . ($projectId ?? 'null') . ' credentials_path=' . ($credPath ? (is_file($credPath) ? 'exists' : 'missing') : 'null'));
            return Command::FAILURE;
        }
        if ($ok) {
            $this->info('Admin upserted in Firestore.');
            return Command::SUCCESS;
        }

        $this->error('Failed to upsert admin.');
        return Command::FAILURE;
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use App\Services\FirestoreRestService;

class FirestoreAdminCreate extends Command
{
    protected $signature = 'firestore:admin-create 
        {email} 
        {password} 
        {name?} 
        {--roles=*} 
        {--active=1}';

    protected $description = 'Create Firestore-only admin account';

    public function handle()
    {
        $email = strtolower(trim($this->argument('email')));
        $password = $this->argument('password');
        $name = $this->argument('name') ?: 'Admin';
        $roles = $this->option('roles') ?: ['admin'];
        $active = (bool) $this->option('active');

        if ($email === '' || $password === '') {
            $this->error('Email and password are required');
            return 1;
        }

        try {
            $service = app(FirestoreRestService::class);
            $ok = $service->upsertAdmin($email, [
                'email' => $email,
                'name' => $name,
                'roles' => array_values($roles),
                'is_active' => $active,
                'password_hash' => Hash::make($password),
                'created_at' => now()->toIso8601String(),
                'updated_at' => now()->toIso8601String(),
            ]);
            if (!$ok) {
                throw new \RuntimeException('upsertAdmin returned false');
            }

            $this->info("Admin created successfully: admins/{$email}");
            return 0;
        } catch (\Throwable $e) {
            $this->error('Failed: ' . $e->getMessage());
            return 1;
        }
    }
}

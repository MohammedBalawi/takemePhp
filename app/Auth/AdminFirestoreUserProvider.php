<?php

namespace App\Auth;

use App\Services\AdminAuth\AdminFirestoreRepository;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Support\Facades\Hash;

class AdminFirestoreUserProvider implements UserProvider
{
    private AdminFirestoreRepository $repo;

    public function __construct(AdminFirestoreRepository $repo)
    {
        $this->repo = $repo;
    }

    public function retrieveById($identifier)
    {
        $data = session('admin_auth');
        if (is_array($data) && ($data['id'] ?? null) === $identifier) {
            return new FirestoreAdminUser($data);
        }

        $admin = $this->repo->findById((string) $identifier);
        if (!$admin) {
            return null;
        }
        return new FirestoreAdminUser([
            'id' => $admin['uid'] ?? ($admin['email'] ?? $identifier),
            'email' => $admin['email'] ?? '',
            'name' => $admin['name'] ?? 'Admin',
            'roles' => $admin['roles'] ?? [],
            'is_active' => $admin['is_active'] ?? true,
            'user_type' => 'admin',
        ]);
    }

    public function retrieveByToken($identifier, $token)
    {
        return null;
    }

    public function updateRememberToken(Authenticatable $user, $token)
    {
    }

    public function retrieveByCredentials(array $credentials)
    {
        $email = $credentials['email'] ?? '';
        $admin = $this->repo->findByEmail((string) $email);
        if (!$admin) {
            return null;
        }
        return new FirestoreAdminUser([
            'id' => $admin['uid'] ?? ($admin['email'] ?? ''),
            'email' => $admin['email'] ?? '',
            'name' => $admin['name'] ?? 'Admin',
            'roles' => $admin['roles'] ?? [],
            'is_active' => $admin['is_active'] ?? true,
            'user_type' => 'admin',
            'password_hash' => $admin['password_hash'] ?? null,
        ]);
    }

    public function validateCredentials(Authenticatable $user, array $credentials)
    {
        $hash = $user->getAuthPassword();
        if (!is_string($hash) || $hash === '') {
            return false;
        }
        return Hash::check($credentials['password'] ?? '', $hash);
    }

    public function rehashPasswordIfRequired(Authenticatable $user, array $credentials, bool $force = false)
    {
    }
}

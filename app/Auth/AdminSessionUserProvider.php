<?php

namespace App\Auth;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;

class AdminSessionUserProvider implements UserProvider
{
    public function retrieveById($identifier)
    {
        $data = session('admin_auth');
        if (!is_array($data)) {
            return null;
        }

        if (($data['id'] ?? null) !== $identifier) {
            return null;
        }

        return new FirestoreAdminUser($data);
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
        return null;
    }

    public function validateCredentials(Authenticatable $user, array $credentials)
    {
        return false;
    }

    public function rehashPasswordIfRequired(Authenticatable $user, array $credentials, bool $force = false)
    {
    }
}

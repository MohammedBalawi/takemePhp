<?php

namespace App\Services;

use Illuminate\Support\Facades\Hash;

class FirestoreAdminAuth
{
    private FirestoreRestService $firestoreRest;

    public function __construct(FirestoreRestService $firestoreRest)
    {
        $this->firestoreRest = $firestoreRest;
    }

    public function attempt(string $email, string $password): ?array
    {
        $email = strtolower(trim($email));
        if ($email === '' || $password === '') {
            return null;
        }

        $admin = $this->firestoreRest->getDocumentFields('admins', 'admin');
        if (is_array($admin)) {
            $admin['docId'] = 'admin';
            $admin['email'] = isset($admin['email']) ? strtolower((string) $admin['email']) : '';
            if ($admin['email'] !== $email) {
                $admin = null;
            }
        } else {
            $admin = $this->firestoreRest->getAdminByEmail($email);
        }
        if (!is_array($admin)) {
            return null;
        }

        $passwordHash = $admin['password_hash'] ?? null;
        if (!is_string($passwordHash) || $passwordHash === '') {
            return null;
        }

        if (!$this->verifyPassword($password, $passwordHash)) {
            return null;
        }

        $isActive = $admin['is_active'] ?? true;
        if ($isActive === false || $isActive === 0 || $isActive === '0') {
            return null;
        }

        $roles = $admin['roles'] ?? ['admin'];
        $roles = is_array($roles) ? $roles : ['admin'];

        $name = $admin['name'] ?? 'Admin';
        $uid = $admin['docId'] ?? $email;

        return [
            'uid' => $uid,
            'email' => $admin['email'] ?? $email,
            'name' => $name,
            'roles' => $roles,
        ];
    }

    private function verifyPassword(string $password, string $hash): bool
    {
        if (password_verify($password, $hash)) {
            return true;
        }
        return Hash::check($password, $hash);
    }
}

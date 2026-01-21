<?php

namespace App\Services\AdminAuth;

use App\Services\FirestoreRestService;

class AdminFirestoreRepository
{
    private FirestoreRestService $firestore;

    public function __construct(FirestoreRestService $firestore)
    {
        $this->firestore = $firestore;
    }

    public function findByEmail(string $email): ?array
    {
        $email = strtolower(trim($email));
        if ($email === '') {
            return null;
        }

        $admin = $this->firestore->getAdminByEmail($email);
        if (!empty($admin)) {
            return $this->normalizeAdmin($admin);
        }

        $doc = $this->firestore->getDocumentFields('admins', $email) ?? [];
        if (!empty($doc)) {
            $doc['email'] = $doc['email'] ?? $email;
            return $this->normalizeAdmin($doc);
        }

        return null;
    }

    public function findById(string $id): ?array
    {
        $id = trim($id);
        if ($id === '') {
            return null;
        }

        $doc = $this->firestore->getDocumentFields('admins', $id) ?? [];
        if (empty($doc)) {
            return null;
        }
        $doc['email'] = $doc['email'] ?? $id;
        return $this->normalizeAdmin($doc);
    }

    public function upsertAdmin(array $data): bool
    {
        $email = strtolower(trim((string) ($data['email'] ?? '')));
        if ($email === '') {
            return false;
        }
        $docId = (string) ($data['uid'] ?? $email);
        return $this->firestore->patchDocumentTyped('admins', $docId, $data);
    }

    private function normalizeAdmin(array $admin): array
    {
        $roles = $admin['roles'] ?? [];
        if (is_string($roles)) {
            $roles = [$roles];
        }
        $roles = array_values(array_unique(array_filter(array_map(function ($role) {
            return is_string($role) ? strtolower(trim($role)) : null;
        }, is_array($roles) ? $roles : []))));

        $admin['roles'] = $roles;
        return $admin;
    }
}

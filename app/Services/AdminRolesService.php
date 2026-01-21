<?php

namespace App\Services;

use App\Support\FeatureFlags;

class AdminRolesService
{
    private FirestoreRestService $firestore;
    private static bool $logged = false;

    public function __construct(FirestoreRestService $firestore)
    {
        $this->firestore = $firestore;
    }

    public function getCurrentAdminDoc(): array
    {
        if (\App\Support\FeatureFlags::useMock()) {
            $mock = config('mock_data.mock_admins', []);
            if (is_array($mock) && count($mock) > 0) {
                return $mock[0];
            }
            return [
                'email' => 'mock@example.com',
                'name' => 'Mock Admin',
                'roles' => ['admin'],
                'is_active' => true,
            ];
        }
        $sessionAdmin = session('admin_auth', []);
        $uid = $sessionAdmin['id'] ?? (auth()->guard('admin')->user()->id ?? null);
        $email = $sessionAdmin['email'] ?? (auth()->guard('admin')->user()->email ?? null);

        $doc = [];
        $source = '';

        if (FeatureFlags::firestoreEnabled() && !empty($uid)) {
            $doc = $this->firestore->getDocumentFields('admins', (string) $uid) ?? [];
            if (!empty($doc)) {
                $source = 'docId';
            }
        }

        if (empty($doc) && FeatureFlags::firestoreEnabled() && !empty($email)) {
            $doc = $this->firestore->getAdminByEmail((string) $email) ?? [];
            if (!empty($doc)) {
                $source = 'emailQuery';
            }
        }

        if (empty($doc)) {
            $doc = [
                'email' => $email,
                'name' => $sessionAdmin['name'] ?? null,
                'roles' => $sessionAdmin['roles'] ?? [],
            ];
            $source = 'session';
        }

        if (env('APP_DEBUG') && !self::$logged) {
            $roles = $this->normalizeRoles($doc['roles'] ?? []);
            logger()->debug('ADMIN_ROLES_FETCH source=' . $source . ' roles=' . json_encode($roles));
            self::$logged = true;
        }

        return $doc;
    }

    public function getRoles(): array
    {
        $doc = $this->getCurrentAdminDoc();
        $roles = $doc['roles'] ?? ($doc['role'] ?? []);
        return $this->normalizeRoles($roles);
    }

    public function isSuperAdmin(): bool
    {
        $roles = $this->getRoles();
        return in_array('super_admin', $roles, true) || in_array('admin', $roles, true);
    }

    public function isSubAdmin(): bool
    {
        $roles = $this->getRoles();
        return in_array('sub_admin', $roles, true);
    }

    public function permissionProfile(): array
    {
        if ($this->isSuperAdmin()) {
            return [
                'key' => 'full',
                'canEdit' => true,
                'canViewAllMenus' => true,
                'allowedRoutes' => ['*'],
            ];
        }

        if ($this->isSubAdmin()) {
            return [
                'key' => 'stats',
                'canEdit' => false,
                'canViewAllMenus' => false,
                'allowedRoutes' => [
                    'home',
                    'permissions.index',
                ],
            ];
        }

        return [
            'key' => 'stats',
            'canEdit' => false,
            'canViewAllMenus' => false,
            'allowedRoutes' => [
                'home',
                'permissions.index',
            ],
        ];
    }

    private function normalizeRoles($roles): array
    {
        if (is_string($roles)) {
            $roles = [$roles];
        }
        if (!is_array($roles)) {
            return [];
        }
        $normalized = [];
        foreach ($roles as $role) {
            if (!is_string($role)) {
                continue;
            }
            $role = trim($role);
            if ($role === '') {
                continue;
            }
            $normalized[] = strtolower($role);
        }
        return array_values(array_unique($normalized));
    }
}

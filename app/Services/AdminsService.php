<?php

namespace App\Services;

use App\Support\FeatureFlags;
use Carbon\Carbon;

class AdminsService
{
    private FirestoreRestService $firestore;
    private static bool $logged = false;

    public function __construct(FirestoreRestService $firestore)
    {
        $this->firestore = $firestore;
    }

    public function listAdmins(): array
    {
        if (\App\Support\FeatureFlags::useMock()) {
            $mock = config('mock_data.mock_admins', []);
            return is_array($mock) ? $mock : [];
        }
        if (!FeatureFlags::firestoreEnabled()) {
            return [];
        }

        $docs = $this->firestore->listDocuments('admins', 200);
        $rows = [];
        foreach ($docs as $doc) {
            $rows[] = $this->mapAdmin($doc, $doc);
        }

        usort($rows, function ($a, $b) {
            return strcmp($b['created_at'] ?? '', $a['created_at'] ?? '');
        });

        return $rows;
    }

    public function updateAdminRoles(string $docIdOrEmail, array $roles, bool $isActive): bool
    {
        if (!FeatureFlags::firestoreEnabled()) {
            return false;
        }

        $roles = $this->normalizeRoles($roles);
        $docId = $this->resolveDocIdByEmail($docIdOrEmail) ?? $docIdOrEmail;
        if ($docId === '') {
            return false;
        }

        $fields = [
            'roles' => $roles,
            'is_active' => $isActive,
            'updated_at' => Carbon::now('UTC'),
        ];

        $ok = $this->firestore->patchDocumentTyped('admins', $docId, $fields);
        if ($ok) {
            logger()->info('ADMIN_ROLES_UPDATED email=' . $docIdOrEmail . ' roles=' . json_encode($roles) . ' is_active=' . ($isActive ? 'true' : 'false'));
        }
        return $ok;
    }

    public function resolveDocIdByEmail(string $email): ?string
    {
        if ($email === '') {
            return null;
        }

        $results = $this->firestore->runStructuredQuery('admins', [
            ['field' => 'email', 'op' => 'EQUAL', 'value' => ['stringValue' => $email]],
        ], 1);

        if (count($results) === 0) {
            return null;
        }

        return $results[0]['__id'] ?? null;
    }

    private function mapAdmin(array $doc, array $raw = []): array
    {
        $createdAt = $doc['created_at'] ?? $doc['createdAt'] ?? ($raw['_updateTime'] ?? null);
        return [
            'id' => $this->scalarize($doc['_docId'] ?? ($doc['uid'] ?? '')),
            'email' => $this->scalarize($doc['email'] ?? ''),
            'name' => $this->scalarize($doc['name'] ?? ''),
            'is_active' => (bool) ($doc['is_active'] ?? false),
            'roles' => $this->normalizeRoles($doc['roles'] ?? []),
            'created_at' => $this->formatTimestamp($createdAt),
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
        $allowed = ['super_admin', 'admin', 'sub_admin'];
        $out = [];
        foreach ($roles as $role) {
            if (!is_string($role)) {
                continue;
            }
            $role = strtolower(trim($role));
            if ($role === '' || !in_array($role, $allowed, true)) {
                continue;
            }
            $out[] = $role;
        }
        return array_values(array_unique($out));
    }

    private function scalarize($value): string
    {
        if ($value === null) {
            return '';
        }
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        if (is_scalar($value)) {
            return (string) $value;
        }
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }
        return '';
    }

    private function formatTimestamp($value): string
    {
        if (!$value) {
            return '';
        }

        try {
            return Carbon::parse($value)->format('Y-m-d H:i:s');
        } catch (\Throwable $e) {
            return (string) $value;
        }
    }
}

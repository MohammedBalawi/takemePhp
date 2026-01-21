<?php

namespace App\Services;

use App\Support\FeatureFlags;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SubAdminsService
{
    private FirestoreRestService $firestore;
    private static array $cache = [];
    private static array $logged = [];

    public function __construct(FirestoreRestService $firestore)
    {
        $this->firestore = $firestore;
    }

    public function listSubAdmins(): array
    {
        if (!FeatureFlags::shouldUseFirestore('SUBADMINS')) {
            throw new \RuntimeException('FIRESTORE_ENABLED=false for subadmins');
        }

        if (isset(self::$cache['subadmins.list'])) {
            return self::$cache['subadmins.list'];
        }

        try {
            $documents = $this->firestore->listDocuments('admins', 500);
            $mapped = [];

            foreach ($documents as $doc) {
                $roles = $doc['roles'] ?? [];
                $isSubAdmin = is_array($roles) && in_array('sub_admin', $roles, true);
                if (!$isSubAdmin) {
                    continue;
                }
                $mapped[] = $this->mapSubAdmin($doc, $doc);
            }

            usort($mapped, function ($a, $b) {
                return strcmp($b['created_at'] ?? '', $a['created_at'] ?? '');
            });

            if (env('APP_DEBUG')) {
                $uids = array_slice(array_map(function ($admin) {
                    return $admin['uid'] ?? '';
                }, $mapped), 0, 2);
                $sampleKeys = [];
                if (count($documents) > 0) {
                    $sampleKeys = array_keys($documents[0]);
                }
                logger()->debug('SUBADMINS_QUERY count=' . count($mapped) . ' sampleKeys=' . implode(',', $sampleKeys));
                logger()->debug('SUBADMINS_FIRESTORE_LIST totalDocs=' . count($documents) . ' subadmins=' . count($mapped) . ' uids=' . implode(',', $uids));
                if (count($mapped) === 0) {
                    logger()->debug('SUBADMINS_ZERO_HINT rolesField=roles value=admin');
                }
            }

            self::$cache['subadmins.list'] = $mapped;
            return $mapped;
        } catch (\Throwable $e) {
            $this->logFallbackOnce($e);
            return [];
        }
    }

    public function createSubAdmin(array $payload): bool
    {
        if (!FeatureFlags::shouldUseFirestore('SUBADMINS')) {
            throw new \RuntimeException('FIRESTORE_ENABLED=false for subadmins');
        }

        try {
            $uid = $payload['uid'] ?? Str::uuid()->toString();
            $now = Carbon::now('UTC');

            $fields = [
                'uid' => $uid,
                'name' => (string) ($payload['name'] ?? ''),
                'email' => (string) ($payload['email'] ?? ''),
                'roles' => ['sub_admin'],
                'is_active' => (bool) ($payload['is_active'] ?? true),
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (!empty($payload['password'])) {
                $fields['password_hash'] = Hash::make((string) $payload['password']);
            }

            return $this->firestore->patchDocumentTyped('admins', $uid, $fields);
        } catch (\Throwable $e) {
            $this->logFallbackOnce($e);
            return false;
        }
    }

    private function mapSubAdmin(array $doc, array $raw = []): array
    {
        $uid = $doc['uid'] ?? ($raw['_docId'] ?? '');
        $createdAt = $doc['created_at'] ?? $doc['createdAt'] ?? $doc['updated_at'] ?? ($raw['_updateTime'] ?? '');

        return [
            'uid' => $uid,
            'name' => $doc['name'] ?? $doc['full_name'] ?? $doc['display_name'] ?? '-',
            'email' => $doc['email'] ?? '',
            'is_active' => $doc['is_active'] ?? true,
            'created_at' => $this->formatTimestamp($createdAt),
        ];
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

    private function logFallbackOnce(\Throwable $e): void
    {
        if (isset(self::$logged['SUBADMINS'])) {
            return;
        }

        $reason = stripos($e->getMessage(), 'timeout') !== false ? 'timeout' : 'exception';
        logger()->warning('FIRESTORE_FALLBACK feature=SUBADMINS reason=' . $reason . ' message=' . $e->getMessage());
        self::$logged['SUBADMINS'] = true;
    }
}

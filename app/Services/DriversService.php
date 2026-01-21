<?php

namespace App\Services;

use App\Support\FeatureFlags;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DriversService
{
    private FirestoreRestService $firestore;
    private static array $cache = [];
    private static array $logged = [];

    public function __construct(FirestoreRestService $firestore)
    {
        $this->firestore = $firestore;
    }

    public function listDrivers(?string $status = null): array
    {
        $cacheKey = 'drivers.list.' . ($status ?: 'all');

        if (!FeatureFlags::driversFirestoreEnabled()) {
            throw new \RuntimeException('FIRESTORE_ENABLED=false for drivers');
        }

        if (isset(self::$cache[$cacheKey])) {
            return self::$cache[$cacheKey];
        }

        try {
            $documents = $this->firestore->listDocuments('drivers', 500);
            $mapped = [];

            foreach ($documents as $doc) {
                $fields = $doc;
                if ($status === 'pending' && !$this->isPending($fields)) {
                    continue;
                }
                $mapped[] = $this->mapDriver($doc, $fields);
            }

            usort($mapped, function ($a, $b) {
                return strcmp($b['created_at'] ?? '', $a['created_at'] ?? '');
            });

            if (env('APP_DEBUG')) {
                $sampleKeys = [];
                if (count($documents) > 0) {
                    $sampleKeys = array_keys($documents[0]);
                }
                logger()->debug('DRIVERS_FIRESTORE_QUERY count=' . count($mapped) . ' totalDocs=' . count($documents) . ' sampleKeys=' . implode(',', $sampleKeys));
                if ($status === 'pending') {
                    logger()->debug('DRIVERS_FIRESTORE_FILTER_PENDING count=' . count($mapped));
                }
                if (count($mapped) === 0) {
                    logger()->debug('DRIVERS_ZERO_HINT field=verificationStatus value=pending|not approved');
                }
            }

            self::$cache[$cacheKey] = $mapped;
            return $mapped;
        } catch (\Throwable $e) {
            $this->logFallbackOnce($e, 'DRIVERS');
            return [];
        }
    }

    public function createDriver(array $payload): bool
    {
        if (!FeatureFlags::driversFirestoreEnabled()) {
            throw new \RuntimeException('FIRESTORE_ENABLED=false for drivers');
        }

        try {
            $uid = $payload['uid'] ?? Str::uuid()->toString();
            $now = Carbon::now('UTC');

            $fields = [
                'uid' => $uid,
                'name' => (string) ($payload['name'] ?? ''),
                'email' => (string) ($payload['email'] ?? ''),
                'phone' => (string) ($payload['phone'] ?? ''),
                'username' => (string) ($payload['username'] ?? ''),
                'cityId' => (string) ($payload['cityId'] ?? ''),
                'verificationStatus' => (string) ($payload['verificationStatus'] ?? 'pending'),
                'isOnline' => false,
                'isAvailable' => false,
                'availability' => [
                    'online' => false,
                    'busy' => false,
                    'available' => false,
                ],
                'documents' => [
                    'licenseUrl' => '',
                    'insuranceUrl' => '',
                    'carLicenseUrl' => '',
                ],
                'createdAt' => $now,
                'updatedAt' => $now,
            ];

            if (!empty($payload['password'])) {
                $fields['password_hash'] = Hash::make((string) $payload['password']);
            }

            return $this->firestore->patchDocumentTyped('drivers', $uid, $fields);
        } catch (\Throwable $e) {
            $this->logFallbackOnce($e, 'DRIVERS');
            return false;
        }
    }

    public function getDriverDocuments(string $driverId): array
    {
        if (!FeatureFlags::driverDocumentsFirestoreEnabled()) {
            throw new \RuntimeException('FIRESTORE_ENABLED=false for driver documents');
        }

        try {
            $doc = $this->firestore->getDocument('drivers', $driverId) ?? [];
            $fields = $this->firestore->decodeDocumentFields($doc);
            return $this->mapDriverDocuments($fields);
        } catch (\Throwable $e) {
            $this->logFallbackOnce($e, 'DRIVERS');
            return [];
        }
    }

    public function listPendingDrivers(): array
    {
        $cacheKey = 'drivers.list.pending';

        if (!FeatureFlags::driversFirestoreEnabled()) {
            throw new \RuntimeException('FIRESTORE_ENABLED=false for drivers');
        }

        if (isset(self::$cache[$cacheKey])) {
            return self::$cache[$cacheKey];
        }

        try {
            $documents = $this->firestore->listDocuments('drivers', 500);
            $mapped = [];

            foreach ($documents as $doc) {
                $fields = $doc;
                if (!$this->isPending($fields)) {
                    continue;
                }
                $row = $this->mapDriver($doc, $fields);
                if (($row['verificationStatus'] ?? '') === '') {
                    $row['verificationStatus'] = 'pending';
                }
                $mapped[] = $row;
            }

            usort($mapped, function ($a, $b) {
                return strcmp($b['created_at'] ?? '', $a['created_at'] ?? '');
            });

            if (env('APP_DEBUG')) {
                $uids = array_slice(array_map(function ($driver) {
                    return $driver['uid'] ?? '';
                }, $mapped), 0, 3);
                logger()->debug('DRIVERS_PENDING_FIRESTORE_QUERY totalDocs=' . count($documents) . ' pending=' . count($mapped) . ' sampleUids=' . implode(',', $uids));
            }

            self::$cache[$cacheKey] = $mapped;
            return $mapped;
        } catch (\Throwable $e) {
            $this->logFallbackOnce($e, 'DRIVERS_PENDING');
            return [];
        }
    }

    public function getDriverById(string $uid): array
    {
        if (!FeatureFlags::driverDocumentsFirestoreEnabled()) {
            throw new \RuntimeException('FIRESTORE_ENABLED=false for driver documents');
        }

        try {
            $doc = $this->firestore->getDocument('drivers', $uid) ?? [];
            $fields = $this->firestore->decodeDocumentFields($doc);
            if (empty($fields)) {
                $documents = $this->firestore->listDocuments('drivers', 500);
                foreach ($documents as $candidate) {
                    $candidateFields = $candidate;
                    if (($candidateFields['uid'] ?? '') === $uid) {
                        $fields = $candidateFields;
                        break;
                    }
                }
            }
            if (empty($fields) && env('APP_DEBUG')) {
                logger()->debug('DRIVER_DOCUMENTS uid=' . $uid . ' empty=1');
            }
            return $fields;
        } catch (\Throwable $e) {
            $this->logFallbackOnce($e, 'DRIVER_DOCUMENTS');
            return [];
        }
    }

    public function getDriverDocumentsLinks(array $driver): array
    {
        if (empty($driver)) {
            return $this->withLinkFlags([]);
        }

        $docMap = is_array($driver['documents'] ?? null) ? $driver['documents'] : [];
        $car = is_array($driver['car'] ?? null) ? $driver['car'] : [];
        $carImages = is_array($car['images'] ?? null) ? $car['images'] : [];

        $items = [
            ['label' => 'profile', 'url' => $driver['profileImageUrl'] ?? $driver['profileImage'] ?? $driver['avatarUrl'] ?? ''],
            ['label' => 'license', 'url' => $driver['licenseUrl'] ?? ($docMap['licenseUrl'] ?? '')],
            ['label' => 'insurance', 'url' => $docMap['insuranceUrl'] ?? ($driver['insuranceUrl'] ?? '')],
            ['label' => 'car_license', 'url' => $driver['carLicenseUrl'] ?? ($docMap['carLicenseUrl'] ?? '')],
            ['label' => 'car_image', 'url' => $driver['carImageUrl'] ?? ($carImages[0] ?? '')],
        ];

        if (env('APP_DEBUG')) {
            $uid = $driver['uid'] ?? $driver['__id'] ?? '';
            $flags = [];
            foreach ($items as $item) {
                $flags[] = $item['label'] . '=' . (!empty($item['url']) ? '1' : '0');
            }
            logger()->debug('DRIVER_DOCUMENTS uid=' . $uid . ' ' . implode(' ', $flags));
        }

        return $this->withLinkFlags($items);
    }

    public function getDriverDocumentLinks(array $driver): array
    {
        return $this->getDriverDocumentsLinks($driver);
    }

    private function withLinkFlags(array $items): array
    {
        $out = [];
        foreach ($items as $item) {
            $url = (string) ($item['url'] ?? '');
            $out[] = [
                'label' => (string) ($item['label'] ?? ''),
                'url' => $url,
                'has' => $url !== '',
            ];
        }
        return $out;
    }

    private function mapDriver(array $doc, array $fields): array
    {
        $createdAt = $fields['createdAt'] ?? $fields['created_at'] ?? $fields['created'] ?? null;
        $updatedAt = $fields['updatedAt'] ?? $fields['updated_at'] ?? ($doc['_updateTime'] ?? null);
        $lastSeenAt = $fields['lastSeenAt'] ?? $fields['last_seen_at'] ?? null;

        $availability = is_array($fields['availability'] ?? null) ? $fields['availability'] : [];

        return [
            'id' => $fields['uid'] ?? ($doc['_docId'] ?? ''),
            'uid' => $fields['uid'] ?? ($doc['_docId'] ?? ''),
            'name' => $fields['name'] ?? trim(($fields['first_name'] ?? '') . ' ' . ($fields['last_name'] ?? '')),
            'email' => $fields['email'] ?? '',
            'phone' => $fields['phone'] ?? '',
            'cityId' => $fields['cityId'] ?? ($fields['city_id'] ?? ($fields['cityKey'] ?? '')),
            'verificationStatus' => $fields['verificationStatus'] ?? ($fields['verification_status'] ?? ''),
            'isOnline' => $availability['online'] ?? ($fields['isOnline'] ?? false),
            'isAvailable' => $availability['available'] ?? ($fields['isAvailable'] ?? false),
            'lastSeenAt' => $this->formatTimestamp($lastSeenAt ?? $updatedAt),
            'created_at' => $this->formatTimestamp($createdAt ?? $updatedAt),
        ];
    }

    private function mapDriverDocuments(array $doc): array
    {
        $documents = [];
        $docMap = is_array($doc['documents'] ?? null) ? $doc['documents'] : [];

        $candidates = [
            'licenseUrl' => $docMap['licenseUrl'] ?? ($doc['licenseUrl'] ?? ''),
            'insuranceUrl' => $docMap['insuranceUrl'] ?? ($doc['insuranceUrl'] ?? ''),
            'carLicenseUrl' => $docMap['carLicenseUrl'] ?? ($doc['carLicenseUrl'] ?? ''),
            'profileImageUrl' => $doc['profileImageUrl'] ?? '',
            'avatarUrl' => $doc['avatarUrl'] ?? '',
            'carImageUrl' => $doc['carImageUrl'] ?? '',
        ];

        foreach ($candidates as $label => $url) {
            if (!empty($url)) {
                $documents[] = ['label' => $label, 'url' => $url];
            }
        }

        if (isset($doc['car']['images']) && is_array($doc['car']['images'])) {
            foreach ($doc['car']['images'] as $idx => $url) {
                if (!empty($url)) {
                    $documents[] = ['label' => 'carImage' . ($idx + 1), 'url' => $url];
                }
            }
        }

        return $documents;
    }

    private function isPending(array $doc): bool
    {
        $status = strtolower((string) ($doc['verificationStatus'] ?? $doc['verification_status'] ?? $doc['status'] ?? ''));
        if ($status === 'pending') {
            return true;
        }
        if (in_array($status, ['waiting', 'in_review', 'review', 'submitted', 'rejected'], true)) {
            return true;
        }
        return $status === '' || $status !== 'approved';
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

    private function logFallbackOnce(\Throwable $e, string $feature): void
    {
        if (isset(self::$logged[$feature])) {
            return;
        }

        $reason = stripos($e->getMessage(), 'timeout') !== false ? 'timeout' : 'exception';
        logger()->warning('FIRESTORE_FALLBACK feature=' . $feature . ' reason=' . $reason . ' message=' . $e->getMessage());
        self::$logged[$feature] = true;
    }
}

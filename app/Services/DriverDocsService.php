<?php

namespace App\Services;

use App\Support\FeatureFlags;
use Carbon\Carbon;

class DriverDocsService
{
    private FirestoreRestService $firestore;
    private static array $cache = [];
    private static array $logged = [];

    public function __construct(FirestoreRestService $firestore)
    {
        $this->firestore = $firestore;
    }

    public function listDriverDocs(): array
    {
        $mock = config('mock_data.driver_docs', []);
        if (!FeatureFlags::shouldUseFirestore('DRIVERDOCS')) {
            return is_array($mock) ? $mock : [];
        }

        if (isset(self::$cache['driverdocs.list'])) {
            return self::$cache['driverdocs.list'];
        }

        try {
            $documents = $this->firestore->listDocuments('drivers', 300);
            $mapped = [];

            foreach ($documents as $doc) {
                $mapped[] = $this->mapDriverDocs($doc, $doc);
            }

            usort($mapped, function ($a, $b) {
                return strcmp($b['created_at'] ?? '', $a['created_at'] ?? '');
            });

            if (env('APP_DEBUG')) {
                logger()->debug('DRIVERDOCS_FIRESTORE_QUERY count=' . count($mapped));
            }

            self::$cache['driverdocs.list'] = $mapped;
            return $mapped;
        } catch (\Throwable $e) {
            $this->logFallbackOnce($e);
            return is_array($mock) ? $mock : [];
        }
    }

    private function mapDriverDocs(array $doc, array $raw = []): array
    {
        $docMap = is_array($doc['documents'] ?? null) ? $doc['documents'] : [];
        $car = is_array($doc['car'] ?? null) ? $doc['car'] : [];
        $carImages = is_array($car['images'] ?? null) ? $car['images'] : [];

        return [
            'uid' => $doc['uid'] ?? ($raw['_docId'] ?? ''),
            'name' => $doc['name'] ?? trim(($doc['first_name'] ?? '') . ' ' . ($doc['last_name'] ?? '')),
            'email' => $doc['email'] ?? '',
            'phone' => $doc['phone'] ?? '',
            'verificationStatus' => $doc['verificationStatus'] ?? '',
            'created_at' => $this->formatTimestamp($doc['createdAt'] ?? $doc['created_at'] ?? ($raw['_updateTime'] ?? null)),
            'docs' => [
                'profile' => $doc['profileImageUrl'] ?? $doc['profileImage'] ?? $doc['avatarUrl'] ?? '',
                'license' => $doc['licenseUrl'] ?? ($docMap['licenseUrl'] ?? ''),
                'insurance' => $docMap['insuranceUrl'] ?? ($doc['insuranceUrl'] ?? ''),
                'car_license' => $docMap['carLicenseUrl'] ?? ($doc['carLicenseUrl'] ?? ''),
                'car_image' => $doc['carImageUrl'] ?? ($carImages[0] ?? ''),
            ],
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
        if (isset(self::$logged['DRIVERDOCS'])) {
            return;
        }

        $reason = stripos($e->getMessage(), 'timeout') !== false ? 'timeout' : 'exception';
        logger()->warning('FIRESTORE_FALLBACK feature=DRIVERDOCS reason=' . $reason . ' message=' . $e->getMessage());
        self::$logged['DRIVERDOCS'] = true;
    }
}

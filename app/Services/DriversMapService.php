<?php

namespace App\Services;

use App\Support\FeatureFlags;

class DriversMapService
{
    private FirestoreRestService $firestore;
    private static array $logged = [];

    public function __construct(FirestoreRestService $firestore)
    {
        $this->firestore = $firestore;
    }

    public function listOnlineDrivers(int $limit = 500): array
    {
        if (!FeatureFlags::firestoreEnabled()) {
            throw new \RuntimeException('FIRESTORE_ENABLED=false for drivers map');
        }

        try {
            $activeDocs = $this->firestore->listDocuments('drivers_active', $limit);
            $driverDocs = $this->firestore->listDocuments('drivers', $limit);

            $markers = [];
            $seen = [];

            foreach ($activeDocs as $doc) {
                $marker = $this->mapDriver($doc, $doc);
                if (!$marker) {
                    continue;
                }
                $uid = $marker['id'];
                $seen[$uid] = true;
                $markers[] = $marker;
            }

            foreach ($driverDocs as $doc) {
                $marker = $this->mapDriver($doc, $doc);
                if (!$marker) {
                    continue;
                }
                $uid = $marker['id'];
                if (isset($seen[$uid])) {
                    continue;
                }
                $seen[$uid] = true;
                $markers[] = $marker;
            }

            if (env('APP_DEBUG')) {
                logger()->debug('DRIVERS_MAP total_active=' . count($activeDocs) . ' total_drivers=' . count($driverDocs) . ' online=' . count($markers));
            }

            return $markers;
        } catch (\Throwable $e) {
            $this->logFallbackOnce($e);
            return [];
        }
    }

    public function getDriverProfile(string $uid): array
    {
        if ($uid === '' || !FeatureFlags::firestoreEnabled()) {
            throw new \RuntimeException('FIRESTORE_ENABLED=false for drivers map');
        }

        try {
            $doc = $this->firestore->getDocument('drivers', $uid);
            $fields = $this->firestore->decodeDocumentFields($doc ?? []);
            if (empty($fields)) {
                return [];
            }

            $name = $fields['name'] ?? $fields['driverName'] ?? $fields['display_name'] ?? '';
            if ($name === '' && (isset($fields['first_name']) || isset($fields['last_name']))) {
                $name = trim(($fields['first_name'] ?? '') . ' ' . ($fields['last_name'] ?? ''));
            }

            return [
                'id' => $uid,
                'display_name' => $name !== '' ? $name : $uid,
                'contact_number' => $fields['phone'] ?? $fields['contact_number'] ?? '-',
                'last_location_update_at' => $fields['lastLocationUpdateAt'] ?? $fields['last_location_update_at'] ?? '',
            ];
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function mapDriver(array $fields, array $raw): ?array
    {
        $uid = (string) ($fields['driverUid'] ?? $fields['uid'] ?? ($raw['_docId'] ?? ''));
        if ($uid === '') {
            return null;
        }

        $isOnline = $this->isTruthy($fields['isOnline'] ?? ($fields['online'] ?? ($fields['availability']['online'] ?? false)));
        if (!$isOnline) {
            return null;
        }

        $coords = $this->extractCoordinates($fields);
        if (!$coords) {
            return null;
        }

        $name = $fields['driverName'] ?? $fields['name'] ?? $fields['display_name'] ?? '';
        if ($name === '' && (isset($fields['first_name']) || isset($fields['last_name']))) {
            $name = trim(($fields['first_name'] ?? '') . ' ' . ($fields['last_name'] ?? ''));
        }

        $isAvailable = $this->isTruthy($fields['isAvailable'] ?? ($fields['availability']['available'] ?? false));

        return [
            'id' => $uid,
            'display_name' => $name !== '' ? $name : $uid,
            'contact_number' => $fields['phone'] ?? $fields['contact_number'] ?? '-',
            'latitude' => $coords['lat'],
            'longitude' => $coords['lng'],
            'is_online' => true,
            'is_available' => $isAvailable,
        ];
    }

    private function extractCoordinates(array $fields): ?array
    {
        $lat = $fields['lat'] ?? $fields['latitude'] ?? $fields['lastLat'] ?? $fields['last_lat'] ?? null;
        $lng = $fields['lng'] ?? $fields['longitude'] ?? $fields['lastLng'] ?? $fields['last_lng'] ?? null;

        if ($lat !== null && $lng !== null) {
            return [
                'lat' => (float) $lat,
                'lng' => (float) $lng,
            ];
        }

        $lastLocation = $fields['lastLocation'] ?? $fields['last_location'] ?? null;
        if (is_array($lastLocation)) {
            $lat = $lastLocation['lat'] ?? $lastLocation['latitude'] ?? null;
            $lng = $lastLocation['lng'] ?? $lastLocation['longitude'] ?? null;
            if ($lat !== null && $lng !== null) {
                return [
                    'lat' => (float) $lat,
                    'lng' => (float) $lng,
                ];
            }
        }

        return null;
    }

    private function isTruthy($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_numeric($value)) {
            return (int) $value === 1;
        }
        if (is_string($value)) {
            return in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true);
        }
        return false;
    }

    private function logFallbackOnce(\Throwable $e): void
    {
        if (isset(self::$logged['DRIVERS_MAP'])) {
            return;
        }
        $reason = stripos($e->getMessage(), 'timeout') !== false ? 'timeout' : 'exception';
        logger()->warning('FIRESTORE_FALLBACK feature=DRIVERS_MAP reason=' . $reason . ' message=' . $e->getMessage());
        self::$logged['DRIVERS_MAP'] = true;
    }
}

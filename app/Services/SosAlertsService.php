<?php

namespace App\Services;

use Carbon\Carbon;
use App\Services\GeoCoderService;

class SosAlertsService
{
    private FirestoreRestService $firestore;
    private static array $cache = [];

    public function __construct(FirestoreRestService $firestore)
    {
        $this->firestore = $firestore;
    }

    public function listAlerts(int $limit = 100): array
    {
        if (!$this->shouldUseFirestore()) {
            return [];
        }

        $docs = $this->firestore->listDocuments('sos_alerts', $limit);
        $rows = [];

        foreach ($docs as $doc) {
            $coords = $this->extractCoords($doc);
            $createdAt = $doc['createdAt'] ?? $doc['created_at'] ?? ($doc['_updateTime'] ?? null);
            $triggeredBy = strtolower((string) ($doc['triggeredBy'] ?? ''));
            $riderUid = (string) ($doc['riderUid'] ?? '');
            $driverUid = (string) ($doc['driverUid'] ?? '');
            $actorUid = $triggeredBy === 'driver' ? $driverUid : $riderUid;

            $rows[] = [
                'id' => $this->scalarize($doc['_docId'] ?? ''),
                'ride_id' => $this->scalarize($doc['rideId'] ?? ''),
                'status' => $this->scalarize($doc['status'] ?? ''),
                'triggered_by' => $this->scalarize($triggeredBy),
                'rider_uid' => $this->scalarize($riderUid),
                'driver_uid' => $this->scalarize($driverUid),
                'actor_uid' => $this->scalarize($actorUid),
                'lat' => $coords['lat'],
                'lng' => $coords['lng'],
                'created_at' => $this->formatTimestamp($createdAt),
                'created_at_raw' => is_scalar($createdAt) ? $createdAt : null,
            ];
        }

        usort($rows, function ($a, $b) {
            return strcmp($b['created_at'] ?? '', $a['created_at'] ?? '');
        });

        return $rows;
    }

    public function enrichRows(array $rows): array
    {
        if (count($rows) === 0) {
            return [];
        }

        $riderUids = [];
        $driverUids = [];
        $rideIds = [];

        foreach ($rows as $row) {
            if (!empty($row['ride_id'])) {
                $rideIds[] = $row['ride_id'];
            }
            if (($row['triggered_by'] ?? '') === 'driver' && !empty($row['actor_uid'])) {
                $driverUids[] = $row['actor_uid'];
            } elseif (!empty($row['actor_uid'])) {
                $riderUids[] = $row['actor_uid'];
            }
        }

        $actors = $this->prefetchActors($riderUids, $driverUids);
        $rides = $this->prefetchRides($rideIds);
        $requests = $this->prefetchRideRequests($rideIds);

        $output = [];
        foreach ($rows as $row) {
            $actor = [];
            if (($row['triggered_by'] ?? '') === 'driver') {
                $actor = $actors['drivers'][$row['actor_uid']] ?? [];
            } else {
                $actor = $actors['riders'][$row['actor_uid']] ?? [];
            }

            $name = $this->pickName($actor);
            $phone = $this->pickPhone($actor);
            if ($name === '') {
                $name = $row['actor_uid'] ?? '-';
            }
            if ($phone === '') {
                $phone = '-';
            }

            $lat = (string) ($row['lat'] ?? '');
            $lng = (string) ($row['lng'] ?? '');
            $address = $this->resolveAddress((string) ($row['ride_id'] ?? ''), $rides, $requests, $lat, $lng);
            $geo = app(GeoCoderService::class)->reverseGeocode($lat, $lng);
            $locationText = $geo ?? ($lat !== '' && $lng !== '' ? $this->coordString($lat, $lng) : '-');
            $mapUrl = $lat !== '' && $lng !== '' ? $this->mapUrl($lat, $lng) : '';

            $titleAddress = $address !== '' ? $address : ($row['ride_id'] ?? '');
            if ($titleAddress === '' && $lat !== '' && $lng !== '') {
                $titleAddress = $this->coordString($lat, $lng);
            }
            if ($titleAddress === '') {
                $titleAddress = '-';
            }

            $output[] = [
                'id' => $this->scalarize($row['id'] ?? ''),
                'ride_id' => $this->scalarize($row['ride_id'] ?? ''),
                'title_address' => $this->scalarize($titleAddress),
                'actor_name' => $this->scalarize($name),
                'actor_phone' => $this->scalarize($phone),
                'location_text' => $this->scalarize($locationText),
                'map_url' => $this->scalarize($mapUrl),
                'created_at' => $this->scalarize($row['created_at'] ?? ''),
                'status' => $this->scalarize($row['status'] ?? ''),
            ];
        }

        return $output;
    }

    private function prefetchActors(array $riderUids, array $driverUids): array
    {
        $out = ['riders' => [], 'drivers' => []];
        $riderUids = array_unique(array_filter($riderUids));
        $driverUids = array_unique(array_filter($driverUids));

        foreach ($riderUids as $uid) {
            if (isset(self::$cache['rider'][$uid])) {
                $out['riders'][$uid] = self::$cache['rider'][$uid];
                continue;
            }
            $doc = $this->firestore->getDocumentFields('users', $uid) ?? [];
            self::$cache['rider'][$uid] = $doc;
            $out['riders'][$uid] = $doc;
        }

        foreach ($driverUids as $uid) {
            if (isset(self::$cache['driver'][$uid])) {
                $out['drivers'][$uid] = self::$cache['driver'][$uid];
                continue;
            }
            $doc = $this->firestore->getDocumentFields('drivers', $uid) ?? [];
            self::$cache['driver'][$uid] = $doc;
            $out['drivers'][$uid] = $doc;
        }

        return $out;
    }

    private function prefetchRides(array $rideIds): array
    {
        $rides = [];
        $rideIds = array_unique(array_filter($rideIds));
        foreach ($rideIds as $rideId) {
            if (isset(self::$cache['ride'][$rideId])) {
                $rides[$rideId] = self::$cache['ride'][$rideId];
                continue;
            }
            $doc = $this->firestore->getDocumentFields('rides', $rideId) ?? [];
            self::$cache['ride'][$rideId] = $doc;
            if (!empty($doc)) {
                $rides[$rideId] = $doc;
            }
        }
        return $rides;
    }

    private function prefetchRideRequests(array $rideIds): array
    {
        $requests = [];
        $rideIds = array_unique(array_filter($rideIds));
        $docs = $this->firestore->listDocuments('ride_requests', 300);
        $indexed = [];
        foreach ($docs as $doc) {
            $key = (string) ($doc['rideId'] ?? '');
            if ($key !== '') {
                $indexed[$key] = $doc;
            }
        }

        foreach ($rideIds as $rideId) {
            if (isset(self::$cache['request'][$rideId])) {
                $requests[$rideId] = self::$cache['request'][$rideId];
                continue;
            }
            $doc = $indexed[$rideId] ?? [];
            self::$cache['request'][$rideId] = $doc;
            if (!empty($doc)) {
                $requests[$rideId] = $doc;
            }
        }

        return $requests;
    }

    private function resolveAddress(string $rideId, array $rides, array $requests, $lat, $lng): string
    {
        if ($rideId !== '' && isset($rides[$rideId])) {
            $ride = $rides[$rideId];
            $start = is_array($ride['start'] ?? null) ? $ride['start'] : [];
            $end = is_array($ride['end'] ?? null) ? $ride['end'] : [];
            $pickup = $start['address'] ?? '';
            $dropoff = $end['address'] ?? '';
            if ($pickup !== '' && $dropoff !== '') {
                return $pickup . ' → ' . $dropoff;
            }
            return $pickup ?: $dropoff;
        }

        if ($rideId !== '' && isset($requests[$rideId])) {
            $req = $requests[$rideId];
            $pickup = $req['pickupAddress'] ?? '';
            $dropoff = $req['dropoffAddress'] ?? '';
            if ($pickup !== '' && $dropoff !== '') {
                return $pickup . ' → ' . $dropoff;
            }
            return $pickup ?: $dropoff;
        }

        $coord = trim($this->scalarize($lat) . ',' . $this->scalarize($lng), ',');
        return $coord !== '' ? $coord : '-';
    }

    private function coordString($lat, $lng): string
    {
        return trim($this->scalarize($lat) . ',' . $this->scalarize($lng), ',');
    }

    private function mapUrl($lat, $lng): string
    {
        $coord = $this->coordString($lat, $lng);
        if ($coord === '') {
            return '';
        }
        return 'https://www.google.com/maps?q=' . $coord;
    }

    private function extractCoords(array $doc): array
    {
        $location = is_array($doc['location'] ?? null) ? $doc['location'] : [];
        $lat = $location['lat'] ?? $location['latitude'] ?? ($doc['lat'] ?? ($doc['latitude'] ?? ''));
        $lng = $location['lng'] ?? $location['longitude'] ?? ($doc['lng'] ?? ($doc['longitude'] ?? ''));

        if ($lat !== '' && $lng !== '') {
            return [
                'lat' => (string) $lat,
                'lng' => (string) $lng,
            ];
        }

        return [
            'lat' => '',
            'lng' => '',
        ];
    }

    private function shouldUseFirestore(): bool
    {
        $flag = env('FF_SOS_FIRESTORE');
        if ($flag === null) {
            return \App\Support\FeatureFlags::firestoreEnabled();
        }
        return \App\Support\FeatureFlags::firestoreEnabled() && (bool) $flag;
    }

    private function pickName(array $actor): string
    {
        if (isset($actor['name']) && $actor['name'] !== '') {
            return (string) $actor['name'];
        }
        $profile = is_array($actor['profileExtra'] ?? null) ? $actor['profileExtra'] : [];
        if (isset($profile['username']) && $profile['username'] !== '') {
            return (string) $profile['username'];
        }
        if (isset($actor['username']) && $actor['username'] !== '') {
            return (string) $actor['username'];
        }
        if (isset($actor['email']) && $actor['email'] !== '') {
            return (string) $actor['email'];
        }
        return '';
    }

    private function pickPhone(array $actor): string
    {
        if (isset($actor['phone']) && $actor['phone'] !== '') {
            return (string) $actor['phone'];
        }
        if (isset($actor['contactNumber']) && $actor['contactNumber'] !== '') {
            return (string) $actor['contactNumber'];
        }
        if (isset($actor['mobile']) && $actor['mobile'] !== '') {
            return (string) $actor['mobile'];
        }
        return '';
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

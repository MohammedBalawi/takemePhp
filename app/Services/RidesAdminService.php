<?php

namespace App\Services;

use App\Support\FeatureFlags;
use Carbon\Carbon;

class RidesAdminService
{
    private FirestoreRestService $firestore;
    private static array $cache = [];
    private static array $logged = [];

    public function __construct(FirestoreRestService $firestore)
    {
        $this->firestore = $firestore;
    }

    public function listAll(): array
    {
        return $this->buildList('all');
    }

    public function listCompleted(): array
    {
        return $this->buildList('completed');
    }

    public function listTodayNew(): array
    {
        return $this->buildList('today');
    }

    public function listCancelled(): array
    {
        return $this->buildList('cancelled');
    }

    public function listInProgress(): array
    {
        return $this->buildList('inprogress');
    }

    public function getDetails(string $id, string $source): array
    {
        if (!FeatureFlags::ridesFirestoreEnabled()) {
            return [];
        }

        try {
            if ($source === 'ride_requests') {
                return $this->firestore->getDocumentFields('ride_requests', $id) ?? [];
            }
            return $this->firestore->getDocumentFields('rides', $id) ?? [];
        } catch (\Throwable $e) {
            $this->logFallbackOnce($e);
            return [];
        }
    }

    private function buildList(string $type): array
    {
        $mock = config('mock_data.mock_ride_rows', []);
        if (!FeatureFlags::ridesFirestoreEnabled()) {
            return $this->applyFilter(is_array($mock) ? $mock : [], $type);
        }

        if (isset(self::$cache['rides.' . $type])) {
            return self::$cache['rides.' . $type];
        }

        try {
            $rides = $this->firestore->listDocuments('rides', 200);
            $requests = $this->firestore->listDocuments('ride_requests', 200);

            if (env('APP_DEBUG')) {
                logger()->debug('RIDES_FIRESTORE_QUERY rides_count=' . count($rides) . ' requests_count=' . count($requests));
            }

            $rows = $this->mergeRows($rides, $requests);
            $rows = $this->applyFilter($rows, $type);

            if (env('APP_DEBUG')) {
                logger()->debug('RIDES_MERGE_RESULT count=' . count($rows));
            }

            self::$cache['rides.' . $type] = $rows;
            return $rows;
        } catch (\Throwable $e) {
            $this->logFallbackOnce($e);
            return $this->applyFilter(is_array($mock) ? $mock : [], $type);
        }
    }

    private function mergeRows(array $rides, array $requests): array
    {
        $rows = [];
        $seen = [];

        foreach ($rides as $doc) {
            $row = $this->mapRide($doc, $doc, 'rides');
            if ($row['id'] !== '') {
                $seen[$row['id']] = true;
            }
            $rows[] = $row;
        }

        foreach ($requests as $doc) {
            $row = $this->mapRideRequest($doc, $doc);
            if ($row['id'] !== '' && isset($seen[$row['id']])) {
                continue;
            }
            $rows[] = $row;
        }

        usort($rows, function ($a, $b) {
            return strcmp($b['created_at'] ?? '', $a['created_at'] ?? '');
        });

        return $rows;
    }

    private function applyFilter(array $rows, string $type): array
    {
        if ($type === 'all') {
            return $rows;
        }

        $todayStart = Carbon::today();
        $todayEnd = Carbon::today()->endOfDay();

        return array_values(array_filter($rows, function ($row) use ($type, $todayStart, $todayEnd) {
            $status = strtolower((string) ($row['status'] ?? ''));

            switch ($type) {
                case 'completed':
                    return $status === 'completed';
                case 'cancelled':
                    return in_array($status, ['cancelled', 'canceled', 'declined', 'expired'], true);
                case 'inprogress':
                    return in_array($status, ['accepted', 'arrived', 'started', 'onride', 'in_progress', 'searching', 'ongoing'], true);
                case 'today':
                    $createdAt = $row['created_at_raw'] ?? null;
                    if (!$createdAt) {
                        return false;
                    }
                    try {
                        $ts = Carbon::parse($createdAt);
                        return $ts->betweenIncluded($todayStart, $todayEnd);
                    } catch (\Throwable $e) {
                        return false;
                    }
                default:
                    return true;
            }
        }));
    }

    private function mapRide(array $doc, array $raw, string $source): array
    {
        $createdAt = $doc['createdAt'] ?? $doc['created_at'] ?? ($raw['_updateTime'] ?? null);
        $status = $doc['status'] ?? ($doc['status_simple'] ?? '');
        $pricing = is_array($doc['pricing'] ?? null) ? $doc['pricing'] : [];
        $fare = is_array($doc['fare'] ?? null) ? $doc['fare'] : [];
        $start = is_array($doc['start'] ?? null) ? $doc['start'] : [];
        $end = is_array($doc['end'] ?? null) ? $doc['end'] : [];
        $payment = is_array($doc['payment'] ?? null) ? $doc['payment'] : [];

        $total = $pricing['total'] ?? ($fare['total'] ?? ($doc['fare'] ?? ($doc['pricingTotal'] ?? null)));
        $currency = $pricing['currency'] ?? ($fare['currency'] ?? ($doc['currency'] ?? ''));
        $pickupAddress = $start['address'] ?? ($doc['pickupAddress'] ?? ($doc['start_address'] ?? ''));
        $dropoffAddress = $end['address'] ?? ($doc['dropoffAddress'] ?? ($doc['end_address'] ?? ''));
        $pickupLat = $start['lat'] ?? ($start['latitude'] ?? ($doc['pickupLat'] ?? ($doc['start_lat'] ?? null)));
        $pickupLng = $start['lng'] ?? ($start['longitude'] ?? ($doc['pickupLng'] ?? ($doc['start_lng'] ?? null)));
        $dropoffLat = $end['lat'] ?? ($end['latitude'] ?? ($doc['dropoffLat'] ?? ($doc['end_lat'] ?? null)));
        $dropoffLng = $end['lng'] ?? ($end['longitude'] ?? ($doc['dropoffLng'] ?? ($doc['end_lng'] ?? null)));

        return [
            'id' => $this->scalarize($doc['_docId'] ?? ($doc['rideId'] ?? '')),
            'source' => $source,
            'created_at' => $this->formatTimestamp($createdAt),
            'created_at_raw' => is_scalar($createdAt) ? $createdAt : null,
            'status' => strtolower((string) $status),
            'rider_uid' => $this->scalarize($doc['riderUid'] ?? ($doc['rider_id'] ?? '')),
            'driver_uid' => $this->scalarize($doc['driverUid'] ?? ($doc['driver_id'] ?? '')),
            'city_id' => $this->scalarize($doc['cityId'] ?? ($doc['city_id'] ?? '')),
            'pickup_address' => $this->scalarize($pickupAddress),
            'dropoff_address' => $this->scalarize($dropoffAddress),
            'pickup_lat' => $this->scalarize($pickupLat),
            'pickup_lng' => $this->scalarize($pickupLng),
            'dropoff_lat' => $this->scalarize($dropoffLat),
            'dropoff_lng' => $this->scalarize($dropoffLng),
            'fare_total' => is_numeric($total) ? $total : $this->scalarize($total),
            'currency' => $this->scalarize($currency),
            'payment_status' => $this->scalarize($payment['status'] ?? ($payment['payment_status'] ?? '')),
            'payment_method' => $this->scalarize($payment['method'] ?? ($payment['payment_method'] ?? '')),
        ];
    }

    private function mapRideRequest(array $doc, array $raw): array
    {
        $createdAt = $doc['createdAt'] ?? $doc['created_at'] ?? ($raw['_updateTime'] ?? null);
        $status = $doc['status'] ?? '';
        $pricingTotal = $doc['pricingTotal'] ?? $doc['fare'] ?? null;
        $currency = $doc['currency'] ?? '';

        return [
            'id' => $this->scalarize($doc['_docId'] ?? ($doc['rideId'] ?? ($doc['requestId'] ?? ''))),
            'source' => 'ride_requests',
            'created_at' => $this->formatTimestamp($createdAt),
            'created_at_raw' => is_scalar($createdAt) ? $createdAt : null,
            'status' => strtolower((string) $status),
            'rider_uid' => $this->scalarize($doc['riderUid'] ?? ($doc['rider_id'] ?? '')),
            'driver_uid' => $this->scalarize($doc['driverUid'] ?? ($doc['driver_id'] ?? '')),
            'city_id' => $this->scalarize($doc['cityId'] ?? ($doc['city_id'] ?? '')),
            'pickup_address' => $this->scalarize($doc['pickupAddress'] ?? ''),
            'dropoff_address' => $this->scalarize($doc['dropoffAddress'] ?? ''),
            'pickup_lat' => $this->scalarize($doc['pickupLat'] ?? ($doc['start_lat'] ?? null)),
            'pickup_lng' => $this->scalarize($doc['pickupLng'] ?? ($doc['start_lng'] ?? null)),
            'dropoff_lat' => $this->scalarize($doc['dropoffLat'] ?? ($doc['end_lat'] ?? null)),
            'dropoff_lng' => $this->scalarize($doc['dropoffLng'] ?? ($doc['end_lng'] ?? null)),
            'fare_total' => is_numeric($pricingTotal) ? $pricingTotal : $this->scalarize($pricingTotal),
            'currency' => $this->scalarize($currency),
            'payment_status' => '',
            'payment_method' => '',
        ];
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

    private function logFallbackOnce(\Throwable $e): void
    {
        if (isset(self::$logged['RIDES'])) {
            return;
        }

        $reason = stripos($e->getMessage(), 'timeout') !== false ? 'timeout' : 'exception';
        logger()->warning('FIRESTORE_FALLBACK feature=RIDES reason=' . $reason . ' message=' . $e->getMessage());
        self::$logged['RIDES'] = true;
    }
}

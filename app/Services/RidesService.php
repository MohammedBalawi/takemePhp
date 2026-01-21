<?php

namespace App\Services;

use App\Support\FeatureFlags;
use Carbon\Carbon;

class RidesService
{
    private FirestoreRestService $firestore;
    private static array $cache = [];
    private static array $logged = [];

    public function __construct(FirestoreRestService $firestore)
    {
        $this->firestore = $firestore;
    }

    public function listByKey(string $key): array
    {
        switch ($key) {
            case 'new-request':
                return $this->listRideRequestsByStatuses(['pending', 'requested', 'new']);
            case 'accepted':
                return $this->listRideRequestsByStatuses(['accepted']);
            case 'offer-submitted':
                return $this->listRideRequestsByStatuses(['offered', 'offer_sent', 'bid_submitted']);
            case 'offer-accepted':
                return $this->listRideRequestsByStatuses(['offer_accepted', 'accepted_offer']);
            case 'offer-declined':
                return $this->listRideRequestsByStatuses(['declined', 'rejected']);
            case 'enroute':
                return $this->listRidesByStatuses(['enroute', 'on_the_way']);
            case 'arrived':
                return $this->listRidesByStatuses(['arrived']);
            case 'inprogress':
                return $this->listInProgress();
            case 'cancelled-driver':
                return $this->listRidesByStatuses(['cancelled_by_driver', 'driver_cancelled']);
            case 'cancelled-rider':
                return $this->listRidesByStatuses(['cancelled_by_rider', 'rider_cancelled']);
            case 'completed':
                return $this->listCompleted();
            case 'payment-status':
                return $this->listPayments(request('payment_type'));
            case 'new-today':
                return $this->listNewToday();
            case 'cancelled':
                return $this->listCancelled();
            case 'all':
            default:
                return $this->listAll();
        }
    }

    public function listAll(): array
    {
        return $this->buildList('all');
    }

    public function listCompleted(): array
    {
        return $this->buildList('completed');
    }

    public function listInProgress(): array
    {
        return $this->buildList('inprogress');
    }

    public function listCancelled(): array
    {
        return $this->buildList('cancelled');
    }

    public function listNewToday(): array
    {
        return $this->buildList('today');
    }

    private function buildList(string $type): array
    {
        if (!FeatureFlags::ridesFirestoreEnabled()) {
            throw new \RuntimeException('FIRESTORE_ENABLED=false for rides');
        }

        if (isset(self::$cache['rides.' . $type])) {
            return self::$cache['rides.' . $type];
        }

        try {
            $rides = $this->firestore->listDocuments('rides', 200);
            $requests = $this->firestore->listDocuments('ride_requests', 200);

            $rows = $this->mergeRows($rides, $requests);
            $rows = $this->applyFilter($rows, $type);

            if (env('APP_DEBUG')) {
                logger()->debug('RIDES_FIRESTORE_AGG rides=' . count($rides) . ' reqs=' . count($requests) . ' merged=' . count($rows) . ' key=' . $type);
            }

            self::$cache['rides.' . $type] = $rows;
            return $rows;
        } catch (\Throwable $e) {
            $this->logFallbackOnce($e);
            return [];
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
            if (!empty($row['ride_id'])) {
                $seen[$row['ride_id']] = true;
            }
            $rows[] = $row;
        }

        foreach ($requests as $doc) {
            $row = $this->mapRideRequest($doc, $doc);
            if (!empty($row['ride_id']) && isset($seen[$row['ride_id']])) {
                continue;
            }
            if ($row['id'] !== '' && isset($seen[$row['id']])) {
                continue;
            }
            $rows[] = $row;
        }

        usort($rows, function ($a, $b) {
            return strcmp($b['createdAt'] ?? '', $a['createdAt'] ?? '');
        });

        return $rows;
    }

    private function applyFilter(array $rows, string $type): array
    {
        $todayStart = Carbon::today();
        $todayEnd = Carbon::today()->endOfDay();

        return array_values(array_filter($rows, function ($row) use ($type, $todayStart, $todayEnd) {
            $status = strtolower((string) ($row['status'] ?? ''));
            switch ($type) {
                case 'completed':
                    return $status === 'completed';
                case 'cancelled':
                    return in_array($status, ['cancelled', 'canceled', 'declined', 'expired', 'rejected'], true);
                case 'inprogress':
                    return in_array($status, ['accepted', 'assigned', 'arrived', 'started', 'in_progress', 'onride', 'ongoing', 'searching'], true);
                case 'today':
                    $createdAtRaw = $row['created_at_raw'] ?? null;
                    if (!$createdAtRaw) {
                        return false;
                    }
                    try {
                        $ts = Carbon::parse($createdAtRaw);
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
        $pricingSnapshot = is_array($doc['pricingSnapshot'] ?? null) ? $doc['pricingSnapshot'] : [];
        $pricingBreakdown = is_array($pricing['breakdown'] ?? null) ? $pricing['breakdown'] : [];
        $start = is_array($doc['start'] ?? null) ? $doc['start'] : [];
        $end = is_array($doc['end'] ?? null) ? $doc['end'] : [];
        $payment = is_array($doc['payment'] ?? null) ? $doc['payment'] : [];

        $total = $pricing['total'] ?? ($fare['total'] ?? ($doc['fare'] ?? ($pricingSnapshot['total'] ?? ($pricingBreakdown['total'] ?? null))));
        $currency = $pricing['currency'] ?? ($fare['currency'] ?? ($doc['currency'] ?? ''));
        $paymentMethod = $payment['method'] ?? ($doc['payment_method'] ?? ($doc['paymentMethod'] ?? ($doc['paymentMethodType'] ?? '')));
        $paymentStatus = $payment['status'] ?? ($doc['payment_status'] ?? ($doc['paymentStatus'] ?? 'unknown'));

        $driverUid = $this->scalarize($doc['driverUid'] ?? ($doc['driver_id'] ?? ''));
        $riderUid = $this->scalarize($doc['riderUid'] ?? ($doc['rider_id'] ?? ''));

        return [
            'id' => $this->scalarize($doc['_docId'] ?? ($doc['rideId'] ?? '')),
            'ride_id' => $this->scalarize($doc['rideId'] ?? ''),
            'source' => $source,
            'created_at' => $this->formatTimestamp($createdAt),
            'created_at_raw' => is_scalar($createdAt) ? $createdAt : null,
            'status' => $this->scalarize($status),
            'rider_uid' => $riderUid,
            'driver_uid' => $driverUid,
            'rider_name' => $this->getRiderName($riderUid),
            'driver_name' => $this->getDriverName($driverUid),
            'pickup_address' => $this->scalarize($start['address'] ?? ($doc['pickupAddress'] ?? '')),
            'dropoff_address' => $this->scalarize($end['address'] ?? ($doc['dropoffAddress'] ?? '')),
            'total' => is_numeric($total) ? (float) $total : $this->scalarize($total),
            'currency' => $this->scalarize($currency),
            'payment_method' => $this->scalarize($paymentMethod),
            'payment_status' => $this->scalarize($paymentStatus),
        ];
    }

    private function mapRideRequest(array $doc, array $raw): array
    {
        $createdAt = $doc['createdAt'] ?? $doc['created_at'] ?? ($raw['_updateTime'] ?? null);
        $status = $doc['status'] ?? '';
        $total = $doc['pricingTotal'] ?? $doc['fare'] ?? null;
        $payment = is_array($doc['payment'] ?? null) ? $doc['payment'] : [];

        $driverUid = $this->scalarize($doc['driverUid'] ?? ($doc['driver_id'] ?? ''));
        $riderUid = $this->scalarize($doc['riderUid'] ?? ($doc['rider_id'] ?? ''));

        return [
            'id' => $this->scalarize($doc['_docId'] ?? ($doc['requestId'] ?? '')),
            'ride_id' => $this->scalarize($doc['rideId'] ?? ''),
            'source' => 'ride_requests',
            'created_at' => $this->formatTimestamp($createdAt),
            'created_at_raw' => is_scalar($createdAt) ? $createdAt : null,
            'status' => $this->scalarize($status),
            'rider_uid' => $riderUid,
            'driver_uid' => $driverUid,
            'rider_name' => $this->getRiderName($riderUid),
            'driver_name' => $this->getDriverName($driverUid),
            'pickup_address' => $this->scalarize($doc['pickupAddress'] ?? ''),
            'dropoff_address' => $this->scalarize($doc['dropoffAddress'] ?? ''),
            'total' => is_numeric($total) ? (float) $total : $this->scalarize($total),
            'currency' => $this->scalarize($doc['currency'] ?? ''),
            'payment_method' => $this->scalarize($payment['method'] ?? ($doc['payment_method'] ?? ($doc['paymentMethod'] ?? ''))),
            'payment_status' => $this->scalarize($payment['status'] ?? ($doc['payment_status'] ?? 'unknown')),
        ];
    }

    private function listRideRequestsByStatuses(array $statuses): array
    {
        $rows = $this->getRideRequests();
        $statuses = array_map('strtolower', $statuses);
        $filtered = array_values(array_filter($rows, function ($row) use ($statuses) {
            return in_array(strtolower((string) ($row['status'] ?? '')), $statuses, true);
        }));
        if (env('APP_DEBUG')) {
            logger()->debug('RIDES_FIRESTORE_QUERY key=ride_requests count=' . count($filtered));
        }
        return $filtered;
    }

    private function listRidesByStatuses(array $statuses): array
    {
        $rows = $this->getRides();
        $statuses = array_map('strtolower', $statuses);
        $filtered = array_values(array_filter($rows, function ($row) use ($statuses) {
            return in_array(strtolower((string) ($row['status'] ?? '')), $statuses, true);
        }));
        if (env('APP_DEBUG')) {
            logger()->debug('RIDES_FIRESTORE_QUERY key=rides count=' . count($filtered));
        }
        return $filtered;
    }

    private function listPayments(?string $type): array
    {
        $rows = $this->getRides();
        $type = $type ? strtolower($type) : '';
        $map = [
            'wallet' => ['wallet'],
            'cash' => ['cash'],
            'online' => ['card', 'online', 'payment_gateway'],
        ];
        $allowed = $type !== '' ? ($map[$type] ?? []) : [];
        $filtered = array_values(array_filter($rows, function ($row) use ($allowed, $type) {
            if ($type === '') {
                return true;
            }
            return in_array(strtolower((string) ($row['payment_method'] ?? '')), $allowed, true);
        }));
        if (env('APP_DEBUG')) {
            logger()->debug('RIDES_FIRESTORE_QUERY key=payment-status count=' . count($filtered));
        }
        return $filtered;
    }

    private function getRides(): array
    {
        if (isset(self::$cache['rides'])) {
            return self::$cache['rides'];
        }
        if (!FeatureFlags::ridesFirestoreEnabled()) {
            throw new \RuntimeException('FIRESTORE_ENABLED=false for rides');
        }
        try {
            $docs = $this->firestore->listDocuments('rides', 200);
            $rows = [];
            foreach ($docs as $doc) {
                $rows[] = $this->mapRide($doc, $doc, 'rides');
            }
            self::$cache['rides'] = $rows;
            return $rows;
        } catch (\Throwable $e) {
            $this->logFallbackOnce($e);
            return [];
        }
    }

    private function getRideRequests(): array
    {
        if (isset(self::$cache['ride_requests'])) {
            return self::$cache['ride_requests'];
        }
        if (!FeatureFlags::ridesFirestoreEnabled()) {
            throw new \RuntimeException('FIRESTORE_ENABLED=false for rides');
        }
        try {
            $docs = $this->firestore->listDocuments('ride_requests', 200);
            $rows = [];
            foreach ($docs as $doc) {
                $rows[] = $this->mapRideRequest($doc, $doc);
            }
            self::$cache['ride_requests'] = $rows;
            return $rows;
        } catch (\Throwable $e) {
            $this->logFallbackOnce($e);
            return [];
        }
    }

    private function getDriverName(string $uid): string
    {
        if ($uid === '') {
            return '';
        }
        if (isset(self::$cache['driver:' . $uid])) {
            return self::$cache['driver:' . $uid];
        }
        $doc = $this->firestore->getDocumentFields('drivers', $uid) ?? [];
        $name = $doc['name'] ?? '';
        $name = $this->scalarize($name);
        self::$cache['driver:' . $uid] = $name;
        return $name;
    }

    private function getRiderName(string $uid): string
    {
        if ($uid === '') {
            return '';
        }
        if (isset(self::$cache['rider:' . $uid])) {
            return self::$cache['rider:' . $uid];
        }
        $doc = $this->firestore->getDocumentFields('users', $uid) ?? [];
        $name = $doc['name'] ?? '';
        if ($name === '' && (isset($doc['first_name']) || isset($doc['last_name']))) {
            $name = trim(($doc['first_name'] ?? '') . ' ' . ($doc['last_name'] ?? ''));
        }
        $name = $this->scalarize($name);
        self::$cache['rider:' . $uid] = $name;
        return $name;
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
        logger()->warning('RIDES_FIRESTORE_FALLBACK reason=' . $reason . ' message=' . $e->getMessage());
        self::$logged['RIDES'] = true;
    }
}

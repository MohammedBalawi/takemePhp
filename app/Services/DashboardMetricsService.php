<?php

namespace App\Services;

use App\Support\FeatureFlags;
use Carbon\Carbon;
use Illuminate\Support\Str;

class DashboardMetricsService
{
    private FirestoreRestService $firestore;
    private static array $cache = [];
    private static array $userCache = [];
    private static array $driverCache = [];

    public function __construct(FirestoreRestService $firestore)
    {
        $this->firestore = $firestore;
    }

    public function getDashboardMetrics(): array
    {
        if (!FeatureFlags::firestoreEnabled()) {
            throw new \RuntimeException('FIRESTORE_ENABLED=false for dashboard metrics');
        }
        $cacheKey = 'dashboard.metrics';
        if (array_key_exists($cacheKey, self::$cache)) {
            return self::$cache[$cacheKey];
        }

        $this->firestore->resetFailure();

        try {
            $metrics = [
                'totalDrivers' => $this->countDriversAll(),
                'pendingDrivers' => $this->countDriversPending(),
                'totalRiders' => $this->countRiders(),
                'totalRides' => $this->countRidesAll(),
                'todayEarnings' => $this->sumRidesTotalsByRange($this->todayStart(), $this->todayEnd()),
                'monthEarnings' => $this->sumRidesTotalsByRange($this->monthStart(), $this->monthEnd()),
                'totalEarnings' => $this->sumRidesTotalsByRange(null, null),
                'sosCount' => $this->countSosAlerts(),
                'recentRides' => [],
                'newRideRequests' => 0,
                'pendingComplaints' => 0,
                'pendingWithdrawRequests' => 0,
                'pendingSupportRequests' => 0,
            ];
        } catch (\Throwable $e) {
            $this->logFallbackOnce('DASHBOARD', $e);
            return [];
        }

        if ($this->firestore->hadFailure()) {
            return [];
        }

        self::$cache[$cacheKey] = $metrics;

        return $metrics;
    }

    public function getRecentRides(int $limit = 10): array
    {
        if (!FeatureFlags::firestoreEnabled()) {
            throw new \RuntimeException('FIRESTORE_ENABLED=false for dashboard metrics');
        }
        $cacheKey = 'dashboard.recent.' . $limit;
        if (array_key_exists($cacheKey, self::$cache)) {
            return self::$cache[$cacheKey];
        }

        $this->firestore->resetFailure();
        $limit = min(10, max(1, $limit));

        try {
            $rides = $this->firestore->queryCollection('rides', [], [
                ['field' => 'createdAt', 'direction' => 'DESCENDING'],
            ], $limit, [
                'ride_id',
                'id',
                'riderUid',
                'driverUid',
                'riderName',
                'riderPhone',
                'driverName',
                'driverPhone',
                'status',
                'status_simple',
                'createdAt',
                'pricing.total',
                'pricing.currency',
                'fare.total',
                'fare.currency',
                'currency',
                'cityId',
                'startAddress',
                'endAddress',
                'distanceKm',
                'durationMin',
                'paymentMethod',
                'paymentStatus',
            ]);
        } catch (\Throwable $e) {
            $this->logFallbackOnce('DASHBOARD', $e);
            return [];
        }

        if ($this->firestore->hadFailure()) {
            return [];
        }

        $results = [];
        foreach ($rides as $ride) {
            $riderUid = $ride['riderUid'] ?? null;
            $driverUid = $ride['driverUid'] ?? null;

            $rider = [
                'name' => $ride['riderName'] ?? '-',
                'phone' => $ride['riderPhone'] ?? '-',
            ];
            if (($rider['name'] === '-' || $rider['phone'] === '-') && $riderUid) {
                $rider = $this->getUserSummary($riderUid);
            }

            $driver = [
                'name' => $ride['driverName'] ?? '-',
                'phone' => $ride['driverPhone'] ?? '-',
            ];
            if (($driver['name'] === '-' || $driver['phone'] === '-') && $driverUid) {
                $driver = $this->getDriverSummary($driverUid);
            }

            $createdAt = $this->formatTimestamp($ride['createdAt'] ?? null);
            $total = $this->resolveRideTotal($ride);
            $currency = $this->resolveRideCurrency($ride);
            $rideId = $ride['ride_id'] ?? $ride['id'] ?? $ride['__id'] ?? '';

            if ($createdAt === null || $total <= 0) {
                continue;
            }

            if ($rideId === '' && isset($ride['__id'])) {
                $rideId = $ride['__id'];
            }
            $shortId = $rideId !== '' ? $rideId : $this->shortDocId($ride['__id'] ?? '');

            $results[] = [
                'id' => $shortId,
                'riderName' => $rider['name'],
                'riderPhone' => $rider['phone'],
                'driverName' => $driver['name'],
                'driverPhone' => $driver['phone'],
                'status' => $ride['status'] ?? ($ride['status_simple'] ?? 'pending'),
                'createdAt' => $createdAt,
                'total' => $total,
                'currency' => $currency,
                'cityId' => $ride['cityId'] ?? '-',
                'startAddress' => $ride['startAddress'] ?? '-',
                'endAddress' => $ride['endAddress'] ?? '-',
                'distanceKm' => $ride['distanceKm'] ?? '-',
                'durationMin' => $ride['durationMin'] ?? '-',
                'paymentMethod' => $ride['paymentMethod'] ?? '-',
                'paymentStatus' => $ride['paymentStatus'] ?? '-',
            ];
        }

        self::$cache[$cacheKey] = $results;
        return $results;
    }

    private function countDriversAll(): int
    {
        return $this->firestore->countCollection('drivers');
    }

    private function countDriversPending(): int
    {
        return $this->firestore->countCollection('drivers', [
            ['field' => 'verificationStatus', 'op' => 'EQUAL', 'value' => 'pending'],
        ]);
    }

    private function countRiders(): int
    {
        return $this->firestore->countCollection('users', [
            ['field' => 'user_type', 'op' => 'EQUAL', 'value' => 'rider'],
        ]);
    }

    private function countRidesAll(): int
    {
        return $this->firestore->countCollection('rides');
    }

    private function countSosAlerts(): int
    {
        return $this->firestore->countCollection('sos_alerts');
    }

    private function sumRidesTotalsByRange(?Carbon $start, ?Carbon $end): float
    {
        $where = [];
        if ($start !== null) {
            $where[] = ['field' => 'createdAt', 'op' => 'GREATER_THAN_OR_EQUAL', 'value' => $start];
        }
        if ($end !== null) {
            $where[] = ['field' => 'createdAt', 'op' => 'LESS_THAN', 'value' => $end];
        }

        $pageSize = 200;
        $maxDocs = 200;
        $offset = 0;
        $total = 0.0;

        while ($offset < $maxDocs) {
            $rides = $this->firestore->queryCollection('rides', $where, [], $pageSize, [
                'pricing.total',
                'fare.total',
            ], $offset);
            if (count($rides) === 0) {
                break;
            }

            foreach ($rides as $ride) {
                $total += $this->resolveRideTotal($ride);
            }

            $offset += count($rides);
            if (count($rides) < $pageSize) {
                break;
            }
        }

        return $total;
    }

    private function resolveRideTotal(array $ride): float
    {
        $pricing = $this->getValueByPath($ride, 'pricing.total');
        if (is_numeric($pricing)) {
            return (float) $pricing;
        }
        $fare = $this->getValueByPath($ride, 'fare.total');
        if (is_numeric($fare)) {
            return (float) $fare;
        }
        return 0.0;
    }

    private function resolveRideCurrency(array $ride): string
    {
        $currency = $this->getValueByPath($ride, 'pricing.currency')
            ?? $this->getValueByPath($ride, 'fare.currency')
            ?? ($ride['currency'] ?? '');
        return is_string($currency) ? $currency : '';
    }

    private function getUserSummary(string $uid): array
    {
        if (array_key_exists($uid, self::$userCache)) {
            return self::$userCache[$uid];
        }

        if ($this->tooManyLookups(self::$userCache)) {
            return ['name' => '-', 'phone' => '-'];
        }

        $doc = $this->firestore->getDocumentFields('users', $uid) ?? [];
        $summary = $this->formatPersonSummary($doc);

        self::$userCache[$uid] = $summary;
        return $summary;
    }

    private function getDriverSummary(string $uid): array
    {
        if (array_key_exists($uid, self::$driverCache)) {
            return self::$driverCache[$uid];
        }

        if ($this->tooManyLookups(self::$driverCache)) {
            return ['name' => '-', 'phone' => '-'];
        }

        $doc = $this->firestore->getDocumentFields('drivers', $uid) ?? [];
        $summary = $this->formatPersonSummary($doc);

        self::$driverCache[$uid] = $summary;
        return $summary;
    }

    private function formatPersonSummary(array $doc): array
    {
        $name = $doc['name']
            ?? $doc['display_name']
            ?? $doc['full_name']
            ?? $this->concatName($doc['first_name'] ?? null, $doc['last_name'] ?? null)
            ?? $doc['email']
            ?? '-';

        $phone = $doc['phone']
            ?? $doc['mobile']
            ?? $doc['contact_number']
            ?? '-';

        return [
            'name' => $name,
            'phone' => $phone,
        ];
    }

    private function concatName(?string $first, ?string $last): ?string
    {
        $parts = array_filter([$first, $last], function ($value) {
            return is_string($value) && $value !== '';
        });

        if (count($parts) === 0) {
            return null;
        }

        return implode(' ', $parts);
    }

    private function formatTimestamp($value): ?string
    {
        if (!$value) {
            return null;
        }

        try {
            return Carbon::parse($value)->format('Y-m-d H:i:s');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function getValueByPath(array $data, string $path)
    {
        if ($path === '') {
            return null;
        }

        $segments = explode('.', $path);
        $value = $data;
        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return null;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    private function shortDocId(string $docId): string
    {
        if ($docId === '') {
            return '-';
        }
        return Str::limit($docId, 8, '');
    }

    private function tooManyLookups(array $cache): bool
    {
        return count($cache) >= 20;
    }

    private function todayStart(): Carbon
    {
        return Carbon::now('UTC')->startOfDay();
    }

    private function todayEnd(): Carbon
    {
        return Carbon::now('UTC')->startOfDay()->addDay();
    }

    private function monthStart(): Carbon
    {
        return Carbon::now('UTC')->startOfMonth();
    }

    private function monthEnd(): Carbon
    {
        return Carbon::now('UTC')->startOfMonth()->addMonth();
    }

    private function logFallbackOnce(string $feature, \Throwable $e): void
    {
        static $logged = [];
        if (isset($logged[$feature])) {
            return;
        }

        $reason = stripos($e->getMessage(), 'timeout') !== false ? 'timeout' : 'exception';
        logger()->warning('FIRESTORE_FALLBACK feature=' . $feature . ' reason=' . $reason . ' message=' . $e->getMessage());
        $logged[$feature] = true;
    }

}

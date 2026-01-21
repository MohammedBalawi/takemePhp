<?php

namespace App\Services;

use App\Support\FeatureFlags;
use Carbon\Carbon;
use Google\Cloud\Core\Timestamp;
use Illuminate\Support\Arr;

class DashboardService
{
    private $firestore;
    private static array $cache = [];
    private static array $userCache = [];
    private static array $driverCache = [];

    public function __construct()
    {
        $this->firestore = app('firebase.firestore')->database();
    }

    public function getStats(): array
    {
        if (!FeatureFlags::firestoreEnabled()) {
            throw new \RuntimeException('FIRESTORE_ENABLED=false for dashboard');
        }

        if (isset(self::$cache['stats'])) {
            return self::$cache['stats'];
        }

        $stats = [
            'totalDrivers' => $this->countCollection('drivers'),
            'pendingDrivers' => $this->countPendingDrivers(),
            'totalRiders' => $this->countRidersOrUsers(),
            'totalRides' => $this->countCollection('rides'),
            'todayEarnings' => $this->sumRideTotalsByRange($this->todayStart(), $this->todayEnd()),
            'monthEarnings' => $this->sumRideTotalsByRange($this->monthStart(), $this->monthEnd()),
            'totalEarnings' => $this->sumRideTotalsByRange(null, null),
            'sosCount' => $this->countCollection('sos'),
            'recentRides' => [],
            'newRideRequests' => 0,
            'pendingComplaints' => 0,
            'pendingWithdrawRequests' => 0,
            'pendingSupportRequests' => 0,
        ];

        self::$cache['stats'] = $stats;
        return $stats;
    }

    public function getRecentRides(int $limit = 10): array
    {
        if (!FeatureFlags::firestoreEnabled()) {
            throw new \RuntimeException('FIRESTORE_ENABLED=false for dashboard');
        }

        $cacheKey = 'recent:' . $limit;
        if (isset(self::$cache[$cacheKey])) {
            return self::$cache[$cacheKey];
        }

        $rides = [];
        $query = $this->firestore->collection('rides')
            ->orderBy('createdAt', 'DESC')
            ->limit($limit);

        foreach ($query->documents() as $doc) {
            if (!$doc->exists()) {
                continue;
            }
            $data = $doc->data();
            $rides[] = $this->mapRideRow($doc->id(), $data);
        }

        self::$cache[$cacheKey] = $rides;
        return $rides;
    }

    public function getRideById(string $id): ?array
    {
        if (!FeatureFlags::firestoreEnabled()) {
            throw new \RuntimeException('FIRESTORE_ENABLED=false for dashboard');
        }

        $doc = $this->firestore->collection('rides')->document($id)->snapshot();
        if ($doc->exists()) {
            return $this->scalarizeArray($doc->data(), $doc->id());
        }

        $query = $this->firestore->collection('rides')
            ->where('ride_id', '==', $id)
            ->limit(1);
        foreach ($query->documents() as $match) {
            if ($match->exists()) {
                return $this->scalarizeArray($match->data(), $match->id());
            }
        }

        return null;
    }

    private function countCollection(string $collection, array $filters = []): int
    {
        try {
            $query = $this->firestore->collection($collection);
            foreach ($filters as $filter) {
                $query = $query->where($filter['field'], $filter['op'], $filter['value']);
            }

            if (method_exists($query, 'count')) {
                $agg = $query->count();
                $docs = $agg->documents();
                foreach ($docs as $row) {
                    if (isset($row['count'])) {
                        return (int) $row['count'];
                    }
                }
            }

            $count = 0;
            foreach ($query->documents() as $doc) {
                if ($doc->exists()) {
                    $count++;
                }
                if ($count > 5000) {
                    break;
                }
            }
            return $count;
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function countPendingDrivers(): int
    {
        $pending = $this->countCollection('drivers', [
            ['field' => 'verificationStatus', 'op' => '==', 'value' => 'pending'],
        ]);
        if ($pending > 0) {
            return $pending;
        }

        $count = 0;
        foreach ($this->firestore->collection('drivers')->documents() as $doc) {
            if (!$doc->exists()) {
                continue;
            }
            $status = $doc->data()['verificationStatus'] ?? null;
            if (!is_string($status) || strtolower($status) !== 'approved') {
                $count++;
            }
            if ($count > 5000) {
                break;
            }
        }

        return $count;
    }

    private function countRidersOrUsers(): int
    {
        $riders = $this->countCollection('users', [
            ['field' => 'user_type', 'op' => '==', 'value' => 'rider'],
        ]);
        if ($riders > 0) {
            return $riders;
        }
        return $this->countCollection('users');
    }

    private function sumRideTotalsByRange(?Carbon $start, ?Carbon $end): float
    {
        $sum = 0.0;
        $query = $this->firestore->collection('rides');
        $tz = config('app.timezone', 'UTC');

        if ($start !== null) {
            $query = $query->where('createdAt', '>=', new Timestamp($start->copy()->setTimezone($tz)->toDateTime()));
        }
        if ($end !== null) {
            $query = $query->where('createdAt', '<', new Timestamp($end->copy()->setTimezone($tz)->toDateTime()));
        }

        $statusQuery = $query;
        $statuses = ['completed', 'paid', 'finished'];
        $useStatusFilter = false;
        try {
            $statusQuery = $statusQuery->where('status', 'in', $statuses);
            $useStatusFilter = true;
        } catch (\Throwable $e) {
            $statusQuery = $query;
        }

        $docs = $statusQuery->documents();
        $counted = 0;
        foreach ($docs as $doc) {
            if (!$doc->exists()) {
                continue;
            }
            $sum += $this->resolveRideTotal($doc->data());
            $counted++;
            if ($counted > 2000) {
                break;
            }
        }

        if ($counted === 0 && $useStatusFilter) {
            $docs = $query->documents();
            foreach ($docs as $doc) {
                if (!$doc->exists()) {
                    continue;
                }
                $sum += $this->resolveRideTotal($doc->data());
                $counted++;
                if ($counted > 2000) {
                    break;
                }
            }
        }

        return $sum;
    }

    private function mapRideRow(string $docId, array $data): array
    {
        $riderUid = $data['riderUid'] ?? '';
        $driverUid = $data['driverUid'] ?? '';
        $riderName = $data['riderName'] ?? '';
        $riderPhone = $data['riderPhone'] ?? '';

        if (($riderName === '' || $riderPhone === '') && $riderUid !== '') {
            $rider = $this->getUserSummary($riderUid);
            $riderName = $riderName !== '' ? $riderName : $rider['name'];
            $riderPhone = $riderPhone !== '' ? $riderPhone : $rider['phone'];
        }

        $driverName = $data['driverName'] ?? '';
        if ($driverName === '' && $driverUid !== '') {
            $driver = $this->getDriverSummary($driverUid);
            $driverName = $driver['name'];
        }

        return [
            'id' => $docId,
            'riderName' => $this->scalarize($riderName),
            'riderPhone' => $this->scalarize($riderPhone),
            'driverUid' => $this->scalarize($driverUid),
            'driverName' => $this->scalarize($driverName),
            'status' => $this->scalarize($data['status'] ?? ($data['status_simple'] ?? '')),
            'paymentMethod' => $this->scalarize($this->getValueByPath($data, 'payment.method') ?? $data['paymentMethod'] ?? ''),
            'paymentStatus' => $this->scalarize($this->getValueByPath($data, 'payment.status') ?? $data['payment_status'] ?? ''),
            'cityId' => $this->scalarize($data['cityId'] ?? ''),
            'startAddress' => $this->scalarize($this->getValueByPath($data, 'start.address') ?? ''),
            'endAddress' => $this->scalarize($this->getValueByPath($data, 'end.address') ?? ''),
            'distanceKm' => $this->scalarize($data['distanceKm'] ?? ($data['distance_km'] ?? '')),
            'durationMin' => $this->scalarize($data['durationMin'] ?? ($data['duration_min'] ?? '')),
            'total' => $this->resolveRideTotal($data),
            'createdAt' => $this->formatTimestamp($data['createdAt'] ?? $data['created_at'] ?? $data['__updateTime'] ?? null),
        ];
    }

    private function resolveRideTotal(array $data): float
    {
        $pricing = $this->getValueByPath($data, 'pricing.total');
        if (is_numeric($pricing)) {
            return (float) $pricing;
        }
        $fare = $this->getValueByPath($data, 'fare.total');
        if (is_numeric($fare)) {
            return (float) $fare;
        }
        return 0.0;
    }

    private function getUserSummary(string $uid): array
    {
        if (isset(self::$userCache[$uid])) {
            return self::$userCache[$uid];
        }

        $doc = $this->firestore->collection('users')->document($uid)->snapshot();
        $data = $doc->exists() ? $doc->data() : [];
        $summary = $this->formatPersonSummary($data);
        self::$userCache[$uid] = $summary;
        return $summary;
    }

    private function getDriverSummary(string $uid): array
    {
        if (isset(self::$driverCache[$uid])) {
            return self::$driverCache[$uid];
        }

        $doc = $this->firestore->collection('drivers')->document($uid)->snapshot();
        $data = $doc->exists() ? $doc->data() : [];
        $summary = $this->formatPersonSummary($data);
        self::$driverCache[$uid] = $summary;
        return $summary;
    }

    private function formatPersonSummary(array $doc): array
    {
        $name = $doc['name']
            ?? Arr::get($doc, 'profileExtra.username')
            ?? $doc['username']
            ?? $doc['email']
            ?? $this->concatName($doc['first_name'] ?? null, $doc['last_name'] ?? null)
            ?? '-';

        $phone = $doc['phone']
            ?? $doc['contactNumber']
            ?? $doc['mobile']
            ?? '-';

        return [
            'name' => $this->scalarize($name),
            'phone' => $this->scalarize($phone),
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

    private function formatTimestamp($value): string
    {
        if ($value instanceof Timestamp) {
            return Carbon::parse($value->get())->setTimezone(config('app.timezone', 'UTC'))->format('Y-m-d H:i:s');
        }
        if (is_string($value) && $value !== '') {
            try {
                return Carbon::parse($value)->setTimezone(config('app.timezone', 'UTC'))->format('Y-m-d H:i:s');
            } catch (\Throwable $e) {
                return $value;
            }
        }
        return '';
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

    private function scalarizeArray(array $data, string $docId): array
    {
        $out = ['__id' => $docId];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $out[$key] = json_encode($value, JSON_UNESCAPED_UNICODE);
            } elseif ($value instanceof Timestamp) {
                $out[$key] = $this->formatTimestamp($value);
            } else {
                $out[$key] = $this->scalarize($value);
            }
        }
        return $out;
    }

    private function todayStart(): Carbon
    {
        return Carbon::now(config('app.timezone', 'UTC'))->startOfDay();
    }

    private function todayEnd(): Carbon
    {
        return Carbon::now(config('app.timezone', 'UTC'))->startOfDay()->addDay();
    }

    private function monthStart(): Carbon
    {
        return Carbon::now(config('app.timezone', 'UTC'))->startOfMonth();
    }

    private function monthEnd(): Carbon
    {
        return Carbon::now(config('app.timezone', 'UTC'))->startOfMonth()->addMonth();
    }
}

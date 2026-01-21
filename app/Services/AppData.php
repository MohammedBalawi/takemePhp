<?php

namespace App\Services;

use Carbon\Carbon;

class AppData
{
    private FirestoreRestService $firestore;

    public function __construct(FirestoreRestService $firestore)
    {
        $this->firestore = $firestore;
    }

    public function getAppSettings(): array
    {
        if (!isFirestoreEnabled()) {
            throw new \RuntimeException('FIRESTORE_ENABLED is false. App requires Firestore.');
        }

        $data = $this->firestore->getAppSettings();
        if (!is_array($data)) {
            return ['language_option' => 'ar'];
        }

        $merged = $data;
        if (!isset($merged['language_option'])) {
            $merged['language_option'] = 'ar';
        }

        return $merged;
    }

    public function getFrontendData(string $type): array
    {
        if (!isFirestoreEnabled()) {
            throw new \RuntimeException('FIRESTORE_ENABLED is false. App requires Firestore.');
        }

        $data = $this->firestore->getFrontendData($type);
        if (!is_array($data)) {
            return [];
        }

        return $data;
    }

    public function getDashboardMetrics(): array
    {
        if (!\App\Support\FeatureFlags::shouldUseFirestore('DASHBOARD')) {
            throw new \RuntimeException('FIRESTORE_ENABLED is false. App requires Firestore.');
        }

        try {
            $service = app(DashboardMetricsService::class);
            $metrics = $service->getDashboardMetrics();
            if (!is_array($metrics) || count($metrics) === 0) {
                return [];
            }
            return $metrics;
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function sumRideTotals(array $where = []): float
    {
        $rides = $this->firestore->queryCollection('rides', $where, [], 1000, []);
        $total = 0.0;
        foreach ($rides as $ride) {
            $total += $this->resolveRideTotal($ride);
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

    private function getRecentRides(int $limit): array
    {
        $rides = $this->firestore->queryCollection('rides', [], [
            ['field' => 'createdAt', 'direction' => 'DESCENDING'],
        ], $limit, []);

        $results = [];
        foreach ($rides as $ride) {
            $riderUid = $ride['riderUid'] ?? null;
            $driverUid = $ride['driverUid'] ?? null;

            $rider = $riderUid ? $this->getUserSummary($riderUid) : ['name' => '-', 'phone' => '-'];
            $driver = $driverUid ? $this->getUserSummary($driverUid) : ['name' => '-', 'phone' => '-'];

            $createdAt = $this->formatTimestamp($ride['createdAt'] ?? null);

            $results[] = [
                'id' => $ride['__id'] ?? '',
                'riderName' => $rider['name'],
                'riderPhone' => $rider['phone'],
                'driverName' => $driver['name'],
                'driverPhone' => $driver['phone'],
                'status' => $ride['status'] ?? 'pending',
                'createdAt' => $createdAt,
                'total' => $this->resolveRideTotal($ride),
            ];
        }

        return $results;
    }

    private function getUserSummary(string $uid): array
    {
        $doc = $this->firestore->getDocumentFields('users', $uid);
        $name = $doc['display_name'] ?? $doc['name'] ?? $doc['full_name'] ?? $doc['email'] ?? '-';
        $phone = $doc['phone'] ?? $doc['contact_number'] ?? $doc['mobile'] ?? '-';

        return [
            'name' => $name,
            'phone' => $phone,
        ];
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
}

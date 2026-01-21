<?php

namespace App\Services;

use App\Support\FeatureFlags;
use Carbon\Carbon;

class PaymentsService
{
    private FirestoreRestService $firestore;
    private static array $cache = [];
    private static array $logged = [];

    public function __construct(FirestoreRestService $firestore)
    {
        $this->firestore = $firestore;
    }

    public function listByType(string $type): array
    {
        $type = strtolower($type);
        $mock = config('mock_data.payments_' . $type, []);
        if (!FeatureFlags::paymentsFirestoreEnabled()) {
            return is_array($mock) ? $mock : [];
        }

        if (isset(self::$cache['payments.' . $type])) {
            return self::$cache['payments.' . $type];
        }

        try {
            $rides = $this->firestore->listDocuments('rides', 500);
            $reqs = $this->firestore->listDocuments('ride_requests', 500);

            $rows = [];
            foreach ($rides as $doc) {
                $row = $this->mapRidePayment($doc);
                if ($this->matchesType($row['payment_method'] ?? '', $type)) {
                    $rows[] = $row;
                }
            }

            foreach ($reqs as $doc) {
                $row = $this->mapRequestPayment($doc);
                if ($this->matchesType($row['payment_method'] ?? '', $type)) {
                    $rows[] = $row;
                }
            }

            usort($rows, function ($a, $b) {
                return strcmp($b['created_at'] ?? '', $a['created_at'] ?? '');
            });

            if (env('APP_DEBUG')) {
                $sampleIds = array_slice(array_map(function ($row) {
                    return $row['id'] ?? '';
                }, $rows), 0, 2);
                logger()->debug('PAYMENTS_FIRESTORE_AGG rides=' . count($rides) . ' reqs=' . count($reqs) . ' out=' . count($rows) . ' type=' . $type . ' sample=' . implode(',', $sampleIds));
            }

            self::$cache['payments.' . $type] = $rows;
            return $rows;
        } catch (\Throwable $e) {
            $this->logFallbackOnce($e);
            return is_array($mock) ? $mock : [];
        }
    }

    private function mapRidePayment(array $doc): array
    {
        $createdAt = $doc['createdAt'] ?? $doc['created_at'] ?? $doc['updatedAt'] ?? ($doc['_updateTime'] ?? null);
        $pricing = is_array($doc['pricing'] ?? null) ? $doc['pricing'] : [];
        $pricingSnapshot = is_array($doc['pricingSnapshot'] ?? null) ? $doc['pricingSnapshot'] : [];
        $pricingBreakdown = is_array($pricing['breakdown'] ?? null) ? $pricing['breakdown'] : [];
        $fare = is_array($doc['fare'] ?? null) ? $doc['fare'] : [];
        $payment = is_array($doc['payment'] ?? null) ? $doc['payment'] : [];

        $total = $pricing['total'] ?? ($fare['total'] ?? ($doc['fare'] ?? ($pricingSnapshot['total'] ?? ($pricingBreakdown['total'] ?? 0))));
        $currency = $pricing['currency'] ?? ($fare['currency'] ?? ($doc['currency'] ?? ''));
        $method = $payment['method'] ?? ($doc['payment_method'] ?? ($doc['paymentMethod'] ?? ($doc['paymentMethodType'] ?? '')));
        $status = $payment['status'] ?? ($doc['payment_status'] ?? ($doc['paymentStatus'] ?? 'unknown'));

        return [
            'id' => $this->scalarize($doc['_docId'] ?? ($doc['rideId'] ?? '')),
            'source' => 'rides',
            'rider_uid' => $this->scalarize($doc['riderUid'] ?? ($doc['rider_id'] ?? '')),
            'driver_uid' => $this->scalarize($doc['driverUid'] ?? ($doc['driver_id'] ?? '')),
            'total' => is_numeric($total) ? (float) $total : $this->scalarize($total),
            'currency' => $this->scalarize($currency),
            'payment_method' => $this->normalizePaymentMethod($method),
            'payment_status' => $this->scalarize($status),
            'created_at' => $this->formatTimestamp($createdAt),
        ];
    }

    private function mapRequestPayment(array $doc): array
    {
        $createdAt = $doc['createdAt'] ?? $doc['created_at'] ?? $doc['updatedAt'] ?? ($doc['_updateTime'] ?? null);
        $payment = is_array($doc['payment'] ?? null) ? $doc['payment'] : [];
        $total = $doc['pricingTotal'] ?? $doc['fare'] ?? 0;
        $currency = $doc['currency'] ?? '';
        $method = $payment['method'] ?? ($doc['payment_method'] ?? ($doc['paymentMethod'] ?? ''));
        $status = $payment['status'] ?? ($doc['payment_status'] ?? 'unknown');

        return [
            'id' => $this->scalarize($doc['_docId'] ?? ($doc['rideId'] ?? ($doc['requestId'] ?? ''))),
            'source' => 'ride_requests',
            'rider_uid' => $this->scalarize($doc['riderUid'] ?? ($doc['rider_id'] ?? '')),
            'driver_uid' => $this->scalarize($doc['driverUid'] ?? ($doc['driver_id'] ?? '')),
            'total' => is_numeric($total) ? (float) $total : $this->scalarize($total),
            'currency' => $this->scalarize($currency),
            'payment_method' => $this->normalizePaymentMethod($method),
            'payment_status' => $this->scalarize($status),
            'created_at' => $this->formatTimestamp($createdAt),
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

    private function normalizePaymentMethod($value): string
    {
        $method = strtolower(trim((string) $value));
        if ($method === '') {
            return '';
        }
        return $method;
    }

    private function matchesType(string $method, string $type): bool
    {
        $method = strtolower(trim($method));
        $type = strtolower(trim($type));
        if ($type === '') {
            return true;
        }
        if ($type === 'cash') {
            return $method === 'cash';
        }
        if ($type === 'wallet') {
            return $method === 'wallet';
        }
        if ($type === 'online') {
            return in_array($method, ['online', 'card', 'credit', 'apple_pay', 'stc_pay'], true);
        }
        return $method === $type;
    }

    private function logFallbackOnce(\Throwable $e): void
    {
        if (isset(self::$logged['PAYMENTS'])) {
            return;
        }

        $reason = stripos($e->getMessage(), 'timeout') !== false ? 'timeout' : 'exception';
        logger()->warning('PAYMENTS_FIRESTORE_FALLBACK reason=' . $reason . ' message=' . $e->getMessage());
        self::$logged['PAYMENTS'] = true;
    }
}

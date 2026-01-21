<?php

namespace App\Services;

use App\Support\FeatureFlags;
use Carbon\Carbon;
use Illuminate\Support\Str;

class CouponsService
{
    private FirestoreRestService $firestore;
    private static array $cache = [];
    private static array $logged = [];

    public function __construct(FirestoreRestService $firestore)
    {
        $this->firestore = $firestore;
    }

    public function listCoupons(): array
    {
        $mock = config('mock_data.coupons', []);
        if (!FeatureFlags::shouldUseFirestore('COUPONS')) {
            return is_array($mock) ? $mock : [];
        }

        if (isset(self::$cache['coupons.list'])) {
            return self::$cache['coupons.list'];
        }

        try {
            $documents = $this->firestore->listDocuments('coupons', 300);
            $mapped = [];

            foreach ($documents as $doc) {
                $mapped[] = $this->mapCoupon($doc, $doc);
            }

            usort($mapped, function ($a, $b) {
                return strcmp($b['created_at'] ?? '', $a['created_at'] ?? '');
            });

            if (env('APP_DEBUG')) {
                logger()->debug('COUPONS_FIRESTORE_QUERY count=' . count($mapped));
            }

            self::$cache['coupons.list'] = $mapped;
            return $mapped;
        } catch (\Throwable $e) {
            $this->logFallbackOnce($e);
            return is_array($mock) ? $mock : [];
        }
    }

    public function createCoupon(array $data): bool
    {
        if (!FeatureFlags::shouldUseFirestore('COUPONS')) {
            return true;
        }

        try {
            $code = strtoupper(trim((string) ($data['code'] ?? '')));
            $docId = $this->safeDocId($code) ? $code : Str::uuid()->toString();

            if ($docId === $code) {
                $existing = $this->firestore->getDocumentFields('coupons', $docId);
                if (!empty($existing)) {
                    return false;
                }
            } else {
                $documents = $this->firestore->listDocuments('coupons', 200);
                foreach ($documents as $doc) {
                    if (strtoupper((string) ($doc['code'] ?? '')) === $code) {
                        return false;
                    }
                }
            }

            $now = Carbon::now('UTC');
            $startAt = $this->parseDate($data['start_date'] ?? null);
            $endAt = $this->parseDate($data['end_date'] ?? null);

            $fields = [
                'code' => $code,
                'title' => (string) ($data['title'] ?? ''),
                'type' => (string) ($data['coupon_type'] ?? 'all'),
                'discount_type' => $this->mapDiscountType($data['discount_type'] ?? ''),
                'discount' => (float) ($data['discount'] ?? 0),
                'min_amount' => (float) ($data['minimum_amount'] ?? 0),
                'max_discount' => (float) ($data['maximum_discount'] ?? 0),
                'start_at' => $startAt ?? $now,
                'end_at' => $endAt ?? $now,
                'per_user_limit' => (int) ($data['usage_limit_per_rider'] ?? 0),
                'status' => $this->mapStatus($data['status'] ?? '1'),
                'description' => (string) ($data['description'] ?? ''),
                'created_at' => $now,
                'updated_at' => $now,
            ];

            return $this->firestore->patchDocumentTyped('coupons', $docId, $fields);
        } catch (\Throwable $e) {
            $this->logFallbackOnce($e);
            return false;
        }
    }

    private function mapCoupon(array $doc, array $raw = []): array
    {
        $createdAt = $doc['created_at'] ?? $doc['createdAt'] ?? ($raw['_updateTime'] ?? null);
        $startAt = $doc['start_at'] ?? $doc['startAt'] ?? $doc['start_date'] ?? null;
        $endAt = $doc['end_at'] ?? $doc['endAt'] ?? $doc['end_date'] ?? null;

        return [
            'id' => $doc['_docId'] ?? '',
            'code' => $doc['code'] ?? '',
            'title' => $doc['title'] ?? '',
            'coupon_type' => $doc['type'] ?? $doc['coupon_type'] ?? '',
            'discount_type' => $doc['discount_type'] ?? '',
            'discount' => $doc['discount'] ?? 0,
            'minimum_amount' => $doc['min_amount'] ?? $doc['minimum_amount'] ?? 0,
            'maximum_discount' => $doc['max_discount'] ?? $doc['maximum_discount'] ?? 0,
            'start_date' => $this->formatDate($startAt),
            'end_date' => $this->formatDate($endAt),
            'usage_limit_per_rider' => $doc['per_user_limit'] ?? $doc['usage_limit_per_rider'] ?? 0,
            'status' => $doc['status'] ?? 'inactive',
            'description' => $doc['description'] ?? '',
            'created_at' => $this->formatTimestamp($createdAt),
        ];
    }

    private function mapDiscountType(string $value): string
    {
        $value = strtolower($value);
        if ($value === 'percentage') {
            return 'percent';
        }
        if ($value === 'percent') {
            return 'percent';
        }
        return 'fixed';
    }

    private function mapStatus($value): string
    {
        if ((string) $value === '1' || $value === true || $value === 'active') {
            return 'active';
        }
        return 'inactive';
    }

    private function safeDocId(string $code): bool
    {
        return $code !== '' && preg_match('/^[A-Za-z0-9_-]+$/', $code) === 1;
    }

    private function parseDate(?string $value): ?Carbon
    {
        if (!$value) {
            return null;
        }

        try {
            return Carbon::parse($value)->setTimezone('UTC');
        } catch (\Throwable $e) {
            return null;
        }
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

    private function formatDate($value): string
    {
        if (!$value) {
            return '';
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return (string) $value;
        }
    }

    private function logFallbackOnce(\Throwable $e): void
    {
        if (isset(self::$logged['COUPONS'])) {
            return;
        }

        $reason = stripos($e->getMessage(), 'timeout') !== false ? 'timeout' : 'exception';
        logger()->warning('FIRESTORE_FALLBACK feature=COUPONS reason=' . $reason . ' message=' . $e->getMessage());
        self::$logged['COUPONS'] = true;
    }
}

<?php

namespace App\Services;

use App\Support\FeatureFlags;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PricingService
{
    private FirestoreRestService $firestore;
    private static array $warned = [];

    public function __construct(FirestoreRestService $firestore)
    {
        $this->firestore = $firestore;
    }

    public function listBasePricing(?string $cityId = null): array
    {
        if (!FeatureFlags::pricingFirestoreEnabled()) {
            return config('mock_data.pricing', []);
        }

        try {
            $docs = $this->firestore->listDocuments('pricing', 500);
            $rows = [];
            foreach ($docs as $doc) {
                if ($cityId && (string) ($doc['cityId'] ?? '') !== $cityId) {
                    continue;
                }
                $rows[] = $this->normalizeRow($doc);
            }
            return $rows;
        } catch (\Throwable $e) {
            $this->logFallback('PRICING', $this->reasonFromException($e));
            return config('mock_data.pricing', []);
        }
    }

    public function getBasePricing(string $cityKey, string $serviceId): ?array
    {
        if (!FeatureFlags::pricingFirestoreEnabled()) {
            return null;
        }

        $cityKey = trim($cityKey);
        $serviceId = trim($serviceId);
        if ($cityKey === '' || $serviceId === '') {
            return null;
        }

        $docId = $this->makeDocId($cityKey, $serviceId);
        $doc = $this->firestore->getDocumentFields('pricing', $docId);
        return is_array($doc) ? $this->normalizeRow($doc + ['_docId' => $docId]) : null;
    }

    public function upsertBasePricing(array $payload): bool
    {
        if (!FeatureFlags::pricingFirestoreEnabled()) {
            return false;
        }

        $cityId = (string) ($payload['cityId'] ?? '');
        $cityName = (string) ($payload['cityName'] ?? '');
        $serviceId = (string) ($payload['serviceId'] ?? '');
        $cityKey = $cityId !== '' ? $cityId : $cityName;
        if ($cityKey === '' || $serviceId === '') {
            return false;
        }

        $docId = $this->makeDocId($cityKey, $serviceId);
        $payload['updatedAt'] = $payload['updatedAt'] ?? now();
        $payload['createdAt'] = $payload['createdAt'] ?? now();

        $ok = $this->firestore->patchDocumentTyped('pricing', $docId, $payload);

        if (env('APP_DEBUG')) {
            Log::debug('PRICING_FIRESTORE_UPSERT cityId=' . $cityKey . ' serviceId=' . $serviceId . ' ok=' . ($ok ? '1' : '0'));
        }

        return $ok;
    }

    public function createModifier(array $payload): bool
    {
        if (!FeatureFlags::pricingFirestoreEnabled()) {
            return false;
        }

        $payload['createdAt'] = $payload['createdAt'] ?? now();
        $payload['updatedAt'] = $payload['updatedAt'] ?? now();

        $ok = $this->firestore->createDocument('pricing_modifiers', null, $payload);

        if (env('APP_DEBUG')) {
            Log::debug('PRICING_MODIFIER_CREATE type=' . ($payload['type'] ?? '') . ' ok=' . ($ok ? '1' : '0'));
        }

        return $ok;
    }

    private function normalizeRow(array $doc): array
    {
        $createdAt = $this->firestore->tsToString($doc['createdAt'] ?? null, $doc['_updateTime'] ?? null);
        return [
            'id' => (string) ($doc['_docId'] ?? ''),
            'cityId' => $this->firestore->safeScalar($doc['cityId'] ?? ''),
            'cityName' => $this->firestore->safeScalar($doc['cityName'] ?? ''),
            'serviceId' => $this->firestore->safeScalar($doc['serviceId'] ?? ''),
            'baseFare' => $this->firestore->safeScalar($doc['baseFare'] ?? $doc['base_fare'] ?? 0),
            'perKm' => $this->firestore->safeScalar($doc['perKm'] ?? $doc['per_km'] ?? 0),
            'perMin' => $this->firestore->safeScalar($doc['perMin'] ?? $doc['per_min'] ?? 0),
            'minFare' => $this->firestore->safeScalar($doc['minFare'] ?? $doc['min_fare'] ?? 0),
            'currency' => $this->firestore->safeScalar($doc['currency'] ?? 'SAR'),
            'isActive' => $this->firestore->safeScalar($doc['isActive'] ?? ''),
            'createdAt' => $createdAt,
        ];
    }

    private function logFallback(string $feature, string $reason): void
    {
        $key = $feature . ':' . $reason;
        if (isset(self::$warned[$key])) {
            return;
        }
        self::$warned[$key] = true;
        Log::warning('FIRESTORE_FALLBACK feature=' . $feature . ' reason=' . $reason);
    }

    private function reasonFromException(\Throwable $e): string
    {
        $message = strtolower($e->getMessage());
        if (str_contains($message, 'timeout')) {
            return 'timeout';
        }
        return 'exception';
    }

    private function makeDocId(string $cityKey, string $serviceId): string
    {
        $cityKey = strtolower(trim($cityKey));
        $cityKey = preg_replace('/\s+/', '-', $cityKey);
        return $cityKey . '_' . $serviceId;
    }
}

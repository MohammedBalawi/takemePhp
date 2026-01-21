<?php

namespace App\Services;

use App\Support\FeatureFlags;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PricingModifiersService
{
    private FirestoreRestService $firestore;
    private static array $warned = [];

    public function __construct(FirestoreRestService $firestore)
    {
        $this->firestore = $firestore;
    }

    public function listModifiers(?string $type = null): array
    {
        if (!FeatureFlags::pricingFirestoreEnabled()) {
            throw new \RuntimeException('FIRESTORE_ENABLED=false for pricing modifiers');
        }

        try {
            $docs = $this->firestore->listDocuments('pricing_modifiers', 500);
            $rows = [];
            foreach ($docs as $doc) {
                $row = $this->normalizeRowScalar($doc);
                if ($type && $row['type'] !== $type) {
                    continue;
                }
                $rows[] = $row;
            }
            return $rows;
        } catch (\Throwable $e) {
            $this->logFallback('PRICING', $this->reasonFromException($e));
            return [];
        }
    }

    public function createModifier(array $payload): bool
    {
        if (!FeatureFlags::pricingFirestoreEnabled()) {
            throw new \RuntimeException('FIRESTORE_ENABLED=false for pricing modifiers');
        }

        $payload['createdAt'] = $payload['createdAt'] ?? now();
        $payload['updatedAt'] = $payload['updatedAt'] ?? now();

        return $this->firestore->createDocument('pricing_modifiers', null, $payload);
    }

    public function deactivateActiveFixedGlobal(): int
    {
        if (!FeatureFlags::pricingFirestoreEnabled()) {
            throw new \RuntimeException('FIRESTORE_ENABLED=false for pricing modifiers');
        }

        try {
            $docs = $this->firestore->listDocuments('pricing_modifiers', 500);
            $count = 0;
            foreach ($docs as $doc) {
                if (($doc['type'] ?? '') !== 'fixed') {
                    continue;
                }
                $isActive = $doc['isActive'] ?? false;
                if ($isActive === true || $isActive === '1' || $isActive === 1) {
                    $docId = $doc['_docId'] ?? null;
                    if ($docId) {
                        $ok = $this->firestore->patchDocumentTyped('pricing_modifiers', (string) $docId, [
                            'isActive' => false,
                            'updatedAt' => now(),
                        ]);
                        if ($ok) {
                            $count++;
                        }
                    }
                }
            }

            if (env('APP_DEBUG')) {
                Log::debug('FIXED_MODIFIER_DEACTIVATED count=' . $count);
            }

            return $count;
        } catch (\Throwable $e) {
            $this->logFallback('PRICING', $this->reasonFromException($e));
            return 0;
        }
    }

    public function updateModifier(string $id, array $payload): bool
    {
        if (!FeatureFlags::pricingFirestoreEnabled()) {
            throw new \RuntimeException('FIRESTORE_ENABLED=false for pricing modifiers');
        }

        $payload['updatedAt'] = $payload['updatedAt'] ?? now();
        return $this->firestore->patchDocumentTyped('pricing_modifiers', $id, $payload);
    }

    public function deleteModifier(string $id): bool
    {
        if (!FeatureFlags::pricingFirestoreEnabled()) {
            throw new \RuntimeException('FIRESTORE_ENABLED=false for pricing modifiers');
        }
        return $this->firestore->deleteDocument('pricing_modifiers', $id);
    }

    public function normalizeRowScalar(array $doc): array
    {
        $createdAt = $this->firestore->tsToString($doc['createdAt'] ?? null, $doc['_updateTime'] ?? null);
        $startTime = $this->firestore->safeScalar($doc['startTime'] ?? $doc['timeFrom'] ?? '');
        $endTime = $this->firestore->safeScalar($doc['endTime'] ?? $doc['timeTo'] ?? '');

        return [
            'id' => (string) ($doc['_docId'] ?? ''),
            'type' => $this->firestore->safeScalar($doc['type'] ?? ''),
            'cityId' => $this->firestore->safeScalar($doc['cityId'] ?? ''),
            'cityName' => $this->firestore->safeScalar($doc['cityName'] ?? ''),
            'serviceId' => $this->firestore->safeScalar($doc['serviceId'] ?? ''),
            'currency' => $this->firestore->safeScalar($doc['currency'] ?? 'SAR'),
            'modifierMode' => $this->firestore->safeScalar($doc['modifierMode'] ?? $doc['increaseType'] ?? ''),
            'modifierValue' => $this->firestore->safeScalar($doc['modifierValue'] ?? $doc['increaseValue'] ?? ''),
            'overrideBaseFare' => $this->firestore->safeScalar($doc['overrideBaseFare'] ?? ''),
            'overridePerKm' => $this->firestore->safeScalar($doc['overridePerKm'] ?? ''),
            'overridePerMin' => $this->firestore->safeScalar($doc['overridePerMin'] ?? ''),
            'overrideMinFare' => $this->firestore->safeScalar($doc['overrideMinFare'] ?? ''),
            'day' => $this->firestore->safeScalar($doc['day'] ?? ''),
            'startTime' => $startTime,
            'endTime' => $endTime,
            'weatherCondition' => $this->firestore->safeScalar($doc['weatherCondition'] ?? $doc['weather'] ?? ''),
            'placeKey' => $this->firestore->safeScalar($doc['placeKey'] ?? ''),
            'placeName' => $this->firestore->safeScalar($doc['placeName'] ?? ''),
            'zoneId' => $this->firestore->safeScalar($doc['zoneId'] ?? ''),
            'surgeTag' => $this->firestore->safeScalar($doc['surgeTag'] ?? ''),
            'description' => $this->firestore->safeScalar($doc['description'] ?? ''),
            'isActive' => $this->firestore->safeScalar($doc['isActive'] ?? ''),
            'priority' => $this->firestore->safeScalar($doc['priority'] ?? ''),
            'createdAt' => $createdAt,
        ];
    }

    public function getApplicableModifier(string $cityKey, string $serviceId, ?string $now = null): ?array
    {
        if (!FeatureFlags::pricingFirestoreEnabled()) {
            return null;
        }
        $mods = $this->listModifiers();
        if (count($mods) === 0) {
            return null;
        }
        $nowTime = $now ? Carbon::parse($now) : now();
        $day = strtolower($nowTime->format('D'));
        $dayMap = ['sat' => 'sat', 'sun' => 'sun', 'mon' => 'mon', 'tue' => 'tue', 'wed' => 'wed', 'thu' => 'thu', 'fri' => 'fri'];

        $applicable = [];
        foreach ($mods as $mod) {
            if (($mod['serviceId'] ?? '') !== $serviceId) {
                continue;
            }
            $cityMatch = ($mod['cityId'] ?? '') === $cityKey || ($mod['cityName'] ?? '') === $cityKey || ($mod['cityName'] ?? '') === '';
            if (!$cityMatch) {
                continue;
            }
            if (($mod['isActive'] ?? '') === '0') {
                continue;
            }
            $modDay = strtolower((string) ($mod['day'] ?? 'all'));
            if ($modDay !== 'all' && $modDay !== ($dayMap[$day] ?? $day)) {
                continue;
            }
            $start = $mod['startTime'] ?? '';
            $end = $mod['endTime'] ?? '';
            if ($start !== '' && $end !== '') {
                $startTime = Carbon::parse($nowTime->format('Y-m-d') . ' ' . $start);
                $endTime = Carbon::parse($nowTime->format('Y-m-d') . ' ' . $end);
                if ($nowTime < $startTime || $nowTime > $endTime) {
                    continue;
                }
            }
            $applicable[] = $mod;
        }

        if (count($applicable) === 0) {
            return null;
        }

        usort($applicable, function ($a, $b) {
            return (int) ($b['priority'] ?? 0) <=> (int) ($a['priority'] ?? 0);
        });

        return $applicable[0];
    }

    public function computeEffectivePricing(array $base, array $modifier): array
    {
        $effective = $base;
        if (empty($modifier)) {
            return $effective;
        }

        if (!empty($modifier['overrideBaseFare'])) {
            $effective['baseFare'] = $modifier['overrideBaseFare'];
        }
        if (!empty($modifier['overridePerKm'])) {
            $effective['perKm'] = $modifier['overridePerKm'];
        }
        if (!empty($modifier['overridePerMin'])) {
            $effective['perMin'] = $modifier['overridePerMin'];
        }
        if (!empty($modifier['overrideMinFare'])) {
            $effective['minFare'] = $modifier['overrideMinFare'];
        }

        return $effective;
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
}

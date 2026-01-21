<?php

namespace App\Services;

use App\Support\FeatureFlags;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SurgeRulesService
{
    private FirestoreRestService $firestore;
    private static array $warned = [];

    public function __construct(FirestoreRestService $firestore)
    {
        $this->firestore = $firestore;
    }

    public function listRules(array $filters = []): array
    {
        if (!FeatureFlags::surgeRulesFirestoreEnabled()) {
            return config('mock_data.surge_rules', []);
        }

        try {
            $docs = $this->firestore->listDocuments('surge_rules', 500);
            $rows = [];
            foreach ($docs as $doc) {
                $rows[] = $this->normalizeRowScalar($doc);
            }

            usort($rows, function ($a, $b) {
                return $this->toSortKey($b['createdAt'] ?? '') <=> $this->toSortKey($a['createdAt'] ?? '');
            });

            if (env('APP_DEBUG')) {
                Log::debug('SURGE_RULES_FIRESTORE_LIST count=' . count($rows));
            }

            return $rows;
        } catch (\Throwable $e) {
            $this->logFallback('SURGE_RULES', $this->reasonFromException($e));
            return config('mock_data.surge_rules', []);
        }
    }

    public function createRule(array $payload): bool
    {
        if (!FeatureFlags::surgeRulesFirestoreEnabled()) {
            return false;
        }

        $payload['createdAt'] = $payload['createdAt'] ?? now();
        $payload['updatedAt'] = $payload['updatedAt'] ?? now();

        $ok = $this->firestore->createDocument('surge_rules', null, $payload);

        if (env('APP_DEBUG')) {
            Log::debug('SURGE_RULES_FIRESTORE_CREATE ok=' . ($ok ? '1' : '0'));
        }

        return $ok;
    }

    public function listCities(): array
    {
        if (!FeatureFlags::surgeRulesFirestoreEnabled()) {
            return config('mock_data.cities', []);
        }

        try {
            $docs = $this->firestore->listDocuments('riyadh', 500);
            $rows = [];
            foreach ($docs as $doc) {
                $id = (string) ($doc['_docId'] ?? '');
                $name = (string) ($doc['name'] ?? $doc['cityName'] ?? $doc['title'] ?? $id);
                $rows[] = [
                    'id' => $id,
                    'name' => $name,
                ];
            }
            return $rows;
        } catch (\Throwable $e) {
            $this->logFallback('SURGE_RULES', $this->reasonFromException($e));
            return config('mock_data.cities', []);
        }
    }

    public function normalizeRowScalar(array $doc): array
    {
        $createdAt = $this->firestore->tsToString($doc['createdAt'] ?? $doc['created_at'] ?? null, $doc['_updateTime'] ?? null);
        $timeFrom = $this->firestore->safeScalar($doc['timeFrom'] ?? $doc['from_time'] ?? '');
        $timeTo = $this->firestore->safeScalar($doc['timeTo'] ?? $doc['to_time'] ?? '');

        return [
            'id' => (string) ($doc['_docId'] ?? ''),
            'cityId' => $this->firestore->safeScalar($doc['cityId'] ?? ''),
            'cityName' => $this->firestore->safeScalar($doc['cityName'] ?? ''),
            'serviceId' => $this->firestore->safeScalar($doc['serviceId'] ?? ''),
            'type' => $this->firestore->safeScalar($doc['type'] ?? ''),
            'increaseType' => $this->firestore->safeScalar($doc['increaseType'] ?? ''),
            'increaseValue' => $this->firestore->safeScalar($doc['increaseValue'] ?? ''),
            'description' => $this->firestore->safeScalar($doc['description'] ?? ''),
            'weather' => $this->firestore->safeScalar($doc['weather'] ?? ''),
            'day' => $this->firestore->safeScalar($doc['day'] ?? ''),
            'timeFrom' => $timeFrom,
            'timeTo' => $timeTo,
            'status' => $this->firestore->safeScalar($doc['status'] ?? ''),
            'createdAt' => $createdAt,
        ];
    }

    private function toSortKey(string $value): int
    {
        if ($value === '') {
            return 0;
        }
        try {
            return Carbon::parse($value)->getTimestamp();
        } catch (\Throwable $e) {
            return 0;
        }
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

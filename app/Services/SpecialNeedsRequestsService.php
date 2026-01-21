<?php

namespace App\Services;

use App\Support\FeatureFlags;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SpecialNeedsRequestsService
{
    private FirestoreRestService $firestore;
    private static array $warned = [];

    public function __construct(FirestoreRestService $firestore)
    {
        $this->firestore = $firestore;
    }

    public function list(int $limit = 200): array
    {
        if (!FeatureFlags::specialNeedsRequestsFirestoreEnabled()) {
            return config('mock_data.mock_special_needs_requests', []);
        }

        try {
            $docs = $this->firestore->listDocuments('special_needs_requests', $limit);
            $rows = [];
            foreach ($docs as $doc) {
                $rows[] = $this->normalizeRow($doc);
            }

            if (env('APP_DEBUG')) {
                Log::debug('SPECIAL_NEEDS_FIRESTORE_QUERY count=' . count($rows));
            }

            return $rows;
        } catch (\Throwable $e) {
            $this->logFallback($this->reasonFromException($e));
            return config('mock_data.mock_special_needs_requests', []);
        }
    }

    public function create(array $data): bool
    {
        if (!FeatureFlags::specialNeedsRequestsFirestoreEnabled()) {
            return false;
        }

        $payload = $data;
        $payload['created_at'] = $payload['created_at'] ?? now();
        $payload['updated_at'] = $payload['updated_at'] ?? now();

        return $this->firestore->createDocument('special_needs_requests', null, $payload);
    }

    private function normalizeRow(array $doc): array
    {
        $createdAt = $this->firestore->tsToString($doc['created_at'] ?? null, $doc['_updateTime'] ?? null);
        $fromAddress = (string) ($doc['from_address'] ?? '');
        $toAddress = (string) ($doc['to_address'] ?? '');
        $fromLat = $doc['from_lat'] ?? '';
        $fromLng = $doc['from_lng'] ?? '';
        $toLat = $doc['to_lat'] ?? '';
        $toLng = $doc['to_lng'] ?? '';

        return [
            'id' => (string) ($doc['_docId'] ?? ''),
            'created_at' => $createdAt,
            'status' => $this->firestore->safeScalar($doc['status'] ?? ''),
            'name' => $this->firestore->safeScalar($doc['name'] ?? ''),
            'email' => $this->firestore->safeScalar($doc['email'] ?? ''),
            'phone' => $this->firestore->safeScalar($doc['phone'] ?? ''),
            'notes' => $this->firestore->safeScalar($doc['notes'] ?? ''),
            'service_type' => $this->firestore->safeScalar($doc['service_type'] ?? ''),
            'user_id' => $this->firestore->safeScalar($doc['user_id'] ?? ''),
            'from_address' => $fromAddress,
            'to_address' => $toAddress,
            'from_coords' => trim(($fromLat !== '' || $fromLng !== '' ? $fromLat . ',' . $fromLng : '')),
            'to_coords' => trim(($toLat !== '' || $toLng !== '' ? $toLat . ',' . $toLng : '')),
        ];
    }

    private function logFallback(string $reason): void
    {
        $key = 'SPECIAL_NEEDS:' . $reason;
        if (isset(self::$warned[$key])) {
            return;
        }
        self::$warned[$key] = true;
        Log::warning('SPECIAL_NEEDS_FIRESTORE_FALLBACK reason=' . $reason);
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

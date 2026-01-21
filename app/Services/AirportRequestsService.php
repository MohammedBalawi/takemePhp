<?php

namespace App\Services;

use App\Support\FeatureFlags;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AirportRequestsService
{
    private FirestoreRestService $firestore;
    private static array $warned = [];

    public function __construct(FirestoreRestService $firestore)
    {
        $this->firestore = $firestore;
    }

    public function list(int $limit = 200): array
    {
        if (!FeatureFlags::airportRequestsFirestoreEnabled()) {
            throw new \RuntimeException('FIRESTORE_ENABLED=false for airport_requests');
        }

        try {
            $docs = $this->firestore->listDocuments('airport_requests', $limit);
            $rows = [];
            foreach ($docs as $doc) {
                $rows[] = $this->normalizeRow($doc);
            }

            if (env('APP_DEBUG')) {
                Log::debug('AIRPORT_FIRESTORE_QUERY count=' . count($rows));
            }

            return $rows;
        } catch (\Throwable $e) {
            $this->logFallback($this->reasonFromException($e));
            return [];
        }
    }

    public function create(array $data): bool
    {
        if (!FeatureFlags::airportRequestsFirestoreEnabled()) {
            throw new \RuntimeException('FIRESTORE_ENABLED=false for airport_requests');
        }

        $payload = $data;
        $payload['created_at'] = $payload['created_at'] ?? now();
        $payload['updated_at'] = $payload['updated_at'] ?? now();

        return $this->firestore->createDocument('airport_requests', null, $payload);
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
            'driver_time' => $this->firestore->safeScalar($doc['driver_time'] ?? ''),
            'service_type' => $this->firestore->safeScalar($doc['service_type'] ?? ''),
            'from_address' => $fromAddress,
            'to_address' => $toAddress,
            'from_coords' => trim(($fromLat !== '' || $fromLng !== '' ? $fromLat . ',' . $fromLng : '')),
            'to_coords' => trim(($toLat !== '' || $toLng !== '' ? $toLat . ',' . $toLng : '')),
        ];
    }

    private function logFallback(string $reason): void
    {
        $key = 'AIRPORT:' . $reason;
        if (isset(self::$warned[$key])) {
            return;
        }
        self::$warned[$key] = true;
        Log::warning('AIRPORT_FIRESTORE_FALLBACK reason=' . $reason);
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

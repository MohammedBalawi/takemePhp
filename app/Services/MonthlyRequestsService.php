<?php

namespace App\Services;

use App\Support\FeatureFlags;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class MonthlyRequestsService
{
    private FirestoreRestService $firestore;
    private static array $warned = [];

    public function __construct(FirestoreRestService $firestore)
    {
        $this->firestore = $firestore;
    }

    public function listEmployee(int $limit = 200): array
    {
        return $this->listByType('توصيل موظفين', $limit, 'employee');
    }

    public function listSchools(int $limit = 200): array
    {
        return $this->listByType('توصيل مدارس', $limit, 'schools');
    }

    public function create(array $data): bool
    {
        if (!FeatureFlags::monthlyRequestsFirestoreEnabled()) {
            return false;
        }

        $payload = $data;
        $payload['created_at'] = $payload['created_at'] ?? now();
        $payload['updated_at'] = $payload['updated_at'] ?? now();

        return $this->firestore->createDocument('school_monthly_requests', null, $payload);
    }

    private function listByType(string $serviceType, int $limit, string $logType): array
    {
        if (!FeatureFlags::monthlyRequestsFirestoreEnabled()) {
            return config($logType === 'employee' ? 'mock_data.mock_school_monthly_employee' : 'mock_data.mock_school_monthly_schools', []);
        }

        try {
            $docs = $this->firestore->listDocuments('school_monthly_requests', $limit);
            $rows = [];
            foreach ($docs as $doc) {
                $type = (string) ($doc['service_type'] ?? '');
                if ($type !== $serviceType) {
                    continue;
                }
                $rows[] = $this->normalizeRow($doc);
            }

            if (env('APP_DEBUG')) {
                Log::debug('MONTHLY_FIRESTORE_QUERY type=' . $logType . ' count=' . count($rows));
            }

            return $rows;
        } catch (\Throwable $e) {
            $this->logFallback('MONTHLY', $this->reasonFromException($e));
            return config($logType === 'employee' ? 'mock_data.mock_school_monthly_employee' : 'mock_data.mock_school_monthly_schools', []);
        }
    }

    private function normalizeRow(array $doc): array
    {
        $createdAt = $this->firestore->tsToString($doc['created_at'] ?? null, $doc['_updateTime'] ?? null);
        $shifts = $doc['shifts'] ?? [];
        $shiftsText = is_array($shifts) ? json_encode($shifts, JSON_UNESCAPED_UNICODE) : (string) $shifts;

        $homeAddress = (string) ($doc['home_address'] ?? '');
        $homeLat = $doc['home_lat'] ?? '';
        $homeLng = $doc['home_lng'] ?? '';
        $destAddress = (string) ($doc['dest_address'] ?? '');
        $destLat = $doc['dest_lat'] ?? '';
        $destLng = $doc['dest_lng'] ?? '';

        return [
            'id' => (string) ($doc['_docId'] ?? ''),
            'created_at' => $createdAt,
            'service_type' => $this->firestore->safeScalar($doc['service_type'] ?? ''),
            'status' => $this->firestore->safeScalar($doc['status'] ?? ''),
            'phone' => $this->firestore->safeScalar($doc['phone'] ?? ''),
            'notes' => $this->firestore->safeScalar($doc['notes'] ?? ''),
            'persons' => $this->firestore->safeScalar($doc['persons'] ?? ''),
            'is_shift_work' => $this->firestore->safeScalar($doc['is_shift_work'] ?? ''),
            'days_count' => $this->firestore->safeScalar($doc['days_count'] ?? ''),
            'home' => trim($homeAddress . ($homeLat !== '' || $homeLng !== '' ? ' (' . $homeLat . ',' . $homeLng . ')' : '')),
            'dest' => trim($destAddress . ($destLat !== '' || $destLng !== '' ? ' (' . $destLat . ',' . $destLng . ')' : '')),
            'driver_arrival_time' => $this->firestore->safeScalar($doc['driver_arrival_time'] ?? ''),
            'start_time' => $this->firestore->safeScalar($doc['start_time'] ?? ''),
            'end_time' => $this->firestore->safeScalar($doc['end_time'] ?? ''),
            'shifts_text' => $shiftsText,
        ];
    }

    private function logFallback(string $feature, string $reason): void
    {
        $key = $feature . ':' . $reason;
        if (isset(self::$warned[$key])) {
            return;
        }
        self::$warned[$key] = true;
        Log::warning('MONTHLY_FIRESTORE_FALLBACK reason=' . $reason);
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

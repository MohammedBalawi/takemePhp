<?php

namespace App\Services;

use App\Support\FeatureFlags;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DashboardIncomeService
{
    private FirestoreRestService $firestore;
    private static array $warned = [];

    public function __construct(FirestoreRestService $firestore)
    {
        $this->firestore = $firestore;
    }

    public function monthlyIncomeSeries(int $year): array
    {
        $series = array_fill(1, 12, 0.0);
        if (!FeatureFlags::firestoreEnabled()) {
            throw new \RuntimeException('FIRESTORE_ENABLED=false for dashboard income');
        }

        try {
            $docs = $this->firestore->listDocuments('rides', 500);
            foreach ($docs as $doc) {
                $createdAt = $doc['createdAt'] ?? $doc['created_at'] ?? $doc['_updateTime'] ?? null;
                if (!$createdAt) {
                    continue;
                }
                $dt = Carbon::parse($createdAt);
                if ((int) $dt->format('Y') !== $year) {
                    continue;
                }
                $month = (int) $dt->format('n');
                $total = $doc['pricing']['total'] ?? $doc['fare']['total'] ?? $doc['fare'] ?? 0;
                if (is_array($total)) {
                    $total = 0;
                }
                $series[$month] += (float) $total;
            }
        } catch (\Throwable $e) {
            $this->logFallback($this->reasonFromException($e));
        }

        return $series;
    }

    private function logFallback(string $reason): void
    {
        $key = 'DASH_INCOME:' . $reason;
        if (isset(self::$warned[$key])) {
            return;
        }
        self::$warned[$key] = true;
        Log::warning('FIRESTORE_FALLBACK feature=DASH_INCOME reason=' . $reason);
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

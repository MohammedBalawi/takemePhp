<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Support\FeatureFlags;

class SosBellService
{
    private FirestoreRestService $firestore;
    private static array $warned = [];

    public function __construct(FirestoreRestService $firestore)
    {
        $this->firestore = $firestore;
    }

    public function hasUnreadSos(): bool
    {
        if (!FeatureFlags::firestoreEnabled()) {
            throw new \RuntimeException('FIRESTORE_ENABLED=false for sos alerts');
        }

        try {
            $lastSeen = $this->getLastSeenAt();
            $docs = $this->firestore->listDocuments('sos_alerts', 200);
            foreach ($docs as $doc) {
                $status = strtolower((string) ($doc['status'] ?? ''));
                if ($status !== 'open') {
                    continue;
                }
                $createdAt = $doc['createdAt'] ?? $doc['created_at'] ?? $doc['_updateTime'] ?? null;
                if (!$createdAt) {
                    return true;
                }
                if ($lastSeen === null) {
                    return true;
                }
                if (strtotime((string) $createdAt) > strtotime((string) $lastSeen)) {
                    return true;
                }
            }
        } catch (\Throwable $e) {
            $this->logFallback($this->reasonFromException($e));
        }

        return false;
    }

    public function markSeen(): void
    {
        if (!FeatureFlags::firestoreEnabled()) {
            throw new \RuntimeException('FIRESTORE_ENABLED=false for sos alerts');
        }

        $latest = $this->getLatestSosTimestamp();
        $key = $this->cacheKey();
        Cache::put($key, $latest ?? now()->toIso8601String(), now()->addDays(7));
    }

    private function getLastSeenAt(): ?string
    {
        $key = $this->cacheKey();
        $cached = Cache::get($key);
        return is_string($cached) ? $cached : null;
    }

    private function getLatestSosTimestamp(): ?string
    {
        try {
            $docs = $this->firestore->listDocuments('sos_alerts', 200);
            $latest = null;
            foreach ($docs as $doc) {
                $createdAt = $doc['createdAt'] ?? $doc['created_at'] ?? $doc['_updateTime'] ?? null;
                if (!$createdAt) {
                    continue;
                }
                if ($latest === null || strtotime((string) $createdAt) > strtotime((string) $latest)) {
                    $latest = (string) $createdAt;
                }
            }
            return $latest;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function cacheKey(): string
    {
        $adminId = session('firebase_email', session('admin.email', 'admin'));
        return 'sos:last_seen:' . $adminId;
    }

    private function logFallback(string $reason): void
    {
        $key = 'SOS_BELL:' . $reason;
        if (isset(self::$warned[$key])) {
            return;
        }
        self::$warned[$key] = true;
        Log::warning('FIRESTORE_FALLBACK feature=SOS_BELL reason=' . $reason);
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

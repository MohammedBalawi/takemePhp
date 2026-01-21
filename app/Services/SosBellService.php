<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
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
            return false;
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
            return;
        }

        $this->firestore->patchDocumentPath('admin_meta/sos_seen/primary', [
            'lastSeenAt' => now(),
        ]);
    }

    private function getLastSeenAt(): ?string
    {
        $doc = $this->firestore->getDocumentPath('admin_meta/sos_seen/primary');
        if (!is_array($doc) || !isset($doc['fields'])) {
            return null;
        }
        $fields = $this->firestore->decodeDocumentFields($doc);
        return is_array($fields) ? ($fields['lastSeenAt'] ?? null) : null;
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

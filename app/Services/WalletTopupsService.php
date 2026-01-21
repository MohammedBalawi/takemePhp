<?php

namespace App\Services;

use App\Support\FeatureFlags;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class WalletTopupsService
{
    private FirestoreRestService $firestore;
    private static array $warned = [];

    public function __construct(FirestoreRestService $firestore)
    {
        $this->firestore = $firestore;
    }

    public function listByType(string $type): array
    {
        if (!FeatureFlags::walletTopupsFirestoreEnabled()) {
            return config('mock_data.wallet_topups', []);
        }

        $type = strtolower(trim($type));
        if ($type === '') {
            $type = 'all';
        }

        try {
            $docs = $this->firestore->listDocuments('wallet_topups', 500);
            $rows = [];
            $counts = [
                'pending' => 0,
                'approved' => 0,
                'decline' => 0,
            ];

            foreach ($docs as $doc) {
                $row = $this->mapRow($doc);
                $status = strtolower((string) ($row['status'] ?? ''));
                if ($status === 'pending') {
                    $counts['pending']++;
                } elseif ($status === 'approved') {
                    $counts['approved']++;
                } elseif ($status === 'decline' || $status === 'declined') {
                    $counts['decline']++;
                }

                if ($this->matchesType($status, $type)) {
                    $rows[] = $row;
                }
            }

            usort($rows, function ($a, $b) {
                return $this->toSortKey($b['created_at'] ?? '') <=> $this->toSortKey($a['created_at'] ?? '');
            });

            if (env('APP_DEBUG')) {
                Log::debug('WALLET_TOPUPS_FIRESTORE_LIST type=' . $type . ' total=' . count($docs) . ' pending=' . $counts['pending'] . ' approved=' . $counts['approved'] . ' decline=' . $counts['decline']);
            }

            return $rows;
        } catch (\Throwable $e) {
            $this->logFallback($this->reasonFromException($e));
            return config('mock_data.wallet_topups', []);
        }
    }

    public function updateStatus(string $docId, string $newStatus): bool
    {
        if (!FeatureFlags::walletTopupsFirestoreEnabled()) {
            return false;
        }

        $docId = trim($docId);
        $newStatus = strtolower(trim($newStatus));
        if ($docId === '' || !in_array($newStatus, ['approved', 'decline'], true)) {
            return false;
        }

        $ok = $this->firestore->patchDocumentTyped('wallet_topups', $docId, [
            'status' => $newStatus,
            'updatedAt' => now(),
        ]);

        if (env('APP_DEBUG')) {
            Log::debug('WALLET_TOPUPS_STATUS_UPDATE id=' . $docId . ' status=' . $newStatus . ' ok=' . ($ok ? '1' : '0'));
        }

        return $ok;
    }

    private function mapRow(array $doc): array
    {
        $createdAt = $this->firestore->tsToString(
            $doc['createdAt'] ?? $doc['created_at'] ?? null,
            $doc['_updateTime'] ?? null
        );

        $amount = $doc['amount'] ?? 0;
        if (is_array($amount)) {
            $amount = 0;
        }

        $receiptUrl = $doc['receiptUrl'] ?? $doc['receipt_url'] ?? $doc['receiptPath'] ?? $doc['receipt_path'] ?? '';

        return [
            'id' => (string) ($doc['_docId'] ?? ''),
            'uid' => (string) ($doc['uid'] ?? ''),
            'amount' => $amount,
            'note' => $this->firestore->safeScalar($doc['note'] ?? ''),
            'status' => $this->firestore->safeScalar($doc['status'] ?? ''),
            'created_at' => $createdAt,
            'receipt_url' => $this->firestore->safeScalar($receiptUrl),
        ];
    }

    private function matchesType(string $status, string $type): bool
    {
        if ($type === 'all') {
            return true;
        }
        if ($type === 'pending') {
            return $status === 'pending';
        }
        if ($type === 'approved') {
            return $status === 'approved';
        }
        if ($type === 'decline') {
            return $status === 'decline' || $status === 'declined';
        }
        return true;
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

    private function logFallback(string $reason): void
    {
        $key = 'WALLET_TOPUPS:' . $reason;
        if (isset(self::$warned[$key])) {
            return;
        }
        self::$warned[$key] = true;
        Log::warning('FIRESTORE_FALLBACK feature=WALLET_TOPUPS reason=' . $reason);
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

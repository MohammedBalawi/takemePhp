<?php

namespace App\Services;

use App\Support\FeatureFlags;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class OffersService
{
    private FirestoreRestService $firestore;
    private static array $driverCache = [];
    private static array $offerCache = [];
    private static array $bidsCache = [];
    private static array $warned = [];

    public function __construct(FirestoreRestService $firestore)
    {
        $this->firestore = $firestore;
    }

    public function listOffers(): array
    {
        if (!FeatureFlags::offersFirestoreEnabled()) {
            return config('mock_data.offers', []);
        }

        try {
            $docs = $this->firestore->listDocuments('offers', 500);
            $rows = [];
            $ids = [];

            foreach ($docs as $doc) {
                $id = (string) ($doc['_docId'] ?? $doc['id'] ?? $doc['rideId'] ?? '');
                $ids[] = $id;
                $rows[] = $this->mapOfferRow($doc, $id);
            }

            usort($rows, function ($a, $b) {
                return $this->toSortKey($b['submittedAt'] ?? '') <=> $this->toSortKey($a['submittedAt'] ?? '');
            });

            if (env('APP_DEBUG')) {
                Log::debug('OFFERS_FIRESTORE_LIST total=' . count($rows) . ' firstIds=' . implode(',', array_slice($ids, 0, 5)));
            }

            return $rows;
        } catch (\Throwable $e) {
            $this->logFallback('OFFERS', $this->reasonFromException($e));
            return config('mock_data.offers', []);
        }
    }

    public function listOfferBidders(string $offerId): array
    {
        if (!FeatureFlags::offersFirestoreEnabled()) {
            return config('mock_data.offer_bidders', []);
        }

        $offerId = trim($offerId);
        if ($offerId === '') {
            return [];
        }

        try {
            $offer = $this->getOffer($offerId);
            if ($offer === null) {
                return [];
            }

            $driverUids = $this->collectBidderUids($offerId, $offer);
            $rows = [];
            foreach ($driverUids as $uid) {
                $driver = $this->getDriver($uid);
                if ($driver === null) {
                    $rows[] = [
                        'uid' => $uid,
                        'name' => $uid,
                        'phone' => '-',
                        'is_online' => false,
                        'is_available' => false,
                    ];
                    continue;
                }

                $rows[] = [
                    'uid' => $uid,
                    'name' => $this->safeScalar($driver['name'] ?? $driver['driverName'] ?? $driver['full_name'] ?? $uid),
                    'phone' => $this->safeScalar($driver['phone'] ?? $driver['contactNumber'] ?? $driver['mobile'] ?? '-'),
                    'is_online' => (bool) ($driver['isOnline'] ?? $driver['online'] ?? data_get($driver, 'availability.online', false)),
                    'is_available' => (bool) ($driver['isAvailable'] ?? $driver['available'] ?? data_get($driver, 'availability.isAvailable', false)),
                ];
            }

            if (env('APP_DEBUG')) {
                Log::debug('OFFERS_BIDDERS offerId=' . $offerId . ' count=' . count($rows));
            }

            return $rows;
        } catch (\Throwable $e) {
            $this->logFallback('OFFERS', $this->reasonFromException($e));
            return config('mock_data.offer_bidders', []);
        }
    }

    public function approveBidder(string $offerId, string $driverUid): bool
    {
        if (!FeatureFlags::offersFirestoreEnabled()) {
            return false;
        }

        $offerId = trim($offerId);
        $driverUid = trim($driverUid);
        if ($offerId === '' || $driverUid === '') {
            return false;
        }

        $fields = [
            'assignedDriverUid' => $driverUid,
            'approvedAt' => now(),
            'updatedAt' => now(),
            'status' => 'assigned',
        ];

        $ok = $this->firestore->patchDocumentTyped('offers', $offerId, $fields);

        if (env('APP_DEBUG')) {
            Log::debug('OFFERS_APPROVE offerId=' . $offerId . ' driverUid=' . $driverUid . ' ok=' . ($ok ? '1' : '0'));
        }

        return $ok;
    }

    private function mapOfferRow(array $doc, string $id): array
    {
        $pickupAddress = data_get($doc, 'pickup.address')
            ?? data_get($doc, 'pickupAddress')
            ?? data_get($doc, 'pickup_address')
            ?? '';
        $dropoffAddress = data_get($doc, 'dropoff.address')
            ?? data_get($doc, 'dropoffAddress')
            ?? data_get($doc, 'dropoff_address')
            ?? '';

        $basePrice = $doc['basePrice'] ?? $doc['base_price'] ?? 0;
        if (is_array($basePrice)) {
            $basePrice = 0;
        }

        $discountPercent = $doc['discountPercent'] ?? $doc['discount_percent'] ?? 0;
        if (is_array($discountPercent)) {
            $discountPercent = 0;
        }

        $submittedAt = $this->firestore->tsToString(
            $doc['submittedAt'] ?? $doc['submitted_at'] ?? $doc['createdAt'] ?? $doc['created_at'] ?? null,
            $doc['_updateTime'] ?? null
        );

        $updatedAt = $this->firestore->tsToString(
            $doc['updatedAt'] ?? $doc['updated_at'] ?? null,
            $doc['_updateTime'] ?? null
        );

        $bidsCount = $this->getBidsCount($id, $doc);

        return [
            'id' => $id,
            'title' => $this->safeScalar($doc['title'] ?? ''),
            'riderName' => $this->safeScalar($doc['riderName'] ?? $doc['rider_name'] ?? ''),
            'riderPhone' => $this->safeScalar($doc['riderPhone'] ?? $doc['rider_phone'] ?? ''),
            'basePrice' => $basePrice,
            'currency' => $this->safeScalar($doc['currency'] ?? 'SAR'),
            'discountPercent' => $discountPercent,
            'pickupAddress' => $this->safeScalar($pickupAddress),
            'dropoffAddress' => $this->safeScalar($dropoffAddress),
            'status' => $this->safeScalar($doc['status'] ?? ''),
            'submittedAt' => $submittedAt,
            'updatedAt' => $updatedAt,
            'assignedDriverUid' => $this->safeScalar($doc['assignedDriverUid'] ?? ''),
            'bidsCount' => $bidsCount,
        ];
    }

    private function getBidsCount(string $offerId, array $doc): int
    {
        if ($offerId === '') {
            return 0;
        }

        if (isset(self::$bidsCache[$offerId])) {
            return self::$bidsCache[$offerId];
        }

        $count = 0;
        $candidateUids = $doc['candidateDriverUids'] ?? null;
        if (is_array($candidateUids)) {
            $count = count($candidateUids);
        } elseif (isset($doc['bids']) && is_array($doc['bids'])) {
            $count = count($doc['bids']);
        } elseif (isset($doc['candidates']) && is_array($doc['candidates'])) {
            $count = count($doc['candidates']);
        } else {
            $bids = $this->firestore->listSubDocuments('offers/' . $offerId . '/bids', 500);
            $count = count($bids);
        }

        self::$bidsCache[$offerId] = $count;

        if (env('APP_DEBUG')) {
            Log::debug('OFFERS_BIDS_COUNT offerId=' . $offerId . ' bids=' . $count);
        }

        return $count;
    }

    private function collectBidderUids(string $offerId, array $offer): array
    {
        $uids = [];
        $candidateUids = $offer['candidateDriverUids'] ?? null;
        if (is_array($candidateUids)) {
            foreach ($candidateUids as $uid) {
                if (is_string($uid) && $uid !== '') {
                    $uids[] = $uid;
                }
            }
        }

        $bids = $offer['bids'] ?? null;
        if (is_array($bids)) {
            foreach ($bids as $bid) {
                $uid = is_array($bid) ? ($bid['driverUid'] ?? $bid['uid'] ?? $bid['driver_id'] ?? null) : null;
                if (is_string($uid) && $uid !== '') {
                    $uids[] = $uid;
                }
            }
        }

        $candidates = $offer['candidates'] ?? null;
        if (is_array($candidates)) {
            foreach ($candidates as $candidate) {
                $uid = is_array($candidate) ? ($candidate['driverUid'] ?? $candidate['uid'] ?? $candidate['driver_id'] ?? null) : null;
                if (is_string($uid) && $uid !== '') {
                    $uids[] = $uid;
                }
            }
        }

        if (count($uids) === 0) {
            $subBids = $this->firestore->listSubDocuments('offers/' . $offerId . '/bids', 500);
            foreach ($subBids as $bid) {
                $uid = $bid['driverUid'] ?? $bid['uid'] ?? $bid['driver_id'] ?? null;
                if (is_string($uid) && $uid !== '') {
                    $uids[] = $uid;
                }
            }
        }

        $uids = array_values(array_unique(array_filter($uids)));
        return $uids;
    }

    private function getOffer(string $offerId): ?array
    {
        if (isset(self::$offerCache[$offerId])) {
            return self::$offerCache[$offerId];
        }

        $doc = $this->firestore->getDocumentFields('offers', $offerId);
        if (!is_array($doc)) {
            self::$offerCache[$offerId] = null;
            return null;
        }

        self::$offerCache[$offerId] = $doc;
        return $doc;
    }

    private function getDriver(string $uid): ?array
    {
        if ($uid === '') {
            return null;
        }
        if (isset(self::$driverCache[$uid])) {
            return self::$driverCache[$uid];
        }

        $driver = $this->firestore->getDocumentFields('drivers', $uid);
        if (!is_array($driver)) {
            $driver = $this->firestore->getDocumentFields('drivers_active', $uid);
        }

        self::$driverCache[$uid] = is_array($driver) ? $driver : null;
        return self::$driverCache[$uid];
    }

    private function safeScalar($value, string $default = '')
    {
        return $this->firestore->safeScalar($value, $default);
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

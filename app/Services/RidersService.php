<?php

namespace App\Services;

use App\Support\FeatureFlags;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class RidersService
{
    private FirestoreRestService $firestore;
    private static array $cache = [];
    private static array $logged = [];

    public function __construct(FirestoreRestService $firestore)
    {
        $this->firestore = $firestore;
    }

    public function listRiders(): array
    {
        $mock = config('mock_data.riders', []);
        if (!(FeatureFlags::firestoreEnabled() && FeatureFlags::featureEnabled('RIDERS'))) {
            return is_array($mock) ? $mock : [];
        }

        if (isset(self::$cache['riders.list'])) {
            return self::$cache['riders.list'];
        }

        try {
            $documents = $this->firestore->listDocuments('users', 500);
            if (count($documents) === 0) {
                $this->logFallbackOnce(new \RuntimeException('empty_docs'));
                return is_array($mock) ? $mock : [];
            }

            $mapped = [];
            foreach ($documents as $doc) {
                $fields = $doc;
                $userType = $fields['user_type'] ?? $fields['userType'] ?? '';
                if ($userType !== 'rider') {
                    continue;
                }
                $mapped[] = $this->mapRiderFromFields($doc, $fields);
            }

            usort($mapped, function ($a, $b) {
                $aTs = $this->timestampToSortValue($a['created_at'] ?? '');
                $bTs = $this->timestampToSortValue($b['created_at'] ?? '');
                return $bTs <=> $aTs;
            });

            if (env('APP_DEBUG')) {
                $uids = array_slice(array_map(function ($rider) {
                    return $rider['uid'] ?? '';
                }, $mapped), 0, 2);
                logger()->debug('RIDERS_FIRESTORE_LIST totalDocs=' . count($documents) . ' riders=' . count($mapped) . ' sample=' . implode(',', $uids));
                if (count($documents) > 0 && count($mapped) === 0) {
                    $firstKeys = array_keys($documents[0]['fields'] ?? []);
                    logger()->debug('RIDERS_FIRESTORE_ZERO_MATCH firstDocKeys=' . implode(',', $firstKeys));
                }
            }

            self::$cache['riders.list'] = $mapped;
            return $mapped;
        } catch (\Throwable $e) {
            $this->logFallbackOnce($e);
            return is_array($mock) ? $mock : [];
        }
    }

    public function createRider(array $payload): bool
    {
        if (!FeatureFlags::shouldUseFirestore('RIDERS')) {
            return true;
        }

        try {
            $uid = $payload['uid'] ?? Str::uuid()->toString();
            $now = Carbon::now('UTC');

            $fields = [
                'uid' => $uid,
                'first_name' => (string) ($payload['first_name'] ?? ''),
                'last_name' => (string) ($payload['last_name'] ?? ''),
                'email' => (string) ($payload['email'] ?? ''),
                'phone' => (string) ($payload['phone'] ?? ''),
                'username' => (string) ($payload['username'] ?? ''),
                'address' => (string) ($payload['address'] ?? ''),
                'user_type' => 'rider',
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (!empty($payload['password'])) {
                $fields['password_hash'] = Hash::make((string) $payload['password']);
            }

            return $this->firestore->patchDocumentTyped('users', $uid, $fields);
        } catch (\Throwable $e) {
            $this->logFallbackOnce($e);
            return false;
        }
    }

    private function mapRiderFromFields(array $doc, array $fields): array
    {
        $uid = $fields['uid'] ?? ($doc['_docId'] ?? '');
        $firstName = $fields['first_name'] ?? '';
        $lastName = $fields['last_name'] ?? '';
        $address = $fields['address'] ?? $fields['usersaddress'] ?? '';
        $createdAt = $fields['created_at'] ?? $fields['createdAt'] ?? $fields['updated_at'] ?? ($doc['_updateTime'] ?? '');

        return [
            'uid' => $uid,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $fields['email'] ?? '',
            'phone' => $fields['phone'] ?? '',
            'username' => $fields['username'] ?? '',
            'address' => $address,
            'created_at' => $this->formatTimestamp($createdAt),
            'status' => $fields['status'] ?? '',
        ];
    }

    private function formatTimestamp($value): string
    {
        if (!$value) {
            return '';
        }

        try {
            return Carbon::parse($value)->format('Y-m-d H:i:s');
        } catch (\Throwable $e) {
            return (string) $value;
        }
    }

    private function timestampToSortValue($value): int
    {
        if (!$value) {
            return 0;
        }

        try {
            return Carbon::parse($value)->getTimestamp();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function decodeFields(array $fields): array
    {
        if (!is_array($fields)) {
            return [];
        }

        $decoded = [];
        foreach ($fields as $key => $value) {
            if (!is_array($value)) {
                continue;
            }
            $decodedValue = $this->decodeFieldValue($value);
            if ($decodedValue !== null) {
                $decoded[$key] = $decodedValue;
            }
        }

        return $decoded;
    }

    private function decodeFieldValue(array $field)
    {
        if (isset($field['stringValue'])) {
            return (string) $field['stringValue'];
        }
        if (array_key_exists('integerValue', $field)) {
            return (int) $field['integerValue'];
        }
        if (array_key_exists('doubleValue', $field)) {
            return (float) $field['doubleValue'];
        }
        if (array_key_exists('booleanValue', $field)) {
            return (bool) $field['booleanValue'];
        }
        if (isset($field['timestampValue'])) {
            return (string) $field['timestampValue'];
        }
        if (isset($field['arrayValue']['values']) && is_array($field['arrayValue']['values'])) {
            $items = [];
            foreach ($field['arrayValue']['values'] as $value) {
                if (is_array($value)) {
                    $items[] = $this->decodeFieldValue($value);
                }
            }
            return $items;
        }
        if (isset($field['mapValue']['fields']) && is_array($field['mapValue']['fields'])) {
            return $this->decodeFields($field['mapValue']['fields']);
        }

        return null;
    }

    private function docIdFromName(string $name): string
    {
        if ($name === '') {
            return '';
        }
        $parts = explode('/', $name);
        return end($parts) ?: '';
    }

    private function logFallbackOnce(\Throwable $e): void
    {
        if (isset(self::$logged['RIDERS'])) {
            return;
        }

        $reason = stripos($e->getMessage(), 'timeout') !== false ? 'timeout' : 'exception';
        logger()->warning('FIRESTORE_FALLBACK feature=RIDERS reason=' . $reason . ' message=' . $e->getMessage());
        self::$logged['RIDERS'] = true;
    }
}

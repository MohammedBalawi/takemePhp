<?php

namespace App\Services;

use Google\Auth\Credentials\ServiceAccountCredentials;
use GuzzleHttp\Client;
use App\Support\FeatureFlags;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class FirestoreRestService
{
    private Client $client;
    private static ?Client $sharedClient = null;
    private static array $warned = [];
    private static bool $hadFailure = false;
    private static array $requestCache = [];
    private ?string $projectId;
    private ?string $credentialsPath;
    private static array $frontendCache = [];
    private static ?array $appSettingsCache = null;

    public function __construct()
    {
        if (self::$sharedClient === null) {
            self::$sharedClient = new Client([
                'connect_timeout' => (float) env('FIRESTORE_CONNECT_TIMEOUT', 2),
                'timeout' => (float) env('FIRESTORE_HTTP_TIMEOUT', 6),
                'http_errors' => false,
            ]);
        }
        $this->client = self::$sharedClient;
        $this->projectId = config('firebase.project_id');
        $this->credentialsPath = config('firebase.credentials_path');
    }

    public function getDocument(string $collection, string $documentId): ?array
    {
        if (!FeatureFlags::firestoreEnabled()) {
            return null;
        }
        $key = 'getDocument:' . $collection . '/' . $documentId;
        if (isset(self::$requestCache[$key])) {
            return self::$requestCache[$key];
        }
        try {
            $baseUrl = $this->baseUrl();
            $token = $this->getAccessToken();
            if ($baseUrl === null || $token === null) {
                $this->warnOnce($key, 'missing_base_url_or_token');
                return null;
            }

            $url = $baseUrl . '/' . rawurlencode($collection) . '/' . rawurlencode($documentId);
            $response = $this->request('GET', $url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                ],
            ], $key);

            if (!$this->isSuccessfulResponse($response)) {
                $this->warnOnce($key, 'non_success_response');
                return null;
            }

            $data = json_decode((string) $response->getBody(), true);
            $result = is_array($data) ? $data : null;
            self::$requestCache[$key] = $result;
            return $result;
        } catch (\Throwable $e) {
            $this->warnOnce($key, $e->getMessage());
            return null;
        }
    }

    public function listDocuments(string $collection, int $pageSize = 200): array
    {
        if (!FeatureFlags::firestoreEnabled()) {
            return [];
        }
        $key = 'listDocuments:' . $collection . ':' . $pageSize;
        if (isset(self::$requestCache[$key])) {
            return self::$requestCache[$key];
        }

        try {
            $baseUrl = $this->baseUrl();
            $token = $this->getAccessToken();
            if ($baseUrl === null || $token === null) {
                $this->warnOnce($key, 'missing_base_url_or_token');
                return [];
            }

            $url = $baseUrl . '/' . rawurlencode($collection) . '?pageSize=' . max(1, $pageSize);
            $response = $this->request('GET', $url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                ],
            ], $key);

            if (!$this->isSuccessfulResponse($response)) {
                $this->warnOnce($key, 'non_success_response');
                return [];
            }

            $payload = json_decode((string) $response->getBody(), true);
            if (!is_array($payload)) {
                return [];
            }

            $documents = $payload['documents'] ?? [];
            $decoded = [];
            foreach ($documents as $doc) {
                if (!is_array($doc)) {
                    continue;
                }
                $fields = $this->decodeDocumentFields($doc);
                $fields['_docPath'] = $doc['name'] ?? '';
                $fields['_docId'] = $this->docIdFromName((string) ($doc['name'] ?? ''));
                $fields['_updateTime'] = $doc['updateTime'] ?? null;
                $decoded[] = $fields;
            }

            self::$requestCache[$key] = $decoded;
            return $decoded;
        } catch (\Throwable $e) {
            $this->warnOnce($key, $e->getMessage());
            return [];
        }
    }

    public function listSubDocuments(string $path, int $pageSize = 200): array
    {
        if (!FeatureFlags::firestoreEnabled()) {
            return [];
        }
        $path = trim($path, '/');
        if ($path === '') {
            return [];
        }
        $key = 'listSubDocuments:' . $path . ':' . $pageSize;
        if (isset(self::$requestCache[$key])) {
            return self::$requestCache[$key];
        }

        try {
            $baseUrl = $this->baseUrl();
            $token = $this->getAccessToken();
            if ($baseUrl === null || $token === null) {
                $this->warnOnce($key, 'missing_base_url_or_token');
                return [];
            }

            $url = $baseUrl . '/' . $path . '?pageSize=' . max(1, $pageSize);
            $response = $this->request('GET', $url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                ],
            ], $key);

            if (!$this->isSuccessfulResponse($response)) {
                $this->warnOnce($key, 'non_success_response');
                return [];
            }

            $payload = json_decode((string) $response->getBody(), true);
            if (!is_array($payload)) {
                return [];
            }

            $documents = $payload['documents'] ?? [];
            $decoded = [];
            foreach ($documents as $doc) {
                if (!is_array($doc)) {
                    continue;
                }
                $fields = $this->decodeDocumentFields($doc);
                $fields['_docPath'] = $doc['name'] ?? '';
                $fields['_docId'] = $this->docIdFromName((string) ($doc['name'] ?? ''));
                $fields['_updateTime'] = $doc['updateTime'] ?? null;
                $decoded[] = $fields;
            }

            self::$requestCache[$key] = $decoded;
            return $decoded;
        } catch (\Throwable $e) {
            $this->warnOnce($key, $e->getMessage());
            return [];
        }
    }

    public function getDocumentPath(string $path): ?array
    {
        if (!FeatureFlags::firestoreEnabled()) {
            return null;
        }
        $path = trim($path, '/');
        if ($path === '') {
            return null;
        }
        $key = 'getDocumentPath:' . $path;
        if (isset(self::$requestCache[$key])) {
            return self::$requestCache[$key];
        }
        try {
            $baseUrl = $this->baseUrl();
            $token = $this->getAccessToken();
            if ($baseUrl === null || $token === null) {
                $this->warnOnce($key, 'missing_base_url_or_token');
                return null;
            }

            $url = $baseUrl . '/' . $path;
            $response = $this->request('GET', $url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                ],
            ], $key);

            if (!$this->isSuccessfulResponse($response)) {
                $this->warnOnce($key, 'non_success_response');
                return null;
            }

            $data = json_decode((string) $response->getBody(), true);
            $result = is_array($data) ? $data : null;
            self::$requestCache[$key] = $result;
            return $result;
        } catch (\Throwable $e) {
            $this->warnOnce($key, $e->getMessage());
            return null;
        }
    }

    public function patchDocumentPath(string $path, array $fields): bool
    {
        if (!FeatureFlags::firestoreEnabled()) {
            return false;
        }
        $path = trim($path, '/');
        if ($path === '') {
            return false;
        }
        $key = 'patchDocumentPath:' . $path;
        try {
            $baseUrl = $this->baseUrl();
            $token = $this->getAccessToken();
            if ($baseUrl === null || $token === null) {
                $this->warnOnce($key, 'missing_base_url_or_token');
                return false;
            }

            $payloadFields = $this->encodeFields($fields);
            if (count($payloadFields) === 0) {
                return false;
            }

            $fieldPaths = array_keys($payloadFields);
            $updateMask = implode('&updateMask.fieldPaths=', array_map('rawurlencode', $fieldPaths));
            $url = $baseUrl . '/' . $path . '?updateMask.fieldPaths=' . $updateMask;

            $response = $this->request('PATCH', $url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'fields' => $payloadFields,
                ],
            ], $key);

            return $this->isSuccessfulResponse($response);
        } catch (\Throwable $e) {
            $this->warnOnce($key, $e->getMessage());
            return false;
        }
    }

    public function listCollection(string $collection, array $where = [], int $limit = 200): array
    {
        if (!FeatureFlags::firestoreEnabled()) {
            return [];
        }

        if (count($where) === 0) {
            return $this->listDocuments($collection, $limit);
        }

        $filters = [];
        foreach ($where as $condition) {
            if (!isset($condition['field'], $condition['op'], $condition['value'])) {
                continue;
            }
            $filters[] = $condition;
        }

        if (count($filters) === 0) {
            return $this->listDocuments($collection, $limit);
        }

        return $this->runStructuredQuery($collection, $filters, $limit);
    }

    public function deleteDocument(string $collection, string $documentId): bool
    {
        if (!FeatureFlags::firestoreEnabled()) {
            return false;
        }
        $key = 'deleteDocument:' . $collection . '/' . $documentId;
        try {
            $baseUrl = $this->baseUrl();
            $token = $this->getAccessToken();
            if ($baseUrl === null || $token === null) {
                $this->warnOnce($key, 'missing_base_url_or_token');
                return false;
            }

            $url = $baseUrl . '/' . rawurlencode($collection) . '/' . rawurlencode($documentId);
            $response = $this->request('DELETE', $url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                ],
            ], $key);

            return $this->isSuccessfulResponse($response);
        } catch (\Throwable $e) {
            $this->warnOnce($key, $e->getMessage());
            return false;
        }
    }

    public function queryWhereEqual(string $collection, string $field, $value, int $limit = 1): array
    {
        if (!FeatureFlags::firestoreEnabled()) {
            return [];
        }

        $filters = [
            ['field' => $field, 'op' => 'EQUAL', 'value' => $value],
        ];

        return $this->runStructuredQuery($collection, $filters, $limit);
    }

    public function getAdminByEmail(string $email): ?array
    {
        if (!FeatureFlags::firestoreEnabled()) {
            return null;
        }

        $email = strtolower(trim($email));
        if ($email === '') {
            return null;
        }

        $direct = $this->getDocumentFields('admins', $email);
        if (is_array($direct)) {
            $direct['docId'] = $email;
            if (!isset($direct['email']) || !is_string($direct['email'])) {
                $direct['email'] = $email;
            }
            return $this->normalizeAdmin($direct);
        }

        $results = $this->runStructuredQuery('admins', [
            ['field' => 'email', 'op' => 'EQUAL', 'value' => $email],
        ], 1);

        if (count($results) === 0) {
            return null;
        }

        $admin = $results[0];
        if (!isset($admin['email']) || !is_string($admin['email'])) {
            $admin['email'] = $email;
        }

        if (!isset($admin['docId']) && isset($admin['__id']) && is_string($admin['__id'])) {
            $admin['docId'] = $admin['__id'];
        }

        return $this->normalizeAdmin($admin);
    }

    public function upsertAdmin(string $docId, array $fields): bool
    {
        if (!FeatureFlags::firestoreEnabled()) {
            return false;
        }

        $docId = strtolower(trim($docId));
        if ($docId === '') {
            return false;
        }

        $fields['email'] = strtolower(trim((string) ($fields['email'] ?? $docId)));

        return $this->patchDocumentTyped('admins', $docId, $fields);
    }

    public function runStructuredQuery(string $collection, array $filters = [], int $limit = 10): array
    {
        if (!FeatureFlags::firestoreEnabled()) {
            return [];
        }
        $key = 'runStructuredQuery:' . $collection . ':' . md5(json_encode([$filters, $limit]));
        if (isset(self::$requestCache[$key])) {
            return self::$requestCache[$key];
        }

        try {
            $baseUrl = $this->baseUrl();
            $token = $this->getAccessToken();
            if ($baseUrl === null || $token === null) {
                $this->warnOnce($key, 'missing_base_url_or_token');
                return [];
            }

            $query = [
                'from' => [
                    ['collectionId' => $collection],
                ],
                'limit' => $limit,
            ];

            $filterList = [];
            foreach ($filters as $condition) {
                if (!isset($condition['field'], $condition['op'], $condition['value'])) {
                    continue;
                }
                $value = $condition['value'];
                if (!is_array($value)) {
                    $value = $this->encodeValue($value);
                }
                $filterList[] = [
                    'fieldFilter' => [
                        'field' => ['fieldPath' => $condition['field']],
                        'op' => $condition['op'],
                        'value' => $value,
                    ],
                ];
            }

            if (count($filterList) === 1) {
                $query['where'] = $filterList[0];
            } elseif (count($filterList) > 1) {
                $query['where'] = [
                    'compositeFilter' => [
                        'op' => 'AND',
                        'filters' => $filterList,
                    ],
                ];
            }

            $response = $this->request('POST', $baseUrl . ':runQuery', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'structuredQuery' => $query,
                ],
            ], $key);

            if (!$this->isSuccessfulResponse($response)) {
                $this->warnOnce($key, 'non_success_response');
                return [];
            }

            $payload = json_decode((string) $response->getBody(), true);
            if (!is_array($payload)) {
                return [];
            }

            $results = [];
            foreach ($payload as $row) {
                if (!isset($row['document']['fields']) || !is_array($row['document']['fields'])) {
                    continue;
                }
                $fields = $row['document']['fields'];
                $item = [];
                foreach ($fields as $key => $field) {
                    $value = $this->decodeFieldValue($field);
                    if ($value !== null) {
                        $item[$key] = $value;
                    }
                }
                $name = $row['document']['name'] ?? '';
                if (is_string($name) && $name !== '') {
                    $parts = explode('/', $name);
                    $item['__id'] = end($parts);
                }
                $updateTime = $row['document']['updateTime'] ?? null;
                if (is_string($updateTime) && $updateTime !== '') {
                    $item['__updateTime'] = $updateTime;
                }
                $results[] = $item;
            }

            self::$requestCache[$key] = $results;
            return $results;
        } catch (\Throwable $e) {
            $this->warnOnce($key, $e->getMessage());
            return [];
        }
    }

    public function getDocumentFields(string $collection, string $documentId): ?array
    {
        $key = 'getDocumentFields:' . $collection . '/' . $documentId;
        if (isset(self::$requestCache[$key])) {
            return self::$requestCache[$key];
        }
        $doc = $this->getDocument($collection, $documentId);
        if (!is_array($doc) || !isset($doc['fields']) || !is_array($doc['fields'])) {
            $this->warnOnce($key, 'missing_fields');
            return null;
        }

        $result = [];
        foreach ($doc['fields'] as $key => $field) {
            $value = $this->decodeFieldValue($field);
            if ($value !== null) {
                $result[$key] = $value;
            }
        }

        $result = count($result) > 0 ? $result : null;
        self::$requestCache[$key] = $result;
        return $result;
    }

    public function getSetting(string $type, string $key): ?string
    {
        if (!FeatureFlags::firestoreEnabled()) {
            return null;
        }
        $requestKey = 'getSetting:' . $type . '/' . $key;
        try {
            $doc = $this->getDocument('settings', $type);
            if (!is_array($doc) || !isset($doc['fields'][$key]) || !is_array($doc['fields'][$key])) {
                $this->warnOnce($requestKey, 'missing_fields');
                return null;
            }

            $field = $doc['fields'][$key];
            if (isset($field['stringValue'])) {
                return $field['stringValue'];
            }
            if (isset($field['integerValue'])) {
                return (string) $field['integerValue'];
            }
            if (isset($field['doubleValue'])) {
                return (string) $field['doubleValue'];
            }
            if (isset($field['booleanValue'])) {
                return $field['booleanValue'] ? 'true' : 'false';
            }
            return null;
        } catch (\Throwable $e) {
            $this->warnOnce($requestKey, $e->getMessage());
            return null;
        }
    }

    public function getAppSettings(): ?array
    {
        if (!FeatureFlags::firestoreEnabled()) {
            return null;
        }
        if (self::$appSettingsCache !== null) {
            return self::$appSettingsCache;
        }

        $key = 'getAppSettings:default';
        try {
            $doc = $this->getDocument('app_settings', 'default');
            if (!is_array($doc) || !isset($doc['fields']) || !is_array($doc['fields']) || count($doc['fields']) === 0) {
                $this->warnOnce($key, 'missing_fields');
                self::$appSettingsCache = null;
                return null;
            }

            $result = [];
            foreach ($doc['fields'] as $key => $field) {
                $value = $this->decodeFieldValue($field);
                if ($value !== null) {
                    $result[$key] = $value;
                }
            }

            if (count($result) === 0) {
                self::$appSettingsCache = null;
                return null;
            }

            self::$appSettingsCache = $result;
            return $result;
        } catch (\Throwable $e) {
            $this->warnOnce($key, $e->getMessage());
            self::$appSettingsCache = null;
            return null;
        }
    }

    public function getAppSetting(string $key)
    {
        $settings = $this->getAppSettings();
        return is_array($settings) && array_key_exists($key, $settings) ? $settings[$key] : null;
    }

    public function patchDocument(string $collection, string $documentId, array $fields): bool
    {
        if (!FeatureFlags::firestoreEnabled()) {
            return false;
        }
        $key = 'patchDocument:' . $collection . '/' . $documentId;
        try {
            $baseUrl = $this->baseUrl();
            $token = $this->getAccessToken();
            if ($baseUrl === null || $token === null) {
                $this->warnOnce($key, 'missing_base_url_or_token');
                return false;
            }

            $fieldPaths = array_keys($fields);
            if (count($fieldPaths) === 0) {
                return false;
            }

            $updateMask = implode('&updateMask.fieldPaths=', array_map('rawurlencode', $fieldPaths));
            $url = $baseUrl . '/' . rawurlencode($collection) . '/' . rawurlencode($documentId)
                . '?updateMask.fieldPaths=' . $updateMask;

            $payloadFields = [];
            foreach ($fields as $key => $value) {
                $payloadFields[$key] = ['stringValue' => (string) $value];
            }

            $response = $this->request('PATCH', $url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'fields' => $payloadFields,
                ],
            ], $key);

            return $this->isSuccessfulResponse($response);
        } catch (\Throwable $e) {
            $this->warnOnce($key, $e->getMessage());
            return false;
        }
    }

    public function patchDocumentTyped(string $collection, string $documentId, array $fields): bool
    {
        if (!FeatureFlags::firestoreEnabled()) {
            return false;
        }
        $key = 'patchDocumentTyped:' . $collection . '/' . $documentId;

        try {
            $baseUrl = $this->baseUrl();
            $token = $this->getAccessToken();
            if ($baseUrl === null || $token === null) {
                $this->warnOnce($key, 'missing_base_url_or_token');
                return false;
            }

            $payloadFields = $this->encodeFields($fields);
            if (count($payloadFields) === 0) {
                return false;
            }

            $fieldPaths = array_keys($payloadFields);
            $updateMask = implode('&updateMask.fieldPaths=', array_map('rawurlencode', $fieldPaths));
            $url = $baseUrl . '/' . rawurlencode($collection) . '/' . rawurlencode($documentId)
                . '?updateMask.fieldPaths=' . $updateMask;

            $response = $this->request('PATCH', $url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'fields' => $payloadFields,
                ],
            ], $key);

            return $this->isSuccessfulResponse($response);
        } catch (\Throwable $e) {
            $this->warnOnce($key, $e->getMessage());
            return false;
        }
    }

    public function patchAppSettings(array $fields): bool
    {
        if (!FeatureFlags::firestoreEnabled()) {
            return false;
        }
        $key = 'patchAppSettings:default';
        try {
            $baseUrl = $this->baseUrl();
            $token = $this->getAccessToken();
            if ($baseUrl === null || $token === null) {
                $this->warnOnce($key, 'missing_base_url_or_token');
                return false;
            }

            $payloadFields = $this->encodeFields($fields);
            if (count($payloadFields) === 0) {
                return false;
            }

            $fieldPaths = array_keys($payloadFields);
            $updateMask = implode('&updateMask.fieldPaths=', array_map('rawurlencode', $fieldPaths));
            $url = $baseUrl . '/' . rawurlencode('app_settings') . '/' . rawurlencode('default')
                . '?updateMask.fieldPaths=' . $updateMask;

            $response = $this->request('PATCH', $url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'fields' => $payloadFields,
                ],
            ], $key);

            return $this->isSuccessfulResponse($response);
        } catch (\Throwable $e) {
            $this->warnOnce($key, $e->getMessage());
            return false;
        }
    }

    public function countCollection(string $collection, array $where = []): int
    {
        if (!FeatureFlags::firestoreEnabled()) {
            return 0;
        }
        $key = 'countCollection:' . $collection;
        try {
            $baseUrl = $this->baseUrl();
            $token = $this->getAccessToken();
            if ($baseUrl === null || $token === null) {
                $this->warnOnce($key, 'missing_base_url_or_token');
                return 0;
            }

            $query = [
                'from' => [
                    ['collectionId' => $collection],
                ],
            ];

            $filter = $this->buildFilter($where);
            if ($filter !== null) {
                $query['where'] = $filter;
            }

            $response = $this->request('POST', $baseUrl . ':runAggregationQuery', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'structuredAggregationQuery' => [
                        'structuredQuery' => $query,
                        'aggregations' => [
                            [
                                'alias' => 'count',
                                'count' => new \stdClass(),
                            ],
                        ],
                    ],
                ],
            ], $key);

            if (!$this->isSuccessfulResponse($response)) {
                $this->warnOnce($key, 'non_success_response');
                return 0;
            }

            $payload = json_decode((string) $response->getBody(), true);
            if (!is_array($payload)) {
                return 0;
            }

            foreach ($payload as $row) {
                $count = $row['result']['aggregateFields']['count']['integerValue'] ?? null;
                if ($count !== null) {
                    return (int) $count;
                }
            }
        } catch (\Throwable $e) {
            $this->warnOnce($key, $e->getMessage());
            // fall back below
        }

        $documents = $this->queryCollection($collection, $where, [], 1000, []);
        return count($documents);
    }

    public function queryCollection(string $collection, array $where = [], array $orderBy = [], int $limit = 10, array $select = [], int $offset = 0): array
    {
        if (!FeatureFlags::firestoreEnabled()) {
            return [];
        }
        $key = 'queryCollection:' . $collection . ':' . md5(json_encode([$where, $orderBy, $limit, $select, $offset]));
        if (isset(self::$requestCache[$key])) {
            return self::$requestCache[$key];
        }
        try {
            $baseUrl = $this->baseUrl();
            $token = $this->getAccessToken();
            if ($baseUrl === null || $token === null) {
                $this->warnOnce($key, 'missing_base_url_or_token');
                return [];
            }

            $query = [
                'from' => [
                    ['collectionId' => $collection],
                ],
                'limit' => $limit,
            ];

            if ($offset > 0) {
                $query['offset'] = $offset;
            }

            $filter = $this->buildFilter($where);
            if ($filter !== null) {
                $query['where'] = $filter;
            }

            if (!empty($select)) {
                $query['select'] = [
                    'fields' => array_map(function ($fieldPath) {
                        return ['fieldPath' => $fieldPath];
                    }, $select),
                ];
            }

            if (!empty($orderBy)) {
                $query['orderBy'] = [];
                foreach ($orderBy as $order) {
                    if (!isset($order['field'])) {
                        continue;
                    }
                    $query['orderBy'][] = [
                        'field' => ['fieldPath' => $order['field']],
                        'direction' => $order['direction'] ?? 'ASCENDING',
                    ];
                }
            }

            $response = $this->request('POST', $baseUrl . ':runQuery', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'structuredQuery' => $query,
                ],
            ], $key);

            if (!$this->isSuccessfulResponse($response)) {
                $this->warnOnce($key, 'non_success_response');
                return [];
            }

            $payload = json_decode((string) $response->getBody(), true);
            if (!is_array($payload)) {
                return [];
            }

            $results = [];
            foreach ($payload as $row) {
                if (!isset($row['document']['fields']) || !is_array($row['document']['fields'])) {
                    continue;
                }
                $fields = $row['document']['fields'];
                $item = [];
                foreach ($fields as $key => $field) {
                    $value = $this->decodeFieldValue($field);
                    if ($value !== null) {
                        $item[$key] = $value;
                    }
                }
                $name = $row['document']['name'] ?? '';
                if (is_string($name) && $name !== '') {
                    $parts = explode('/', $name);
                    $item['__id'] = end($parts);
                }
                $updateTime = $row['document']['updateTime'] ?? null;
                if (is_string($updateTime) && $updateTime !== '') {
                    $item['__updateTime'] = $updateTime;
                }
                $results[] = $item;
            }

            self::$requestCache[$key] = $results;
            return $results;
        } catch (\Throwable $e) {
            $this->warnOnce($key, $e->getMessage());
            return [];
        }
    }

    public function sumField(string $collection, string $fieldPath, array $where = []): float
    {
        if (!FeatureFlags::firestoreEnabled()) {
            return 0.0;
        }
        $key = 'sumField:' . $collection . '/' . $fieldPath;
        $total = 0.0;
        $documents = $this->queryCollection($collection, $where, [], 1000, []);
        foreach ($documents as $doc) {
            $value = $this->getValueByPath($doc, $fieldPath);
            if (is_numeric($value)) {
                $total += (float) $value;
            }
        }
        if (self::$hadFailure) {
            $this->warnOnce($key, 'firestore_failure_flagged');
            return 0.0;
        }
        return $total;
    }


    public function getFrontendData(string $type): ?array
    {
        if (!FeatureFlags::firestoreEnabled()) {
            return null;
        }
        if (array_key_exists($type, self::$frontendCache)) {
            return self::$frontendCache[$type];
        }

        try {
            $doc = $this->getDocument('frontend_data', $type);
            if (!is_array($doc) || !isset($doc['fields']) || !is_array($doc['fields']) || count($doc['fields']) === 0) {
                self::$frontendCache[$type] = null;
                return null;
            }

            $result = [];
            foreach ($doc['fields'] as $key => $field) {
                $value = $this->decodeFieldValue($field);
                if ($value !== null) {
                    $result[$key] = $value;
                }
            }

            if (count($result) === 0) {
                self::$frontendCache[$type] = null;
                return null;
            }

            self::$frontendCache[$type] = $result;
            return $result;
        } catch (\Throwable $e) {
            self::$frontendCache[$type] = null;
            return null;
        }
    }

    public function patchFrontendData(string $type, array $fields): bool
    {
        if (!FeatureFlags::firestoreEnabled()) {
            return false;
        }
        try {
            $baseUrl = $this->baseUrl();
            $token = $this->getAccessToken();
            if ($baseUrl === null || $token === null) {
                return false;
            }

            $payloadFields = $this->encodeFields($fields);
            if (count($payloadFields) === 0) {
                return false;
            }

            $fieldPaths = array_keys($payloadFields);
            $updateMask = implode('&updateMask.fieldPaths=', array_map('rawurlencode', $fieldPaths));
            $url = $baseUrl . '/' . rawurlencode('frontend_data') . '/' . rawurlencode($type)
                . '?updateMask.fieldPaths=' . $updateMask;

            $response = $this->request('PATCH', $url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'fields' => $payloadFields,
                ],
            ]);

            return $this->isSuccessfulResponse($response);
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function baseUrl(): ?string
    {
        if (!$this->projectId) {
            $this->warnOnce('firestore:project_id', 'missing_project_id');
            return null;
        }
        return 'https://firestore.googleapis.com/v1/projects/' . $this->projectId . '/databases/(default)/documents';
    }

    private function decodeFieldValue(array $field)
    {
        if (isset($field['stringValue'])) {
            return $field['stringValue'];
        }
        if (isset($field['integerValue'])) {
            return (int) $field['integerValue'];
        }
        if (isset($field['doubleValue'])) {
            return (float) $field['doubleValue'];
        }
        if (isset($field['booleanValue'])) {
            return (bool) $field['booleanValue'];
        }
        if (isset($field['timestampValue'])) {
            return $field['timestampValue'];
        }
        if (isset($field['geoPointValue']) && is_array($field['geoPointValue'])) {
            $lat = $field['geoPointValue']['latitude'] ?? null;
            $lng = $field['geoPointValue']['longitude'] ?? null;
            if ($lat !== null && $lng !== null) {
                return [
                    'lat' => (float) $lat,
                    'lng' => (float) $lng,
                ];
            }
        }
        if (isset($field['mapValue']['fields']) && is_array($field['mapValue']['fields'])) {
            $map = [];
            foreach ($field['mapValue']['fields'] as $key => $value) {
                if (!is_array($value)) {
                    continue;
                }
                $decoded = $this->decodeFieldValue($value);
                if ($decoded !== null) {
                    $map[$key] = $decoded;
                }
            }
            return $map;
        }
        if (isset($field['arrayValue']['values']) && is_array($field['arrayValue']['values'])) {
            $values = [];
            foreach ($field['arrayValue']['values'] as $value) {
                if (!is_array($value)) {
                    continue;
                }
                $decoded = $this->decodeFieldValue($value);
                if ($decoded !== null) {
                    $values[] = $decoded;
                }
            }
            return $values;
        }
        return null;
    }

    public function decodeDocumentFields(array $doc): array
    {
        $fields = $doc['fields'] ?? null;
        if (!is_array($fields)) {
            return [];
        }

        $decoded = [];
        foreach ($fields as $key => $field) {
            if (!is_array($field)) {
                continue;
            }
            $value = $this->decodeFieldValue($field);
            if ($value !== null) {
                $decoded[$key] = $value;
            }
        }

        return $decoded;
    }

    public function getField(array $doc, string $path, $default = null)
    {
        if ($path === '') {
            return $default;
        }
        $segments = explode('.', $path);
        $value = $doc;
        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }
        return $value ?? $default;
    }

    public function docIdFromName(string $name): string
    {
        if ($name === '') {
            return '';
        }
        $parts = explode('/', $name);
        return end($parts) ?: '';
    }

    public function toDateTimeStringFromTimestamp($value, ?string $fallbackUpdateTime = null): string
    {
        $candidate = $value ?: $fallbackUpdateTime;
        if (!$candidate) {
            return '';
        }

        try {
            return Carbon::parse($candidate)->format('Y-m-d H:i:s');
        } catch (\Throwable $e) {
            return (string) $candidate;
        }
    }

    public function tsToString($value, ?string $fallbackUpdateTime = null): string
    {
        return $this->toDateTimeStringFromTimestamp($value, $fallbackUpdateTime);
    }

    public function fsValue(array $doc, string $path, $default = null)
    {
        return $this->getField($doc, $path, $default);
    }

    public function safeScalar($value, string $default = '')
    {
        if ($value === null) {
            return $default;
        }
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }
        if (is_object($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }
        return $value;
    }

    private function encodeFields(array $fields): array
    {
        $payloadFields = [];
        foreach ($fields as $key => $value) {
            $payloadFields[$key] = $this->encodeValue($value);
        }
        return $payloadFields;
    }

    private function normalizeAdmin(array $data): array
    {
        $roles = $data['roles'] ?? ['admin'];
        $roles = is_array($roles) ? $roles : ['admin'];

        $isActive = $data['is_active'] ?? true;
        $isActive = !($isActive === false || $isActive === 0 || $isActive === '0');

        return [
            'docId' => $data['docId'] ?? ($data['__id'] ?? null),
            'email' => strtolower(trim((string) ($data['email'] ?? ''))),
            'name' => (string) ($data['name'] ?? 'Admin'),
            'roles' => $roles,
            'is_active' => $isActive,
            'password_hash' => $data['password_hash'] ?? null,
        ];
    }

    private function buildFilter(array $where): ?array
    {
        if (count($where) === 0) {
            return null;
        }

        $filters = [];
        foreach ($where as $condition) {
            if (!isset($condition['field'], $condition['op'], $condition['value'])) {
                continue;
            }

            $filters[] = [
                'fieldFilter' => [
                    'field' => ['fieldPath' => $condition['field']],
                    'op' => $condition['op'],
                    'value' => $this->encodeValue($condition['value']),
                ],
            ];
        }

        if (count($filters) === 1) {
            return $filters[0];
        }

        return [
            'compositeFilter' => [
                'op' => 'AND',
                'filters' => $filters,
            ],
        ];
    }

    private function encodeValue($value): array
    {
        if (is_array($value) && (isset($value['timestampValue']) || isset($value['stringValue']) || isset($value['integerValue']) || isset($value['doubleValue']) || isset($value['booleanValue']))) {
            return $value;
        }
        if (is_array($value)) {
            if ($this->isAssocArray($value)) {
                $fields = [];
                foreach ($value as $key => $item) {
                    $fields[$key] = $this->encodeValue($item);
                }
                return ['mapValue' => ['fields' => $fields]];
            }
            $values = [];
            foreach ($value as $item) {
                $values[] = $this->encodeValue($item);
            }
            return ['arrayValue' => ['values' => $values]];
        }
        if ($value instanceof \DateTimeInterface) {
            return ['timestampValue' => $value->format(DATE_RFC3339)];
        }
        if (is_bool($value)) {
            return ['booleanValue' => $value];
        }
        if (is_int($value)) {
            return ['integerValue' => (string) $value];
        }
        if (is_float($value)) {
            return ['doubleValue' => $value];
        }
        return ['stringValue' => (string) $value];
    }

    private function isAssocArray(array $value): bool
    {
        if ($value === []) {
            return false;
        }
        return array_keys($value) !== range(0, count($value) - 1);
    }

    private function getValueByPath(array $data, string $path)
    {
        if ($path === '') {
            return null;
        }

        $segments = explode('.', $path);
        $value = $data;
        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return null;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    private function getAccessToken(): ?string
    {
        try {
            if (!$this->credentialsPath || !is_file($this->credentialsPath)) {
                $this->warnOnce('firestore:credentials', 'missing_credentials');
                return null;
            }

            $credentials = new ServiceAccountCredentials(
                ['https://www.googleapis.com/auth/datastore'],
                $this->credentialsPath
            );

            $token = $credentials->fetchAuthToken();
            if (is_array($token) && isset($token['access_token'])) {
                return $token['access_token'];
            }

            $last = $credentials->getLastReceivedToken();
            return is_array($last) && isset($last['access_token']) ? $last['access_token'] : null;
        } catch (\Throwable $e) {
            $this->warnOnce('firestore:credentials', $e->getMessage());
            return null;
        }
    }

    private function request(string $method, string $url, array $options, ?string $logKey = null)
    {
        $options = array_merge([
            'connect_timeout' => (float) env('FIRESTORE_CONNECT_TIMEOUT', 2),
            'timeout' => (float) env('FIRESTORE_HTTP_TIMEOUT', 6),
            'http_errors' => false,
        ], $options);

        $key = $logKey ?? ($method . ' ' . $url);
        try {
            $response = $this->client->request($method, $url, $options);
            if ($response !== null && $response->getStatusCode() >= 500) {
                $this->warnOnce($key, 'server_error status=' . $response->getStatusCode());
            }
            return $response;
        } catch (\Throwable $e) {
            $this->warnOnce($key, $e->getMessage());
            return null;
        }
    }

    private function isSuccessfulResponse($response): bool
    {
        if ($response === null) {
            return false;
        }
        $status = $response->getStatusCode();
        return $status >= 200 && $status < 300;
    }

    public function hadFailure(): bool
    {
        return self::$hadFailure;
    }

    public function resetFailure(): void
    {
        self::$hadFailure = false;
    }

    private function warnOnce(string $key, string $message): void
    {
        if (isset(self::$warned[$key])) {
            return;
        }
        self::$warned[$key] = true;
        self::$hadFailure = true;
        Log::warning('FIRESTORE_REST warning=' . $key . ' message=' . $message);
    }
}

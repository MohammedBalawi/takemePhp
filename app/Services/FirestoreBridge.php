<?php

namespace App\Services;

use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;

class FirestoreBridge
{
    private Client $client;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => config('services.firestore_bridge'),
            'connect_timeout' => 5,
            'timeout' => 10,
            'http_errors' => false,
        ]);
    }

    public function set(string $collection, string $docId, array $data, bool $merge = true): array
    {
        $this->guardSelfCall();
        $response = $this->client->post('/firestore/set', [
            'json' => [
                'collection' => $collection,
                'docId' => $docId,
                'data' => $data,
                'merge' => $merge,
            ],
        ]);

        return $this->decodeResponse($response);
    }

    public function get(string $collection, string $docId): array
    {
        $this->guardSelfCall();
        $response = $this->client->get('/firestore/get', [
            'query' => [
                'collection' => $collection,
                'docId' => $docId,
            ],
        ]);

        return $this->decodeResponse($response);
    }

    public function login(string $email, string $password): array
    {
        $this->guardSelfCall();
        $response = $this->client->post('/auth/login', [
            'json' => [
                'email' => $email,
                'password' => $password,
            ],
        ]);

        return $this->decodeResponse($response);
    }

    public function register(string $email, string $password, ?string $displayName = null): array
    {
        $this->guardSelfCall();
        $payload = [
            'email' => $email,
            'password' => $password,
        ];
        if (is_string($displayName) && $displayName !== '') {
            $payload['displayName'] = $displayName;
        }

        $response = $this->client->post('/auth/register', [
            'json' => $payload,
        ]);

        return $this->decodeResponse($response);
    }

    public function verify(string $idToken): array
    {
        $this->guardSelfCall();
        $response = $this->client->post('/auth/verify', [
            'json' => [
                'idToken' => $idToken,
            ],
        ]);

        return $this->decodeResponse($response);
    }

    private function decodeResponse(ResponseInterface $response): array
    {
        $body = (string) $response->getBody();
        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            return [
                'ok' => false,
                'error' => 'Invalid response from Firestore bridge',
            ];
        }

        if (!array_key_exists('ok', $decoded)) {
            $decoded['ok'] = $response->getStatusCode() < 400;
        }

        return $decoded;
    }

    private function guardSelfCall(): void
    {
        $base = config('services.firestore_bridge');
        $appUrl = config('app.url');
        if (!is_string($base) || !is_string($appUrl) || $base === '' || $appUrl === '') {
            return;
        }

        $baseHost = parse_url($base, PHP_URL_HOST);
        $basePort = parse_url($base, PHP_URL_PORT) ?? 80;
        $appHost = parse_url($appUrl, PHP_URL_HOST);
        $appPort = parse_url($appUrl, PHP_URL_PORT) ?? 80;

        if ($baseHost === $appHost && $basePort === $appPort) {
            throw new \RuntimeException('Firestore bridge base_url must not point to APP_URL');
        }
    }
}

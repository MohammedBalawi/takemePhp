<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class FirebaseAdminService
{
    public function signInWithPassword(string $email, string $password): ?array
    {
        $apiKey = (string) env('FIREBASE_WEB_API_KEY', '');
        if ($apiKey === '') {
            return null;
        }

        $url = 'https://identitytoolkit.googleapis.com/v1/accounts:signInWithPassword?key=' . $apiKey;

        try {
            $response = Http::asJson()
                ->connectTimeout(5)
                ->timeout(10)
                ->post($url, [
                    'email' => $email,
                    'password' => $password,
                    'returnSecureToken' => true,
                ]);
        } catch (\Throwable $e) {
            return null;
        }

        if (!$response->ok()) {
            return null;
        }

        $data = $response->json();
        return is_array($data) ? $data : null;
    }

    public function fetchAdminProfile(?string $uid, ?string $email): array
    {
        $uid = is_string($uid) ? trim($uid) : '';
        $email = is_string($email) ? strtolower(trim($email)) : '';

        $default = [
            'uid' => $uid,
            'email' => $email,
            'name' => 'Admin',
            'language' => 'ar',
        ];

        try {
            $firestore = app('firebase.firestore')->database();
            $collection = $firestore->collection('admins');

            if ($uid !== '') {
                $snapshot = $collection->document($uid)->snapshot();
                if ($snapshot->exists()) {
                    $data = $snapshot->data();
                    return array_merge($default, [
                        'uid' => $uid,
                        'email' => (string) ($data['email'] ?? $email),
                        'name' => (string) ($data['name'] ?? 'Admin'),
                        'language' => (string) ($data['language'] ?? 'ar'),
                    ]);
                }
            }

            if ($email !== '') {
                $documents = $collection->where('email', '=', $email)->documents();
                foreach ($documents as $document) {
                    if ($document->exists()) {
                        $data = $document->data();
                        $docId = $document->id();
                        return array_merge($default, [
                            'uid' => $docId !== '' ? $docId : $uid,
                            'email' => (string) ($data['email'] ?? $email),
                            'name' => (string) ($data['name'] ?? 'Admin'),
                            'language' => (string) ($data['language'] ?? 'ar'),
                        ]);
                    }
                }
            }
        } catch (\Throwable $e) {
            return $default;
        }

        return $default;
    }
}

<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class GeoCoderService
{
    private static array $cache = [];

    public function reverseGeocode($lat, $lng): ?string
    {
        $lat = $this->normalizeCoord($lat);
        $lng = $this->normalizeCoord($lng);
        if ($lat === '' || $lng === '') {
            return null;
        }

        $key = 'geocode:rev:' . $this->roundCoord($lat) . ',' . $this->roundCoord($lng);
        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }

        $provider = (string) config('geocode.provider', 'nominatim');
        if ($provider !== 'nominatim') {
            return null;
        }

        $timeout = (int) config('geocode.timeout', 6);
        $userAgent = (string) config('geocode.user_agent', 'TakeMeAdmin/1.0 (support@takeme.local)');
        if ($userAgent === '') {
            return null;
        }

        try {
            $ttl = (int) config('geocode.cache_ttl_seconds', 86400);
            $address = Cache::remember($key, $ttl, function () use ($lat, $lng, $timeout, $userAgent) {
                $response = Http::withHeaders([
                    'User-Agent' => $userAgent,
                ])
                    ->connectTimeout(5)
                    ->timeout(min(6, max(1, $timeout)))
                    ->get('https://nominatim.openstreetmap.org/reverse', [
                        'format' => 'jsonv2',
                        'lat' => $lat,
                        'lon' => $lng,
                    ]);

                if (!$response->ok()) {
                    return null;
                }

                $data = $response->json();
                return is_array($data) ? ($data['display_name'] ?? null) : null;
            });

            if (!is_string($address) || $address === '') {
                $this->debugLog(false, $lat, $lng);
                return null;
            }

            self::$cache[$key] = $address;
            $this->debugLog(true, $lat, $lng);
            return $address;
        } catch (\Throwable $e) {
            $this->debugLog(false, $lat, $lng);
            return null;
        }
    }

    private function normalizeCoord($value): string
    {
        if ($value === null) {
            return '';
        }
        if (is_numeric($value)) {
            return (string) $value;
        }
        if (is_string($value)) {
            return trim($value);
        }
        return '';
    }

    private function roundCoord(string $value): string
    {
        if (!is_numeric($value)) {
            return $value;
        }
        return number_format((float) $value, 5, '.', '');
    }

    private function debugLog(bool $ok, string $lat, string $lng): void
    {
        if (!env('APP_DEBUG')) {
            return;
        }
        logger()->debug('SOS_GEOCODE ' . ($ok ? 'ok' : 'fail') . ' lat=' . $lat . ' lng=' . $lng);
    }
}

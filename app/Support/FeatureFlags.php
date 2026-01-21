<?php

namespace App\Support;

/**
 * Feature flags for progressive Firestore adoption.
 *
 * Usage examples:
 * - MOCK_MODE=true FIRESTORE_ENABLED=false php artisan serve
 * - FF_DASHBOARD_FIRESTORE=true FIRESTORE_ENABLED=true MOCK_MODE=true php artisan serve
 */
class FeatureFlags
{
    public static function firestoreEnabled(): bool
    {
        return (bool) env('FIRESTORE_ENABLED', false);
    }

    public static function useMock(): bool
    {
        return (bool) env('MOCK_MODE', true);
    }

    public static function featureEnabled(string $key): bool
    {
        $envKey = 'FF_' . strtoupper($key) . '_FIRESTORE';
        return (bool) env($envKey, false);
    }

    public static function shouldUseFirestore(string $featureKey): bool
    {
        if (!self::firestoreEnabled()) {
            return false;
        }
        return self::featureEnabled($featureKey);
    }

    public static function driversFirestoreEnabled(): bool
    {
        return self::firestoreEnabled() && self::featureEnabled('DRIVERS');
    }

    public static function driverDocumentsFirestoreEnabled(): bool
    {
        return self::firestoreEnabled() && (bool) env('FF_DRIVER_DOCUMENTS_FIRESTORE', false);
    }

    public static function ridesFirestoreEnabled(): bool
    {
        return self::firestoreEnabled() && self::featureEnabled('RIDES');
    }

    public static function paymentsFirestoreEnabled(): bool
    {
        return self::firestoreEnabled() && (bool) env('FF_PAYMENTS_FIRESTORE', false);
    }

    public static function isPaymentsFirestoreEnabled(): bool
    {
        return self::paymentsFirestoreEnabled();
    }

    public static function offersFirestoreEnabled(): bool
    {
        return self::firestoreEnabled() && (bool) env('FF_OFFERS_FIRESTORE', false);
    }

    public static function walletTopupsFirestoreEnabled(): bool
    {
        return self::firestoreEnabled() && (bool) env('FF_WALLET_TOPUPS_FIRESTORE', false);
    }

    public static function pricingFirestoreEnabled(): bool
    {
        return self::firestoreEnabled() && (bool) env('FF_PRICING_FIRESTORE', false);
    }

    public static function surgeRulesFirestoreEnabled(): bool
    {
        return self::firestoreEnabled() && (bool) env('FF_SURGE_RULES_FIRESTORE', false);
    }

    public static function monthlyRequestsFirestoreEnabled(): bool
    {
        return self::firestoreEnabled() && (bool) env('FF_MONTHLY_REQUESTS_FIRESTORE', false);
    }

    public static function airportRequestsFirestoreEnabled(): bool
    {
        return self::firestoreEnabled() && (bool) env('FF_AIRPORT_REQUESTS_FIRESTORE', false);
    }

    public static function specialNeedsRequestsFirestoreEnabled(): bool
    {
        return self::firestoreEnabled() && (bool) env('FF_SPECIAL_NEEDS_REQUESTS_FIRESTORE', false);
    }
}

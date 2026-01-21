<?php

namespace App\Support;

/**
 * Feature flags for progressive Firestore adoption.
 *
 * Usage examples:
 * - FIRESTORE_ENABLED=true php artisan serve
 */
class FeatureFlags
{
    public static function firestoreEnabled(): bool
    {
        return (bool) env('FIRESTORE_ENABLED', true);
    }

    public static function featureEnabled(string $key): bool
    {
        return true;
    }

    public static function shouldUseFirestore(string $featureKey): bool
    {
        return self::firestoreEnabled();
    }

    public static function driversFirestoreEnabled(): bool
    {
        return self::firestoreEnabled();
    }

    public static function driverDocumentsFirestoreEnabled(): bool
    {
        return self::firestoreEnabled();
    }

    public static function ridesFirestoreEnabled(): bool
    {
        return self::firestoreEnabled();
    }

    public static function paymentsFirestoreEnabled(): bool
    {
        return self::firestoreEnabled();
    }

    public static function isPaymentsFirestoreEnabled(): bool
    {
        return self::paymentsFirestoreEnabled();
    }

    public static function offersFirestoreEnabled(): bool
    {
        return self::firestoreEnabled();
    }

    public static function walletTopupsFirestoreEnabled(): bool
    {
        return self::firestoreEnabled();
    }

    public static function pricingFirestoreEnabled(): bool
    {
        return self::firestoreEnabled();
    }

    public static function surgeRulesFirestoreEnabled(): bool
    {
        return self::firestoreEnabled();
    }

    public static function monthlyRequestsFirestoreEnabled(): bool
    {
        return self::firestoreEnabled();
    }

    public static function airportRequestsFirestoreEnabled(): bool
    {
        return self::firestoreEnabled();
    }

    public static function specialNeedsRequestsFirestoreEnabled(): bool
    {
        return self::firestoreEnabled();
    }
}

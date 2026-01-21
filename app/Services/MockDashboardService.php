<?php

namespace App\Services;

class MockDashboardService
{
    public function getStats(): array
    {
        return [
            'totalDrivers' => 42,
            'pendingDrivers' => 5,
            'totalRiders' => 180,
            'totalRides' => 320,
            'todayEarnings' => 1250.0,
            'monthEarnings' => 18250.0,
            'totalEarnings' => 245000.0,
            'sosCount' => 3,
        ];
    }

    public function getRecentRides(int $limit = 10): array
    {
        $rides = [
            [
                'id' => 'MOCK-1001',
                'riderName' => 'Rider One',
                'riderPhone' => '+000000000',
                'driverName' => 'Driver One',
                'driverPhone' => '+000000000',
                'status' => 'completed',
                'cityId' => 'Mock City',
                'startAddress' => 'Mock Pickup A',
                'endAddress' => 'Mock Drop A',
                'distanceKm' => '8.2 km',
                'durationMin' => '18 min',
                'total' => 120.0,
                'createdAt' => '2026-01-20 09:15:00',
                'paymentMethod' => 'cash',
                'paymentStatus' => 'paid',
            ],
            [
                'id' => 'MOCK-1002',
                'riderName' => 'Rider Two',
                'riderPhone' => '+000000000',
                'driverName' => 'Driver Two',
                'driverPhone' => '+000000000',
                'status' => 'pending',
                'cityId' => 'Mock City',
                'startAddress' => 'Mock Pickup B',
                'endAddress' => 'Mock Drop B',
                'distanceKm' => '3.4 km',
                'durationMin' => '9 min',
                'total' => 45.0,
                'createdAt' => '2026-01-20 10:05:00',
                'paymentMethod' => 'wallet',
                'paymentStatus' => 'unpaid',
            ],
        ];

        return array_slice($rides, 0, max(0, $limit));
    }
}

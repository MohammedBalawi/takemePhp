<?php

namespace App\Console\Commands;

use App\Services\FirestoreRestService;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SeedFirestoreMockData extends Command
{
    protected $signature = 'firestore:seed-mock {--module=all}';
    protected $description = 'Seed mock data into Firestore collections';

    public function handle(): int
    {
        $module = (string) $this->option('module');
        $module = $module !== '' ? $module : 'all';

        $service = app(FirestoreRestService::class);

        $seeded = 0;
        if ($module === 'all' || $module === 'rides') {
            $seeded += $this->seedRides($service);
        }
        if ($module === 'all' || $module === 'ride_requests') {
            $seeded += $this->seedRideRequests($service);
        }
        if ($module === 'all' || $module === 'admins') {
            $seeded += $this->seedAdmins($service);
        }
        if ($module === 'all' || $module === 'mail_templates') {
            $seeded += $this->seedMailTemplates($service);
        }
        if ($module === 'all' || $module === 'sos_alerts') {
            $seeded += $this->seedSosAlerts($service);
        }

        $this->info('Seeded ' . $seeded . ' documents.');
        return Command::SUCCESS;
    }

    private function seedRides(FirestoreRestService $service): int
    {
        $rows = config('mock_data.mock_rides.all', []);
        $count = 0;
        foreach ($rows as $row) {
            $id = $row['id'] ?? ('ride-' . uniqid());
            $fields = [
                'rideId' => $id,
                'status' => $row['status'] ?? 'completed',
                'riderUid' => $row['riderUid'] ?? '',
                'driverUid' => $row['driverUid'] ?? '',
                'start' => ['address' => $row['pickupAddress'] ?? ''],
                'end' => ['address' => $row['dropoffAddress'] ?? ''],
                'fare' => ['total' => (float) ($row['fareTotal'] ?? 0), 'currency' => $row['currency'] ?? 'SAR'],
                'payment' => ['method' => $row['paymentMethod'] ?? 'cash', 'status' => $row['paymentStatus'] ?? 'paid'],
                'createdAt' => Carbon::now('UTC'),
                'updatedAt' => Carbon::now('UTC'),
            ];
            if ($service->patchDocumentTyped('rides', $id, $fields)) {
                $count++;
            }
        }
        return $count;
    }

    private function seedRideRequests(FirestoreRestService $service): int
    {
        $rows = config('mock_data.mock_ride_requests', []);
        $count = 0;
        foreach ($rows as $row) {
            $id = $row['id'] ?? ('req-' . uniqid());
            $fields = [
                'requestId' => $id,
                'rideId' => $row['id'] ?? $id,
                'status' => $row['status'] ?? 'pending',
                'riderUid' => $row['riderUid'] ?? '',
                'driverUid' => $row['driverUid'] ?? '',
                'pickupAddress' => $row['pickupAddress'] ?? '',
                'dropoffAddress' => $row['dropoffAddress'] ?? '',
                'pricingTotal' => (float) ($row['fareTotal'] ?? 0),
                'createdAt' => Carbon::now('UTC'),
                'updatedAt' => Carbon::now('UTC'),
            ];
            if ($service->patchDocumentTyped('ride_requests', $id, $fields)) {
                $count++;
            }
        }
        return $count;
    }

    private function seedAdmins(FirestoreRestService $service): int
    {
        $rows = config('mock_data.mock_admins', []);
        $rows[] = [
            'email' => 'admin@example.com',
            'name' => 'Admin',
            'roles' => ['super_admin'],
            'is_active' => true,
            'locale' => 'ar',
            'lang' => 'ar',
            'password_hash' => Hash::make('Admin@12345'),
        ];
        $count = 0;
        foreach ($rows as $row) {
            $id = $row['email'] ?? ('admin-' . uniqid());
            $fields = [
                'email' => $row['email'] ?? '',
                'name' => $row['name'] ?? 'Admin',
                'roles' => $row['roles'] ?? ['admin'],
                'is_active' => (bool) ($row['is_active'] ?? true),
                'locale' => $row['locale'] ?? 'ar',
                'lang' => $row['lang'] ?? 'ar',
                'password_hash' => $row['password_hash'] ?? Hash::make('Admin@12345'),
                'updated_at' => Carbon::now('UTC'),
            ];
            if ($service->patchDocumentTyped('admins', $id, $fields)) {
                $count++;
            }
        }
        return $count;
    }

    private function seedMailTemplates(FirestoreRestService $service): int
    {
        $templates = config('mock_data.mail_templates', []);
        $count = 0;
        foreach ($templates as $type => $tpl) {
            $fields = [
                'type' => $type,
                'subject' => $tpl['subject'] ?? '',
                'body_html' => $tpl['body_html'] ?? '',
                'body_text' => $tpl['body_text'] ?? '',
                'updatedAt' => Carbon::now('UTC'),
            ];
            if ($service->patchDocumentTyped('mail_templates', $type, $fields)) {
                $count++;
            }
        }
        return $count;
    }

    private function seedSosAlerts(FirestoreRestService $service): int
    {
        $rows = config('mock_data.mock_sos_alerts', []);
        $count = 0;
        foreach ($rows as $row) {
            $id = $row['id'] ?? ('sos-' . uniqid());
            $fields = [
                'rideId' => $row['ride_id'] ?? '',
                'riderUid' => $row['rider_uid'] ?? '',
                'driverUid' => $row['driver_uid'] ?? '',
                'status' => $row['status'] ?? 'open',
                'triggeredBy' => $row['triggeredBy'] ?? 'rider',
                'location' => ['lat' => 24.7136, 'lng' => 46.6753],
                'createdAt' => Carbon::now('UTC'),
            ];
            if ($service->patchDocumentTyped('sos_alerts', $id, $fields)) {
                $count++;
            }
        }
        return $count;
    }
}

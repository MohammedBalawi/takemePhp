<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\FirestoreRestService;
use App\Services\SosBellService;

class SosAlertsController extends Controller
{
    public function index(FirestoreRestService $firestore, SosBellService $bell)
    {
        $pageTitle = 'تنبيهات الطوارئ';
        $rows = [];

        $docs = $firestore->listDocuments('sos_alerts', 200);
        foreach ($docs as $doc) {
            $createdAt = $firestore->tsToString($doc['createdAt'] ?? $doc['created_at'] ?? null, $doc['_updateTime'] ?? null);
            $lat = $doc['location']['lat'] ?? $doc['lat'] ?? $doc['latitude'] ?? '';
            $lng = $doc['location']['lng'] ?? $doc['lng'] ?? $doc['longitude'] ?? '';
            $rows[] = [
                'id' => (string) ($doc['_docId'] ?? ''),
                'created_at' => $createdAt,
                'ride_request_id' => (string) ($doc['rideId'] ?? $doc['ride_id'] ?? ''),
                'latitude' => (string) $lat,
                'longitude' => (string) $lng,
            ];
        }

        $bell->markSeen();

        return view('sos_alerts.index', compact('pageTitle', 'rows'));
    }
}

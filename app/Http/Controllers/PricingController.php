<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Support\FeatureFlags;
use App\Services\FirestoreRestService;

class PricingController extends Controller
{
    private const DOC_PATH = 'pricing/taxi/zones/city_hebron/rates/default';

    public function index(FirestoreRestService $firestore)
    {
        if (!FeatureFlags::pricingFirestoreEnabled()) {
            return redirect()->back()->withErrors(['message' => 'Firestore disabled']);
        }

        $pageTitle = 'التسعيرة';
        $docPath = self::DOC_PATH;
        $doc = $firestore->getDocumentPath($docPath);
        $fields = $doc ? $firestore->decodeDocumentFields($doc) : [];
        $updatedAt = $firestore->tsToString($fields['updatedAt'] ?? null, $doc['updateTime'] ?? null);

        return view('pricing.index', compact('pageTitle', 'docPath', 'fields', 'updatedAt'));
    }

    public function update(Request $request, FirestoreRestService $firestore)
    {
        $validated = $request->validate([
            'isActive' => ['nullable', 'boolean'],
            'baseFare' => ['nullable', 'numeric'],
            'bookingFee' => ['nullable', 'numeric'],
            'cancelFee' => ['nullable', 'numeric'],
            'minimumFare' => ['nullable', 'numeric'],
            'perKm' => ['nullable', 'numeric'],
            'perMin' => ['nullable', 'numeric'],
            'nightStartHour' => ['nullable', 'numeric'],
            'nightEndHour' => ['nullable', 'numeric'],
            'nightMultiplier' => ['nullable', 'numeric'],
            'surgeMultiplierDefault' => ['nullable', 'numeric'],
        ]);

        if (!FeatureFlags::pricingFirestoreEnabled()) {
            return redirect()->back()->withErrors(['message' => 'Firestore disabled']);
        }

        $payload = [
            'isActive' => !empty($validated['isActive']),
        ];

        $numericFields = [
            'baseFare',
            'bookingFee',
            'cancelFee',
            'minimumFare',
            'perKm',
            'perMin',
            'nightStartHour',
            'nightEndHour',
            'nightMultiplier',
            'surgeMultiplierDefault',
        ];

        foreach ($numericFields as $field) {
            if (array_key_exists($field, $validated) && $validated[$field] !== null) {
                $payload[$field] = (float) $validated[$field];
            }
        }

        $ok = $firestore->patchDocumentPath(self::DOC_PATH, $payload);
        if (!$ok) {
            return redirect()->back()->withErrors(['message' => 'Failed to save pricing']);
        }

        return redirect()->route('pricing.index')->withSuccess('تم الحفظ');
    }
}

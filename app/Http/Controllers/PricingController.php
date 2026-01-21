<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\PricingService;
use App\Support\FeatureFlags;

class PricingController extends Controller
{
    public function index(PricingService $service)
    {
        $pageTitle = 'التسعيرة الأساسية';
        $rows = $service->listBasePricing();
        return view('pricing.index', compact('pageTitle', 'rows'));
    }

    public function create()
    {
        $pageTitle = 'إضافة تسعيرة أساسية';
        return view('pricing.form', compact('pageTitle'));
    }

    public function store(Request $request, PricingService $service)
    {
        $validated = $request->validate([
            'city_id' => ['nullable', 'string'],
            'city_name' => ['nullable', 'string'],
            'service_id' => ['required', 'string'],
            'currency' => ['nullable', 'string'],
            'base_fare' => ['required', 'numeric', 'min:0'],
            'per_km' => ['required', 'numeric', 'min:0'],
            'per_min' => ['required', 'numeric', 'min:0'],
            'min_fare' => ['required', 'numeric', 'min:0'],
            'is_active' => ['nullable'],
        ]);

        if (!FeatureFlags::pricingFirestoreEnabled()) {
            return redirect()->back()->withErrors(['message' => 'Firestore disabled']);
        }

        $payload = [
            'cityId' => $validated['city_id'] ?? '',
            'cityName' => $validated['city_name'] ?? '',
            'serviceId' => $validated['service_id'],
            'currency' => $validated['currency'] ?? 'SAR',
            'baseFare' => (float) $validated['base_fare'],
            'perKm' => (float) $validated['per_km'],
            'perMin' => (float) $validated['per_min'],
            'minFare' => (float) $validated['min_fare'],
            'isActive' => !empty($validated['is_active']),
        ];

        $ok = $service->upsertBasePricing($payload);
        if (!$ok) {
            return redirect()->back()->withErrors(['message' => 'Failed to save pricing']);
        }

        return redirect()->route('pricing.index')->withSuccess('تم الحفظ');
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\PricingModifiersService;
use App\Support\FeatureFlags;

class PricingModifiersController extends Controller
{
    public function index(Request $request, PricingModifiersService $service)
    {
        $pageTitle = 'قواعد التسعير';
        $type = $request->query('type');
        $rows = $service->listModifiers($type);
        return view('pricing_modifiers.index', compact('pageTitle', 'rows', 'type'));
    }

    public function create()
    {
        $pageTitle = 'إضافة قاعدة تسعير';
        return view('pricing_modifiers.form', compact('pageTitle'));
    }

    public function store(Request $request, PricingModifiersService $service)
    {
        $validated = $request->validate([
            'type' => ['required', 'string'],
            'city_id' => ['nullable', 'string'],
            'city_name' => ['nullable', 'string'],
            'service_id' => ['required', 'string'],
            'currency' => ['nullable', 'string'],
            'modifier_mode' => ['required', 'string'],
            'modifier_value' => ['required', 'numeric', 'min:0'],
            'override_base_fare' => ['nullable', 'numeric', 'min:0'],
            'override_per_km' => ['nullable', 'numeric', 'min:0'],
            'override_per_min' => ['nullable', 'numeric', 'min:0'],
            'override_min_fare' => ['nullable', 'numeric', 'min:0'],
            'day' => ['nullable', 'string'],
            'start_time' => ['nullable', 'string'],
            'end_time' => ['nullable', 'string'],
            'weather' => ['nullable', 'string'],
            'place_key' => ['nullable', 'string'],
            'place_name' => ['nullable', 'string'],
            'zone_id' => ['nullable', 'string'],
            'surge_tag' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable'],
            'priority' => ['nullable', 'numeric'],
        ]);

        if (!FeatureFlags::pricingFirestoreEnabled()) {
            return redirect()->back()->withErrors(['message' => 'Firestore disabled']);
        }

        $payload = [
            'type' => $validated['type'],
            'cityId' => $validated['city_id'] ?? '',
            'cityName' => $validated['city_name'] ?? '',
            'serviceId' => $validated['service_id'],
            'currency' => $validated['currency'] ?? 'SAR',
            'modifierMode' => $validated['modifier_mode'],
            'modifierValue' => (float) $validated['modifier_value'],
            'overrideBaseFare' => $validated['override_base_fare'] ?? null,
            'overridePerKm' => $validated['override_per_km'] ?? null,
            'overridePerMin' => $validated['override_per_min'] ?? null,
            'overrideMinFare' => $validated['override_min_fare'] ?? null,
            'day' => $validated['day'] ?? 'all',
            'startTime' => $validated['start_time'] ?? '',
            'endTime' => $validated['end_time'] ?? '',
            'weatherCondition' => $validated['weather'] ?? '',
            'placeKey' => $validated['place_key'] ?? '',
            'placeName' => $validated['place_name'] ?? '',
            'zoneId' => $validated['zone_id'] ?? '',
            'surgeTag' => $validated['surge_tag'] ?? '',
            'description' => $validated['description'] ?? '',
            'isActive' => !empty($validated['is_active']),
            'priority' => $validated['priority'] ?? 0,
        ];

        $ok = $service->createModifier($payload);
        if (!$ok) {
            return redirect()->back()->withErrors(['message' => 'Failed to save rule']);
        }

        return redirect()->route('pricing_modifiers.index')->withSuccess('تم الحفظ');
    }
}

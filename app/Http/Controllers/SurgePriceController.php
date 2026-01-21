<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SurgeRulesService;
use App\Services\PricingService;
use App\Support\FeatureFlags;

class SurgePriceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(SurgeRulesService $service)
    {
        $pageTitle = __('message.list_form_title',['form' => __('message.surge_price')] );
        $button ='<a href="'.route('surge-prices.create').'" class="float-right btn btn-sm border-radius-10 btn-primary me-2"><i class="fa fa-plus-circle"></i> '.__('message.add_form_title',['form' => __('message.surge_price')]).'</a>';
        $rules = $service->listRules();
        return view('surge_price.index', compact('pageTitle','button','rules'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $pageTitle = __('message.add_form_title',[ 'form' => __('message.surge_price')]);
        $cities = app(SurgeRulesService::class)->listCities();
        return view('surge_price.form', compact('pageTitle','cities'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, SurgeRulesService $service, PricingService $pricingService)
    {
        $validated = $request->validate([
            'city_id' => ['nullable', 'string'],
            'city_name' => ['nullable', 'string'],
            'service_id' => ['nullable', 'string'],
            'rule_type' => ['required', 'string'],
            'increase_type' => ['required', 'string'],
            'increase_value' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'weather' => ['nullable', 'string'],
            'day' => ['nullable', 'string'],
            'time_from' => ['nullable', 'string'],
            'time_to' => ['nullable', 'string'],
            'place_key' => ['nullable', 'string'],
            'status' => ['required', 'string'],
            'base_fare' => ['nullable', 'numeric', 'min:0'],
            'per_km' => ['nullable', 'numeric', 'min:0'],
            'per_min' => ['nullable', 'numeric', 'min:0'],
            'min_fare' => ['nullable', 'numeric', 'min:0'],
        ]);

        if (!FeatureFlags::surgeRulesFirestoreEnabled() && !FeatureFlags::pricingFirestoreEnabled()) {
            return redirect()->back()->withErrors(['message' => 'Firestore disabled']);
        }

        $ruleType = $validated['rule_type'];
        $ok = true;

        if ($ruleType === 'base_price') {
            $ok = $pricingService->upsertBasePricing([
                'cityId' => $validated['city_id'],
                'serviceId' => $validated['service_id'] ?? '',
                'baseFare' => $validated['base_fare'] ?? 0,
                'perKm' => $validated['per_km'] ?? 0,
                'perMin' => $validated['per_min'] ?? 0,
                'minFare' => $validated['min_fare'] ?? 0,
                'status' => $validated['status'],
            ]);

            if (($validated['time_from'] ?? '') !== '' || ($validated['time_to'] ?? '') !== '') {
                $ok = $ok && $pricingService->createModifier([
                    'type' => 'time_price',
                    'cityId' => $validated['city_id'],
                    'serviceId' => $validated['service_id'] ?? '',
                    'timeFrom' => $validated['time_from'] ?? '',
                    'timeTo' => $validated['time_to'] ?? '',
                    'baseFare' => $validated['base_fare'] ?? 0,
                    'perKm' => $validated['per_km'] ?? 0,
                    'perMin' => $validated['per_min'] ?? 0,
                    'minFare' => $validated['min_fare'] ?? 0,
                    'status' => $validated['status'],
                ]);
            }
        } elseif (in_array($ruleType, ['time_price', 'weather_price', 'place_modifier'], true)) {
            $ok = $pricingService->createModifier([
                'type' => $ruleType,
                'cityId' => $validated['city_id'],
                'cityName' => $validated['city_name'] ?? '',
                'serviceId' => $validated['service_id'] ?? '',
                'increaseType' => $validated['increase_type'],
                'increaseValue' => (float) $validated['increase_value'],
                'description' => $validated['description'] ?? '',
                'weather' => $validated['weather'] ?? '',
                'day' => $validated['day'] ?? 'all',
                'timeFrom' => $validated['time_from'] ?? '',
                'timeTo' => $validated['time_to'] ?? '',
                'placeKey' => $validated['place_key'] ?? '',
                'status' => $validated['status'],
            ]);
        } else {
            $payload = [
                'type' => $ruleType,
                'cityId' => $validated['city_id'],
                'cityName' => $validated['city_name'] ?? '',
                'serviceId' => $validated['service_id'] ?? '',
                'increaseType' => $validated['increase_type'],
                'increaseValue' => (float) $validated['increase_value'],
                'description' => $validated['description'] ?? '',
                'weather' => $validated['weather'] ?? '',
                'day' => $validated['day'] ?? 'all',
                'timeFrom' => $validated['time_from'] ?? '',
                'timeTo' => $validated['time_to'] ?? '',
                'placeKey' => $validated['place_key'] ?? '',
                'status' => $validated['status'],
                'createdAt' => now(),
                'updatedAt' => now(),
            ];

            $ok = $service->createRule($payload);
        }

        if (!$ok) {
            return redirect()->back()->withErrors(['message' => 'Failed to save rule']);
        }

        $message = __('message.save_form',['form' => __('message.surge_price')]);
        return redirect()->route('surge-prices.index')->withSuccess($message);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        abort(404);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        abort(404);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        abort(404);
    }
}

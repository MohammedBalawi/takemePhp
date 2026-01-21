<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\PricingModifiersService;
use App\Support\FeatureFlags;

class SurgePriceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, PricingModifiersService $service)
    {
        $pageTitle = __('message.list_form_title',['form' => __('message.surge_price')] );
        $tab = $request->query('tab', 'all');
        $type = $tab === 'all' ? null : $tab;
        $button ='<a href="'.route('surge-prices.create', ['type' => $tab]).'" class="float-right btn btn-sm border-radius-10 btn-primary me-2"><i class="fa fa-plus-circle"></i> '.__('message.add_form_title',['form' => __('message.surge_price')]).'</a>';
        $rules = $service->listModifiers($type);
        return view('surge_price.index', compact('pageTitle','button','rules','tab'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $pageTitle = __('message.add_form_title',[ 'form' => __('message.surge_price')]);
        $type = $request->query('type', 'all');
        return view('surge_price.form', compact('pageTitle','type'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, PricingModifiersService $service)
    {
        $validated = $request->validate([
            'city_id' => ['nullable', 'string'],
            'city_name' => ['nullable', 'string'],
            'service_id' => ['nullable', 'string'],
            'rule_type' => ['required', 'string'],
            'modifier_mode' => ['required', 'string'],
            'modifier_value' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'weather_condition' => ['nullable', 'string'],
            'day' => ['nullable', 'string'],
            'start_time' => ['nullable', 'string'],
            'end_time' => ['nullable', 'string'],
            'place_key' => ['nullable', 'string'],
            'place_name' => ['nullable', 'string'],
            'zone_id' => ['nullable', 'string'],
            'surge_tag' => ['nullable', 'string'],
            'scope' => ['nullable', 'string'],
            'status' => ['required', 'string'],
            'priority' => ['nullable', 'numeric'],
        ]);

        if (!FeatureFlags::pricingFirestoreEnabled()) {
            return redirect()->back()->withErrors(['message' => 'Firestore disabled']);
        }

        $ruleType = $validated['rule_type'];
        $isActive = ($validated['status'] ?? 'active') === 'active';
        if ($ruleType === 'fixed' && $isActive) {
            $service->deactivateActiveFixedGlobal();
        }

        $payload = [
            'type' => $ruleType,
            'scope' => $validated['scope'] ?? 'global',
            'cityId' => $validated['city_id'] ?? '',
            'cityName' => $validated['city_name'] ?? '',
            'serviceId' => $validated['service_id'] ?? '',
            'currency' => 'SAR',
            'modifierMode' => $validated['modifier_mode'],
            'modifierValue' => (float) $validated['modifier_value'],
            'day' => $validated['day'] ?? 'all',
            'startTime' => $validated['start_time'] ?? '',
            'endTime' => $validated['end_time'] ?? '',
            'weatherCondition' => $validated['weather_condition'] ?? '',
            'placeKey' => $validated['place_key'] ?? '',
            'placeName' => $validated['place_name'] ?? '',
            'zoneId' => $validated['zone_id'] ?? '',
            'surgeTag' => $validated['surge_tag'] ?? '',
            'description' => $validated['description'] ?? '',
            'isActive' => $isActive,
            'priority' => $validated['priority'] ?? 0,
            'createdAt' => now(),
            'updatedAt' => now(),
        ];

        $ok = $service->createModifier($payload);

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

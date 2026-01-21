<?php

namespace App\Http\Controllers;

use App\Http\Requests\CouponRequest;
use App\Services\CouponsService;

class CouponController extends Controller
{
    public function index()
    {
        $pageTitle = __('message.list_form_title', ['form' => __('message.coupon')]);
        $assets = [];
        $button = '<a href="' . route('coupon.create') . '" class="float-right btn btn-sm border-radius-10 btn-primary me-2"><i class="fa fa-plus-circle"></i> ' . __('message.add_form_title', ['form' => __('message.coupon')]) . '</a>';
        $coupons = app(CouponsService::class)->listCoupons();

        return view('coupon.index', compact('pageTitle', 'button', 'assets', 'coupons'));
    }

    public function create()
    {
        $pageTitle = __('message.add_form_title', ['form' => __('message.coupon')]);
        $selected_service = $selected_region = [];

        return view('coupon.form', compact('pageTitle', 'selected_service', 'selected_region'));
    }

    public function store(CouponRequest $request)
    {
        $data = $request->all();
        if ($request->coupon_type === 'first_ride') {
            $data['usage_limit_per_rider'] = 1;
        }

        $ok = app(CouponsService::class)->createCoupon($data);
        if (!$ok) {
            return redirect()->back()->withInput()->withErrors(__('message.coupon') . ' ' . __('message.already_in_use'));
        }

        return redirect()->route('coupon.index')->withSuccess(__('message.save_form', ['form' => __('coupon')]));
    }

    public function show($id)
    {
        abort(404);
    }

    public function edit($id)
    {
        abort(404);
    }

    public function update(CouponRequest $request, $id)
    {
        abort(404);
    }

    public function destroy($id)
    {
        abort(404);
    }
}

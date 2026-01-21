<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\RiderRequest;
use App\Services\RidersService;

class RiderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $pageTitle = __('message.list_form_title',['form' => __('message.rider')] );
        $assets = [];
        $last_actived_at = request('last_actived_at') ?? null;
        $button = '<a href="'.route('rider.create').'" class="float-right btn btn-sm border-radius-10 btn-primary me-2"><i class="fa fa-plus-circle"></i> '.__('message.add_form_title',['form' => __('message.rider')]).'</a>';

        $riders = app(RidersService::class)->listRiders();
        return view('rider.index', compact('assets','pageTitle','button','last_actived_at', 'riders'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $pageTitle = __('message.add_form_title',[ 'form' => __('message.rider')]);
        $assets = ['phone'];
        return view('rider.form', compact('pageTitle','assets'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(RiderRequest $request)
    {
        $payload = [
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'username' => $request->username,
            'address' => $request->address,
            'status' => $request->status,
            'password' => $request->password,
        ];

        app(RidersService::class)->createRider($payload);
        return redirect()->route('rider.index')->withSuccess(__('message.save_form', ['form' => __('message.rider')]));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(RideRequestDataTable $dataTable, WalletHistoryDataTable $walletHistoryDataTable, WithdrawRequestDataTable $WithdrawRequestDataTable, $id)
    {
        abort(404);


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
    public function update(RiderRequest $request, $id)
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

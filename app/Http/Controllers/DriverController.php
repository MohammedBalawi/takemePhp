<?php

namespace App\Http\Controllers;

use App\Http\Requests\DriverRequest;
use App\Services\DriversService;

class DriverController extends Controller
{
    public function index()
    {
        $pageTitle = __('message.list_form_title', ['form' => __('message.driver')]);
        $assets = [];
        $button = '<a href="' . route('driver.create') . '" class="float-right btn btn-sm border-radius-10 btn-primary me-2"><i class="fa fa-plus-circle"></i> ' . __('message.add_form_title', ['form' => __('message.driver')]) . '</a>';
        $drivers = app(DriversService::class)->listDrivers();

        return view('driver.index', compact('assets', 'pageTitle', 'button', 'drivers'));
    }

    public function pending()
    {
        $pageTitle = __('message.pending_list_form_title', ['form' => __('message.driver')]);
        $assets = [];
        $button = '<a href="' . route('driver.create') . '" class="float-right btn btn-sm border-radius-10 btn-primary me-2"><i class="fa fa-plus-circle"></i> ' . __('message.add_form_title', ['form' => __('message.driver')]) . '</a>';
        $drivers = app(DriversService::class)->listPendingDrivers();

        return view('driver.pending', compact('assets', 'pageTitle', 'button', 'drivers'));
    }

    public function create()
    {
        $pageTitle = __('message.add_form_title', ['form' => __('message.driver')]);
        $assets = ['phone'];
        return view('driver.form', compact('pageTitle', 'assets'));
    }

    public function store(DriverRequest $request)
    {
        $payload = [
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'username' => $request->username,
            'cityId' => $request->cityId,
            'password' => $request->password,
            'verificationStatus' => $request->verificationStatus,
        ];

        app(DriversService::class)->createDriver($payload);

        return redirect()->route('driver.index')->withSuccess(__('message.save_form', ['form' => __('message.driver')]));
    }

    public function documents(string $id)
    {
        $pageTitle = __('message.driver_document');
        $assets = [];
        $driver = app(DriversService::class)->getDriverById($id);
        $links = app(DriversService::class)->getDriverDocumentLinks($driver);
        return view('driver.documents', compact('pageTitle', 'assets', 'driver', 'links'));
    }

    public function verify(string $uid)
    {
        $pageTitle = __('message.driver_document');
        $assets = [];
        $driver = app(DriversService::class)->getDriverById($uid);
        $links = app(DriversService::class)->getDriverDocumentLinks($driver);
        return view('driver.verify', compact('pageTitle', 'assets', 'driver', 'links'));
    }

    public function approve(string $uid)
    {
        $ok = app(DriversService::class)->approveDriver($uid);
        return redirect()->route('driver.pending')->withSuccess($ok ? __('message.update_form', ['form' => __('message.driver')]) : __('message.something_wrong'));
    }

    public function reject(string $uid)
    {
        $reason = request('reason');
        $ok = app(DriversService::class)->rejectDriver($uid, $reason);
        return redirect()->route('driver.pending')->withSuccess($ok ? __('message.update_form', ['form' => __('message.driver')]) : __('message.something_wrong'));
    }

    public function show($id)
    {
        abort(404);
    }

    public function edit($id)
    {
        abort(404);
    }

    public function update(DriverRequest $request, $id)
    {
        abort(404);
    }

    public function destroy($id)
    {
        abort(404);
    }
}

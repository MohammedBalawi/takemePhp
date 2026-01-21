<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubAdminRequest;
use App\Services\SubAdminsService;

class SubAdminController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $pageTitle = __('message.list_form_title', ['form' => __('message.sub_admin')]);
        $assets = [];
        $button = '<a href="' . route('sub-admin.create') . '" class="float-right btn btn-sm border-radius-10 btn-primary"><i class="fa fa-plus-circle"></i> ' . __('message.add_form_title', ['form' => __('message.sub_admin')]) . '</a>';
        $sub_admins = app(SubAdminsService::class)->listSubAdmins();
        return view('subadmin.index', compact('assets', 'pageTitle', 'button', 'sub_admins'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $pageTitle =  __('message.sub_admin');
        $assets = [];
        return view('subadmin.form', compact('pageTitle', 'assets'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(SubAdminRequest $request)
    {
        $payload = [
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
            'is_active' => $request->boolean('is_active', true),
        ];
        app(SubAdminsService::class)->createSubAdmin($payload);
        $message = __('message.save_form', ['form' => __('message.sub_admin')]);
        return redirect()->route('sub-admin.index')->withSuccess($message);
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
    public function update($id)
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

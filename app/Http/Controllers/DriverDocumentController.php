<?php

namespace App\Http\Controllers;

use App\Services\DriverDocsService;
use Illuminate\Http\Request;

class DriverDocumentController extends Controller
{
    public function index()
    {
        $pageTitle = __('message.list_form_title', ['form' => __('message.driver_document')]);
        $auth_user = authSession();
        $assets = [];
        $button = '';
        $driver_docs = app(DriverDocsService::class)->listDriverDocs();

        return view('driver_document.index', compact('pageTitle', 'button', 'auth_user', 'driver_docs', 'assets'));
    }

    public function create()
    {
        abort(404);
    }

    public function store(Request $request)
    {
        abort(404);
    }

    public function show($id)
    {
        abort(404);
    }

    public function edit($id)
    {
        abort(404);
    }

    public function update(Request $request, $id)
    {
        abort(404);
    }

    public function destroy($id)
    {
        abort(404);
    }
}

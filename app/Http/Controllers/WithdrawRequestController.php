<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\WalletTopupsService;

class WithdrawRequestController extends Controller
{
    public function index(Request $request, WalletTopupsService $service)
    {
        $pageTitle = 'تحويلات السائق';
        $type = (string) $request->query('withdraw_type', 'all');
        $rows = $service->listByType($type);

        return view('withdrawrequest.index', compact('pageTitle', 'rows', 'type'));
    }

    public function create()
    {
        abort(404);
    }

    public function store(Request $request)
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

    public function update(Request $request, $id)
    {
        abort(404);
    }

    public function approve(Request $request, string $id, WalletTopupsService $service)
    {
        $ok = $service->updateStatus($id, 'approved');
        if (!$ok) {
            return redirect()->back()->withErrors(['message' => 'تعذر اعتماد الطلب']);
        }
        return redirect()->back()->withSuccess('تم اعتماد الطلب');
    }

    public function decline(Request $request, string $id, WalletTopupsService $service)
    {
        $ok = $service->updateStatus($id, 'decline');
        if (!$ok) {
            return redirect()->back()->withErrors(['message' => 'تعذر رفض الطلب']);
        }
        return redirect()->back()->withSuccess('تم رفض الطلب');
    }

    public function userBankDetail($id)
    {
        $title = __('message.detail_form_title', [ 'form' => __('message.bank') ]);
        $data = null;

        return view('withdrawrequest.bankdetail', compact('title','data'));
    }

    public function downloadWithdrawRequestList(Request $request)
    {        
        abort(404);
    }
}

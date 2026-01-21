<?php

namespace App\Http\Controllers;

use App\Services\RidesAdminService;
use App\Services\RidesService;

class RideAdminController extends Controller
{
    public function all()
    {
        return $this->renderList(__('message.list_form_title', ['form' => __('message.riderequest')]), app(RidesService::class)->listAll());
    }

    public function completed()
    {
        return $this->renderList(__('message.list_form_title', ['form' => __('message.completed')]), app(RidesService::class)->listCompleted());
    }

    public function newToday()
    {
        return $this->renderList(__('message.list_form_title', ['form' => __('message.new_ride')]), app(RidesService::class)->listTodayNew());
    }

    public function cancelled()
    {
        return $this->renderList(__('message.list_form_title', ['form' => __('message.canceled')]), app(RidesService::class)->listCancelled());
    }

    public function inProgress()
    {
        return $this->renderList(__('message.list_form_title', ['form' => __('message.pending')]), app(RidesService::class)->listInProgress());
    }

    public function show(string $id)
    {
        $source = request('source', 'rides');
        $pageTitle = __('message.riderequest');
        $assets = [];
        $data = app(RidesAdminService::class)->getDetails($id, $source);

        return view('ride.show', compact('pageTitle', 'assets', 'data', 'source'));
    }

    private function renderList(string $pageTitle, array $rows)
    {
        $auth_user = authSession();
        $assets = [];
        $button = '';

        return view('ride.index', compact('pageTitle', 'button', 'auth_user', 'assets', 'rows'));
    }
}

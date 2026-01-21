<?php

namespace App\Http\Controllers;

use App\Services\RidesService;
use App\Services\DashboardService;
use App\Services\RidesAdminService;

class RideController extends Controller
{
    public function all()
    {
        return $this->render('ride.index', __('message.list_form_title', ['form' => __('message.riderequest')]), app(RidesAdminService::class)->listAll());
    }

    public function completed()
    {
        return $this->render('ride.index', __('message.list_form_title', ['form' => __('message.completed')]), app(RidesAdminService::class)->listCompleted());
    }

    public function newToday()
    {
        return $this->render('ride.index', __('message.list_form_title', ['form' => __('message.new_ride')]), app(RidesAdminService::class)->listTodayNew());
    }

    public function cancelled()
    {
        return $this->render('ride.index', __('message.list_form_title', ['form' => __('message.canceled')]), app(RidesAdminService::class)->listCancelled());
    }

    public function inProgress()
    {
        return $this->render('ride.index', __('message.list_form_title', ['form' => __('message.pending')]), app(RidesAdminService::class)->listInProgress());
    }

    public function key(string $key)
    {
        return $this->render('ride.index', __('message.riderequest'), app(RidesService::class)->listByKey($key));
    }

    public function view(string $source, string $id)
    {
        $pageTitle = __('message.riderequest');
        $assets = [];
        $service = app(\App\Services\FirestoreRestService::class);
        $data = $source === 'ride_requests'
            ? ($service->getDocumentFields('ride_requests', $id) ?? [])
            : ($service->getDocumentFields('rides', $id) ?? []);

        return view('ride.view', compact('pageTitle', 'assets', 'data', 'source', 'id'));
    }

    public function showDetails(string $id)
    {
        $pageTitle = __('message.riderequest');
        $assets = [];
        $service = app(DashboardService::class);
        $data = $service->getRideById($id) ?? [];

        return view('ride.details', compact('pageTitle', 'assets', 'data', 'id'));
    }

    private function render(string $view, string $pageTitle, array $rows)
    {
        $auth_user = authSession();
        $assets = [];
        $button = '';

        return view($view, compact('pageTitle', 'auth_user', 'assets', 'button', 'rows'));
    }
}

@php
    $url = '';

    $MyNavBar = \Menu::make('MenuList', function ($menu) use($url){
        $roles = session('admin.roles', []);
        $roles = is_array($roles) ? $roles : [];
        $isSuperAdmin = in_array('super_admin', $roles, true);
        $isStatsOnly = !$isSuperAdmin;

        $menu->add('<span>'.__('message.book_now').'</span>', [ 'class' => '', 'route' => 'dispatch.create'])
                ->prepend('<i class="fa fa-plus"></i>')
                ->data('permission', 'order-add')
                ->link->attr(['class' => '']);

        //Admin Dashboard
        $menu->add('<span>'.__('message.dashboard').'</span>', ['route' => 'home'])
            ->prepend('<i class="fas fa-home"></i>')
            ->link->attr(['class' => '']);

        if ($isStatsOnly) {
            $menu->add('<span>'.__('message.permission').'</span>', ['route' => 'permissions.index'])
                ->prepend('<i class="fas fa-shield-alt"></i>')
                ->link->attr(['class' => '']);
            return;
        }

        $menu->add('<span>'.__('message.rider').'</span>', ['class' => ''])
            ->prepend('<i class="fas fa-user"></i>')
            ->nickname('rider')
            ->data('permission', 'rider list')
            ->link->attr(['class' => ''])
            ->href('#rider');

            $menu->rider->add('<span>'.__('message.list_form_title',['form' => __('message.rider')]).'</span>', ['class' => 'sidebar-layout' ,'route' => 'rider.index'])
                ->data('permission', 'rider list')
                ->prepend('<i class="fas fa-list"></i>')
                ->link->attr(['class' => '']);

            $menu->rider->add('<span>'.__('message.add_form_title',['form' => __('message.rider')]).'</span>', ['class' => 'sidebar-layout' ,'route' => 'rider.create'])
                ->data('permission', [ 'rider add', 'rider edit'])
                ->prepend('<i class="fas fa-plus-square"></i>')
                ->link->attr(['class' => '']);

        $menu->add('<span>'.__('message.sub_admin').'</span>', ['class' => ''])
                ->prepend('<i class="fa fa-user-circle"></i>')
                ->nickname('sub_admin')
                ->data('permission', 'sub_admin-list')
                ->link->attr(['class' => ''])
                ->href('#sub_admin');

            $menu->sub_admin->add('<span>'.__('message.add_form_title',['form' => __('message.sub_admin')]).'</span>', ['class' => request()->is('country/*/edit') ? 'sidebar-layout active' : 'sidebar-layout' ,'route' => 'sub-admin.create'])
                ->data('permission', [ 'sub_admin-add', 'sub_admin-edit'])
                ->prepend('<i class="fas fa-plus-square"></i>')
                ->link->attr(['class' => '']);

            $menu->sub_admin->add('<span>'.__('message.list_form_title',['form' => __('message.sub_admin')]).'</span>', ['class' => 'sidebar-layout' ,'route' => 'sub-admin.index'])
                ->data('permission', 'sub_admin-list')
                ->prepend('<i class="fas fa-list"></i>')
                ->link->attr(['class' => '']);

        $menu->add('<span>'.__('message.service').'</span>', [ 'class' => '', 'route' => 'service.index'])
            ->prepend('<i class="fas fa-taxi"></i>')
            ->nickname('service')
            ->data('permission', 'service list')
            ->link->attr(['class' => ''])
            ->href('#service');

            $menu->service->add('<span>'.__('message.list_form_title',['form' => __('message.service')]).'</span>', ['class' => 'sidebar-layout' ,'route' => 'service.index'])
                ->data('permission', 'service list')
                ->prepend('<i class="fas fa-list"></i>')
                ->link->attr(['class' => '']);

            $menu->service->add('<span>'.__('message.add_form_title',['form' => __('message.service')]).'</span>', ['class' => request()->is('service/*/edit') ? 'sidebar-layout active' : 'sidebar-layout','route' => 'service.create'])
                ->data('permission', [ 'service add', 'service edit'])
                ->prepend('<i class="fas fa-plus-square"></i>')
                ->link->attr(['class' => '']);

        $menu->add('<span>'.__('message.driver').'</span>', ['class' => ''])
            ->prepend('<i class="fas fa-id-card"></i>')
            ->nickname('driver')
            ->data('permission', 'driver list')
            ->link->attr(['class' => ''])
            ->href('#driver');

            $menu->driver->add('<span>'.__('message.list_form_title',['form' => __('message.driver')]).'</span>', ['class' => 'sidebar-layout' ,'route' => 'driver.index'])
                ->data('permission', 'driver list')
                ->prepend('<i class="fas fa-list"></i>')
                ->link->attr(['class' => '']);

            $menu->driver->add('<span>'.__('message.pending_list_form_title',['form' => __('message.driver')]).'</span>', ['class' => 'sidebar-layout' ,'route' => 'driver.pending' ])
                ->data('permission', 'driver list')
                ->prepend('<i class="fas fa-list"></i>')
                ->link->attr(['class' => '']);

            $menu->driver->add('<span>'.__('message.add_form_title',['form' => __('message.driver')]).'</span>', ['class' => request()->is('driver/*/edit') ? 'sidebar-layout active' : 'sidebar-layout', 'route' => 'driver.create'])
                ->data('permission', [ 'driver add', 'driver edit'])
                ->prepend('<i class="fas fa-plus-square"></i>')
                ->link->attr(['class' => '']);

            $menu->driver->add('<span>'.__('message.manage_driver_document').'</span>', ['class' => ( request()->is('driverdocument') || request()->is('driverdocument/*') ) ? 'sidebar-layout active' : 'sidebar-layout', 'route' => 'driverdocument.index'])
                ->data('permission', ['driverdocument list'])
                ->prepend('<i class="fas fa-plus-square"></i>')
                ->link->attr(['class' => '']);

        $menu->add('<span>'.__('message.coupon').'</span>', [ 'class' => ''])
            ->prepend('<i class="fas fa-gift"></i>')
            ->nickname('coupon')
            ->data('permission', 'coupon list')
            ->link->attr(['class' => ''])
            ->href('#coupon');

            $menu->coupon->add('<span>'.__('message.list_form_title',['form' => __('message.coupon')]).'</span>', ['class' => 'sidebar-layout' ,'route' => 'coupon.index'])
                ->data('permission', 'coupon list')
                ->prepend('<i class="fas fa-list"></i>')
                ->link->attr(['class' => '']);

            $menu->coupon->add('<span>'.__('message.add_form_title',['form' => __('message.coupon')]).'</span>', ['class' => request()->is('coupon/*/edit') ? 'sidebar-layout active' : 'sidebar-layout', 'route' => 'coupon.create'])
                ->data('permission', [ 'coupon add', 'coupon edit'])
                ->prepend('<i class="fas fa-plus-square"></i>')
                ->link->attr(['class' => '']);

        $dashboard_metrics = app(App\Services\DashboardMetricsService::class)->getDashboardMetrics();
        $new_ride_request = $dashboard_metrics['newRideRequests'] ?? 0;
        $menu->add('<span>'.__('message.riderequest').'</span>'. ($new_ride_request > 0 ? '<span class="badge badge-primary ride-badge">'.$new_ride_request.'</span>' : '') , [ 'class' => '' ])
            ->prepend('<i class="fas fa-car-side"></i>')
            ->nickname('riderequest')
            ->data('permission', 'riderequest list')
            ->link->attr(['class' => ''])
            ->href('#riderequest');

            $menu->riderequest->add('<span>'.__('message.list_form_title',['form' => __('message.all')]).'</span>', ['class' => 'sidebar-layout' ,'route' => 'ride.all'])
                ->data('permission', 'riderequest list')
                ->prepend('<i class="fas fa-list"></i>')
                ->link->attr(['class' => '']);

            $menu->riderequest->add('<span>'.__('message.list_form_title',['form' => __('message.new_ride')]).'</span>', ['class' => 'sidebar-layout' ,'route' => 'ride.new'])
                ->data('permission', 'riderequest list')
                ->prepend('<i class="fas fa-list mr-1"></i>'.($new_ride_request > 0 ? '<span class="badge badge-primary ride-badge">'.$new_ride_request.'</span>' : ''))
                ->link->attr(['class' => '']);

            $menu->riderequest->add('<span>'.__('message.list_form_title',['form' => __('message.completed')]).'</span>', ['class' => 'sidebar-layout' ,'route' => 'ride.completed'])
                ->data('permission', 'riderequest list')
                ->prepend('<i class="fas fa-list"></i>')
                ->link->attr(['class' => '']);

            $menu->riderequest->add('<span>'.__('message.list_form_title',['form' => __('message.canceled')]).'</span>', ['class' => 'sidebar-layout' ,'route' => 'ride.cancelled'])
                ->data('permission', 'riderequest list')
                ->prepend('<i class="fas fa-list"></i>')
                ->link->attr(['class' => '']);

            $menu->riderequest->add('<span>'.__('message.list_form_title',['form' => __('message.pending')]).'</span>', ['class' => 'sidebar-layout' ,'route' => 'ride.inprogress'])
                ->data('permission', 'riderequest list')
                ->prepend('<i class="fas fa-list"></i>')
                ->link->attr(['class' => '']);

        $menu->add('<span>الطوارئ</span>', [ 'class' => ''])
            ->prepend('<i class="fas fa-file-alt"></i>')
            ->nickname('sos')
            ->data('permission', 'sos list')
            ->link->attr(['class' => ''])
            ->href('#sos');

            $menu->sos->add('<span>قائمة الطوارئ</span>', ['class' => 'sidebar-layout' ,'route' => 'sos.index'])
                ->data('permission', 'sos list')
                ->prepend('<i class="fas fa-list"></i>')
                ->link->attr(['class' => '']);

        $menu->add('<span>'.__('message.surge_price').'</span>', [ 'class' => '', 'route' => 'surge-prices.index'])
            ->prepend('<i class="fas fa-dollar-sign"></i>')
            ->data('permission', 'surgeprice list')
            ->link->attr(['class' => '']);

        $pending_withdraw_request = $dashboard_metrics['pendingWithdrawRequests'] ?? 0;
        $menu->add('<span>'.__('message.withdrawrequest').'</span>'.($pending_withdraw_request > 0 ? '<span class="badge badge-dark ride-badge">'.$pending_withdraw_request.'</span>' : ''), ['class' => ''])
            ->prepend('<i class="fas fa-money-check"></i>')
            ->nickname('withdrawrequest')
            ->data('permission', 'withdrawrequest list')
            ->link->attr(['class' => ''])
            ->href('#withdrawrequest');

            $menu->withdrawrequest->add('<span>'.__('message.all').'</span>', ['class' => 'sidebar-layout' ,'route' => ['withdrawrequest.index','withdraw_type' => 'all']])
                ->data('permission', 'withdrawrequest list')
                ->prepend('<i class="fas fa-list"></i>')
                ->link->attr(['class' => '']);

            $menu->withdrawrequest->add('<span>'.__('message.list_form_title',['form' => __('message.pending')]).'</span>', ['class' => 'sidebar-layout' ,'route' => ['withdrawrequest.index','withdraw_type'=>'pending']])
                ->data('permission', 'withdrawrequest list')
                ->prepend('<i class="fas fa-list"></i>'.($pending_withdraw_request > 0 ? '<span class="badge badge-dark ride-badge">'.$pending_withdraw_request.'</span>' : ''))
                ->link->attr(['class' => '']);

            $menu->withdrawrequest->add('<span>'.__('message.list_form_title',['form' => __('message.approved')]).'</span>', ['class' => 'sidebar-layout' ,'route' => ['withdrawrequest.index','withdraw_type'=>'approved']])
                ->data('permission', 'withdrawrequest list')
                ->prepend('<i class="fas fa-list"></i>')
                ->link->attr(['class' => '']);

            $menu->withdrawrequest->add('<span>'.__('message.list_form_title',['form' => __('message.decline')]).'</span>', ['class' => 'sidebar-layout' ,'route' => ['withdrawrequest.index','withdraw_type'=>'decline']])
                ->data('permission', 'withdrawrequest list')
                ->prepend('<i class="fas fa-list"></i>')
                ->link->attr(['class' => '']);

        $menu->add('<span>'.__('message.payment').'</span>', ['class' => ''])
            ->prepend('<i class="ri-secure-payment-fill" style="font-size: 22px;"></i>')
            ->nickname('payment')
            ->data('permission', 'payment-list')
            ->link->attr(['class' => ''])
            ->href('#payment');

            $menu->payment->add('<span>'. __('message.online_payment').'</span>', ['class' => 'sidebar-layout' ,'route' => ['payment.index','payment_type'=>'online']])
                ->data('permission', 'online-payment-list')
                ->prepend('<i class="fas fa-list"></i>')
                ->link->attr(['class' => '']);

            $menu->payment->add('<span>'.__('message.cash_payment').'</span>', ['class' => 'sidebar-layout' ,'route' => ['payment.index','payment_type'=>'cash']])
                ->data('permission', 'cash-payment-list')
                ->prepend('<i class="fas fa-list"></i>')
                ->link->attr(['class' => '']);

            $menu->payment->add('<span>'.__('message.wallet_payment').'</span>', ['class' => 'sidebar-layout' ,'route' => ['payment.index','payment_type'=>'wallet']])
                ->data('permission', 'wallet-payment-list')
                ->prepend('<i class="fas fa-list"></i>')
                ->link->attr(['class' => '']);


                $requestCount = $dashboard_metrics['pendingSupportRequests'] ?? 0;
                $count = '<span class="badge badge-dark ride-badge" id="requestCount">' . $requestCount . '</span>';

        /*$menu->add('<span>'.__('message.customer_support').' ' . $count .'</span>', ['class' => ''])
            ->prepend('<i class="fa fa-headset"></i>')
            ->nickname('customersupport')
            ->data('permission', 'customersupport-list')
            ->link->attr(['class' => ''])
            ->href('#customersupport');

            $menu->customersupport->add('<span>'.__('message.all').'</span>', ['class' => 'sidebar-layout' ,'route' => ['customersupport.index','status_type'=>'all']])
                ->data('permission', 'customersupport-list')
                ->prepend('<i class="fas fa-list"></i>')
                ->link->attr(['class' => '']);

            $menu->customersupport->add('<span>'.__('message.pending').' ' . $count .'</span>', ['class' => 'sidebar-layout' ,'route' =>['customersupport.index','status_type'=>'pending']])
                ->data('permission', 'customersupport-list')
                ->prepend('<i class="fas fa-list"></i>')
                ->link->attr(['class' => '']);

            $menu->customersupport->add('<span>'.__('message.inreview').'</span>', ['class' => 'sidebar-layout' ,'route' => ['customersupport.index','status_type'=>'inreview']])
                ->data('permission', 'customersupport-list')
                ->prepend('<i class="fas fa-list"></i>')
                ->link->attr(['class' => '']);

            $menu->customersupport->add('<span>'.__('message.resolved').'</span>', ['class' => 'sidebar-layout' ,'route' => ['customersupport.index','status_type'=>'resolved']])
                ->data('permission', 'customersupport-list')
                ->prepend('<i class="fas fa-list"></i>')
                ->link->attr(['class' => '']); */

        $menu->add('<span>'.__('message.account_setting').'</span>', ['class' => ''])
            ->prepend('<i class="fas fa-users-cog"></i>')
            ->nickname('account_setting')
            ->data('permission', ['role list','permission list'])
            ->link->attr(["class" => ""])
            ->href('#account_setting');

            $menu->account_setting->add('<span>'.__('message.list_form_title',['form' => __('message.permission')]).'</span>', ['class' => 'sidebar-layout' ,'route' => 'permissions.index'])
                ->data('permission', 'permission list')
                ->prepend('<i class="fas fa-list"></i>')
                ->link->attr(['class' => '']);

        $menu->add('<span>'.__('message.driver_location').'</span>', ['route' => 'map'])
                ->prepend('<i class="fas fa-map"></i>')
                ->nickname('map')
                ->data('permission', 'driver location');

        })->filter(function ($item) {
            return checkMenuRoleAndPermission($item);
        });
@endphp

<div class="mm-sidebar sidebar-default">
    <div class="mm-sidebar-logo d-flex align-items-center justify-content-between">
        <a href="{{ route('home') }}" class="header-logo" >
            @php
                $app_settings = $app_settings ?? appSettingData('get');
            @endphp
            <img src="{{ getSingleMedia($app_settings,'site_logo',null) }}" class="img-fluid mode light-img rounded-normal light-logo site_logo_preview" alt="logo">
            <img src="{{ getSingleMedia($app_settings,'site_dark_logo',null) }}" class="img-fluid mode dark-img rounded-normal darkmode-logo site_dark_logo_preview" alt="dark-logo">
        </a>
        <div class="side-menu-bt-sidebar">
            <i class="fas fa-bars wrapper-menu"></i>
        </div>
    </div>

    <div class="data-scrollbar" data-scroll="1">
        <nav class="mm-sidebar-menu">
            <ul id="mm-sidebar-toggle" class="side-menu">
                @include(config('laravel-menu.views.bootstrap-items'), ['items' => $MyNavBar->roots()])
            </ul>
        </nav>
        <div class="pt-5 pb-5"></div>
    </div>
</div>

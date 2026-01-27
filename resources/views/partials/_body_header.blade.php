<div class="mm-top-navbar">
    <div class="mm-navbar-custom">
        <nav class="navbar navbar-expand-lg navbar-light p-0">
            <div class="mm-navbar-logo d-flex align-items-center justify-content-between">
                <i class="fas fa-bars wrapper-menu"></i>
                <a href="{{ asset('/') }}" class="header-logo">
                    @php
                        $app_settings = appSettingData('get');
                    @endphp
                    <img src="{{ getSingleMedia($app_settings,'site_logo',null) }}" class="img-fluid rounded-normal site_logo_preview app-logo" alt="logo">
                    <span class="ml-1 font-weight-bold">Take Me</span>
                </a>
            </div>
            <div class="mm-search-bar device-search m-auto">
                <!-- <form action="#" class="searchbox">
                    <a class="search-link" href="#"><i class="ri-search-line"></i></a>
                    <input type="text" class="text search-input" placeholder="Search here...">
                </form> -->
            </div>
            <div class="d-flex align-items-center">
                @php
                    $admin_name = session('admin_name', 'Admin');
                    $hasUnreadSos = app(\App\Services\SosBellService::class)->hasUnreadSos();
                @endphp
                <div class="change-mode">
                    <div class="custom-control custom-switch custom-switch-icon custom-control-inline">
                        <div class="custom-switch-inner">
                            <p class="mb-0"> </p>
                            <input type="checkbox" class="custom-control-input" id="dark-mode" data-active="true">
                            <label class="custom-control-label" for="dark-mode" data-mode="toggle">
                                <span class="switch-icon-left">
                                    <svg class="svg-icon" id="h-moon" height="20" width="20" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                                    </svg>
                                </span>
                                <span class="switch-icon-right">
                                    <svg class="svg-icon" id="h-sun" height="20" width="20" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                                    </svg>
                                </span>
                            </label>
                        </div>
                    </div>
                </div>
                <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent"  aria-label="Toggle navigation">
                    <i class="ri-menu-3-line"></i>
                </button>
                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <ul class="navbar-nav ml-auto navbar-list align-items-center">                        
                        <li class="nav-item nav-icon dropdown">
                            <a href="#" class="search-toggle dropdown-toggle notification_list" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" >
                                <svg class="svg-icon {{ $hasUnreadSos ? 'bell--alert' : 'text-primary' }}" id="mm-bell-2" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-bell"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                                <span class="bg-primary "></span>
                                <span class="badge badge-pill badge-primary badge-up notify_count count-mail d-none"></span>
                                <span class="bg-primary dots d-none"></span>
                            </a>
                            <div class="mm-sub-dropdown dropdown-menu notification-menu" aria-labelledby="dropdownMenuButton">
                                <div class="card shadow-none m-0 border-0">
                                    <div class="card-body p-0 notification_data">
                                    </div>
                                </div>
                            </div>
                        </li>

                        <li class="nav-item nav-icon d-flex align-items-center">
                            <span class="ml-2 d-none d-lg-inline">{{ $admin_name }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </div>
</div>
<style>
    .bell--alert { color: #dc3545; }
</style>

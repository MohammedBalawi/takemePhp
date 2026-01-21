<?php

namespace App\Providers;

use App\Auth\AdminSessionUserProvider;
use App\Auth\AdminFirestoreUserProvider;
use App\Services\AdminAuth\AdminFirestoreRepository;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Auth::provider('admin_session', function ($app, array $config) {
            return new AdminSessionUserProvider();
        });

        Auth::provider('admin_firestore', function ($app, array $config) {
            return new AdminFirestoreUserProvider($app->make(AdminFirestoreRepository::class));
        });
    }
}

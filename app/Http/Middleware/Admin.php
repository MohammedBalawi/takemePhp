<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

class Admin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {   
        if ((bool) env('PUBLIC_ADMIN_PANEL', false)) {
            return $next($request);
        }

        if (Auth::guard('admin')->check()) {
            $user = Auth::guard('admin')->user();

            // List of roles that are NOT allowed to login
            $restrictedRoles = ['rider', 'driver'];

            if ($user->hasAnyRole($restrictedRoles)) {
                Auth::guard('admin')->logout();
                abort(403, __('message.access_denied'));
            }

            return $next($request);
        }

        Auth::guard('admin')->logout();
        abort(403, __('message.access_denied'));
    }

}

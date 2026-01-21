<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminSessionAuth
{
    public function handle(Request $request, Closure $next)
    {
        if ((bool) env('PUBLIC_ADMIN_PANEL', false)) {
            return $next($request);
        }

        if ($request->routeIs('login') || $request->is('login')) {
            if ($request->session()->get('is_admin') === true) {
                return redirect('/home');
            }
            return $next($request);
        }

        if ($request->session()->get('is_admin') !== true) {
            return redirect()->route('login');
        }

        return $next($request);
    }
}

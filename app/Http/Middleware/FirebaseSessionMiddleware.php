<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class FirebaseSessionMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->routeIs('login') || $request->is('login')) {
            return $next($request);
        }

        $uid = $request->session()->get('firebase_uid');
        if (!is_string($uid) || $uid === '') {
            return redirect()->route('login');
        }

        return $next($request);
    }
}

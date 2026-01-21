<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class AdminLanguageMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $language = $request->session()->get('admin_language', 'ar');
        if (is_string($language) && $language !== '') {
            App::setLocale($language);
        } else {
            App::setLocale('ar');
        }

        return $next($request);
    }
}

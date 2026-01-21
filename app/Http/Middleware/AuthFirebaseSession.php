<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AuthFirebaseSession
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->routeIs('login') || $request->is('login')) {
            if ($request->session()->has('admin')) {
                return redirect('/home');
            }
            return $next($request);
        }

        $admin = $request->session()->get('admin');
        if (!is_array($admin) || empty($admin['email'])) {
            return $this->guardRedirect($request, route('login'));
        }

        return $next($request);
    }

    private function guardRedirect(Request $request, string $target)
    {
        $key = '_redirect_loop_guard';
        $state = $request->session()->get($key, []);
        $lastTarget = is_array($state) ? ($state['target'] ?? null) : null;
        $lastAt = is_array($state) ? (int) ($state['at'] ?? 0) : 0;
        $now = time();

        if ($lastTarget === $target && ($now - $lastAt) < 5) {
            Log::warning('REDIRECT_LOOP_ABORT target=' . $target);
            abort(409, 'Redirect loop detected');
        }

        Log::info('REDIRECT_TARGET target=' . $target);
        $request->session()->put($key, ['target' => $target, 'at' => $now]);
        return redirect()->guest($target);
    }
}

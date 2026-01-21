<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Support\FeatureFlags;

class FirebaseSession
{
    public function handle(Request $request, Closure $next)
    {
        if (FeatureFlags::useMock()) {
            if (!Auth::guard('admin')->check()) {
                $user = new \App\Auth\FirestoreAdminUser([
                    'id' => 'mock-admin',
                    'email' => 'mock@example.com',
                    'name' => 'Mock Admin',
                    'roles' => ['super_admin'],
                    'is_active' => true,
                ]);
                Auth::guard('admin')->setUser($user);
            }
            return $next($request);
        }

        if (Auth::guard('admin')->check()) {
            return $next($request);
        }

        $session = $request->session()->get('firebase_auth', []);
        $localId = $session['localId'] ?? null;
        $idToken = $session['idToken'] ?? null;

        if (!is_string($localId) || $localId === '' || !is_string($idToken) || $idToken === '') {
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

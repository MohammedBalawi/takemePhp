<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\FirestoreAdminAuth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        if (session()->has('admin')) {
            return redirect('/home');
        }

        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     *
     * @param  \App\Http\Requests\Auth\LoginRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(LoginRequest $request)
    {
        $request->ensureIsNotRateLimited();

        $credentials = $request->validated();
        $email = strtolower(trim((string) $credentials['email']));

        $authService = app(FirestoreAdminAuth::class);
        $admin = $authService->attempt($email, (string) $credentials['password']);
        if (!$admin) {
            RateLimiter::hit($request->throttleKey());
            if (app()->environment('local')) {
                logger()->info('ADMIN_AUTH_FAIL reason=invalid_credentials email=' . $email);
            }
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $request->session()->put('admin', $admin);
        $request->session()->put('admin_name', $admin['name'] ?? 'Admin');
        $request->session()->put('admin_language', 'ar');
        $request->session()->put('is_admin', true);
        $request->session()->regenerate();
        RateLimiter::clear($request->throttleKey());

        return redirect('/home');
    }

    /**
     * Destroy an authenticated session.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request)
    {
        $request->session()->forget([
            'admin',
            'admin_name',
            'admin_language',
            'is_admin',
        ]);

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }

}

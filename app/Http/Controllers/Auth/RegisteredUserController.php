<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Services\FirestoreBridge;
use App\Auth\FirestoreAdminUser;
use Carbon\Carbon;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $assets = ['phone'];
        return view('auth.register',compact('assets'));
    }

    /**
     * Handle an incoming registration request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'username' => 'nullable|max:255',
            'contact_number' => 'nullable|max:20',
            'password' => 'required|string|confirmed|min:8',
        ]);

        if (!\App\Support\FeatureFlags::shouldUseFirestore('ADMINS')) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $email = strtolower(trim((string) $request->email));
        $displayName = trim($request->first_name . ' ' . $request->last_name);

        $bridge = app(FirestoreBridge::class);
        $authResponse = $bridge->register($email, (string) $request->password, $displayName);
        if (empty($authResponse['ok']) || empty($authResponse['localId'])) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $localId = (string) $authResponse['localId'];
        $now = Carbon::now('UTC')->toIso8601String();
        $bridge->set('users', $localId, [
            'uid' => $localId,
            'email' => $email,
            'name' => $displayName,
            'roles' => ['admin'],
            'username' => $request->username,
            'contact_number' => $request->contact_number,
            'createdAt' => $now,
            'updatedAt' => $now,
        ], true);

        $sessionAdmin = [
            'id' => $localId,
            'email' => $email,
            'name' => $displayName,
            'roles' => ['admin'],
            'is_active' => true,
            'user_type' => 'admin',
        ];

        $request->session()->put('firebase_auth', [
            'idToken' => $authResponse['idToken'] ?? null,
            'refreshToken' => $authResponse['refreshToken'] ?? null,
            'localId' => $localId,
            'email' => $email,
        ]);
        $request->session()->put('admin_auth', $sessionAdmin);
        Auth::guard('admin')->login(new FirestoreAdminUser($sessionAdmin));
        $request->session()->regenerate();
        return redirect(RouteServiceProvider::HOME);
    }
}

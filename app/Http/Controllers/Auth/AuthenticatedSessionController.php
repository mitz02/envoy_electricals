<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = $request->user();

        // Determine redirect based on user type.
        // A staff/super-admin user may also hold a trainee record, so the
        // staff role takes priority: they always land on the admin dashboard.
        if ($user->role && $user->role->slug === 'buyer') {
            // Website buyers sign in via /login and land on their account.
            return redirect(route('buyer.dashboard', absolute: false));
        }

        if ($user->role && $user->role->slug !== 'trainee') {
            return redirect(route('admin.dashboard', absolute: false));
        }

        // Explicitly load trainee relationship to ensure it's available.
        $user->load('trainee');

        if ($user->trainee) {
            // Trainee/apprentice -> redirect to portal dashboard
            return redirect(route('portal.dashboard', absolute: false));
        }

        // Admin/staff -> redirect to admin dashboard
        return redirect(route('admin.dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}

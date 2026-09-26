<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureBuyerRole
{
    /**
     * Require an authenticated buyer before entering the buyer account area.
     *
     * Guests go to /login. Authenticated users whose role is not "buyer"
     * (staff, trainees) are redirected to their own dashboard instead of
     * being shown the buyer account pages.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->guest(route('login'));
        }

        $slug = $user->role?->slug;

        // Legacy registered buyers who predate the buyer role have no role.
        if ($slug === 'buyer' || $slug === null) {
            return $next($request);
        }

        if ($slug === 'trainee') {
            return redirect()->route('portal.dashboard');
        }

        return redirect()->route('admin.dashboard');
    }
}

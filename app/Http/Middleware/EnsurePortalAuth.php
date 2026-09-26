<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class EnsurePortalAuth
{
    /**
     * Require an authenticated session before entering the training portal,
     * redirecting guests to the dedicated portal login page.
     */
    public function handle(Request $request, Closure $next): SymfonyResponse|Response
    {
        if (! Auth::check()) {
            return redirect()->guest(route('portal.login'));
        }

        return $next($request);
    }
}

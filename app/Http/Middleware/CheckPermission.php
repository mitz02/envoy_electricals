<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(Request $request, Closure $next, ...$permissions): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403);
        }

        $can = false;
        foreach ($permissions as $permission) {
            if ($permission === 'authenticated' || $user->hasPermission($permission)) {
                $can = true;
                break;
            }
        }

        if (! $can) {
            abort(403, 'You do not have permission to access this page.');
        }

        return $next($request);
    }
}

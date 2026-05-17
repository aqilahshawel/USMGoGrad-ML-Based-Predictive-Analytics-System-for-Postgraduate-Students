<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ForcePasswordChange
{
    /**
     * Redirect users who are still on their default password to the change-password screen.
     */
    public function handle(Request $request, Closure $next)
    {
        if (! Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();
        $mustChange = (bool) ($user->force_password_change ?? false);

        if (! $mustChange) {
            return $next($request);
        }

        if (
            $request->routeIs('password.change')
            || $request->routeIs('password.update')
            || $request->routeIs('logout')
        ) {
            return $next($request);
        }

        return redirect()->route('password.change');
    }
}

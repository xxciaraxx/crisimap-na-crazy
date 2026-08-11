<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountSetupComplete
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->must_complete_setup && ! $request->routeIs('account.setup.*', 'logout')) {
            return redirect()->route('account.setup.create');
        }

        return $next($request);
    }
}

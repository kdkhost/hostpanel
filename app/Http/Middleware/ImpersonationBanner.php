<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ImpersonationBanner
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if (session()->has('impersonation') && !$request->expectsJson()) {
            $data = session('impersonation');
            view()->share('impersonating', true);
            view()->share('impersonation_data', $data);
        }

        return $response;
    }
}

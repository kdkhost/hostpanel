<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminAuthenticate
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::guard('admin')->check()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Não autenticado.'], 401);
            }
            return redirect()->route('admin.login')->with('error', 'Faça login para continuar.');
        }

        $admin = Auth::guard('admin')->user();

        if ($admin->status !== 'active') {
            Auth::guard('admin')->logout();
            return redirect()->route('admin.login')->with('error', 'Conta administrativa inativa.');
        }

        return $next($request);
    }
}

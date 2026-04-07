<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClientAuthenticate
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::guard('client')->check()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Não autenticado.'], 401);
            }
            return redirect()->route('client.login')->with('error', 'Faça login para continuar.');
        }

        $client = Auth::guard('client')->user();

        if ($client->status === 'blocked') {
            Auth::guard('client')->logout();
            return redirect()->route('client.login')->with('error', 'Conta bloqueada. Entre em contato com o suporte.');
        }

        return $next($request);
    }
}

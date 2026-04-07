<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use Closure;
use Illuminate\Http\Request;

class ApiTokenAuthenticate
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken() ?? $request->query('api_token');

        if (!$token) {
            return response()->json(['message' => 'Token de API não fornecido.'], 401);
        }

        $apiToken = \App\Models\ApiToken::where('token', hash('sha256', $token))
            ->where('active', true)
            ->first();

        if (!$apiToken) {
            return response()->json(['message' => 'Token inválido ou inativo.'], 401);
        }

        if ($apiToken->expires_at && $apiToken->expires_at->isPast()) {
            return response()->json(['message' => 'Token expirado.'], 401);
        }

        $apiToken->update(['last_used_at' => now(), 'uses_count' => $apiToken->uses_count + 1]);
        $request->attributes->set('api_token', $apiToken);

        return $next($request);
    }
}

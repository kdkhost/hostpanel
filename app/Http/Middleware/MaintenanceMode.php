<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MaintenanceMode
{
    public function handle(Request $request, Closure $next): Response
    {
        // Skip for admin routes and install routes
        if (str_starts_with($request->path(), 'admin') || str_starts_with($request->path(), 'install')) {
            return $next($request);
        }

        $enabled = Setting::get('maintenance.enabled', false);

        if (!$enabled || $enabled === '0' || $enabled === 'false') {
            return $next($request);
        }

        // Check IP whitelist
        $allowedIps = array_filter(array_map('trim', explode("\n", Setting::get('maintenance.allowed_ips', ''))));
        $clientIp   = $request->ip();

        if (in_array($clientIp, $allowedIps)) {
            return $next($request);
        }

        // Check secret bypass token in query string
        $secret = Setting::get('maintenance.secret', '');
        if ($secret && $request->query('bypass') === $secret) {
            // Set cookie to bypass for 24h
            return $next($request)->withCookie(cookie('maintenance_bypass', $secret, 1440));
        }

        // Check bypass cookie
        if ($secret && $request->cookie('maintenance_bypass') === $secret) {
            return $next($request);
        }

        $message = Setting::get('maintenance.message', 'Estamos realizando melhorias no sistema. Voltaremos em breve!');

        return response()->view('errors.503', [
            'exception' => new \Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException(null, $message),
        ], 503);
    }
}

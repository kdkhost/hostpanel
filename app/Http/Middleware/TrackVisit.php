<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class TrackVisit
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only track GET requests to HTML pages (not API, assets, admin)
        if (
            $request->isMethod('GET')
            && !$request->expectsJson()
            && !str_starts_with($request->path(), 'admin')
            && !str_starts_with($request->path(), 'api')
            && !str_starts_with($request->path(), 'install')
            && !str_starts_with($request->path(), '_')
            && $response->getStatusCode() === 200
        ) {
            try {
                if (Schema::hasTable('page_views')) {
                    DB::table('page_views')->insert([
                        'path'       => substr($request->path(), 0, 255),
                        'ip'         => $request->ip(),
                        'user_agent' => substr($request->userAgent() ?? '', 0, 500),
                        'referer'    => substr($request->header('referer', ''), 0, 500),
                        'client_id'  => $request->user('client')?->id,
                        'visited_at' => now(),
                    ]);
                }
            } catch (\Throwable $e) {
                // Silently fail — visit tracking should never break the app
            }
        }

        return $response;
    }
}

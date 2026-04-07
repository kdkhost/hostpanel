<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class InstallerCheck
{
    public function handle(Request $request, Closure $next)
    {
        $installed = file_exists(storage_path('installed'));
        $isInstallRoute = str_starts_with($request->path(), 'install');

        if (!$installed && !$isInstallRoute) {
            return redirect()->route('install.index');
        }

        if ($installed && $isInstallRoute) {
            return redirect()->route('home');
        }

        return $next($request);
    }
}

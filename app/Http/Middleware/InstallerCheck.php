<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InstallerCheck
{
    public function handle(Request $request, Closure $next): Response
    {
        $isInstallRoute = str_starts_with($request->path(), 'install');
        
        // Verificar se está instalado (arquivo storage/installed existe)
        try {
            $installed = file_exists(storage_path('installed'));
        } catch (\Throwable $e) {
            // Se não conseguir verificar (ex: storage não existe), assumir não instalado
            $installed = false;
        }

        // Se não instalado E não está em rota de install → redirecionar para install
        if (!$installed && !$isInstallRoute) {
            return redirect()->route('install.index');
        }

        // Se já instalado E tentando acessar install → redirecionar para home
        if ($installed && $isInstallRoute) {
            return redirect()->route('home');
        }

        return $next($request);
    }
}

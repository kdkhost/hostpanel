<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'auth.admin'          => \App\Http\Middleware\AdminAuthenticate::class,
            'auth.client'         => \App\Http\Middleware\ClientAuthenticate::class,
            'impersonation.banner'=> \App\Http\Middleware\ImpersonationBanner::class,
            'api.token'           => \App\Http\Middleware\ApiTokenAuthenticate::class,
            'installer.check'     => \App\Http\Middleware\InstallerCheck::class,
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\InstallerCheck::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'admin/webhook/*',
            'api/webhook/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

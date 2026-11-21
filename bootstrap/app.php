<?php

use App\Http\Middleware\EnsureUserHasRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => EnsureUserHasRole::class,
        ]);

        // Hosting platforms put a proxy in front of the app. Trusting it lets Laravel see the real
        // scheme and address of the visitor. Set TRUSTED_PROXIES to a list of addresses to narrow it.
        $middleware->trustProxies(at: env('TRUSTED_PROXIES', '*'));
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();

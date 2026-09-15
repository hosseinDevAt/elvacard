<?php

use App\Http\Middleware\Admin;
use App\Http\Middleware\EnsurePasswordSession;
use App\Http\Middleware\EnsureUserStatus;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => Admin::class,
        ]);

        $middleware->web(append: [
            EnsurePasswordSession::class,
            EnsureUserStatus::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'checkout/payment/callback/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();

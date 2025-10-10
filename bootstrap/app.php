<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\AuthenticationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
       ->withMiddleware(function (Middleware $middleware): void {
           $middleware->alias([
               'admin' => \App\Http\Middleware\AdminMiddleware::class,
               'webhook' => \App\Http\Middleware\WebhookMiddleware::class,
           ]);
       })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Handle unauthenticated requests for API routes
        $exceptions->render(function (AuthenticationException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated. Please login.',
                    'error' => 'Authentication required'
                ], 401);
            }
        });
    })->create();

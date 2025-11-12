<?php

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
        // Register global middleware to track session activity
        $middleware->web(append: [
            \App\Http\Middleware\TrackSessionActivity::class,
        ]);

        // Register route middleware
        $middleware->alias([
            'academic.period.set' => \App\Http\Middleware\EnsureAcademicPeriodSet::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();

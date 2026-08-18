<?php

declare(strict_types=1);

use App\Http\Middleware\EnforceIndexingPolicy;
use App\Http\Middleware\EnsureFeature;
use App\Http\Middleware\SecurityHeaders;
use App\Support\ExceptionReporter;
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
        // Interdit l'indexation (moteurs + robots IA) tant que le site n'est pas
        // declare indexable. En-tete pose sur toutes les reponses web.
        $middleware->web(append: [
            EnforceIndexingPolicy::class,
            SecurityHeaders::class,
        ]);

        // feature:capacite -> bloque la route (404) si la capacité n'est pas active
        // dans le mode de plateforme courant.
        $middleware->alias([
            'feature' => EnsureFeature::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        // Notification email a l'admin sur exception non geree (throttlee, cf. config lms).
        $exceptions->report(function (Throwable $e): void {
            app(ExceptionReporter::class)->report($e);
        });
    })->create();

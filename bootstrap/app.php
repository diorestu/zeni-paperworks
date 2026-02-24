<?php

use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Inertia\Inertia;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'webhooks/midtrans',
        ]);

        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureRole::class,
            'verified_account' => \App\Http\Middleware\EnsureAccountVerified::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (Throwable $exception, Request $request) {
            if (config('app.debug')) {
                return null;
            }

            $status = $exception instanceof HttpExceptionInterface
                ? $exception->getStatusCode()
                : 500;

            if ($status === 419) {
                return redirect()->back()->with('error', 'The page expired, please try again.');
            }

            if (! in_array($status, [403, 404, 429, 500, 503], true)) {
                $status = 500;
            }

            if ($request->header('X-Inertia')) {
                return Inertia::render('Error', [
                    'status' => $status,
                ])->toResponse($request)->setStatusCode($status);
            }

            return response()->view("errors.{$status}", [
                'status' => $status,
            ], $status);
        });
    })->create();

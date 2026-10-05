<?php

use App\Http\Middleware\EnsureAgencyProfileExists;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\EnsureUserIsNotSuspended;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\AuthenticateSession;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            AuthenticateSession::class,
            HandleInertiaRequests::class,
        ]);

        $middleware->encryptCookies(except: [
            HandleInertiaRequests::COOKIE_CONSENT,
        ]);

        $middleware->alias([
            'role' => EnsureUserHasRole::class,
            'not-suspended' => EnsureUserIsNotSuspended::class,
            'agency.exists' => EnsureAgencyProfileExists::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        $exceptions->render(function (ThrottleRequestsException $exception, Request $request): ?RedirectResponse {
            if (! $request->header('X-Inertia')) {
                return null;
            }

            $minutes = (int) max(1, ceil(((int) ($exception->getHeaders()['Retry-After'] ?? 60)) / 60));

            return back()->with('throttled', trans_choice('app.rate_limit.throttled', $minutes, ['minutes' => $minutes]));
        });
    })->create();

<?php

use App\Http\Middleware\AssignRequestId;
use App\Http\Middleware\EnsureEmailIsVerifiedWhenEnabled;
use App\Http\Middleware\AuthenticateApiKey;
use App\Http\Middleware\EnsurePartnerActive;
use App\Http\Middleware\EnforceApiScopes;
use App\Http\Middleware\HandleIdempotency;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\LogApiRequest;
use App\Http\Middleware\ThrottlePartnerApi;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            Route::middleware('api')
                ->group(base_path('routes/webhooks.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'verified' => EnsureEmailIsVerifiedWhenEnabled::class,
            'request.id' => AssignRequestId::class,
            'api.key' => AuthenticateApiKey::class,
            'partner.active' => EnsurePartnerActive::class,
            'api.scopes' => EnforceApiScopes::class,
            'api.idempotency' => HandleIdempotency::class,
            'api.log' => LogApiRequest::class,
            'api.throttle' => ThrottlePartnerApi::class,
        ]);

        $middleware->web(append: [
            HandleInertiaRequests::class,
        ]);

        // Guest middleware on /login and /signup must not send signed-in partners back to marketing home.
        $middleware->redirectUsersTo(fn () => route('app.dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();

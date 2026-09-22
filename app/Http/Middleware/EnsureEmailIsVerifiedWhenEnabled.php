<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;

class EnsureEmailIsVerifiedWhenEnabled extends EnsureEmailIsVerified
{
    /**
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $redirectToRoute
     * @return mixed
     */
    public function handle($request, Closure $next, $redirectToRoute = null)
    {
        if (! config('auth.email_verification_enabled')) {
            return $next($request);
        }

        return parent::handle($request, $next, $redirectToRoute);
    }
}

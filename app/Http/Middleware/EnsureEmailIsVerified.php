<?php

namespace App\Http\Middleware;

use App\Exceptions\UnauthorizedActionException;
use Closure;
use Illuminate\Http\Request;

class EnsureEmailIsVerified
{
    public function handle(Request $request, Closure $next)
    {
        if (! $request->user()->hasVerifiedEmail()) {
            throw new UnauthorizedActionException('Please verify your email address before continuing.');
        }

        return $next($request);
    }
}

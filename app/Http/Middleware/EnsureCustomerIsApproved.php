<?php

namespace App\Http\Middleware;

use App\Exceptions\UnauthorizedActionException;
use Closure;
use Illuminate\Http\Request;

class EnsureCustomerIsApproved
{
    public function handle(Request $request, Closure $next)
    {
        if (! $request->user()->is_approved) {
            throw new UnauthorizedActionException('Your account is pending admin approval.');
        }

        return $next($request);
    }
}

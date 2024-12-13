<?php

namespace MainSys\Http\Middleware;

use Closure;

class AuthenticateToken
{
    public function handle($request, Closure $next)
    {
        return $next($request);
    }
}

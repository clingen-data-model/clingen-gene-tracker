<?php

namespace App\Http\Middleware;

use Closure;

class LogRequestsReceived
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        logDebug('Request received', $request->all());

        return $next($request);
    }
}

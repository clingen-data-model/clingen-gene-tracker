<?php

namespace App\Http\Middleware;

use Closure;

class LogResponsesSent
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
        $response = $next($request);

        logDebug(__METHOD__.': sending response', $response->getContent()->toArray());

        return $response;
    }
}

<?php

namespace Darkness\Repository\Middleware;

use Closure;

class TagForRequestMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $request->tag = uniqid('request_') . uniqid('-');
        $response = $next($request);
        \Darkness\Repository\Cache\FlushCache::request($request);
        return $response;
    }
}

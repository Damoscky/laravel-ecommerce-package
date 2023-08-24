<?php

namespace SbscPackages\Authentication\Http\Middleware;

use Closure;

class CoreMiddleware
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
        if (!auth()->user()) {
            return response()->json([
                "success" => false,
                "message" => "Unauthenticated :("
            ], 403);
        }
        return $next($request);
    }
}

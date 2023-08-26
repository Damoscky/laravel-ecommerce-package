<?php

namespace SbscPackage\Ecommerce\Http\Middleware;

use Closure;

class EcommerceAdminMiddleware
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
        if (auth()->user()->roles[0]->slug != "ecommerceadmin") {
            return response()->json([
                "success" => false,
                "message" => "Access Denied :("
            ], 403);
        }
        return $next($request);
    }
}

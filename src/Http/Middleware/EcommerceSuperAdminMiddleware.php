<?php

namespace SbscPackage\Ecommerce\Http\Middleware;

use Closure;

class EcommerceSuperAdminMiddleware
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
        if (!auth()->user()->hasRole(["ecommercesuperadmin", "ecommerceadmin"])) {
            return response()->json([
                "success" => false,
                "message" => "Access Denied :("
            ], 401);
        }
        return $next($request);
    }
}

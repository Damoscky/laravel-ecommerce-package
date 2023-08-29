<?php

namespace SbscPackage\Ecommerce\Http\Middleware;

use Closure;

class EcommerceCustomerMiddleware
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
        if (auth()->user()->roles[0]->slug != "ecommercecustomer") {
            return response()->json([
                "success" => false,
                "message" => "Access Denied :("
            ], 401);
        }
        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiAuth
{
    /**
     * Handle an incoming request.
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $key = env('API_KEY');

        if($request->get('api_key') != $key){
            return response()->json([
                'success' => false,
                'message' => 'key not matched'
            ], 401);
        } else {
            return $next($request);
        }
    }
}

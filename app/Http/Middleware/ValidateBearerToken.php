<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ValidateBearerToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $providedToken = $request->header('Authorization');

        if (empty($providedToken) || !str_starts_with($providedToken, 'Bearer ')) {
            return response()->json('Unauthorized', 401);
        }

        $token = explode(' ', $providedToken)[1];

        if ($token !== env('API_BEARER_TOKEN')) {
            return response()->json('Unauthorized', 401);
        }

        return $next($request);
    }
}

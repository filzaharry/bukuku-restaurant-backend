<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Facades\JWTAuth;

class JwtResponse
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */

    public function handle($request, Closure $next)
    {
        try {
            // Use JWTAuth facade to authenticate the token
            $user = JWTAuth::parseToken()->authenticate();
        } catch (TokenExpiredException $e) {
            return response()->json([
                'statusCode' => 401,
                'message' => 'Token expired',
                'data' => null
            ], 401);
        } catch (TokenInvalidException $e) {
            return response()->json([
                'statusCode' => 401,
                'message' => 'Token invalid',
                'data' => null
            ], 401);
        } catch (JWTException $e) {
            return response()->json([
                'statusCode' => 401,
                'message' => 'Unauthorized',
                'data' => null
            ], 401);
        }

        return $next($request);
    }
}

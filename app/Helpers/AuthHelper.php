<?php

namespace App\Helpers;

use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Exception;

class AuthHelper
{
    public static function requireAuth()
{
    if (!JWTAuth::parser()->setRequest(request())->hasToken()) {
        return ResponseHelper::jsonResponse(401, 'Token not provided');
    }

    try {
        $user = JWTAuth::parseToken()->authenticate();

        if (!$user) {
            return ResponseHelper::jsonResponse(401, 'Unauthorized');
        }

        return $user;

    } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
        return ResponseHelper::jsonResponse(401, 'Token expired');
    } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
        return ResponseHelper::jsonResponse(401, 'Token invalid');
    } catch (\Exception $e) {
        return ResponseHelper::jsonResponse(401, 'Unauthorized');
    }
}

    public static function getAuthUserId()
    {
        return self::requireAuth()->id;
    }
}

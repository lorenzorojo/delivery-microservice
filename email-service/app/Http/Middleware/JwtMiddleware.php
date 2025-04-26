<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Firebase\JWT\ExpiredException;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\TokenBlacklistedException;

class JwtMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next): JsonResponse
    {
        $request->headers->set('Accept', 'application/json');
        $token = $request->header('Authorization');
        if (!$token) {
            return response()->json(['error' => 'Token not provided'], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $token = str_replace('Bearer ', '', $token);
            JWT::decode($token, new Key(config('jwt.secret'), 'HS256'));
            $response = $next($request);
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        } catch (ExpiredException|TokenBlacklistedException|TokenInvalidException|Exception $e) {
            Log::error('Invalid token' . $e->getMessage());
            return response()->json(['error' => 'Invalid token' . $e->getMessage()], Response::HTTP_UNAUTHORIZED);
        }
    }
}

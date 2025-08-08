<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApi
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['error' => 'Token required'], 401);
        }

        $user = \App\Models\User::where('api_token', $token)->first();

        if (!$user) {
            return response()->json(['error' => 'Invalid token'], 401);
        }

        auth()->setUser($user);

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RatingMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $ip = $request->ip();
        $userAgent = $request->userAgent();
        $endpoint = $request->path();
        $method = $request->method();

        // Log les informations de rating limit
        Log::info('Rating Limit Check', [
            'user_id' => $user ? $user->id : null,
            'user_role' => $user ? $user->role : 'guest',
            'ip' => $ip,
            'user_agent' => $userAgent,
            'endpoint' => $endpoint,
            'method' => $method,
            'timestamp' => now()->toISOString(),
        ]);

        $response = $next($request);

        // Log la réponse
        Log::info('Rating Limit Response', [
            'user_id' => $user ? $user->id : null,
            'status_code' => $response->getStatusCode(),
            'endpoint' => $endpoint,
            'timestamp' => now()->toISOString(),
        ]);

        return $response;
    }
}
